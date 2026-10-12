<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\District;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BatchHandoffService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class VendorController extends Controller
{
    public function __construct(
        protected BatchHandoffService $handoffService
    ) {}

    public function dashboard()
    {
        $user = Auth::user();

        // Batches currently in this vendor's custody
        $batchesInCustody = Batch::with(['crop', 'originDistrict', 'transactions' => fn($q) => $q->where('is_voided', false)])
            ->where('current_custodian_id', $user->id)
            ->where('status', 'active')
            ->get();

        // Vendor's transaction history (both bought and sold)
        $transactions = Transaction::with(['batch.crop', 'seller', 'buyer', 'district', 'anomalyFlags'])
            ->where(function ($q) use ($user) {
                $q->where('seller_id', $user->id)->orWhere('buyer_id', $user->id);
            })
            ->latest('transaction_date')
            ->paginate(15);

        // Potential buyers based on role
        $eligibleBuyers = match ($user->role) {
            'collector' => User::whereIn('role', ['wholesale', 'retail'])->where('id', '!=', $user->id)->get(),
            'wholesale' => User::whereIn('role', ['retail', 'wholesale'])->where('id', '!=', $user->id)->get(),
            'retail' => User::where('role', 'consumer')->get(),
            default => User::where('id', '!=', $user->id)->get(),
        };

        $districts = District::orderBy('name')->get();

        $stats = [
            'in_custody_count' => $batchesInCustody->count(),
            'total_sales' => Transaction::where('seller_id', $user->id)->where('is_voided', false)->count(),
            'total_purchases' => Transaction::where('buyer_id', $user->id)->where('is_voided', false)->count(),
            'flagged_alerts' => Transaction::where('seller_id', $user->id)->where('validation_status', 'flagged')->count(),
        ];

        return view('vendor.dashboard', compact('user', 'batchesInCustody', 'transactions', 'eligibleBuyers', 'districts', 'stats'));
    }

    public function storeHandoff(Request $request)
    {
        $validated = $request->validate([
            'batch_id' => ['required', 'exists:batches,id'],
            'buyer_id' => ['required', 'exists:users,id'],
            'stage' => ['required', 'in:farmer_to_collector,collector_to_wholesale,wholesale_to_retail,retail_sale'],
            'quantity' => ['required', 'numeric', 'min:0.1'],
            'price_per_unit' => ['required', 'numeric', 'min:0.01'],
            'district_id' => ['required', 'exists:districts,id'],
            'market_name' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:20,27'],
            'longitude' => ['nullable', 'numeric', 'between:88,93'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $tx = $this->handoffService->recordHandoff([
                'batch_id' => (int) $validated['batch_id'],
                'seller_id' => Auth::id(),
                'buyer_id' => (int) $validated['buyer_id'],
                'stage' => $validated['stage'],
                'quantity' => (float) $validated['quantity'],
                'price_per_unit' => (float) $validated['price_per_unit'],
                'district_id' => (int) $validated['district_id'],
                'market_name' => $validated['market_name'] ?? null,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'note' => $validated['note'] ?? null,
                'recorded_by_user_id' => Auth::id(),
            ]);

            $flagNotice = ($tx->validation_status === 'flagged')
                ? " Note: This transaction triggered an anomaly review flag due to markup rules."
                : "";

            return redirect()->route('vendor.dashboard')
                ->with('success', "Custody handoff completed successfully! Transaction #{$tx->id} recorded.{$flagNotice}");
        } catch (InvalidArgumentException $e) {
            return redirect()->route('vendor.dashboard')
                ->with('error', "Handoff rejected: " . $e->getMessage());
        }
    }

    public function voidTransaction(Request $request, int $id)
    {
        $validated = $request->validate([
            'void_reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        try {
            $tx = Transaction::findOrFail($id);
            if ($tx->recorded_by_user_id !== Auth::id() && !Auth::user()->isAdmin()) {
                abort(403, 'You are not authorized to void this transaction.');
            }

            $this->handoffService->voidTransaction($id, Auth::id(), $validated['void_reason']);

            return redirect()->route('vendor.dashboard')
                ->with('success', "Transaction #{$id} has been voided and previous custody restored.");
        } catch (InvalidArgumentException $e) {
            return redirect()->route('vendor.dashboard')
                ->with('error', "Void failed: " . $e->getMessage());
        }
    }
}
