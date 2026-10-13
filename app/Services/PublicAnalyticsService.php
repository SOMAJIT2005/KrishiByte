<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class PublicAnalyticsService
{
    public static function products(Collection $crops, ?int $district, string $source, string $endDate, int $days): Collection
    {
        $end = CarbonImmutable::parse($endDate, 'Asia/Dhaka');
        $start = $end->subDays($days - 1);
        $observations = PriceReferenceService::baseQuery($district, $source)
            ->whereIn('crop_id', $crops->pluck('id'))->whereBetween('observation_date', [$start->toDateString(), $end->toDateString()])->get()->groupBy('crop_id');

        return $crops->map(function ($crop) use ($observations, $start, $days) {
            $rows = $observations->get($crop->id, collect())->where('unit', $crop->unit)->groupBy(fn ($row) => $row->observation_date->toDateString());
            $daily = collect();
            for ($i = 0; $i < $days; $i++) {
                $date = $start->addDays($i)->toDateString();
                $daily->push(['date' => $date] + PriceReferenceService::summarize($rows->get($date, collect())));
            }
            $valid = $daily->whereNotNull('indicative_price')->values();
            $latest = $valid->last();
            $previous = $valid->count() > 1 ? $valid[$valid->count() - 2] : null;
            $change = $previous && $previous['indicative_price'] > 0 ? round(($latest['indicative_price'] - $previous['indicative_price']) / $previous['indicative_price'] * 100, 2) : null;

            return ['crop' => $crop, 'daily' => $daily, 'latest' => $latest, 'previous' => $previous, 'change' => $change,
                'lowest' => $valid->sortBy('indicative_price')->first(), 'highest' => $valid->sortByDesc('indicative_price')->first(),
                'trend' => $change === null ? 'Insufficient history' : ($change > 0 ? 'Rising' : ($change < 0 ? 'Falling' : 'Stable'))];
        });
    }
}
