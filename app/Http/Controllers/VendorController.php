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

    public function dashboard(Request $request)
    {
        $request->validate(['buyer_search' => ['nullable', 'string', 'max:100']]);
        $user = Auth::user();

        // Batches currently in this vendor's custody
        $batchesInCustody = Batch::with(['crop', 'originDistrict', 'transactions' => fn ($q) => $q->where('is_voided', false), 'handoffProposals' => fn ($q) => $q->where('status', 'proposed')->where('expires_at', '>', now())])
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
        $roles = match ($user->role) {
            'farmer' => ['collector'],
            'collector', 'wholesale' => ['collector', 'wholesale', 'retail'],
            'retail' => ['consumer'],
            default => ['collector', 'wholesale', 'retail', 'consumer'],
        };
        $eligibleBuyers = User::whereIn('role', $roles)->where('account_status', 'active')->where('id', '!=', $user->id)->when($request->filled('buyer_search'), fn ($q) => $q->where('name', 'like', '%'.$request->string('buyer_search').'%'))->orderBy('name')->limit(50)->get();

        $districts = District::orderBy('name')->get();

        $collectionBatches = $user->isCollector()
            ? Batch::with(['crop', 'farmer', 'originDistrict'])->where('current_stage', 'farm_gate')
                ->where('status', 'active')->where('verification_status', '!=', 'rejected')
                ->whereHas('currentCustodian', fn ($q) => $q->where('role', 'farmer')->where('account_status', 'active'))
                ->latest()->paginate(10, ['*'], 'collection_page')
            : collect();

        $stats = [
            'in_custody_count' => $batchesInCustody->count(),
            'total_sales' => Transaction::where('seller_id', $user->id)->where('is_voided', false)->count(),
            'total_purchases' => Transaction::where('buyer_id', $user->id)->where('is_voided', false)->count(),
            'flagged_alerts' => Transaction::where('seller_id', $user->id)->where('validation_status', 'flagged')->count(),
        ];

        return view('vendor.dashboard', compact('user', 'batchesInCustody', 'transactions', 'eligibleBuyers', 'districts', 'stats', 'collectionBatches'));
    }

    public function storeCollection(Request $request)
    {
        abort_unless(Auth::user()->isCollector(), 403);
        $request->merge(['buyer_id' => Auth::id(), 'stage' => 'auto']);

        return $this->storeHandoff($request, true);
    }

    public function storeHandoff(Request $request, bool $collection = false)
    {
        $validated = $request->validate([
            'batch_id' => ['required', 'exists:batches,id'],
            'buyer_id' => [Auth::user()->isRetail() ? 'nullable' : 'required', 'exists:users,id'],
            'request_key' => ['required', 'uuid'],
            'stage' => ['required', 'in:auto,farmer_to_collector,collector_to_wholesale,wholesale_to_retail,retail_sale'],
            'quantity' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:1000000'],
            'price_per_unit' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:100000'],
            'district_id' => ['required', 'exists:districts,id'],
            'market_name' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:20,27'],
            'longitude' => ['nullable', 'numeric', 'between:88,93'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $sellerId = Auth::id();
            if ($collection) {
                $batch = Batch::with('currentCustodian')->findOrFail($validated['batch_id']);
                abort_unless($batch->currentCustodian->isFarmer(), 422, 'This batch is no longer at the farm gate.');
                $sellerId = $batch->current_custodian_id;
            }
            $terms = [
                'batch_id' => (int) $validated['batch_id'],
                'seller_id' => $sellerId,
                'buyer_id' => isset($validated['buyer_id']) ? (int) $validated['buyer_id'] : null,
                'stage' => $validated['stage'],
                'quantity' => (float) $validated['quantity'],
                'price_per_unit' => (float) $validated['price_per_unit'],
                'district_id' => (int) $validated['district_id'],
                'market_name' => $validated['market_name'] ?? null,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'note' => $validated['note'] ?? null,
                'recorded_by_user_id' => Auth::id(),
            ];
            if (Auth::user()->isRetail()) {
                $tx = $this->handoffService->recordRetailSale($terms, $validated['request_key']);
                $message = "Retail sale #{$tx->id} recorded. Payment is not verified.";
            } else {
                $proposal = $this->handoffService->propose($terms, $validated['request_key']);
                $message = "Proposal #{$proposal->id} submitted. Custody stays unchanged until the counterparty accepts.";
            }

            return redirect()->route(Auth::user()->isRetail() ? 'vendor.dashboard' : 'handoffs.index')->with('success', $message);
        } catch (InvalidArgumentException $e) {
            return redirect()->route(Auth::user()->isFarmer() ? 'farmer.handoff' : 'vendor.dashboard')
                ->with('error', 'Handoff rejected: '.$e->getMessage());
        }
    }

    public function voidTransaction(Request $request, int $id)
    {
        $validated = $request->validate([
            'void_reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        try {
            $tx = Transaction::findOrFail($id);
            if ($tx->recorded_by_user_id !== Auth::id() && ! Auth::user()->isAdmin()) {
                abort(403, 'You are not authorized to void this transaction.');
            }

            $this->handoffService->voidTransaction($id, Auth::id(), $validated['void_reason']);

            return redirect()->route(Auth::user()->isFarmer() ? 'farmer.handoff' : 'vendor.dashboard')
                ->with('success', "Transaction #{$id} has been voided and previous custody restored.");
        } catch (InvalidArgumentException $e) {
            return redirect()->route(Auth::user()->isFarmer() ? 'farmer.handoff' : 'vendor.dashboard')
                ->with('error', 'Void failed: '.$e->getMessage());
        }
    }
}
