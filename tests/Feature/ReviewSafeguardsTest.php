<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Crop;
use App\Models\District;
use App\Models\FarmPurchase;
use App\Models\PriceReport;
use App\Models\User;
use App\Services\BatchHandoffService;
use App\Services\EvidenceService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ReviewSafeguardsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_storage_budget_rejection_writes_no_orphan_and_releases_lock(): void
    {
        Storage::fake('local');
        config(['evidence.storage_budget_bytes' => 0]);
        $photo = UploadedFile::fake()->image('qa-budget.jpg', 50, 50);
        try {
            EvidenceService::store($photo);
            $this->fail('Budget must reject');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('evidence_photo', $e->errors());
        }
        $this->assertCount(0, Storage::disk('local')->allFiles());
        config(['evidence.storage_budget_bytes' => 512 * 1024 * 1024]);
        $evidence = EvidenceService::store($photo);
        $this->assertTrue(Storage::disk('local')->exists($evidence['evidence_path']));
        $this->assertSame(0, (int) DB::selectOne("SELECT IS_USED_LOCK('krishibyte_evidence_budget') IS NOT NULL AS held")->held);
    }

    public function test_daily_agreement_limit_rejects_upload_without_orphan(): void
    {
        Storage::fake('local');
        $collector = User::factory()->create(['role' => 'collector', 'account_status' => 'active', 'trading_approved' => true]);
        $farmer = User::where('role', 'farmer')->firstOrFail();
        $batch = Batch::where('farmer_id', $farmer->id)->firstOrFail();
        $rows = [];
        for ($i = 0; $i < 100; $i++) {
            $rows[] = ['collector_id' => $collector->id, 'farmer_id' => $farmer->id, 'batch_id' => $batch->id, 'request_key' => (string) Str::uuid(), 'request_hash' => str_repeat('a', 64), 'terms' => '{}', 'witness_name' => 'Quota-only QA fixture', 'evidence_path' => 'evidence/qa-quota-'.$i.'.jpg', 'evidence_mime' => 'image/jpeg', 'evidence_original_sha256' => str_repeat('b', 64), 'evidence_transformation' => 'Quota-only synthetic database fixture, no image', 'created_at' => now(), 'updated_at' => now()];
        }
        DB::table('farm_purchases')->insert($rows);
        $data = ['farmer_id' => $farmer->id, 'crop_id' => Crop::first()->id, 'quantity' => 10, 'price_per_unit' => 21, 'district_id' => District::first()->id, 'harvest_date' => now('Asia/Dhaka')->toDateString(), 'witness_name' => 'QA witness', 'agreement_read_aloud' => 1, 'request_key' => (string) Str::uuid(), 'agreement_photo' => UploadedFile::fake()->image('qa-limit.jpg', 50, 50)];
        $this->actingAs($collector)->post(route('vendor.farm-purchase.store'), $data)->assertRedirect()->assertSessionHas('error');
        $this->assertStringContainsString('100 farmer agreements', session('error'));
        $this->assertSame(100, FarmPurchase::where('collector_id', $collector->id)->count());
        $this->assertCount(0, Storage::disk('local')->allFiles());
    }

    public function test_assigned_reviewer_cannot_review_own_report_and_admin_details_require_scope(): void
    {
        $reviewer = User::factory()->create(['role' => 'authority', 'account_status' => 'active']);
        $report = PriceReport::firstOrFail();
        DB::table('review_assignments')->insert(['user_id' => $reviewer->id, 'district_id' => $report->district_id, 'assigned_by' => $reviewer->id, 'reason' => 'QA scope', 'created_at' => now(), 'updated_at' => now()]);
        $report->update(['reporter_user_id' => $reviewer->id]);
        $this->actingAs($reviewer)->post(route('authority.reports.moderate', $report->id), ['status' => 'verified', 'moderation_notes' => 'Self-review must be blocked'])->assertForbidden();
        $admin = User::factory()->create(['role' => 'admin', 'account_status' => 'active']);
        $this->actingAs($admin)->get(route('admin.batches.detail', Batch::first()->id))->assertForbidden();
    }

    public function test_assisted_farmer_correction_keeps_custody_until_collector_acceptance(): void
    {
        $farmer = User::factory()->create(['role' => 'farmer', 'account_status' => 'active']);
        $collector = User::factory()->create(['role' => 'collector', 'account_status' => 'active', 'trading_approved' => true]);
        $batch = Batch::create(['batch_code' => Batch::generateBatchCode(), 'crop_id' => Crop::first()->id, 'farmer_id' => $farmer->id, 'origin_district_id' => District::first()->id, 'harvest_date' => now(), 'initial_quantity' => 10, 'available_quantity' => 10, 'unit' => 'kg', 'initial_farmer_price' => 20, 'current_custodian_id' => $farmer->id, 'current_stage' => 'farm_gate', 'status' => 'active']);
        $service = app(BatchHandoffService::class);
        $tx = $service->recordHandoff(['batch_id' => $batch->id, 'seller_id' => $farmer->id, 'buyer_id' => $collector->id, 'quantity' => 10, 'price_per_unit' => 20, 'district_id' => District::first()->id, 'recorded_by_user_id' => $collector->id, '_legacy_import' => true]);
        $data = ['request_key' => (string) Str::uuid(), 'reason' => 'Supervised farmer correction request'];
        $this->post(route('farmers.access.correction', $tx->id), $data)->assertForbidden();
        $this->withSession(['farmer_access_id' => $farmer->id, 'farmer_access_expires' => time() + 3600])->post(route('farmers.access.correction', $tx->id), $data)->assertRedirect()->assertSessionHas('success');
        $correction = DB::table('correction_requests')->where('transaction_id', $tx->id)->first();
        $this->assertSame($farmer->id, $correction->requested_by);
        $this->assertSame($collector->id, $batch->fresh()->current_custodian_id);
        $this->actingAs($collector)->post(route('corrections.decide', $correction->id), ['decision' => 'accepted', 'reason' => 'Agreed produce returned to farmer'])->assertRedirect();
        $this->assertSame($farmer->id,$batch->fresh()->current_custodian_id);
    }
}
