@extends('layouts.app')

@section('title', 'Track Price Report — KrishiByte')

@section('content')
<div class="row justify-content-center py-4">
    <div class="col-lg-8">
        <div class="card shadow-sm mb-4">
            <div class="card-body p-4 text-center">
                <h3 class="fw-bold mb-2"><i class="bi bi-search text-success me-2"></i> Track Your Report Status</h3>
                <p class="text-muted">Enter the unique tracking code you received upon submission.</p>

                <form method="GET" action="{{ route('public.report.track') }}" class="mt-3">
                    <div class="input-group input-group-lg">
                        <input type="text" name="code" class="form-control" placeholder="e.g. RPT-20261012-ON001" value="{{ $code }}" required>
                        <button class="btn btn-success" type="submit">Check Status</button>
                    </div>
                </form>
            </div>
        </div>

        @if(!empty($code))
            @if($report)
                @if($report->is_demo)
                <div class="alert alert-warning">Illustrative demo report; this is a synthetic example, not a real allegation or market observation.</div>
                @endif
                <div class="card shadow-sm border-top border-{{ $report->status === 'verified' ? 'success' : ($report->status === 'rejected' ? 'danger' : 'warning') }} border-4">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold font-monospace">{{ $report->tracking_code }}</h5>
                        <span class="badge bg-{{ $report->status === 'verified' ? 'success' : ($report->status === 'rejected' ? 'danger' : 'warning text-dark') }} fs-6 px-3 py-2">
                            Status: {{ ucfirst($report->status) }}
                        </span>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <span class="text-muted small d-block">Reported Crop:</span>
                                <strong>{{ $report->crop->name }}</strong>
                            </div>
                            <div class="col-md-6">
                                <span class="text-muted small d-block">Market Location:</span>
                                <strong>{{ $report->market_name }}, {{ $report->district->name }}</strong>
                            </div>
                            <div class="col-md-6">
                                <span class="text-muted small d-block">Observed Price:</span>
                                <span class="fs-5 fw-bold text-danger">{{ $report->reported_price !== null ? 'BDT '.number_format($report->reported_price, 2) : 'Not supplied' }}</span> / {{ $report->unit }}
                            </div>
                            <div class="col-md-6">
                                <span class="text-muted small d-block">Submission Date:</span>
                                <span>{{ $report->created_at->timezone('Asia/Dhaka')->format('d M Y, h:i A') }}</span>
                            </div>
                            <div class="col-12">
                                <span class="text-muted small d-block">Report Description:</span>
                                <div class="p-3 bg-light rounded text-secondary">Details are private to the submitter and review team.</div>
                            </div>
                        </div>

                        <hr>

                        <h6 class="fw-bold mb-2"><i class="bi bi-shield-check text-primary me-1"></i> Official Moderation Outcome</h6>
                        @if($report->status === 'pending')
                            <div class="alert alert-warning mb-0">
                                <i class="bi bi-hourglass-split me-1"></i> Your report is currently in the authority queue. An agricultural market officer will verify local market arrivals and stall prices.
                            </div>
                        @elseif($report->status === 'verified')
                            <div class="alert alert-success mb-0">
                                <i class="bi bi-check-circle-fill me-1"></i> <strong>Verified:</strong> The review team has verified this report.

                            </div>
                        @else
                            <div class="alert alert-secondary mb-0">
                                <i class="bi bi-x-circle me-1"></i> <strong>{{ ucfirst($report->status) }}:</strong>

                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="alert alert-danger shadow-sm text-center py-4">
                    <i class="bi bi-exclamation-triangle-fill fs-3 d-block mb-2"></i>
                    No report found matching tracking code <code>{{ $code }}</code>. Please verify the code and try again.
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
