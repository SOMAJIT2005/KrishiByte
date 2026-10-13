@extends('layouts.app')

@section('title', 'KrishiByte — Agricultural Price Transparency & Traceability')

@section('content')
{{-- Hero Section --}}
<div class="row align-items-center g-4 mb-5">
    <div class="col-lg-7">
        <div class="mb-3">
            <span class="badge bg-white text-success border border-success px-3 py-2 rounded-pill fw-semibold shadow-sm d-inline-flex align-items-center">
                <i class="bi bi-shield-check me-1"></i> Transparent Farm-to-Retail Intelligence
            </span>
        </div>
        <h1 class="display-5 fw-bold mb-3" style="color: var(--kb-text);">
            Every harvest has a story. Follow it to market.
        </h1>
        <p class="lead text-muted mb-4" style="font-size: 1.1rem;">
            KrishiByte brings end-to-end auditability to Bangladesh's agricultural markets. From northern grower fields in Bogura and Dinajpur to retail kitchen markets in Dhaka and Chattogram, follow batch prices, review price changes, and empower fair trade.
        </p>
        <div class="d-flex flex-wrap gap-3">
            <a href="{{ route('public.prices') }}" class="btn btn-lg px-4 py-2" style="background: var(--kb-primary); border-color: var(--kb-primary); color: #fff;">
                <i class="bi bi-graph-up me-1"></i> Check Market Prices
            </a>
            <a href="{{ route('public.traceability') }}" class="btn btn-lg px-4 py-2 btn-outline-primary" style="border-color: var(--kb-primary); color: var(--kb-primary);">
                <i class="bi bi-route me-1"></i> Track Batch Journey
            </a>
            <a href="{{ route('public.report') }}" class="btn btn-lg px-4 py-2" style="background: var(--kb-accent); border-color: var(--kb-accent); color: #1e293b;">
                <i class="bi bi-exclamation-triangle me-1"></i> Report Price Anomaly
            </a>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card shadow-sm h-100" style="border: 1px solid var(--kb-border);">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-primary-light d-flex align-items-center justify-content-center rounded-circle me-2" style="width: 2.5rem; height: 2.5rem; background: rgba(25,135,84,0.12); color: var(--kb-primary-light);">
                        <i class="bi bi-activity fs-4"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0" style="color: var(--kb-text);">Live Network Activity</h6>
                        <span class="small text-muted">Recorded activity · illustrative demo data</span>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <div class="p-3 rounded border d-flex align-items-center justify-content-between" style="background: #f8fafc; border-color: var(--kb-border);">
                            <div>
                                <div class="text-muted small mb-0">Crop Batches</div>
                                <div class="fw-bold fs-4" style="color: var(--kb-text);">{{ $stats['total_batches'] }}</div>
                            </div>
                            <i class="bi bi-boxes fs-3" style="color: var(--kb-primary-light);"></i>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 rounded border d-flex align-items-center justify-content-between" style="background: #f8fafc; border-color: var(--kb-border);">
                            <div>
                                <div class="text-muted small mb-0">Active Crops</div>
                                <div class="fw-bold fs-4" style="color: var(--kb-success);">{{ $stats['tracked_crops'] }}</div>
                            </div>
                            <i class="bi bi-seedling fs-3" style="color: var(--kb-success);"></i>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 rounded border d-flex align-items-center justify-content-between" style="background: #f8fafc; border-color: var(--kb-border);">
                            <div>
                                <div class="text-muted small mb-0">Districts</div>
                                <div class="fw-bold fs-4" style="color: var(--kb-info);">{{ $stats['monitored_districts'] }}</div>
                            </div>
                            <i class="bi bi-geo-alt fs-3" style="color: var(--kb-info);"></i>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 rounded border d-flex align-items-center justify-content-between" style="background: #f8fafc; border-color: var(--kb-border);">
                            <div>
                                <div class="text-muted small mb-0">Reviewed surveys</div>
                                <div class="fw-bold fs-4" style="color: var(--kb-warning);">{{ $stats['verified_observations'] }}</div>
                            </div>
                            <i class="bi bi-patch-check fs-3" style="color: var(--kb-warning);"></i>
                        </div>
                    </div>
                </div>
                <div class="mt-3 p-2 rounded bg-light border-0 small" style="color: var(--kb-text-muted);">
                    <i class="bi bi-info-circle me-1" style="color: var(--kb-primary-light);"></i>
                    <strong>Public Governance Standard:</strong> Consumer price submissions remain private until moderated by authorized monitors.
                </div>
            </div>
        </div>
    </div>
</div>

<section class="card card-body p-4 mb-5"><div class="row align-items-center g-3"><div class="col-md-9"><span class="badge bg-success-subtle text-success mb-2">PUBLIC ANALYTICS</span><h2 class="h3 fw-bold">Watch prices rise and fall</h2><p class="text-muted mb-0">Explore every crop’s daily price history, compare districts, and see which products are rising or falling with clear sample coverage.</p></div><div class="col-md-3"><a class="btn btn-primary w-100" href="{{ route('public.analytics') }}">Explore price analytics <i class="bi bi-arrow-right"></i></a></div></div></section>
{{-- 4-Stage Pipeline --}}
<div class="row g-4 mb-5">
    <div class="col-12">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h2 class="fw-bold mb-0" style="color: var(--kb-text);">How KrishiByte Works</h2>
                <p class="text-muted small">Flexible custody chain with repeated collectors and wholesalers, quantity tracking, and price review</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card h-100 border-0 shadow-sm" style="background: var(--kb-surface);">
            <div class="card-body p-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="rounded bg-success-kpi" style="width: 2.5rem; height: 2.5rem; min-width: 2.5rem; display: flex; align-items: center; justify-content: center; background: rgba(16,185,129,0.12); color: var(--kb-success); font-size: 1.25rem;">
                        <i class="bi bi-person-badge"></i>
                    </div>
                    <div>
                        <span class="small text-muted d-block mb-1" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Stage 1</span>
                        <h6 class="fw-bold mb-1" style="color: var(--kb-text);">Farm Gate</h6>
                        <p class="small text-muted mb-0" style="font-size: 0.8125rem;">Farmers enroll for a profile. Collectors record crop quantity and price with a private handwritten agreement.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card h-100 border-0 shadow-sm" style="background: var(--kb-surface);">
            <div class="card-body p-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="rounded bg-info-kpi" style="width: 2.5rem; height: 2.5rem; min-width: 2.5rem; display: flex; align-items: center; justify-content: center; background: rgba(59,130,246,0.12); color: var(--kb-info); font-size: 1.25rem;">
                        <i class="bi bi-truck"></i>
                    </div>
                    <div>
                        <span class="small text-muted d-block mb-1" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Stage 2</span>
                        <h6 class="fw-bold mb-1" style="color: var(--kb-text);">Faria / Collector</h6>
                        <p class="small text-muted mb-0" style="font-size: 0.8125rem;">Collectors record agreed purchase prices and onward transfers. More than one collector can handle a lot.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card h-100 border-0 shadow-sm" style="background: var(--kb-surface);">
            <div class="card-body p-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="rounded bg-primary-kpi" style="width: 2.5rem; height: 2.5rem; min-width: 2.5rem; display: flex; align-items: center; justify-content: center; background: rgba(25,135,84,0.12); color: var(--kb-primary-light); font-size: 1.25rem;">
                        <i class="bi bi-building"></i>
                    </div>
                    <div>
                        <span class="small text-muted d-block mb-1" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Stage 3</span>
                        <h6 class="fw-bold mb-1" style="color: var(--kb-text);">Wholesale Arat</h6>
                        <p class="small text-muted mb-0" style="font-size: 0.8125rem;">Regional wholesalers record each purchase and sale before passing lots to another distributor or retailer.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card h-100 border-0 shadow-sm" style="background: var(--kb-surface);">
            <div class="card-body p-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="rounded bg-warning-kpi" style="width: 2.5rem; height: 2.5rem; min-width: 2.5rem; display: flex; align-items: center; justify-content: center; background: rgba(245,158,11,0.12); color: var(--kb-warning); font-size: 1.25rem;">
                        <i class="bi bi-cart4"></i>
                    </div>
                    <div>
                        <span class="small text-muted d-block mb-1" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Stage 4</span>
                        <h6 class="fw-bold mb-1" style="color: var(--kb-text);">Retail Market</h6>
                        <p class="small text-muted mb-0" style="font-size: 0.8125rem;">Retail vendors sell to citizens. Markups breaching thresholds automatically flag for authority review.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Recent Batches Table --}}
<div class="row g-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <div class="section-title">Recent Traceable Crop Batches</div>
                    <div class="section-subtitle">End-to-end batch journeys from farm to retail</div>
                </div>
                <a href="{{ route('public.traceability') }}" class="btn btn-sm btn-outline-secondary" style="color: var(--kb-text); border-color: var(--kb-border);">
                    View All <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" tabindex="0" role="region" aria-label="Scrollable data table">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Batch Code</th>
                                <th>Crop</th>
                                <th>Origin</th>
                                <th>Harvest</th>
                                <th>Quantity</th>
                                <th>Farm Price</th>
                                <th>Custodian</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentBatches as $batch)
                                <tr>
                                    <td>
                                        <a href="{{ route('public.traceability.show', $batch->batch_code) }}" class="fw-bold text-decoration-none" style="color: var(--kb-primary-light); font-family: 'JetBrains Mono', monospace; font-size: 0.8125rem;">
                                            {{ $batch->batch_code }}
                                        </a>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $batch->crop->name }}</div>
                                        <small class="text-muted mb-0">{{ $batch->crop->variety_or_grade }}</small>
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
                                        <small class="text-muted d-block">/ {{ $batch->unit }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border" style="font-size: 0.6875rem;">
                                            {{ $batch->currentCustodian->name }}
                                            <span class="badge-role">{{ $batch->currentCustodian->role }}</span>
                                        </span>
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
                                        <a href="{{ route('public.traceability.show', $batch->batch_code) }}" class="btn btn-sm btn-outline-primary btn-sm" style="color: var(--kb-primary); border-color: var(--kb-border); font-weight: 500;">
                                            <i class="bi bi-route me-1"></i> Trace
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9">
                                        <div class="empty-state">
                                            <i class="bi bi-inbox d-block"></i>
                                            <p class="mb-0">No batches registered yet. Log in as a farmer to create a batch.</p>
                                        </div>
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
