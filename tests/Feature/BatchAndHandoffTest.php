<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Crop;
use App\Models\District;
use App\Models\MarkupRule;
use App\Models\User;
use App\Services\BatchHandoffService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use InvalidArgumentException;
use Tests\TestCase;

class BatchAndHandoffTest extends TestCase
{
    use DatabaseTransactions;

    protected User $farmer;
    protected User $collector;
    protected User $wholesale;
    protected Crop $crop;
    protected District $district;

    protected function setUp(): void
    {
        parent::setUp();

        $this->farmer = User::where('role', 'farmer')->first();
        $this->collector = User::where('role', 'collector')->first();
        $this->wholesale = User::where('role', 'wholesale')->first();
        $this->crop = Crop::first();
        $this->district = District::first();
    }

    /** AT-05: Farmer creates valid batch */
    public function test_farmer_creates_valid_batch(): void
    {
        $response = $this->actingAs($this->farmer)->post(route('farmer.batches.store'), [
            'crop_id' => $this->crop->id,
            'origin_district_id' => $this->district->id,
            'farm_location_name' => 'Test Green Acre',
            'harvest_date' => now()->toDateString(),
            'initial_quantity' => 1500,
            'unit' => 'kg',
            'initial_farmer_price' => 24.50,
            'origin_latitude' => 24.8465,
            'origin_longitude' => 89.3777,
            'notes' => 'Test batch creation',
        ]);

        $response->assertRedirect(route('farmer.dashboard'));
        $this->assertDatabaseHas('batches', [
            'farmer_id' => $this->farmer->id,
            'current_custodian_id' => $this->farmer->id,
            'current_stage' => 'farm_gate',
            'initial_quantity' => 1500,
            'initial_farmer_price' => 24.50,
        ]);
    }

    /** AT-06: Farmer submits invalid negative price or quantity */
    public function test_farmer_submits_invalid_negative_price_or_quantity(): void
    {
        $response = $this->actingAs($this->farmer)->post(route('farmer.batches.store'), [
            'crop_id' => $this->crop->id,
            'origin_district_id' => $this->district->id,
            'harvest_date' => now()->toDateString(),
            'initial_quantity' => -50, // invalid negative
            'unit' => 'kg',
            'initial_farmer_price' => -10, // invalid negative
        ]);

        $response->assertSessionHasErrors(['initial_quantity', 'initial_farmer_price']);
    }

    /** AT-07: Current custodian records valid handoff */
    public function test_current_custodian_records_valid_handoff(): void
    {
        $batch = Batch::create([
            'batch_code' => Batch::generateBatchCode(),
            'crop_id' => $this->crop->id,
            'farmer_id' => $this->farmer->id,
            'origin_district_id' => $this->district->id,
            'harvest_date' => now()->toDateString(),
            'initial_quantity' => 1000,
            'available_quantity' => 1000,
            'unit' => 'kg',
            'initial_farmer_price' => 25.00,
            'current_custodian_id' => $this->farmer->id,
            'current_stage' => 'farm_gate',
            'status' => 'active',
        ]);

        $service = app(BatchHandoffService::class);
        $tx = $service->recordHandoff([
            'batch_id' => $batch->id,
            'seller_id' => $this->farmer->id,
            'buyer_id' => $this->collector->id,
            'stage' => 'farmer_to_collector',
            'quantity' => 1000,
            'price_per_unit' => 30.00,
            'district_id' => $this->district->id,
            'recorded_by_user_id' => $this->collector->id,
        ]);

        $this->assertEquals(1, $tx->sequence_no);
        $this->assertEquals(5.00, $tx->gross_price_spread);
        $this->assertEquals(20.00, $tx->markup_percent);

        // Custody must be atomically updated to collector
        $batch->refresh();
        $this->assertEquals($this->collector->id, $batch->current_custodian_id);
        $this->assertEquals('collector_custody', $batch->current_stage);
    }

    /** AT-08: Non-custodian attempts handoff (rejected) */
    public function test_non_custodian_attempt_handoff_is_rejected(): void
    {
        $batch = Batch::create([
            'batch_code' => Batch::generateBatchCode(),
            'crop_id' => $this->crop->id,
            'farmer_id' => $this->farmer->id,
            'origin_district_id' => $this->district->id,
            'harvest_date' => now()->toDateString(),
            'initial_quantity' => 500,
            'available_quantity' => 500,
            'unit' => 'kg',
            'initial_farmer_price' => 20.00,
            'current_custodian_id' => $this->farmer->id, // Held by farmer!
            'current_stage' => 'farm_gate',
            'status' => 'active',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Seller is not the current custodian of this batch.");

        $service = app(BatchHandoffService::class);
        // Wholesale vendor tries to sell batch they do not hold!
        $service->recordHandoff([
            'batch_id' => $batch->id,
            'seller_id' => $this->wholesale->id,
            'buyer_id' => $this->collector->id,
            'stage' => 'collector_to_wholesale',
            'quantity' => 500,
            'price_per_unit' => 30.00,
            'district_id' => $this->district->id,
            'recorded_by_user_id' => $this->wholesale->id,
        ]);
    }

    /** AT-09: Quantity exceeds available batch quantity (rejected) */
    public function test_quantity_exceeding_available_is_rejected(): void
    {
        $batch = Batch::create([
            'batch_code' => Batch::generateBatchCode(),
            'crop_id' => $this->crop->id,
            'farmer_id' => $this->farmer->id,
            'origin_district_id' => $this->district->id,
            'harvest_date' => now()->toDateString(),
            'initial_quantity' => 500,
            'available_quantity' => 500,
            'unit' => 'kg',
            'initial_farmer_price' => 20.00,
            'current_custodian_id' => $this->farmer->id,
            'current_stage' => 'farm_gate',
            'status' => 'active',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("exceeds available batch quantity");

        $service = app(BatchHandoffService::class);
        $service->recordHandoff([
            'batch_id' => $batch->id,
            'seller_id' => $this->farmer->id,
            'buyer_id' => $this->collector->id,
            'stage' => 'farmer_to_collector',
            'quantity' => 9999, // Exceeds 500
            'price_per_unit' => 25.00,
            'district_id' => $this->district->id,
            'recorded_by_user_id' => $this->collector->id,
        ]);
    }

    /** AT-10: Same vendor is buyer and seller (rejected) */
    public function test_self_trade_is_rejected(): void
    {
        $batch = Batch::create([
            'batch_code' => Batch::generateBatchCode(),
            'crop_id' => $this->crop->id,
            'farmer_id' => $this->farmer->id,
            'origin_district_id' => $this->district->id,
            'harvest_date' => now()->toDateString(),
            'initial_quantity' => 500,
            'available_quantity' => 500,
            'unit' => 'kg',
            'initial_farmer_price' => 20.00,
            'current_custodian_id' => $this->farmer->id,
            'current_stage' => 'farm_gate',
            'status' => 'active',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Seller and buyer cannot be the same user.");

        $service = app(BatchHandoffService::class);
        $service->recordHandoff([
            'batch_id' => $batch->id,
            'seller_id' => $this->farmer->id,
            'buyer_id' => $this->farmer->id, // Self trade!
            'stage' => 'farmer_to_collector',
            'quantity' => 500,
            'price_per_unit' => 25.00,
            'district_id' => $this->district->id,
            'recorded_by_user_id' => $this->farmer->id,
        ]);
    }

    /** AT-11: Markup exceeding active threshold generates anomaly flag */
    public function test_markup_above_threshold_generates_anomaly_flag(): void
    {
        // Set a strict 20% rule for this crop
        MarkupRule::create([
            'crop_id' => $this->crop->id,
            'stage' => 'farmer_to_collector',
            'max_markup_percent' => 20.00,
            'is_active' => true,
            'created_by_user_id' => User::where('role', 'admin')->first()->id,
            'explanation' => 'Test rule',
        ]);

        $batch = Batch::create([
            'batch_code' => Batch::generateBatchCode(),
            'crop_id' => $this->crop->id,
            'farmer_id' => $this->farmer->id,
            'origin_district_id' => $this->district->id,
            'harvest_date' => now()->toDateString(),
            'initial_quantity' => 100,
            'available_quantity' => 100,
            'unit' => 'kg',
            'initial_farmer_price' => 20.00,
            'current_custodian_id' => $this->farmer->id,
            'current_stage' => 'farm_gate',
            'status' => 'active',
        ]);

        $service = app(BatchHandoffService::class);
        // 20.00 to 35.00 = 75% markup, which breaches 20% cap!
        $tx = $service->recordHandoff([
            'batch_id' => $batch->id,
            'seller_id' => $this->farmer->id,
            'buyer_id' => $this->collector->id,
            'stage' => 'farmer_to_collector',
            'quantity' => 100,
            'price_per_unit' => 35.00,
            'district_id' => $this->district->id,
            'recorded_by_user_id' => $this->collector->id,
        ]);

        $this->assertEquals('flagged', $tx->validation_status);
        $this->assertDatabaseHas('anomaly_flags', [
            'batch_id' => $batch->id,
            'transaction_id' => $tx->id,
            'reason_code' => 'EXCESSIVE_MARKUP',
            'status' => 'new',
        ]);
    }

    /** AT-18: Authorized void of transaction restores previous custody */
    public function test_authorized_void_restores_previous_custody(): void
    {
        $batch = Batch::create([
            'batch_code' => Batch::generateBatchCode(),
            'crop_id' => $this->crop->id,
            'farmer_id' => $this->farmer->id,
            'origin_district_id' => $this->district->id,
            'harvest_date' => now()->toDateString(),
            'initial_quantity' => 200,
            'available_quantity' => 200,
            'unit' => 'kg',
            'initial_farmer_price' => 20.00,
            'current_custodian_id' => $this->farmer->id,
            'current_stage' => 'farm_gate',
            'status' => 'active',
        ]);

        $service = app(BatchHandoffService::class);
        $tx = $service->recordHandoff([
            'batch_id' => $batch->id,
            'seller_id' => $this->farmer->id,
            'buyer_id' => $this->collector->id,
            'stage' => 'farmer_to_collector',
            'quantity' => 200,
            'price_per_unit' => 24.00,
            'district_id' => $this->district->id,
            'recorded_by_user_id' => $this->collector->id,
        ]);

        $batch->refresh();
        $this->assertEquals($this->collector->id, $batch->current_custodian_id);

        // Now void transaction
        $service->voidTransaction($tx->id, $this->collector->id, "Clerical weight error");

        $tx->refresh();
        $batch->refresh();

        $this->assertTrue($tx->is_voided);
        $this->assertEquals('voided', $tx->validation_status);
        $this->assertEquals($this->farmer->id, $batch->current_custodian_id); // Custody returned to farmer!
    }
}
