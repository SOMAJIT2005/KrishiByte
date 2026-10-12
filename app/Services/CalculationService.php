<?php

namespace App\Services;

class CalculationService
{
    /**
     * Gross price spread: Sell Price - Buy Price
     */
    public static function calculateSpread(float $buyPrice, float $sellPrice): float
    {
        return round($sellPrice - $buyPrice, 2);
    }

    /**
     * Markup percent on cost: ((S - B) / B) * 100, when B > 0
     */
    public static function calculateMarkupPercent(float $buyPrice, float $sellPrice): ?float
    {
        if ($buyPrice <= 0) {
            return null;
        }

        return round((($sellPrice - $buyPrice) / $buyPrice) * 100, 2);
    }

    /**
     * Gross margin percent on sale price: ((S - B) / S) * 100, when S > 0
     */
    public static function calculateMarginPercent(float $buyPrice, float $sellPrice): ?float
    {
        if ($sellPrice <= 0) {
            return null;
        }

        return round((($sellPrice - $buyPrice) / $sellPrice) * 100, 2);
    }

    /**
     * Calculate a robust indicative reference price using trimmed mean or median.
     *
     * @param array<float> $prices
     * @return array{indicative_price: ?float, sample_count: int, confidence: string, method: string}
     */
    public static function calculateSequentialMultiMiddlemanPricing(array $prices, int $minMiddlemanPathLength = 2): array
    {
        $count = count($prices);

        // Require at least 2 prices (farmer and at least 1 middleman stage)
        if ($count < 2) {
            return [
                'indicative_price' => null,
                'sample_count' => $count,
                'confidence' => 'none',
                'method' => 'none',
                'path_length' => 0,
                'per_middleman_margin' => null,
                'total_markup_chain' => null,
            ];
        }

        // Step prices: from farmer to first middleman, compute per-stage increment
        $priceChain = $prices; // array of prices in sequence, farmer first
        $priceChainCount = count($priceChain);

        if ($priceChainCount < 2) {
            return [
                'indicative_price' => null,
                'sample_count' => $priceChainCount,
                'confidence' => 'none',
                'method' => 'none',
                'path_length' => 0,
                'per_middleman_margin' => null,
                'total_markup_chain' => null,
            ];
        }

        // Compute per-middleman markup chain
        $markupSteps = [];
        $totalMarkup = 0.0;
        $prevPrice = null;

        foreach ($priceChain as $stepIndex => $currentPrice) {
            if ($prevPrice === null) {
                $prevPrice = $currentPrice;
                continue;
            }

            $markup = CalculationService::calculateMarkupPercent($prevPrice, $currentPrice);
            $markupSteps[] = [
                'step' => $stepIndex,
                'prev_price' => $prevPrice,
                'current_price' => $currentPrice,
                'markup_percent' => $markup,
            ];
            $totalMarkup += $markup;
            $prevPrice = $currentPrice;
        }

        $perMiddlemanMargin = null;
        if (count($markupSteps) > 0) {
            $avgMarkup = array_sum(array_column($markupSteps, 'markup_percent')) / count($markupSteps);
            $perMiddlemanMargin = round($avgMarkup, 2);
        }            return [
                'indicative_price' => round(end($priceChain), 2),
                'sample_count' => $priceChainCount,
                'confidence' => $priceChainCount >= $minMiddlemanPathLength + 1 ? 'high' : 'low',
                'method' => 'sequential_middleman_pricing',
                'path_length' => $priceChainCount - 1,
                'per_middleman_margin' => $perMiddlemanMargin,
                'total_markup_chain' => round($totalMarkup, 2),
                'markup_steps' => $markupSteps,
            ];
    }

    /**
     * Calculate a robust indicative reference price using trimmed mean or median.
     *
     * @param array<float> $prices
     * @return array{indicative_price: ?float, sample_count: int, confidence: string, method: string}
     */

    /**
     * Calculate a robust indicative reference price using trimmed mean or median.
     *
     * @param array<float> $prices
     * @return array{indicative_price: ?float, sample_count: int, confidence: string, method: string}
     */

    public static function calculateSequentialMultiMiddlemanPricing(array $prices, int $minMiddlemanPathLength = 2): array
    {
        $count = count($prices);

        // Require at least 2 prices (farmer and at least 1 middleman stage)
        if ($count < 2) {
            return [
                'indicative_price' => null,
                'sample_count' => $count,
                'confidence' => 'none',
                'method' => 'none',
                'path_length' => 0,
                'per_middleman_margin' => null,
                'total_markup_chain' => null,
            ];
        }

        // Step prices: from farmer to first middleman, compute per-stage increment
        $priceChain = $prices; // array of prices in sequence, farmer first
        $priceChainCount = count($priceChain);

        if ($priceChainCount < 2) {
            return [
                'indicative_price' => null,
                'sample_count' => $priceChainCount,
                'confidence' => 'none',
                'method' => 'none',
                'path_length' => 0,
                'per_middleman_margin' => null,
                'total_markup_chain' => null,
            ];
        }

        // Compute per-middleman markup chain
        $markupSteps = [];
        $totalMarkup = 0.0;
        $prevPrice = null;

        foreach ($priceChain as $stepIndex => $currentPrice) {
            if ($prevPrice === null) {
                $prevPrice = $currentPrice;
                continue;
            }

            $markup = CalculationService::calculateMarkupPercent($prevPrice, $currentPrice);
            $markupSteps[] = [
                'step' => $stepIndex,
                'prev_price' => $prevPrice,
                'current_price' => $currentPrice,
                'markup_percent' => $markup,
            ];
            $totalMarkup += $markup;
            $prevPrice = $currentPrice;
        }

        $perMiddlemanMargin = null;
        if (count($markupSteps) > 0) {
            $avgMarkup = array_sum(array_column($markupSteps, 'markup_percent')) / count($markupSteps);
            $perMiddlemanMargin = round($avgMarkup, 2);
        }

        return [
            'indicative_price' => round(end($priceChain), 2),
            'sample_count' => $priceChainCount,
            'confidence' => $priceChainCount >= $minMiddlemanPathLength + 1 ? 'high' : 'low',
            'method' => 'sequential_middleman_pricing',
            'path_length' => $priceChainCount - 1,
            'per_middleman_margin' => $perMiddlemanMargin,
            'total_markup_chain' => round($totalMarkup, 2),
            'markup_steps' => $markupSteps,
        ];
    }


    public static function calculateSequentialMultiMiddlemanPricing(array $prices, int $minMiddlemanPathLength = 2): array
    {
        $count = count($prices);

        // Require at least 2 prices (farmer and at least 1 middleman stage)
        if ($count < 2) {
            return [
                'indicative_price' => null,
                'sample_count' => $count,
                'confidence' => 'none',
                'method' => 'none',
                'path_length' => 0,
                'per_middleman_margin' => null,
                'total_markup_chain' => null,
            ];
        }

        // Step prices: from farmer to first middleman, compute per-stage increment
        $priceChain = $prices; // array of prices in sequence, farmer first
        $priceChainCount = count($priceChain);

        if ($priceChainCount < 2) {
            return [
                'indicative_price' => null,
                'sample_count' => $priceChainCount,
                'confidence' => 'none',
                'method' => 'none',
                'path_length' => 0,
                'per_middleman_margin' => null,
                'total_markup_chain' => null,
            ];
        }

        // Compute per-middleman markup chain
        $markupSteps = [];
        $totalMarkup = 0.0;
        $prevPrice = null;

        foreach ($priceChain as $stepIndex => $currentPrice) {
            if ($prevPrice === null) {
                $prevPrice = $currentPrice;
                continue;
            }

            $markup = CalculationService::calculateMarkupPercent($prevPrice, $currentPrice);
            $markupSteps[] = [
                'step' => $stepIndex,
                'prev_price' => $prevPrice,
                'current_price' => $currentPrice,
                'markup_percent' => $markup,
            ];
            $totalMarkup += $markup;
            $prevPrice = $currentPrice;
        }

        $perMiddlemanMargin = null;
        if (count($markupSteps) > 0) {
            $avgMarkup = array_sum(array_column($markupSteps, 'markup_percent')) / count($markupSteps);
            $perMiddlemanMargin = round($avgMarkup, 2);
        }            return [
                'indicative_price' => round(end($priceChain), 2),
                'sample_count' => $priceChainCount,
                'confidence' => $priceChainCount >= $minMiddlemanPathLength + 1 ? 'high' : 'low',
                'method' => 'sequential_middleman_pricing',
                'path_length' => $priceChainCount - 1,
                'per_middleman_margin' => $perMiddlemanMargin,
                'total_markup_chain' => round($totalMarkup, 2),
                'markup_steps' => $markupSteps,
            ];
    }

    /**
     * Calculate a robust indicative reference price using trimmed mean or median.
     *
     * @param array<float> $prices
     * @return array{indicative_price: ?float, sample_count: int, confidence: string, method: string}
     */

    /**
     * Calculate a robust indicative reference price using trimmed mean or median.
     *
     * @param array<float> $prices
     * @return array{indicative_price: ?float, sample_count: int, confidence: string, method: string}
     */

    public static function calculateSequentialMultiMiddlemanPricing(array $prices, int $minMiddlemanPathLength = 2): array
    {
        $count = count($prices);

        // Require at least 2 prices (farmer and at least 1 middleman stage)
        if ($count < 2) {
            return [
                'indicative_price' => null,
                'sample_count' => $count,
                'confidence' => 'none',
                'method' => 'none',
                'path_length' => 0,
                'per_middleman_margin' => null,
                'total_markup_chain' => null,
            ];
        }

        // Step prices: from farmer to first middleman, compute per-stage increment
        $priceChain = $prices; // array of prices in sequence, farmer first
        $priceChainCount = count($priceChain);

        if ($priceChainCount < 2) {
            return [
                'indicative_price' => null,
                'sample_count' => $priceChainCount,
                'confidence' => 'none',
                'method' => 'none',
                'path_length' => 0,
                'per_middleman_margin' => null,
                'total_markup_chain' => null,
            ];
        }

        // Compute per-middleman markup chain
        $markupSteps = [];
        $totalMarkup = 0.0;
        $prevPrice = null;

        foreach ($priceChain as $stepIndex => $currentPrice) {
            if ($prevPrice === null) {
                $prevPrice = $currentPrice;
                continue;
            }

            $markup = CalculationService::calculateMarkupPercent($prevPrice, $currentPrice);
            $markupSteps[] = [
                'step' => $stepIndex,
                'prev_price' => $prevPrice,
                'current_price' => $currentPrice,
                'markup_percent' => $markup,
            ];
            $totalMarkup += $markup;
            $prevPrice = $currentPrice;
        }

        $perMiddlemanMargin = null;
        if (count($markupSteps) > 0) {
            $avgMarkup = array_sum(array_column($markupSteps, 'markup_percent')) / count($markupSteps);
            $perMiddlemanMargin = round($avgMarkup, 2);
        }

        return [
            'indicative_price' => round(end($priceChain), 2),
            'sample_count' => $priceChainCount,
            'confidence' => $priceChainCount >= $minMiddlemanPathLength + 1 ? 'high' : 'low',
            'method' => 'sequential_middleman_pricing',
            'path_length' => $priceChainCount - 1,
            'per_middleman_margin' => $perMiddlemanMargin,
            'total_markup_chain' => round($totalMarkup, 2),
            'markup_steps' => $markupSteps,
        ];
    }


    public static function calculateReferencePrice(array $prices): array
    {
        $count = count($prices);

        if ($count === 0) {
            return [
                'indicative_price' => null,
                'sample_count' => 0,
                'confidence' => 'none',
                'method' => 'none',
            ];
        }



        sort($prices, SORT_NUMERIC);

        // 20% trimmed mean if sample count >= 3
        if ($count >= 3) {
        if ($count >= 3) {
            $trimCount = (int) floor($count * 0.2);
            $trimmed = array_slice($prices, $trimCount, $count - (2 * $trimCount));
            $trimmedAvg = round(array_sum($trimmed) / count($trimmed), 2);

            return [
                'indicative_price' => $trimmedAvg,
                'sample_count' => $count,
                'confidence' => 'high',
                'method' => '20%_trimmed_mean',
            ];
        }

        // Fallback for fewer than 3 samples
        if ($count === 0) {
            return [
                'indicative_price' => null,
                'sample_count' => 0,
                'confidence' => 'none',
                'method' => 'none',
            ];
        }

        $avg = round(array_sum($prices) / $count, 2);
        return [
            'indicative_price' => $avg,
            'sample_count' => $count,
            'confidence' => 'low',
            'method' => 'untrimmed_mean',
        ];
    }

    }
}
