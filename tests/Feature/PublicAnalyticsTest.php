<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\District;
use App\Models\PriceObservation;
use App\Models\User;
use App\Services\PublicAnalyticsService;
use Database\Seeders\DemoPriceHistorySeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PublicAnalyticsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_local_analytics_defaults_to_demo_while_explicit_sources_and_production_keep_surveys(): void
    {
        $this->app->instance('env', 'local');
        try {
            $this->get(route('public.analytics'))->assertOk()->assertViewHas('source', 'demo')->assertSee('Illustrative demo data');
            $this->get(route('public.analytics', ['source' => 'survey']))->assertOk()->assertViewHas('source', 'survey');
            $this->app->instance('env', 'production');
            $this->get(route('public.analytics'))->assertOk()->assertViewHas('source', 'survey');
        } finally {
            $this->app->instance('env', 'testing');
        }
    }

    public function test_demo_history_seeding_is_idempotent_and_never_enters_real_survey_statistics(): void
    {
        $this->seed(DemoPriceHistorySeeder::class);
        $count = PriceObservation::where('market_name', 'like', 'Illustrative % analytics sample %')->count();
        $this->assertSame(1470, $count);
        $this->seed(DemoPriceHistorySeeder::class);
        $this->assertSame($count, PriceObservation::where('market_name', 'like', 'Illustrative % analytics sample %')->count());
        $this->assertSame(0, PriceObservation::where('market_name', 'like', 'Illustrative % analytics sample %')->where('is_demo', false)->count());
        $products = PublicAnalyticsService::products(Crop::where('is_active', true)->get(), District::where('name', 'Chattogram')->value('id'), 'demo', now('Asia/Dhaka')->toDateString(), 14);
        $this->assertSame(2, $products->where('trend', 'Rising')->count());
        $this->assertSame(4, $products->where('trend', 'Falling')->count());
        $this->assertSame(1, $products->where('trend', 'Stable')->count());
        $this->assertTrue($products->every(fn ($product) => $product['latest']['sample_count'] === 5));
        $potato = $products->first(fn ($product) => str_starts_with($product['crop']->name, 'Potato'));
        $this->assertEquals(28, $potato['lowest']['indicative_price']);
        $this->assertEquals(50, $potato['highest']['indicative_price']);
    }

    public function test_comparable_daily_prices_show_rises_falls_gaps_and_separate_demo_sources(): void
    {
        PriceObservation::query()->delete();
        $crops = Crop::where('is_active', true)->take(2)->get();
        $district = District::first();
        $today = now('Asia/Dhaka')->toDateString();
        $yesterday = now('Asia/Dhaka')->subDay()->toDateString();
        foreach ($crops as $index => $crop) {
            foreach ([$yesterday => 30, $today => ($index === 0 ? 33 : 27)] as $date => $price) {
                foreach ([-1, 0, 1] as $offset) {
                    PriceObservation::create(['crop_id' => $crop->id, 'district_id' => $district->id, 'market_name' => 'Analytics test', 'unit' => $crop->unit, 'observation_date' => $date, 'source' => 'verified_market_survey', 'verification_status' => 'verified', 'entered_by_user_id' => User::first()->id, 'observed_retail_price' => $price + $offset]);
                }
            }
            PriceObservation::create(['crop_id' => $crop->id, 'district_id' => $district->id, 'market_name' => 'Excluded demo', 'unit' => $crop->unit, 'observation_date' => $today, 'source' => 'verified_market_survey', 'verification_status' => 'verified', 'is_demo' => true, 'observed_retail_price' => 900]);
        }
        $products = PublicAnalyticsService::products($crops, $district->id, 'survey', $today, 7);
        $this->assertEquals(10, $products[0]['change']);
        $this->assertEquals(-10, $products[1]['change']);
        $this->assertNull($products[0]['daily'][0]['indicative_price']);
        $this->assertSame(3, $products[0]['latest']['sample_count']);
        $this->get(route('public.analytics', ['district_id' => $district->id]))->assertOk()->assertSee('Rising')->assertSee('Falling')->assertSee('+10.00%')->assertSee('-10.00%')->assertDontSee('900.00');
        $this->get(route('public.analytics', ['source' => 'demo']))->assertOk()->assertSee('Illustrative demo data')->assertSee('Insufficient history');
        $this->get(route('public.analytics', ['days' => 365]))->assertRedirect();
        $this->get(route('public.analytics', ['end_date' => now()->addDays(2)->toDateString()]))->assertRedirect();
        $this->get(route('public.home'))->assertOk()->assertSee('Explore price analytics');
    }
}
