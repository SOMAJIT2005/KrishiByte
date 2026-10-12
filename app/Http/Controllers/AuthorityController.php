<?php

namespace App\Http\Controllers;

use App\Models\AnomalyFlag;
use App\Models\Batch;
use App\Models\Crop;
use App\Models\PriceObservation;
use App\Models\PriceReport;
use App\Models\Transaction;
use App\Services\AuditLoggerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthorityController extends Controller
{
    public function dashboard(Request $request)
    {
        $request->validate(['crop_id' => ['nullable', 'exists:crops,id']]);
        $crops = Crop::orderBy('name')->get();
        $defaultCropId = Batch::whereHas('transactions', fn ($q) => $q->where('is_voided', false))->value('crop_id');
        $selectedCrop = $crops->firstWhere('id', $request->input('crop_id')) ?? $crops->firstWhere('id', $defaultCropId) ?? $crops->first();
        $statusFilter = $request->input('flag_status');
        $reportFilter = $request->input('report_status');

        $flagQuery = AnomalyFlag::with(['batch.crop', 'transaction.seller', 'rule', 'reviewer'])->latest();
        if ($statusFilter) {
            $flagQuery->where('status', $statusFilter);
        }
        $flags = $flagQuery->paginate(10, ['*'], 'flags_page')->withQueryString();

        $reportQuery = PriceReport::with(['crop', 'district', 'reviewer'])->latest();
        if ($reportFilter) {
            $reportQuery->where('status', $reportFilter);
        }
        $reports = $reportQuery->paginate(10, ['*'], 'reports_page')->withQueryString();

        $stats = [
            'total_batches' => Batch::count(),
            'total_transactions' => Transaction::count(),
            'pending_flags' => AnomalyFlag::whereIn('status', ['new', 'reviewing'])->count(),
            'pending_reports' => PriceReport::where('status', 'pending')->count(),
            'verified_reports' => PriceReport::where('status', 'verified')->count(),
        ];

        // Chart Data: Price spread averages across stages
        $stageAverages = Transaction::where('is_voided', false)
            ->whereHas('batch', fn ($q) => $q->where('crop_id', $selectedCrop?->id))
            ->where('unit', $selectedCrop?->unit)
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
            'crops', 'selectedCrop',
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

        DB::transaction(function () use ($id, $validated) {
            $report = PriceReport::lockForUpdate()->findOrFail($id);
            $report->update(['status' => $validated['status'], 'moderation_notes' => $validated['moderation_notes'], 'reviewed_by_user_id' => Auth::id()]);
            if ($report->category === 'price') {
                $observation = PriceObservation::where('price_report_id', $report->id)->first();
                if ($report->status === 'verified') {
                    PriceObservation::updateOrCreate(['price_report_id' => $report->id], [
                        'crop_id' => $report->crop_id, 'district_id' => $report->district_id,
                        'market_name' => $report->market_name, 'unit' => $report->unit,
                        'observed_retail_price' => $report->reported_price, 'observation_date' => $report->incident_date,
                        'source' => 'verified_citizen_report', 'verification_status' => 'verified',
                        'entered_by_user_id' => Auth::id(), 'notes' => 'Reviewed report '.$report->tracking_code,
                    ]);
                } elseif ($observation) {
                    $observation->update(['verification_status' => 'rejected']);
                }
            }
            AuditLoggerService::log(action: 'REPORT_MODERATED', targetType: 'PriceReport', targetId: $report->id, notes: $validated['moderation_notes'], after: ['status' => $report->status], userId: Auth::id());
        });

        return redirect()->route('authority.dashboard')
            ->with('success', "Price report {$report->tracking_code} updated to {$validated['status']}.");
    }
}
