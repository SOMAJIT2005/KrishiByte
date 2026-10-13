<?php

namespace Database\Seeders;

use App\Models\Crop;
use App\Models\District;
use App\Models\PriceObservation;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoPriceHistorySeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Illustrative history is only available in local/test environments.');
        }
        $author = User::where('email', 'authority@krishibyte.local')->firstOrFail();
        $patterns = [
            'Potato' => [22, 23, 25, 24, 27, 29, 28, 31, 33, 32, 35, 37, 40, 44],
            'Onion' => [115, 112, 110, 106, 104, 100, 96, 93, 90, 84, 80, 75, 70, 64],
            'Tomato' => [45, 49, 42, 57, 50, 65, 48, 61, 54, 70, 58, 46, 62, 55],
            'Green Chili' => [90, 94, 100, 120, 155, 195, 240, 210, 180, 150, 140, 130, 115, 100],
            'Brinjal' => [48, 48, 48, 48, 48, 48, 48, 48, 48, 48, 48, 48, 48, 48],
            'Cauliflower' => [70, 66, 60, 55, 48, 42, 38, 35, 40, 45, 50, 54, 58, 62],
            'Rice' => [78, 78, 77, 77, 76, 76, 75, 75, 74, 74, 73, 73, 72, 71],
        ];
        foreach ($patterns as $name => $prices) {
            $crop = Crop::where('name', 'like', $name.' (%')->first();
            if (! $crop) {
                continue;
            }
            foreach (['Dhaka' => 0, 'Bogura' => -4, 'Chattogram' => 6] as $districtName => $regionalDifference) {
                $district = District::where('name', $districtName)->firstOrFail();
                foreach ($prices as $index => $price) {
                    foreach ([-2, -1, 0, 1, 2] as $offset) {
                        PriceObservation::updateOrCreate(['crop_id' => $crop->id, 'district_id' => $district->id, 'market_name' => 'Illustrative '.$districtName.' analytics sample '.($offset + 3), 'observation_date' => now('Asia/Dhaka')->subDays(13 - $index)->toDateString(), 'unit' => $crop->unit, 'entered_by_user_id' => $author->id], ['observed_retail_price' => $price + $regionalDifference + $offset, 'source' => 'verified_market_survey', 'verification_status' => 'verified', 'is_demo' => true, 'notes' => 'Illustrative demo price history; synthetic values for presentation, not market evidence.']);
                    }
                }
            }
        }
    }
}
