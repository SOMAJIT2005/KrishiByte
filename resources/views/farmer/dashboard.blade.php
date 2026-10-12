@extends('layouts.app')

@section('title', 'Farmer Dashboard — KrishiByte')

@section('content')
<div class="d-flex justify-content-end mb-3"><a class="btn btn-outline-primary" href="{{ route('farmer.handoff') }}">Transfer a harvest to a collector →</a></div>
<div class="row g-3 mb-4 align-items-center">
    <div class="col-md-8">
        <div>
            <h2 class="fw-bold mb-1" style="color: var(--kb-text);">
                <i class="bi bi-tractor me-2" style="color: var(--kb-primary-light);"></i> Farmer Producer Portal
            </h2>
            <p class="text-muted mb-0">
                Welcome, <strong>{{ $farmer->name }}</strong>
                <span class="mx-2" style="color: var(--kb-text-muted);">•</span>
                {{ $farmer->district ? 'Region: ' . $farmer->district->name : 'Bangladesh' }}
                <span class="badge bg-light text-dark border badge-role ms-2">{{ $farmer->role }}</span>
            </p>
        </div>
    </div>
    <div class="col-md-4 text-md-end mt-3 mt-md-0">
        <button type="button" class="btn btn-primary btn-lg px-4 py-2" data-bs-toggle="modal" data-bs-target="#createBatchModal" style="border-radius: 0.5rem;">
            <i class="bi bi-plus-circle me-1"></i> Register New Crop Batch
        </button>
    </div>
</div>

{{-- KPI Cards --}}
<div class="row g-4 mb-4">
    <div class="col-md-4 col-6">
        <div class="kpi-card border-success bg-success-kpi">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="kpi-label">Total Registered Batches</div>
                    <div class="kpi-value">{{ $stats['total_batches'] }}</div>
                </div>
                <div class="kpi-icon">
                    <i class="bi bi-boxes"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-6">
        <div class="kpi-card border-primary bg-primary-kpi">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="kpi-label">Active in Your Custody</div>
                    <div class="kpi-value" style="color: var(--kb-primary-light);">{{ $stats['active_in_custody'] }}</div>
                </div>
                <div class="kpi-icon">
                    <i class="bi bi-hold"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-6">
        <div class="kpi-card border-info bg-info-kpi">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="kpi-label">Transferred to Aggregators</div>
                    <div class="kpi-value" style="color: var(--kb-info);">{{ $stats['transferred'] }}</div>
                </div>
                <div class="kpi-icon">
                    <i class="bi bi-truck"></i>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Market Benchmarks --}}
@if(count($marketBenchmarks) > 0)
    <div class="card shadow-sm mb-4">
        <div class="card-header d-flex align-items-center gap-2">
            <i class="bi bi-tag-fill" style="color: var(--kb-success);"></i>
            <span class="section-title mb-0">Indicative Market Benchmarks — {{ $farmer->district ? $farmer->district->name : 'Your District' }}</span>
        </div>
        <div class="card-body p-4">
            <p class="small text-muted mb-3">Use these reference rates to negotiate fair farm-gate pricing with local collectors and arats.</p>
            <div class="row g-3">
                @foreach($marketBenchmarks as $mb)
                    <div class="col-md-4 col-sm-6">
                        <div class="p-3 rounded border d-flex align-items-center justify-content-between h-100" style="background: #f8fafc; border-color: var(--kb-border);">
                            <div>
                                <div class="fw-semibold mb-1" style="color: var(--kb-text);">{{ $mb['crop'] }}</div>
                                <small class="text-muted" style="font-size: 0.6875rem;">{{ $mb['samples'] }} verified surveys • {{ $mb['confidence'] }} confidence</small>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold" style="color: var(--kb-success); font-family: monospace; font-size: 1.1rem;">
                                    ৳ {{ number_format($mb['price'], 2) }}
                                </div>
                                <small class="text-muted" style="font-size: 0.6875rem;">/ {{ $mb['unit'] }}</small>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif

{{-- Batches Table --}}
<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <div class="section-title">Your Registered Batches</div>
            <div class="section-subtitle">{{ $batches->total() }} batches registered</div>
        </div>
        <a href="{{ route('public.traceability') }}" class="btn btn-sm btn-outline-secondary" style="color: var(--kb-text); border-color: var(--kb-border);">
            <i class="bi bi-route me-1"></i> Trace a Batch
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive" tabindex="0" role="region" aria-label="Scrollable data table">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Batch Code</th>
                        <th>Crop</th>
                        <th>Origin Field</th>
                        <th>Harvest</th>
                        <th>Qty</th>
                        <th>Farm Price</th>
                        <th>Current Custodian</th>
                        <th>Stage</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batches as $batch)
                        <tr>
                            <td>
                                <a href="{{ route('public.traceability.show', $batch->batch_code) }}" class="fw-bold text-decoration-none" style="color: var(--kb-primary-light); font-family: monospace; font-size: 0.8125rem;">
                                    {{ $batch->batch_code }}
                                </a>
                            </td>
                            <td>
                                <strong>{{ $batch->crop->name }}</strong>
                                <small class="text-muted d-block" style="font-size: 0.6875rem;">{{ $batch->crop->variety_or_grade }}</small>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    <i class="bi bi-geo-alt" style="color: var(--kb-danger); font-size: 0.8125rem;"></i>
                                    <span class="small">{{ $batch->originDistrict->name }}</span>
                                </div>
                                @if($batch->farm_location_name)
                                    <small class="text-muted" style="font-size: 0.6875rem;">{{ $batch->farm_location_name }}</small>
                                @endif
                            </td>
                            <td><small class="text-muted">{{ $batch->harvest_date->format('d M Y') }}</small></td>
                            <td><small class="text-muted">{{ number_format($batch->initial_quantity, 0) }} {{ $batch->unit }}</small></td>
                            <td>
                                <span class="fw-bold" style="color: var(--kb-success); font-family: monospace;">
                                    ৳ {{ number_format($batch->initial_farmer_price, 2) }}
                                </span>
                                <small class="text-muted d-block" style="font-size: 0.6875rem;">/ {{ $batch->unit }}</small>
                            </td>
                            <td>
                                @if($batch->current_custodian_id === auth()->id())
                                    <span class="badge bg-success bg-opacity-10 text-success d-inline-flex align-items-center">
                                        <i class="bi bi-check-circle me-1"></i>You (Farm Gate)
                                    </span>
                                @else
                                    <span class="badge bg-light text-dark border d-inline-flex align-items-center">
                                        {{ $batch->currentCustodian->name }}
                                        <span class="badge-role ms-1">{{ $batch->currentCustodian->role }}</span>
                                    </span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border" style="font-size: 0.6875rem;">
                                    {{ ucwords(str_replace('_', ' ', $batch->current_stage)) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('public.traceability.show', $batch->batch_code) }}" class="btn btn-sm btn-outline-secondary" style="color: var(--kb-primary); border-color: var(--kb-border); border-radius: 0.375rem;">
                                    <i class="bi bi-route me-1"></i>View Route
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="empty-state">
                                    <i class="bi bi-inbox d-block"></i>
                                    <p class="mb-0">You haven't registered any batches yet. Click "Register New Crop Batch" to get started.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($batches->hasPages())
        <div class="card-footer d-flex justify-content-end">
            {{ $batches->links() }}
        </div>
    @endif
</div>

<!-- Create Batch Modal -->
<div class="modal fade" id="createBatchModal" tabindex="-1" aria-labelledby="createBatchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('farmer.batches.store') }}">
                @csrf
                <div class="modal-header" style="background: var(--kb-primary); color: #fff;">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-plus-circle me-1"></i> Register New Crop Batch
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="modal_crop_id" class="form-label">Crop / Produce <span class="text-danger">*</span></label>
                            <select name="crop_id" id="modal_crop_id" class="form-select" required>
                                <option value="">-- Choose Crop --</option>
                                @foreach($crops as $crop)
                                    <option value="{{ $crop->id }}">{{ $crop->name }} ({{ $crop->unit }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="modal_origin_district_id" class="form-label">Origin District <span class="text-danger">*</span></label>
                            <select name="origin_district_id" id="modal_origin_district_id" class="form-select" required>
                                <option value="">-- Choose District --</option>
                                @foreach($districts as $district)
                                    <option value="{{ $district->id }}" {{ $farmer->district_id == $district->id ? 'selected' : '' }}>
                                        {{ $district->name }} ({{ $district->division }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <label for="farm_location_name" class="form-label">Field / Farm Location Name</label>
                            <input type="text" name="farm_location_name" id="farm_location_name" class="form-control" placeholder="e.g. Shibganj Agro Field, Plot 3">
                        </div>

                        <div class="col-md-4">
                            <label for="harvest_date" class="form-label">Harvest Date <span class="text-danger">*</span></label>
                            <input type="date" name="harvest_date" id="harvest_date" class="form-control" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label for="initial_quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                            <input type="number" step="1" name="initial_quantity" id="initial_quantity" class="form-control" placeholder="e.g. 2000" required>
                        </div>
                        <div class="col-md-4">
                            <label for="unit" class="form-label">Unit <span class="text-danger">*</span></label>
                            <select name="unit" id="unit" class="form-select" required>
                                <option value="kg">kg (Kilogram)</option>
                                <option value="mon">mon / maund (মণ)</option>
                                <option value="sack">sack / bag (বস্তা)</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="initial_farmer_price" class="form-label">Farm-Gate Price (BDT) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text" style="background: #f8fafc;">৳</span>
                                <input type="number" step="0.5" name="initial_farmer_price" id="initial_farmer_price" class="form-control" placeholder="e.g. 25.00" required>
                            </div>
                            <small class="text-muted">Price per unit received by farmer.</small>
                        </div>

                        <div class="col-md-3">
                            <label for="origin_latitude" class="form-label small">GPS Latitude</label>
                            <input type="text" name="origin_latitude" id="origin_latitude" class="form-control form-control-sm" placeholder="24.xxxx">
                        </div>
                        <div class="col-md-3">
                            <label for="origin_longitude" class="form-label small">GPS Longitude</label>
                            <input type="text" name="origin_longitude" id="origin_longitude" class="form-control form-control-sm" placeholder="89.xxxx">
                        </div>

                        <div class="col-12">
                            <label for="notes" class="form-label">Quality & Sorting Notes</label>
                            <textarea name="notes" id="notes" rows="2" class="form-control" placeholder="e.g. Grade A diamond potato, sun-dried, sorted in 50kg bags."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4" style="border-radius: 0.5rem;">
                        <i class="bi bi-check-circle me-1"></i> Save & Generate Batch
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
