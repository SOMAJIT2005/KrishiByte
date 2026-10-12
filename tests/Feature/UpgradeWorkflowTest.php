<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Crop;
use App\Models\District;
use App\Models\MarkupRule;
use App\Models\PriceObservation;
use App\Models\PriceReport;
use App\Models\User;
use App\Services\BatchHandoffService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\TestCase;

class UpgradeWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    private function person(string $role): User
    {
        return User::factory()->create(['role' => $role, 'account_status' => 'active']);
    }

    private function batch(User $farmer): Batch
    {
        return Batch::create(['batch_code' => Batch::generateBatchCode(), 'crop_id' => Crop::first()->id,
            'farmer_id' => $farmer->id, 'origin_district_id' => District::first()->id, 'harvest_date' => now(),
            'initial_quantity' => 100, 'available_quantity' => 100, 'unit' => 'kg', 'initial_farmer_price' => 20,
            'current_custodian_id' => $farmer->id, 'current_stage' => 'farm_gate', 'status' => 'active']);
    }

    private function transfer(Batch $batch, User $seller, User $buyer, float $price, float $quantity = 100)
    {
        return app(BatchHandoffService::class)->recordHandoff(['batch_id' => $batch->id, 'seller_id' => $seller->id,
            'buyer_id' => $buyer->id, 'stage' => 'auto', 'quantity' => $quantity, 'price_per_unit' => $price,
            'district_id' => District::first()->id, 'recorded_by_user_id' => $seller->id]);
    }

    public function test_multi_intermediary_chain_and_retail_stock_conservation(): void
    {
        $farmer = $this->person('farmer');
        $batch = $this->batch($farmer);
        $chain = [$farmer, $this->person('collector'), $this->person('collector'), $this->person('wholesale'), $this->person('wholesale'), $this->person('retail')];
        for ($i = 1; $i < count($chain); $i++) {
            $this->transfer($batch, $chain[$i - 1], $chain[$i], 20 + $i * 2);
        }
        $batch->refresh();
        $this->assertEquals(30, $batch->latest_price);
        $this->assertEquals($farmer->id, $batch->farmer_id);
        $this->assertEquals(100, $batch->available_quantity);
        $retail = end($chain);
        $consumer = $this->person('consumer');
        $sale = $this->transfer($batch, $retail, $consumer, 40, 25);
        $this->assertEquals(30, $sale->previous_price_per_unit);
        $this->assertEquals($retail->id, $batch->fresh()->current_custodian_id);
        $this->assertEquals(75, $batch->fresh()->available_quantity);
        $second = $this->transfer($batch, $retail, $consumer, 42, 75);
        $this->assertEquals(30, $second->previous_price_per_unit);
        $this->assertEquals('completed', $batch->fresh()->status);
        app(BatchHandoffService::class)->voidTransaction($second->id, $retail->id, 'Incorrect retail entry');
        $this->assertEquals(75, $batch->fresh()->available_quantity);
        $replacement = $this->transfer($batch, $retail, $consumer, 41, 75);
        $this->assertEquals($second->sequence_no + 1, $replacement->sequence_no);
    }

    public function test_partial_custody_transfer_rejected_without_mutation(): void
    {
        $farmer = $this->person('farmer');
        $batch = $this->batch($farmer);
        try {
            $this->transfer($batch, $farmer, $this->person('collector'), 25, 50);
            $this->fail('Expected rejection');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('entire available lot', $e->getMessage());
        }
        $this->assertEquals(0, $batch->transactions()->count());
        $this->assertEquals($farmer->id, $batch->fresh()->current_custodian_id);
    }

    public function test_old_handoff_cannot_be_voided_after_downstream_transfer(): void
    {
        $farmer = $this->person('farmer');
        $batch = $this->batch($farmer);
        $collector = $this->person('collector');
        $tx = $this->transfer($batch, $farmer, $collector, 25);
        $this->transfer($batch, $collector, $this->person('retail'), 30);
        $this->expectException(InvalidArgumentException::class);
        app(BatchHandoffService::class)->voidTransaction($tx->id, $farmer->id, 'Invalid old correction');
    }

    public function test_admin_verification_is_audited_and_cannot_be_self_assigned(): void
    {
        $admin = $this->person('admin');
        $farmer = $this->person('farmer');
        $this->actingAs($farmer)->post(route('admin.users.verify', $farmer->id), ['verified' => 1, 'reason' => 'Self assignment'])->assertForbidden();
        $this->actingAs($admin)->post(route('admin.users.verify', $farmer->id), ['verified' => 1, 'reason' => 'Reviewed identity records'])->assertRedirect();
        $this->assertTrue($farmer->fresh()->is_verified);
        $this->assertDatabaseHas('audit_logs', ['action' => 'USER_VERIFICATION_UPDATED', 'target_id' => $farmer->id]);
        $this->actingAs($admin)->post(route('admin.users.verify', $farmer->id), ['verified' => 0, 'reason' => 'Review needs renewal'])->assertRedirect();
        $this->assertFalse($farmer->fresh()->is_verified);
        foreach (['collector', 'wholesale', 'retail'] as $role) {
            $participant = $this->person($role);
            $this->post(route('admin.users.verify', $participant->id), ['verified' => 1, 'reason' => 'Checked participant records'])->assertRedirect();
            $this->assertTrue($participant->fresh()->is_verified);
        }
    }

    public function test_report_evidence_private_and_my_reports_scoped(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $owner = $this->person('collector');
        $other = $this->person('farmer');
        $this->actingAs($owner)->post(route('public.report.store'), ['crop_id' => Crop::first()->id,
            'district_id' => District::first()->id, 'category' => 'quantity', 'market_name' => 'QA test market',
            'reported_price' => 35, 'unit' => 'kg', 'incident_date' => now()->toDateString(),
            'report_narrative' => 'Private test evidence narrative.', 'evidence_photo' => UploadedFile::fake()->image('test.jpg')])->assertRedirect();
        $report = PriceReport::where('reporter_user_id', $owner->id)->latest('id')->firstOrFail();
        Storage::disk('local')->assertExists($report->evidence_path);
        Storage::disk('public')->assertMissing($report->evidence_path);
        $this->get(route('reports.evidence', $report->id))->assertOk();
        $this->actingAs($other)->get(route('reports.evidence', $report->id))->assertForbidden();
        $this->get(route('reports.mine'))->assertDontSee($report->tracking_code);
        $this->get(route('public.report.track', ['code' => $report->tracking_code]))->assertDontSee('Private test evidence narrative.');
        $this->actingAs($this->person('authority'))->get(route('reports.evidence', $report->id))->assertOk();
    }

    public function test_report_re_review_updates_one_observation_and_withdraws_invalid_sample(): void
    {
        $report = PriceReport::create(['tracking_code' => PriceReport::generateTrackingCode(), 'crop_id' => Crop::first()->id,
            'district_id' => District::first()->id, 'market_name' => 'Review market', 'reported_price' => 25, 'unit' => 'kg',
            'incident_date' => now(), 'report_narrative' => 'Price observed for review.', 'status' => 'pending']);
        $this->actingAs($this->person('authority'));
        foreach (['verified', 'rejected', 'verified', 'verified'] as $status) {
            $this->post(route('authority.reports.moderate', $report->id), ['status' => $status, 'moderation_notes' => 'QA review decision'])->assertRedirect();
            $this->assertEquals($status === 'verified' ? 1 : 0, PriceObservation::where('price_report_id', $report->id)->where('verification_status', 'verified')->count());
        }
        $this->assertEquals(1, PriceObservation::where('price_report_id', $report->id)->count());
    }

    public function test_every_role_workspace_and_batch_review_pages_render(): void
    {
        foreach (['admin' => 'admin.dashboard', 'authority' => 'authority.dashboard', 'farmer' => 'farmer.dashboard', 'collector' => 'vendor.dashboard', 'wholesale' => 'vendor.dashboard', 'retail' => 'vendor.dashboard'] as $role => $route) {
            $this->actingAs($this->person($role))->get(route($route))->assertOk();
            $this->get(route('reports.mine'))->assertOk();
        }
        $this->actingAs($this->person('farmer'))->get(route('farmer.handoff'))->assertOk();
        $batch = $this->batch($this->person('farmer'));
        $this->actingAs($this->person('admin'))->get(route('admin.batches.verification'))->assertOk();
        $this->get(route('admin.batches.detail', $batch->id))->assertOk();
    }

    public function test_collector_collects_at_farm_gate_but_retail_cannot(): void
    {
        $farmer = $this->person('farmer');
        $batch = $this->batch($farmer);
        $collector = $this->person('collector');
        $payload = ['batch_id' => $batch->id, 'quantity' => 100, 'price_per_unit' => 22, 'district_id' => District::first()->id];
        $this->actingAs($this->person('retail'))->post(route('vendor.collect'), $payload)->assertForbidden();
        $this->actingAs($collector)->post(route('vendor.collect'), $payload)->assertRedirect();
        $this->assertEquals($collector->id, $batch->fresh()->current_custodian_id);
        $this->assertEquals($farmer->id, $batch->transactions()->first()->seller_id);
    }

    public function test_quality_report_without_price_does_not_create_price_observation(): void
    {
        $farmer = $this->person('farmer');
        $this->actingAs($farmer)->post(route('public.report.store'), ['category' => 'quality', 'crop_id' => Crop::first()->id,
            'district_id' => District::first()->id, 'market_name' => 'QA quality market', 'unit' => 'kg',
            'incident_date' => now()->toDateString(), 'report_narrative' => 'Quality discrepancy identified at delivery.'])->assertRedirect();
        $report = PriceReport::where('reporter_user_id', $farmer->id)->latest('id')->firstOrFail();
        $this->assertNull($report->reported_price);
        $this->actingAs($this->person('authority'))->post(route('authority.reports.moderate', $report->id), ['status' => 'verified', 'moderation_notes' => 'Inspected quality evidence'])->assertRedirect();
        $this->assertEquals(0, PriceObservation::where('price_report_id', $report->id)->count());
    }

    public function test_spread_rule_flags_even_when_markup_is_below_cap(): void
    {
        $farmer = $this->person('farmer');
        $batch = $this->batch($farmer);
        MarkupRule::create(['crop_id' => $batch->crop_id, 'stage' => 'farmer_to_collector', 'max_markup_percent' => 100,
            'max_spread_amount' => 2, 'is_active' => true, 'created_by_user_id' => User::where('role', 'admin')->first()->id, 'explanation' => 'QA spread rule']);
        $tx = $this->transfer($batch, $farmer, $this->person('collector'), 23);
        $this->assertDatabaseHas('anomaly_flags', ['transaction_id' => $tx->id, 'reason_code' => 'EXCESSIVE_SPREAD', 'measured_value' => 3, 'threshold_value' => 2]);
    }

    public function test_batch_rejection_is_saved_and_hidden_from_public_trace(): void
    {
        $batch = $this->batch($this->person('farmer'));
        $this->actingAs($this->person('admin'))->post(route('admin.batches.reject', $batch->id), ['admin_notes' => 'Provenance requires correction'])->assertRedirect();
        $this->assertEquals('rejected', $batch->fresh()->verification_status);
        $this->get(route('public.traceability.show', $batch->batch_code))->assertNotFound();
        $this->post(route('admin.batches.verify', $batch->id), ['admin_notes' => 'Provenance checked'])->assertRedirect();
        $this->get(route('public.traceability.show', $batch->batch_code))->assertOk();
    }
}
