<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\HandoffProposal;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CorrectionService
{
    public function request(int $transactionId, int $actorId, string $key, string $reason): int
    {
        return DB::transaction(function () use ($transactionId, $actorId, $key, $reason) {
            $tx = Transaction::findOrFail($transactionId);
            Batch::whereKey($tx->batch_id)->lockForUpdate()->firstOrFail();
            $tx = Transaction::lockForUpdate()->findOrFail($transactionId);
            $actor = User::findOrFail($actorId);
            if ($actor->account_status !== 'active' || ! in_array($actorId, [$tx->seller_id, $tx->buyer_id], true) || $tx->stage === 'retail_sale' || mb_strlen(trim($reason)) < 5) {
                throw new InvalidArgumentException('Only a custody participant can request this correction with a reason.');
            }
            $prior = DB::table('correction_requests')->where('requested_by', $actorId)->where('request_key', $key)->first();
            if ($prior) {
                if ($prior->transaction_id !== $transactionId || $prior->reason !== $reason) {
                    throw new InvalidArgumentException('Request key reused with different correction terms.');
                }

                return $prior->id;
            }
            if ($tx->is_voided || Transaction::where('batch_id', $tx->batch_id)->where('is_voided', false)->where('sequence_no', '>', $tx->sequence_no)->exists()
                || HandoffProposal::where('batch_id', $tx->batch_id)->where('status', 'proposed')->where('expires_at', '>', now())->exists()
                || DB::table('correction_requests')->where('transaction_id', $tx->id)->where('status', 'pending')->exists()) {
                throw new InvalidArgumentException('Only the latest active custody transfer can have one pending correction.');
            }
            $id = DB::table('correction_requests')->insertGetId(['transaction_id' => $transactionId, 'requested_by' => $actorId, 'counterparty_id' => $actorId === $tx->seller_id ? $tx->buyer_id : $tx->seller_id, 'request_key' => $key, 'reason' => $reason, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
            AuditLoggerService::log(action: 'CORRECTION_REQUESTED', targetType: 'CorrectionRequest', targetId: $id, notes: $reason, userId: $actorId);

            return $id;
        });
    }

    public function decide(int $id, int $actorId, string $decision, string $reason): void
    {
        DB::transaction(function () use ($id, $actorId, $decision, $reason) {
            $request = DB::table('correction_requests')->find($id);
            abort_unless($request, 404);
            $tx = Transaction::findOrFail($request->transaction_id);
            Batch::whereKey($tx->batch_id)->lockForUpdate()->firstOrFail();
            Transaction::whereKey($tx->id)->lockForUpdate()->firstOrFail();
            $request = DB::table('correction_requests')->where('id', $id)->lockForUpdate()->first();
            abort_unless(User::findOrFail($actorId)->account_status === 'active' && ($decision === 'cancelled' ? $actorId === $request->requested_by : $actorId === $request->counterparty_id), 403);
            abort_unless(in_array($decision, ['accepted', 'disputed', 'cancelled'], true) && mb_strlen(trim($reason)) >= 5, 422);
            if ($request->status === $decision) {
                return;
            }
            abort_unless($request->status === 'pending', 409);
            DB::table('correction_requests')->where('id', $id)->update(['status' => $decision, 'decision_reason' => $reason, 'decided_at' => now(), 'updated_at' => now()]);
            if ($decision === 'accepted') {
                app(BatchHandoffService::class)->voidTransaction($tx->id, $actorId, $reason, $id);
            }
            AuditLoggerService::log(action: 'CORRECTION_'.strtoupper($decision), targetType: 'CorrectionRequest', targetId: $id, notes: $reason, userId: $actorId);
        });
    }
}
