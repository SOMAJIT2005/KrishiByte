@extends('layouts.app')

@section('title', 'Crop Price Explorer — KrishiByte')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12">
        <div>
            <h2 class="fw-bold mb-1" style="color: var(--kb-text);">
                <i class="bi bi-graph-up me-2" style="color: var(--kb-primary-light);"></i> Crop Price Explorer
            </h2>
            <p class="text-muted mb-0">Aggregated indicative retail benchmarks and verified market surveys across Bangladesh</p>
        </div>
    </div>
</div>

{{-- Filter Bar --}}
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('public.prices') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="crop_id" class="form-label">Crop / Commodity <span class="text-danger">*</span></label>
                <select name="crop_id" id="crop_id" class="form-select">
                    <option value="">-- Select Crop --</option>
                    @foreach($crops as $crop)
                        <option value="{{ $crop->id }}" {{ $selectedCropId == $crop->id ? 'selected' : '' }}>
                            {{ $crop->name }} ({{ $crop->unit }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label for="district_id" class="form-label">District / Market Region</label>
                <select name="district_id" id="district_id" class="form-select">
                    <option value="">All Monitored Districts</option>
                    @foreach($districts as $district)
                        <option value="{{ $district->id }}" {{ $selectedDistrictId == $district->id ? 'selected' : '' }}>
                            {{ $district->name }} ({{ $district->division }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="date" class="form-label">Observation Date</label>
                <input type="date" name="date" id="date" class="form-control" value="{{ $selectedDate }}">
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-funnel me-1"></i> Apply Filters
                </button>
            </div>
        </form>
    </div>
</div>

<form method="GET" class="card card-body mb-3"><input type="hidden" name="crop_id" value="{{ $selectedCropId }}"><input type="hidden" name="district_id" value="{{ $selectedDistrictId }}"><input type="hidden" name="date" value="{{ $selectedDate }}"><label for="source" class="form-label">Observation source</label><select id="source" name="source" class="form-select mb-2">@foreach(['survey'=>'Reviewed market surveys','reports'=>'Reviewed citizen reports (complaint sample)','demo'=>'Illustrative demo samples'] as $key=>$label)<option value="{{ $key }}" @selected($selectedSource === $key)>{{ $label }}</option>@endforeach</select><button class="btn btn-outline-primary">Apply source</button></form>
<div class="alert alert-info">Daily window: {{ $selectedDate }} (Bangladesh calendar date), retail observations only. {{ $referenceStats['sample_count'] }} samples from {{ $referenceStats['market_count'] }} markets; {{ $referenceStats['source_count'] }} recorded contributors (not proof of independence). Last observed date: {{ $referenceStats['last_observed_date'] ?? 'None' }}. @if($selectedSource === 'demo')<strong>Illustrative demo data, not market evidence.</strong>@endif Indicative prices are not official ceilings; sample size does not establish reliability. No historical fallback is applied.</div>
{{-- Reference Price + Methodology --}}
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                    <div>
                        <span class="badge bg-light text-dark border mb-2 d-inline-flex align-items-center">
                            {{ $selectedCrop ? $selectedCrop->name : 'All Crops' }}
                            @if($selectedDistrict) &bull; {{ $selectedDistrict->name }} @endif
                        </span>
                        <h3 class="fw-bold mb-1" style="color: var(--kb-text);">Indicative Reference Price</h3>
                        <small class="text-muted">Selected day {{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }}</small>
                    </div>
                    <div class="text-end">
                        @if($referenceStats['sample_sufficiency'] === 'larger_sample')
                            <span class="badge bg-success-subtle text-success border border-success px-3 py-2 d-inline-flex align-items-center">
                                <i class="bi bi-shield-check me-1"></i> Larger sample
                                <span class="small ms-1 d-block" style="font-weight: 400;">Trimmed Mean</span>
                            </span>
                        @elseif($referenceStats['sample_sufficiency'] === 'minimum_met')
                            <span class="badge bg-info-subtle text-info-emphasis border border-info px-3 py-2 d-inline-flex align-items-center">
                                <i class="bi bi-check-circle me-1"></i> Minimum sample met
                                <span class="small ms-1 d-block" style="font-weight: 400;">Median</span>
                            </span>
                        @elseif($referenceStats['sample_sufficiency'] === 'low')
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning px-3 py-2 d-inline-flex align-items-center">
                                <i class="bi bi-exclamation-triangle me-1"></i> Insufficient samples
                                <span class="small ms-1 d-block" style="font-weight: 400;">Insufficient Data</span>
                            </span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary border px-3 py-2">
                                Insufficient Verified Samples
                            </span>
                        @endif
                    </div>
                </div>

                @if($referenceStats['indicative_price'])
                    <div class="d-flex align-items-baseline mb-3">
                        <span class="display-4 fw-bold" style="color: var(--kb-success); font-family: monospace;">
                            ৳ {{ number_format($referenceStats['indicative_price'], 2) }}
                        </span>
                        <span class="fs-5 text-muted ms-2">/ {{ $selectedCrop ? $selectedCrop->unit : 'unit' }}</span>
                    </div>

                    <div class="p-3 rounded bg-light border mb-0" style="background: #f8fafc;">
                        <div class="row g-3 small">
                            <div class="col-6">
                                <strong>Data Points:</strong> {{ $referenceStats['sample_count'] }} verified observations
                            </div>
                            <div class="col-6">
                                <strong>Method:</strong> <code>{{ $referenceStats['method'] }}</code>
                            </div>
                        </div>
                        @if($referenceStats['sample_sufficiency'] === 'low')
                            <div class="mt-2 p-2 rounded bg-warning-subtle border text-warning-emphasis small mb-0">
                                <i class="bi bi-exclamation-circle-fill me-1"></i>
                                <strong>Note:</strong> Fewer than 3 verified observations. This is an untrimmed average — treat as preliminary.
                            </div>
                        @else
                            <p class="mt-2 mb-0 text-muted">
                                Outliers and extreme spikes have been statistically isolated to protect consumers and farmers from distorted benchmarks.
                            </p>
                        @endif
                    </div>
                @else
                    <div class="p-4 rounded bg-light border text-center" style="border-color: var(--kb-border);">
                        <i class="bi bi-data-warning d-block fs-1 mb-2" style="color: var(--kb-text-muted);"></i>
                        <p class="mb-0 text-muted">Insufficient verified data: {{ $referenceStats['sample_count'] }} observation(s). At least 3 comparable observations are needed to calculate a reference price.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header">
                <i class="bi bi-journal-text me-1" style="color: var(--kb-primary-light);"></i> Data Methodology
            </div>
            <div class="card-body">
                <div class="small" style="color: var(--kb-text-muted);">
                    <p class="mb-3"><strong>Why "Indicative"?</strong></p>
                    <p class="mb-3">
                        Agricultural commodity rates naturally fluctuate across haats and retail sheds depending on grade, moisture content, and transport fuel prices.
                    </p>
                    <div class="p-2 rounded bg-light border mb-2" style="border-color: var(--kb-border); font-size: 0.8125rem;">
                        <div class="fw-semibold mb-1" style="color: var(--kb-text);">Statistical Methods Used:</div>
                        <ul class="small mb-0" style="color: var(--kb-text-muted);">
                            <li><code class="small">≥5 samples</code>: 20% trimmed mean</li>
                            <li><code class="small">3-4 samples</code>: Median</li>
                            <li><code class="small">&lt;3 samples</code>: Insufficient data warning</li>
                        </ul>
                    </div>
                    <p class="mb-0">
                        Sources are shown separately: reviewed market surveys, reviewed citizen concerns, or illustrative demo samples. Complaint samples may be biased; no result is an official price ceiling.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Observations Table --}}
<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <div class="section-title">Verified Market Survey Observations</div>
            <div class="section-subtitle">{{ $observations->total() }} records matching current filters</div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive" tabindex="0" role="region" aria-label="Scrollable data table">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Crop</th>
                        <th>District / Division</th>
                        <th>Market / Haat</th>
                        <th>Observed Price</th>
                        <th>Verification</th>
                        <th>Source</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($observations as $obs)
                        <tr>
                            <td><small class="text-muted">{{ $obs->observation_date->format('d M Y') }}</small></td>
                            <td>
                                <div class="fw-semibold">{{ $obs->crop->name }}</div>
                                <small class="text-muted mb-0">{{ $obs->crop->variety_or_grade }}</small>
                            </td>
                            <td>
                                <i class="bi bi-geo-alt me-1" style="color: var(--kb-danger); font-size: 0.875rem;"></i>
                                <div>{{ $obs->district->name }}</div>
                                <small class="text-muted">{{ $obs->district->division }}</small>
                            </td>
                            <td>
                                <div>{{ $obs->market_name ?: 'Central Market' }}</div>
                                <small class="text-muted">Verified survey location</small>
                            </td>
                            <td>
                                <div class="fw-bold" style="color: var(--kb-success); font-family: monospace;">
                                    ৳ {{ number_format($obs->observed_retail_price, 2) }}
                                </div>
                                <small class="text-muted">/ {{ $obs->unit }}</small>
                            </td>
                            <td>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle d-inline-flex align-items-center">
                                    <i class="bi bi-patch-check-fill me-1"></i> Verified
                                </span>
                            </td>
                            <td class="small text-muted" style="max-width: 120px;">
                                {{ $obs->is_demo ? 'Illustrative demo' : ucwords(str_replace('_', ' ', $obs->source)) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="bi bi-search d-block"></i>
                                    <p class="mb-0">No observations matching current criteria. Try adjusting your filters.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($observations->hasPages())
        <div class="card-footer d-flex justify-content-end">
            {{ $observations->links() }}
        </div>
    @endif
</div>
@endsection
