<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Crop;
use App\Models\District;
use App\Models\User;
use App\Services\BatchHandoffService;
use App\Services\SmsService;
use Illuminate\Database\Seeder;

class BatchAndTransactionSeeder extends Seeder
{
    public function run(): void
    {
        $farmer = User::where('email', 'farmer.rahim@krishibyte.local')->first();
        $collector = User::where('email', 'collector.karim@krishibyte.local')->first();
        $wholesale = User::where('email', 'wholesale.hasan@krishibyte.local')->first();
        $retail = User::where('email', 'retail.rafiq@krishibyte.local')->first();

        $bogura = District::where('code', 'BOG')->first();
        $pabna = District::where('code', 'PAB')->first();
        $rajshahi = District::where('code', 'RAJ')->first();
        $dinajpur = District::where('code', 'DIN')->first();
        $dhaka = District::where('code', 'DHK')->first();

        $potato = Crop::where('name', 'like', '%Potato%')->first();
        $onion = Crop::where('name', 'like', '%Onion%')->first();
        $tomato = Crop::where('name', 'like', '%Tomato%')->first();
        $chili = Crop::where('name', 'like', '%Chili%')->first();
        $rice = Crop::where('name', 'like', '%Rice%')->first();

        $handoffService = app(BatchHandoffService::class);

        // --- Batch 1: Bogura Potato (Clean normal chain) ---
        $batch1 = Batch::create([
            'batch_code' => 'KB-20261010-POT001',
            'crop_id' => $potato->id,
            'farmer_id' => $farmer->id,
            'origin_district_id' => $bogura->id,
            'farm_location_name' => 'Shibganj Agro Field, Bogura',
            'origin_latitude' => 24.9856,
            'origin_longitude' => 89.3241,
            'harvest_date' => now()->subDays(3)->toDateString(),
            'initial_quantity' => 2500.00,
            'available_quantity' => 2500.00,
            'unit' => 'kg',
            'initial_farmer_price' => 22.00,
            'current_custodian_id' => $farmer->id,
            'current_stage' => 'farm_gate',
            'status' => 'active',
            'notes' => 'Early winter harvest diamond potato, washed and sorted.',
        ]);

        // Step 1: Farmer -> Collector
        $handoffService->recordHandoff([
            'batch_id' => $batch1->id,
            'seller_id' => $farmer->id,
            'buyer_id' => $collector->id,
            'stage' => 'farmer_to_collector',
            'quantity' => 2500.00,
            'price_per_unit' => 26.00,
            'district_id' => $bogura->id,
            'market_name' => 'Mahasthangarh Haat, Bogura',
            'latitude' => 24.9600,
            'longitude' => 89.3400,
            'note' => 'Graded bagging completed at farm gate.',
            'recorded_by_user_id' => $collector->id,
        ]);

        // Step 2: Collector -> Wholesale
        $handoffService->recordHandoff([
            'batch_id' => $batch1->id,
            'seller_id' => $collector->id,
            'buyer_id' => $wholesale->id,
            'stage' => 'collector_to_wholesale',
            'quantity' => 2500.00,
            'price_per_unit' => 32.00,
            'district_id' => $dhaka->id,
            'market_name' => 'Karwan Bazar Wholesale Arat, Dhaka',
            'latitude' => 23.7516,
            'longitude' => 90.3944,
            'note' => 'Arrived via truck transport; quality verified A-grade.',
            'recorded_by_user_id' => $wholesale->id,
        ]);

        // Step 3: Wholesale -> Retail
        $handoffService->recordHandoff([
            'batch_id' => $batch1->id,
            'seller_id' => $wholesale->id,
            'buyer_id' => $retail->id,
            'stage' => 'wholesale_to_retail',
            'quantity' => 2500.00,
            'price_per_unit' => 38.00,
            'district_id' => $dhaka->id,
            'market_name' => 'Mohammadpur Krishi Market, Dhaka',
            'latitude' => 23.7712,
            'longitude' => 90.3601,
            'note' => 'Delivered to retail shelves for consumer purchase.',
            'recorded_by_user_id' => $retail->id,
        ]);

        // --- Batch 2: Pabna Onion (Gouging Anomaly Chain) ---
        $batch2 = Batch::create([
            'batch_code' => 'KB-20261011-ONI002',
            'crop_id' => $onion->id,
            'farmer_id' => $farmer->id,
            'origin_district_id' => $pabna->id,
            'farm_location_name' => 'Santhia Upazila, Pabna',
            'origin_latitude' => 24.0612,
            'origin_longitude' => 89.5312,
            'harvest_date' => now()->subDays(2)->toDateString(),
            'initial_quantity' => 1800.00,
            'available_quantity' => 1800.00,
            'unit' => 'kg',
            'initial_farmer_price' => 55.00,
            'current_custodian_id' => $farmer->id,
            'current_stage' => 'farm_gate',
            'status' => 'active',
            'notes' => 'Red Taherpuri local onion.',
        ]);

        // Step 1: Farmer -> Collector
        $handoffService->recordHandoff([
            'batch_id' => $batch2->id,
            'seller_id' => $farmer->id,
            'buyer_id' => $collector->id,
            'stage' => 'farmer_to_collector',
            'quantity' => 1800.00,
            'price_per_unit' => 65.00,
            'district_id' => $pabna->id,
            'market_name' => 'Kashinathpur Mokam, Pabna',
            'latitude' => 23.9500,
            'longitude' => 89.6000,
            'note' => 'Procured directly from grower.',
            'recorded_by_user_id' => $collector->id,
        ]);

        // Step 2: Collector -> Wholesale
        $handoffService->recordHandoff([
            'batch_id' => $batch2->id,
            'seller_id' => $collector->id,
            'buyer_id' => $wholesale->id,
            'stage' => 'collector_to_wholesale',
            'quantity' => 1800.00,
            'price_per_unit' => 78.00,
            'district_id' => $dhaka->id,
            'market_name' => 'Shyambazar River Terminal Arat, Dhaka',
            'latitude' => 23.7081,
            'longitude' => 90.4143,
            'note' => 'Transferred into central stock.',
            'recorded_by_user_id' => $wholesale->id,
        ]);

        // Step 3: Wholesale -> Retail (Excessive price hike: 78 to 115 = 47.44% markup vs 28% max)
        $handoffService->recordHandoff([
            'batch_id' => $batch2->id,
            'seller_id' => $wholesale->id,
            'buyer_id' => $retail->id,
            'stage' => 'wholesale_to_retail',
            'quantity' => 1800.00,
            'price_per_unit' => 115.00,
            'district_id' => $dhaka->id,
            'market_name' => 'Karwan Bazar Retail Shed, Dhaka',
            'latitude' => 23.7516,
            'longitude' => 90.3944,
            'note' => 'High demand market delivery.',
            'recorded_by_user_id' => $retail->id,
        ]);

        // --- Batch 3: Rajshahi Tomato (Mid-chain in Collector custody) ---
        $batch3 = Batch::create([
            'batch_code' => 'KB-20261012-TOM003',
            'crop_id' => $tomato->id,
            'farmer_id' => $farmer->id,
            'origin_district_id' => $rajshahi->id,
            'farm_location_name' => 'Godagari Tomato Zone, Rajshahi',
            'origin_latitude' => 24.4682,
            'origin_longitude' => 88.3307,
            'harvest_date' => now()->subDay()->toDateString(),
            'initial_quantity' => 1200.00,
            'available_quantity' => 1200.00,
            'unit' => 'kg',
            'initial_farmer_price' => 40.00,
            'current_custodian_id' => $farmer->id,
            'current_stage' => 'farm_gate',
            'status' => 'active',
            'notes' => 'Early red ripe tomatoes in plastic crates.',
        ]);

        $handoffService->recordHandoff([
            'batch_id' => $batch3->id,
            'seller_id' => $farmer->id,
            'buyer_id' => $collector->id,
            'stage' => 'farmer_to_collector',
            'quantity' => 1200.00,
            'price_per_unit' => 48.00,
            'district_id' => $rajshahi->id,
            'market_name' => 'Baneswar Hat, Rajshahi',
            'latitude' => 24.3750,
            'longitude' => 88.7500,
            'note' => 'Collected in fresh crates, prepared for transport.',
            'recorded_by_user_id' => $collector->id,
        ]);

        // --- Batch 4: Dinajpur Green Chili (Newly created at farm gate) ---
        Batch::create([
            'batch_code' => 'KB-20261012-CHL004',
            'crop_id' => $chili->id,
            'farmer_id' => $farmer->id,
            'origin_district_id' => $dinajpur->id,
            'farm_location_name' => 'Birganj Upazila, Dinajpur',
            'origin_latitude' => 25.8500,
            'origin_longitude' => 88.6600,
            'harvest_date' => now()->toDateString(),
            'initial_quantity' => 600.00,
            'available_quantity' => 600.00,
            'unit' => 'kg',
            'initial_farmer_price' => 80.00,
            'current_custodian_id' => $farmer->id,
            'current_stage' => 'farm_gate',
            'status' => 'active',
            'notes' => 'Fresh green chili lot, freshly picked today.',
        ]);

                // --- Batch 5: LONG-DISTANCE Multi-Middleman Chain ---
        // Route: Sylhet (Farm) -> Sylhet (Collector) -> Dhaka (Wholesale #1) -> Chattogram (Wholesale #2) -> Rangpur (Wholesale #3) -> Khulna (Retail) -> Rajshahi (Consumer)
        // Demonstrates 6 transactors / 5 middleman transfers across 6 districts
        $sylhet = District::where('code', 'SYL')->first();
        $khulna = District::where('code', 'KHL')->first();
        $rangpur = District::where('code', 'RAP')->first();
        $chattogram = District::where('code', 'CTG')->first();

        // Fetch extra wholesale users for the long chain
        $extraWholesalers = User::where('role', 'wholesale')
            ->where('id', '!=', $wholesale->id)
            ->orderBy('id')
            ->get();
        $wholesaleChattogram = $extraWholesalers->first();
        $wholesaleRangpur = $extraWholesalers->get(1);

        $batchLongChain = Batch::create([
            'batch_code' => 'KB-20261011-RIC005',
            'crop_id' => $rice->id,
            'farmer_id' => $farmer->id,
            'origin_district_id' => $sylhet->id,
            'farm_location_name' => 'Beani Bazar Rice Field, Sylhet',
            'origin_latitude' => 24.9425,
            'origin_longitude' => 91.8687,
            'harvest_date' => now()->subDays(6)->toDateString(),
            'initial_quantity' => 5000.00,
            'available_quantity' => 5000.00,
            'unit' => 'kg',
            'initial_farmer_price' => 40.00,
            'current_custodian_id' => $farmer->id,
            'current_stage' => 'farm_gate',
            'status' => 'active',
            'notes' => 'BRRI dhan28 Miniket rice — 6-stop cross-country supply chain (Sylhet → Dhaka → Chattogram → Rangpur → Khulna → Rajshahi).',
        ]);

        // Step 1: Farmer (Sylhet) -> Collector (Sylhet)  [farmer_to_collector → collector_custody]
        $handoffService->recordHandoff([
            'batch_id' => $batchLongChain->id,
            'seller_id' => $farmer->id,
            'buyer_id' => $collector->id,
            'stage' => 'farmer_to_collector',
            'quantity' => 5000.00,
            'price_per_unit' => 44.00,
            'district_id' => $sylhet->id,
            'market_name' => 'Beani Bazar Haat, Sylhet',
            'latitude' => 24.9425,
            'longitude' => 91.8687,
            'note' => 'Rice procured from grower at harvest rate in Sylhet region.',
            'recorded_by_user_id' => $collector->id,
        ]);

        // Step 2: Collector (Sylhet) -> Wholesale #1 (Dhaka)  [collector_to_wholesale → wholesale_custody]
        $handoffService->recordHandoff([
            'batch_id' => $batchLongChain->id,
            'seller_id' => $collector->id,
            'buyer_id' => $wholesale->id,
            'stage' => 'collector_to_wholesale',
            'quantity' => 4900.00,
            'price_per_unit' => 51.00,
            'district_id' => $dhaka->id,
            'market_name' => 'Shyambazar River Terminal Arat, Dhaka',
            'latitude' => 23.7081,
            'longitude' => 90.4143,
            'note' => 'Freight train Sylhet → Dhaka central warehouse; 100kg transit loss (5000 - 4900).',
            'recorded_by_user_id' => $wholesale->id,
        ]);

        // Step 3: Wholesale #1 (Dhaka) -> Wholesale #2 (Chattogram)  [wholesale_to_retail → retail_custody]
        if ($wholesaleChattogram) {
            $handoffService->recordHandoff([
                'batch_id' => $batchLongChain->id,
                'seller_id' => $wholesale->id,
                'buyer_id' => $wholesaleChattogram->id,
                'stage' => 'wholesale_to_retail',
                'quantity' => 4850.00,
                'price_per_unit' => 56.00,
                'district_id' => $chattogram->id,
                'market_name' => 'Chattogram Port Arat Warehouse #7',
                'latitude' => 22.3579,
                'longitude' => 91.7973,
                'note' => 'Re-sold to Chattogram port wholesale for coastal distribution; 50kg handling loss.',
                'recorded_by_user_id' => $wholesaleChattogram->id,
            ]);
        }

        // Step 4: Wholesale #2 (Chattogram) -> Wholesale #3 (Rangpur)  [wholesale_to_retail → retail_custody]
        if ($wholesaleRangpur) {
            $handoffService->recordHandoff([
                'batch_id' => $batchLongChain->id,
                'seller_id' => $wholesaleChattogram->id,
                'buyer_id' => $wholesaleRangpur->id,
                'stage' => 'wholesale_to_retail',
                'quantity' => 4800.00,
                'price_per_unit' => 60.00,
                'district_id' => $rangpur->id,
                'market_name' => 'Rangpur Northern Arat Depot',
                'latitude' => 25.7439,
                'longitude' => 89.2611,
                'note' => 'Extra middleman: Chattogram → Rangpur distributor for far northern distribution; 50kg loss.',
                'recorded_by_user_id' => $wholesaleRangpur->id,
            ]);
        }

        // Step 5: Wholesale #3 (Rangpur) -> Retail (Khulna)  [wholesale_to_retail → retail_custody]
        if ($wholesaleRangpur) {
            $handoffService->recordHandoff([
                'batch_id' => $batchLongChain->id,
                'seller_id' => $wholesaleRangpur->id,
                'buyer_id' => $retail->id,
                'stage' => 'wholesale_to_retail',
            'quantity' => 4750.00,
            'price_per_unit' => 66.00,
            'district_id' => $khulna->id,
            'market_name' => 'Khulna City Market Wholesale Depot',
            'latitude' => 22.8172,
            'longitude' => 89.5300,
            'note' => 'Supply routed from Rangpur via coastal highway to Khulna regional market; 50kg loss.',
            'recorded_by_user_id' => $retail->id,
            ]);
        }

        // Step 6: Retail (Khulna) -> Consumer (Rajshahi)  [retail_sale → sold_out]
        if ($wholesaleRangpur) {
            $consumer = User::where('role', 'consumer')->first();
            if ($consumer) {
                $handoffService->recordHandoff([
                    'batch_id' => $batchLongChain->id,
                    'seller_id' => $retail->id,
                    'buyer_id' => $consumer->id,
                    'stage' => 'retail_sale',
                    'quantity' => 4700.00,
                    'price_per_unit' => 78.00,
                    'district_id' => $rajshahi->id,
                    'market_name' => 'Barirhat Kitchen Market, Rajshahi',
                    'latitude' => 24.3765,
                    'longitude' => 88.6200,
                    'note' => 'Final consumer sale: 6-stop journey — Sylhet → Dhaka → Chattogram → Rangpur → Khulna → Rajshahi (5 middleman transfers, 300kg cumulative loss).',
                    'recorded_by_user_id' => $consumer->id,
                ]);
            }
        }
// --- Batch 2: Pabna Onion (Gouging Anomaly Chain) ---
        $batch2 = Batch::create([
            'batch_code' => 'KB-20261011-ONI002',
            'crop_id' => $onion->id,
            'farmer_id' => $farmer->id,
            'origin_district_id' => $pabna->id,
            'farm_location_name' => 'Santhia Upazila, Pabna',
            'origin_latitude' => 24.0612,
            'origin_longitude' => 89.5312,
            'harvest_date' => now()->subDays(2)->toDateString(),
            'initial_quantity' => 1800.00,
            'available_quantity' => 1800.00,
            'unit' => 'kg',
            'initial_farmer_price' => 55.00,
            'current_custodian_id' => $farmer->id,
            'current_stage' => 'farm_gate',
            'status' => 'active',
            'notes' => 'Red Taherpuri local onion.',
        ]);

        // Step 1: Farmer -> Collector
        $handoffService->recordHandoff([
            'batch_id' => $batch2->id,
            'seller_id' => $farmer->id,
            'buyer_id' => $collector->id,
            'stage' => 'farmer_to_collector',
            'quantity' => 1800.00,
            'price_per_unit' => 65.00,
            'district_id' => $pabna->id,
            'market_name' => 'Kashinathpur Mokam, Pabna',
            'latitude' => 23.9500,
            'longitude' => 89.6000,
            'note' => 'Procured directly from grower.',
            'recorded_by_user_id' => $collector->id,
        ]);

        // Step 2: Collector -> Wholesale
        $handoffService->recordHandoff([
            'batch_id' => $batch2->id,
            'seller_id' => $collector->id,
            'buyer_id' => $wholesale->id,
            'stage' => 'collector_to_wholesale',
            'quantity' => 1800.00,
            'price_per_unit' => 78.00,
            'district_id' => $dhaka->id,
            'market_name' => 'Shyambazar River Terminal Arat, Dhaka',
            'latitude' => 23.7081,
            'longitude' => 90.4143,
            'note' => 'Transferred into central stock.',
            'recorded_by_user_id' => $wholesale->id,
        ]);

        // Step 3: Wholesale -> Retail (Excessive price hike: 78 to 115 = 47.44% markup vs 28% max)
        $handoffService->recordHandoff([
            'batch_id' => $batch2->id,
            'seller_id' => $wholesale->id,
            'buyer_id' => $retail->id,
            'stage' => 'wholesale_to_retail',
            'quantity' => 1800.00,
            'price_per_unit' => 115.00,
            'district_id' => $dhaka->id,
            'market_name' => 'Karwan Bazar Retail Shed, Dhaka',
            'latitude' => 23.7516,
            'longitude' => 90.3944,
            'note' => 'High demand market delivery.',
            'recorded_by_user_id' => $retail->id,
        ]);

        // --- Batch 3: Rajshahi Tomato (Mid-chain in Collector custody) ---
        $batch3 = Batch::create([
            'batch_code' => 'KB-20261012-TOM003',
            'crop_id' => $tomato->id,
            'farmer_id' => $farmer->id,
            'origin_district_id' => $rajshahi->id,
            'farm_location_name' => 'Godagari Tomato Zone, Rajshahi',
            'origin_latitude' => 24.4682,
            'origin_longitude' => 88.3307,
            'harvest_date' => now()->subDay()->toDateString(),
            'initial_quantity' => 1200.00,
            'available_quantity' => 1200.00,
            'unit' => 'kg',
            'initial_farmer_price' => 40.00,
            'current_custodian_id' => $farmer->id,
            'current_stage' => 'farm_gate',
            'status' => 'active',
            'notes' => 'Early red ripe tomatoes in plastic crates.',
        ]);

        $handoffService->recordHandoff([
            'batch_id' => $batch3->id,
            'seller_id' => $farmer->id,
            'buyer_id' => $collector->id,
            'stage' => 'farmer_to_collector',
            'quantity' => 1200.00,
            'price_per_unit' => 48.00,
            'district_id' => $rajshahi->id,
            'market_name' => 'Baneswar Hat, Rajshahi',
            'latitude' => 24.3750,
            'longitude' => 88.7500,
            'note' => 'Collected in fresh crates, prepared for transport.',
            'recorded_by_user_id' => $collector->id,
        ]);

        // --- Batch 4: Dinajpur Green Chili (Newly created at farm gate) ---
        Batch::create([
            'batch_code' => 'KB-20261012-CHL004',
            'crop_id' => $chili->id,
            'farmer_id' => $farmer->id,
            'origin_district_id' => $dinajpur->id,
            'farm_location_name' => 'Birganj Upazila, Dinajpur',
            'origin_latitude' => 25.8500,
            'origin_longitude' => 88.6600,
            'harvest_date' => now()->toDateString(),
            'initial_quantity' => 600.00,
            'available_quantity' => 600.00,
            'unit' => 'kg',
            'initial_farmer_price' => 80.00,
            'current_custodian_id' => $farmer->id,
            'current_stage' => 'farm_gate',
            'status' => 'active',
            'notes' => 'Fresh green chili lot, freshly picked today.',
        ]);

        // --- Batch 5: LONG-DISTANCE Multi-Middleman Chain ---
        // Route: Sylhet (Farm) -> Sylhet (Collector) -> Dhaka (Wholesale #1) -> Chattogram (Wholesale #2) -> Rangpur (Wholesale #3) -> Khulna (Retail) -> Rajshahi (Consumer)
        // Demonstrates 6 transactors / 5 middleman transfers across 6 districts (Sylhet, Dhaka, Chattogram, Rangpur, Khulna, Rajshahi)
        $sylhet = District::where('code', 'SYL')->first();
        $cumilla = District::where('code', 'CUM')->first();
        $khulna = District::where('code', 'KHL')->first();
        $rangpur = District::where('code', 'RAP')->first();
        $chattogram = District::where('code', 'CTG')->first();

        // Fetch extra wholesale users for the long chain
        $extraWholesalers = User::where('role', 'wholesale')
            ->where('id', '!=', $wholesale->id)
            ->orderBy('id')
            ->get();
        $wholesaleChattogram = $extraWholesalers->first();
        $wholesaleRangpur = $extraWholesalers->get(1);

        $batchLongChain = Batch::create([
            'batch_code' => 'KB-20261011-RIC005',
            'crop_id' => $rice->id,
            'farmer_id' => $farmer->id,
            'origin_district_id' => $sylhet->id,
            'farm_location_name' => 'Beani Bazar Rice Field, Sylhet',
            'origin_latitude' => 24.9425,
            'origin_longitude' => 91.8687,
            'harvest_date' => now()->subDays(6)->toDateString(),
            'initial_quantity' => 5000.00,
            'available_quantity' => 5000.00,
            'unit' => 'kg',
            'initial_farmer_price' => 40.00,
            'current_custodian_id' => $farmer->id,
            'current_stage' => 'farm_gate',
            'status' => 'active',
            'notes' => 'BRRI dhan28 Miniket rice — 6-stop cross-country supply chain (Sylhet → Dhaka → Chattogram → Rangpur → Khulna → Rajshahi).',
        ]);

        // Step 1: Farmer (Sylhet) -> Collector (Sylhet)  [farmer_to_collector → collector_custody]
        $handoffService->recordHandoff([
            'batch_id' => $batchLongChain->id,
            'seller_id' => $farmer->id,
            'buyer_id' => $collector->id,
            'stage' => 'farmer_to_collector',
            'quantity' => 5000.00,
            'price_per_unit' => 44.00,
            'district_id' => $sylhet->id,
            'market_name' => 'Beani Bazar Haat, Sylhet',
            'latitude' => 24.9425,
            'longitude' => 91.8687,
            'note' => 'Rice procured from grower at harvest rate in Sylhet region.',
            'recorded_by_user_id' => $collector->id,
        ]);

        // Step 2: Collector (Sylhet) -> Wholesale #1 (Dhaka)  [collector_to_wholesale → wholesale_custody]
        $handoffService->recordHandoff([
            'batch_id' => $batchLongChain->id,
            'seller_id' => $collector->id,
            'buyer_id' => $wholesale->id,
            'stage' => 'collector_to_wholesale',
            'quantity' => 4900.00,
            'price_per_unit' => 51.00,
            'district_id' => $dhaka->id,
            'market_name' => 'Shyambazar River Terminal Arat, Dhaka',
            'latitude' => 23.7081,
            'longitude' => 90.4143,
            'note' => 'Freight train Sylhet → Dhaka central warehouse; 100kg transit loss (5000 - 4900).',
            'recorded_by_user_id' => $wholesale->id,
        ]);        // Step 3: Wholesale #1 (Dhaka) -> Wholesale #2 (Chattogram)  [wholesale_to_retail → retail_custody]
        if ($wholesaleChattogram) {
            $handoffService->recordHandoff([
                'batch_id' => $batchLongChain->id,
                'seller_id' => $wholesale->id,
                'buyer_id' => $wholesaleChattogram->id,
                'stage' => 'wholesale_to_retail',
                'quantity' => 4850.00,
                'price_per_unit' => 56.00,
                'district_id' => $chattogram->id,
                'market_name' => 'Chattogram Port Arat Warehouse #7',
                'latitude' => 22.3579,
                'longitude' => 91.7973,
                'note' => 'Re-sold to Chattogram port wholesale for coastal distribution; 50kg handling loss.',
                'recorded_by_user_id' => $wholesaleChattogram->id,
            ]);
        }

        // Step 4: Wholesale #2 (Chattogram) -> Wholesale #3 (Rangpur)  [wholesale_to_retail → retail_custody]
        if ($wholesaleRangpur) {
            $handoffService->recordHandoff([
                'batch_id' => $batchLongChain->id,
                'seller_id' => $wholesaleChattogram->id,
                'buyer_id' => $wholesaleRangpur->id,
                'stage' => 'wholesale_to_retail',
                'quantity' => 4800.00,
                'price_per_unit' => 60.00,
                'district_id' => $rangpur->id,
                'market_name' => 'Rangpur Northern Arat Depot',
                'latitude' => 25.7439,
                'longitude' => 89.2611,
                'note' => 'Extra middleman: Chattogram → Rangpur distributor for far northern distribution; 50kg loss.',
                'recorded_by_user_id' => $wholesaleRangpur->id,
            ]);
        }

        // Step 5: Wholesale #3 (Rangpur) -> Retail (Khulna)  [wholesale_to_retail → retail_custody]
        if ($wholesaleRangpur) {
            $handoffService->recordHandoff([
                'batch_id' => $batchLongChain->id,
                'seller_id' => $wholesaleRangpur->id,
                'buyer_id' => $retail->id,
                'stage' => 'wholesale_to_retail',
                'quantity' => 4750.00,
                'price_per_unit' => 66.00,
                'district_id' => $khulna->id,
                'market_name' => 'Khulna City Market Wholesale Depot',
                'latitude' => 22.8172,
                'longitude' => 89.5300,
                'note' => 'Supply routed from Rangpur via coastal highway to Khulna regional market; 50kg loss.',
                'recorded_by_user_id' => $retail->id,
            ]);
        }
        }

        // Step 6: Retail (Khulna) -> Consumer (Rajshahi)  [retail_sale → sold_out]
        if ($wholesaleRangpur) {
            $consumer = User::where('role', 'consumer')->first();
            if ($consumer) {
                $handoffService->recordHandoff([
                    'batch_id' => $batchLongChain->id,
                    'seller_id' => $retail->id,
                    'buyer_id' => $consumer->id,
                    'stage' => 'retail_sale',
                    'quantity' => 4700.00,
                    'price_per_unit' => 78.00,
                    'district_id' => $rajshahi->id,
                    'market_name' => 'Barirhat Kitchen Market, Rajshahi',
                    'latitude' => 24.3765,
                    'longitude' => 88.6200,
                    'note' => 'Final consumer sale: 6-stop journey — Sylhet → Dhaka → Chattogram → Rangpur → Khulna → Rajshahi (5 middleman transfers, 300kg cumulative loss).',
                    'recorded_by_user_id' => $consumer->id,
                ]);
            }
        }
            $consumer = User::where('role', 'consumer')->first();
            if ($consumer) {
                $handoffService->recordHandoff([
                    'batch_id' => $batchLongChain->id,
                    'seller_id' => $retail->id,
                    'buyer_id' => $consumer->id,
                    'stage' => 'retail_sale',
                    'quantity' => 4700.00,
                    'price_per_unit' => 78.00,
                    'district_id' => $rajshahi->id,
                    'market_name' => 'Barirhat Kitchen Market, Rajshahi',
                    'latitude' => 24.3765,
                    'longitude' => 88.6200,
                    'note' => 'Final consumer sale: 6-stop journey — Sylhet → Dhaka → Chattogram → Rangpur → Khulna → Rajshahi (5 middleman transfers, 300kg cumulative loss).',
                    'recorded_by_user_id' => $consumer->id,
                ]);
            }
        }
    }
}

        // Step 6: Retail (Khulna) -> Consumer (Rajshahi)  [retail_sale → sold_out]
        $consumer = User::where('role', 'consumer')->first();
        if ($consumer) {
            $handoffService->recordHandoff([
                'batch_id' => $batchLongChain->id,
                'seller_id' => $retail->id,
                'buyer_id' => $consumer->id,
                'stage' => 'retail_sale',
                'quantity' => 4700.00,
                'price_per_unit' => 78.00,
                'district_id' => $rajshahi->id,
                'market_name' => 'Barirhat Kitchen Market, Rajshahi',
                'latitude' => 24.3765,
                'longitude' => 88.6200,
                'note' => 'Final consumer sale: 6-stop journey — Sylhet → Dhaka → Chattogram → Rangpur → Khulna → Rajshahi (5 middleman transfers, 300kg cumulative loss).',
                'recorded_by_user_id' => $consumer->id,
            ]);
        }

        // --- Batch 2: Pabna Onion (Gouging Anomaly Chain) ---
        $batch2 = Batch::create([
            'batch_code' => 'KB-20261011-ONI002',
            'crop_id' => $onion->id,
            'farmer_id' => $farmer->id,
            'origin_district_id' => $pabna->id,
            'farm_location_name' => 'Santhia Upazila, Pabna',
            'origin_latitude' => 24.0612,
            'origin_longitude' => 89.5312,
            'harvest_date' => now()->subDays(2)->toDateString(),
            'initial_quantity' => 1800.00,
            'available_quantity' => 1800.00,
            'unit' => 'kg',
            'initial_farmer_price' => 55.00,
            'current_custodian_id' => $farmer->id,
            'current_stage' => 'farm_gate',
            'status' => 'active',
            'notes' => 'Red Taherpuri local onion.',
        ]);

        // Step 1: Farmer -> Collector
        $handoffService->recordHandoff([
            'batch_id' => $batch2->id,
            'seller_id' => $farmer->id,
            'buyer_id' => $collector->id,
            'stage' => 'farmer_to_collector',
            'quantity' => 1800.00,
            'price_per_unit' => 65.00,
            'district_id' => $pabna->id,
            'market_name' => 'Kashinathpur Mokam, Pabna',
            'latitude' => 23.9500,
            'longitude' => 89.6000,
            'note' => 'Procured directly from grower.',
            'recorded_by_user_id' => $collector->id,
        ]);

        // Step 2: Collector -> Wholesale
        $handoffService->recordHandoff([
            'batch_id' => $batch2->id,
            'seller_id' => $collector->id,
            'buyer_id' => $wholesale->id,
            'stage' => 'collector_to_wholesale',
            'quantity' => 1800.00,
            'price_per_unit' => 78.00,
            'district_id' => $dhaka->id,
            'market_name' => 'Shyambazar River Terminal Arat, Dhaka',
            'latitude' => 23.7081,
            'longitude' => 90.4143,
            'note' => 'Transferred into central stock.',
            'recorded_by_user_id' => $wholesale->id,
        ]);

        // Step 3: Wholesale -> Retail (Excessive price hike: 78 to 115 = 47.44% markup vs 28% max)
        $handoffService->recordHandoff([
            'batch_id' => $batch2->id,
            'seller_id' => $wholesale->id,
            'buyer_id' => $retail->id,
            'stage' => 'wholesale_to_retail',
            'quantity' => 1800.00,
            'price_per_unit' => 115.00,
            'district_id' => $dhaka->id,
            'market_name' => 'Karwan Bazar Retail Shed, Dhaka',
            'latitude' => 23.7516,
            'longitude' => 90.3944,
            'note' => 'High demand market delivery.',
            'recorded_by_user_id' => $retail->id,
        ]);

        // Step 3: Wholesale #1 (Dhaka) -> Wholesale #2 (Chattogram)  [wholesale_to_retail → retail_custody]
        if ($wholesaleChattogram) {
            $handoffService->recordHandoff([
                'batch_id' => $batchLongChain->id,
                'seller_id' => $wholesale->id,
                'buyer_id' => $wholesaleChattogram->id,
                'stage' => 'wholesale_to_retail',
                'quantity' => 4850.00,
                'price_per_unit' => 56.00,
                'district_id' => $chattogram->id,
                'market_name' => 'Chattogram Port Arat Warehouse #7',
                'latitude' => 22.3579,
                'longitude' => 91.7973,
                'note' => 'Re-sold to Chattogram port wholesale for coastal distribution.',
                'recorded_by_user_id' => $wholesaleChattogram->id,
            ]);
        }

        // Step 4: Wholesale #2 (Chattogram) -> Wholesale #3 (Rangpur)  [wholesale_to_retail → retail_custody]
        if ($wholesaleRangpur) {
            $handoffService->recordHandoff([
                'batch_id' => $batchLongChain->id,
                'seller_id' => $wholesaleChattogram->id,
                'buyer_id' => $wholesaleRangpur->id,
                'stage' => 'wholesale_to_retail',
                'quantity' => 4750.00,
                'price_per_unit' => 60.00,
                'district_id' => $rangpur->id,
                'market_name' => 'Rangpur Northern Arat Depot',
                'latitude' => 25.7439,
                'longitude' => 89.2611,
            'note' => 'Extra middleman: Chattogram → Rangpur distributor for far northern distribution; 50kg loss.',
            'recorded_by_user_id' => $wholesaleRangpur->id,
        });
        }        // Step 5: Wholesale #3 (Rangpur) -> Retail (Khulna)  [wholesale_to_retail → retail_custody]
        if ($wholesaleRangpur) {
            $handoffService->recordHandoff([
                'batch_id' => $batchLongChain->id,
                'seller_id' => $wholesaleRangpur->id,
                'buyer_id' => $retail->id,
                'stage' => 'wholesale_to_retail',
                'quantity' => 4750.00,
                'price_per_unit' => 66.00,
                'district_id' => $khulna->id,
                'market_name' => 'Khulna City Market Wholesale Depot',
                'latitude' => 22.8172,
                'longitude' => 89.5300,
                'note' => 'Supply routed from Rangpur via coastal highway to Khulna regional market; 50kg loss.',
                'recorded_by_user_id' => $retail->id,
            ]);
        }

        // Step 6: Retail (Khulna) -> Consumer (Rajshahi)  [retail_sale → sold_out]
        if ($wholesaleRangpur) {
            $consumer = User::where('role', 'consumer')->first();
            if ($consumer) {
                $handoffService->recordHandoff([
                    'batch_id' => $batchLongChain->id,
                    'seller_id' => $retail->id,
                    'buyer_id' => $consumer->id,
                    'stage' => 'retail_sale',
                    'quantity' => 4700.00,
                    'price_per_unit' => 78.00,
                    'district_id' => $rajshahi->id,
                    'market_name' => 'Barirhat Kitchen Market, Rajshahi',
                    'latitude' => 24.3765,
                    'longitude' => 88.6200,
                    'note' => 'Final consumer sale: 6-stop journey — Sylhet → Dhaka → Chattogram → Rangpur → Khulna → Rajshahi (5 middleman transfers, 300kg cumulative loss).',
                    'recorded_by_user_id' => $consumer->id,
                ]);
            }
        }
    }
}

