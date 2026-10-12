<?php

namespace Database\Seeders;

use App\Models\Crop;
use App\Models\MarkupRule;
use App\Models\User;
use Illuminate\Database\Seeder;

class MarkupRuleSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        if (!$admin) return;

        $potato = Crop::where('name', 'like', '%Potato%')->first();
        $onion = Crop::where('name', 'like', '%Onion%')->first();
        $tomato = Crop::where('name', 'like', '%Tomato%')->first();

        $rules = [
            // Global baseline fallback
            [
                'crop_id' => null,
                'stage' => 'all',
                'max_markup_percent' => 35.00,
                'max_spread_amount' => 25.00,
                'is_active' => true,
                'created_by_user_id' => $admin->id,
                'explanation' => 'Default national ceiling guideline across unclassified stages.',
            ],
            // Potato specific stage rules
            [
                'crop_id' => $potato?->id,
                'stage' => 'farmer_to_collector',
                'max_markup_percent' => 25.00,
                'max_spread_amount' => 8.00,
                'is_active' => true,
                'created_by_user_id' => $admin->id,
                'explanation' => 'Maximum fair margin for primary rural aggregation of potatoes.',
            ],
            [
                'crop_id' => $potato?->id,
                'stage' => 'collector_to_wholesale',
                'max_markup_percent' => 25.00,
                'max_spread_amount' => 10.00,
                'is_active' => true,
                'created_by_user_id' => $admin->id,
                'explanation' => 'Inter-district transport and arat wholesale commission guideline.',
            ],
            [
                'crop_id' => $potato?->id,
                'stage' => 'wholesale_to_retail',
                'max_markup_percent' => 30.00,
                'max_spread_amount' => 12.00,
                'is_active' => true,
                'created_by_user_id' => $admin->id,
                'explanation' => 'Retail shop level markup guideline accounting for spoilage.',
            ],
            // Onion specific rules
            [
                'crop_id' => $onion?->id,
                'stage' => 'wholesale_to_retail',
                'max_markup_percent' => 28.00,
                'max_spread_amount' => 18.00,
                'is_active' => true,
                'created_by_user_id' => $admin->id,
                'explanation' => 'Domestic onion retail spread cap to mitigate artificial hoarding.',
            ],
            // Tomato specific rules
            [
                'crop_id' => $tomato?->id,
                'stage' => 'wholesale_to_retail',
                'max_markup_percent' => 35.00,
                'max_spread_amount' => 20.00,
                'is_active' => true,
                'created_by_user_id' => $admin->id,
                'explanation' => 'Perishable vegetable markup rule with allowable buffer.',
            ],
        ];

        foreach ($rules as $r) {
            MarkupRule::create($r);
        }
    }
}
