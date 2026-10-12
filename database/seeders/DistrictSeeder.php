<?php

namespace Database\Seeders;

use App\Models\District;
use Illuminate\Database\Seeder;

class DistrictSeeder extends Seeder
{
    public function run(): void
    {
        $districts = [
            ['name' => 'Bogura', 'division' => 'Rajshahi', 'code' => 'BOG', 'latitude' => 24.8465, 'longitude' => 89.3777],
            ['name' => 'Rajshahi', 'division' => 'Rajshahi', 'code' => 'RAJ', 'latitude' => 24.3636, 'longitude' => 88.6241],
            ['name' => 'Dinajpur', 'division' => 'Rangpur', 'code' => 'DIN', 'latitude' => 25.6217, 'longitude' => 88.6355],
            ['name' => 'Rangpur', 'division' => 'Rangpur', 'code' => 'RAN', 'latitude' => 25.7439, 'longitude' => 89.2752],
            ['name' => 'Dhaka', 'division' => 'Dhaka', 'code' => 'DHK', 'latitude' => 23.8103, 'longitude' => 90.4125],
            ['name' => 'Gazipur', 'division' => 'Dhaka', 'code' => 'GAZ', 'latitude' => 24.0023, 'longitude' => 90.4264],
            ['name' => 'Mymensingh', 'division' => 'Mymensingh', 'code' => 'MYM', 'latitude' => 24.7471, 'longitude' => 90.4203],
            ['name' => 'Chattogram', 'division' => 'Chattogram', 'code' => 'CTG', 'latitude' => 22.3569, 'longitude' => 91.7832],
            ['name' => 'Cumilla', 'division' => 'Chattogram', 'code' => 'CUM', 'latitude' => 23.4607, 'longitude' => 91.1809],
            ['name' => 'Jashore', 'division' => 'Khulna', 'code' => 'JAS', 'latitude' => 23.1664, 'longitude' => 89.2081],
            ['name' => 'Pabna', 'division' => 'Rajshahi', 'code' => 'PAB', 'latitude' => 24.0064, 'longitude' => 89.2372],
            ['name' => 'Barishal', 'division' => 'Barishal', 'code' => 'BAR', 'latitude' => 22.7010, 'longitude' => 90.3535],
            ['name' => 'Sylhet', 'division' => 'Sylhet', 'code' => 'SYL', 'latitude' => 24.8949, 'longitude' => 91.8687],
        ];

        foreach ($districts as $d) {
            District::updateOrCreate(['code' => $d['code']], $d);
        }
    }
}
