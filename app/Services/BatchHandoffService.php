<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\FarmPurchase;
use App\Models\HandoffProposal;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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

            $data = $this->validateTerms($batch, $data);
            $qty = $data['quantity'];
            $pricePerUnit = $data['price_per_unit'];
            $seller = User::findOrFail($data['seller_id']);
            $buyer = isset($data['buyer_id']) ? User::findOrFail($data['buyer_id']) : null;
            $legacy = ($data['_legacy_import'] ?? false) && app()->runningInConsole();
            $farmPurchase = null;
            if (! $legacy && isset($data['_farm_purchase_id'])) {
                $farmPurchase = FarmPurchase::lockForUpdate()->findOrFail($data['_farm_purchase_id']);
                if ($data['stage'] !== 'farmer_to_collector' || $farmPurchase->transaction_id !== null || $farmPurchase->collector_id !== (int) $data['recorded_by_user_id'] || $farmPurchase->farmer_id !== (int) $data['seller_id'] || $farmPurchase->batch_id !== $batch->id || $this->terms($farmPurchase->terms) !== $this->terms($data) || ! Storage::disk('local')->exists($farmPurchase->evidence_path)) {
                    throw new InvalidArgumentException('A matching private farmer agreement is required.');
                }
            }
            if (! $legacy && ! $farmPurchase && $data['stage'] !== 'retail_sale') {
                $proposal = HandoffProposal::lockForUpdate()->find($data['_proposal_id'] ?? 0);
                if (! $proposal || $proposal->status !== 'accepted' || $proposal->transaction_id !== null || $this->terms($proposal->terms) !== $this->terms($data)) {
                    throw new InvalidArgumentException('Counterparty acceptance is required before custody changes.');
                }
            }
            if (! $legacy && $data['stage'] === 'retail_sale') {
                if (empty($data['request_key'])) {
                    throw new InvalidArgumentException('An idempotency request key is required.');
                }
                if (HandoffProposal::where('batch_id', $batch->id)->where('status', 'proposed')->where('expires_at', '>', now())->exists()) {
                    throw new InvalidArgumentException('A pending custody proposal reserves this lot.');
                }
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
                'confirmation_status' => $legacy ? 'legacy_unconfirmed' : ($farmPurchase ? 'collector_attested' : ($data['stage'] === 'retail_sale' ? 'retail_recorded' : 'counterparty_accepted')),
                'confirmed_by_user_id' => $data['_confirmed_by'] ?? null,
                'request_key' => $data['request_key'] ?? null,
                'request_hash' => isset($data['request_key']) ? hash('sha256', json_encode($this->terms($data))) : null,
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
                action: $data['stage'] === 'retail_sale' ? 'RETAIL_SALE_RECORDED' : 'HANDOFF_RECORDED',
                targetType: 'Transaction',
                targetId: $transaction->id,
                notes: "Handoff recorded for batch {$batch->batch_code} (seq #{$sequenceNo}): BDT {$pricePerUnit}/{$batch->unit}",
                before: $before,
                after: ['custodian_id' => $batch->current_custodian_id, 'buyer_id' => $data['buyer_id'] ?? null, 'stage' => $newStage, 'available_quantity' => $remaining, 'event_type' => $data['stage'] === 'retail_sale' ? 'retail_sale' : 'custody_transfer'],
                userId: $data['recorded_by_user_id']
            );

            if ($buyer?->phone) {
                $message = $data['stage'] === 'retail_sale'
                    ? "KrishiByte: Retail purchase recorded for {$batch->batch_code}: {$qty} {$batch->unit} @ BDT {$pricePerUnit}. Payment is not verified."
                    : "KrishiByte: Custody recorded ({$transaction->confirmation_status}) for {$batch->batch_code}: {$qty} {$batch->unit} @ BDT {$pricePerUnit}. Payment is not verified.";
                DB::afterCommit(fn () => $this->smsService->send($buyer->phone, $message));
            }

            return $transaction;
        });
    }

    private function validateTerms(Batch $batch, array $data): array
    {
        if ($batch->status !== 'active' || (float) $batch->available_quantity <= 0) {
            throw new InvalidArgumentException('Only an active batch with available stock can be transferred.');
        }

        // Pilot policy: participant-entered unreviewed lots may trade; rejected lots may not.
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
        $buyer = isset($data['buyer_id']) ? User::findOrFail($data['buyer_id']) : null;
        $actor = User::findOrFail($data['recorded_by_user_id']);
        if ($actor->account_status !== 'active' || $seller->account_status !== 'active' || ($buyer && $buyer->account_status !== 'active')) {
            throw new InvalidArgumentException('Both participants must have active accounts.');
        }
        if ($actor->id !== $seller->id && ! ($seller->role === 'farmer' && $actor->id === $buyer?->id && $buyer?->role === 'collector')) {
            throw new InvalidArgumentException('Only the custodian can record this transfer.');
        }
        $allowed = match ($seller->role) {
            'farmer' => ['collector'],
            'collector', 'wholesale' => ['collector', 'wholesale', 'retail'],
            'retail' => ['consumer'],
            default => [],
        };
        if (! in_array($buyer?->role ?? 'consumer', $allowed, true) || (! $buyer && $seller->role !== 'retail')) {
            throw new InvalidArgumentException('This recipient role is not eligible for the next handoff.');
        }
        $data['stage'] = $seller->role === 'farmer' ? 'farmer_to_collector' : ($seller->role === 'retail' ? 'retail_sale' : $seller->role.'_to_'.$buyer->role);
        // A batch has one custodian. Whole-lot transfers preserve quantity; retail sales consume stock.
        if ($data['stage'] !== 'retail_sale' && bccomp((string) $qty, (string) $batch->available_quantity, 2) !== 0) {
            throw new InvalidArgumentException('Transfer the entire available lot. Separate harvest lots into batches before collection.');
        }
        if ($batch->unit_needs_review || ! in_array($batch->unit, ['kg', 'mon', 'sack', 'piece'], true)) {
            throw new InvalidArgumentException('The lot unit requires review before trading.');
        }
        if (isset($data['unit']) && $data['unit'] !== $batch->unit) {
            throw new InvalidArgumentException('Batch unit changed after terms were recorded. Cancel and create a new proposal.');
        }

        return $data;
    }

    private function terms(array $data): array
    {
        $keys = ['batch_id', 'seller_id', 'buyer_id', 'quantity', 'price_per_unit', 'district_id', 'market_name', 'latitude', 'longitude', 'note', 'recorded_by_user_id'];
        $terms = array_intersect_key($data, array_flip($keys));
        foreach (['batch_id', 'seller_id', 'buyer_id', 'district_id', 'recorded_by_user_id'] as $key) {
            if (isset($terms[$key])) {
                $terms[$key] = (int) $terms[$key];
            }
        }
        foreach (['quantity', 'price_per_unit'] as $key) {
            $terms[$key] = number_format((float) $terms[$key], 2, '.', '');
        }
        foreach (['market_name', 'latitude', 'longitude', 'note'] as $key) {
            $terms[$key] = $terms[$key] ?? null;
        }
        ksort($terms);

        return $terms;
    }

    public function propose(array $data, string $requestKey): HandoffProposal
    {
        return DB::transaction(function () use ($data, $requestKey) {
            $batch = Batch::lockForUpdate()->findOrFail($data['batch_id']);
            $terms = $this->terms($data);
            $hash = hash('sha256', json_encode($terms));
            $prior = HandoffProposal::where('proposed_by_user_id', $data['recorded_by_user_id'])->where('request_key', $requestKey)->first();
            if ($prior) {
                if ($prior->request_hash !== $hash) {
                    throw new InvalidArgumentException('Request key was already used with different terms.');
                }

                return $prior;
            }
            $validated = $this->validateTerms($batch, $data);
            if ($validated['stage'] === 'retail_sale') {
                throw new InvalidArgumentException('Retail sales are recorded separately and do not claim customer acceptance.');
            }
            HandoffProposal::where('batch_id', $batch->id)->where('status', 'proposed')->where('expires_at', '<=', now())->update(['status' => 'expired', 'decided_at' => now()]);
            if (HandoffProposal::where('batch_id', $batch->id)->where('status', 'proposed')->exists()) {
                throw new InvalidArgumentException('This lot already has an active proposal.');
            }
            $proposal = HandoffProposal::create(['batch_id' => $batch->id, 'proposed_by_user_id' => $data['recorded_by_user_id'], 'seller_id' => $data['seller_id'], 'buyer_id' => $data['buyer_id'], 'request_key' => $requestKey, 'request_hash' => $hash, 'terms' => $terms + ['unit' => $batch->unit], 'expires_at' => now()->addHours(48)]);
            AuditLoggerService::log(action: 'HANDOFF_PROPOSED', targetType: 'HandoffProposal', targetId: $proposal->id, after: $terms, userId: $proposal->proposed_by_user_id);

            return $proposal;
        });
    }

    public function decide(int $id, int $actorId, string $decision, ?string $reason = null): HandoffProposal
    {
        return DB::transaction(function () use ($id, $actorId, $decision, $reason) {
            $batchId = HandoffProposal::findOrFail($id)->batch_id;
            Batch::lockForUpdate()->findOrFail($batchId);
            $proposal = HandoffProposal::lockForUpdate()->findOrFail($id);
            $actor = User::findOrFail($actorId);
            $counterparty = $proposal->proposed_by_user_id === $proposal->seller_id ? $proposal->buyer_id : $proposal->seller_id;
            if ($actor->account_status !== 'active' || ($decision === 'cancelled' ? $actorId !== $proposal->proposed_by_user_id : $actorId !== $counterparty)) {
                throw new InvalidArgumentException('Only the intended participant can make this decision.');
            }
            if (! in_array($decision, ['accepted', 'disputed', 'cancelled'], true)) {
                throw new InvalidArgumentException('Unsupported decision.');
            }
            if ($decision !== 'accepted' && mb_strlen(trim($reason ?? '')) < 5) {
                throw new InvalidArgumentException('A decision reason is required.');
            }
            if ($proposal->status === $decision) {
                return $proposal;
            }
            if ($proposal->status !== 'proposed') {
                throw new InvalidArgumentException('This proposal is already closed.');
            }
            if ($proposal->expires_at->isPast()) {
                $proposal->update(['status' => 'expired', 'decided_at' => now()]);

                return $proposal;
            }
            $proposal->update(['status' => $decision, 'decision_reason' => $reason, 'decided_at' => now()]);
            if ($decision === 'accepted') {
                $tx = $this->recordHandoff($proposal->terms + ['_proposal_id' => $proposal->id, '_confirmed_by' => $actorId]);
                $proposal->update(['transaction_id' => $tx->id]);
            }
            AuditLoggerService::log(action: 'HANDOFF_'.strtoupper($decision), targetType: 'HandoffProposal', targetId: $id, notes: $reason, after: ['status' => $proposal->status, 'transaction_id' => $proposal->transaction_id], userId: $actorId);

            return $proposal;
        });
    }

    public function recordRetailSale(array $data, string $requestKey): Transaction
    {
        return DB::transaction(function () use ($data, $requestKey) {
            $batch = Batch::lockForUpdate()->findOrFail($data['batch_id']);
            $actor = User::findOrFail($data['recorded_by_user_id']);
            if ($actor->role !== 'retail' || $actor->account_status !== 'active' || $actor->id !== (int) $data['seller_id']) {
                throw new InvalidArgumentException('Only the active retailer can record a retail sale.');
            }
            $hash = hash('sha256', json_encode($this->terms($data)));
            $prior = Transaction::where('recorded_by_user_id', $actor->id)->where('request_key', $requestKey)->first();
            if ($prior) {
                if ($prior->request_hash !== $hash) {
                    throw new InvalidArgumentException('Request key was already used with different terms.');
                }

                return $prior;
            }

            return $this->recordHandoff($data + ['request_key' => $requestKey]);
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
            if ($actor->account_status !== 'active' || (! $actor->isAdmin() && $tx->recorded_by_user_id !== $actorId) || mb_strlen(trim($reason)) < 5) {
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
