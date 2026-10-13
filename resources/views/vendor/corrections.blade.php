@extends('layouts.app')
@section('title','Custody correction requests')
@section('content')
<h1 class="h3">Custody correction requests</h1><p>A request reserves the lot. Accepted corrections preserve the original record and require the affected participant’s agreement. Resolve pending handoff proposals first.</p>
@forelse($requests as $item)
<div class="card card-body mb-3"><h2 class="h5">Request #{{ $item->id }} · Transaction #{{ $item->transaction_id }} · {{ $item->status }}</h2><p>{{ $item->reason }}</p><p>{{ $item->decision_reason }}</p>
@if($item->status === 'pending')
<form method="POST" action="{{ route('corrections.decide',$item->id) }}">@csrf<label for="reason-{{ $item->id }}">Decision reason</label><textarea id="reason-{{ $item->id }}" name="reason" class="form-control mb-2" required minlength="5" maxlength="1000"></textarea>
@if($item->counterparty_id === auth()->id())<button name="decision" value="accepted" class="btn btn-primary">Accept correction</button> <button name="decision" value="disputed" class="btn btn-outline-danger">Dispute</button>@else<button name="decision" value="cancelled" class="btn btn-outline-secondary">Cancel request</button>@endif
</form>
@endif</div>
@empty<p>No correction requests involving your account.</p>@endforelse
@endsection
