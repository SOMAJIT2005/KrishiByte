@extends('layouts.app')

@section('title', "Batch journey: {$batch->batch_code}")

@section('content')
<div class="alert alert-light border small">Batch review: <strong>{{ ucfirst($batch->verification_status) }}</strong>. {{ $batch->verification_status === 'verified' ? 'Provenance reviewed by an administrator.' : 'Participant-entered provenance awaiting administrator review.' }} Account badges are shown separately.</div>
<div class="row g-3 mb-4">
    <div class="col-12">
        <nav aria-label="breadcrumb" class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('public.traceability') }}">Batch lookup</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $batch->batch_code }}</li>
        </nav>
    </div>
    <div class="col-12">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div>
                <h2 class="fw-bold mb-0" style="color: var(--kb-text);">
                    <i class="bi bi-route me-2" style="color: var(--kb-primary-light);"></i> {{ $batch->batch_code }}
                </h2>
                <small class="text-muted">Registered by {{ $batch->farmer->name }} <x-verified-badge :user="$batch->farmer" /> on {{ $batch->created_at->timezone('Asia/Dhaka')->format('d M Y, h:i A') }}</small>
            </div>
            <div class="d-flex gap-2 ms-auto">
                <span class="badge bg-light text-dark border px-3 py-2 d-inline-flex align-items-center">
                    <i class="bi bi-tag me-1"></i> {{ ucwords(str_replace('_', ' ', $batch->current_stage)) }}
                </span>
                <span class="badge px-3 py-2 d-inline-flex align-items-center" style="background: var(--kb-success); color: #fff;">
                    {{ ucfirst($batch->status) }}
                </span>
            </div>
        </div>
    </div>
</div>

{{-- Batch Info Card --}}
<div class="card shadow-sm mb-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between flex-wrap gap-3">
            <div>
                <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-3 py-2 d-inline-flex align-items-center mb-2">
                    <i class="bi bi-box me-1"></i> Traceable Crop Batch
                </span>
            </div>
        </div>                <div class="row g-4">
                    <div class="col-md-3 col-6">
                        <div class="p-3 rounded border d-flex flex-column align-items-center text-center h-100" style="background: #f8fafc; border-color: var(--kb-border);">
                            <i class="bi bi-seedling d-block fs-2 mb-2" style="color: var(--kb-success);"></i>
                            <div class="text-muted small mb-1">Crop & Variety</div>
                            <div class="fw-bold" style="color: var(--kb-text);">{{ $batch->crop->name }}</div>
                            <small class="text-secondary mb-0">{{ $batch->crop->variety_or_grade }}</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="p-3 rounded border d-flex flex-column align-items-center text-center h-100" style="background: #f8fafc; border-color: var(--kb-border);">
                            <i class="bi bi-geo-alt d-block fs-2 mb-2" style="color: var(--kb-danger);"></i>
                            <div class="text-muted small mb-1">Origin District</div>
                            <div class="fw-bold" style="color: var(--kb-text);">{{ $batch->originDistrict->name }}</div>
                            @if($batch->farm_location_name)
                                <small class="text-secondary mb-0">{{ $batch->farm_location_name }}</small>
                            @else
                                <small class="text-secondary mb-0">Farm Field</small>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="p-3 rounded border d-flex flex-column align-items-center text-center h-100" style="background: #f8fafc; border-color: var(--kb-border);">
                            <i class="bi bi-route d-block fs-2 mb-2" style="color: var(--kb-info);"></i>
                            <div class="text-muted small mb-1">Chain Length</div>
                            <div class="fw-bold fs-4" style="color: var(--kb-text);">{{ $batch->total_handoffs }}</div>
                            <small class="text-secondary mb-0">middleman transfers</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="p-3 rounded border d-flex flex-column align-items-center text-center h-100" style="background: #f8fafc; border-color: var(--kb-border);">
                            <i class="bi bi-geo-alt d-block fs-2 mb-2" style="color: var(--kb-primary-light);"></i>
                            <div class="text-muted small mb-1">Districts Traversed</div>
                            <div class="fw-bold small mb-1" style="color: var(--kb-text); font-size: 0.875rem;">{{ count($batch->traversed_districts) }}</div>
                            <small class="text-secondary mb-0" style="font-size: 0.6875rem;">
                                @foreach($batch->traversed_districts as $d)
                                    {{ $d }}@if(!$loop->last) → @endif
                                @endforeach
                            </small>
                        </div>
                    </div>
                </div>

                @if($batch->total_handoffs > 0)
                    <hr class="my-4" style="border-color: var(--kb-border);">
                    <div class="row g-4">
                        <div class="col-md-4 col-6">
                            <div class="p-3 rounded border d-flex flex-column align-items-center text-center h-100" style="background: #f0fdf4; border-color: var(--kb-success); border-width: 2px;">
                                <i class="bi bi bi-arrow-down-left d-block fs-2 mb-2" style="color: var(--kb-success); transform: rotate(180deg);"></i>
                                <div class="text-muted small mb-1">Farm-gate → Current</div>
                                <div class="fw-bold fs-4" style="color: var(--kb-success); font-family: monospace;">
                                    ৳ {{ number_format($batch->cumulative_spread ?? 0, 2) }}
                                </div>
                                <small class="text-secondary mb-0">
                                    cumulative spread ({{ number_format($batch->cumulative_markup_percent ?? 0, 1) }}% markup)
                                </small>
                            </div>
                        </div>
                        <div class="col-md-4 col-6">
                            <div class="p-3 rounded border d-flex flex-column align-items-center text-center h-100" style="background: #f8fafc; border-color: var(--kb-border);">
                                <i class="bi bi-egg d-block fs-2 mb-2" style="color: var(--kb-warning);"></i>
                                <div class="text-muted small mb-1">Farm-gate Price</div>
                                <div class="fw-bold fs-4" style="color: var(--kb-text); font-family: monospace;">
                                    ৳ {{ number_format($batch->initial_farmer_price, 2) }}
                                </div>
                                <small class="text-secondary mb-0">per {{ $batch->unit }} (origin)</small>
                            </div>
                        </div>
                        <div class="col-md-4 col-6">
                            <div class="p-3 rounded border d-flex flex-column align-items-center text-center h-100" style="background: #fef2f2; border-color: var(--kb-danger); border-width: 2px;">
                                <i class="bi bi-price-tag d-block fs-2 mb-2" style="color: var(--kb-danger);"></i>
                                <div class="text-muted small mb-1">Current Market Price</div>
                                <div class="fw-bold fs-4" style="color: var(--kb-danger); font-family: monospace;">
                                    ৳ {{ number_format($batch->latest_price, 2) }}
                                </div>
                                <small class="text-secondary mb-0">per {{ $batch->unit }} (latest)</small>
                            </div>
                        </div>
                    </div>
                @endif


    </div>
</div>

{{-- Map --}}
<div class="row g-4 mb-4">
    <div class="col-lg-12">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <div class="section-title">Supply Chain Transit Route Map</div>
                    <div class="section-subtitle">GPS waypoints and transit path across custody stages</div>
                </div>
                <span class="badge bg-light text-dark border small d-inline-flex align-items-center">
                    <i class="bi bi-map me-1"></i> OpenStreetMap & Leaflet
                </span>
            </div>
            <div class="card-body p-0">
                <div id="traceabilityMap" style="height: 400px; width: 100%;"></div>
            </div>
        </div>
    </div>
</div>

{{-- Timeline --}}
<div class="row g-4">
    <div class="col-12">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h3 class="fw-bold mb-1" style="color: var(--kb-text);">Custody Transfer Timeline</h3>
                <p class="text-muted mb-0 small">Chronological record of every custody handoff across the supply chain</p>
            </div>
        </div>
    </div>

    {{-- Farm Gate Origin --}}
    <div class="col-12">
        <div class="card shadow-sm mb-4" style="border-left: 4px solid var(--kb-success);">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-success bg-opacity-10 text-success px-2 py-1 d-inline-flex align-items-center" style="font-size: 0.6875rem;">
                            <i class="bi bi-circle-fill me-1"></i> Origin Stage 0
                        </span>
                        <h5 class="fw-bold mb-0" style="color: var(--kb-text);">Harvest & Farm Gate Aggregation</h5>
                    </div>
                    <span class="text-muted small">{{ $batch->harvest_date->format('d M Y') }}</span>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="small text-muted d-block mb-1">Registered By</div>
                        <div class="fw-semibold" style="color: var(--kb-text);">{{ $batch->farmer->name }} <x-verified-badge :user="$batch->farmer" /></div>
                        <span class="badge bg-light text-dark border badge-role ms-1">Farmer</span>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted d-block mb-1">Farm Location</div>
                        <div class="d-flex align-items-center gap-1">
                            <i class="bi bi-geo-alt" style="color: var(--kb-danger); font-size: 0.8125rem;"></i>
                            <span style="color: var(--kb-text);">{{ $batch->originDistrict->name }}</span>
                        </div>
                        @if($batch->farm_location_name)
                            <small class="text-secondary">{{ $batch->farm_location_name }}</small>
                        @endif
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted d-block mb-1">Baseline Farm Price</div>
                        <div class="fw-bold fs-4" style="color: var(--kb-success); font-family: monospace;">৳ {{ number_format($batch->initial_farmer_price, 2) }}</div>
                        <small class="text-secondary">/ {{ $batch->unit }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Transactions --}}
    @forelse($batch->transactions as $tx)
        <div class="col-12">
            <div class="card shadow-sm mb-3" style="border-left: 4px solid {{ $tx->validation_status === 'flagged' ? 'var(--kb-danger)' : 'var(--kb-primary-light)' }};">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge px-2 py-1 d-inline-flex align-items-center" style="font-size: 0.6875rem;" {{ $tx->validation_status === 'flagged' ? 'style="background: var(--kb-danger); color: #fff;"' : '' }}>
                                <i class="bi bi-arrow-left-right me-1"></i> Handoff Step #{{ $tx->sequence_no }}
                            </span>
                            <h5 class="fw-bold mb-0" style="color: var(--kb-text);">
                                {{ ucwords(str_replace('_', ' ', $tx->stage)) }}
                            </h5>
                            @if($tx->validation_status === 'flagged')
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 ms-2 d-inline-flex align-items-center">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i> Anomaly Flagged
                                </span>
                            @endif
                        </div>
                        <span class="text-muted small">{{ $tx->transaction_date->timezone('Asia/Dhaka')->format('d M Y, h:i A') }}</span>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="small text-muted d-block mb-1">Custody Transfer</div>
                            <div class="small">
                                <strong>{{ $tx->seller->name }} <x-verified-badge :user="$tx->seller" /></strong>
                                <span class="badge-role bg-light text-dark border ms-1">{{ $tx->seller->role }}</span>
                                <span class="text-muted mx-1" style="font-size: 0.875rem;">→</span>
                                <strong>{{ $tx->buyer->name }} <x-verified-badge :user="$tx->buyer" /></strong>
                                <span class="badge-role bg-light text-dark border ms-1">{{ $tx->buyer->role }}</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="small text-muted d-block mb-1">Transfer Market</div>
                            <div class="d-flex align-items-center gap-1">
                                <i class="bi bi-geo-alt" style="color: var(--kb-danger); font-size: 0.8125rem;"></i>
                                <span style="color: var(--kb-text);">{{ $tx->district->name }}</span>
                            </div>
                            @if($tx->market_name)
                                <small class="text-secondary">{{ $tx->market_name }}</small>
                            @endif
                        </div>
                        <div class="col-md-2">
                            <div class="small text-muted d-block mb-1">Quantity</div>
                            <div class="fw-semibold" style="color: var(--kb-text);">{{ number_format($tx->quantity, 0) }} {{ $tx->unit }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="small text-muted d-block mb-1">Transfer Price</div>
                            <div class="fw-bold fs-4" style="color: var(--kb-text); font-family: monospace;">৳ {{ number_format($tx->price_per_unit, 2) }}</div>
                            <small class="text-secondary">/ {{ $tx->unit }}</small>
                            @if($tx->gross_price_spread !== null)
                                <div class="mt-2 small">
                                    <span class="fw-semibold" style="color: {{ $tx->gross_price_spread > 0 ? 'var(--kb-text)' : 'var(--kb-success)' }};">
                                        Spread: {{ $tx->gross_price_spread >= 0 ? '+' : '' }}৳ {{ number_format($tx->gross_price_spread, 2) }}
                                    </span>
                                    @if($tx->markup_percent !== null)
                                        <span class="badge px-2 py-1 ms-2 d-inline-flex align-items-center" style="font-size: 0.6875rem;" {{ $tx->markup_percent > 30 ? 'style="background: var(--kb-warning); color: #1e293b;"' : 'style="background: var(--kb-bg); color: var(--kb-text); border: 1px solid var(--kb-border);' }}>
                                            +{{ number_format($tx->markup_percent, 1) }}% markup
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>


                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body p-4 text-center">
                    <i class="bi bi-inbox d-block fs-1 mb-2" style="color: var(--kb-text-muted);"></i>
                    <p class="text-muted mb-0">This batch is currently held at the farm gate. No downstream custody transfers recorded yet.</p>
                </div>
            </div>
        </div>
    @endforelse
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const points = @json($mapPoints);

        const defaultCenter = [23.8103, 90.4125];
        const defaultZoom = 7;

        if (typeof L === 'undefined') { document.getElementById('traceabilityMap').textContent = 'Map unavailable. Follow the recorded journey below.'; return; }
        const map = L.map('traceabilityMap').setView(defaultCenter, defaultZoom);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        if (points && points.length > 0) {
            const latlngs = [];
            points.forEach((pt, idx) => {
                const latlng = [pt.lat, pt.lng];
                latlngs.push(latlng);

                const marker = L.marker(latlng).addTo(map);
                const unit = @json($batch->unit);
                const popup = document.createElement('div');
                popup.textContent = `${pt.name} · ${pt.stage} · BDT ${pt.price} per ${unit}`;
                marker.bindPopup(popup);

                if (idx === 0) {
                    marker.openPopup();
                }
            });

            if (latlngs.length > 1) {
                const polyline = L.polyline(latlngs, {
                    color: '#198754',
                    weight: 4,
                    dashArray: '8, 8',
                    opacity: 0.85
                }).addTo(map);

                map.fitBounds(polyline.getBounds(), { padding: [50, 50] });
            } else {
                map.setView(latlngs[0], 11);
            }
        }
    });
</script>
@endpush
