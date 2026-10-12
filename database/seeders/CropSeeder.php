<?php

namespace Database\Seeders;

use App\Models\Crop;
use Illuminate\Database\Seeder;

class CropSeeder extends Seeder
{
    public function run(): void
    {
        $crops = [
            ['name' => 'Potato (গোল আলু)', 'category' => 'Vegetable', 'variety_or_grade' => 'Diamond / Cardinal', 'unit' => 'kg', 'is_active' => true],
            ['name' => 'Onion (দেশি পেঁয়াজ)', 'category' => 'Spice', 'variety_or_grade' => 'Taherpuri / Local Red', 'unit' => 'kg', 'is_active' => true],
            ['name' => 'Tomato (টমেটো)', 'category' => 'Vegetable', 'variety_or_grade' => 'Hybrid Roma', 'unit' => 'kg', 'is_active' => true],
            ['name' => 'Green Chili (কাঁচামরিচ)', 'category' => 'Spice', 'variety_or_grade' => 'Bindu / Bogura Local', 'unit' => 'kg', 'is_active' => true],
            ['name' => 'Brinjal (বেগুন)', 'category' => 'Vegetable', 'variety_or_grade' => 'Uttara / Black Beauty', 'unit' => 'kg', 'is_active' => true],
            ['name' => 'Cauliflower (ফুলকপি)', 'category' => 'Vegetable', 'variety_or_grade' => 'Snow White', 'unit' => 'kg', 'is_active' => true],
            ['name' => 'Rice (চাল - মিনিকেট)', 'category' => 'Grain', 'variety_or_grade' => 'Grade A Parboiled', 'unit' => 'kg', 'is_active' => true],
        ];

        foreach ($crops as $c) {
            Crop::updateOrCreate(['name' => $c['name']], $c);
        }
    }
}
