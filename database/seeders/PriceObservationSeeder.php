<?php

namespace Database\Seeders;

use App\Models\Crop;
use App\Models\District;
use App\Models\PriceObservation;
use App\Models\User;
use Illuminate\Database\Seeder;

class PriceObservationSeeder extends Seeder
{
    public function run(): void
    {
        $authority = User::where('role', 'authority')->first();
        $potato = Crop::where('name', 'like', '%Potato%')->first();
        $onion = Crop::where('name', 'like', '%Onion%')->first();
        $tomato = Crop::where('name', 'like', '%Tomato%')->first();
        $chili = Crop::where('name', 'like', '%Chili%')->first();

        $dhaka = District::where('code', 'DHK')->first();
        $bogura = District::where('code', 'BOG')->first();
        $ctg = District::where('code', 'CTG')->first();
        $rajshahi = District::where('code', 'RAJ')->first();

        $observations = [
            // Potato in Dhaka (Multiple verified observations for robust statistics)
            ['crop_id' => $potato->id, 'district_id' => $dhaka->id, 'market_name' => 'Karwan Bazar', 'observed_retail_price' => 38.00, 'observation_date' => now()->toDateString()],
            ['crop_id' => $potato->id, 'district_id' => $dhaka->id, 'market_name' => 'Mohammadpur Krishi Market', 'observed_retail_price' => 39.00, 'observation_date' => now()->toDateString()],
            ['crop_id' => $potato->id, 'district_id' => $dhaka->id, 'market_name' => 'Kaptan Bazar', 'observed_retail_price' => 37.50, 'observation_date' => now()->toDateString()],
            ['crop_id' => $potato->id, 'district_id' => $dhaka->id, 'market_name' => 'Mirpur-1 Bazar', 'observed_retail_price' => 40.00, 'observation_date' => now()->toDateString()],
            ['crop_id' => $potato->id, 'district_id' => $dhaka->id, 'market_name' => 'Sadarghat Bazar', 'observed_retail_price' => 38.50, 'observation_date' => now()->toDateString()],

            // Potato in Bogura (Origin district)
            ['crop_id' => $potato->id, 'district_id' => $bogura->id, 'market_name' => 'Fateh Ali Bazar', 'observed_retail_price' => 28.00, 'observation_date' => now()->toDateString()],
            ['crop_id' => $potato->id, 'district_id' => $bogura->id, 'market_name' => 'Rajar Bazar', 'observed_retail_price' => 29.00, 'observation_date' => now()->toDateString()],
            ['crop_id' => $potato->id, 'district_id' => $bogura->id, 'market_name' => 'Mahasthangarh Haat', 'observed_retail_price' => 27.50, 'observation_date' => now()->toDateString()],

            // Onion in Dhaka
            ['crop_id' => $onion->id, 'district_id' => $dhaka->id, 'market_name' => 'Karwan Bazar', 'observed_retail_price' => 110.00, 'observation_date' => now()->toDateString()],
            ['crop_id' => $onion->id, 'district_id' => $dhaka->id, 'market_name' => 'Shyambazar Retail', 'observed_retail_price' => 105.00, 'observation_date' => now()->toDateString()],
            ['crop_id' => $onion->id, 'district_id' => $dhaka->id, 'market_name' => 'Mohammadpur Krishi Market', 'observed_retail_price' => 115.00, 'observation_date' => now()->toDateString()],

            // Tomato in Rajshahi
            ['crop_id' => $tomato->id, 'district_id' => $rajshahi->id, 'market_name' => 'Shaheb Bazar', 'observed_retail_price' => 55.00, 'observation_date' => now()->toDateString()],
            ['crop_id' => $tomato->id, 'district_id' => $rajshahi->id, 'market_name' => 'Baneswar Hat', 'observed_retail_price' => 52.00, 'observation_date' => now()->toDateString()],
            ['crop_id' => $tomato->id, 'district_id' => $rajshahi->id, 'market_name' => 'Binodpur Bazar', 'observed_retail_price' => 54.00, 'observation_date' => now()->toDateString()],

            // Chili in Chattogram (Only 1 sample - demonstrates 'low confidence / insufficient data' indicator!)
            ['crop_id' => $chili->id, 'district_id' => $ctg->id, 'market_name' => 'Reazuddin Bazar', 'observed_retail_price' => 135.00, 'observation_date' => now()->toDateString()],
        ];

        foreach ($observations as $obs) {
            PriceObservation::firstOrCreate($obs, [
                'unit' => 'kg', 'is_demo' => true,
                'source' => 'verified_market_survey',
                'verification_status' => 'verified',
                'entered_by_user_id' => $authority?->id,
                'notes' => 'Illustrative demo survey; not an official government observation.',
            ]);
        }
    }
}
