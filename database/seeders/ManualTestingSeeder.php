<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Crop;
use App\Models\District;
use App\Models\User;
use App\Services\BatchHandoffService;
use App\Services\CorrectionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ManualTestingSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Manual fixtures are restricted to local/test environments.');
        }
        $this->call([DatabaseSeeder::class, ShowcaseScenarioSeeder::class, DemoPriceHistorySeeder::class, MarketplaceShowcaseSeeder::class]);
        $farmer = User::where('email', 'farmer.rahim@krishibyte.local')->firstOrFail();
        $collector = User::where('email', 'collector.karim@krishibyte.local')->firstOrFail();
        $wholesale = User::where('email', 'wholesale.hasan@krishibyte.local')->firstOrFail();
        $admin = User::where('email', 'admin@krishibyte.local')->firstOrFail();
        $district = District::where('name', 'Bogura')->firstOrFail();
        $crop = Crop::where('name', 'like', 'Potato (%')->firstOrFail();

        foreach (['pending' => 'active', 'suspended' => 'suspended', 'verified' => 'active'] as $scenario => $accountStatus) {
            User::firstOrCreate(['email' => 'manual.'.$scenario.'@example.test'], ['name' => 'Synthetic '.$scenario.' collector', 'role' => 'collector', 'account_status' => $accountStatus, 'trading_approved' => $scenario === 'verified', 'district_id' => $district->id, 'nid_or_trade_license' => 'SYNTHETIC-MANUAL-'.$scenario, 'password' => 'ManualTest123!', 'is_verified' => $scenario === 'verified', 'verified_by_user_id' => $scenario === 'verified' ? $admin->id : null, 'verification_date' => $scenario === 'verified' ? now() : null, 'verification_reason' => $scenario === 'verified' ? 'Synthetic manual-test identity, not certification' : null]);
        }
        foreach (['pending', 'disputed', 'cancelled', 'accepted'] as $decision) {
            $code = 'KB-MANUAL-CORRECTION-'.strtoupper($decision);
            if (Batch::where('batch_code', $code)->exists()) {
                continue; // Never reset decisions performed during manual testing.
            }
            DB::transaction(function () use ($code, $decision, $farmer, $collector, $wholesale, $district, $crop) {
                $batch = Batch::create(['batch_code' => $code, 'crop_id' => $crop->id, 'farmer_id' => $farmer->id, 'origin_district_id' => $district->id, 'harvest_date' => now()->toDateString(), 'initial_quantity' => 25, 'available_quantity' => 25, 'unit' => 'kg', 'initial_farmer_price' => 20, 'current_custodian_id' => $farmer->id, 'current_stage' => 'farm_gate', 'status' => 'active', 'verification_status' => 'unverified', 'notes' => 'Synthetic manual correction scenario. Original farm movement is an unconfirmed fixture import.']);
                $service = app(BatchHandoffService::class);
                $terms = ['batch_id' => $batch->id, 'seller_id' => $farmer->id, 'buyer_id' => $collector->id, 'quantity' => 25, 'price_per_unit' => 21, 'district_id' => $district->id, 'recorded_by_user_id' => $collector->id, '_legacy_import' => true];
                $service->recordHandoff($terms);
                unset($terms['_legacy_import']);
                $terms['seller_id'] = $collector->id;
                $terms['buyer_id'] = $wholesale->id;
                $terms['price_per_unit'] = 24;
                $proposal = $service->propose($terms, (string) Str::uuid());
                $accepted = $service->decide($proposal->id, $wholesale->id, 'accepted');
                $correction = app(CorrectionService::class)->request($accepted->transaction_id, $collector->id, (string) Str::uuid(), 'Synthetic manual fixture: incorrect custody transfer needs correction.');
                if ($decision !== 'pending') {
                    app(CorrectionService::class)->decide($correction, $decision === 'cancelled' ? $collector->id : $wholesale->id, $decision, 'Synthetic manual-test correction decision.');
                }
            });
        }
        foreach (['pending', 'rejected', 'approved'] as $status) {
            if (DB::table('farmer_access_requests')->where('reason', 'Synthetic manual access scenario '.$status)->exists()) {
                continue;
            }
            DB::table('farmer_access_requests')->insertOrIgnore(['farmer_id' => $farmer->id, 'name' => 'Synthetic access '.$status, 'phone' => '01700000000', 'reason' => 'Synthetic manual access scenario '.$status, 'status' => $status, 'token_hash' => $status === 'approved' ? hash('sha256', 'expired-manual-fixture-access') : null, 'expires_at' => $status === 'approved' ? now()->subDay() : null, 'reviewed_by' => $status === 'pending' ? null : $admin->id, 'verification_method' => $status === 'pending' ? null : 'Synthetic fixture only; no real contact verification', 'created_at' => now(), 'updated_at' => now()]);
        }
        foreach (['approved', 'rejected'] as $index => $status) {
            DB::table('farmer_enrollments')->insertOrIgnore(['tracking_code' => '00000000-0000-4000-8000-00000000001'.($index + 1), 'name' => 'Synthetic '.$status.' enrollment', 'phone' => '01700000000', 'district_id' => $district->id, 'grown_crop_ids' => json_encode([$crop->id]), 'status' => $status, 'farmer_id' => $status === 'approved' ? $farmer->id : null, 'reviewed_by_user_id' => $admin->id, 'review_reason' => 'Synthetic manual-test review outcome', 'consented_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        }
        // Infrastructure coverage is deliberately expired/quarantined, not live access/work.
        DB::table('cache')->insertOrIgnore(['key' => 'manual-fixture:expired', 'value' => serialize('Synthetic expired cache'), 'expiration' => 0]);
        DB::table('cache_locks')->insertOrIgnore(['key' => 'manual-fixture:expired', 'owner' => 'synthetic-owner', 'expiration' => 0]);
        DB::table('password_reset_tokens')->insertOrIgnore(['email' => 'expired.reset@example.test', 'token' => password_hash('expired-synthetic-token', PASSWORD_BCRYPT), 'created_at' => '2000-01-01 00:00:00']);
        DB::table('sessions')->insertOrIgnore(['id' => 'synthetic-expired-manual-session', 'user_id' => null, 'ip_address' => null, 'user_agent' => 'Inert synthetic fixture', 'payload' => base64_encode(serialize([])), 'last_activity' => 0]);
        $payload = json_encode(['displayName' => 'Inert synthetic fixture; do not run/retry', 'job' => 'manual-fixture-disabled', 'data' => []], JSON_THROW_ON_ERROR);
        if (! DB::table('jobs')->where('queue', 'manual-fixture-disabled')->exists()) {
            DB::table('jobs')->insert(['queue' => 'manual-fixture-disabled', 'payload' => $payload, 'attempts' => 0, 'reserved_at' => null, 'available_at' => 2147483647, 'created_at' => 1]);
        }
        DB::table('job_batches')->insertOrIgnore(['id' => 'manual-fixture-finished', 'name' => 'Synthetic finished fixture', 'total_jobs' => 0, 'pending_jobs' => 0, 'failed_jobs' => 0, 'failed_job_ids' => '[]', 'options' => serialize([]), 'cancelled_at' => 1, 'created_at' => 1, 'finished_at' => 1]);
        DB::table('failed_jobs')->insertOrIgnore(['uuid' => '00000000-0000-4000-8000-000000000099', 'connection' => 'database', 'queue' => 'manual-fixture-disabled', 'payload' => $payload, 'exception' => 'Synthetic historical failure only; not a real error. Do not retry this inert fixture.', 'failed_at' => '2000-01-01 00:00:00']);
    }
}
