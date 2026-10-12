<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Crop;
use App\Models\District;
use App\Models\MarkupRule;
use App\Models\User;
use App\Services\AuditLoggerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function toggleUserStatus(int $id)
    {
        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return redirect()->back()->with('error', 'You cannot suspend your own admin account.');
        }

        $newStatus = ($user->account_status === 'active') ? 'suspended' : 'active';
        $user->update(['account_status' => $newStatus]);

        AuditLoggerService::log(
            action: 'USER_STATUS_TOGGLED',
            targetType: 'User',
            targetId: $user->id,
            notes: "Admin set account status of {$user->email} to {$newStatus}",
            userId: Auth::id()
        );

        return redirect()->back()->with('success', "Account {$user->email} is now {$newStatus}.");
    }

    public function storeRule(Request $request)
    {
        $validated = $request->validate([
            'crop_id' => ['nullable', 'exists:crops,id'],
            'stage' => ['required', 'in:all,farmer_to_collector,collector_to_wholesale,wholesale_to_retail'],
            'max_markup_percent' => ['required', 'numeric', 'min:1', 'max:500'],
            'max_spread_amount' => ['nullable', 'numeric', 'min:0'],
            'explanation' => ['required', 'string', 'max:500'],
        ]);

        $rule = MarkupRule::create([
            'crop_id' => $validated['crop_id'] ?: null,
            'stage' => $validated['stage'],
            'max_markup_percent' => $validated['max_markup_percent'],
            'max_spread_amount' => $validated['max_spread_amount'] ?: null,
            'is_active' => true,
            'created_by_user_id' => Auth::id(),
            'explanation' => $validated['explanation'],
        ]);

        AuditLoggerService::log(
            action: 'MARKUP_RULE_CREATED',
            targetType: 'MarkupRule',
            targetId: $rule->id,
            notes: "Created markup rule: max {$rule->max_markup_percent}% for stage {$rule->stage}",
            userId: Auth::id()
        );

        return redirect()->back()->with('success', 'Markup threshold rule added successfully.');
    }

    public function toggleRule(int $id)
    {
        $rule = MarkupRule::findOrFail($id);
        $rule->update(['is_active' => !$rule->is_active]);

        AuditLoggerService::log(
            action: 'MARKUP_RULE_TOGGLED',
            targetType: 'MarkupRule',
            targetId: $rule->id,
            notes: "Markup rule #{$rule->id} active state toggled to " . ($rule->is_active ? 'active' : 'inactive'),
            userId: Auth::id()
        );

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
            before: ['verification_status' => 'unverified'],
            after: ['verification_status' => 'verified', 'verified_by_admin_id' => Auth::id()],
            userId: Auth::id()
        );

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

        AuditLoggerService::log(
            action: 'BATCH_REJECTED',
            targetType: 'Batch',
            targetId: $batch->id,
            notes: "Admin rejected batch {$batch->batch_code}. Reason: {$validated['admin_notes']}",
            before: ['verification_status' => $batch->getOriginal('verification_status')],
            after: ['verification_status' => 'rejected', 'verified_by_admin_id' => Auth::id()],
            userId: Auth::id()
        );

        return redirect()->back()->with('success', "Batch {$batch->batch_code} rejected. Admin note recorded.");
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
            before: ['verification_status' => 'unverified'],
            after: ['verification_status' => 'verified', 'verified_by_admin_id' => Auth::id()],
            userId: Auth::id()
        );

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
            before: ['verification_status' => $batch->getOriginal('verification_status')],
            after: ['verification_status' => 'rejected', 'verified_by_admin_id' => Auth::id()],
            userId: Auth::id()
        );

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
            before: ['verification_status' => 'unverified'],
            after: ['verification_status' => 'verified', 'verified_by_admin_id' => Auth::id()],
            userId: Auth::id()
        );

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

        AuditLoggerService::log(
            action: 'BATCH_REJECTED',
            targetType: 'Batch',
            targetId: $batch->id,
            notes: "Admin rejected batch {$batch->batch_code}. Reason: {$validated['admin_notes']}",
            before: ['verification_status' => $batch->getOriginal('verification_status')],
            after: ['verification_status' => 'rejected', 'verified_by_admin_id' => Auth::id()],
            userId: Auth::id()
        );

        return redirect()->back()->with('success', "Batch {$batch->batch_code} rejected. Admin note recorded.");
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
            before: ['verification_status' => 'unverified'],
            after: ['verification_status' => 'verified', 'verified_by_admin_id' => Auth::id()],
            userId: Auth::id()
        );

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
            before: ['verification_status' => $batch->getOriginal('verification_status')],
            after: ['verification_status' => 'rejected', 'verified_by_admin_id' => Auth::id()],
            userId: Auth::id()
        );

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
