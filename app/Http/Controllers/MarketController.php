<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\District;
use App\Models\Marketplace;
use App\Models\RetailPrice;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AuditLoggerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MarketController extends Controller
{
    public function admin()
    {
        $districts = District::orderBy('name')->get();
        $markets = Marketplace::with('district')->orderBy('district_id')->orderBy('name')->get();

        return view('admin.marketplaces', compact('districts', 'markets'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['district_id' => ['required', 'exists:districts,id'], 'name' => ['required', 'string', 'max:150', Rule::unique('marketplaces')->where('district_id', $request->input('district_id'))]]);
        $market = Marketplace::create($data + ['created_by' => auth()->id()]);
        AuditLoggerService::log(action: 'MARKETPLACE_CREATED', targetType: 'Marketplace', targetId: $market->id, after: $data);

        return back()->with('success', 'Marketplace added to the district.');
    }

    public function prices()
    {
        $retailer = auth()->user();
        $batches = Batch::with(['crop', 'originDistrict'])->where('current_custodian_id', $retailer->id)->where('status', 'active')->where('verification_status', '!=', 'rejected')->get();
        $history = RetailPrice::with('batch.crop')->where('retailer_id', $retailer->id)->latest()->paginate(15)->withQueryString();
        $receipts = Transaction::with(['batch.crop', 'seller'])->where('buyer_id', $retailer->id)
            ->latest('transaction_date')->latest('id')->paginate(10, ['*'], 'receipts_page')->withQueryString();

        return view('vendor.retail-prices', compact('retailer', 'batches', 'history', 'receipts'));
    }

    public function recordPrice(Request $request)
    {
        $data = $request->validate(['batch_id' => ['required', 'integer', 'exists:batches,id'], 'price_per_unit' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:100000'], 'request_key' => ['required', 'uuid']]);
        DB::transaction(function () use ($data) {
            $retailer = User::lockForUpdate()->findOrFail(auth()->id());
            abort_unless($retailer->isRetail() && $retailer->trading_approved && $retailer->account_status === 'active', 403);
            $batch = Batch::lockForUpdate()->findOrFail($data['batch_id']);
            $market = Marketplace::where('is_active', true)->findOrFail($retailer->marketplace_id);
            abort_unless($market->district_id === $retailer->district_id, 422, 'Shop market must match its registered district.');
            $prior = RetailPrice::where('retailer_id', $retailer->id)->where('request_key', $data['request_key'])->first();
            if ($prior) {
                abort_unless($prior->batch_id === $batch->id && bccomp((string) $prior->price_per_unit, (string) $data['price_per_unit'], 2) === 0, 409, 'Request key already used with different terms.');

                return;
            }
            abort_unless($batch->current_custodian_id === $retailer->id && $batch->status === 'active' && $batch->verification_status !== 'rejected', 403, 'Only your available retail stock may be priced.');
            abort_if($batch->unit_needs_review, 422);
            $price = RetailPrice::create($data + ['retailer_id' => $retailer->id, 'marketplace_id' => $market->id, 'unit' => $batch->unit, 'is_demo' => $market->is_demo || str_starts_with($batch->batch_code, 'KB-MARKET-DEMO-')]);
            AuditLoggerService::log(action: 'RETAIL_PRICE_RECORDED', targetType: 'RetailPrice', targetId: $price->id, after: ['batch_id' => $batch->id, 'price' => $price->price_per_unit, 'unit' => $price->unit, 'marketplace_id' => $market->id]);
        });

        return back()->with('success', 'Shop selling price recorded. This records a price quote, without a customer transfer or stock deduction.');
    }
}
