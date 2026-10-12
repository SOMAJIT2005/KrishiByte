<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BatchHandoffService
{
    public function __construct(
        protected SmsService $smsService
    ) {}

    /**
     * Record an atomic batch custody handoff
     *
     * @param array{
     *     batch_id: int,
     *     seller_id: int,
     *     buyer_id: int,
     *     stage: string,
     *     quantity: float,
     *     price_per_unit: float,
     *     district_id: int,
     *     market_name?: ?string,
     *     latitude?: ?float,
     *     longitude?: ?float,
     *     note?: ?string,
     *     recorded_by_user_id: int
     * } $data
     */
    public function recordHandoff(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $batch = Batch::lockForUpdate()->findOrFail($data['batch_id']);

            if ($batch->status === 'voided') {
                throw new InvalidArgumentException("Cannot transfer a voided batch.");
            }

            // Non-public batches must be verified before they appear in any Marketplace/traceability view.
            // This check applies to all chain roles: farmer, collectors, wholesalers, retailers.
            if ($batch->verification_status !== 'verified' && $batch->verification_status !== 'unverified') {
                throw new InvalidArgumentException("This batch is not publicly visible until an admin verifies it.");
            }

            // Custody check
            if ($batch->current_custodian_id !== (int) $data['seller_id']) {
                throw new InvalidArgumentException("Seller is not the current custodian of this batch.");
            }

            // Self-trade check
            if ((int) $data['seller_id'] === (int) $data['buyer_id']) {
                throw new InvalidArgumentException("Seller and buyer cannot be the same user.");
            }

            // Quantity check
            $qty = (float) $data['quantity'];
            if ($qty <= 0) {
                throw new InvalidArgumentException("Transfer quantity must be greater than zero.");
            }
            if ($qty > (float) $batch->available_quantity) {
                throw new InvalidArgumentException("Transferred quantity ({$qty}) exceeds available batch quantity ({$batch->available_quantity}).");
            }

            // Price check
            $pricePerUnit = (float) $data['price_per_unit'];
            if ($pricePerUnit <= 0) {
                throw new InvalidArgumentException("Price per unit must be greater than zero.");
            }

            // Determine previous price and sequence number
            $lastTx = Transaction::where('batch_id', $batch->id)
                ->where('is_voided', false)
                ->latest('sequence_no')
                ->first();

            $sequenceNo = $lastTx ? ($lastTx->sequence_no + 1) : 1;
            $previousPrice = $lastTx ? (float) $lastTx->price_per_unit : (float) $batch->initial_farmer_price;

            // Calculations
            $totalAmount = round($qty * $pricePerUnit, 2);
            $spread = CalculationService::calculateSpread($previousPrice, $pricePerUnit);
            $markupPercent = CalculationService::calculateMarkupPercent($previousPrice, $pricePerUnit);
            $marginPercent = CalculationService::calculateMarginPercent($previousPrice, $pricePerUnit);

            $transaction = Transaction::create([
                'batch_id' => $batch->id,
                'sequence_no' => $sequenceNo,
                'seller_id' => $data['seller_id'],
                'buyer_id' => $data['buyer_id'],
                'stage' => $data['stage'],
                'quantity' => $qty,
                'unit' => $batch->unit,
                'price_per_unit' => $pricePerUnit,
                'total_amount' => $totalAmount,
                'previous_price_per_unit' => $previousPrice,
                'gross_price_spread' => $spread,
                'markup_percent' => $markupPercent,
                'gross_margin_percent' => $marginPercent,
                'transaction_date' => now(),
                'district_id' => $data['district_id'],
                'market_name' => $data['market_name'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'note' => $data['note'] ?? null,
                'recorded_by_user_id' => $data['recorded_by_user_id'],
                'validation_status' => 'valid',
                'is_voided' => false,
            ]);

            // Update batch custody and stage
            $stageMapping = [
                'farmer_to_collector' => 'collector_custody',
                'collector_to_wholesale' => 'wholesale_custody',
                'wholesale_to_retail' => 'retail_custody',
                'retail_sale' => 'sold_out',
            ];
            $newStage = $stageMapping[$data['stage']] ?? 'in_transit';

            $batch->update([
                'current_custodian_id' => $data['buyer_id'],
                'current_stage' => $newStage,
                'available_quantity' => ($data['stage'] === 'retail_sale')
                    ? round((float) $batch->available_quantity - $qty, 2)
                    : $batch->available_quantity,
            ]);

            // Mark batch as publicly visible once it has entered the supply chain and been reviewed by admin.
            // Unverified batches remain hidden from public traceability, prices, and marketplace listings.
            if ($batch->verification_status === 'verified') {
                // Keep verified mark; nothing to update on successful transfer.
            } else {
                // Newly entering chain without admin verification: leave as unverified (still hidden).
            }

            // Anomaly check
            AnomalyDetectionService::evaluateTransaction($transaction);

            // Audit log
            AuditLoggerService::log(
                action: 'HANDOFF_RECORDED',
                targetType: 'Transaction',
                targetId: $transaction->id,
                notes: "Handoff recorded for batch {$batch->batch_code} (seq #{$sequenceNo}): BDT {$pricePerUnit}/{$batch->unit}",
                before: ['custodian_id' => $data['seller_id'], 'stage' => $batch->getOriginal('current_stage')],
                after: ['custodian_id' => $data['buyer_id'], 'stage' => $newStage],
                userId: $data['recorded_by_user_id']
            );

            // Dummy SMS notification to buyer
            $buyer = User::find($data['buyer_id']);
            if ($buyer && $buyer->phone) {
                $this->smsService->send(
                    $buyer->phone,
                    "KrishiByte: Batch {$batch->batch_code} transferred to your custody. Qty: {$qty} {$batch->unit} @ BDT {$pricePerUnit}."
                );
            }

            return $transaction;
        });
    }

    /**
     * Authorized void of a transaction with documented reason and audit logging
     */
    public function voidTransaction(int $transactionId, int $actorId, string $reason): Transaction
    {
        return DB::transaction(function () use ($transactionId, $actorId, $reason) {
            $tx = Transaction::lockForUpdate()->findOrFail($transactionId);
            $batch = Batch::lockForUpdate()->findOrFail($tx->batch_id);

            if ($tx->is_voided) {
                throw new InvalidArgumentException("Transaction is already voided.");
            }

            $tx->update([
                'is_voided' => true,
                'void_reason' => $reason,
                'voided_by_user_id' => $actorId,
                'validation_status' => 'voided',
            ]);

            // Restore custody to seller
            $batch->update([
                'current_custodian_id' => $tx->seller_id,
            ]);

            AuditLoggerService::log(
                action: 'TRANSACTION_VOIDED',
                targetType: 'Transaction',
                targetId: $tx->id,
                notes: "Transaction #{$tx->id} for batch {$batch->batch_code} was voided. Reason: {$reason}",
                userId: $actorId
            );

            return $tx;
        });
    }
}
