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

            if ($batch->status !== 'active' || (float) $batch->available_quantity <= 0) {
                throw new InvalidArgumentException('Only an active batch with available stock can be transferred.');
            }

            // Non-public batches must be verified before they appear in any Marketplace/traceability view.
            // This check applies to all chain roles: farmer, collectors, wholesalers, retailers.
            if ($batch->verification_status !== 'verified' && $batch->verification_status !== 'unverified') {
                throw new InvalidArgumentException('This batch is not publicly visible until an admin verifies it.');
            }

            // Custody check
            if ($batch->current_custodian_id !== (int) $data['seller_id']) {
                throw new InvalidArgumentException('Seller is not the current custodian of this batch.');
            }

            // Self-trade check
            if ((int) $data['seller_id'] === (int) $data['buyer_id']) {
                throw new InvalidArgumentException('Seller and buyer cannot be the same user.');
            }

            // Quantity check
            $qty = (float) $data['quantity'];
            if (! is_finite($qty) || round($qty, 2) !== $qty) {
                throw new InvalidArgumentException('Quantity must have at most two decimal places.');
            }
            if ($qty <= 0) {
                throw new InvalidArgumentException('Transfer quantity must be greater than zero.');
            }
            if ($qty > (float) $batch->available_quantity) {
                throw new InvalidArgumentException("Transferred quantity ({$qty}) exceeds available batch quantity ({$batch->available_quantity}).");
            }

            // Price check
            $pricePerUnit = (float) $data['price_per_unit'];
            if (! is_finite($pricePerUnit) || round($pricePerUnit, 2) !== $pricePerUnit) {
                throw new InvalidArgumentException('Price must have at most two decimal places.');
            }
            if ($pricePerUnit <= 0) {
                throw new InvalidArgumentException('Price per unit must be greater than zero.');
            }

            $seller = User::findOrFail($data['seller_id']);
            $buyer = User::findOrFail($data['buyer_id']);
            $actor = User::findOrFail($data['recorded_by_user_id']);
            if ($seller->account_status !== 'active' || $buyer->account_status !== 'active') {
                throw new InvalidArgumentException('Both participants must have active accounts.');
            }
            if (! $actor->isAdmin() && $actor->id !== $seller->id && ! ($seller->role === 'farmer' && $actor->id === $buyer->id && $buyer->role === 'collector')) {
                throw new InvalidArgumentException('Only the custodian can record this transfer.');
            }
            $allowed = match ($seller->role) {
                'farmer' => ['collector'],
                'collector', 'wholesale' => ['collector', 'wholesale', 'retail'],
                'retail' => ['consumer'],
                default => [],
            };
            if (! in_array($buyer->role, $allowed, true)) {
                throw new InvalidArgumentException('This recipient role is not eligible for the next handoff.');
            }
            $data['stage'] = $seller->role === 'farmer' ? 'farmer_to_collector' : ($seller->role === 'retail' ? 'retail_sale' : $seller->role.'_to_'.$buyer->role);
            // A batch has one custodian. Whole-lot transfers preserve quantity; retail sales consume stock.
            if ($data['stage'] !== 'retail_sale' && bccomp((string) $qty, (string) $batch->available_quantity, 2) !== 0) {
                throw new InvalidArgumentException('Transfer the entire available lot. Separate harvest lots into batches before collection.');
            }
            $before = ['custodian_id' => $batch->current_custodian_id, 'stage' => $batch->current_stage];

            // Determine previous price and sequence number
            $lastTx = Transaction::where('batch_id', $batch->id)
                ->where('is_voided', false)
                ->latest('sequence_no')
                ->first();

            $sequenceNo = (int) Transaction::where('batch_id', $batch->id)->max('sequence_no') + 1;
            $acquisition = Transaction::where('batch_id', $batch->id)->where('is_voided', false)->where('stage', '!=', 'retail_sale')->reorder()->latest('sequence_no')->first();
            $previousPrice = $acquisition ? (float) $acquisition->price_per_unit : (float) $batch->initial_farmer_price;

            // Calculations
            $totalAmount = round((float) bcmul((string) $qty, (string) $pricePerUnit, 4), 2);
            if ($totalAmount > 9999999999.99) {
                throw new InvalidArgumentException('Transaction amount exceeds the supported limit.');
            }
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

            $remaining = $data['stage'] === 'retail_sale' ? bcsub((string) $batch->available_quantity, (string) $qty, 2) : $batch->available_quantity;
            $newStage = $data['stage'] === 'retail_sale' ? ((float) $remaining === 0.0 ? 'sold_out' : 'retail_custody') : $buyer->role.'_custody';
            $batch->update([
                'current_custodian_id' => $data['stage'] === 'retail_sale' ? $seller->id : $buyer->id,
                'current_stage' => $newStage,
                'available_quantity' => $remaining,
                'status' => (float) $remaining === 0.0 ? 'completed' : 'active',
            ]);

            // Anomaly check
            AnomalyDetectionService::evaluateTransaction($transaction);

            // Audit log
            AuditLoggerService::log(
                action: 'HANDOFF_RECORDED',
                targetType: 'Transaction',
                targetId: $transaction->id,
                notes: "Handoff recorded for batch {$batch->batch_code} (seq #{$sequenceNo}): BDT {$pricePerUnit}/{$batch->unit}",
                before: $before,
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
            $batchId = Transaction::findOrFail($transactionId)->batch_id;
            $batch = Batch::lockForUpdate()->findOrFail($batchId);
            $tx = Transaction::lockForUpdate()->findOrFail($transactionId);

            if ($tx->is_voided) {
                throw new InvalidArgumentException('Transaction is already voided.');
            }

            $actor = User::findOrFail($actorId);
            if ((! $actor->isAdmin() && $tx->recorded_by_user_id !== $actorId) || mb_strlen(trim($reason)) < 5) {
                throw new InvalidArgumentException('An authorized actor and correction reason are required.');
            }
            if (Transaction::where('batch_id', $batch->id)->where('is_voided', false)->where('sequence_no', '>', $tx->sequence_no)->exists()) {
                throw new InvalidArgumentException('Only the latest active transaction can be voided. Reverse later handoffs first.');
            }
            $tx->update([
                'is_voided' => true,
                'void_reason' => $reason,
                'voided_by_user_id' => $actorId,
                'validation_status' => 'voided',
            ]);
            $tx->anomalyFlags()->update(['status' => 'cleared', 'reviewer_notes' => 'Source transaction voided: '.$reason, 'reviewed_by_user_id' => $actorId]);

            // Restore custody to seller
            $batch->update([
                'current_custodian_id' => $tx->seller_id,
                'current_stage' => $tx->seller->role === 'farmer' ? 'farm_gate' : $tx->seller->role.'_custody',
                'available_quantity' => $tx->stage === 'retail_sale' ? bcadd((string) $batch->available_quantity, (string) $tx->quantity, 2) : $batch->available_quantity,
                'status' => 'active',
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
