@extends('layouts.app')
@section('title','Farmer Profile')
@section('content')
<a href="{{ route('farmers.access') }}" class="btn btn-outline-primary mb-3">Farmer private access, corrections & consent support</a>
<div class="card card-body p-4 mb-4"><span class="small text-muted">FARMER PROFILE #{{ $farmer->id }}</span><h1 class="fw-bold">{{ $farmer->name }} <x-verified-badge :user="$farmer" /></h1><p>{{ $farmer->district?->name ?? 'District not provided' }}</p><p class="mb-2">{{ $farmer->cultivation_notes }}</p><div><strong>Declared crops grown:</strong> {{ $grownCrops->pluck('name')->join(', ') ?: 'Not yet declared' }}</div><small class="text-muted">Growing details are farmer declarations. Recorded lots are not a measurement of total farm production. Contact details and handwritten agreements are private.</small>
@if(auth()->user()?->isCollector())
<a class="btn btn-primary align-self-start mt-3" href="{{ route('vendor.farm-purchase',['farmer_id'=>$farmer->id]) }}">Record a purchase from this farmer</a>
@endif
</div>
<div class="card mb-4"><div class="card-header"><h2 class="h5 mb-0">Crops & recorded sales</h2></div><div class="table-responsive"><table class="table"><thead><tr><th>Crop / unit</th><th>Harvest lots</th><th>Recorded quantity</th><th>Sold quantity</th><th>Sales</th></tr></thead><tbody>
@forelse($cropStats as $row)
<tr><td>{{ $row['crop']->name }} / {{ $row['unit'] }}</td><td>{{ $row['lots'] }}</td><td>{{ number_format($row['recorded_quantity'],2) }}</td><td>{{ number_format($row['sold_quantity'],2) }}</td><td>{{ $row['sale_count'] }}</td></tr>
@empty
<tr><td colspan="5">No recorded crop lots yet. A collector can create the first lot when purchasing.</td></tr>
@endforelse
</tbody></table></div><div class="card-footer small text-muted">Quantities remain separate by crop and unit. Historical imports, collector attestations and accepted handoffs are recorded history; they do not prove payment or total production.</div></div>
<div class="row g-3 mb-4"><div class="col-lg-7"><div class="card card-body"><h2 class="h4">Rating report</h2>
@forelse($ratingSummary as $summary)
<p><strong>{{ $summary->reviewer_group === 'middlemen' ? 'Middlemen & shops' : 'General public' }}: {{ number_format($summary->average,1) }} / 5</strong> · {{ $summary->total }} approved ratings</p>
@empty
<p class="text-muted">No approved ratings yet.</p>
@endforelse
<p class="small text-muted">Moderated account ratings reflect opinions. Public ratings do not establish a purchase, and one account is not proof of one person.</p>
@foreach($ratings as $rating)
<div class="border-top py-3"><span class="badge bg-warning text-dark">{{ $rating->score }} / 5</span> <span class="small text-muted">{{ $rating->reviewer_group === 'middlemen' ? 'Middleman / shop' : 'Public member' }} · {{ $rating->created_at->timezone('Asia/Dhaka')->format('d M Y') }}</span><p class="mb-0 mt-2">{{ $rating->comment }}</p></div>
@endforeach
{{ $ratings->links() }}</div></div><div class="col-lg-5"><div class="card card-body"><h2 class="h4">Share your experience</h2>
@if(auth()->check() && (auth()->user()->isVendor() || auth()->user()->role === 'consumer'))
<form method="POST" action="{{ route('farmers.rate',$farmer->id) }}">@csrf<label for="score" class="form-label">Rating</label><select id="score" name="score" class="form-select mb-3" required>@foreach([5,4,3,2,1] as $score)<option value="{{ $score }}">{{ $score }} / 5</option>@endforeach</select><label for="comment" class="form-label">Your experience</label><textarea id="comment" name="comment" class="form-control mb-3" rows="4" minlength="10" maxlength="1000" required></textarea><button class="btn btn-primary">Submit for review</button><p class="small text-muted mt-2 mb-0">One rating per account per farmer. Updates replace your rating and require moderation again.</p></form>
@elseif(auth()->check())
<p class="text-muted">Ratings are available to middlemen, shops and consumer accounts. Farmers cannot rate themselves; staff review ratings.</p>
@else
<p>Middlemen and members of the public can rate after signing in.</p><a href="{{ route('login') }}" class="btn btn-outline-primary">Sign in to rate</a>
@endif
</div></div></div>
@endsection
