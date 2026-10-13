<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Crop;
use App\Models\District;
use App\Models\FarmerEnrollment;
use App\Models\FarmerRating;
use App\Models\PriceObservation;
use App\Models\PriceReport;
use App\Models\User;
use App\Services\AuditLoggerService;
use App\Services\BatchHandoffService;
use App\Services\FarmPurchaseService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ShowcaseScenarioSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Showcase data is restricted to local/test environments.');
        }
        $roles = [];
        foreach (['farmer' => 'farmer.rahim', 'collector' => 'collector.karim', 'wholesale' => 'wholesale.hasan', 'retail' => 'retail.rafiq', 'consumer' => 'consumer.tariq', 'authority' => 'authority', 'admin' => 'admin'] as $role => $email) {
            $roles[$role] = User::where('email', $email.'@krishibyte.local')->where('role', $role)->where('account_status', 'active')->firstOrFail();
        }
        $farmer = $roles['farmer'];
        $bogura = District::where('name', 'Bogura')->firstOrFail();
        $dhaka = District::where('name', 'Dhaka')->firstOrFail();
        $ownedFiles = [];
        try {
            DB::transaction(function () use ($roles, $farmer, $bogura, $dhaka, &$ownedFiles) {
                if (! $farmer->grown_crop_ids) {
                    $farmer->update(['farmer_profile_public' => true, 'grown_crop_ids' => Crop::where('is_active', true)->pluck('id')->all(), 'cultivation_notes' => $farmer->cultivation_notes ?: 'Illustrative demo farmer growing seasonal vegetables and rice. Records are synthetic showcase scenarios.']);
                }
                $handoffs = app(BatchHandoffService::class);
                foreach ([
                    ['KB-DEMO-COLLECTOR', 'Potato', 250, 25, [], null],
                    ['KB-DEMO-WHOLESALE', 'Tomato', 200, 35, [['wholesale', 42]], null],
                    ['KB-DEMO-RETAIL', 'Rice', 500, 62, [['wholesale', 66], ['retail', 70]], 'partial'],
                    ['KB-DEMO-HIGH-MARKUP', 'Onion', 100, 30, [['wholesale', 75], ['retail', 120]], null],
                    ['KB-DEMO-PENDING', 'Potato', 150, 28, [['wholesale', 31]], 'proposed'],
                    ['KB-DEMO-DISPUTED', 'Tomato', 120, 32, [['retail', 36]], 'disputed'],
                    ['KB-DEMO-CANCELLED', 'Cauliflower', 80, 35, [['wholesale', 39]], 'cancelled'],
                    ['KB-DEMO-SOLD-OUT', 'Brinjal', 50, 28, [['wholesale', 32], ['retail', 35]], 'sold_out'],
                    ['KB-DEMO-FARM-READY', 'Cauliflower', 200, 28, [], 'farm_ready'],
                    ['KB-DEMO-REJECTED', 'Green Chili', 60, 65, [], 'rejected'],
                ] as [$code,$cropName,$quantity,$price,$chain,$scenario]) {
                    if (Batch::where('batch_code', $code)->exists()) {
                        continue;
                    } // Preserve actions performed during a presentation.
                    $crop = Crop::where('name', 'like', $cropName.' (%')->firstOrFail();
                    $batch = Batch::create(['batch_code' => $code, 'crop_id' => $crop->id, 'farmer_id' => $farmer->id, 'origin_district_id' => $bogura->id, 'harvest_date' => now('Asia/Dhaka')->subDay()->toDateString(), 'initial_quantity' => $quantity, 'available_quantity' => $quantity, 'unit' => $crop->unit, 'initial_farmer_price' => $price, 'current_custodian_id' => $farmer->id, 'current_stage' => 'farm_gate', 'status' => 'active', 'verification_status' => $scenario === 'rejected' ? 'rejected' : 'unverified', 'notes' => 'Illustrative demo scenario: '.$scenario.'. Not a real crop lot, contract or payment.']);
                    if (in_array($scenario, ['farm_ready', 'rejected'], true)) {
                        continue;
                    }
                    $evidence = $this->agreement($code, $crop->name, $quantity, $price);
                    $ownedFiles[] = $evidence['evidence_path'];
                    $purchase = app(FarmPurchaseService::class)->record(['batch_id' => $batch->id, 'farmer_id' => $farmer->id, 'crop_id' => $crop->id, 'quantity' => number_format($quantity, 2, '.', ''), 'price_per_unit' => number_format($price, 2, '.', ''), 'district_id' => $bogura->id, 'harvest_date' => $batch->harvest_date->toDateString(), 'market_name' => 'Illustrative demo collection', 'witness_name' => 'Synthetic showcase witness', 'agreement_read_aloud' => 1, 'request_key' => (string) Str::uuid()], $evidence, $roles['collector']->id);
                    $seller = $roles['collector'];
                    foreach ($chain as [$role,$salePrice]) {
                        $buyer = $roles[$role];
                        $proposal = $handoffs->propose(['batch_id' => $batch->id, 'seller_id' => $seller->id, 'buyer_id' => $buyer->id, 'quantity' => $quantity, 'price_per_unit' => $salePrice, 'district_id' => $dhaka->id, 'market_name' => 'Illustrative demo handoff', 'recorded_by_user_id' => $seller->id], (string) Str::uuid());
                        if ($scenario === 'proposed') {
                            break;
                        }
                        if ($scenario === 'disputed') {
                            $handoffs->decide($proposal->id, $buyer->id, 'disputed', 'Illustrative demo: buyer disputes the quoted price.');
                            break;
                        }
                        if ($scenario === 'cancelled') {
                            $handoffs->decide($proposal->id, $seller->id, 'cancelled', 'Illustrative demo: seller selected the wrong recipient.');
                            break;
                        }
                        $handoffs->decide($proposal->id, $buyer->id, 'accepted');
                        $seller = $buyer;
                    }
                    if (in_array($scenario, ['partial', 'sold_out'], true)) {
                        $handoffs->recordRetailSale(['batch_id' => $batch->id, 'seller_id' => $seller->id, 'buyer_id' => null, 'quantity' => $scenario === 'sold_out' ? $quantity : 40, 'price_per_unit' => $scenario === 'sold_out' ? 40 : 74, 'district_id' => $dhaka->id, 'market_name' => 'Illustrative demo retail sale', 'recorded_by_user_id' => $seller->id], (string) Str::uuid());
                        if ($scenario === 'partial') {
                            $handoffs->recordRetailSale(['batch_id' => $batch->id, 'seller_id' => $seller->id, 'buyer_id' => $roles['consumer']->id, 'quantity' => 10, 'price_per_unit' => 75, 'district_id' => $dhaka->id, 'market_name' => 'Illustrative demo registered customer sale', 'recorded_by_user_id' => $seller->id], (string) Str::uuid());
                        }
                    }
                    if ($scenario === 'partial') {
                        $batch->update(['verification_status' => 'verified', 'verified_by_admin_id' => $roles['admin']->id, 'verified_at' => now(), 'admin_verification_notes' => 'Illustrative seeded review state; not certification of real goods.']);
                    }
                }
                foreach (['price', 'quantity', 'quality', 'traceability'] as $category) {
                    $crop = Crop::where('name', 'like', 'Potato (%')->firstOrFail();
                    foreach (['pending', 'verified', 'rejected'] as $status) {
                        $report = PriceReport::firstOrCreate(['tracking_code' => 'RPT-DEMO-'.strtoupper($category).'-'.strtoupper($status)], ['reporter_user_id' => $roles['consumer']->id, 'category' => $category, 'is_demo' => true, 'crop_id' => $crop->id, 'district_id' => $dhaka->id, 'market_name' => 'Illustrative demo market', 'reported_price' => $category === 'price' ? 150 : null, 'unit' => $crop->unit, 'incident_date' => now('Asia/Dhaka')->toDateString(), 'report_narrative' => 'Illustrative demo '.$category.' concern; synthetic scenario, not an allegation about any real participant.', 'status' => $status, 'reviewed_by_user_id' => $status === 'pending' ? null : $roles['authority']->id, 'moderation_notes' => $status === 'pending' ? null : 'Illustrative seeded moderation outcome.', 'ip_address' => '127.0.0.1']);
                        if ($category === 'price' && $report->status === 'verified') {
                            PriceObservation::firstOrCreate(['price_report_id' => $report->id], ['crop_id' => $crop->id, 'district_id' => $dhaka->id, 'market_name' => $report->market_name, 'observed_retail_price' => $report->reported_price, 'unit' => $crop->unit, 'observation_date' => $report->incident_date, 'source' => 'verified_citizen_report', 'verification_status' => 'verified', 'is_demo' => true, 'entered_by_user_id' => $roles['authority']->id, 'notes' => 'Illustrative demo complaint sample, not representative market evidence.']);
                        }
                    }
                }
                FarmerEnrollment::firstOrCreate(['tracking_code' => '00000000-0000-4000-8000-000000000001'], ['name' => 'Illustrative Demo Enrollment', 'phone' => '01700000000', 'district_id' => $bogura->id, 'grown_crop_ids' => Crop::where('is_active', true)->take(3)->pluck('id')->all(), 'cultivation_notes' => 'Synthetic enrollment for the administrator walkthrough. No real identity or farm.', 'status' => 'pending']);
                foreach (['consumer' => 'pending', 'wholesale' => 'approved', 'retail' => 'rejected'] as $role => $status) {
                    FarmerRating::firstOrCreate(['farmer_id' => $farmer->id, 'reviewer_id' => $roles[$role]->id], ['score' => $role === 'retail' ? 2 : 4, 'comment' => 'Illustrative demo '.$status.' rating. Synthetic showcase feedback, not a real trading experience.', 'reviewer_group' => $role === 'consumer' ? 'public' : 'middlemen', 'status' => $status, 'moderated_by_user_id' => $status === 'pending' ? null : $roles['admin']->id, 'moderation_reason' => $status === 'pending' ? null : 'Illustrative seeded rating moderation outcome.']);
                }
                AuditLoggerService::log(action: 'SHOWCASE_SCENARIOS_SEEDED', targetType: 'Showcase', notes: 'Synthetic local/test examples; accepted handoffs, review states and uploaded images are demonstration fixtures. Existing records were preserved.');
            });
        } catch (\Throwable $e) {
            foreach ($ownedFiles as $path) {
                Storage::disk('local')->delete($path);
            } throw $e;
        }
    }

    private function agreement(string $code, string $crop, int $quantity, int $price): array
    {
        $image = imagecreatetruecolor(1000, 550);
        $white = imagecolorallocate($image, 255, 255, 255);
        $ink = imagecolorallocate($image, 20, 55, 40);
        imagefill($image, 0, 0, $white);
        foreach (['ILLUSTRATIVE DEMO - NOT A SIGNED CONTRACT', $code, 'Crop: '.preg_replace('/\s*\(.*/', '', $crop), 'Farmer: Rahim (synthetic demo) | Collector: Karim (synthetic demo)', 'Quantity: '.$quantity.' kg | Price: '.$price.' BDT/kg', 'Recorded total: '.($quantity * $price).' BDT', 'Witness: Synthetic showcase witness', 'Generated fixture only; no real signature, consent or payment.'] as $index => $line) {
            imagestring($image, 5, 30, 30 + $index * 55, $line, $ink);
        }
        ob_start();
        imagejpeg($image, null, 90);
        $bytes = ob_get_clean();
        imagedestroy($image);
        $path = 'evidence/showcase_'.Str::random(32).'.jpg';
        if (! Storage::disk('local')->put($path, $bytes)) {
            throw new \RuntimeException('Demo agreement storage failed.');
        }

        return ['evidence_path' => $path, 'evidence_mime' => 'image/jpeg', 'evidence_original_sha256' => hash('sha256', $bytes), 'evidence_transformation' => 'Generated synthetic JPEG fixture for local showcase; no real handwritten agreement or signature.'];
    }
}
