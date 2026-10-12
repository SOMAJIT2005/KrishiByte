<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Crop;
use App\Models\District;
use App\Models\PriceReport;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicAndReportTest extends TestCase
{
    use DatabaseTransactions;

    protected Crop $crop;

    protected District $district;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crop = Crop::first();
        $this->district = District::first();
    }

    /** AT-03: Guest opens public price explorer, home, and traceability */
    public function test_guest_can_access_all_public_views(): void
    {
        $this->get(route('public.home'))->assertStatus(200);
        $this->get(route('public.prices'))->assertStatus(200);
        $this->get(route('public.traceability'))->assertStatus(200);

        $batch = Batch::first();
        if ($batch) {
            $response = $this->get(route('public.traceability.show', $batch->batch_code));
            $response->assertStatus(200);
            // Verify public view does not leak private user phone numbers or emails
            $response->assertDontSee($batch->farmer->email);
        }

        $this->get(route('public.report'))->assertStatus(200);
        $this->get(route('public.faq'))->assertStatus(200);
    }

    /** AT-04: Guest opening protected dashboards gets redirected */
    public function test_guest_opening_private_dashboards_redirected_to_login(): void
    {
        $this->get(route('farmer.dashboard'))->assertRedirect(route('login'));
        $this->get(route('vendor.dashboard'))->assertRedirect(route('login'));
        $this->get(route('authority.dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    /** AT-14: Consumer submits valid price report */
    public function test_consumer_submits_valid_price_report(): void
    {
        Storage::fake('local');

        $photo = UploadedFile::fake()->image('price_tag.jpg', 600, 400);

        $response = $this->post(route('public.report.store'), [
            'crop_id' => $this->crop->id,
            'district_id' => $this->district->id,
            'market_name' => 'Mohakhali Kitchen Market',
            'reported_price' => 120.00,
            'unit' => 'kg',
            'incident_date' => now()->toDateString(),
            'report_narrative' => 'Observed price hike on local produce without market notice.',
            'reporter_name' => 'Test Citizen',
            'reporter_phone' => '+8801711122233',
            'evidence_photo' => $photo,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('price_reports', [
            'crop_id' => $this->crop->id,
            'district_id' => $this->district->id,
            'reported_price' => 120.00,
            'status' => 'pending', // Must be pending moderation
        ]);
    }

    /** AT-15: Unsafe or oversized files are rejected */
    public function test_unsafe_or_oversized_file_upload_is_rejected(): void
    {
        Storage::fake('local');

        // Disallowed executable script disguised as php
        $fakeScript = UploadedFile::fake()->create('malicious.php', 100, 'application/x-php');

        $response = $this->post(route('public.report.store'), [
            'crop_id' => $this->crop->id,
            'district_id' => $this->district->id,
            'market_name' => 'Market',
            'reported_price' => 50,
            'unit' => 'kg',
            'incident_date' => now()->toDateString(),
            'report_narrative' => 'Testing file validation.',
            'evidence_photo' => $fakeScript,
        ]);

        $response->assertSessionHasErrors('evidence_photo');
    }

    /** AT-17: Track report shows current review status */
    public function test_track_report_status_shows_accurate_outcome(): void
    {
        $report = PriceReport::create([
            'tracking_code' => PriceReport::generateTrackingCode(),
            'crop_id' => $this->crop->id,
            'district_id' => $this->district->id,
            'market_name' => 'Karwan Bazar',
            'reported_price' => 95.00,
            'unit' => 'kg',
            'report_narrative' => 'Testing tracking status.',
            'incident_date' => now()->toDateString(),
            'status' => 'pending',
        ]);

        $response = $this->get(route('public.report.track', ['code' => $report->tracking_code]));
        $response->assertStatus(200);
        $response->assertSee($report->tracking_code);
        $response->assertSee('Pending');
    }
}
