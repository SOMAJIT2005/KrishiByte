@extends('layouts.app')
@section('title', 'Batch review')
@section('content')
<div class="workspace-heading"><div><span class="eyebrow">ADMINISTRATION / TRACEABILITY</span><h1>Batch review</h1><p class="text-muted">Account badges and batch verification are reviewed separately.</p></div><a class="btn btn-outline-primary" href="{{ route('admin.dashboard') }}">Admin workspace</a></div>
<div class="card"><div class="table-responsive" tabindex="0" role="region" aria-label="Scrollable data table"><table class="table mb-0"><thead><tr><th>Batch</th><th>Farmer</th><th>Custodian</th><th>Review status</th><th></th></tr></thead><tbody>
@forelse($batches as $batch)<tr><td>{{ $batch->batch_code }}<small class="d-block text-muted">{{ $batch->crop->name }}</small></td><td>{{ $batch->farmer->name }} <x-verified-badge :user="$batch->farmer" /></td><td>{{ $batch->currentCustodian->name }}</td><td>{{ ucfirst($batch->verification_status) }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.batches.detail', $batch->id) }}">Review</a></td></tr>@empty<tr><td colspan="5">No batches to review.</td></tr>@endforelse
</tbody></table></div></div><div class="mt-3">{{ $batches->links() }}</div>
@endsection
