<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Crop;
use App\Models\District;
use App\Models\FarmerEnrollment;
use App\Models\FarmerRating;
use App\Models\FarmPurchase;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AuditLoggerService;
use App\Services\EvidenceService;
use App\Services\FarmPurchaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FarmerProfileController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['search' => ['nullable', 'string', 'max:100']]);
        $farmers = User::where('role', 'farmer')->where('account_status', 'active')->where('farmer_profile_public', true)->with('district')->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%'))->orderBy('name')->paginate(12)->withQueryString();

        return view('public.farmers', compact('farmers'));
    }

    public function show(int $id)
    {
        $farmer = User::where('role', 'farmer')->where('account_status', 'active')->with('district')->findOrFail($id);
        abort_unless($farmer->farmer_profile_public || auth()->id() === $farmer->id || auth()->user()?->isAdmin(), 404);
        $batches = Batch::with('crop')->where('farmer_id', $id)->where('status', '!=', 'voided')->where('verification_status', '!=', 'rejected')->get();
        $sales = Transaction::where('seller_id', $id)->where('stage', 'farmer_to_collector')->where('is_voided', false)->get();
        $cropStats = $batches->groupBy('crop_id')->map(function ($lots) use ($sales) {
            $crop = $lots->first()->crop;
            $cropSales = $sales->whereIn('batch_id', $lots->pluck('id'))->where('unit', $crop->unit);

            return ['crop' => $crop, 'lots' => $lots->count(), 'recorded_quantity' => $lots->where('unit', $crop->unit)->sum('initial_quantity'), 'sold_quantity' => $cropSales->sum('quantity'), 'sale_count' => $cropSales->count()];
        });
        $grownCrops = Crop::whereIn('id', $farmer->grown_crop_ids ?? [])->get();
        $ratings = FarmerRating::where('farmer_id', $id)->where('status', 'approved')->latest()->paginate(10);
        $ratingSummary = FarmerRating::where('farmer_id', $id)->where('status', 'approved')->selectRaw('reviewer_group, COUNT(*) as total, AVG(score) as average')->groupBy('reviewer_group')->get();

        return view('public.farmer-profile', compact('farmer', 'cropStats', 'grownCrops', 'ratings', 'ratingSummary'));
    }

    public function enrollForm()
    {
        $crops = Crop::where('is_active', true)->orderBy('name')->get();
        $districts = District::orderBy('name')->get();

        return view('public.farmer-enroll', compact('crops', 'districts'));
    }

    public function enroll(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:150'], 'phone' => ['required', 'regex:/^\+?[0-9]{10,15}$/'], 'district_id' => ['required', 'exists:districts,id'], 'grown_crop_ids' => ['required', 'array', 'min:1', 'max:30'], 'grown_crop_ids.*' => ['integer', 'distinct', 'exists:crops,id'], 'cultivation_notes' => ['nullable', 'string', 'max:1000'], 'public_profile_consent' => ['accepted']]);
        unset($data['public_profile_consent']);
        $entry = FarmerEnrollment::create($data + ['tracking_code' => (string) Str::uuid()]);
        AuditLoggerService::log(action: 'FARMER_ENROLLMENT_REQUESTED', targetType: 'FarmerEnrollment', targetId: $entry->id);

        return redirect()->route('farmers.enroll')->with('success', 'Enrollment submitted. Reference: '.$entry->tracking_code.'. An administrator will review it before publishing your profile. No farmer login is required.');
    }

    public function rate(Request $request, int $id)
    {
        $farmer = User::where('role', 'farmer')->where('account_status', 'active')->where('farmer_profile_public', true)->findOrFail($id);
        $data = $request->validate(['score' => ['required', 'integer', 'between:1,5'], 'comment' => ['required', 'string', 'min:10', 'max:1000']]);
        $reviewer = $request->user();
        abort_unless($reviewer->id !== $farmer->id && ($reviewer->isVendor() || $reviewer->role === 'consumer'), 403);
        DB::transaction(function () use ($reviewer, $farmer, $data) {
            User::whereKey($reviewer->id)->lockForUpdate()->firstOrFail();
            $rating = FarmerRating::firstOrNew(['farmer_id' => $farmer->id, 'reviewer_id' => $reviewer->id]);
            $rating->fill($data + ['reviewer_group' => $reviewer->isVendor() ? 'middlemen' : 'public', 'status' => 'pending', 'moderated_by_user_id' => null, 'moderation_reason' => null])->save();
            AuditLoggerService::log(action: 'FARMER_RATING_SUBMITTED', targetType: 'FarmerRating', targetId: $rating->id, userId: $reviewer->id);
        });

        return back()->with('success', 'Rating submitted for moderation. Each account has one rating per farmer; editing sends it for review again.');
    }

    public function reviewQueue()
    {
        $enrollments = FarmerEnrollment::with('district')->where('status', 'pending')->latest()->paginate(15, ['*'], 'enrollment_page');
        $ratings = FarmerRating::with('farmer')->where('status', 'pending')->latest()->paginate(15, ['*'], 'rating_page');

        return view('admin.farmer-review', compact('enrollments', 'ratings'));
    }

    public function reviewEnrollment(Request $request, int $id)
    {
        $data = $request->validate(['decision' => ['required', 'in:approved,rejected'], 'reason' => ['required', 'string', 'min:5', 'max:500']]);
        $farmer = DB::transaction(function () use ($data, $id) {
            $entry = FarmerEnrollment::lockForUpdate()->findOrFail($id);
            if ($entry->status !== 'pending') {
                return $entry->farmer_id;
            }
            $farmerId = null;
            if ($data['decision'] === 'approved') {
                $farmer = User::create(['name' => $entry->name, 'phone' => $entry->phone, 'email' => 'enrolled.'.Str::uuid().'@profiles.krishibyte.invalid', 'password' => Str::random(64), 'role' => 'farmer', 'account_status' => 'active', 'district_id' => $entry->district_id, 'farmer_profile_public' => true, 'grown_crop_ids' => $entry->grown_crop_ids, 'cultivation_notes' => $entry->cultivation_notes]);
                $farmerId = $farmer->id;
            }
            $entry->update(['status' => $data['decision'], 'farmer_id' => $farmerId, 'reviewed_by_user_id' => auth()->id(), 'review_reason' => $data['reason']]);
            AuditLoggerService::log(action: 'FARMER_ENROLLMENT_'.strtoupper($data['decision']), targetType: 'FarmerEnrollment', targetId: $id, notes: $data['reason'], after: ['farmer_id' => $farmerId]);

            return $farmerId;
        });

        return back()->with('success', 'Enrollment reviewed.'.($farmer ? ' Farmer profile #'.$farmer.' generated.' : ''));
    }

    public function moderateRating(Request $request, int $id)
    {
        $data = $request->validate(['decision' => ['required', 'in:approved,rejected'], 'reason' => ['required', 'string', 'min:5', 'max:500']]);
        DB::transaction(function () use ($id, $data) {
            $rating = FarmerRating::lockForUpdate()->findOrFail($id);
            abort_if(in_array(auth()->id(), [$rating->reviewer_id, $rating->farmer_id], true), 403);
            if ($rating->status !== 'pending') {
                return;
            }
            $rating->update(['status' => $data['decision'], 'moderated_by_user_id' => auth()->id(), 'moderation_reason' => $data['reason']]);
            AuditLoggerService::log(action: 'FARMER_RATING_'.strtoupper($data['decision']), targetType: 'FarmerRating', targetId: $id, notes: $data['reason']);
        });

        return back()->with('success', 'Rating reviewed.');
    }

    public function purchaseForm(Request $request)
    {
        $request->validate(['farmer_id' => ['nullable', 'exists:users,id'], 'batch_id' => ['nullable', 'exists:batches,id']]);
        $farmers = User::where('role', 'farmer')->where('account_status', 'active')->where('farmer_profile_public', true)->orderBy('name')->get();
        $crops = Crop::where('is_active', true)->where('unit_needs_review', false)->orderBy('name')->get();
        $districts = District::orderBy('name')->get();
        $batch = $request->filled('batch_id') ? Batch::where('current_stage', 'farm_gate')->where('status', 'active')->findOrFail($request->input('batch_id')) : null;

        return view('vendor.farm-purchase', compact('farmers', 'crops', 'districts', 'batch'));
    }

    public function purchase(Request $request, FarmPurchaseService $service)
    {
        $data = $request->validate(['farmer_id' => ['required', 'integer', 'exists:users,id'], 'crop_id' => ['required', 'integer', 'exists:crops,id'], 'batch_id' => ['nullable', 'integer', 'exists:batches,id'], 'quantity' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:1000000'], 'price_per_unit' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:100000'], 'district_id' => ['required', 'integer', 'exists:districts,id'], 'harvest_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.now('Asia/Dhaka')->toDateString()], 'market_name' => ['nullable', 'string', 'max:255'], 'witness_name' => ['required', 'string', 'max:150'], 'agreement_read_aloud' => ['accepted'], 'request_key' => ['required', 'uuid'], 'agreement_photo' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048']]);
        unset($data['agreement_photo']);
        $evidence = EvidenceService::store($request->file('agreement_photo'));
        try {
            $purchase = $service->record($data, $evidence, auth()->id());
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($evidence['evidence_path']);
            if ($e instanceof \InvalidArgumentException) {
                return back()->withInput()->with('error', $e->getMessage());
            }
            throw $e;
        }
        if ($purchase->evidence_path !== $evidence['evidence_path']) {
            Storage::disk('local')->delete($evidence['evidence_path']);
        }

        return redirect()->route('transactions.receipt', $purchase->transaction_id)->with('success', 'Farmer purchase recorded with a private agreement. No farmer login needed. Payment is not verified.');
    }

    public function agreement(int $id)
    {
        $purchase = FarmPurchase::findOrFail($id);
        abort_unless(auth()->user()->isAuthority() || in_array(auth()->id(), [$purchase->collector_id, $purchase->farmer_id], true), 403);
        abort_unless(Storage::disk('local')->exists($purchase->evidence_path), 404);

        return Storage::disk('local')->response($purchase->evidence_path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
