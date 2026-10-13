<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\FarmPurchase;
use App\Models\HandoffProposal;
use App\Models\PriceObservation;
use App\Models\PriceReport;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PriceReferenceService;
use Database\Seeders\ShowcaseScenarioSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ShowcaseDataTest extends TestCase
{
    use DatabaseTransactions;

    public function test_showcase_population_command_is_disabled_in_production(): void
    {
        $this->app['env'] = 'production';
        $this->artisan('showcase:populate')->expectsOutput('Showcase data is only allowed in local/test environments.')->assertExitCode(1);
    }

    public function test_demo_scenarios_are_complete_idempotent_and_preserve_actions(): void
    {
        Storage::fake('local');
        $this->seed(ShowcaseScenarioSeeder::class);
        $lots = Batch::where('batch_code', 'like', 'KB-DEMO-%')->get();
        $this->assertSame(10, $lots->count());
        $this->assertSame(18, Transaction::whereIn('batch_id', $lots->pluck('id'))->count());
        $this->assertSame(8, FarmPurchase::whereIn('batch_id', $lots->pluck('id'))->count());
        $this->assertSame(10, HandoffProposal::whereIn('batch_id', $lots->pluck('id'))->count());
        $this->assertEquals(450, $lots->firstWhere('batch_code', 'KB-DEMO-RETAIL')->available_quantity);
        $this->assertSame('completed', $lots->firstWhere('batch_code', 'KB-DEMO-SOLD-OUT')->status);
        $this->assertSame('sold_out', $lots->firstWhere('batch_code', 'KB-DEMO-SOLD-OUT')->current_stage);
        $this->assertEquals(0, $lots->firstWhere('batch_code', 'KB-DEMO-SOLD-OUT')->available_quantity);
        $this->assertGreaterThanOrEqual(2, $lots->firstWhere('batch_code', 'KB-DEMO-HIGH-MARKUP')->anomalyFlags()->count());
        $this->assertSame(12, PriceReport::where('tracking_code', 'like', 'RPT-DEMO-%')->where('is_demo', true)->count());
        $this->assertCount(8, Storage::disk('local')->allFiles());
        $lots->firstWhere('batch_code', 'KB-DEMO-FARM-READY')->update(['notes' => 'QA user changed this demo lot.']);
        $this->seed(ShowcaseScenarioSeeder::class);
        $this->assertSame(10, Batch::where('batch_code', 'like', 'KB-DEMO-%')->count());
        $this->assertSame(18, Transaction::whereIn('batch_id', $lots->pluck('id'))->count());
        $this->assertSame('QA user changed this demo lot.', $lots->firstWhere('batch_code', 'KB-DEMO-FARM-READY')->fresh()->notes);
        $this->assertCount(8, Storage::disk('local')->allFiles());
    }

    public function test_moderating_a_demo_report_never_promotes_it_to_real_market_data(): void
    {
        Storage::fake('local');
        $this->seed(ShowcaseScenarioSeeder::class);
        $report = PriceReport::where('tracking_code', 'RPT-DEMO-PRICE-PENDING')->firstOrFail();
        $authority = User::where('email', 'authority@krishibyte.local')->firstOrFail();
        $this->actingAs($authority)->post(route('authority.reports.moderate', $report->id), ['status' => 'verified', 'moderation_notes' => 'QA illustrative sample moderation.'])->assertRedirect();
        $observation = PriceObservation::where('price_report_id', $report->id)->firstOrFail();
        $this->assertTrue($observation->is_demo);
        $this->assertFalse(PriceReferenceService::baseQuery($report->district_id, 'reports')->where('price_report_id', $report->id)->exists());
        $this->assertTrue(PriceReferenceService::baseQuery($report->district_id, 'demo')->where('price_report_id', $report->id)->exists());
    }
}
