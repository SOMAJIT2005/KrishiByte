<?php

namespace Database\Seeders;

use App\Models\Crop;
use App\Models\District;
use App\Models\PriceReport;
use App\Models\User;
use Illuminate\Database\Seeder;

class PriceReportSeeder extends Seeder
{
    public function run(): void
    {
        $dhaka = District::where('code', 'DHK')->first();
        $onion = Crop::where('name', 'like', '%Onion%')->first();
        $potato = Crop::where('name', 'like', '%Potato%')->first();
        $authority = User::where('role', 'authority')->first();

        // 1. Pending report (waiting for authority review)
        PriceReport::create([
            'tracking_code' => 'RPT-20261012-ON001',
            'reporter_name' => 'Tariqul Islam',
            'reporter_phone' => '+8801715000005',
            'reporter_email' => 'consumer.tariq@krishibyte.local',
            'crop_id' => $onion->id,
            'district_id' => $dhaka->id,
            'market_name' => 'Karwan Bazar Retail Stall #42',
            'reported_price' => 125.00,
            'unit' => 'kg',
            'report_narrative' => 'Vendor refused to sell below 125 BDT/kg citing artificial wholesale shortage despite high supply trucks arriving.',
            'incident_date' => now()->toDateString(),
            'latitude' => 23.7518,
            'longitude' => 90.3942,
            'evidence_path' => null,
            'status' => 'pending',
            'ip_address' => '127.0.0.1',
        ]);

        // 2. Verified report
        PriceReport::create([
            'tracking_code' => 'RPT-20261011-PO002',
            'reporter_name' => 'M. Alam',
            'reporter_phone' => '+8801819000008',
            'reporter_email' => 'm.alam@example.com',
            'crop_id' => $potato->id,
            'district_id' => $dhaka->id,
            'market_name' => 'Mohammadpur Krishi Market',
            'reported_price' => 42.00,
            'unit' => 'kg',
            'report_narrative' => 'Stall selling standard diamond potato at BDT 42 when wholesale arrival price was BDT 32.',
            'incident_date' => now()->subDay()->toDateString(),
            'latitude' => 23.7710,
            'longitude' => 90.3605,
            'status' => 'verified',
            'reviewed_by_user_id' => $authority?->id,
            'moderation_notes' => 'Investigated on site by field officer. Stall issued warning to align with retail guidelines.',
            'ip_address' => '127.0.0.1',
        ]);

        // 3. Rejected report
        PriceReport::create([
            'tracking_code' => 'RPT-20261009-RE003',
            'reporter_name' => 'Anonymous',
            'reporter_phone' => null,
            'reporter_email' => null,
            'crop_id' => $potato->id,
            'district_id' => $dhaka->id,
            'market_name' => 'Unknown grocery shop',
            'reported_price' => 95.00,
            'unit' => 'kg',
            'report_narrative' => 'Absurd price claim without specific location or evidence.',
            'incident_date' => now()->subDays(3)->toDateString(),
            'latitude' => null,
            'longitude' => null,
            'status' => 'rejected',
            'reviewed_by_user_id' => $authority?->id,
            'moderation_notes' => 'Unverifiable claims with no verifiable stall or market address.',
            'ip_address' => '127.0.0.1',
        ]);
    }
}
