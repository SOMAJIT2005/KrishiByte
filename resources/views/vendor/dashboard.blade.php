@extends('layouts.app')

@section('title', 'Vendor Custody Workspace — KrishiByte')

@section('content')
@if(!$user->trading_approved && $user->isVendor())<div class="alert alert-info">Trading approval is pending. An administrator must approve your trading capability before purchases or handoff proposals can be recorded. Identity badges are reviewed separately.</div>@endif
<form method="GET" class="card card-body mb-3"><div class="row g-3 align-items-end"><div class="col-md-3"><label for="buyer_district_id" class="form-label">1. Recipient district</label><select id="buyer_district_id" name="buyer_district_id" class="form-select" required><option value="">-- Select district first --</option>@foreach($districts as $district)<option value="{{ $district->id }}" @selected($buyerDistrictId == $district->id)>{{ $district->name }}</option>@endforeach</select></div><div class="col-md-3"><label for="buyer_role" class="form-label">2. Recipient role</label><select id="buyer_role" name="buyer_role" class="form-select">@foreach($roles as $role)<option value="{{ $role }}" @selected($buyerRole === $role)>{{ $role === 'retail' ? 'Retail shops' : ($role === 'wholesale' ? 'Wholesalers' : ucfirst($role).'s') }}</option>@endforeach</select></div><div class="col-md-4"><label for="buyer_search" class="form-label">Recipient or shop name</label><input id="buyer_search" name="buyer_search" class="form-control" value="{{ request('buyer_search') }}" placeholder="Search listed accounts or shops" maxlength="100"></div><div class="col-md-2"><button class="btn btn-outline-primary w-100">Find recipient</button></div></div><p class="small text-muted mt-2 mb-0">Choose a district, then a role to find its active, approved registered accounts. Up to 50 matches; narrow by name when needed.</p></form>
<a href="{{ route('handoffs.index') }}" class="btn btn-outline-primary mb-3">Review handoff proposals</a>
@if($buyerDistrictId)<p class="small {{ $eligibleBuyers->isEmpty() ? 'text-danger' : 'text-muted' }}">{{ $eligibleBuyers->count() }} matching {{ $buyerRole === 'retail' ? 'retail shops' : ($buyerRole === 'wholesale' ? 'wholesalers' : 'collectors') }} in {{ $districts->firstWhere('id',$buyerDistrictId)?->name }}. @if($eligibleBuyers->isEmpty())Try another district, role or name.@endif</p>@endif
@if(!$buyerDistrictId || $eligibleBuyers->isEmpty())
<div class="alert alert-info" role="status">Handoff buttons become available after you choose a recipient district and role, then press <strong>Find recipient</strong> to find an approved registered buyer. @if($buyerDistrictId && $eligibleBuyers->isEmpty())No buyer matches these filters; clear the name search or choose another district or role.@endif</div>
@endif
@if($user->isCollector())
<div class="card card-body mb-4"><h2 class="h5">Collect from a farmer</h2><p>Enter the agreed quantity and price, upload the handwritten agreement, and collect the lot without a farmer login.</p><a href="{{ route('vendor.farm-purchase') }}" class="btn btn-primary align-self-start mb-3">Record farmer purchase</a>
@forelse($collectionBatches as $lot)
<div class="border-top py-3"><strong>{{ $lot->batch_code }} · {{ $lot->crop->name }}</strong><div class="small text-muted">{{ $lot->farmer->name }} · {{ $lot->available_quantity }} {{ $lot->unit }}</div><a href="{{ route('vendor.farm-purchase',['batch_id'=>$lot->id]) }}" class="btn btn-outline-primary btn-sm mt-2">Upload agreement & collect this lot</a></div>
@empty
<p class="small text-muted">No existing farm lots available. Record a purchase to create a new lot.</p>
@endforelse
@if($collectionBatches->isNotEmpty())
{{ $collectionBatches->links() }}
@endif
</div>
@endif
<div class="row g-3 mb-4 align-items-center">
    <div class="col-md-8">
        <div>
            <h2 class="fw-bold mb-1" style="color: var(--kb-text);">
                <i class="bi bi-shop me-2" style="color: var(--kb-primary-light);"></i> Supply Chain Custodian Workspace
            </h2>
            <p class="text-muted mb-0">
                Welcome, <strong>{{ $user->name }}</strong>
                <span class="mx-2" style="color: var(--kb-text-muted);">•</span>
                Role: <span class="badge bg-warning text-dark badge-role">{{ $user->role }}</span>
            </p>
        </div>
    </div>
    <div class="col-md-4 text-md-end mt-3 mt-md-0">
        <button type="button" class="btn btn-primary px-4 py-2" data-bs-toggle="modal" data-bs-target="#recordHandoffModal" style="border-radius: 0.5rem;" @disabled(!$buyerDistrictId || $eligibleBuyers->isEmpty() || ($user->isVendor() && !$user->trading_approved))>
            <i class="bi bi-arrow-left-right me-1"></i> {{ $user->isRetail() ? 'Record retail sale' : 'Propose custody handoff' }}
        </button>
    </div>
</div>

{{-- KPI Cards --}}
<div class="row g-4 mb-4">
    <div class="col-md-3 col-6">
        <div class="kpi-card border-warning bg-warning-kpi">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="kpi-label">In Your Custody</div>
                    <div class="kpi-value">{{ $stats['in_custody_count'] }}</div>
                </div>
                <div class="kpi-icon">
                    <i class="bi bi-inbox"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="kpi-card border-success bg-success-kpi">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="kpi-label">Recorded dispatches</div>
                    <div class="kpi-value" style="color: var(--kb-success);">{{ $stats['total_sales'] }}</div>
                </div>
                <div class="kpi-icon">
                    <i class="bi bi-arrow-right-circle"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="kpi-card border-primary bg-primary-kpi">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="kpi-label">Recorded receipts</div>
                    <div class="kpi-value" style="color: var(--kb-primary-light);">{{ $stats['total_purchases'] }}</div>
                </div>
                <div class="kpi-icon">
                    <i class="bi bi-arrow-left-circle"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="kpi-card border-danger bg-danger-kpi">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="kpi-label">Anomaly Alerts</div>
                    <div class="kpi-value" style="color: var(--kb-danger);">{{ $stats['flagged_alerts'] }}</div>
                </div>
                <div class="kpi-icon">
                    <i class="bi bi-flag"></i>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Inventory Card --}}
<div class="card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-inbox fs-5" style="color: var(--kb-warning);"></i>
                <span class="section-title mb-0">Inventory Under Current Custody</span>
            </div>
            <div class="section-subtitle mt-1">Recorded inventory assigned to your account</div>
        </div>
        <span class="badge bg-light text-dark border d-inline-flex align-items-center">
            <i class="bi bi-box me-1"></i> {{ $batchesInCustody->count() }} active lots
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive" tabindex="0" role="region" aria-label="Scrollable data table">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Batch Code</th>
                        <th>Crop</th>
                        <th>Origin</th>
                        <th>Available Qty</th>
                        <th>Acquisition Price</th>
                        <th>Status</th>
                        <th>Margin</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batchesInCustody as $batch)
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
                            </td>
                            <td><small class="text-muted">{{ number_format($batch->available_quantity, 2) }} {{ $batch->unit }}</small></td>
                            <td>
                                <span class="fw-bold" style="color: var(--kb-text); font-family: monospace;">
                                    ৳ {{ number_format($batch->acquisition_price, 2) }}
                                </span>
                                <small class="text-muted d-block" style="font-size: 0.6875rem;">/ {{ $batch->unit }}</small>
                            </td>
                            <td>
                                <span class="badge bg-info bg-opacity-10 text-info d-inline-flex align-items-center" style="font-size: 0.6875rem;">
                                    <i class="bi bi-hold me-1"></i> In Custody @if($batch->handoffProposals->isNotEmpty())<small class="d-block">Pending handoff · stock reserved</small>@endif
                                </span>
                            </td>
                            <td>
                                @if($batch->custody_markup_percent !== null)
                                    <span class="badge px-2 py-1 d-inline-flex align-items-center" style="font-size: 0.6875rem;" {{ $batch->custody_markup_percent > 30 ? 'style="background: var(--kb-warning); color: #1e293b;"' : 'style="background: var(--kb-bg); color: var(--kb-text); border: 1px solid var(--kb-border);"' }}>
                                        <i class="bi bi-graph-up me-1"></i> +{{ number_format($batch->custody_markup_percent, 1) }}% total
                                    </span>
                                @else
                                    <span class="text-muted small">Farm-gate price</span>
                                @endif
                                <small class="text-muted d-block" style="font-size: 0.6875rem;">
                                    Farm: ৳{{ number_format($batch->initial_farmer_price, 2) }} → Now: ৳{{ number_format($batch->acquisition_price, 2) }}
                                </small>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-primary" style="border-radius: 0.375rem;" data-id="{{ $batch->id }}" data-code="{{ $batch->batch_code }}" data-quantity="{{ $batch->available_quantity }}" data-unit="{{ $batch->unit }}" data-price="{{ $batch->acquisition_price }}" onclick="prepareHandoffModal(this.dataset.id, this.dataset.code, Number(this.dataset.quantity), this.dataset.unit, Number(this.dataset.price))" @disabled(!$buyerDistrictId || $eligibleBuyers->isEmpty() || ($user->isVendor() && !$user->trading_approved))>
                                    <i class="bi bi-arrow-right-circle me-1"></i> Record Handoff
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <i class="bi bi-inbox d-block"></i>
                                    <p class="mb-0">You currently have no active batches in your custody. Batches transferred to you will appear here.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Transaction History --}}
<p class="small text-muted">Movement status follows recorded custody transfers: the sender sees Dispatched and the recipient sees Received after acceptance. Farmer purchases show Received from farmer (collector attested). Unconfirmed older records and voided transfers are labelled separately. Pending proposals do not move custody.</p>
<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-clock-history fs-5" style="color: var(--kb-primary-light);"></i>
                <span class="section-title mb-0">Transaction & Handoff History</span>
            </div>
        </div>
        <span class="badge bg-light text-dark border d-inline-flex align-items-center">
            <i class="bi bi-list me-1"></i> {{ $transactions->total() }} records
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive" tabindex="0" role="region" aria-label="Scrollable data table">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Batch</th>
                        <th>Counterparty</th>
                        <th>Stage</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Spread</th>
                        <th>Markup %</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $tx)
                        <tr class="{{ $tx->is_voided ? 'table-light text-muted' : '' }}">
                            <td><small class="text-muted">{{ $tx->transaction_date->timezone('Asia/Dhaka')->format('d M Y') }}</small></td>
                            <td>
                                <a href="{{ route('public.traceability.show', $tx->batch->batch_code) }}" class="font-monospace text-decoration-none" style="font-size: 0.8125rem; color: var(--kb-primary-light);">
                                    {{ $tx->batch->batch_code }}
                                </a>
                            </td>
                            <td>
                                @if($tx->seller_id === auth()->id())
                                    <div><strong class="d-block" style="font-size: 0.875rem;">{{ $tx->buyer?->name ?? 'Anonymous retail customer' }}</strong></div>
                                    <small class="text-muted" style="font-size: 0.6875rem;">{{ $tx->buyer?->role ?? 'consumer' }}</small>
                                @else
                                    <div><strong class="d-block" style="font-size: 0.875rem;">{{ $tx->seller->name }}</strong></div>
                                    <small class="text-muted" style="font-size: 0.6875rem;">{{ $tx->seller->role }}</small>
                                @endif
                            </td>
                            <td>
                                <code class="small" style="font-size: 0.6875rem;">{{ ucwords(str_replace('_', ' ', $tx->stage)) }}</code>
                            </td>
                            <td><small class="text-muted">{{ number_format($tx->quantity, 2) }} {{ $tx->unit }}</small></td>
                            <td>
                                <span class="fw-semibold" style="font-family: monospace; font-size: 0.875rem;">৳ {{ number_format($tx->price_per_unit, 2) }}</span>
                                <small class="text-muted d-block" style="font-size: 0.6875rem;">/ {{ $tx->unit }}</small>
                            </td>
                            <td>
                                @if($tx->gross_price_spread !== null)
                                    <span class="fw-semibold" style="color: {{ $tx->gross_price_spread > 0 ? 'var(--kb-text)' : 'var(--kb-success)' }}; font-family: monospace;">
                                        {{ $tx->gross_price_spread >= 0 ? '+' : '' }}৳ {{ number_format($tx->gross_price_spread, 2) }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($tx->markup_percent !== null)
                                    <span class="badge px-2 py-1 d-inline-flex align-items-center" style="font-size: 0.6875rem;" {{ $tx->markup_percent > 30 ? 'style="background: var(--kb-warning); color: #1e293b;"' : 'style="background: var(--kb-bg); color: var(--kb-text); border: 1px solid var(--kb-border);' }}>
                                        {{ number_format($tx->markup_percent, 1) }}%
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <x-movement-status :transaction="$tx" :participant="$user" />
                                @if(!$tx->is_voided && $tx->validation_status === 'flagged')
                                    <div class="small text-danger mt-1"><i class="bi bi-flag-fill me-1" aria-hidden="true"></i>Flagged for review</div>
                                @endif
                            </td>
                            <td class="text-end">
                                @if(!$tx->is_voided && $tx->stage === 'retail_sale' && $tx->recorded_by_user_id === auth()->id())
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="openVoidModal({{ $tx->id }}, '{{ $tx->batch->batch_code }}')" style="border-radius: 0.375rem;">
                                        <i class="bi bi-x-circle me-1"></i> Void
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10">
                                <div class="empty-state">
                                    <i class="bi bi-inbox d-block"></i>
                                    <p class="mb-0">No transaction history found.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($transactions->hasPages())
        <div class="card-footer d-flex justify-content-end">
            {{ $transactions->links() }}
        </div>
    @endif
</div>

<!-- Record Handoff Modal -->
<div class="modal fade" id="recordHandoffModal" tabindex="-1" aria-labelledby="recordHandoffModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route(auth()->user()->isFarmer() ? 'farmer.handoff.store' : 'vendor.handoff.store') }}" id="handoffForm">
                <input type="hidden" name="buyer_district_id" value="{{ $buyerDistrictId }}">
                @csrf<input type="hidden" name="request_key" value="{{ \Illuminate\Support\Str::uuid() }}">
                <div class="modal-header" style="background: var(--kb-primary); color: #fff;">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-arrow-left-right me-1"></i> {{ $user->isRetail() ? 'Record retail sale' : 'Propose custody handoff' }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="batchChainInfo" class="p-3 rounded bg-light border mb-4" style="border-color: var(--kb-border); display: none;">
                        <div class="small text-muted mb-2">Batch Chain Context</div>
                        <div class="d-flex justify-content-between flex-wrap gap-2">
                            <span class="fw-semibold" style="color: var(--kb-text);" id="chainBatchCode"></span>
                            <span class="badge bg-light text-dark border" id="chainStageBadge"></span>
                        </div>
                        <div class="row g-2 mt-2 small">
                            <div class="col-6">
                                <span class="text-muted">Farm-gate Price:</span>
                                <span class="fw-semibold" style="color: var(--kb-success); font-family: monospace;" id="chainFarmPrice"></span>
                            </div>
                            <div class="col-6">
                                <span class="text-muted">Current Price:</span>
                                <span class="fw-semibold" style="color: var(--kb-text); font-family: monospace;" id="chainCurrentPrice"></span>
                            </div>
                            <div class="col-6">
                                <span class="text-muted">Cumulative Markup:</span>
                                <span class="fw-semibold" style="color: var(--kb-warning); font-family: monospace;" id="chainMarkup"></span>
                            </div>
                            <div class="col-6">
                                <span class="text-muted">Handoffs:</span>
                                <span class="fw-semibold" style="color: var(--kb-text);" id="chainHandoffs"></span>
                            </div>
                        </div>
                        @if($batch->traversed_districts ?? [])
                            <div class="mt-2 small text-muted">
                                <i class="bi bi-geo-alt me-1"></i>
                                <span class="fw-semibold" style="color: var(--kb-text);">Route:</span>
                                <span id="chainRoute"></span>
                            </div>
                        @endif
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="modal_batch_id" class="form-label">Select Batch in Custody <span class="text-danger">*</span></label>
                            <select name="batch_id" id="modal_batch_id" class="form-select" required onchange="handleBatchSelectionChange(this)">
                                <option value="">-- Choose Batch --</option>
                                @foreach($batchesInCustody as $b)
                                    <option value="{{ $b->id }}" data-qty="{{ $b->available_quantity }}" data-unit="{{ $b->unit }}" data-price="{{ $b->acquisition_price }}" data-farm-price="{{ $b->initial_farmer_price }}" data-markup="{{ $b->custody_markup_percent ?? 0 }}" data-handoffs="{{ $b->total_handoffs }}" data-route="{{ implode(' → ', $b->traversed_districts ?? []) }}">
                                        {{ $b->batch_code }} — {{ $b->crop->name }} (Avail: {{ $b->available_quantity }} {{ $b->unit }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="buyer_id" class="form-label">{{ $user->isRetail() ? 'Customer (optional)' : 'Transfer recipient *' }}</label>
                            <select name="buyer_id" id="buyer_id" class="form-select" {{ $user->isRetail() ? '' : 'required' }}>
                                <option value="">{{ $user->isRetail() ? 'Anonymous retail customer' : '-- Choose Recipient --' }}</option>
                                @foreach($eligibleBuyers as $eb)
                                    <option value="{{ $eb->id }}">{{ $eb->shop_name ?: $eb->name }} ({{ ucfirst($eb->role) }}){{ $eb->marketplace ? ' · '.$eb->marketplace->name : '' }}{{ $eb->district ? ' · '.$eb->district->name : '' }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">{{ $buyerDistrictId ? 'Showing '.$eligibleBuyers->count().' matching recipients in '.$districts->firstWhere('id',$buyerDistrictId)?->name.'.' : 'Select a recipient district above first.' }} Change district, role or search above to find a listed buyer. The recipient must accept before custody moves.</small>
                        </div>

                        <div class="col-md-6">
                            <label for="stage" class="form-label">Handoff Stage <span class="text-danger">*</span></label>
                            <select name="stage" id="stage" class="form-select"><option value="auto">Automatic — determined by participant roles</option></select><small class="text-muted">Collectors and wholesalers can pass goods through multiple intermediaries. Whole lots retain their farmer provenance.</small>
                        </div>

                        <div class="col-md-6">
                            <label for="modal_district_id" class="form-label">Transfer Market District <span class="text-danger">*</span></label>
                            <select name="district_id" id="modal_district_id" class="form-select" required>
                                @foreach($districts as $d)
                                    <option value="{{ $d->id }}" {{ (($buyerDistrictId ?: auth()->user()->district_id) == $d->id) ? 'selected' : '' }}>
                                        {{ $d->name }} ({{ $d->division }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="modal_quantity" class="form-label">Transfer Quantity <span class="text-danger">*</span></label>
                            <input type="number" step="0.5" name="quantity" id="modal_quantity" class="form-control" placeholder="Qty" required>
                        </div>
                        <div class="col-md-4">
                            <label for="modal_price" class="form-label">Sale Price / Unit (BDT) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text" style="background: #f8fafc;">৳</span>
                                <input type="number" step="0.01" min="0.01" name="price_per_unit" id="modal_price" class="form-control" placeholder="Price" required oninput="calculatePreviewSpread()">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Live Spread Preview</label>
                            <div class="p-2 rounded border text-center small" id="previewSpreadBox" style="background: #f8fafc; border-color: var(--kb-border);">
                                <span class="text-muted">Enter price to preview spread</span>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="modal_market_name" class="form-label">Specific Market / Haat / Warehouse</label>
                            <input type="text" name="market_name" id="modal_market_name" class="form-control" placeholder="e.g. Karwan Bazar Arat #4, Dhaka">
                        </div>

                        <div class="col-12">
                            <label for="modal_note" class="form-label">Handoff Note / Transport Info</label>
                            <textarea name="note" id="modal_note" rows="2" class="form-control" placeholder="e.g. Transported via truck; inspected quality upon receipt."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4" style="border-radius: 0.5rem;">
                        <i class="bi bi-check-circle me-1"></i> {{ $user->isRetail() ? 'Record retail sale' : 'Submit handoff proposal' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Void Modal -->
<div class="modal fade" id="voidTransactionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="voidForm">
                @csrf<input type="hidden" name="request_key" value="{{ \Illuminate\Support\Str::uuid() }}">
                <div class="modal-header" style="background: var(--kb-danger); color: #fff;">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-x-circle me-1"></i> Void Transaction
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="p-3 rounded bg-light border mb-3 small" style="border-color: var(--kb-danger); color: var(--kb-danger);">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        <strong>Warning:</strong> This action is auditable. Voiding requires a mandatory explanation and will restore previous batch custody.
                    </div>
                    <div class="mb-3">
                        <label for="void_reason" class="form-label">Reason for Void / Correction <span class="text-danger">*</span></label>
                        <textarea name="void_reason" id="void_reason" rows="3" class="form-control" placeholder="State reason (e.g. clerical error in price entry, cancelled delivery)" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger px-4" style="border-radius: 0.5rem;">
                        <i class="bi bi-check-circle me-1"></i> Confirm Void
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let currentBatchAcqPrice = 0;

    function prepareHandoffModal(batchId, batchCode, qty, unit, acqPrice) {
        const select = document.getElementById('modal_batch_id');
        select.value = batchId;
        currentBatchAcqPrice = acqPrice;
        handleBatchSelectionChange(select);
        document.getElementById('modal_quantity').value = qty;
        document.getElementById('modal_quantity').max = qty;
        new bootstrap.Modal(document.getElementById('recordHandoffModal')).show();
    }

    function handleBatchSelectionChange(select) {
        const selected = select.options[select.selectedIndex];
        const infoBox = document.getElementById('batchChainInfo');
        if (selected && selected.dataset.qty) {
            document.getElementById('modal_quantity').value = selected.dataset.qty;
            document.getElementById('modal_quantity').max = selected.dataset.qty;
            currentBatchAcqPrice = parseFloat(selected.dataset.price || 0);
            calculatePreviewSpread();

            // Show chain context
            infoBox.style.display = 'block';
            document.getElementById('chainBatchCode').textContent = select.value ? select.options[select.selectedIndex].text.split(' — ')[0] : '';
            document.getElementById('chainFarmPrice').textContent = '৳ ' + parseFloat(selected.dataset.farmPrice || 0).toFixed(2);
            document.getElementById('chainCurrentPrice').textContent = '৳ ' + parseFloat(selected.dataset.price || 0).toFixed(2);
            const markup = parseFloat(selected.dataset.markup || 0);
            document.getElementById('chainMarkup').textContent = (markup > 0 ? '+' : '') + markup.toFixed(1) + '%';
            document.getElementById('chainHandoffs').textContent = selected.dataset.handoffs || '0';
            document.getElementById('chainRoute').textContent = selected.dataset.route || 'Origin only';
            document.getElementById('chainStageBadge').textContent = 'Step ' + (parseInt(selected.dataset.handoffs || 0) + 1);
        }
    }

    function calculatePreviewSpread() {
        const enteredPrice = parseFloat(document.getElementById('modal_price').value);
        const box = document.getElementById('previewSpreadBox');
        if (!isNaN(enteredPrice) && currentBatchAcqPrice > 0) {
            const spread = enteredPrice - currentBatchAcqPrice;
            const markup = ((spread / currentBatchAcqPrice) * 100).toFixed(1);
            box.textContent = `Spread: BDT ${spread.toFixed(2)} per unit (${markup}% markup). Saved transactions use configured review rules.`;
            box.style.borderColor = 'var(--kb-border)';
        } else {
            box.innerHTML = '<span class="text-muted small">Enter price to preview</span>';
            box.style.borderColor = 'var(--kb-border)';
        }
    }

    function openVoidModal(txId, batchCode) {
        document.getElementById('voidForm').action = `/vendor/transactions/${txId}/void`;
        new bootstrap.Modal(document.getElementById('voidTransactionModal')).show();
    }
</script>
@endpush
