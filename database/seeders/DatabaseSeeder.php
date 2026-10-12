<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            DistrictSeeder::class,
            CropSeeder::class,
            UserSeeder::class,
            MarkupRuleSeeder::class,
            BatchAndTransactionSeeder::class,
            PriceObservationSeeder::class,
            PriceReportSeeder::class,
        ]);
    }
}
