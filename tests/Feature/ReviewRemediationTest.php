<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Crop;
use App\Models\District;
use App\Models\FarmerEnrollment;
use App\Models\MarkupRule;
use App\Models\PriceReport;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BatchHandoffService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class ReviewRemediationTest extends TestCase
{
    use DatabaseTransactions;

    private function person(string $role, bool $approved = true): User
    {
        return User::factory()->create(['role' => $role, 'account_status' => 'active', 'trading_approved' => $approved, 'farmer_profile_public' => $role === 'farmer', 'district_id' => District::first()->id]);
    }

    private function lot(User $owner, float $price = 20): Batch
    {
        $farmer = $owner->isFarmer() ? $owner : $this->person('farmer');

        return Batch::create(['batch_code' => Batch::generateBatchCode(), 'crop_id' => Crop::first()->id, 'farmer_id' => $farmer->id, 'origin_district_id' => District::first()->id, 'harvest_date' => now(), 'initial_quantity' => 100, 'available_quantity' => 100, 'unit' => 'kg', 'initial_farmer_price' => $price, 'current_custodian_id' => $owner->id, 'current_stage' => $owner->isFarmer() ? 'farm_gate' : $owner->role.'_custody', 'status' => 'active']);
    }

    private function sale(Batch $lot, User $retail, float $price = 30, float $quantity = .25): Transaction
    {
        return app(BatchHandoffService::class)->recordRetailSale(['batch_id' => $lot->id, 'seller_id' => $retail->id, 'buyer_id' => null, 'quantity' => $quantity, 'price_per_unit' => $price, 'district_id' => District::first()->id, 'recorded_by_user_id' => $retail->id], (string) Str::uuid());
    }

    private function assign(User $reviewer): void
    {
        DB::table('review_assignments')->insert(['user_id' => $reviewer->id, 'district_id' => District::first()->id, 'assigned_by' => $reviewer->id, 'reason' => 'Explicit case regression scope', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_extreme_percentages_survive_mysql_and_fractional_quantities_display(): void
    {
        $retail = $this->person('retail');
        $first = $this->sale($this->lot($retail, .01), $retail, 100000, 1);
        $this->assertEquals(999999900, $first->fresh()->markup_percent);
        $this->assertEquals(999999900, $first->anomalyFlags()->where('reason_code', 'EXCESSIVE_MARKUP')->firstOrFail()->measured_value);
        $second = $this->sale($this->lot($retail, 100000), $retail, .01);
        $this->assertEquals(-999999900, $second->fresh()->gross_margin_percent);
        $second->batch->update(['initial_quantity' => .25]);
        $this->get(route('public.traceability.show', $second->batch->batch_code))->assertOk()->assertSee('0.25 kg')->assertDontSee('+-');
    }

    public function test_mixed_flags_resolve_only_after_all_clear_and_voided_flags_stay_closed(): void
    {
        $retail = $this->person('retail');
        $lot = $this->lot($retail);
        MarkupRule::create(['crop_id' => $lot->crop_id, 'stage' => 'retail_sale', 'max_markup_percent' => 10, 'max_spread_amount' => 1, 'is_active' => true, 'created_by_user_id' => User::where('role', 'admin')->first()->id, 'explanation' => 'Both metrics regression']);
        $tx = $this->sale($lot, $retail, 40);
        $this->assertCount(2, $tx->anomalyFlags);
        $authority = $this->person('authority');
        $this->assign($authority);
        $this->actingAs($authority);
        foreach ($tx->anomalyFlags as $index => $flag) {
            $this->post(route('authority.flags.review', $flag->id), ['status' => 'cleared', 'reviewer_notes' => 'Reviewed supporting evidence'])->assertRedirect();
            $this->assertSame($index === 0 ? 'flagged' : 'valid', $tx->fresh()->validation_status);
        }
        $this->assertSame(2, $tx->anomalyFlags()->where('status', 'cleared')->count());
        $this->post(route('authority.flags.review', $tx->anomalyFlags->first()->id), ['status' => 'escalated', 'reviewer_notes' => 'Try reopening closed case'])->assertStatus(422);
        app(BatchHandoffService::class)->voidTransaction($tx->id, $retail->id, 'Wrong retail entry corrected');
        $this->post(route('authority.flags.review', $tx->anomalyFlags->first()->id), ['status' => 'cleared', 'reviewer_notes' => 'Voided source review attempt'])->assertStatus(422);
    }

    public function test_custody_correction_requires_counterparty_and_real_http_permissions(): void
    {
        $farmer = $this->person('farmer');
        $collector = $this->person('collector');
        $lot = $this->lot($farmer);
        $proposal = app(BatchHandoffService::class)->propose(['batch_id' => $lot->id, 'seller_id' => $farmer->id, 'buyer_id' => $collector->id, 'quantity' => 100, 'price_per_unit' => 25, 'district_id' => District::first()->id, 'recorded_by_user_id' => $farmer->id], (string) Str::uuid());
        $proposal = app(BatchHandoffService::class)->decide($proposal->id, $collector->id, 'accepted');
        try {
            app(BatchHandoffService::class)->voidTransaction($proposal->transaction_id, $farmer->id, 'Unilateral custody correction');
            $this->fail('Unilateral void allowed');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('counterparty', $e->getMessage());
        }
        $this->actingAs($farmer)->post(route('corrections.store', $proposal->transaction_id), ['request_key' => (string) Str::uuid(), 'reason' => 'Counterparty confirmed return'])->assertRedirect(route('corrections.index'));
        $request = DB::table('correction_requests')->where('transaction_id', $proposal->transaction_id)->first();
        $this->assertSame($collector->id, $lot->fresh()->current_custodian_id);
        $this->actingAs($this->person('admin'))->post(route('corrections.decide', $request->id), ['decision' => 'accepted', 'reason' => 'Cannot impersonate participant'])->assertForbidden();
        $this->actingAs($collector)->post(route('corrections.decide', $request->id), ['decision' => 'accepted', 'reason' => 'Produce returned by agreement'])->assertRedirect();
        $this->assertSame($farmer->id, $lot->fresh()->current_custodian_id);
        $this->assertTrue(Transaction::findOrFail($proposal->transaction_id)->is_voided);
        $this->post(route('corrections.decide', $request->id), ['decision' => 'accepted', 'reason' => 'Repeated acceptance remains safe'])->assertRedirect();
    }

    public function test_explicit_status_retry_does_not_reactivate_and_stale_version_is_rejected(): void
    {
        $admin = $this->person('admin');
        $target = $this->person('collector');
        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()]);
        $data = ['status' => 'suspended', 'version' => 0, 'reason' => 'Documented account review'];
        $this->post(route('admin.users.toggle', $target->id), $data)->assertRedirect();
        $this->post(route('admin.users.toggle', $target->id), $data)->assertRedirect();
        $this->assertSame('suspended', $target->fresh()->account_status);
        $this->assertSame(1, $target->fresh()->status_version);
        $this->post(route('admin.users.toggle', $target->id), ['status' => 'active', 'version' => 0, 'reason' => 'Stale account review'])->assertStatus(409);
        $this->post(route('admin.users.toggle', $admin->id), $data)->assertStatus(422);
    }

    public function test_reviewer_assignment_scopes_decisions_evidence_and_aggregates(): void
    {
        $report = PriceReport::firstOrFail();
        $reviewer = $this->person('authority');
        $this->actingAs($reviewer)->get(route('authority.dashboard'))->assertOk()->assertDontSee($report->tracking_code);
        $this->post(route('authority.reports.moderate', $report->id), ['status' => 'verified', 'moderation_notes' => 'Outside district assignment'])->assertForbidden();
        $this->get(route('reports.evidence', $report->id))->assertForbidden();
    }

    public function test_farmer_profile_keeps_units_separate(): void
    {
        $farmer = $this->person('farmer');
        $kg = $this->lot($farmer);
        $sacks = $this->lot($farmer);
        $sacks->update(['unit' => 'sack', 'initial_quantity' => 4]);
        $response = $this->get(route('farmers.show', $farmer->id))->assertOk();
        $rows = $response->viewData('cropStats');
        $this->assertCount(2, $rows);
        $this->assertEquals(100, $rows->firstWhere('unit', 'kg')['recorded_quantity']);
        $this->assertEquals(4, $rows->firstWhere('unit', 'sack')['recorded_quantity']);
    }

    public function test_duplicate_enrollment_links_exact_identity_and_new_traders_need_approval(): void
    {
        $data = ['name' => 'Duplicate farmer QA', 'phone' => '01788888888', 'district_id' => District::first()->id, 'grown_crop_ids' => [Crop::first()->id], 'public_profile_consent' => 1];
        $this->post(route('farmers.enroll.store'), $data)->assertRedirect();
        $this->post(route('farmers.enroll.store'), $data)->assertRedirect();
        $entries = FarmerEnrollment::where('name', $data['name'])->get();
        $this->actingAs($this->person('admin'))->withSession(['auth.password_confirmed_at' => time()]);
        foreach ($entries as $entry) {
            $this->post(route('admin.farmer-enrollment.review', $entry->id), ['decision' => 'approved', 'reason' => 'Checked identity and consent'])->assertRedirect();
        }
        $this->assertSame(1, User::where('name', $data['name'])->count());
        $this->assertSame($entries[0]->fresh()->farmer_id, $entries[1]->fresh()->farmer_id);
        $collector = $this->person('collector', false);
        $this->actingAs($collector)->get(route('vendor.farm-purchase'))->assertForbidden();
    }

    public function test_supervised_offline_access_is_single_use_private_and_honors_withdrawal(): void
    {
        $farmer = $this->person('farmer');
        $lot = $this->lot($farmer);
        $this->post(route('farmers.access.request'), ['farmer_id' => $farmer->id, 'name' => $farmer->name, 'phone' => '01788888888', 'reason' => 'Request supervised private sale access'])->assertRedirect();
        $entry = DB::table('farmer_access_requests')->latest('id')->first();
        $this->assertNull($entry->token_hash);
        $this->actingAs($this->person('admin'))->withSession(['auth.password_confirmed_at' => time()])->post(route('admin.farmer-access.review', $entry->id), ['decision' => 'granted', 'verification_method' => 'Synthetic QA supervised in-person identity check'])->assertRedirect();
        preg_match('/hours: ([A-Za-z0-9]{64})/', session('success'), $match);
        $this->assertNotEmpty($match[1]);
        auth()->logout();
        $this->post(route('farmers.access.redeem'), ['access_code' => $match[1]])->assertRedirect(route('farmers.access'));
        $this->post(route('farmers.access.redeem'), ['access_code' => $match[1]])->assertStatus(422);
        $this->get(route('farmers.access'))->assertOk()->assertSee($farmer->name)->assertHeader('Cache-Control', 'no-store, private');
        $this->post(route('farmers.withdraw'), ['reason' => 'Withdraw public publication'])->assertRedirect();
        $this->assertFalse($farmer->fresh()->farmer_profile_public);
        $this->get(route('public.traceability.show', $lot->batch_code))->assertOk()->assertDontSee($farmer->name)->assertSee('Private farmer');
        session()->forget(['farmer_access_id', 'farmer_access_expires']);
        $this->get(route('farmers.show', $farmer->id))->assertNotFound();
        $this->post(route('farmers.withdraw'), ['reason' => 'Guessed profile cannot withdraw'])->assertForbidden();
    }
}
