@extends('layouts.app')
@section('title','Handoff proposals')
@section('content')
<h1 class="h3">Handoff proposals</h1><p class="text-muted">Review the exact quantity and price before accepting. Proposals expire after 48 hours and reserve the whole lot. Changing terms requires cancellation and a new proposal.</p>
<a class="btn btn-outline-primary mb-3" href="{{ auth()->user()->isFarmer() ? route('farmer.handoff') : route('vendor.dashboard') }}">Back to workspace</a>
@forelse($proposals as $proposal)
<div class="card mb-3"><div class="card-body"><div class="d-flex justify-content-between flex-wrap"><h2 class="h5">Proposal #{{ $proposal->id }} · {{ $proposal->batch->batch_code }}</h2><span class="badge bg-secondary">{{ $proposal->status === 'proposed' && $proposal->expires_at->isPast() ? 'expired' : $proposal->status }}</span></div>
<p>{{ $proposal->seller->name }} → {{ $proposal->buyer->name }}<br>{{ $proposal->terms['quantity'] }} {{ $proposal->batch->unit }} at BDT {{ $proposal->terms['price_per_unit'] }}/{{ $proposal->batch->unit }} · {{ $proposal->terms['market_name'] ?? 'Location not supplied' }}</p>
<p class="small text-muted">Proposed by {{ $proposal->proposer->name }} · expires {{ $proposal->expires_at->timezone('Asia/Dhaka')->format('d M Y, h:i A') }}. Original farmer: {{ $proposal->batch->farmer->name }}. Payment is not verified.</p>
@if($proposal->terms['note'])<p>{{ $proposal->terms['note'] }}</p>@endif
@if($proposal->decision_reason)<p>Decision reason: {{ $proposal->decision_reason }}</p>@endif
@if($proposal->transaction_id)<a href="{{ route('transactions.receipt',$proposal->transaction_id) }}">View printable receipt #{{ $proposal->transaction_id }}</a>@endif
@if($proposal->status === 'proposed' && $proposal->expires_at->isFuture())
@if(auth()->id() !== $proposal->proposed_by_user_id)
<form method="POST" action="{{ route('handoffs.decide',$proposal->id) }}" class="mb-2">@csrf<input type="hidden" name="decision" value="accepted"><button class="btn btn-success">Accept proposal #{{ $proposal->id }}</button></form>
<form method="POST" action="{{ route('handoffs.decide',$proposal->id) }}" class="d-flex gap-2">@csrf<input type="hidden" name="decision" value="disputed"><label class="visually-hidden" for="reason-{{ $proposal->id }}">Dispute reason</label><input id="reason-{{ $proposal->id }}" name="reason" class="form-control" placeholder="Reason for dispute" minlength="5" maxlength="1000" required><button class="btn btn-outline-danger">Dispute</button></form>
@else
<form method="POST" action="{{ route('handoffs.decide',$proposal->id) }}" class="d-flex gap-2">@csrf<input type="hidden" name="decision" value="cancelled"><label class="visually-hidden" for="reason-{{ $proposal->id }}">Cancellation reason</label><input id="reason-{{ $proposal->id }}" name="reason" class="form-control" placeholder="Reason for cancellation" minlength="5" maxlength="1000" required><button class="btn btn-outline-secondary">Cancel</button></form>
@endif
@endif
</div></div>
@empty<div class="card card-body">No proposals yet. Create a proposal from your workspace or wait for a counterparty.</div>@endforelse
{{ $proposals->links() }}
@endsection
