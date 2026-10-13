@extends('layouts.app')
@section('title', 'Markets & crop traceability — KrishiByte')
@section('content')
<h1 class="h2">Markets & crop traceability</h1>
<p class="text-muted">Choose the destination district and market to find shops, goods and their journeys from other districts.</p>
<form method="GET" class="card card-body mb-4">
<div class="row g-3"><div class="col-md-4"><label for="destination-district" class="form-label">Destination district</label><select id="destination-district" name="district_id" class="form-select" onchange="this.form.marketplace_id.value=''; this.form.submit()"><option value="">All districts</option>@foreach($districts as $district)<option value="{{ $district->id }}" @selected($districtId == $district->id)>{{ $district->name }}</option>@endforeach</select></div>
<div class="col-md-5"><label for="destination-market" class="form-label">Marketplace</label><select id="destination-market" name="marketplace_id" class="form-select" @disabled(!$districtId)><option value="">All markets in this district</option>@foreach($markets as $market)<option value="{{ $market->id }}" @selected($marketId == $market->id)>{{ $market->name }}{{ $market->is_demo ? ' · Demo' : '' }}</option>@endforeach</select></div><div class="col-md-3 d-flex align-items-end"><button class="btn btn-primary w-100">Show goods & shops</button></div></div>
<noscript><p class="mt-2 mb-0">Select a district and submit first, then choose its market and submit again.</p></noscript>
</form>
<form method="GET" class="d-flex gap-2 mb-4"><label for="batch-code" class="visually-hidden">Batch code</label><input id="batch-code" name="code" class="form-control" maxlength="100" placeholder="Or enter a batch code" required><button class="btn btn-outline-primary">Trace batch</button></form>
@if($districtId)
<h2 class="h5">Registered retail shops ({{ $shops->count() }})</h2><div class="row g-3 mb-4">@forelse($shops as $shop)<div class="col-md-4"><div class="card card-body h-100"><strong>{{ $shop->shop_name ?: $shop->name }} <x-verified-badge :user="$shop" /></strong><small class="text-muted">{{ $shop->marketplace->name }} · {{ $shop->marketplace->district->name }}</small>@if($shop->marketplace->is_demo)<span class="badge bg-warning text-dark align-self-start mt-2">Synthetic demo shop</span>@endif</div></div>@empty<p>No approved shops registered in this market yet.</p>@endforelse</div>
@endif
<h2 class="h5 mb-3">{{ $districtId ? 'Goods stocked in the selected destination' : 'Recent crop journeys — select a market to browse its shops' }}</h2>
<div class="row g-3 mb-4">
@forelse($recentBatches as $batch)
@php($quote = $retailPrices->get($batch->id))
<div class="col-md-6"><article class="card card-body h-100">
    <div class="d-flex justify-content-between gap-2 mb-2"><h3 class="h5 mb-0">{{ $batch->crop->name }}</h3>@if(str_starts_with($batch->batch_code,'KB-MARKET-DEMO-'))<span class="badge bg-warning text-dark align-self-start">Demo</span>@endif</div>
    <small class="text-muted mb-3" style="overflow-wrap:anywhere">{{ $batch->batch_code }}</small>
    <div class="mb-3"><span class="small text-muted d-block">Shop asking price</span><strong class="fs-4 text-success">{{ $quote ? 'BDT '.number_format($quote->price_per_unit,2).' / '.$quote->unit : 'Not posted' }}</strong>@if($quote)<small class="text-muted d-block">{{ $quote->created_at->timezone('Asia/Dhaka')->format('d M Y') }}{{ $quote->is_demo ? ' · Demo quote' : '' }}</small>@endif</div>
    <dl class="mb-3"><dt class="small text-muted">Origin district</dt><dd>{{ $batch->originDistrict->name }}</dd><dt class="small text-muted">Retail shop / market</dt><dd>{{ $batch->currentCustodian?->isRetail() ? ($batch->currentCustodian->shop_name ?: $batch->currentCustodian->name) : 'En route to a retail shop' }}<small class="text-muted d-block">{{ $batch->currentCustodian?->marketplace?->name }}</small></dd><dt class="small text-muted">Recorded stock quantity</dt><dd class="mb-0">{{ number_format($batch->available_quantity,2) }} {{ $batch->unit }}</dd></dl>
    <a class="btn btn-outline-primary mt-auto align-self-start" href="{{ route('public.traceability.show',$batch->batch_code) }}">View journey</a>
</article></div>
@empty<div class="col-12"><p class="card card-body text-muted">No available traced goods in this market yet. Try another marketplace.</p></div>@endforelse
</div>{{ $recentBatches->links() }}
<p class="small text-muted">Prices are shop-entered quotes, not verified customer payments. Stock quantities reflect recorded receipts; customer sales are not logged. Marketplace demo names, shops, lots and prices are illustrative.</p>
@endsection
