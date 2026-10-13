<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Crop;
use App\Models\District;
use App\Models\Marketplace;
use App\Models\RetailPrice;
use App\Models\User;
use App\Services\BatchHandoffService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MarketplaceShowcaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Market demos require local/test environment.');
        }
        $districts = District::orderBy('id')->get();
        $crops = Crop::where('is_active', true)->where('unit_needs_review', false)->get();
        $collector = User::where('email', 'collector.karim@krishibyte.local')->firstOrFail();
        $wholesale = User::where('email', 'wholesale.hasan@krishibyte.local')->firstOrFail();
        $service = app(BatchHandoffService::class);
        foreach ($districts as $district) {
            foreach (['Central Market', 'Wholesale Market', 'Neighbourhood Market'] as $name) {
                Marketplace::firstOrCreate(['district_id' => $district->id, 'name' => 'Illustrative '.$district->name.' '.$name], ['is_demo' => true]);
            }
        }
        foreach (Marketplace::where('is_active', true)->get() as $market) {
            DB::transaction(function () use ($market, $districts, $crops, $collector, $wholesale, $service) {
                for ($shopNo = 1; $shopNo <= 2; $shopNo++) {
                    $shop = User::firstOrCreate(['email' => "shop.{$market->id}.{$shopNo}@demo.krishibyte.invalid"], ['name' => 'Demo shop owner '.$market->id.'-'.$shopNo, 'shop_name' => "Illustrative Produce Shop {$shopNo}", 'role' => 'retail', 'account_status' => 'active', 'trading_approved' => true, 'district_id' => $market->district_id, 'marketplace_id' => $market->id, 'nid_or_trade_license' => 'SYNTHETIC-DEMO-LICENSE-'.$market->id.'-'.$shopNo, 'password' => Hash::make('RetailPass123!')]);
                    for ($lotNo = 1; $lotNo <= 2; $lotNo++) {
                        $code = "KB-MARKET-DEMO-{$market->id}-{$shopNo}-{$lotNo}";
                        if (Batch::where('batch_code', $code)->exists()) {
                            continue;
                        }
                        $index = $districts->search(fn ($d) => $d->id === $market->district_id);
                        $origin = $districts[(($index === false ? 0 : $index) + $lotNo + $shopNo) % $districts->count()];
                        $farmer = User::firstOrCreate(['email' => "grower.{$origin->id}@demo.krishibyte.invalid"], ['name' => 'Illustrative '.$origin->name.' Grower', 'role' => 'farmer', 'account_status' => 'active', 'district_id' => $origin->id, 'farmer_profile_public' => true, 'publication_consented_at' => now(), 'consent_method' => 'Synthetic demo profile; no real person or consent', 'cultivation_notes' => 'Synthetic marketplace showcase farmer.', 'password' => Hash::make(Str::random(40))]);
                        $crop = $crops[($market->id + $shopNo + $lotNo) % $crops->count()];
                        $farmPrice = 20 + (($market->id + $lotNo) % 8) * 10;
                        $quantity = 80 + $lotNo * 25.5;
                        $batch = Batch::create(['batch_code' => $code, 'crop_id' => $crop->id, 'farmer_id' => $farmer->id, 'origin_district_id' => $origin->id, 'harvest_date' => now('Asia/Dhaka')->subDays($lotNo), 'initial_quantity' => $quantity, 'available_quantity' => $quantity, 'unit' => $crop->unit, 'initial_farmer_price' => $farmPrice, 'current_custodian_id' => $farmer->id, 'current_stage' => 'farm_gate', 'status' => 'active', 'verification_status' => 'unverified', 'notes' => 'SYNTHETIC MARKET DEMO: imported farm-gate record, no real handwritten agreement, payment or produce. Onward handoffs are synthetic accepted proposals.']);
                        $service->recordHandoff(['batch_id' => $batch->id, 'seller_id' => $farmer->id, 'buyer_id' => $collector->id, 'quantity' => $quantity, 'price_per_unit' => $farmPrice, 'district_id' => $origin->id, 'recorded_by_user_id' => $collector->id, 'note' => 'Synthetic imported farm-gate fixture; no farmer attestation.', '_legacy_import' => true]);
                        $seller = $collector;
                        foreach ([[$wholesale, $farmPrice + 5], [$shop, $farmPrice + 12]] as [$buyer, $price]) {
                            $proposal = $service->propose(['batch_id' => $batch->id, 'seller_id' => $seller->id, 'buyer_id' => $buyer->id, 'quantity' => $quantity, 'price_per_unit' => $price, 'district_id' => $market->district_id, 'market_name' => $market->name, 'recorded_by_user_id' => $seller->id, 'note' => 'Synthetic marketplace showcase handoff.'], (string) Str::uuid());
                            $service->decide($proposal->id, $buyer->id, 'accepted');
                            $seller = $buyer;
                        }
                        foreach ([0, 1] as $day) {
                            $quote = $farmPrice + 18 + ($day ? (($market->id % 3) - 1) * 8 : 0);
                            RetailPrice::create(['batch_id' => $batch->id, 'retailer_id' => $shop->id, 'marketplace_id' => $market->id, 'price_per_unit' => $quote, 'unit' => $batch->unit, 'request_key' => (string) Str::uuid(), 'is_demo' => true, 'created_at' => now()->subDays(1 - $day), 'updated_at' => now()->subDays(1 - $day)]);
                        }
                    }
                }
            });
        }
        $rafiq = User::where('email', 'retail.rafiq@krishibyte.local')->first();
        if ($rafiq && ! $rafiq->marketplace_id) {
            $market = Marketplace::where('district_id', $rafiq->district_id)->where('is_demo', true)->first();
            if ($market) {
                $rafiq->update(['marketplace_id' => $market->id, 'shop_name' => 'Rafiq Demo Produce Shop']);
            }
        }
        if ($rafiq?->marketplace_id) {
            foreach (Batch::where('current_custodian_id', $rafiq->id)->where('status', 'active')->get() as $batch) {
                RetailPrice::firstOrCreate(['batch_id' => $batch->id, 'retailer_id' => $rafiq->id], ['marketplace_id' => $rafiq->marketplace_id, 'price_per_unit' => (float) $batch->acquisition_price + 5, 'unit' => $batch->unit, 'request_key' => (string) Str::uuid(), 'is_demo' => true]);
            }
        }
    }
}
