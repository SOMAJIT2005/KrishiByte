<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Crop;
use App\Models\District;
use App\Models\PriceObservation;
use App\Models\PriceReport;
use App\Services\CalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicController extends Controller
{
    public function index()
    {
        $crops = Crop::where('is_active', true)->get();
        $districts = District::orderBy('name')->get();

        $recentBatches = Batch::with(['crop', 'originDistrict', 'currentCustodian'])
            ->where('status', '!=', 'voided')->where('verification_status', '!=', 'rejected')
            ->latest()
            ->take(6)
            ->get();

        $stats = [
            'total_batches' => Batch::count(),
            'tracked_crops' => Crop::where('is_active', true)->count(),
            'monitored_districts' => District::count(),
            'verified_observations' => PriceObservation::where('verification_status', 'verified')->count(),
        ];

        return view('public.index', compact('crops', 'districts', 'recentBatches', 'stats'));
    }

    public function prices(Request $request)
    {
        $request->validate(['crop_id' => ['nullable', 'exists:crops,id'], 'district_id' => ['nullable', 'exists:districts,id'], 'date' => ['nullable', 'date']]);
        $crops = Crop::where('is_active', true)->orderBy('name')->get();
        $districts = District::orderBy('name')->get();

        $selectedCropId = $request->input('crop_id', $crops->first()?->id);
        $selectedDistrictId = $request->input('district_id');
        $selectedDate = $request->input('date', now()->toDateString());

        $query = PriceObservation::with(['crop', 'district'])
            ->where('verification_status', 'verified');

        if ($selectedCropId) {
            $query->where('crop_id', $selectedCropId);
            $query->where('unit', $crops->firstWhere('id', $selectedCropId)?->unit);
        }

        if ($selectedDistrictId) {
            $query->where('district_id', $selectedDistrictId);
        }

        if ($selectedDate) {
            $query->whereDate('observation_date', '<=', $selectedDate);
        }

        $observations = $query->latest('observation_date')->paginate(15)->withQueryString();

        // Calculate indicative reference price using robust statistics
        $priceValues = (clone $query)->pluck('observed_retail_price')->map(fn ($p) => (float) $p)->toArray();
        $referenceStats = CalculationService::calculateReferencePrice($priceValues);

        $selectedCrop = $crops->firstWhere('id', $selectedCropId);
        $selectedDistrict = $districts->firstWhere('id', $selectedDistrictId);

        return view('public.prices', compact(
            'crops',
            'districts',
            'observations',
            'selectedCropId',
            'selectedDistrictId',
            'selectedDate',
            'referenceStats',
            'selectedCrop',
            'selectedDistrict'
        ));
    }

    public function traceability(Request $request)
    {
        $batchCode = trim($request->input('code', ''));

        if (! empty($batchCode)) {
            return redirect()->route('public.traceability.show', ['batch_code' => $batchCode]);
        }

        $recentBatches = Batch::with(['crop', 'originDistrict'])
            ->where('status', '!=', 'voided')->where('verification_status', '!=', 'rejected')
            ->latest()
            ->take(10)
            ->get();

        return view('public.traceability-search', compact('recentBatches'));
    }

    public function traceabilityShow(string $batch_code)
    {
        $batch = Batch::with([
            'crop',
            'originDistrict',
            'farmer',
            'currentCustodian',
            'transactions' => function ($q) {
                $q->where('is_voided', false)
                    ->with(['seller', 'buyer', 'district'])
                    ->orderBy('sequence_no', 'asc');
            },
        ])->where('status', '!=', 'voided')->where('verification_status', '!=', 'rejected')->where('batch_code', $batch_code)->firstOrFail();

        // Prepare map coordinates trail
        $mapPoints = [];
        if ($batch->origin_latitude && $batch->origin_longitude) {
            $mapPoints[] = [
                'name' => 'Farm Origin: '.($batch->farm_location_name ?: $batch->originDistrict->name),
                'stage' => 'Farm Gate',
                'lat' => (float) $batch->origin_latitude,
                'lng' => (float) $batch->origin_longitude,
                'price' => (float) $batch->initial_farmer_price,
            ];
        }

        foreach ($batch->transactions as $tx) {
            if ($tx->latitude && $tx->longitude) {
                $mapPoints[] = [
                    'name' => ($tx->market_name ?: $tx->district->name).' ('.ucwords(str_replace('_', ' ', $tx->stage)).')',
                    'stage' => ucwords(str_replace('_', ' ', $tx->stage)),
                    'lat' => (float) $tx->latitude,
                    'lng' => (float) $tx->longitude,
                    'price' => (float) $tx->price_per_unit,
                ];
            }
        }

        return view('public.traceability-show', compact('batch', 'mapPoints'));
    }

    public function reportForm()
    {
        $crops = Crop::where('is_active', true)->orderBy('name')->get();
        $districts = District::orderBy('name')->get();

        return view('public.report', compact('crops', 'districts'));
    }

    public function reportStore(Request $request)
    {
        $request->merge(['category' => $request->input('category') ?: 'price']);
        $validated = $request->validate([
            'category' => ['nullable', 'in:price,quantity,quality,traceability'],
            'batch_code' => ['nullable', 'exists:batches,batch_code'],
            'crop_id' => ['required', 'exists:crops,id'],
            'district_id' => ['required', 'exists:districts,id'],
            'market_name' => ['required', 'string', 'max:255'],
            'reported_price' => ['nullable', 'required_if:category,price', 'numeric', 'min:0.01', 'max:10000'],
            'unit' => ['required', 'string', 'max:20'],
            'incident_date' => ['required', 'date', 'before_or_equal:today'],
            'report_narrative' => ['required', 'string', 'min:10', 'max:2000'],
            'reporter_name' => ['nullable', 'string', 'max:100'],
            'reporter_phone' => ['nullable', 'string', 'max:20'],
            'reporter_email' => ['nullable', 'email', 'max:100'],
            'latitude' => ['nullable', 'numeric', 'between:20,27'],
            'longitude' => ['nullable', 'numeric', 'between:88,93'],
            'evidence_photo' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'], // Max 2MB, strict image mimes
        ]);

        if ($request->user() && $request->user()->account_status !== 'active') {
            abort(403);
        }
        $duplicate = PriceReport::where('crop_id', $validated['crop_id'])->where('district_id', $validated['district_id'])
            ->where('market_name', $validated['market_name'])->where('reported_price', $validated['reported_price'] ?? null)
            ->where('category', $validated['category'])->where('reporter_user_id', $request->user()?->id)
            ->where('report_narrative', $validated['report_narrative'])->whereDate('incident_date', $validated['incident_date'])
            ->where('ip_address', $request->ip())->where('created_at', '>=', now()->subDay())->first();
        if ($duplicate) {
            return redirect()->route('public.report.track', ['code' => $duplicate->tracking_code])->with('info', 'This report has already been received.');
        }
        $evidencePath = null;
        $evidenceMime = null;

        if ($request->hasFile('evidence_photo')) {
            $file = $request->file('evidence_photo');
            $safeName = 'report_'.Str::random(24).'.'.$file->extension();
            $evidencePath = $file->storeAs('evidence', $safeName, 'local');
            $evidenceMime = $file->getMimeType();
        }

        $trackingCode = PriceReport::generateTrackingCode();

        $report = PriceReport::create([
            'tracking_code' => $trackingCode,
            'reporter_user_id' => $request->user()?->id,
            'category' => $validated['category'] ?? 'price',
            'batch_id' => ! empty($validated['batch_code']) ? Batch::where('batch_code', $validated['batch_code'])->value('id') : null,
            'reporter_name' => $validated['reporter_name'] ?? null,
            'reporter_phone' => $validated['reporter_phone'] ?? null,
            'reporter_email' => $validated['reporter_email'] ?? null,
            'crop_id' => $validated['crop_id'],
            'district_id' => $validated['district_id'],
            'market_name' => $validated['market_name'],
            'reported_price' => $validated['reported_price'] ?? null,
            'unit' => $validated['unit'],
            'report_narrative' => $validated['report_narrative'],
            'incident_date' => $validated['incident_date'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'evidence_path' => $evidencePath,
            'evidence_mime' => $evidenceMime,
            'status' => 'pending',
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('public.report.track', ['code' => $trackingCode])
            ->with('success', "Report submitted successfully! Your tracking code is: {$trackingCode}. Our market monitoring desk will review this entry.");
    }

    public function trackReport(Request $request)
    {
        $code = trim($request->input('code', ''));
        $report = null;

        if (! empty($code)) {
            $report = PriceReport::with(['crop', 'district'])
                ->where('tracking_code', $code)
                ->first();
        }

        return view('public.report-track', compact('code', 'report'));
    }

    public function myReports()
    {
        $reports = PriceReport::with(['crop', 'district'])->where('reporter_user_id', auth()->id())->latest()->paginate(15);

        return view('public.my-reports', compact('reports'));
    }

    public function evidence(int $id)
    {
        $report = PriceReport::findOrFail($id);
        abort_unless(auth()->user()->isAuthority() || $report->reporter_user_id === auth()->id(), 403);
        abort_unless($report->evidence_path && Storage::disk('local')->exists($report->evidence_path), 404);

        return Storage::disk('local')->response($report->evidence_path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function faq()
    {
        return view('public.faq');
    }
}
