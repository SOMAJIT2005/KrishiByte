<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Crop;
use App\Models\District;
use App\Services\AuditLoggerService;
use App\Services\PriceReferenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FarmerController extends Controller
{
    public function dashboard()
    {
        $farmer = Auth::user();

        $batches = Batch::with(['crop', 'originDistrict', 'currentCustodian', 'transactions'])
            ->where('farmer_id', $farmer->id)
            ->latest()
            ->paginate(10);

        $crops = Crop::where('is_active', true)->orderBy('name')->get();
        $districts = District::orderBy('name')->get();

        $stats = [
            'total_batches' => Batch::where('farmer_id', $farmer->id)->count(),
            'active_in_custody' => Batch::where('farmer_id', $farmer->id)->where('current_custodian_id', $farmer->id)->count(),
            'transferred' => Batch::where('farmer_id', $farmer->id)->where('current_custodian_id', '!=', $farmer->id)->count(),
        ];

        // Market benchmark prices in farmer's district
        $districtId = $farmer->district_id ?? $districts->first()?->id;
        $marketBenchmarks = [];
        foreach ($crops as $crop) {
            $statsPrice = PriceReferenceService::stats(PriceReferenceService::query($crop->id, $crop->unit, $districtId, now('Asia/Dhaka')->toDateString()));
            if ($statsPrice['indicative_price']) {
                $marketBenchmarks[] = [
                    'crop' => $crop->name,
                    'price' => $statsPrice['indicative_price'],
                    'unit' => $crop->unit,
                    'sample_sufficiency' => $statsPrice['sample_sufficiency'],
                    'samples' => $statsPrice['sample_count'],
                ];
            }
        }

        return view('farmer.dashboard', compact('batches', 'crops', 'districts', 'stats', 'marketBenchmarks', 'farmer'));
    }

    public function storeBatch(Request $request)
    {
        abort_unless(Auth::user()->isFarmer(), 403, 'Harvest lots must be registered by a farmer account.');
        $validated = $request->validate([
            'crop_id' => ['required', 'exists:crops,id'],
            'origin_district_id' => ['required', 'exists:districts,id'],
            'farm_location_name' => ['nullable', 'string', 'max:255'],
            'harvest_date' => ['required', 'date', 'before_or_equal:today'],
            'initial_quantity' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:1000000'],
            'unit' => ['required', 'in:kg,mon,sack,piece'],
            'initial_farmer_price' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:100000'],
            'origin_latitude' => ['nullable', 'numeric', 'between:20,27'],
            'origin_longitude' => ['nullable', 'numeric', 'between:88,93'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $batchCode = Batch::generateBatchCode();

        $batch = Batch::create([
            'batch_code' => $batchCode,
            'crop_id' => $validated['crop_id'],
            'farmer_id' => Auth::id(),
            'origin_district_id' => $validated['origin_district_id'],
            'farm_location_name' => $validated['farm_location_name'] ?? null,
            'origin_latitude' => $validated['origin_latitude'] ?? null,
            'origin_longitude' => $validated['origin_longitude'] ?? null,
            'harvest_date' => $validated['harvest_date'],
            'initial_quantity' => $validated['initial_quantity'],
            'available_quantity' => $validated['initial_quantity'],
            'unit' => $validated['unit'],
            'initial_farmer_price' => $validated['initial_farmer_price'],
            'current_custodian_id' => Auth::id(),
            'current_stage' => 'farm_gate',
            'status' => 'active',
            'notes' => $validated['notes'] ?? null,
        ]);

        AuditLoggerService::log(
            action: 'BATCH_CREATED',
            targetType: 'Batch',
            targetId: $batch->id,
            notes: "Farmer created batch {$batch->batch_code} for {$batch->initial_quantity} {$batch->unit} @ BDT {$batch->initial_farmer_price}"
        );

        return redirect()->route('farmer.dashboard')
            ->with('success', "Batch {$batchCode} created successfully! You are the initial custodian at the farm gate.");
    }
}
