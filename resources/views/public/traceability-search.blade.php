@extends('layouts.app')

@section('title', 'Supply Chain Traceability Lookup — KrishiByte')

@section('content')
<div class="row g-4 mb-5">
    <div class="col-12">
        <div>
            <h2 class="fw-bold mb-1" style="color: var(--kb-text);">
                <i class="bi bi-route me-2" style="color: var(--kb-primary-light);"></i> Crop Batch Traceability
            </h2>
            <p class="text-muted mb-0">Enter any KrishiByte Batch Identifier to inspect complete provenance from field to shelf</p>
        </div>
    </div>
</div>

{{-- Search Card --}}
<div class="row justify-content-center mb-5">
    <div class="col-lg-7">
        <div class="card shadow-sm border-0" style="border: 1px solid var(--kb-border);">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <div class="bg-primary-kpi rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 4rem; height: 4rem; font-size: 2rem; color: var(--kb-primary-light);">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <h4 class="fw-bold mb-1" style="color: var(--kb-text);">Trace a Batch Journey</h4>
                    <p class="text-muted small mb-0">Track any registered crop batch from farm gate to retail shelf with full custody history and GPS route visualization.</p>
                </div>
                <form method="GET" action="{{ route('public.traceability') }}">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text" style="background: #f8fafc; border-color: var(--kb-border); font-size: 1.25rem;">
                            <i class="bi bi-search" style="color: var(--kb-text-muted);"></i>
                        </span>
                        <input type="text" name="code" class="form-control form-control-lg" placeholder="Enter Batch Code (e.g. KB-20261010-POT001)" required autofocus style="border-radius: 0.5rem; font-family: 'JetBrains Mono', monospace;">
                        <button class="btn btn-primary btn-lg px-4" type="submit" style="border-radius: 0.5rem;">
                            Trace Batch
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <div class="section-title">Recently Registered Batches</div>
                    <div class="section-subtitle">Batches currently in circulation across the supply chain</div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Batch Code</th>
                                <th>Crop</th>
                                <th>Origin District</th>
                                <th>Harvest Date</th>
                                <th>Quantity</th>
                                <th>Farm-gate Price</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentBatches as $batch)
                                <tr>
                                    <td>
                                        <code class="fw-bold" style="color: var(--kb-primary-light); font-family: monospace;">{{ $batch->batch_code }}</code>
                                    </td>
                                    <td>
                                        <strong>{{ $batch->crop->name }}</strong>
                                        <small class="text-muted d-block">{{ $batch->crop->variety_or_grade }}</small>
                                    </td>
                                    <td>
                                        <i class="bi bi-geo-alt me-1" style="color: var(--kb-danger); font-size: 0.875rem;"></i>
                                        <span class="small">{{ $batch->originDistrict->name }}</span>
                                    </td>
                                    <td><small class="text-muted">{{ $batch->harvest_date->format('d M Y') }}</small></td>
                                    <td><small class="text-muted">{{ number_format($batch->initial_quantity, 0) }} {{ $batch->unit }}</small></td>
                                    <td>
                                        <span class="fw-bold" style="color: var(--kb-success); font-family: monospace;">
                                            ৳ {{ number_format($batch->initial_farmer_price, 2) }}
                                        </span>
                                        <small class="text-muted">/ {{ $batch->unit }}</small>
                                    </td>
                                    <td>
                                        @if($batch->status === 'active')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                        @elseif($batch->status === 'flagged')
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Flagged</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">{{ ucfirst($batch->status) }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('public.traceability.show', $batch->batch_code) }}" class="btn btn-sm btn-outline-secondary" style="color: var(--kb-primary); border-color: var(--kb-border);">
                                            <i class="bi bi-route me-1"></i> View Journey
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8">
                                        <div class="empty-state">
                                            <i class="bi bi-inbox d-block"></i>
                                            <p class="mb-0">No batches recorded yet.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
