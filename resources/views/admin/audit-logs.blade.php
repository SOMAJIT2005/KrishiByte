@extends('layouts.app')

@section('title', 'System Audit Trail — KrishiByte')

@section('content')
<div class="row align-items-center mb-4">
    <div class="col-md-8">
        <h2 class="fw-bold mb-1"><i class="bi bi-journal-code text-success me-2"></i> System Audit Trail</h2>
        <p class="text-muted mb-0">Immutable chronological record of administrative actions, batch creations, custody transfers, and moderation decisions</p>
    </div>
    <div class="col-md-4 text-md-end mt-3 mt-md-0">
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Admin Dashboard
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold"><i class="bi bi-shield-lock text-dark me-2"></i> Audit Event Log</h5>
        <span class="badge bg-light text-dark border">{{ $logs->total() }} Events Logged</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive" tabindex="0" role="region" aria-label="Scrollable data table">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Time (Asia/Dhaka)</th>
                        <th>Actor</th>
                        <th>Action</th>
                        <th>Target</th>
                        <th>IP Address</th>
                        <th>Notes & Context</th>
                        <th>State Changes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="small text-nowrap">
                                <strong>{{ $log->created_at->setTimezone('Asia/Dhaka')->format('d M Y') }}</strong>
                                <div class="text-muted">{{ $log->created_at->setTimezone('Asia/Dhaka')->format('h:i:s A') }}</div>
                            </td>
                            <td>
                                @if($log->user)
                                    <strong>{{ $log->user->name }}</strong>
                                    <div class="small text-muted">{{ $log->user->role }} &bull; {{ $log->user->email }}</div>
                                @else
                                    <span class="badge bg-secondary">System / Guest</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border font-monospace">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="small">
                                @if($log->target_type)
                                    <code>{{ class_basename($log->target_type) }} #{{ $log->target_id }}</code>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="small text-muted">{{ $log->ip_address }}</td>
                            <td class="small text-secondary" style="max-width: 280px;">
                                {{ $log->notes }}
                            </td>
                            <td class="small">
                                @if($log->before_payload || $log->after_payload)
                                    <details>
                                        <summary class="text-primary cursor-pointer small">View Diff</summary>
                                        <div class="p-2 bg-light border rounded mt-1 font-monospace" style="font-size: 11px;">
                                            @if($log->before_payload)
                                                <div class="text-danger">Before: {{ json_encode($log->before_payload) }}</div>
                                            @endif
                                            @if($log->after_payload)
                                                <div class="text-success">After: {{ json_encode($log->after_payload) }}</div>
                                            @endif
                                        </div>
                                    </details>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No audit events recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($logs->hasPages())
        <div class="card-footer bg-white">
            {{ $logs->links() }}
        </div>
    @endif
</div>
@endsection
