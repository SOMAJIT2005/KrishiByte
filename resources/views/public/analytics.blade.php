@extends('layouts.app')
@section('title', 'Public Price Analytics')
@section('content')
<div class="mb-4"><span class="badge bg-success-subtle text-success mb-2">PUBLIC MARKET INSIGHTS</span><h1 class="fw-bold">Follow every crop’s price</h1><p class="text-muted">See rising and falling indicative retail prices, and the samples behind each comparison.</p></div>
<form class="card card-body mb-4" method="GET">
<div class="row g-3 align-items-end">
<div class="col-lg-3"><label for="district_id" class="form-label">District</label><select id="district_id" name="district_id" class="form-select"><option value="">All monitored districts</option>@foreach($districts as $district)<option value="{{ $district->id }}" @selected($districtId == $district->id)>{{ $district->name }}</option>@endforeach
</select></div>
<div class="col-lg-3"><label for="source" class="form-label">Source</label><select id="source" name="source" class="form-select">@foreach(['survey'=>'Reviewed market surveys','reports'=>'Reviewed citizen reports','demo'=>'Illustrative demo samples'] as $key=>$label)<option value="{{ $key }}" @selected($source === $key)>{{ $label }}</option>@endforeach
</select></div>
<div class="col-lg-2"><label for="days" class="form-label">Window</label><select id="days" name="days" class="form-select">@foreach([7,14,30] as $window)<option value="{{ $window }}" @selected($days === $window)>{{ $window }} days</option>@endforeach
</select></div>
<div class="col-lg-2"><label for="end_date" class="form-label">Through date</label><input id="end_date" name="end_date" type="date" class="form-control" value="{{ $endDate }}" max="{{ now('Asia/Dhaka')->toDateString() }}"></div>
<div class="col-lg-2"><button class="btn btn-primary w-100">Update insights</button></div>
<div class="col-12"><label for="crop_id" class="form-label">Product chart</label><select id="crop_id" name="crop_id" class="form-select">@foreach($crops as $crop)<option value="{{ $crop->id }}" @selected($selected && $selected['crop']->id === $crop->id)>{{ $crop->name }} ({{ $crop->unit }})</option>@endforeach
</select></div>
</div></form>
@if($source === 'demo')<div class="alert alert-warning"><strong>Illustrative demo data.</strong> These prices are synthetic and are not market evidence.</div>@elseif($source === 'reports')<div class="alert alert-warning">Citizen reports are a complaint sample and may not represent normal market prices.</div>
@endif


<div class="row g-3 mb-4">@foreach([['Rising products',$rising->count(),'danger'],['Falling products',$falling->count(),'success'],['Awaiting comparable history',$products->whereNull('change')->count(),'secondary']] as [$label,$count,$color])<div class="col-md-4"><div class="card card-body h-100"><div class="text-muted">{{ $label }}</div><strong class="fs-2 text-{{ $color }}">{{ $count }}</strong></div></div>@endforeach
</div>
<div class="card card-body mb-4"><h2 class="h5">What the data says</h2>
@if($rising->isNotEmpty())<p class="mb-2">Largest recorded increase: <strong>{{ $rising->first()['crop']->name }}, +{{ number_format($rising->first()['change'],2) }}%</strong>, between {{ $rising->first()['previous']['date'] }} and {{ $rising->first()['latest']['date'] }}.</p>
@endif


@if($falling->isNotEmpty())<p class="mb-2">Largest recorded decrease: <strong>{{ $falling->first()['crop']->name }}, {{ number_format($falling->first()['change'],2) }}%</strong>, between {{ $falling->first()['previous']['date'] }} and {{ $falling->first()['latest']['date'] }}.</p>
@endif


@if($products->whereNotNull('change')->isEmpty())<p>No product has two days with enough comparable samples in this window. Add reviewed daily observations to reveal price movement.</p>
@endif


<p class="small text-muted mb-0">Each daily reference needs at least 3 distinct samples in the product’s unit. We compare the latest two qualifying days, which may be separated by gaps. Changing markets or contributors can affect the comparison; these are observed changes, not forecasts or official price ceilings.</p></div>
@if($selected)
<div class="card card-body mb-4"><div class="d-flex justify-content-between flex-wrap gap-2"><h2 class="h4">{{ $selected['crop']->name }} price history</h2><span class="text-muted">BDT / {{ $selected['crop']->unit }}</span></div>
@if($selected['latest'] === null && $source !== 'demo')
<div class="alert alert-info mt-3 mb-0">This source has no qualifying daily prices for this product and window. Synthetic showcase prices are stored separately. <a class="alert-link" href="{{ route('public.analytics', ['source' => 'demo', 'crop_id' => $selected['crop']->id, 'district_id' => $districtId, 'days' => $days, 'end_date' => $endDate]) }}">Show illustrative demo prices</a>.</div>
@endif
<div class="row g-3 my-2">
@foreach(['Latest daily reference'=>$selected['latest'],'Lowest in this window'=>$selected['lowest'],'Highest in this window'=>$selected['highest']] as $label=>$reference)
<div class="col-md-4"><div class="border rounded p-3 h-100"><div class="small text-muted">{{ $label }}</div><strong class="fs-4">{{ $reference ? number_format($reference['indicative_price'],2).' BDT' : 'No reference' }}</strong><div class="small text-muted">{{ $reference['date'] ?? 'At least 3 samples needed' }}</div></div></div>
@endforeach
</div>
<div style="height:280px"><canvas id="publicPriceTrend" role="img" aria-label="Daily indicative price chart; exact values follow in the daily samples table"></canvas></div>
<details class="mt-3"><summary>Daily samples and exact prices</summary><div class="table-responsive"><table class="table"><thead><tr><th>Date</th><th>Price (BDT)</th><th>Samples</th><th>Markets</th><th>Contributors</th></tr></thead><tbody>@foreach($selected['daily'] as $day)<tr><td>{{ $day['date'] }}</td><td>{{ $day['indicative_price'] !== null ? number_format($day['indicative_price'],2) : 'Insufficient samples' }}</td><td>{{ $day['sample_count'] }}</td><td>{{ $day['market_count'] }}</td><td>{{ $day['source_count'] }}</td></tr>@endforeach
</tbody></table></div></details></div>

@endif


<div class="card mb-4"><div class="card-header"><h2 class="h5 mb-0">All products at a glance</h2></div><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Product / unit</th><th>Latest reference</th><th>Movement</th><th>Compared dates</th><th>Latest sample coverage</th></tr></thead><tbody>
@forelse($products as $product)<tr><td><a href="{{ route('public.analytics',array_merge(request()->query(),['crop_id'=>$product['crop']->id])) }}">{{ $product['crop']->name }}</a><div class="small text-muted">BDT / {{ $product['crop']->unit }}</div></td><td>{{ $product['latest'] ? number_format($product['latest']['indicative_price'],2) : 'No reference' }}@if($product['latest'])<div class="small text-muted">{{ $product['latest']['date'] }}@if($product['latest']['date'] !== $endDate) · Earlier observation
@endif

</div>
@endif

</td><td><span class="badge {{ $product['trend'] === 'Rising' ? 'bg-danger-subtle text-danger' : ($product['trend'] === 'Falling' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary') }}">{{ $product['trend'] }}</span>@if($product['change'] !== null)<div>{{ $product['change'] > 0 ? '+' : '' }}{{ number_format($product['change'],2) }}%</div>
@endif

</td><td class="small">{{ $product['previous'] ? $product['previous']['date'].' → '.$product['latest']['date'] : 'Two qualifying days needed' }}</td><td class="small">{{ $product['latest'] ? $product['latest']['sample_count'].' samples · '.$product['latest']['market_count'].' markets · '.$product['latest']['source_count'].' contributors' : 'At least 3 daily samples needed' }}</td></tr>@empty<tr><td colspan="5">No active products enrolled yet.</td></tr>@endforelse

</tbody></table></div></div>
<p class="small text-muted">Calendar dates use Asia/Dhaka. Exact duplicate observations are removed. Daily references use a median for 3–4 samples and a 20% trimmed mean for 5 or more. Contributor counts do not prove independence or accuracy. Missing days remain gaps.</p>
@endsection
@push('scripts')
@if($selected)<script>
const trendDays = @json($selected['daily']->values());
if (typeof Chart !== 'undefined') new Chart(document.getElementById('publicPriceTrend'), {type:'line',data:{labels:trendDays.map(day=>day.date),datasets:[{label:'Indicative retail price (BDT / {{ $selected['crop']->unit }})',data:trendDays.map(day=>day.indicative_price),borderColor:'#0d6e4e',backgroundColor:'rgba(13,110,78,.08)',fill:true,spanGaps:false,tension:0,pointRadius:4}]},options:{responsive:true,maintainAspectRatio:false,scales:{y:{title:{display:true,text:'BDT / {{ $selected['crop']->unit }}'}},x:{ticks:{maxTicksLimit:7}}},plugins:{legend:{display:false}}}});
</script>
@endif


@endpush
