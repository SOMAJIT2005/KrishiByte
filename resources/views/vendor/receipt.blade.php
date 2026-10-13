@extends('layouts.app')
@section('title','Transaction receipt')
@section('content')
@php($farmAgreement = \App\Models\FarmPurchase::where('transaction_id',$transaction->id)->first())
@if($farmAgreement)
<div class="alert alert-info">Collector attested this farmer purchase using a handwritten agreement. Witness: {{ $farmAgreement->witness_name }}. Farmer consent, signature and payment have not been independently verified. <a href="{{ route('farmers.agreement',$farmAgreement->id) }}">View private agreement</a> · <a href="{{ route('farmers.show',$farmAgreement->farmer_id) }}">Farmer profile</a></div>
@endif
<div class="card"><div class="card-body"><h1 class="h3">Recorded transaction receipt #{{ $transaction->id }}</h1><p>This records terms and inventory activity; it is not proof of payment or product certification.</p>
<dl class="row"><dt class="col-sm-4">Batch / crop</dt><dd class="col-sm-8">{{ $transaction->batch->batch_code }} / {{ $transaction->batch->crop->name }}</dd><dt class="col-sm-4">Seller / customer</dt><dd class="col-sm-8">{{ $transaction->seller->name }} / {{ $transaction->buyer?->name ?? 'Anonymous retail customer' }}</dd><dt class="col-sm-4">Quantity / unit price</dt><dd class="col-sm-8">{{ $transaction->quantity }} {{ $transaction->unit }} / BDT {{ $transaction->price_per_unit }}</dd><dt class="col-sm-4">Total recorded</dt><dd class="col-sm-8">BDT {{ $transaction->total_amount }}</dd><dt class="col-sm-4">Confirmation</dt><dd class="col-sm-8">{{ $transaction->confirmation_status }}</dd><dt class="col-sm-4">Server record time</dt><dd class="col-sm-8">{{ $transaction->transaction_date->timezone('Asia/Dhaka')->format('d M Y, h:i A') }}</dd><dt class="col-sm-4">Correction state</dt><dd class="col-sm-8">{{ $transaction->is_voided ? 'Voided: '.$transaction->void_reason : 'Active' }}</dd></dl>
<a href="{{ route('public.traceability.show',$transaction->batch->batch_code) }}">Public batch journey</a> <button class="btn btn-primary d-print-none" onclick="window.print()">Print receipt</button>
</div></div>
@endsection
