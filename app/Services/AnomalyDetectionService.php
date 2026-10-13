<?php

namespace App\Services;

use App\Models\AnomalyFlag;
use App\Models\MarkupRule;
use App\Models\Notification;
use App\Models\Transaction;
use App\Models\User;

class AnomalyDetectionService
{
    public static function evaluateTransaction(Transaction $transaction): ?AnomalyFlag
    {
        $batch = $transaction->batch;
        $day = $transaction->transaction_date->timezone('Asia/Dhaka')->toDateString();
        $rule = MarkupRule::where('is_active', true)
            ->where(fn ($q) => $q->where('crop_id', $batch->crop_id)->orWhereNull('crop_id'))
            ->where(fn ($q) => $q->where('stage', $transaction->stage)->orWhere('stage', 'all'))
            ->where(fn ($q) => $q->whereNull('effective_from')->orWhereDate('effective_from', '<=', $day))
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $day))
            ->orderByRaw('crop_id IS NOT NULL DESC')->orderByRaw("stage != 'all' DESC")->latest('id')->first();
        if (! $rule) {
            return null;
        }
        $first = null;
        foreach ([['EXCESSIVE_MARKUP', $transaction->markup_percent, $rule->max_markup_percent, '%'], ['EXCESSIVE_SPREAD', $transaction->gross_price_spread, $rule->max_spread_amount, ' BDT/'.$transaction->unit]] as [$reason,$measured,$threshold,$unit]) {
            if ($measured === null || $threshold === null || (float) $measured <= (float) $threshold) {
                continue;
            }
            $excessRatio = (float) $threshold > 0 ? ((float) $measured - (float) $threshold) / (float) $threshold : INF;
            $severity = $excessRatio >= 1 ? 'high' : ($excessRatio >= .25 ? 'medium' : 'low');
            $snapshot = ['explanation' => $rule->explanation, 'max_markup_percent' => $rule->max_markup_percent, 'max_spread_amount' => $rule->max_spread_amount, 'effective_from' => $rule->effective_from?->toDateString(), 'effective_to' => $rule->effective_to?->toDateString(), 'unit' => $transaction->unit, 'severity_method' => 'relative excess: >=100% high, >=25% medium, otherwise low; positive breach of zero cap high'];
            $flag = AnomalyFlag::create(['batch_id' => $batch->id, 'transaction_id' => $transaction->id, 'user_id' => $transaction->seller_id, 'rule_id' => $rule->id, 'reason_code' => $reason, 'measured_value' => $measured, 'threshold_value' => $threshold, 'severity' => $severity, 'status' => 'new', 'rule_snapshot' => $snapshot]);
            $first ??= $flag;
            $transaction->update(['validation_status' => 'flagged']);
            $message = ($reason === 'EXCESSIVE_SPREAD' ? 'Gross price spread' : 'Markup')." {$measured}{$unit} exceeded {$threshold}{$unit}; {$severity} review signal, not a misconduct finding.";
            foreach (User::whereIn('role', ['authority', 'admin'])->where('account_status', 'active')->get() as $reviewer) {
                Notification::create(['user_id' => $reviewer->id, 'title' => "Price review: {$batch->batch_code}", 'message' => $message, 'link' => '/authority/dashboard']);
            }
            Notification::create(['user_id' => $transaction->seller_id, 'title' => 'Transaction review required', 'message' => $message, 'link' => $transaction->seller->isFarmer() ? '/farmer/handoff' : '/vendor/dashboard']);
        }

        return $first;
    }
}
