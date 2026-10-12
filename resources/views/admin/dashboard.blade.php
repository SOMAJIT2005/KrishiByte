@extends('layouts.app')

@section('title', 'Admin Panel — KrishiByte')

@section('content')
<div class="row g-3 mb-4 align-items-center">
    <div class="col-md-8">
        <div>
            <h2 class="fw-bold mb-1" style="color: var(--kb-text);">
                <i class="bi bi-gear me-2" style="color: var(--kb-primary-light);"></i> System Administration
            </h2>
            <p class="text-muted mb-0">Platform configuration, user accounts, markup rules, and complete audit trail</p>
        </div>

        <div class="section-header">
            <h2>Batch Verification Queue</h2>
            <p>Review unverified batches before they become publicly visible to farmers, middlemen, retailers, and consumers.</p>
        </div>

        <a href="{{ route('admin.batches.verification') }}" class="btn btn-primary">
            <span class="btn-icon">🕵️‍♂️</span> Review Batches for Verification
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
    </div>
    <div class="col-md-4 text-md-end mt-3 mt-md-0">
        <a href="{{ route('admin.audit-logs') }}" class="btn btn-outline-secondary me-2" style="border-radius: 0.5rem; color: var(--kb-text); border-color: var(--kb-border);">
            <i class="bi bi-journal-code me-1"></i> Full Audit Trail
        </a>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRuleModal" style="border-radius: 0.5rem;">
            <i class="bi bi-plus-circle me-1"></i> Add Markup Rule
        </button>
    </div>
</div>

{{-- KPI Cards --}}
<div class="row g-4 mb-4">
    <div class="col-md-3 col-6">
        <div class="kpi-card border-primary bg-primary-kpi">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="kpi-label">Registered Users</div>
                    <div class="kpi-value">{{ $stats['total_users'] }}</div>
                </div>

        <div class="section-header">
            <h2>Batch Verification Queue</h2>
            <p>Review unverified batches before they become publicly visible to farmers, middlemen, retailers, and consumers.</p>
        </div>

        <a href="{{ route('admin.batches.verification') }}" class="btn btn-primary">
            <span class="btn-icon">🕵️‍♂️</span> Review Batches for Verification
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
                <div class="kpi-icon">
                    <i class="bi bi-people"></i>
                </div>

        <div class="section-header">
            <h2>Batch Verification Queue</h2>
            <p>Review unverified batches before they become publicly visible to farmers, middlemen, retailers, and consumers.</p>
        </div>

        <a href="{{ route('admin.batches.verification') }}" class="btn btn-primary">
            <span class="btn-icon">🕵️‍♂️</span> Review Batches for Verification
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
            </div>

        <div class="section-header">
            <h2>Batch Verification Queue</h2>
            <p>Review unverified batches before they become publicly visible to farmers, middlemen, retailers, and consumers.</p>
        </div>

        <a href="{{ route('admin.batches.verification') }}" class="btn btn-primary">
            <span class="btn-icon">🕵️‍♂️</span> Review Batches for Verification
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        </div>

        <div class="section-header">
            <h2>Batch Verification Queue</h2>
            <p>Review unverified batches before they become publicly visible to farmers, middlemen, retailers, and consumers.</p>
        </div>

        <a href="{{ route('admin.batches.verification') }}" class="btn btn-primary">
            <span class="btn-icon">🕵️‍♂️</span> Review Batches for Verification
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
    </div>
    <div class="col-md-3 col-6">
        <div class="kpi-card border-success bg-success-kpi">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="kpi-label">Active Markup Rules</div>
                    <div class="kpi-value" style="color: var(--kb-success);">{{ $stats['active_rules'] }}</div>
                </div>

        <div class="section-header">
            <h2>Batch Verification Queue</h2>
            <p>Review unverified batches before they become publicly visible to farmers, middlemen, retailers, and consumers.</p>
        </div>

        <a href="{{ route('admin.batches.verification') }}" class="btn btn-primary">
            <span class="btn-icon">🕵️‍♂️</span> Review Batches for Verification
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
                <div class="kpi-icon">
                    <i class="bi bi-rule"></i>
                </div>

        <div class="section-header">
            <h2>Batch Verification Queue</h2>
            <p>Review unverified batches before they become publicly visible to farmers, middlemen, retailers, and consumers.</p>
        </div>

        <a href="{{ route('admin.batches.verification') }}" class="btn btn-primary">
            <span class="btn-icon">🕵️‍♂️</span> Review Batches for Verification
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
            </div>

        <div class="section-header">
            <h2>Batch Verification Queue</h2>
            <p>Review unverified batches before they become publicly visible to farmers, middlemen, retailers, and consumers.</p>
        </div>

        <a href="{{ route('admin.batches.verification') }}" class="btn btn-primary">
            <span class="btn-icon">🕵️‍♂️</span> Review Batches for Verification
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        </div>

        <div class="section-header">
            <h2>Batch Verification Queue</h2>
            <p>Review unverified batches before they become publicly visible to farmers, middlemen, retailers, and consumers.</p>
        </div>

        <a href="{{ route('admin.batches.verification') }}" class="btn btn-primary">
            <span class="btn-icon">🕵️‍♂️</span> Review Batches for Verification
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
    </div>
    <div class="col-md-3 col-6">
        <div class="kpi-card border-info bg-info-kpi">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="kpi-label">Monitored Crops</div>
                    <div class="kpi-value" style="color: var(--kb-info);">{{ $stats['registered_crops

            <div class="stat-card">
                <div class="stat-icon">🕵️‍♂️</div>
                <h3>{{ $stats['pending_verification_batches'] ?? 0 }}</h3>
                <p>Pending Verification</p>
            </div>

        <div class="section-header">
            <h2>Batch Verification Queue</h2>
            <p>Review unverified batches before they become publicly visible to farmers, middlemen, retailers, and consumers.</p>
        </div>

        <a href="{{ route('admin.batches.verification') }}" class="btn btn-primary">
            <span class="btn-icon">🕵️‍♂️</span> Review Batches for Verification
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <h3>{{ $stats['verified_batches'] ?? 0 }}</h3>
                <p>Verified Batches</p>'] }}</div>
                </div>

        <div class="section-header">
            <h2>Batch Verification Queue</h2>
            <p>Review unverified batches before they become publicly visible to farmers, middlemen, retailers, and consumers.</p>
        </div>

        <a href="{{ route('admin.batches.verification') }}" class="btn btn-primary">
            <span class="btn-icon">🕵️‍♂️</span> Review Batches for Verification
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
                <div class="kpi-icon">
                    <i class="bi bi-seedling"></i>
                </div>

        <div class="section-header">
            <h2>Batch Verification Queue</h2>
            <p>Review unverified batches before they become publicly visible to farmers, middlemen, retailers, and consumers.</p>
        </div>

        <a href="{{ route('admin.batches.verification') }}" class="btn btn-primary">
            <span class="btn-icon">🕵️‍♂️</span> Review Batches for Verification
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
            </div>

        <div class="section-header">
            <h2>Batch Verification Queue</h2>
            <p>Review unverified batches before they become publicly visible to farmers, middlemen, retailers, and consumers.</p>
        </div>

        <a href="{{ route('admin.batches.verification') }}" class="btn btn-primary">
            <span class="btn-icon">🕵️‍♂️</span> Review Batches for Verification
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        </div>

        <div class="section-header">
            <h2>Batch Verification Queue</h2>
            <p>Review unverified batches before they become publicly visible to farmers, middlemen, retailers, and consumers.</p>
        </div>

        <a href="{{ route('admin.batches.verification') }}" class="btn btn-primary">
            <span class="btn-icon">🕵️‍♂️</span> Review Batches for Verification
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
    </div>
    <div class="col-md-3 col-6">
        <div class="kpi-card border-warning bg-warning-kpi">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="kpi-label">Total Audit Events</div>
                    <div class="kpi-value" style="color: var(--kb-warning);">{{ $stats['total_audit_events'] }}</div>
                </div>

        <div class="section-header">
            <h2>Batch Verification Queue</h2>
            <p>Review unverified batches before they become publicly visible to farmers, middlemen, retailers, and consumers.</p>
        </div>

        <a href="{{ route('admin.batches.verification') }}" class="btn btn-primary">
            <span class="btn-icon">🕵️‍♂️</span> Review Batches for Verification
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
                <div class="kpi-icon">
                    <i class="bi bi-journal-text"></i>
                </div>

        <div class="section-header">
            <h2>Batch Verification Queue</h2>
            <p>Review unverified batches before they become publicly visible to farmers, middlemen, retailers, and consumers.</p>
        </div>

        <a href="{{ route('admin.batches.verification') }}" class="btn btn-primary">
            <span class="btn-icon">🕵️‍♂️</span> Review Batches for Verification
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
            </div>

        <div class="section-header">
            <h2>Batch Verification Queue</h2>
            <p>Review unverified batches before they become publicly visible to farmers, middlemen, retailers, and consumers.</p>
        </div>

        <a href="{{ route('admin.batches.verification') }}" class="btn btn-primary">
            <span class="btn-icon">🕵️‍♂️</span> Review Batches for Verification
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        </div>

        <div class="section-header">
            <h2>Batch Verification Queue</h2>
            <p>Review unverified batches before they become publicly visible to farmers, middlemen, retailers, and consumers.</p>
        </div>

        <a href="{{ route('admin.batches.verification') }}" class="btn btn-primary">
            <span class="btn-icon">🕵️‍♂️</span> Review Batches for Verification
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
    </div>
</div>

{{-- Markup Rules --}}
<div class="card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-rule fs-5" style="color: var(--kb-primary-light);"></i>
                <span class="section-title mb-0">Configurable Fair Markup Threshold Rules</span>
            </div>

        <div class="section-header">
            <h2>Batch Verification Queue</h2>
            <p>Review unverified batches before they become publicly visible to farmers, middlemen, retailers, and consumers.</p>
        </div>

        <a href="{{ route('admin.batches.verification') }}" class="btn btn-primary">
            <span class="btn-icon">🕵️‍♂️</span> Review Batches for Verification
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
            <div class="section-subtitle">Server-evaluated during every custody handoff</div>
        </div>

        <div class="section-header">
            <h2>Batch Verification Queue</h2>
            <p>Review unverified batches before they become publicly visible to farmers, middlemen, retailers, and consumers.</p>
        </div>

        <a href="{{ route('admin.batches.verification') }}" class="btn btn-primary">
            <span class="btn-icon">🕵️‍♂️</span> Review Batches for Verification
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        <span class="badge bg-light text-dark border d-inline-flex align-items-center">
            <i class="bi bi-list me-1"></i> {{ $rules->count() }} rules configured
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Rule</th>
                        <th>Crop Target</th>
                        <th>Stage</th>
                        <th>Max Markup</th>
                        <th>Max Spread</th>
                        <th>Status</th>
                        <th>Policy Rationale</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rules as $r)
                        <tr>
                            <td>
                                <code class="fw-bold" style="color: var(--kb-primary-light); font-family: monospace; font-size: 0.8125rem;">#{{ $r->id }}</code>
                            </td>
                            <td>
                                <strong>{{ $r->crop ? $r->crop->name : 'All Crops (Global)' }}</strong>
                                @if($r->crop)
                                    <small class="text-muted d-block" style="font-size: 0.6875rem;">{{ $r->crop->unit }}</small>
                                @endif
                            </td>
                            <td>
                                <code class="small" style="font-size: 0.6875rem;">{{ ucwords(str_replace('_', ' ', $r->stage)) }}</code>
                            </td>
                            <td>
                                <span class="fw-bold" style="color: var(--kb-warning); font-family: monospace;">
                                    {{ number_format($r->max_markup_percent, 1) }}%
                                </span>
                            </td>
                            <td>
                                @if($r->max_spread_amount)
                                    <span class="fw-semibold" style="font-family: monospace;">৳ {{ number_format($r->max_spread_amount, 2) }}</span>
                                @else
                                    <span class="text-muted small">Unrestricted</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge px-2 py-1 d-inline-flex align-items-center" style="font-size: 0.6875rem;" {{ $r->is_active ? 'style="background: var(--kb-success); color: #fff;"' : 'style="background: var(--kb-bg); color: var(--kb-text); border: 1px solid var(--kb-border);"' }}>
                                    {{ $r->is_active ? 'Active' : 'Disabled' }}
                                </span>
                            </td>
                            <td class="small" style="max-width: 220px; color: var(--kb-text-muted);">{{ $r->explanation }}</td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('admin.rules.toggle', $r->id) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm px-3 py-1 d-inline-flex align-items-center" style="border-radius: 0.375rem;" {{ $r->is_active ? 'class="btn-outline-danger"' : 'class="btn-outline-success"' }}>
                                        <i class="bi bi-toggle{{ $r->is_active ? '-off' : '-on' }} me-1"></i> {{ $r->is_active ? 'Disable' : 'Enable' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <i class="bi bi-rule d-block" style="color: var(--kb-text-muted);"></i>
                                    <p class="mb-0">No markup rules configured. Add a rule to start monitoring price spreads.</p>
                                </div>

        <div class="section-header">
            <h2>Batch Verification Queue</h2>
            <p>Review unverified batches before they become publicly visible to farmers, middlemen, retailers, and consumers.</p>
        </div>

        <a href="{{ route('admin.batches.verification') }}" class="btn btn-primary">
            <span class="btn-icon">🕵️‍♂️</span> Review Batches for Verification
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="section-header">
            <h2>Batch Verification Queue</h2>
            <p>Review unverified batches before they become publicly visible to farmers, middlemen, retailers, and consumers.</p>
        </div>

        <a href="{{ route('admin.batches.verification') }}" class="btn btn-primary">
            <span class="btn-icon">🕵️‍♂️</span> Review Batches for Verification
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
    </div>
</div>

{{-- Users --}}
<div class="card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-people fs-5" style="color: var(--kb-info);"></i>
                <span class="section-title mb-0">User Accounts & RBAC Roles</span>
            </div>

        <div class="section-header">
            <h2>Batch Verification Queue</h2>
            <p>Review unverified batches before they become publicly visible to farmers, middlemen, retailers, and consumers.</p>
        </div>

        <a href="{{ route('admin.batches.verification') }}" class="btn btn-primary">
            <span class="btn-icon">🕵️‍♂️</span> Review Batches for Verification
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        </div>

        <div class="section-header">
            <h2>Batch Verification Queue</h2>
            <p>Review unverified batches before they become publicly visible to farmers, middlemen, retailers, and consumers.</p>
        </div>

        <a href="{{ route('admin.batches.verification') }}" class="btn btn-primary">
            <span class="btn-icon">🕵️‍♂️</span> Review Batches for Verification
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        <span class="badge bg-light text-dark border d-inline-flex align-items-center">
            <i class="bi bi-people me-1"></i> {{ $users->total() }} users
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email / Phone</th>
                        <th>Role</th>
                        <th>District</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th class="text-end">Manage</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $u)
                        <tr>
                            <td>
                                <strong>{{ $u->name }}</strong>
                            </td>
                            <td>
                                <div>{{ $u->email }}</div>
                                @if($u->phone)
                                    <small class="text-muted" style="font-size: 0.6875rem;">{{ $u->phone }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border badge-role">{{ $u->role }}</span>
                            </td>
                            <td>
                                @if($u->district)
                                    <div class="d-flex align-items-center gap-1">
                                        <i class="bi bi-geo-alt" style="color: var(--kb-danger); font-size: 0.8125rem;"></i>
                                        <span class="small">{{ $u->district->name }}</span>
                                    </div>

        <div class="section-header">
            <h2>Batch Verification Queue</h2>
            <p>Review unverified batches before they become publicly visible to farmers, middlemen, retailers, and consumers.</p>
        </div>

        <a href="{{ route('admin.batches.verification') }}" class="btn btn-primary">
            <span class="btn-icon">🕵️‍♂️</span> Review Batches for Verification
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
                                @else
                                    <span class="text-muted small">N/A</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge px-2 py-1 d-inline-flex align-items-center" style="font-size: 0.6875rem;" {{ $u->account_status === 'active' ? 'style="background: var(--kb-success); color: #fff;"' : 'style="background: var(--kb-danger); color: #fff;"' }}>
                                    {{ ucfirst($u->account_status) }}
                                </span>
                            </td>
                            <td><small class="text-muted">{{ $u->created_at->format('d M Y') }}</small></td>
                            <td class="text-end">
                                @if($u->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.users.toggle', $u->id) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm px-3 py-1 d-inline-flex align-items-center" style="border-radius: 0.375rem;" {{ $u->account_status === 'active' ? 'class="btn-outline-danger"' : 'class="btn-outline-success"' }}>
                                            <i class="bi bi-{{ $u->account_status === 'active' ? 'x-circle' : 'check-circle' }} me-1"></i> {{ $u->account_status === 'active' ? 'Suspend' : 'Activate' }}
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="section-header">
            <h2>Batch Verification Queue</h2>
            <p>Review unverified batches before they become publicly visible to farmers, middlemen, retailers, and consumers.</p>
        </div>

        <a href="{{ route('admin.batches.verification') }}" class="btn btn-primary">
            <span class="btn-icon">🕵️‍♂️</span> Review Batches for Verification
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
    </div>
    @if($users->hasPages())
        <div class="card-footer d-flex justify-content-end">
            {{ $users->links() }}
        </div>

        <div class="section-header">
            <h2>Batch Verification Queue</h2>
            <p>Review unverified batches before they become publicly visible to farmers, middlemen, retailers, and consumers.</p>
        </div>

        <a href="{{ route('admin.batches.verification') }}" class="btn btn-primary">
            <span class="btn-icon">🕵️‍♂️</span> Review Batches for Verification
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
    @endif
</div>

{{-- Recent Audit --}}
<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-journal-text fs-5" style="color: var(--kb-text);"></i>
                <span class="section-title mb-0">Recent Audit Log Activity</span>
            </div>

        <div class="section-header">
            <h2>Batch Verification Queue</h2>
            <p>Review unverified batches before they become publicly visible to farmers, middlemen, retailers, and consumers.</p>
        </div>

        <a href="{{ route('admin.batches.verification') }}" class="btn btn-primary">
            <span class="btn-icon">🕵️‍♂️</span> Review Batches for Verification
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        </div>

        <div class="section-header">
            <h2>Batch Verification Queue</h2>
            <p>Review unverified batches before they become publicly visible to farmers, middlemen, retailers, and consumers.</p>
        </div>

        <a href="{{ route('admin.batches.verification') }}" class="btn btn-primary">
            <span class="btn-icon">🕵️‍♂️</span> Review Batches for Verification
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        <a href="{{ route('admin.audit-logs') }}" class="btn btn-sm btn-outline-secondary" style="color: var(--kb-primary); border-color: var(--kb-primary); border-radius: 0.375rem;">
            <i class="bi bi-eye me-1"></i> View Full Trail
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Time (Asia/Dhaka)</th>
                        <th>Actor</th>
                        <th>Action</th>
                        <th>Target</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentLogs as $log)
                        <tr>
                            <td>
                                <div class="small" style="font-size: 0.8125rem;">
                                    <strong>{{ $log->created_at->setTimezone('Asia/Dhaka')->format('d M Y') }}</strong>
                                    <div class="text-muted" style="font-size: 0.6875rem;">{{ $log->created_at->setTimezone('Asia/Dhaka')->format('h:i:s A') }}</div>
                                </div>

        <div class="section-header">
            <h2>Batch Verification Queue</h2>
            <p>Review unverified batches before they become publicly visible to farmers, middlemen, retailers, and consumers.</p>
        </div>

        <a href="{{ route('admin.batches.verification') }}" class="btn btn-primary">
            <span class="btn-icon">🕵️‍♂️</span> Review Batches for Verification
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
                            </td>
                            <td>
                                <strong>{{ $log->user ? $log->user->name : 'System' }}</strong>
                                <small class="text-muted d-block" style="font-size: 0.6875rem;">{{ $log->user ? $log->user->role : 'System' }}</small>
                            </td>
                            <td>
                                <code>{{ $log->action }}</code>
                            </td>
                            <td>
                                @if($log->target_type)
                                    <code class="small" style="font-size: 0.6875rem;">{{ class_basename($log->target_type) }} #{{ $log->target_id }}</code>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td class="small" style="max-width: 280px; color: var(--kb-text-muted);">{{ $log->notes }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="section-header">
            <h2>Batch Verification Queue</h2>
            <p>Review unverified batches before they become publicly visible to farmers, middlemen, retailers, and consumers.</p>
        </div>

        <a href="{{ route('admin.batches.verification') }}" class="btn btn-primary">
            <span class="btn-icon">🕵️‍♂️</span> Review Batches for Verification
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
    </div>
</div>

{{-- Add Rule Modal --}}
<div class="modal fade" id="createRuleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.rules.store') }}">
                @csrf
                <div class="modal-header" style="background: var(--kb-success); color: #fff;">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-plus-circle me-1"></i> Add Markup Threshold Rule
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

        <div class="section-header">
            <h2>Batch Verification Queue</h2>
            <p>Review unverified batches before they become publicly visible to farmers, middlemen, retailers, and consumers.</p>
        </div>

        <a href="{{ route('admin.batches.verification') }}" class="btn btn-primary">
            <span class="btn-icon">🕵️‍♂️</span> Review Batches for Verification
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="rule_crop_id" class="form-label">Target Crop (Optional)</label>
                        <select name="crop_id" id="rule_crop_id" class="form-select">
                            <option value="">All Crops (Universal Baseline)</option>
                            @foreach($crops as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Leave blank to apply as default fallback across all crops.</small>
                    </div>

        <div class="section-header">
            <h2>Batch Verification Queue</h2>
            <p>Review unverified batches before they become publicly visible to farmers, middlemen, retailers, and consumers.</p>
        </div>

        <a href="{{ route('admin.batches.verification') }}" class="btn btn-primary">
            <span class="btn-icon">🕵️‍♂️</span> Review Batches for Verification
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

                    <div class="mb-3">
                        <label for="rule_stage" class="form-label">Supply Chain Stage <span class="text-danger">*</span></label>
                        <select name="stage" id="rule_stage" class="form-select" required>
                            <option value="all">All Stages (Global Baseline)</option>
                            <option value="farmer_to_collector">Farmer → Collector</option>
                            <option value="collector_to_wholesale">Collector → Wholesale Arat</option>
                            <option value="wholesale_to_retail">Wholesale Arat → Retail</option>
                        </select>
                    </div>

        <div class="section-header">
            <h2>Batch Verification Queue</h2>
            <p>Review unverified batches before they become publicly visible to farmers, middlemen, retailers, and consumers.</p>
        </div>

        <a href="{{ route('admin.batches.verification') }}" class="btn btn-primary">
            <span class="btn-icon">🕵️‍♂️</span> Review Batches for Verification
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="max_markup_percent" class="form-label">Max Markup % <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.5" name="max_markup_percent" id="max_markup_percent" class="form-control" placeholder="e.g. 25.0" required>
                                <span class="input-group-text" style="background: #f8fafc;">%</span>
                            </div>

        <div class="section-header">
            <h2>Batch Verification Queue</h2>
            <p>Review unverified batches before they become publicly visible to farmers, middlemen, retailers, and consumers.</p>
        </div>

        <a href="{{ route('admin.batches.verification') }}" class="btn btn-primary">
            <span class="btn-icon">🕵️‍♂️</span> Review Batches for Verification
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
                        </div>

        <div class="section-header">
            <h2>Batch Verification Queue</h2>
            <p>Review unverified batches before they become publicly visible to farmers, middlemen, retailers, and consumers.</p>
        </div>

        <a href="{{ route('admin.batches.verification') }}" class="btn btn-primary">
            <span class="btn-icon">🕵️‍♂️</span> Review Batches for Verification
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
                        <div class="col-6">
                            <label for="max_spread_amount" class="form-label">Max Spread (BDT)</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background: #f8fafc;">৳</span>
                                <input type="number" step="0.5" name="max_spread_amount" id="max_spread_amount" class="form-control" placeholder="Optional limit in BDT">
                            </div>

        <div class="section-header">
            <h2>Batch Verification Queue</h2>
            <p>Review unverified batches before they become publicly visible to farmers, middlemen, retailers, and consumers.</p>
        </div>

        <a href="{{ route('admin.batches.verification') }}" class="btn btn-primary">
            <span class="btn-icon">🕵️‍♂️</span> Review Batches for Verification
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
                        </div>

        <div class="section-header">
            <h2>Batch Verification Queue</h2>
            <p>Review unverified batches before they become publicly visible to farmers, middlemen, retailers, and consumers.</p>
        </div>

        <a href="{{ route('admin.batches.verification') }}" class="btn btn-primary">
            <span class="btn-icon">🕵️‍♂️</span> Review Batches for Verification
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
                    </div>

        <div class="section-header">
            <h2>Batch Verification Queue</h2>
            <p>Review unverified batches before they become publicly visible to farmers, middlemen, retailers, and consumers.</p>
        </div>

        <a href="{{ route('admin.batches.verification') }}" class="btn btn-primary">
            <span class="btn-icon">🕵️‍♂️</span> Review Batches for Verification
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

                    <div class="mb-3">
                        <label for="rule_explanation" class="form-label">Policy Rationale / Explanation <span class="text-danger">*</span></label>
                        <textarea name="explanation" id="rule_explanation" rows="2" class="form-control" required placeholder="e.g. Fair margin cap agreed with Cold Storage Association for seed potatoes."></textarea>
                    </div>

        <div class="section-header">
            <h2>Batch Verification Queue</h2>
            <p>Review unverified batches before they become publicly visible to farmers, middlemen, retailers, and consumers.</p>
        </div>

        <a href="{{ route('admin.batches.verification') }}" class="btn btn-primary">
            <span class="btn-icon">🕵️‍♂️</span> Review Batches for Verification
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
                </div>

        <div class="section-header">
            <h2>Batch Verification Queue</h2>
            <p>Review unverified batches before they become publicly visible to farmers, middlemen, retailers, and consumers.</p>
        </div>

        <a href="{{ route('admin.batches.verification') }}" class="btn btn-primary">
            <span class="btn-icon">🕵️‍♂️</span> Review Batches for Verification
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4" style="border-radius: 0.5rem;">
                        <i class="bi bi-save me-1"></i> Save Rule
                    </button>
                </div>

        <div class="section-header">
            <h2>Batch Verification Queue</h2>
            <p>Review unverified batches before they become publicly visible to farmers, middlemen, retailers, and consumers.</p>
        </div>

        <a href="{{ route('admin.batches.verification') }}" class="btn btn-primary">
            <span class="btn-icon">🕵️‍♂️</span> Review Batches for Verification
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
            </form>
        </div>

        <div class="section-header">
            <h2>Batch Verification Queue</h2>
            <p>Review unverified batches before they become publicly visible to farmers, middlemen, retailers, and consumers.</p>
        </div>

        <a href="{{ route('admin.batches.verification') }}" class="btn btn-primary">
            <span class="btn-icon">🕵️‍♂️</span> Review Batches for Verification
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
    </div>
</div>
@endsection
