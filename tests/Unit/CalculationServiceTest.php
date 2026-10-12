<?php

namespace Tests\Unit;

use App\Services\CalculationService;
use PHPUnit\Framework\TestCase;

class CalculationServiceTest extends TestCase
{
    public function test_gross_price_spread_calculation(): void
    {
        $spread = CalculationService::calculateSpread(20.00, 26.50);
        $this->assertEquals(6.50, $spread);

        $zeroSpread = CalculationService::calculateSpread(20.00, 20.00);
        $this->assertEquals(0.00, $zeroSpread);
    }

    public function test_markup_percent_on_cost(): void
    {
        // Buy = 50, Sell = 65 -> Spread = 15 -> Markup = 30%
        $markup = CalculationService::calculateMarkupPercent(50.00, 65.00);
        $this->assertEquals(30.00, $markup);

        // Buy <= 0 returns null
        $this->assertNull(CalculationService::calculateMarkupPercent(0.00, 50.00));
        $this->assertNull(CalculationService::calculateMarkupPercent(-10.00, 50.00));
    }

    public function test_gross_margin_percent_on_sale_price(): void
    {
        // Buy = 50, Sell = 100 -> Spread = 50 -> Margin = 50%
        $margin = CalculationService::calculateMarginPercent(50.00, 100.00);
        $this->assertEquals(50.00, $margin);

        // Sell <= 0 returns null
        $this->assertNull(CalculationService::calculateMarginPercent(50.00, 0.00));
    }

    public function test_reference_price_handles_insufficient_samples(): void
    {
        // 0 samples
        $res0 = CalculationService::calculateReferencePrice([]);
        $this->assertNull($res0['indicative_price']);
        $this->assertEquals(0, $res0['sample_count']);
        $this->assertEquals('none', $res0['confidence']);

        // 2 samples: low confidence / insufficient sample warning
        $res2 = CalculationService::calculateReferencePrice([30.00, 40.00]);
        $this->assertEquals(35.00, $res2['indicative_price']);
        $this->assertEquals(2, $res2['sample_count']);
        $this->assertEquals('low', $res2['confidence']);
    }

    public function test_reference_price_median_for_medium_samples(): void
    {
        // 3 samples: median = 35
        $res3 = CalculationService::calculateReferencePrice([30.00, 50.00, 35.00]);
        $this->assertEquals(35.00, $res3['indicative_price']);
        $this->assertEquals(3, $res3['sample_count']);
        $this->assertEquals('medium', $res3['confidence']);
    }

    public function test_reference_price_trimmed_mean_for_high_sample_count(): void
    {
        // 5 samples: [20, 30, 35, 40, 100] -> outlier 100 trimmed, outlier 20 trimmed -> mean of [30, 35, 40] = 35.00
        $res5 = CalculationService::calculateReferencePrice([20.00, 30.00, 35.00, 40.00, 100.00]);
        $this->assertEquals(35.00, $res5['indicative_price']);
        $this->assertEquals(5, $res5['sample_count']);
        $this->assertEquals('high', $res5['confidence']);
        $this->assertEquals('20%_trimmed_mean', $res5['method']);
    }
}
