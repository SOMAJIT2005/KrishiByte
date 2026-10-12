<?php

namespace App\Services;

use App\Models\AnomalyFlag;
use App\Models\MarkupRule;
use App\Models\Notification;
use App\Models\Transaction;
use App\Models\User;

class AnomalyDetectionService
{
    /**
     * Evaluate transaction markup against active rules and generate flags if breached.
     */
    public static function evaluateTransaction(Transaction $transaction): ?AnomalyFlag
    {
        if ($transaction->markup_percent === null || $transaction->markup_percent <= 0) {
            return null;
        }

        $batch = $transaction->batch;
        $cropId = $batch->crop_id;
        $stage = $transaction->stage;

        // Find most specific active rule (crop + stage, then crop + all, then all + stage, then default all + all)
        $rule = MarkupRule::where('is_active', true)
            ->where(function ($q) use ($cropId) {
                $q->where('crop_id', $cropId)->orWhereNull('crop_id');
            })
            ->where(function ($q) use ($stage) {
                $q->where('stage', $stage)->orWhere('stage', 'all');
            })
            ->orderByRaw('crop_id IS NOT NULL DESC')
            ->orderByRaw("stage != 'all' DESC")
            ->first();

        if (!$rule) {
            return null;
        }

        $threshold = (float) $rule->max_markup_percent;
        $measured = (float) $transaction->markup_percent;

        if ($measured > $threshold) {
            $excess = $measured - $threshold;
            $severity = 'low';
            if ($excess >= 40) {
                $severity = 'high';
            } elseif ($excess >= 15) {
                $severity = 'medium';
            }

            $flag = AnomalyFlag::create([
                'batch_id' => $batch->id,
                'transaction_id' => $transaction->id,
                'user_id' => $transaction->seller_id,
                'rule_id' => $rule->id,
                'reason_code' => 'EXCESSIVE_MARKUP',
                'measured_value' => $measured,
                'threshold_value' => $threshold,
                'severity' => $severity,
                'status' => 'new',
            ]);

            // Update transaction validation status
            $transaction->update(['validation_status' => 'flagged']);

            // Notify authorities
            $authorities = User::whereIn('role', ['authority', 'admin'])->get();
            foreach ($authorities as $authority) {
                Notification::create([
                    'user_id' => $authority->id,
                    'title' => "Price Anomaly Flagged: Batch {$batch->batch_code}",
                    'message' => "Markup of {$measured}% exceeded threshold ({$threshold}%) for {$batch->crop->name} at stage {$stage}.",
                    'link' => '/authority/dashboard',
                ]);
            }

            // Notify seller under observation
            Notification::create([
                'user_id' => $transaction->seller_id,
                'title' => "Transaction Notice: Review Required",
                'message' => "Your transaction on batch {$batch->batch_code} has been marked for review due to price spread of {$measured}%.",
                'link' => '/vendor/dashboard',
            ]);

            return $flag;
        }

        return null;
    }
}
