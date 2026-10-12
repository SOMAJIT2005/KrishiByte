<?php

namespace App\Services;

class CalculationService
{
    /**
     * Gross price spread: Sell Price - Buy Price
     */
    public static function calculateSpread(float $buyPrice, float $sellPrice): float
    {
        return (float) bcsub(number_format($sellPrice, 2, '.', ''), number_format($buyPrice, 2, '.', ''), 2);
    }

    /**
     * Markup percent on cost: ((S - B) / B) * 100, when B > 0
     */
    public static function calculateMarkupPercent(float $buyPrice, float $sellPrice): ?float
    {
        if ($buyPrice <= 0) {
            return null;
        }

        return round((float) bcmul(bcdiv((string) self::calculateSpread($buyPrice, $sellPrice), (string) $buyPrice, 8), '100', 6), 2);
    }

    /**
     * Gross margin percent on sale price: ((S - B) / S) * 100, when S > 0
     */
    public static function calculateMarginPercent(float $buyPrice, float $sellPrice): ?float
    {
        if ($sellPrice <= 0) {
            return null;
        }

        return round((float) bcmul(bcdiv((string) self::calculateSpread($buyPrice, $sellPrice), (string) $sellPrice, 8), '100', 6), 2);
    }

    public static function calculateReferencePrice(array $prices): array
    {
        $count = count($prices);
        if ($count < 3) {
            return ['indicative_price' => null, 'sample_count' => $count, 'confidence' => 'none', 'method' => 'none'];
        }
        sort($prices, SORT_NUMERIC);
        if ($count < 5) {
            $middle = intdiv($count, 2);
            $value = $count % 2 ? $prices[$middle] : ($prices[$middle - 1] + $prices[$middle]) / 2;

            return ['indicative_price' => round($value, 2), 'sample_count' => $count, 'confidence' => 'medium', 'method' => 'median'];
        }
        $trim = (int) floor($count * .2);
        $values = array_slice($prices, $trim, $count - 2 * $trim);

        return ['indicative_price' => round(array_sum($values) / count($values), 2), 'sample_count' => $count, 'confidence' => 'high', 'method' => '20%_trimmed_mean'];
    }
}
