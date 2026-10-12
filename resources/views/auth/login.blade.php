@extends('layouts.app')

@section('title', 'Sign In — KrishiByte')

@section('content')
<div class="row justify-content-center py-5">
    <div class="col-md-8 col-lg-6 col-xl-5">
        <div class="card shadow-sm border-0" style="border: 1px solid var(--kb-border);">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="bg-primary-kpi rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 3.5rem; height: 3.5rem; font-size: 1.75rem; color: var(--kb-primary-light);">
                        <i class="bi bi-flower1"></i>
                    </div>
                    <h3 class="fw-bold mb-1" style="color: var(--kb-text);">Sign In to KrishiByte</h3>
                    <p class="text-muted small mb-0">Access your role-based agricultural dashboard</p>
                </div>

                <form method="POST" action="{{ route('login.post') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="you@example.com" required autofocus autocomplete="email">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="current-password">
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input type="checkbox" name="remember" class="form-check-input" id="remember" {{ old('remember') ? 'checked' : '' }}>
                            <label class="form-check-label small" for="remember">Remember my session</label>
                        </div>
                    </div>

                    <div class="d-grid mb-4">
                        <button type="submit" class="btn btn-primary btn-lg px-5" style="border-radius: 0.5rem;">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                        </button>
                    </div>

                    <div class="text-center">
                        <span class="small text-muted">Don't have an account? </span>
                        <a href="{{ route('register') }}" class="text-decoration-none fw-semibold" style="color: var(--kb-primary-light);">Register here</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0 mt-4" style="border: 1px solid var(--kb-border);">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="bg-warning-kpi rounded p-2 d-flex flex-column align-items-center" style="min-width: 2rem;">
                        <i class="bi bi-lightning-charge-fill fs-5" style="color: var(--kb-warning);"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0" style="color: var(--kb-text);">1-Click Demo Review Shortcuts</h6>
                        <small class="text-muted d-block">Click any role to instantly log in and test the dashboard</small>
                    </div>
                </div>
                <div class="row g-2">
                    <div class="col-6 col-md-4">
                        <a href="{{ route('demo.login', 'farmer') }}" class="btn btn-outline-success btn-sm w-100 d-flex align-items-center justify-content-center gap-1" style="border-radius: 0.5rem; color: var(--kb-success); border-color: var(--kb-success);">
                            <i class="bi bi-tractor"></i> Farmer
                        </a>
                    </div>
                    <div class="col-6 col-md-4">
                        <a href="{{ route('demo.login', 'collector') }}" class="btn btn-outline-info btn-sm w-100 d-flex align-items-center justify-content-center gap-1" style="border-radius: 0.5rem; color: var(--kb-info); border-color: var(--kb-info);">
                            <i class="bi bi-truck"></i> Collector
                        </a>
                    </div>
                    <div class="col-6 col-md-4">
                        <a href="{{ route('demo.login', 'wholesale') }}" class="btn btn-outline-primary btn-sm w-100 d-flex align-items-center justify-content-center gap-1" style="border-radius: 0.5rem; color: var(--kb-primary-light); border-color: var(--kb-primary-light);">
                            <i class="bi bi-building"></i> Wholesale
                        </a>
                    </div>
                    <div class="col-6 col-md-4">
                        <a href="{{ route('demo.login', 'retail') }}" class="btn btn-outline-secondary btn-sm w-100 d-flex align-items-center justify-content-center gap-1" style="border-radius: 0.5rem; color: var(--kb-text); border-color: var(--kb-border);">
                            <i class="bi bi-shop"></i> Retailer
                        </a>
                    </div>
                    <div class="col-6 col-md-4">
                        <a href="{{ route('demo.login', 'authority') }}" class="btn btn-outline-danger btn-sm w-100 d-flex align-items-center justify-content-center gap-1" style="border-radius: 0.5rem; color: var(--kb-danger); border-color: var(--kb-danger);">
                            <i class="bi bi-shield-check"></i> Authority
                        </a>
                    </div>
                    <div class="col-6 col-md-4">
                        <a href="{{ route('demo.login', 'admin') }}" class="btn btn-outline-dark btn-sm w-100 d-flex align-items-center justify-content-center gap-1" style="border-radius: 0.5rem; color: var(--kb-text); border-color: var(--kb-border);">
                            <i class="bi bi-gear"></i> Admin
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
