<?php

namespace App\Http\Controllers;

use App\Models\AnomalyFlag;
use App\Models\Batch;
use App\Models\Crop;
use App\Models\PriceObservation;
use App\Models\PriceReport;
use App\Models\Transaction;
use App\Services\AuditLoggerService;
use App\Services\ReviewAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthorityController extends Controller
{
    public function dashboard(Request $request)
    {
        $districts = ReviewAccess::districts();
        $request->validate(['crop_id' => ['nullable', 'exists:crops,id']]);
        $crops = Crop::orderBy('name')->get();
        $defaultCropId = Batch::whereHas('transactions', fn ($q) => $q->where('is_voided', false)->where('stage', '!=', 'retail_sale')->whereIn('district_id', $districts))->value('crop_id');
        $selectedCrop = $crops->firstWhere('id', $request->input('crop_id')) ?? $crops->firstWhere('id', $defaultCropId) ?? $crops->first();
        $statusFilter = $request->input('flag_status');
        $reportFilter = $request->input('report_status');

        $flagQuery = AnomalyFlag::with(['batch.crop', 'transaction.seller', 'rule', 'reviewer'])->whereHas('transaction', fn ($q) => $q->whereIn('district_id', $districts))->latest();
        if ($statusFilter) {
            $flagQuery->where('status', $statusFilter);
        }
        $flags = $flagQuery->paginate(10, ['*'], 'flags_page')->withQueryString();

        $reportQuery = PriceReport::with(['crop', 'district', 'reviewer'])->whereIn('district_id', $districts)->latest();
        if ($reportFilter) {
            $reportQuery->where('status', $reportFilter);
        }
        $reports = $reportQuery->paginate(10, ['*'], 'reports_page')->withQueryString();

        $stats = [
            'total_batches' => Batch::whereHas('transactions', fn ($q) => $q->whereIn('district_id', $districts))->count(),
            'total_transactions' => Transaction::whereIn('district_id', $districts)->count(),
            'pending_flags' => AnomalyFlag::whereHas('transaction', fn ($q) => $q->whereIn('district_id', $districts))->whereIn('status', ['new', 'reviewing', 'escalated'])->count(),
            'pending_reports' => PriceReport::whereIn('district_id', $districts)->where('status', 'pending')->count(),
            'verified_reports' => PriceReport::whereIn('district_id', $districts)->where('status', 'verified')->count(),
        ];

        // Independent custody-stage groups, not the chronology of one batch.
        $stageAverages = Transaction::where('is_voided', false)
            ->where('stage', '!=', 'retail_sale')
            ->whereIn('district_id', $districts)
            ->whereHas('batch', fn ($q) => $q->where('crop_id', $selectedCrop?->id)->where('unit_needs_review', false))
            ->whereHas('batch.crop', fn ($q) => $q->where('unit_needs_review', false))
            ->where('unit', $selectedCrop?->unit)
            ->selectRaw('stage, AVG(price_per_unit) as avg_price, MIN(price_per_unit) as min_price, MAX(price_per_unit) as max_price, AVG(markup_percent) as avg_markup, COUNT(markup_percent) as markup_count, COUNT(*) as count')
            ->groupBy('stage')
            ->get();

        $stageNames = [
            'farmer_to_collector' => 'Farmer → collector',
            'collector_to_collector' => 'Collector → collector',
            'collector_to_wholesale' => 'Collector → wholesale',
            'wholesale_to_collector' => 'Wholesale → collector',
            'wholesale_to_wholesale' => 'Wholesale → wholesale',
            'collector_to_retail' => 'Collector → retail',
            'wholesale_to_retail' => 'Wholesale → retail',
        ];
        $stageOrder = array_flip(array_keys($stageNames));
        $stageAverages = $stageAverages->sortBy(fn ($row) => $stageOrder[$row->stage] ?? 99)->values();
        $chartLabels = $stageAverages->map(fn ($row) => $stageNames[$row->stage] ?? ucwords(str_replace('_', ' ', $row->stage)))->all();
        $chartPrices = $stageAverages->map(fn ($row) => round((float) $row->avg_price, 2))->all();
        $chartMarkups = $stageAverages->map(fn ($row) => $row->avg_markup === null ? null : round((float) $row->avg_markup, 2))->all();

        return view('authority.dashboard', compact(
            'crops', 'selectedCrop',
            'flags',
            'reports',
            'stats',
            'statusFilter',
            'reportFilter',
            'chartLabels',
            'chartPrices',
            'chartMarkups', 'stageAverages'
        ));
    }

    public function reviewFlag(Request $request, int $id)
    {
        $flag = AnomalyFlag::findOrFail($id);
        abort_if($flag->user_id === Auth::id(), 403, 'You cannot review your own transaction flag.');

        $validated = $request->validate([
            'status' => ['required', 'in:reviewing,cleared,escalated'],
            'reviewer_notes' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        DB::transaction(function () use ($id, $validated) {
            $source = AnomalyFlag::findOrFail($id)->transaction;
            Batch::whereKey($source->batch_id)->lockForUpdate()->firstOrFail();
            $source = Transaction::lockForUpdate()->findOrFail($source->id);
            $flag = AnomalyFlag::lockForUpdate()->findOrFail($id);
            ReviewAccess::authorize($source->district_id, $source->batch_id, $flag->user_id);
            abort_if($source->is_voided, 422, 'Voided source flags remain closed history.');
            abort_if($flag->status === 'cleared' && $validated['status'] !== 'cleared', 422, 'Closed flags cannot be reopened through an ordinary review.');
            $beforeStatus = $flag->status;

            $flag->update([
                'status' => $validated['status'],
                'reviewer_notes' => $validated['reviewer_notes'],
                'reviewed_by_user_id' => Auth::id(),
            ]);
            $source->update(['validation_status' => $source->anomalyFlags()->whereIn('status', ['new', 'reviewing', 'escalated'])->exists() ? 'flagged' : 'valid']);

            AuditLoggerService::log(
                action: 'FLAG_REVIEWED',
                targetType: 'AnomalyFlag',
                targetId: $flag->id,
                notes: "Flag #{$flag->id} status updated from '{$beforeStatus}' to '{$flag->status}'. Notes: {$flag->reviewer_notes}",
                userId: Auth::id()
            );

        });

        return redirect()->route('authority.dashboard')
            ->with('success', "Anomaly Flag #{$id} has been marked as {$validated['status']}.");
    }

    public function moderateReport(Request $request, int $id)
    {
        $report = PriceReport::findOrFail($id);
        abort_if($report->reporter_user_id === Auth::id(), 403, 'You cannot moderate your own report.');

        $validated = $request->validate([
            'status' => ['required', 'in:verified,rejected,duplicate'],
            'moderation_notes' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        DB::transaction(function () use ($id, $validated) {
            $report = PriceReport::lockForUpdate()->findOrFail($id);
            ReviewAccess::authorize($report->district_id, $report->batch_id, $report->reporter_user_id);
            $report->update(['status' => $validated['status'], 'moderation_notes' => $validated['moderation_notes'], 'reviewed_by_user_id' => Auth::id()]);
            if ($report->category === 'price') {
                $observation = PriceObservation::where('price_report_id', $report->id)->first();
                if ($report->status === 'verified') {
                    PriceObservation::updateOrCreate(['price_report_id' => $report->id], [
                        'crop_id' => $report->crop_id, 'district_id' => $report->district_id,
                        'market_name' => $report->market_name, 'unit' => $report->unit,
                        'observed_retail_price' => $report->reported_price, 'observation_date' => $report->incident_date,
                        'source' => 'verified_citizen_report', 'verification_status' => 'verified', 'is_demo' => $report->is_demo,
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
