@extends('layouts.app')
@section('title','Shop selling prices')
@section('content')
<h1 class="h3">Shop selling prices</h1><p>{{ $retailer->shop_name ?: $retailer->name }} · {{ $retailer->marketplace?->name ?: 'Market registration required' }}</p><p>Record the price you charge for produce in your shop. There is no customer handoff. Quotes do not prove a purchase, payment, or quantity sold.</p>
<p class="small text-muted">Recorded quantities reflect accepted deliveries and retained legacy adjustments. Individual customer sales are not logged or deducted.</p>
<a href="{{ route('handoffs.index') }}" class="btn btn-outline-primary mb-3">Review incoming goods</a>
<a href="{{ route('public.traceability', ['district_id'=>$retailer->district_id, 'marketplace_id'=>$retailer->marketplace_id]) }}" class="btn btn-outline-primary mb-3">View my market</a>
<section class="card mb-4" aria-labelledby="received-goods-heading">
    <div class="card-body"><h2 id="received-goods-heading" class="h5">Received goods</h2>
    <p class="small text-muted mb-0">Accepted deliveries appear as Received. Older unconfirmed records are labelled separately; voided receipts do not count as received stock. Pending deliveries stay in Review incoming goods until accepted.</p></div>
    <div class="table-responsive" tabindex="0" role="region" aria-label="Received goods history">
        <table class="table mb-0"><thead><tr><th>Received at</th><th>Batch / crop</th><th>From</th><th>Quantity</th><th>Acquisition price</th><th>Movement status</th></tr></thead><tbody>
        @forelse($receipts as $receipt)
            <tr><td>{{ $receipt->transaction_date->timezone('Asia/Dhaka')->format('d M Y H:i') }}</td>
            <td><a href="{{ route('public.traceability.show', $receipt->batch->batch_code) }}">{{ $receipt->batch->batch_code }}</a> · {{ $receipt->batch->crop->name }}</td>
            <td>{{ $receipt->seller->name }}</td><td>{{ number_format($receipt->quantity, 2) }} {{ $receipt->unit }}</td>
            <td>BDT {{ number_format($receipt->price_per_unit, 2) }} / {{ $receipt->unit }}</td>
            <td><x-movement-status :transaction="$receipt" :participant="$retailer" /></td></tr>
        @empty<tr><td colspan="6">No received goods recorded. Accept an incoming delivery first.</td></tr>@endforelse
        </tbody></table>
    </div><div class="card-body">{{ $receipts->links() }}</div>
</section>
<div class="card card-body mb-4"><h2 class="h5">Your purchased stock</h2>@forelse($batches as $batch)<div class="border-top py-2"><a href="{{ route('public.traceability.show',$batch->batch_code) }}">{{ $batch->batch_code }}</a> · {{ $batch->crop->name }} · {{ number_format($batch->available_quantity,2) }} {{ $batch->unit }} · acquired at BDT {{ number_format($batch->acquisition_price,2) }} / {{ $batch->unit }}</div>@empty<p>No available stock. Accept an incoming delivery first.</p>@endforelse</div>
@if($retailer->marketplace_id && $retailer->trading_approved)
<form class="card card-body mb-4" method="POST" action="{{ route('retail.prices.store') }}">@csrf<input type="hidden" name="request_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}"><label for="priced-batch">Your stocked batch</label><select id="priced-batch" name="batch_id" class="form-select mb-3" required>@foreach($batches as $batch)<option value="{{ $batch->id }}">{{ $batch->batch_code }} · {{ $batch->crop->name }} · from {{ $batch->originDistrict->name }} · BDT / {{ $batch->unit }}</option>@endforeach</select><label for="shop-price">Selling price per batch unit (BDT)</label><input id="shop-price" name="price_per_unit" type="number" step="0.01" min="0.01" max="100000" class="form-control mb-3" required><button class="btn btn-primary" @disabled($batches->isEmpty())>Record selling price</button></form>
@else<div class="alert alert-info">Complete district/market registration and obtain administrator trading approval first.</div>@endif
<div class="table-responsive"><table class="table"><tr><th>Batch / crop</th><th>Price</th><th>Recorded at</th></tr>@foreach($history as $price)<tr><td><a href="{{ route('public.traceability.show',$price->batch->batch_code) }}">{{ $price->batch->batch_code }}</a> · {{ $price->batch->crop->name }}</td><td>BDT {{ $price->price_per_unit }} / {{ $price->unit }} {{ $price->is_demo ? '· Illustrative demo' : '' }}</td><td>{{ $price->created_at->timezone('Asia/Dhaka')->format('d M Y H:i') }}</td></tr>@endforeach</table></div>{{ $history->links() }}
@endsection
