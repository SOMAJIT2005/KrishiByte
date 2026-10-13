@extends('layouts.app')
@section('title','Farmer private access and support')
@section('content')
<h1 class="h3">Farmer private access and support</h1><p>ফার্মার সহায়তা · Email is not required. An administrator must verify identity/contact in person or through a supervised contact check before privately issuing a one-time code. A submitted phone number does not prove ownership.</p>
@if($farmer)
<div class="card card-body mb-3"><h2 class="h4">Private records for {{ $farmer->name }}</h2><p>Profile publication: {{ $farmer->farmer_profile_public ? 'Public' : 'Private' }}</p>
@foreach($purchases as $purchase)<p>{{ $purchase->batch->crop->name }} · {{ $purchase->terms['quantity'] }} {{ $purchase->terms['unit'] }} @ BDT {{ $purchase->terms['price_per_unit'] }} · <a href="{{ route('farmers.agreement',$purchase->id) }}">Your private agreement #{{ $purchase->id }}</a></p>@endforeach
@foreach($purchases->whereNotNull('transaction_id') as $purchase)
<details class="mb-3"><summary>Request correction for purchase #{{ $purchase->id }}</summary><form method="POST" action="{{ route('farmers.access.correction',$purchase->transaction_id) }}">@csrf<input type="hidden" name="request_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}"><label for="correction-{{ $purchase->id }}">Reason (latest transfer only; collector acceptance required)</label><textarea id="correction-{{ $purchase->id }}" name="reason" class="form-control mb-2" minlength="5" maxlength="1000" required></textarea><button class="btn btn-outline-primary">Request correction</button></form></details>
@endforeach
<form method="POST" action="{{ route('farmers.withdraw') }}">@csrf<label for="withdraw-reason">Publication withdrawal reason</label><textarea id="withdraw-reason" name="reason" class="form-control mb-2" required minlength="5" maxlength="1000"></textarea><button class="btn btn-outline-danger">Withdraw public profile publication</button></form></div>
@else
<form class="card card-body mb-3" method="POST" action="{{ route('farmers.access.redeem') }}">@csrf<label for="access-code">Private one-time access code</label><input id="access-code" name="access_code" class="form-control mb-2" required minlength="64" maxlength="64" autocomplete="off"><button class="btn btn-primary">Open private records</button></form>
@endif
<form class="card card-body" method="POST" action="{{ route('farmers.access.request') }}">@csrf<h2 class="h5">Request assisted access, a correction, or consent support</h2><label for="support-farmer">Farmer profile number</label><input id="support-farmer" name="farmer_id" type="number" class="form-control mb-2" min="1" required><label for="support-name">Your name</label><input id="support-name" name="name" class="form-control mb-2" maxlength="150" required><label for="support-phone">Contact phone</label><input id="support-phone" name="phone" class="form-control mb-2" required><label for="support-reason">Request details</label><textarea id="support-reason" name="reason" class="form-control mb-2" required minlength="10" maxlength="1000"></textarea><button class="btn btn-outline-primary">Request private assistance</button></form>
@endsection
