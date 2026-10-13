<?php

namespace App\Http\Controllers;

use App\Models\FarmPurchase;
use App\Models\User;
use App\Services\AuditLoggerService;
use App\Services\CorrectionService;
use App\Services\FarmerAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FarmerAccessController extends Controller
{
    public function index()
    {
        $farmerId = FarmerAccess::id();
        $farmer = $farmerId ? User::where('role', 'farmer')->where('account_status', 'active')->find($farmerId) : null;
        $purchases = $farmer ? FarmPurchase::with('batch.crop')->where('farmer_id', $farmer->id)->latest()->get() : collect();

        return response()->view('public.farmer-access', compact('farmer', 'purchases'))->header('Cache-Control', 'private, no-store');
    }

    public function requestAccess(Request $request)
    {
        $data = $request->validate(['farmer_id' => ['required', 'integer', 'exists:users,id'], 'name' => ['required', 'string', 'max:150'], 'phone' => ['required', 'regex:/^\+?[0-9]{10,15}$/'], 'reason' => ['required', 'string', 'min:10', 'max:1000']]);
        abort_unless(User::findOrFail($data['farmer_id'])->isFarmer(), 422);
        DB::table('farmer_access_requests')->insert($data + ['status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);

        return back()->with('success', 'Private support request received. An administrator must verify your identity and contact you before granting access. No access was granted by this form.');
    }

    public function review(Request $request, int $id)
    {
        $data = $request->validate(['decision' => ['required', 'in:granted,rejected'], 'verification_method' => ['required', 'string', 'min:10', 'max:1000']]);
        $token = DB::transaction(function () use ($id, $data) {
            $entry = DB::table('farmer_access_requests')->where('id', $id)->lockForUpdate()->first();
            abort_unless($entry, 404);
            abort_if($entry->farmer_id === auth()->id(), 403);
            abort_unless($entry->status === 'pending', 409);
            $token = $data['decision'] === 'granted' ? Str::random(64) : null;
            DB::table('farmer_access_requests')->where('id', $id)->update(['status' => $data['decision'], 'token_hash' => $token ? hash('sha256', $token) : null, 'expires_at' => $token ? now()->addHours(24) : null, 'verification_method' => $data['verification_method'], 'reviewed_by' => auth()->id(), 'updated_at' => now()]);
            AuditLoggerService::log(action: 'FARMER_ACCESS_'.strtoupper($data['decision']), targetType: 'FarmerAccessRequest', targetId: $id, notes: $data['verification_method']);

            return $token;
        });

        return back()->with('success', $token ? 'Deliver this one-time access code privately to the independently verified farmer. It expires in 24 hours: '.$token : 'Access request rejected.');
    }

    public function redeem(Request $request)
    {
        $data = $request->validate(['access_code' => ['required', 'string', 'size:64']]);
        $farmerId = DB::transaction(function () use ($data) {
            $entry = DB::table('farmer_access_requests')->where('token_hash', hash('sha256', $data['access_code']))->lockForUpdate()->first();
            abort_unless($entry && $entry->status === 'granted' && ! $entry->redeemed_at && now()->lt($entry->expires_at), 422, 'Invalid, expired or already used access code.');
            abort_unless(User::findOrFail($entry->farmer_id)->account_status === 'active', 403);
            DB::table('farmer_access_requests')->where('id', $entry->id)->update(['redeemed_at' => now(), 'updated_at' => now()]);
            AuditLoggerService::log(action: 'FARMER_ACCESS_REDEEMED', targetType: 'FarmerAccessRequest', targetId: $entry->id, userId: $entry->farmer_id);

            return $entry->farmer_id;
        });
        $request->session()->regenerate();
        $request->session()->put(['farmer_access_id' => $farmerId, 'farmer_access_expires' => time() + 3600]);

        return redirect()->route('farmers.access')->with('success', 'Private farmer access granted for one hour. This does not sign you in as a trader.');
    }

    public function withdraw(Request $request)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        $id = FarmerAccess::id();
        abort_unless($id, 403);
        DB::transaction(function () use ($id, $data) {
            $farmer = User::where('role', 'farmer')->where('account_status', 'active')->lockForUpdate()->findOrFail($id);
            $farmer->update(['farmer_profile_public' => false, 'publication_withdrawn_at' => now()]);
            AuditLoggerService::log(action: 'FARMER_PUBLICATION_WITHDRAWN', targetType: 'User', targetId: $id, notes: $data['reason'], userId: $id);
        });

        return back()->with('success', 'Public directory/profile publication withdrawn. Public batch traces now hide your name. Financial records and private agreements remain retained.');
    }

    public function correction(Request $request, int $id, CorrectionService $service)
    {
        $farmerId = FarmerAccess::id();
        abort_unless($farmerId, 403);
        $data = $request->validate(['request_key' => ['required', 'uuid'], 'reason' => ['required', 'string', 'min:5', 'max:1000']]);
        try {
            $service->request($id, $farmerId, $data['request_key'], $data['reason']);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Correction requested. The affected collector must accept before custody changes.');
    }
}
