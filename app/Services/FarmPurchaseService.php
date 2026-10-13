<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Crop;
use App\Models\FarmPurchase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FarmPurchaseService
{
    public function record(array $data, array $evidence, int $collectorId): FarmPurchase
    {
        return DB::transaction(function () use ($data, $evidence, $collectorId) {
            $collector = User::lockForUpdate()->findOrFail($collectorId);
            if (! $collector->isCollector() || $collector->account_status !== 'active') {
                throw new InvalidArgumentException('Only active collectors can record farmer purchases.');
            }
            $hash = hash('sha256', json_encode($data + ['evidence_sha256' => $evidence['evidence_original_sha256']]));
            // Serialize requests from one collector, including creation of a new batch.
            $prior = FarmPurchase::where('collector_id', $collectorId)->where('request_key', $data['request_key'])->first();
            if ($prior) {
                if ($prior->request_hash !== $hash) {
                    throw new InvalidArgumentException('Request key was already used with different purchase terms.');
                }

                return $prior;
            }
            $farmer = User::where('role', 'farmer')->where('account_status', 'active')->where('farmer_profile_public', true)->lockForUpdate()->findOrFail($data['farmer_id']);
            $crop = Crop::where('is_active', true)->where('unit_needs_review', false)->findOrFail($data['crop_id']);
            if (! in_array($crop->unit, ['kg', 'mon', 'sack', 'piece'], true)) {
                throw new InvalidArgumentException('Crop unit needs review.');
            }
            if (! empty($data['batch_id'])) {
                $batch = Batch::lockForUpdate()->findOrFail($data['batch_id']);
                if ($batch->farmer_id !== $farmer->id || $batch->crop_id !== $crop->id || $batch->current_custodian_id !== $farmer->id) {
                    throw new InvalidArgumentException('Batch does not belong to this farmer at the farm gate.');
                }
            } else {
                $batch = Batch::create(['batch_code' => Batch::generateBatchCode(), 'crop_id' => $crop->id, 'farmer_id' => $farmer->id, 'origin_district_id' => $farmer->district_id ?? $data['district_id'], 'harvest_date' => $data['harvest_date'], 'initial_quantity' => $data['quantity'], 'available_quantity' => $data['quantity'], 'unit' => $crop->unit, 'initial_farmer_price' => $data['price_per_unit'], 'current_custodian_id' => $farmer->id, 'current_stage' => 'farm_gate', 'status' => 'active', 'verification_status' => 'unverified', 'notes' => 'Collector-entered harvest lot; handwritten agreement retained privately.']);
            }
            if ($batch->handoffProposals()->where('status', 'proposed')->where('expires_at', '>', now())->exists()) {
                throw new InvalidArgumentException('Cancel the existing proposal before recording an agreement.');
            }
            $terms = ['batch_id' => $batch->id, 'seller_id' => $farmer->id, 'buyer_id' => $collectorId, 'quantity' => $data['quantity'], 'price_per_unit' => $data['price_per_unit'], 'district_id' => $data['district_id'], 'market_name' => $data['market_name'] ?? null, 'recorded_by_user_id' => $collectorId, 'note' => 'Collector attested farmer agreement; payment not verified.'];
            $terms['unit'] = $batch->unit;
            $purchase = FarmPurchase::create(['collector_id' => $collectorId, 'farmer_id' => $farmer->id, 'batch_id' => $batch->id, 'request_key' => $data['request_key'], 'request_hash' => $hash, 'terms' => $terms, 'witness_name' => $data['witness_name'], ...$evidence]);
            $tx = app(BatchHandoffService::class)->recordHandoff($terms + ['_farm_purchase_id' => $purchase->id, '_confirmed_by' => $collectorId]);
            $purchase->update(['transaction_id' => $tx->id]);
            AuditLoggerService::log(action: 'FARMER_AGREEMENT_RECORDED', targetType: 'FarmPurchase', targetId: $purchase->id, after: ['batch_id' => $batch->id, 'transaction_id' => $tx->id, 'confirmation' => 'collector_attested'], userId: $collectorId);

            return $purchase;
        });
    }
}
