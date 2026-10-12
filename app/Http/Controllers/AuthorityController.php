<?php

namespace App\Http\Controllers;

use App\Models\AnomalyFlag;
use App\Models\Batch;
use App\Models\Crop;
use App\Models\District;
use App\Models\PriceObservation;
use App\Models\PriceReport;
use App\Models\Transaction;
use App\Services\AuditLoggerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthorityController extends Controller
{
    public function dashboard(Request $request)
    {
        $statusFilter = $request->input('flag_status');
        $reportFilter = $request->input('report_status');

        $flagQuery = AnomalyFlag::with(['batch.crop', 'transaction.seller', 'rule', 'reviewer'])->latest();
        if ($statusFilter) {
            $flagQuery->where('status', $statusFilter);
        }
        $flags = $flagQuery->paginate(10, ['*'], 'flags_page');

        $reportQuery = PriceReport::with(['crop', 'district', 'reviewer'])->latest();
        if ($reportFilter) {
            $reportQuery->where('status', $reportFilter);
        }
        $reports = $reportQuery->paginate(10, ['*'], 'reports_page');

        $stats = [
            'total_batches' => Batch::count(),
            'total_transactions' => Transaction::count(),
            'pending_flags' => AnomalyFlag::whereIn('status', ['new', 'reviewing'])->count(),
            'pending_reports' => PriceReport::where('status', 'pending')->count(),
            'verified_reports' => PriceReport::where('status', 'verified')->count(),
        ];

        // Chart Data: Price spread averages across stages
        $stageAverages = Transaction::where('is_voided', false)
            ->selectRaw('stage, AVG(price_per_unit) as avg_price, AVG(markup_percent) as avg_markup, count(*) as count')
            ->groupBy('stage')
            ->get();

        $chartLabels = [];
        $chartPrices = [];
        $chartMarkups = [];

        foreach ($stageAverages as $sa) {
            $chartLabels[] = ucwords(str_replace('_', ' ', $sa->stage));
            $chartPrices[] = round((float) $sa->avg_price, 2);
            $chartMarkups[] = round((float) ($sa->avg_markup ?? 0), 2);
        }

        return view('authority.dashboard', compact(
            'flags',
            'reports',
            'stats',
            'statusFilter',
            'reportFilter',
            'chartLabels',
            'chartPrices',
            'chartMarkups'
        ));
    }

    public function reviewFlag(Request $request, int $id)
    {
        $flag = AnomalyFlag::findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'in:reviewing,cleared,escalated'],
            'reviewer_notes' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $beforeStatus = $flag->status;

        $flag->update([
            'status' => $validated['status'],
            'reviewer_notes' => $validated['reviewer_notes'],
            'reviewed_by_user_id' => Auth::id(),
        ]);

        AuditLoggerService::log(
            action: 'FLAG_REVIEWED',
            targetType: 'AnomalyFlag',
            targetId: $flag->id,
            notes: "Flag #{$flag->id} status updated from '{$beforeStatus}' to '{$flag->status}'. Notes: {$flag->reviewer_notes}",
            userId: Auth::id()
        );

        return redirect()->route('authority.dashboard')
            ->with('success', "Anomaly Flag #{$id} has been marked as {$validated['status']}.");
    }

    public function moderateReport(Request $request, int $id)
    {
        $report = PriceReport::findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'in:verified,rejected,duplicate'],
            'moderation_notes' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $beforeStatus = $report->status;

        $report->update([
            'status' => $validated['status'],
            'moderation_notes' => $validated['moderation_notes'],
            'reviewed_by_user_id' => Auth::id(),
        ]);

        // If verified, also create a verified price observation to feed the reference price system
        if ($validated['status'] === 'verified' && $beforeStatus !== 'verified') {
            PriceObservation::create([
                'crop_id' => $report->crop_id,
                'district_id' => $report->district_id,
                'market_name' => $report->market_name,
                'unit' => $report->unit,
                'observed_retail_price' => $report->reported_price,
                'observation_date' => $report->incident_date,
                'source' => 'verified_citizen_report',
                'verification_status' => 'verified',
                'entered_by_user_id' => Auth::id(),
                'notes' => "Derived from verified consumer report #{$report->tracking_code}. " . $report->moderation_notes,
            ]);
        }

        AuditLoggerService::log(
            action: 'REPORT_MODERATED',
            targetType: 'PriceReport',
            targetId: $report->id,
            notes: "Report #{$report->tracking_code} moderated as {$validated['status']}. Notes: {$validated['moderation_notes']}",
            userId: Auth::id()
        );

        return redirect()->route('authority.dashboard')
            ->with('success', "Price report {$report->tracking_code} updated to {$validated['status']}.");
    }
}
