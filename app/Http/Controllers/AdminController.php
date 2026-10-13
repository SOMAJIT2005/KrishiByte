<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Batch;
use App\Models\Crop;
use App\Models\MarkupRule;
use App\Models\User;
use App\Services\AuditLoggerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function dashboard(Request $request)
    {
        $users = User::with('district')->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%'))->orderBy('name')->paginate(15)->withQueryString();
        $rules = MarkupRule::with('crop')->get();
        $crops = Crop::orderBy('name')->get();

        return view('admin.dashboard', compact('users', 'rules', 'crops'));
    }

    public function verifyUser(Request $request, int $id)
    {
        $data = $request->validate(['verified' => ['required', 'boolean'], 'reason' => ['required', 'string', 'min:5', 'max:500']]);
        $user = User::findOrFail($id);
        abort_unless(in_array($user->role, ['farmer', 'collector', 'wholesale', 'retail']), 422);
        abort_if($data['verified'] && $user->account_status !== 'active', 422, 'Activate this account first.');
        DB::transaction(function () use ($user, $data) {
            $user = User::lockForUpdate()->findOrFail($user->id);
            abort_if($data['verified'] && $user->account_status !== 'active', 422, 'Activate this account first.');
            $before = ['is_verified' => $user->is_verified];
            $user->update(['is_verified' => $data['verified'], 'verified_by_user_id' => Auth::id(), 'verification_date' => now(), 'verification_reason' => $data['reason']]);
            AuditLoggerService::log(action: 'USER_VERIFICATION_UPDATED', targetType: 'User', targetId: $user->id, notes: $data['reason'], before: $before, after: ['is_verified' => $user->is_verified], userId: Auth::id());
        });

        return back()->with('success', 'Account verification updated.');
    }

    public function toggleUserStatus(Request $request, int $id)
    {
        $validated = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']]);
        abort_if($id === Auth::id(), 422, 'You cannot suspend your own admin account.');
        DB::transaction(function () use ($id, $validated) {
            $user = User::lockForUpdate()->findOrFail($id);
            $before = $user->account_status;
            $status = $before === 'active' ? 'suspended' : 'active';
            $user->update(['account_status' => $status]);
            AuditLoggerService::log(action: 'USER_STATUS_TOGGLED', targetType: 'User', targetId: $id, notes: $validated['reason'], before: ['account_status' => $before], after: ['account_status' => $status], userId: Auth::id());
        });

        return back()->with('success', 'Account status updated.');
    }

    public function storeRule(Request $request)
    {
        $validated = $request->validate([
            'crop_id' => ['nullable', 'exists:crops,id'],
            'stage' => ['required', 'in:all,farmer_to_collector,collector_to_collector,collector_to_wholesale,collector_to_retail,wholesale_to_collector,wholesale_to_wholesale,wholesale_to_retail,retail_sale'],
            'max_markup_percent' => ['required', 'numeric', 'decimal:0,2', 'min:1', 'max:500'],
            'max_spread_amount' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:100000'],
            'explanation' => ['required', 'string', 'max:500'],
            'effective_from' => ['nullable', 'date_format:Y-m-d'],
            'effective_to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('effective_from') ? ['after_or_equal:effective_from'] : [])],
        ]);

        DB::transaction(function () use ($validated) {
            $rule = MarkupRule::create([
                'crop_id' => $validated['crop_id'] ?? null,
                'stage' => $validated['stage'],
                'max_markup_percent' => $validated['max_markup_percent'],
                'max_spread_amount' => $validated['max_spread_amount'] ?? null,
                'is_active' => true,
                'created_by_user_id' => Auth::id(),
                'explanation' => $validated['explanation'],
                'effective_from' => $validated['effective_from'] ?? null,
                'effective_to' => $validated['effective_to'] ?? null,
            ]);

            AuditLoggerService::log(
                action: 'MARKUP_RULE_CREATED',
                targetType: 'MarkupRule',
                targetId: $rule->id,
                notes: "Created markup rule: max {$rule->max_markup_percent}% for stage {$rule->stage}",
                userId: Auth::id()
            );

        });

        return redirect()->back()->with('success', 'Markup threshold rule added successfully.');
    }

    public function toggleRule(int $id)
    {
        DB::transaction(function () use ($id) {
            $rule = MarkupRule::lockForUpdate()->findOrFail($id);
            $rule->update(['is_active' => ! $rule->is_active]);

            AuditLoggerService::log(
                action: 'MARKUP_RULE_TOGGLED',
                targetType: 'MarkupRule',
                targetId: $rule->id,
                notes: "Markup rule #{$rule->id} active state toggled to ".($rule->is_active ? 'active' : 'inactive'),
                userId: Auth::id()
            );

        });

        return redirect()->back()->with('success', 'Rule state updated.');
    }

    public function auditLogs()
    {
        $logs = AuditLog::with('user')->latest('created_at')->paginate(25);

        return view('admin.audit-logs', compact('logs'));
    }

    public function batchVerificationIndex()
    {
        $batches = Batch::with(['crop', 'farmer', 'originDistrict', 'currentCustodian', 'verifiedByAdmin'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.batch-verification', compact('batches'));
    }

    public function verifyBatch(Request $request, int $batchId)
    {
        $batch = Batch::findOrFail($batchId);

        if ($batch->verification_status === 'verified') {
            return redirect()->back()->with('error', 'This batch is already verified.');
        }

        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($batch, $validated) {
            $batch = Batch::lockForUpdate()->findOrFail($batch->id);
            $before = $batch->verification_status;
            $batch->update([
                'verification_status' => 'verified',
                'verified_by_admin_id' => Auth::id(),
                'verified_at' => now(),
                'admin_verification_notes' => $validated['admin_notes'] ?? null,
            ]);

            AuditLoggerService::log(
                action: 'BATCH_VERIFIED',
                targetType: 'Batch',
                targetId: $batch->id,
                notes: "Admin verified batch {$batch->batch_code} (crop: {$batch->crop->name}, status: {$batch->status})",
                before: ['verification_status' => $before],
                after: ['verification_status' => 'verified', 'verified_by_admin_id' => Auth::id()],
                userId: Auth::id()
            );
        });

        return redirect()->back()->with('success', "Batch {$batch->batch_code} marked as verified and now visible to all users.");
    }

    public function rejectBatch(Request $request, int $batchId)
    {
        $batch = Batch::findOrFail($batchId);

        if ($batch->verification_status === 'rejected') {
            return redirect()->back()->with('error', 'This batch is already rejected.');
        }

        $validated = $request->validate([
            'admin_notes' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($batch, $validated) {
            $batch = Batch::lockForUpdate()->findOrFail($batch->id);
            $before = $batch->verification_status;
            $batch->update([
                'verification_status' => 'rejected',
                'verified_by_admin_id' => Auth::id(),
                'verified_at' => now(),
                'admin_verification_notes' => $validated['admin_notes'],
            ]);

            AuditLoggerService::log(
                action: 'BATCH_REJECTED',
                targetType: 'Batch',
                targetId: $batch->id,
                notes: "Admin rejected batch {$batch->batch_code}. Reason: {$validated['admin_notes']}",
                before: ['verification_status' => $before],
                after: ['verification_status' => 'rejected', 'verified_by_admin_id' => Auth::id()],
                userId: Auth::id()
            );
        });

        return redirect()->back()->with('success', "Batch {$batch->batch_code} rejected. Admin note recorded.");
    }

    public function batchDetail(int $batchId)
    {
        $batch = Batch::with([
            'crop',
            'farmer',
            'originDistrict',
            'currentCustodian',
            'verifiedByAdmin',
            'transactions.seller',
            'transactions.buyer',
            'transactions.district',
            'anomalyFlags.rule',
        ])->findOrFail($batchId);

        return view('admin.batch-detail', compact('batch'));
    }
}
