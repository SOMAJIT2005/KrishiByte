<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Crop;
use App\Models\District;
use App\Models\User;
use App\Services\BatchHandoffService;
use App\Services\CorrectionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthorityChartTest extends TestCase
{
    use DatabaseTransactions;

    private function person(string $role): User
    {
        return User::factory()->create(['role' => $role, 'account_status' => 'active', 'trading_approved' => true]);
    }

    private function lot(User $owner, User $farmer, Crop $crop, int $district, string $unit = 'sack'): Batch
    {
        return Batch::create(['batch_code' => Batch::generateBatchCode(), 'crop_id' => $crop->id, 'farmer_id' => $farmer->id, 'origin_district_id' => $district, 'harvest_date' => now(), 'initial_quantity' => 10, 'available_quantity' => 10, 'unit' => $unit, 'initial_farmer_price' => 100, 'current_custodian_id' => $owner->id, 'current_stage' => $owner->role === 'farmer' ? 'farm_gate' : $owner->role.'_custody', 'status' => 'active']);
    }

    private function transfer(Batch $lot, User $seller, User $buyer, int $district, float $price)
    {
        return app(BatchHandoffService::class)->recordHandoff(['batch_id' => $lot->id, 'seller_id' => $seller->id, 'buyer_id' => $buyer->id, 'district_id' => $district, 'quantity' => 10, 'price_per_unit' => $price, 'recorded_by_user_id' => $seller->id, '_legacy_import' => true]);
    }

    public function test_stage_comparison_orders_groups_and_keeps_crop_unit_scope_and_sample_counts(): void
    {
        $district = District::first()->id;
        $otherDistrict = District::where('id', '!=', $district)->first()->id;
        $crop = Crop::create(['name' => 'QA chart sacks', 'category' => 'Vegetable', 'unit' => 'sack', 'is_active' => true]);
        $farmer = $this->person('farmer');
        $collector = $this->person('collector');
        $wholesaler = $this->person('wholesale');
        $retailer = $this->person('retail');
        $lowLot = $this->lot($farmer, $farmer, $crop, $district);
        $this->transfer($lowLot, $farmer, $collector, $district, 50);
        $highLot = $this->lot($farmer, $farmer, $crop, $district);
        $this->transfer($highLot, $farmer, $collector, $district, 150);
        $this->transfer($highLot, $collector, $wholesaler, $district, 200);
        $this->transfer($this->lot($farmer, $farmer, $crop, $district, 'kg'), $farmer, $collector, $district, 999);
        $this->transfer($this->lot($farmer, $farmer, $crop, $otherDistrict), $farmer, $collector, $otherDistrict, 888);
        $voided = $this->transfer($this->lot($farmer, $farmer, $crop, $district), $farmer, $collector, $district, 777);
        $correction = app(CorrectionService::class)->request($voided->id, $farmer->id, (string) Str::uuid(), 'QA exclude corrected transfer');
        app(CorrectionService::class)->decide($correction, $collector->id, 'accepted', 'QA agreed transfer reversal');
        $retailLot = $this->lot($retailer, $farmer, $crop, $district);
        app(BatchHandoffService::class)->recordRetailSale(['batch_id' => $retailLot->id, 'seller_id' => $retailer->id, 'buyer_id' => null, 'quantity' => 1, 'price_per_unit' => 666, 'district_id' => $district, 'recorded_by_user_id' => $retailer->id], (string) Str::uuid());
        $reviewer = $this->person('authority');
        DB::table('review_assignments')->insert(['user_id' => $reviewer->id, 'district_id' => $district, 'assigned_by' => $reviewer->id, 'reason' => 'QA chart scope', 'created_at' => now(), 'updated_at' => now()]);
        $response = $this->actingAs($reviewer)->get(route('authority.dashboard', ['crop_id' => $crop->id]))->assertOk();
        $this->assertSame(['Farmer → collector', 'Collector → wholesale'], $response->viewData('chartLabels'));
        $this->assertEquals([100, 200], $response->viewData('chartPrices'));
        $this->assertEquals([0, 33.33], $response->viewData('chartMarkups'));
        $rows = $response->viewData('stageAverages');
        $this->assertEquals([2, 1], $rows->pluck('count')->all());
        $this->assertEquals(50, $rows[0]->min_price);
        $this->assertEquals(150, $rows[0]->max_price);
        $response->assertSee('BDT / sack')->assertSee('3 recorded transfers')->assertSee('not one batch')->assertSee('Exact values, ranges &amp; sample counts', false);
    }

    public function test_missing_markup_remains_null_and_unassigned_chart_has_honest_empty_state(): void
    {
        $district = District::first()->id;
        $crop = Crop::create(['name' => 'QA chart missing markup', 'category' => 'Vegetable', 'unit' => 'sack', 'is_active' => true]);
        $farmer = $this->person('farmer');
        $collector = $this->person('collector');
        $tx = $this->transfer($this->lot($farmer, $farmer, $crop, $district), $farmer, $collector, $district, 50);
        // A retained legacy row may lack an acquisition-based markup; do not turn missing into zero.
        $tx->update(['markup_percent' => null]);
        $reviewer = $this->person('authority');
        $this->actingAs($reviewer)->get(route('authority.dashboard', ['crop_id' => $crop->id]))->assertOk()->assertSee('No comparable custody transfers');
        DB::table('review_assignments')->insert(['user_id' => $reviewer->id, 'district_id' => $district, 'assigned_by' => $reviewer->id, 'reason' => 'QA chart scope', 'created_at' => now(), 'updated_at' => now()]);
        $response = $this->get(route('authority.dashboard',['crop_id' => $crop->id]))->assertOk()->assertSee('Not available');
        $this->assertSame([null],$response->viewData('chartMarkups'));
        $this->assertEquals(0,$response->viewData('stageAverages')->first()->markup_count);
    }
}
