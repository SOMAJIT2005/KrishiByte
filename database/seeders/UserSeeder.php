<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $dhaka = District::where('code', 'DHK')->first();
        $bogura = District::where('code', 'BOG')->first();

        $users = [
            [
                'name' => 'System Administrator',
                'email' => 'admin@krishibyte.local',
                'phone' => '+8801700000001',
                'role' => 'admin',
                'account_status' => 'active',
                'district_id' => $dhaka?->id,
                'password' => Hash::make('AdminPass123!'),
            ],
            [
                'name' => 'Dr. Shamsul Huda (Market Monitor)',
                'email' => 'authority@krishibyte.local',
                'phone' => '+8801700000002',
                'role' => 'authority',
                'account_status' => 'active',
                'district_id' => $dhaka?->id,
                'password' => Hash::make('AuthorityPass123!'),
            ],
            [
                'name' => 'Md. Rahim Uddin (Farmer)',
                'email' => 'farmer.rahim@krishibyte.local',
                'phone' => '+8801711000001',
                'role' => 'farmer',
                'account_status' => 'active',
                'nid_or_trade_license' => 'NID-19821092837482',
                'district_id' => $bogura?->id,
                'password' => Hash::make('FarmerPass123!'),
            ],
            [
                'name' => 'Karim Bepari (Local Collector / Faria)',
                'email' => 'collector.karim@krishibyte.local',
                'phone' => '+8801712000002',
                'role' => 'collector',
                'account_status' => 'active',
                'nid_or_trade_license' => 'TL-BOG-2024-889',
                'district_id' => $bogura?->id,
                'password' => Hash::make('CollectorPass123!'),
            ],
            [
                'name' => 'Hasan Aratdar (Wholesale Vendor)',
                'email' => 'wholesale.hasan@krishibyte.local',
                'phone' => '+8801713000003',
                'role' => 'wholesale',
                'account_status' => 'active',
                'nid_or_trade_license' => 'TL-DHK-2023-4122',
                'district_id' => $dhaka?->id,
                'password' => Hash::make('WholesalePass123!'),
            ],
            [
                'name' => 'Rafiq Dokandar (Retailer - Karwan Bazar)',
                'email' => 'retail.rafiq@krishibyte.local',
                'phone' => '+8801714000004',
                'role' => 'retail',
                'account_status' => 'active',
                'nid_or_trade_license' => 'TL-DHK-2025-901',
                'district_id' => $dhaka?->id,
                'password' => Hash::make('RetailPass123!'),
            ],
            [
                'name' => 'Tariqul Islam (Consumer / Citizen)',
                'email' => 'consumer.tariq@krishibyte.local',
                'phone' => '+8801715000005',
                'role' => 'consumer',
                'account_status' => 'active',
                'district_id' => $dhaka?->id,
                'password' => Hash::make('ConsumerPass123!'),
            ],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(['email' => $u['email']], $u);
        }
    }
}
