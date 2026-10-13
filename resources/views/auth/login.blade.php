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


    </div>
</div>
@endsection
