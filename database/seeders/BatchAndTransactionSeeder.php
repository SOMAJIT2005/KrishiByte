<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Crop;
use App\Models\District;
use App\Models\User;
use App\Services\BatchHandoffService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BatchAndTransactionSeeder extends Seeder
{
    public function run(): void
    {
        $farmer = User::where('role', 'farmer')->firstOrFail();
        $collector = User::where('role', 'collector')->firstOrFail();
        $wholesale = User::where('role', 'wholesale')->firstOrFail();
        $retail = User::where('role', 'retail')->firstOrFail();
        $district = District::where('code', 'BOG')->firstOrFail();
        $dhaka = District::where('code', 'DHK')->firstOrFail();
        $secondCollector = User::firstOrCreate(['email' => 'collector.salma@krishibyte.local'], [
            'name' => 'Salma Begum (Regional Collector)', 'role' => 'collector', 'account_status' => 'active',
            'district_id' => $dhaka->id, 'password' => 'CollectorPass123!',
        ]);
        $secondWholesale = User::firstOrCreate(['email' => 'wholesale.nasir@krishibyte.local'], [
            'name' => 'Nasir Ahmed (Regional Wholesaler)', 'role' => 'wholesale', 'account_status' => 'active',
            'district_id' => $dhaka->id, 'password' => 'WholesalePass123!',
        ]);
        foreach ([
            ['KB-20261010-POT001', 'Potato', 22, 2500, [$collector, $secondCollector, $wholesale, $secondWholesale, $retail]],
            ['KB-20261011-ONI002', 'Onion', 38, 1000, [$collector, $wholesale, $retail]],
            ['KB-20261012-TOM003', 'Tomato', 48, 1200, [$collector]],
            ['KB-20261012-CHI004', 'Chili', 80, 500, []],
        ] as [$code, $cropName, $price, $quantity, $chain]) {
            if (Batch::where('batch_code', $code)->exists()) {
                continue;
            }
            DB::transaction(function () use ($code, $cropName, $price, $quantity, $chain, $farmer, $district, $dhaka) {
                $crop = Crop::where('name', 'like', '%'.$cropName.'%')->firstOrFail();
                $batch = Batch::create(['batch_code' => $code, 'crop_id' => $crop->id, 'farmer_id' => $farmer->id,
                    'origin_district_id' => $district->id, 'farm_location_name' => 'Illustrative Bogura farm',
                    'origin_latitude' => 24.9856, 'origin_longitude' => 89.3241, 'harvest_date' => now()->subDay(),
                    'initial_quantity' => $quantity, 'available_quantity' => $quantity, 'unit' => 'kg',
                    'initial_farmer_price' => $price, 'current_custodian_id' => $farmer->id,
                    'current_stage' => 'farm_gate', 'status' => 'active', 'notes' => 'Illustrative demo lot; not a real market transaction.']);
                $seller = $farmer;
                foreach ($chain as $index => $buyer) {
                    app(BatchHandoffService::class)->recordHandoff(['batch_id' => $batch->id, 'seller_id' => $seller->id,
                        '_legacy_import' => true, 'buyer_id' => $buyer->id, 'stage' => 'auto', 'quantity' => $quantity,
                        'price_per_unit' => $price + ($cropName === 'Onion' ? 18 : 4) * ($index + 1),
                        'district_id' => $index === 0 ? $district->id : $dhaka->id,
                        'market_name' => $index === 0 ? 'Illustrative collection point' : 'Illustrative regional market',
                        'recorded_by_user_id' => $seller->id]);
                    $seller = $buyer;
                }
            });
        }
    }
}
