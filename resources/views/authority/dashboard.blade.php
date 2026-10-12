@extends('layouts.app')

@section('title', 'Market Monitoring Desk — KrishiByte')

@section('content')
<form method="GET" class="card card-body mb-4"><div class="row g-2 align-items-end"><div class="col-md-8"><label class="form-label" for="chart-crop">Price progression by crop (BDT per {{ $selectedCrop?->unit }})</label><select class="form-select" id="chart-crop" name="crop_id">@foreach($crops as $crop)<option value="{{ $crop->id }}" @selected($crop->id === $selectedCrop?->id)>{{ $crop->name }}</option>@endforeach</select></div><div class="col-md-4"><button class="btn btn-primary">Apply crop filter</button></div></div></form>
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
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-bar-chart-line fs-5" style="color: var(--kb-primary-light);"></i>
                    <span class="section-title mb-0">Stage-Wise Price Progression</span>
                </div>
                <span class="small text-muted">Empirical system data</span>
            </div>
            <div class="card-body">
                <canvas id="stagePriceChart" height="150"></canvas>
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
        const ctx = document.getElementById('stagePriceChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: @json($chartLabels),
                    datasets: [
                        {
                            label: @json('Average price (BDT/'.($selectedCrop?->unit ?? 'unit').')'),
                            data: @json($chartPrices),
                            backgroundColor: 'rgba(25, 135, 84, 0.8)',
                            borderColor: 'rgb(25, 135, 84)',
                            borderWidth: 2,
                            borderRadius: 4,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Average Markup (%)',
                            data: @json($chartMarkups),
                            backgroundColor: 'rgba(245, 158, 11, 0.8)',
                            borderColor: 'rgb(245, 158, 11)',
                            borderWidth: 2,
                            type: 'line',
                            pointRadius: 5,
                            pointBackgroundColor: 'rgb(245, 158, 11)',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            tension: 0.3,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 20,
                                font: { weight: '600', size: 12 }
                            }
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            grid: { color: 'rgba(0,0,0,0.05)' },
                            title: { display: true, text: 'BDT / kg', font: { weight: '600' } }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            grid: { drawOnChartArea: false },
                            title: { display: true, text: 'Markup %', font: { weight: '600' } }
                        }
                    }
                }
            });
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
