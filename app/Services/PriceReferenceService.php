<?php

namespace App\Services;

use App\Models\PriceObservation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PriceReferenceService
{
    public static function query(int $cropId, string $unit, ?int $districtId, string $date, string $source = 'survey'): Builder
    {
        return self::baseQuery($districtId, $source)->where('crop_id', $cropId)->where('unit', $unit)->whereDate('observation_date', $date);
    }

    public static function baseQuery(?int $districtId, string $source): Builder
    {
        $query = PriceObservation::where('unit_needs_review', false)->where('verification_status', 'verified');
        if ($districtId) {
            $query->where('district_id', $districtId);
        }

        return match ($source) {
            'demo' => $query->where('is_demo', true),
            'reports' => $query->where('is_demo', false)->where('source', 'verified_citizen_report'),
            default => $query->where('is_demo', false)->where('source', 'verified_market_survey'),
        };
    }

    public static function stats(Builder $query): array
    {
        return self::summarize((clone $query)->get());
    }

    public static function summarize(Collection $rows): array
    {
        $rows = $rows->unique(fn ($row) => json_encode([$row->district_id, $row->market_name, $row->source, $row->entered_by_user_id, $row->observation_date->toDateString(), $row->observed_retail_price]));

        return CalculationService::calculateReferencePrice($rows->pluck('observed_retail_price')->map(fn ($p) => (float) $p)->all()) + ['market_count' => $rows->filter(fn ($row) => filled($row->market_name))->map(fn ($row) => json_encode([$row->district_id, $row->market_name]))->unique()->count(), 'source_count' => $rows->pluck('entered_by_user_id')->filter()->unique()->count(), 'last_observed_date' => $rows->max('observation_date')?->format('Y-m-d')];
    }
}
