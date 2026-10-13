<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReviewAccess
{
    public static function districts(?User $user = null): array
    {
        $user ??= auth()->user();

        return $user && in_array($user->role, ['authority', 'admin'], true) && $user->account_status === 'active'
            ? DB::table('review_assignments')->where('user_id', $user->id)->pluck('district_id')->all() : [];
    }

    public static function authorize(int $districtId, ?int $batchId = null, ?int $submitterId = null): void
    {
        abort_unless(in_array($districtId, self::districts()), 403, 'This case is outside your review assignment.');
        $actor = auth()->id();
        abort_if($actor === $submitterId, 403, 'A case participant cannot review their own case.');
        if ($batchId) {
            $batch = Batch::findOrFail($batchId);
            abort_if(in_array($actor, [$batch->farmer_id, $batch->current_custodian_id], true)
                || Transaction::where('batch_id', $batchId)->where(fn ($q) => $q->where('seller_id', $actor)->orWhere('buyer_id', $actor)->orWhere('recorded_by_user_id', $actor))->exists(), 403, 'A batch participant cannot review this case.');
        }
    }
}
