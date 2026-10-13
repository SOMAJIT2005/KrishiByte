@extends('layouts.app')

@section('title', 'Market Monitoring Desk — KrishiByte')

@section('content')
<form method="GET" class="card card-body mb-4"><div class="row g-2 align-items-end"><div class="col-md-8"><label class="form-label" for="chart-crop">Compare custody stages by crop (BDT per {{ $selectedCrop?->unit }})</label><select class="form-select" id="chart-crop" name="crop_id">@foreach($crops as $crop)<option value="{{ $crop->id }}" @selected($crop->id === $selectedCrop?->id)>{{ $crop->name }}</option>@endforeach</select></div><div class="col-md-4"><button class="btn btn-primary">Apply crop filter</button></div></div></form>
<div class="row g-3 mb-4 align-items-center">
    <div class="col-md-8">
        <div>
            <h2 class="fw-bold mb-1" style="color: var(--kb-text);">
                <i class="bi bi-shield-check me-2" style="color: var(--kb-primary-light);"></i> Market Monitoring Desk
            </h2>
            <p class="text-muted mb-0">Official surveillance queue for price anomalies, supply chain investigations, and citizen report moderation</p>
        </div>
    </div>
    <div class="col-md-4 text-md-end mt-3 mt-md-0">
        @if($stats['pending_flags'] > 0)
            <span class="badge bg-danger px-3 py-2 d-inline-flex align-items-center" style="border-radius: 0.5rem;">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $stats['pending_flags'] }} Flags Require Review
            </span>
        @else
            <span class="badge bg-success px-3 py-2 d-inline-flex align-items-center" style="border-radius: 0.5rem;">
                <i class="bi bi-check-circle-fill me-1"></i> All Clear
            </span>
        @endif
    </div>
</div>

{{-- KPI Cards --}}
<div class="row g-4 mb-4">
    <div class="col-md-3 col-6">
        <div class="kpi-card border-primary bg-primary-kpi">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="kpi-label">Circulating Batches</div>
                    <div class="kpi-value">{{ $stats['total_batches'] }}</div>
                </div>
                <div class="kpi-icon">
                    <i class="bi bi-boxes"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="kpi-card border-info bg-info-kpi">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="kpi-label">Recorded Transactions</div>
                    <div class="kpi-value" style="color: var(--kb-info);">{{ $stats['total_transactions'] }}</div>
                </div>
                <div class="kpi-icon">
                    <i class="bi bi-arrow-left-right"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="kpi-card border-danger bg-danger-kpi">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="kpi-label">Active Anomaly Flags</div>
                    <div class="kpi-value" style="color: var(--kb-danger);">{{ $stats['pending_flags'] }}</div>
                </div>
                <div class="kpi-icon">
                    <i class="bi bi-flag"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="kpi-card border-warning bg-warning-kpi">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="kpi-label">Citizen Reports</div>
                    <div class="kpi-value" style="color: var(--kb-warning);">{{ $stats['pending_reports'] }}</div>
                </div>
                <div class="kpi-icon">
                    <i class="bi bi-megaphone"></i>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Chart + Framework Sidebar --}}
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-bar-chart-line fs-5" style="color: var(--kb-primary-light);"></i>
                    <span class="section-title mb-0">Prices &amp; markup by custody stage</span>
                </div>
                <span class="small text-muted">{{ $stageAverages->sum('count') }} recorded transfers</span>
            </div>
            <div class="card-body">
                <p class="fw-semibold mb-1">{{ $selectedCrop?->name }} · {{ $selectedCrop?->unit }}</p>
                <p id="stage-chart-description" class="small text-muted">Each bar is an average across different batches, not one batch's price journey. All recorded dates in your assigned districts; includes demo and legacy records. Customer sales are excluded.</p>
                @if($stageAverages->isNotEmpty())
                <div class="d-flex gap-2 flex-wrap mb-3" role="group" aria-label="Chart metric">
                    <button type="button" id="stage-price-mode" class="btn btn-primary btn-sm" aria-pressed="true">Price (BDT / {{ $selectedCrop?->unit }})</button>
                    <button type="button" id="stage-markup-mode" class="btn btn-outline-primary btn-sm" aria-pressed="false">Markup (%)</button>
                </div>
                <p id="stage-chart-metric" class="small fw-semibold mb-2" aria-live="polite">Average recorded price (BDT / {{ $selectedCrop?->unit }})</p>
                <p id="stage-chart-missing" class="alert alert-light border small" hidden>No comparable markup values are recorded for these transfers. See the sample counts below.</p>
                <div id="stage-chart-area" style="position:relative;height:{{ max(250, $stageAverages->count() * 52 + 65) }}px"><canvas id="stagePriceChart" role="img" aria-label="Average recorded price by custody stage" aria-describedby="stage-chart-description">Exact values are available in the table below.</canvas></div>
                <details class="mt-3"><summary class="small fw-semibold">Exact values, ranges &amp; sample counts</summary>
                    <div class="table-responsive mt-2" tabindex="0" aria-label="Custody stage summary"><table class="table table-sm small"><thead><tr><th>Stage</th><th>Average price</th><th>Price range</th><th>Average markup</th><th>Transfers</th></tr></thead><tbody>
                    @foreach($stageAverages as $index => $row)<tr><td>{{ $chartLabels[$index] }}</td><td>BDT {{ number_format($row->avg_price,2) }} / {{ $selectedCrop?->unit }}</td><td>{{ number_format($row->min_price,2) }}–{{ number_format($row->max_price,2) }}</td><td>{{ $row->avg_markup === null ? 'Not available' : number_format($row->avg_markup,2).'%' }}<small class="d-block text-muted">{{ $row->markup_count }} comparable records</small></td><td>{{ $row->count }}</td></tr>@endforeach
                    </tbody></table></div>
                </details>
                @else
                <div class="empty-state py-4">No comparable custody transfers for this crop and unit in your assigned districts.</div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-info-circle fs-5" style="color: var(--kb-info);"></i>
                <span class="section-title mb-0">Regulatory Framework</span>
            </div>
            <div class="card-body">
                <div class="small" style="color: var(--kb-text-muted);">
                    <div class="mb-3">
                        <div class="fw-semibold mb-1" style="color: var(--kb-text);">Threshold Principle</div>
                        <p>Configured markup caps serve as operational tripwires. Threshold breaches generate flags for review. They do not prove wrongdoing or automatically freeze custody.</p>
                    </div>
                    <div>
                        <div class="fw-semibold mb-1" style="color: var(--kb-text);">Fair Assessment</div>
                        <p>Always record concrete field inspection findings or vendor invoices in the mandatory review notes before clearing or escalating any flag.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Anomaly Queue --}}
<div class="card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-flag-fill fs-5" style="color: var(--kb-danger);"></i>
                <span class="section-title mb-0">Price Anomaly Surveillance Queue</span>
            </div>
            <div class="section-subtitle">Transactions where measured price spread exceeded established guidelines</div>
        </div>
        <div class="d-flex gap-1 flex-wrap">
            <a href="{{ route('authority.dashboard') }}" class="btn btn-sm px-3 py-1 d-inline-flex align-items-center" {{ !$statusFilter ? 'style="background: var(--kb-primary); color: #fff; border-color: var(--kb-primary);"' : 'style="background: var(--kb-bg); color: var(--kb-text); border-color: var(--kb-border);"' }}>All</a>
            <a href="{{ route('authority.dashboard', ['flag_status' => 'new']) }}" class="btn btn-sm px-3 py-1 d-inline-flex align-items-center" {{ $statusFilter == 'new' ? 'style="background: var(--kb-danger); color: #fff; border-color: var(--kb-danger);"' : 'style="background: var(--kb-bg); color: var(--kb-text); border-color: var(--kb-border);"' }}>New</a>
            <a href="{{ route('authority.dashboard', ['flag_status' => 'escalated']) }}" class="btn btn-sm px-3 py-1 d-inline-flex align-items-center" {{ $statusFilter == 'escalated' ? 'style="background: var(--kb-warning); color: #1e293b; border-color: var(--kb-warning);"' : 'style="background: var(--kb-bg); color: var(--kb-text); border-color: var(--kb-border);"' }}>Escalated</a>
            <a href="{{ route('authority.dashboard', ['flag_status' => 'cleared']) }}" class="btn btn-sm px-3 py-1 d-inline-flex align-items-center" {{ $statusFilter == 'cleared' ? 'style="background: var(--kb-success); color: #fff; border-color: var(--kb-success);"' : 'style="background: var(--kb-bg); color: var(--kb-text); border-color: var(--kb-border);"' }}>Cleared</a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive" tabindex="0" role="region" aria-label="Scrollable data table">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Flag ID</th>
                        <th>Batch Code</th>
                        <th>Vendor Involved</th>
                        <th>Reason</th>
                        <th>Measured</th>
                        <th>Allowed Cap</th>
                        <th>Severity</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($flags as $flag)
                        <tr>
                            <td>
                                <code class="fw-bold" style="color: var(--kb-primary-light); font-family: monospace;">#{{ $flag->id }}</code>
                            </td>
                            <td>
                                <a href="{{ route('public.traceability.show', $flag->batch->batch_code) }}" class="fw-bold text-decoration-none" style="color: var(--kb-primary-light); font-family: monospace; font-size: 0.8125rem;">
                                    {{ $flag->batch->batch_code }}
                                </a>
                                <small class="text-muted d-block" style="font-size: 0.6875rem;">{{ $flag->batch->crop->name }}</small>
                            </td>
                            <td>
                                <strong>{{ $flag->user ? $flag->user->name : 'N/A' }}</strong>
                                <small class="text-muted d-block" style="font-size: 0.6875rem;">{{ $flag->user ? $flag->user->role : '' }}</small>
                            </td>
                            <td>
                                <code class="small" style="color: var(--kb-text-muted);">{{ $flag->reason_code }}</code>
                            </td>
                            <td>
                                <span class="fw-bold" style="color: var(--kb-danger); font-family: monospace;">
                                    {{ number_format($flag->measured_value, 1) }}{{ $flag->reason_code === 'EXCESSIVE_SPREAD' ? ' BDT/'.$flag->transaction->unit : '%' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border" style="font-size: 0.6875rem;">
                                    {{ number_format($flag->threshold_value, 1) }}{{ $flag->reason_code === 'EXCESSIVE_SPREAD' ? ' BDT/'.$flag->transaction->unit : '%' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge px-2 py-1 d-inline-flex align-items-center" style="font-size: 0.6875rem;" {{ $flag->severity === 'high' ? 'style="background: var(--kb-danger); color: #fff;"' : ($flag->severity === 'medium' ? 'style="background: var(--kb-warning); color: #1e293b;"' : 'style="background: var(--kb-bg); color: var(--kb-text); border: 1px solid var(--kb-border);"') }}>
                                    {{ strtoupper($flag->severity) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge px-2 py-1 d-inline-flex align-items-center" style="font-size: 0.6875rem;" {{ $flag->status === 'new' ? 'style="background: var(--kb-danger); color: #fff;"' : ($flag->status === 'cleared' ? 'style="background: var(--kb-success); color: #fff;"' : ($flag->status === 'escalated' ? 'style="background: var(--kb-text); color: #fff;"' : 'style="background: var(--kb-warning); color: #1e293b;"')) }}>
                                    {{ ucfirst($flag->status) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-primary" style="border-radius: 0.375rem; color: var(--kb-primary); border-color: var(--kb-primary);" data-id="{{ $flag->id }}" data-code="{{ $flag->batch->batch_code }}" data-status="{{ $flag->status }}" data-notes="{{ $flag->reviewer_notes }}" onclick="openReviewModal(this.dataset.id, this.dataset.code, this.dataset.status, this.dataset.notes)">
                                    <i class="bi bi-clipboard-check me-1"></i> Review
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="empty-state">
                                    <i class="bi bi-check-circle d-block" style="color: var(--kb-success);"></i>
                                    <p class="mb-0">No anomaly flags matching current filter. All transactions within thresholds.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($flags->hasPages())
        <div class="card-footer d-flex justify-content-end">
            {{ $flags->links() }}
        </div>
    @endif
</div>

{{-- Reports Queue --}}
<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-megaphone fs-5" style="color: var(--kb-warning);"></i>
                <span class="section-title mb-0">Supply Chain Reports</span>
            </div>
            <div class="section-subtitle">Price, quantity, quality, and traceability concerns from all roles</div>
        </div>
        <div class="d-flex gap-1 flex-wrap">
            <a href="{{ route('authority.dashboard') }}" class="btn btn-sm px-3 py-1 d-inline-flex align-items-center" {{ !$reportFilter ? 'style="background: var(--kb-primary); color: #fff; border-color: var(--kb-primary);"' : 'style="background: var(--kb-bg); color: var(--kb-text); border-color: var(--kb-border);"' }}>All</a>
            <a href="{{ route('authority.dashboard', ['report_status' => 'pending']) }}" class="btn btn-sm px-3 py-1 d-inline-flex align-items-center" {{ $reportFilter == 'pending' ? 'style="background: var(--kb-warning); color: #1e293b; border-color: var(--kb-warning);"' : 'style="background: var(--kb-bg); color: var(--kb-text); border-color: var(--kb-border);"' }}>Pending</a>
            <a href="{{ route('authority.dashboard', ['report_status' => 'verified']) }}" class="btn btn-sm px-3 py-1 d-inline-flex align-items-center" {{ $reportFilter == 'verified' ? 'style="background: var(--kb-success); color: #fff; border-color: var(--kb-success);"' : 'style="background: var(--kb-bg); color: var(--kb-text); border-color: var(--kb-border);"' }}>Verified</a>
            <a href="{{ route('authority.dashboard', ['report_status' => 'rejected']) }}" class="btn btn-sm px-3 py-1 d-inline-flex align-items-center" {{ $reportFilter == 'rejected' ? 'style="background: var(--kb-text); color: #fff; border-color: var(--kb-text);"' : 'style="background: var(--kb-bg); color: var(--kb-text); border-color: var(--kb-border);"' }}>Rejected</a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive" tabindex="0" role="region" aria-label="Scrollable data table">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Tracking Code</th>
                        <th>Crop & Market</th>
                        <th>Reported Price</th>
                        <th>Narrative</th>
                        <th>Date</th>
                        <th>Evidence</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reports as $rpt)
                        <tr>
                            <td>
                                <code class="fw-bold" style="color: var(--kb-text); font-family: monospace;">{{ $rpt->tracking_code }} <small class="d-block text-muted">{{ ucfirst($rpt->category) }}</small></code>
                                @if($rpt->is_demo)
                                <span class="badge bg-warning text-dark">Illustrative demo</span>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $rpt->crop->name }}</strong>
                                <div class="d-flex align-items-center gap-1 small">
                                    <i class="bi bi-geo-alt" style="color: var(--kb-danger); font-size: 0.8125rem;"></i>
                                    <span>{{ $rpt->market_name }}, {{ $rpt->district->name }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="fw-bold" style="color: var(--kb-danger); font-family: monospace;">
                                    {{ $rpt->reported_price !== null ? 'BDT '.number_format($rpt->reported_price, 2) : 'Not supplied' }}
                                </span>
                                <small class="text-muted d-block" style="font-size: 0.6875rem;">/ {{ $rpt->unit }}</small>
                            </td>
                            <td style="max-width: 200px;">
                                <details class="small" style="color: var(--kb-text);"><summary>Read report</summary><div class="mt-2">
                                    {{ $rpt->report_narrative }}
                                </div></details>
                            </td>
                            <td><small class="text-muted">{{ $rpt->incident_date->format('d M Y') }}</small></td>
                            <td>
                                @if($rpt->evidence_path)
                                    <a href="{{ route('reports.evidence', $rpt->id) }}" target="_blank" class="btn btn-sm btn-outline-info" style="color: var(--kb-info); border-color: var(--kb-info); border-radius: 0.375rem;">
                                        <i class="bi bi-image me-1"></i> View
                                    </a>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge px-2 py-1 d-inline-flex align-items-center" style="font-size: 0.6875rem;" {{ $rpt->status === 'verified' ? 'style="background: var(--kb-success); color: #fff;"' : ($rpt->status === 'rejected' ? 'style="background: var(--kb-text); color: #fff;"' : 'style="background: var(--kb-warning); color: #1e293b;"') }}>
                                    {{ ucfirst($rpt->status) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-primary" style="border-radius: 0.375rem; color: var(--kb-primary); border-color: var(--kb-primary);" data-id="{{ $rpt->id }}" data-code="{{ $rpt->tracking_code }}" data-status="{{ $rpt->status }}" data-notes="{{ $rpt->moderation_notes }}" onclick="openModerateModal(this.dataset.id, this.dataset.code, this.dataset.status, this.dataset.notes)">
                                    <i class="bi bi-pencil-square me-1"></i> Moderate
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <i class="bi bi-inbox d-block" style="color: var(--kb-text-muted);"></i>
                                    <p class="mb-0">No reports matching current filter.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($reports->hasPages())
        <div class="card-footer d-flex justify-content-end">
            {{ $reports->links() }}
        </div>
    @endif
</div>

{{-- Report Flag Modal --}}
<div class="modal fade" id="reviewFlagModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="flagReviewForm">
                @csrf
                <div class="modal-header" style="background: var(--kb-text); color: #fff;">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-shield-check me-1"></i> Review Price Anomaly Flag
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 small border-0 mb-3" style="background: #eff6ff; color: var(--kb-info); border: none;">
                        Batch: <strong id="modalFlagBatch"></strong>
                    </div>

                    <div class="mb-3">
                        <label for="flag_status" class="form-label">Surveillance Decision <span class="text-danger">*</span></label>
                        <select name="status" id="flag_status" class="form-select" required>
                            <option value="reviewing">Under Ongoing Observation</option>
                            <option value="cleared">Cleared (Justified Transport / Cold Storage Costs)</option>
                            <option value="escalated">Escalated (Referred to Directorate Enforcement Team)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="reviewer_notes" class="form-label">Mandatory Review Findings / Notes <span class="text-danger">*</span></label>
                        <textarea name="reviewer_notes" id="reviewer_notes" rows="3" class="form-control" required placeholder="Detail reason for decision (e.g. verified invoice shows high transport diesel cost; or vendor failed to supply origin voucher)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4" style="border-radius: 0.5rem;">
                        <i class="bi bi-save me-1"></i> Save Decision
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Moderate Report Modal --}}
<div class="modal fade" id="moderateReportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="reportModerateForm">
                @csrf
                <div class="modal-header" style="background: var(--kb-warning); color: #1e293b;">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-pencil-square me-1"></i> Review Supply Chain Report
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 small border-0 mb-3" style="background: #eff6ff; color: var(--kb-info); border: none;">
                        Tracking Code: <strong id="modalReportCode"></strong>
                    </div>

                    <div class="mb-3">
                        <label for="report_status" class="form-label">Moderation Decision <span class="text-danger">*</span></label>
                        <select name="status" id="report_status" class="form-select" required>
                            <option value="verified">Verified (Confirmed by Field Monitor)</option>
                            <option value="rejected">Rejected (Unsubstantiated or Disputed)</option>
                            <option value="duplicate">Duplicate Entry</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="moderation_notes" class="form-label">Official Moderation Notes <span class="text-danger">*</span></label>
                        <textarea name="moderation_notes" id="moderation_notes" rows="3" class="form-control" required placeholder="Detail market officer findings and corrective action taken"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4" style="border-radius: 0.5rem;">
                        <i class="bi bi-save me-1"></i> Submit Moderation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const canvas = document.getElementById('stagePriceChart');
        if (canvas && typeof Chart !== 'undefined') {
            const labels = @json($chartLabels);
            const prices = @json($chartPrices);
            const markups = @json($chartMarkups);
            const samples = @json($stageAverages->pluck('count')->all());
            const markupSamples = @json($stageAverages->pluck('markup_count')->all());
            const minima = @json($stageAverages->pluck('min_price')->all());
            const maxima = @json($stageAverages->pluck('max_price')->all());
            const unit = @json($selectedCrop?->unit ?? 'unit');
            const number = new Intl.NumberFormat('en-BD', {maximumFractionDigits: 2});
            let markupMode = false;
            const chart = new Chart(canvas, {
                type: 'bar',
                data: {labels, datasets: [{
                    label: 'Average recorded price', data: prices,
                    backgroundColor: '#198754', borderRadius: 5,
                    maxBarThickness: 26
                }]},
                options: {
                    indexAxis: 'y', responsive: true, maintainAspectRatio: false, animation: false,
                    interaction: {mode: 'nearest', intersect: true},
                    plugins: {
                        legend: {display: false},
                        tooltip: {callbacks: {
                            label: context => {
                                const value = number.format(context.parsed.x);
                                const count = (markupMode ? markupSamples : samples)[context.dataIndex];
                                return (markupMode ? value + '%' : 'BDT ' + value + ' / ' + unit) + ' · ' + count + ' records';
                            },
                            afterLabel: context => markupMode
                                ? 'Compared with each transfer\'s acquisition price.'
                                : 'Price range: BDT ' + number.format(minima[context.dataIndex]) + '–' + number.format(maxima[context.dataIndex]) + ' / ' + unit
                        }}
                    },
                    scales: {
                        x: {beginAtZero: true, grid: {color: 'rgba(0,0,0,0.06)'},
                            title: {display: true, text: 'Average price (BDT / ' + unit + ')'},
                            ticks: {callback: value => number.format(value)}},
                        y: {grid: {display: false}, ticks: {autoSkip: false, font: {size: 11}}}
                    }
                }
            });
            const priceButton = document.getElementById('stage-price-mode');
            const markupButton = document.getElementById('stage-markup-mode');
            function selectMetric(markup) {
                markupMode = markup;
                chart.data.datasets[0].data = markup ? markups : prices;
                chart.data.datasets[0].label = markup ? 'Average markup' : 'Average recorded price';
                chart.data.datasets[0].backgroundColor = markup
                    ? markups.map(value => value < 0 ? '#2563eb' : '#f59e0b') : '#198754';
                chart.options.scales.x.title.text = markup ? 'Average markup (%)' : 'Average price (BDT / ' + unit + ')';
                for (const [button, active] of [[priceButton, !markup], [markupButton, markup]]) {
                    button.setAttribute('aria-pressed', String(active));
                    button.classList.toggle('btn-primary', active);
                    button.classList.toggle('btn-outline-primary', !active);
                }
                const description = markup ? 'Average markup (%); price decreases are blue.' : 'Average recorded price (BDT / ' + unit + ')';
                document.getElementById('stage-chart-metric').textContent = description;
                canvas.setAttribute('aria-label', description + ' by custody stage');
                const missing = markup && markups.every(value => value === null);
                document.getElementById('stage-chart-missing').hidden = !missing;
                document.getElementById('stage-chart-area').hidden = missing;
                if (!missing) {
                    chart.resize();
                    chart.update();
                }
            }
            priceButton.addEventListener('click', () => selectMetric(false));
            markupButton.addEventListener('click', () => selectMetric(true));
        }
    });

    function openReviewModal(flagId, batchCode, status, notes) {
        document.getElementById('flagReviewForm').action = `/authority/flags/${flagId}/review`;
        document.getElementById('modalFlagBatch').textContent = batchCode;
        document.getElementById('flag_status').value = (status === 'new') ? 'reviewing' : status;
        document.getElementById('reviewer_notes').value = notes || '';
        new bootstrap.Modal(document.getElementById('reviewFlagModal')).show();
    }

    function openModerateModal(rptId, trackingCode, status, notes) {
        document.getElementById('reportModerateForm').action = `/authority/reports/${rptId}/moderate`;
        document.getElementById('modalReportCode').textContent = trackingCode;
        document.getElementById('report_status').value = status;
        document.getElementById('moderation_notes').value = notes || '';
        new bootstrap.Modal(document.getElementById('moderateReportModal')).show();
    }
</script>
@endpush
