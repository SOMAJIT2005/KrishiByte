@extends('layouts.app')

@section('title', 'Report Price Gouging — KrishiByte')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div>
                <h2 class="fw-bold mb-1" style="color: var(--kb-text);">
                    <i class="bi bi-exclamation-triangle fs-4" style="color: var(--kb-warning);"></i> Submit a Price-Gouging Report
                </h2>
                <p class="text-muted mb-0 small">Report excessive retail markups or unverified price spikes at kitchen markets across Bangladesh</p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-file-text fs-5" style="color: var(--kb-primary-light);"></i>
                    <span class="section-title mb-0">Incident Information</span>
                </div>
                <span class="text-muted small">Fields marked with * are required</span>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('public.report.store') }}" enctype="multipart/form-data" id="reportForm">
                    @csrf

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="crop_id" class="form-label">Crop / Commodity <span class="text-danger">*</span></label>
                            <select name="crop_id" id="crop_id" class="form-select @error('crop_id') is-invalid @enderror" required>
                                <option value="">-- Choose Crop --</option>
                                @foreach($crops as $crop)
                                    <option value="{{ $crop->id }}" {{ old('crop_id') == $crop->id ? 'selected' : '' }}>
                                        {{ $crop->name }} ({{ $crop->unit }})
                                    </option>
                                @endforeach
                            </select>
                            @error('crop_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="district_id" class="form-label">District <span class="text-danger">*</span></label>
                            <select name="district_id" id="district_id" class="form-select @error('district_id') is-invalid @enderror" required>
                                <option value="">-- Choose District --</option>
                                @foreach($districts as $district)
                                    <option value="{{ $district->id }}" {{ old('district_id') == $district->id ? 'selected' : '' }}>
                                        {{ $district->name }} ({{ $district->division }})
                                    </option>
                                @endforeach
                            </select>
                            @error('district_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label for="market_name" class="form-label">Market / Haat / Stall Location <span class="text-danger">*</span></label>
                            <input type="text" name="market_name" id="market_name" class="form-control @error('market_name') is-invalid @enderror" placeholder="e.g. Karwan Bazar, Stall #14 or Mohakhali Kacha Bazar" value="{{ old('market_name') }}" required>
                            @error('market_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="incident_date" class="form-label">Date Observed <span class="text-danger">*</span></label>
                            <input type="date" name="incident_date" id="incident_date" class="form-control @error('incident_date') is-invalid @enderror" value="{{ old('incident_date', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}" required>
                            @error('incident_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="reported_price" class="form-label">Observed Retail Price (BDT) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text" style="background: #f8fafc;">৳</span>
                                <input type="number" step="0.5" name="reported_price" id="reported_price" class="form-control @error('reported_price') is-invalid @enderror" placeholder="e.g. 120.00" value="{{ old('reported_price') }}" required>
                            </div>
                            @error('reported_price') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="unit" class="form-label">Unit of Measure <span class="text-danger">*</span></label>
                            <select name="unit" id="unit" class="form-select @error('unit') is-invalid @enderror" required>
                                <option value="kg" {{ old('unit') == 'kg' ? 'selected' : '' }}>kg (Kilogram)</option>
                                <option value="piece" {{ old('unit') == 'piece' ? 'selected' : '' }}>piece / hali (হালি)</option>
                                <option value="mon" {{ old('unit') == 'mon' ? 'selected' : '' }}>mon / maund (মণ)</option>
                            </select>
                            @error('unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label for="report_narrative" class="form-label">Description & Incident Details <span class="text-danger">*</span></label>
                            <textarea name="report_narrative" id="report_narrative" rows="4" class="form-control @error('report_narrative') is-invalid @enderror" required placeholder="Describe what occurred: price difference from neighbouring stalls, vendor refusal to display price, receipt issues, etc.">{{ old('report_narrative') }}</textarea>
                            <small class="text-muted">Be specific: vendor name, stall number, what you expected to pay, and what you were charged.</small>
                            @error('report_narrative') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <hr class="my-4" style="border-color: var(--kb-border);">

                    <div class="row g-3 mb-4">
                        <div class="col-md-12">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <button type="button" class="btn btn-outline-secondary btn-sm px-3" id="btnGetLocation" style="border-radius: 0.5rem;">
                                    <i class="bi bi-crosshair me-1"></i> Capture Current Device GPS
                                </button>
                                <span id="locationStatus" class="small text-muted"></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="latitude" class="form-label">Latitude (Optional)</label>
                            <input type="text" name="latitude" id="latitude" class="form-control form-control-sm" value="{{ old('latitude') }}" readonly style="font-family: monospace;" placeholder="23.xxxxx">
                        </div>
                        <div class="col-md-6">
                            <label for="longitude" class="form-label">Longitude (Optional)</label>
                            <input type="text" name="longitude" id="longitude" class="form-control form-control-sm" value="{{ old('longitude') }}" readonly style="font-family: monospace;" placeholder="90.xxxxx">
                        </div>

                        <div class="col-12">
                            <label for="evidence_photo" class="form-label">Attach Photo Evidence (Optional)</label>
                            <input type="file" name="evidence_photo" id="evidence_photo" class="form-control @error('evidence_photo') is-invalid @enderror" accept="image/jpeg,image/png,image/webp">
                            <small class="text-muted">Accepted: JPG/PNG/WebP (Max 2MB). Photos of price tags, stalls, or market boards help investigation.</small>
                            @error('evidence_photo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <hr class="my-4" style="border-color: var(--kb-border);">

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label for="reporter_name" class="form-label">Your Name</label>
                            <input type="text" name="reporter_name" id="reporter_name" class="form-control" placeholder="Optional" value="{{ old('reporter_name', auth()->user()?->name) }}">
                        </div>
                        <div class="col-md-4">
                            <label for="reporter_phone" class="form-label">Phone Number</label>
                            <input type="text" name="reporter_phone" id="reporter_phone" class="form-control" placeholder="+88017XXXXXXXX" value="{{ old('reporter_phone', auth()->user()?->phone) }}">
                        </div>
                        <div class="col-md-4">
                            <label for="reporter_email" class="form-label">Email Address</label>
                            <input type="email" name="reporter_email" id="reporter_email" class="form-control" placeholder="Optional" value="{{ old('reporter_email', auth()->user()?->email) }}">
                        </div>
                    </div>

                    <div class="d-grid mt-2">
                        <button type="submit" class="btn btn-primary btn-lg px-5 py-2" style="border-radius: 0.5rem;">
                            <i class="bi bi-paper-plane me-1"></i> Submit Report for Official Review
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-shield-lock fs-5" style="color: var(--kb-primary-light);"></i>
                <span class="section-title mb-0">Privacy Guarantee</span>
            </div>
            <div class="card-body p-4">
                <div class="small" style="color: var(--kb-text-muted);">
                    <div class="fw-semibold mb-2" style="color: var(--kb-text);">How Your Report Is Protected:</div>
                    <ul class="small mb-3" style="padding-left: 1.25rem;">
                        <li>Enters a <strong>private moderation queue</strong> — not visible to the public</li>
                        <li>Market monitors review within 24-48 hours</li>
                        <li>Only <strong>verified reports</strong> contribute to public price data</li>
                        <li>Your identity stays confidential unless you give explicit consent</li>
                    </ul>
                    <div class="d-flex gap-2 flex-wrap mb-2">
                        <span class="badge bg-success bg-opacity-10 text-success border-0" style="font-size: 0.75rem;">Report Tracking Code</span>
                        <span class="badge bg-info bg-opacity-10 text-info border-0" style="font-size: 0.75rem;">Private Until Verified</span>
                        <span class="badge bg-primary bg-opacity-10 text-primary border-0" style="font-size: 0.75rem;">Auth Review Required</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-lightning fs-5" style="color: var(--kb-warning);"></i>
                <span class="section-title mb-0">What Happens Next</span>
            </div>
            <div class="card-body p-4">
                <ol class="small" style="padding-left: 1.25rem; color: var(--kb-text-muted);">
                    <li class="mb-2">You receive a <strong>unique tracking code</strong> (RPT-YYYYMMDD-XXXXXX)</li>
                    <li class="mb-2">Market monitors review within <strong>24-48 hours</strong></li>
                    <li class="mb-2"><strong>Verified reports</strong> convert to market observations</li>
                    <li>Track status anytime using your <strong>tracking code</strong></li>
                </ol>
            </div>
        </div>

        <div class="card shadow-sm" style="border: 2px dashed var(--kb-border);">
            <div class="card-body p-4 text-center" style="background: #fafafa;">
                <i class="bi bi-emoji-smile d-block fs-1 mb-2" style="color: var(--kb-primary-light);"></i>
                <p class="small text-muted mb-0">Help us keep Bangladesh's food markets fair and transparent. Your report matters.</p>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('btnGetLocation').addEventListener('click', function () {
        const statusSpan = document.getElementById('locationStatus');
        const btn = event.target;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Locating...';

        if (!navigator.geolocation) {
            statusSpan.textContent = 'Geolocation not supported.';
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-crosshair me-1"></i> Capture GPS';
            return;
        }

        statusSpan.textContent = 'Acquiring GPS...';
        navigator.geolocation.getCurrentPosition(
            function (pos) {
                document.getElementById('latitude').value = pos.coords.latitude.toFixed(6);
                document.getElementById('longitude').value = pos.coords.longitude.toFixed(6);
                statusSpan.textContent = '✓ GPS captured successfully';
                statusSpan.classList.add('text-success');
                statusSpan.classList.remove('text-muted');
                btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> GPS Captured';
                btn.classList.remove('btn-outline-secondary');
                btn.classList.add('btn-success');
                btn.disabled = false;
            },
            function (err) {
                statusSpan.textContent = 'Unable to get location: ' + err.message;
                statusSpan.classList.add('text-danger');
                statusSpan.classList.remove('text-muted');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-crosshair me-1"></i> Retry GPS';
            },
            { timeout: 10000, enableHighAccuracy: true }
        );
    });
</script>
@endpush
