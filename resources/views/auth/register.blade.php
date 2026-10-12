@extends('layouts.app')

@section('title', 'Register — KrishiByte')

@section('content')
<div class="row justify-content-center py-5">
    <div class="col-md-9 col-lg-7 col-xl-6">
        <div class="card shadow-sm border-0" style="border: 1px solid var(--kb-border);">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="bg-warning-kpi rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 3.5rem; height: 3.5rem; font-size: 1.75rem; color: var(--kb-warning);">
                        <i class="bi bi-person-plus"></i>
                    </div>
                    <h3 class="fw-bold mb-1" style="color: var(--kb-text);">Create Your Account</h3>
                    <p class="text-muted small mb-0">Join Bangladesh's transparent agricultural marketplace</p>
                </div>

                <form method="POST" action="{{ route('register.post') }}">
                    @csrf

                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. Md. Ashraful Alam" required autofocus autocomplete="name">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="name@domain.com" required autocomplete="email">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Mobile Number</label>
                            <input type="text" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" placeholder="+88017XXXXXXXX" autocomplete="tel">
                            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="role" class="form-label">Account Role <span class="text-danger">*</span></label>
                            <select name="role" id="role" class="form-select @error('role') is-invalid @enderror" required>
                                <option value="consumer" {{ old('role') == 'consumer' ? 'selected' : '' }}>Citizen / Consumer</option>
                                <option value="farmer" {{ old('role') == 'farmer' ? 'selected' : '' }}>Farmer / Crop Producer</option>
                                <option value="collector" {{ old('role') == 'collector' ? 'selected' : '' }}>Faria / Local Collector</option>
                                <option value="wholesale" {{ old('role') == 'wholesale' ? 'selected' : '' }}>Wholesale Aratdar</option>
                                <option value="retail" {{ old('role') == 'retail' ? 'selected' : '' }}>Retail Vendor</option>
                            </select>
                            <div class="form-text small">Authority & Admin roles require official clearance.</div>
                            @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="district_id" class="form-label">Home District</label>
                            <select name="district_id" id="district_id" class="form-select">
                                <option value="">-- Select District --</option>
                                @foreach($districts as $district)
                                    <option value="{{ $district->id }}" {{ old('district_id') == $district->id ? 'selected' : '' }}>
                                        {{ $district->name }} ({{ $district->division }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <label for="nid_or_trade_license" class="form-label">NID or Trade License No. (Optional)</label>
                            <input type="text" name="nid_or_trade_license" id="nid_or_trade_license" class="form-control" value="{{ old('nid_or_trade_license') }}" placeholder="For vendor verification only">
                        </div>

                        <div class="col-md-6">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password">
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="password_confirmation" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required autocomplete="new-password">
                        </div>
                    </div>

                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary btn-lg px-5" style="border-radius: 0.5rem;">
                            <i class="bi bi-person-check me-1"></i> Complete Registration
                        </button>
                    </div>

                    <div class="text-center">
                        <span class="small text-muted">Already have an account? </span>
                        <a href="{{ route('login') }}" class="text-decoration-none fw-semibold" style="color: var(--kb-primary-light);">Sign in</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
