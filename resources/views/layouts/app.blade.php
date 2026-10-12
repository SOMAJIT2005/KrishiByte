<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="KrishiByte — Agricultural Supply Chain Tracking & Price Transparency for Bangladesh">
    <title>@yield('title', 'KrishiByte') — Agricultural Price Transparency & Traceability</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Leaflet CSS for Maps -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <style>
        :root {
            --kb-primary: #0d6e4e;
            --kb-primary-light: #198754;
            --kb-primary-dark: #0a5a40;
            --kb-accent: #f59e0b;
            --kb-accent-light: #fbbf24;
            --kb-bg: #f1f5f9;
            --kb-surface: #ffffff;
            --kb-border: #e2e8f0;
            --kb-text: #1e293b;
            --kb-text-muted: #64748b;
            --kb-success: #10b981;
            --kb-warning: #f59e0b;
            --kb-danger: #ef4444;
            --kb-info: #3b82f6;
        }

        * { box-sizing: border-box; }

        body {
            background-color: var(--kb-bg);
            color: var(--kb-text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            font-size: 0.9375rem;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Navbar */
        .navbar {
            background-color: var(--kb-primary) !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.06);
            padding: 0.5rem 0;
        }
        .navbar-brand {
            font-weight: 800;
            letter-spacing: -0.5px;
            font-size: 1.25rem;
        }
        .navbar-brand span {
            color: var(--kb-accent);
        }
        .navbar .nav-link {
            color: rgba(255,255,255,0.85);
            font-weight: 500;
            padding: 0.5rem 0.875rem;
            border-radius: 0.5rem;
            margin: 0 0.125rem;
            transition: all 0.15s ease;
        }
        .navbar .nav-link:hover {
            color: #fff;
            background-color: rgba(255,255,255,0.1);
        }
        .navbar .nav-link.active {
            color: #fff;
            background-color: rgba(255,255,255,0.18);
            font-weight: 600;
        }
        .navbar .dropdown-toggle::after {
            margin-left: 0.375rem;
        }

        /* Main layout */
        .main-content {
            flex: 1 0 auto;
            padding-top: 1.5rem;
            padding-bottom: 2rem;
        }

        /* Cards */
        .card {
            background: var(--kb-surface);
            border: 1px solid var(--kb-border);
            border-radius: 0.75rem;
            box-shadow: 0 1px 2px rgba(0,0,0,0.04), 0 1px 1px rgba(0,0,0,0.02);
            transition: box-shadow 0.15s ease;
        }
        .card:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.03);
        }
        .card-header {
            background-color: var(--kb-surface);
            border-bottom: 1px solid var(--kb-border);
            padding: 0.75rem 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        .card-footer {
            background-color: var(--kb-surface);
            border-top: 1px solid var(--kb-border);
            padding: 0.75rem 1.25rem;
        }

        /* KPI Cards */
        .kpi-card {
            background: var(--kb-surface);
            border: 1px solid var(--kb-border);
            border-radius: 0.75rem;
            padding: 1.25rem;
            position: relative;
            overflow: hidden;
        }
        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            border-radius: 0 0 0 0.75rem;
        }
        .kpi-card .kpi-value {
            font-size: 1.75rem;
            font-weight: 800;
            line-height: 1.2;
            color: var(--kb-text);
        }
        .kpi-card .kpi-label {
            font-size: 0.8125rem;
            color: var(--kb-text-muted);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-top: 0.25rem;
        }
        .kpi-card .kpi-icon {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        .kpi-card.border-primary::before { background-color: var(--kb-primary-light); }
        .kpi-card.border-success::before { background-color: var(--kb-success); }
        .kpi-card.border-warning::before { background-color: var(--kb-warning); }
        .kpi-card.border-danger::before { background-color: var(--kb-danger); }
        .kpi-card.border-info::before { background-color: var(--kb-info); }
        .kpi-card.bg-primary-kpi .kpi-icon { background: rgba(25, 135, 84, 0.1); color: var(--kb-primary-light); }
        .kpi-card.bg-success-kpi .kpi-icon { background: rgba(16, 185, 129, 0.1); color: var(--kb-success); }
        .kpi-card.bg-warning-kpi .kpi-icon { background: rgba(245, 158, 11, 0.1); color: var(--kb-warning); }
        .kpi-card.bg-danger-kpi .kpi-icon { background: rgba(239, 68, 68, 0.1); color: var(--kb-danger); }
        .kpi-card.bg-info-kpi .kpi-icon { background: rgba(59, 130, 246, 0.1); color: var(--kb-info); }

        /* Tables */
        .table {
            margin-bottom: 0;
        }
        .table thead th {
            background-color: #f8fafc;
            color: var(--kb-text-muted);
            font-weight: 600;
            font-size: 0.8125rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 0.75rem 1rem;
            border-bottom: 2px solid var(--kb-border);
            white-space: nowrap;
        }
        .table tbody td {
            padding: 0.75rem 1rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--kb-border);
            color: var(--kb-text);
        }
        .table tbody tr:hover {
            background-color: #f8fafc;
        }
        .table tbody tr:last-child td {
            border-bottom: none;
        }
        .table .font-monospace {
            font-family: 'JetBrains Mono', 'Fira Code', 'Consolas', monospace;
            font-size: 0.8125rem;
        }

        /* Badges */
        .badge {
            font-weight: 600;
            padding: 0.35em 0.65em;
            font-size: 0.75rem;
            border-radius: 0.375rem;
        }
        .badge-role {
            font-size: 0.6875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
        }

        /* Buttons */
        .btn {
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            transition: all 0.15s ease;
        }
        .btn-primary {
            background-color: var(--kb-primary);
            border-color: var(--kb-primary);
        }
        .btn-primary:hover {
            background-color: var(--kb-primary-dark);
            border-color: var(--kb-primary-dark);
        }
        .btn-success {
            background-color: var(--kb-success);
            border-color: var(--kb-success);
        }
        .btn-success:hover {
            background-color: #059669;
            border-color: #059669;
        }

        /* Form controls */
        .form-label {
            font-weight: 600;
            color: var(--kb-text);
            margin-bottom: 0.375rem;
        }
        .form-control, .form-select {
            border: 1px solid var(--kb-border);
            border-radius: 0.5rem;
            padding: 0.5rem 0.75rem;
            color: var(--kb-text);
            background-color: var(--kb-surface);
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--kb-primary-light);
            box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.15);
        }
        .form-control.is-invalid, .form-select.is-invalid {
            border-color: var(--kb-danger);
        }
        .form-control.is-invalid:focus, .form-select.is-invalid:focus {
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
        }

        /* Alerts */
        .alert {
            border: none;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            font-weight: 500;
        }
        .alert-success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .alert-info {
            background: #eff6ff;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }

        /* Price display */
        .price-value {
            font-family: 'JetBrains Mono', 'Consolas', monospace;
            font-weight: 700;
        }

        /* Footer */
        .footer {
            background: var(--kb-text);
            color: #94a3b8;
            padding: 2.5rem 0;
            margin-top: 3rem;
            font-size: 0.875rem;
            line-height: 1.7;
        }
        .footer h5 {
            color: #f1f5f9;
            font-weight: 700;
        }
        .footer h6 {
            color: #e2e8f0;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }
        .footer a {
            color: #94a3b8;
            text-decoration: none;
            transition: color 0.15s ease;
        }
        .footer a:hover {
            color: #f1f5f9;
        }

        /* Section headers */
        .section-title {
            font-weight: 700;
            color: var(--kb-text);
            margin-bottom: 0.125rem;
        }
        .section-subtitle {
            font-size: 0.875rem;
            color: var(--kb-text-muted);
        }

        /* Pagination */
        .pagination .page-link {
            border: 1px solid var(--kb-border);
            border-radius: 0.375rem;
            color: var(--kb-text);
            margin: 0 1px;
            padding: 0.5rem 0.75rem;
        }
        .pagination .page-item.active .page-link {
            background-color: var(--kb-primary);
            border-color: var(--kb-primary);
            color: #fff;
        }

        /* Modal */
        .modal-content {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
        }
        .modal-header {
            border-bottom: 1px solid var(--kb-border);
            padding: 1rem 1.25rem;
        }
        .modal-header .modal-title {
            font-weight: 700;
        }
        .modal-body {
            padding: 1.25rem;
        }
        .modal-footer {
            border-top: 1px solid var(--kb-border);
            padding: 0.75rem 1.25rem;
        }

        /* Breadcrumb */
        .breadcrumb {
            background: transparent;
            padding: 0;
            margin-bottom: 1rem;
        }
        .breadcrumb-item a {
            color: var(--kb-primary-light);
            text-decoration: none;
        }
        .breadcrumb-item a:hover {
            text-decoration: underline;
        }
        .breadcrumb-item.active {
            color: var(--kb-text-muted);
        }

        /* Empty state */
        .empty-state {
            padding: 3rem 1rem;
            text-align: center;
            color: var(--kb-text-muted);
        }
        .empty-state i {
            font-size: 2.5rem;
            margin-bottom: 0.75rem;
            opacity: 0.5;
        }

        /* Map container */
        #traceabilityMap {
            border-radius: 0 0 0.75rem 0.75rem;
        }

        /* Chart containers */
        .chart-container {
            position: relative;
        }

        .table-responsive {
            border-radius: 0;
        }

        .small {
            font-size: 0.8125rem;
        }

        .text-success { color: var(--kb-success) !important; }
        .text-warning { color: var(--kb-warning) !important; }
        .text-danger { color: var(--kb-danger) !important; }
        .text-info { color: var(--kb-info) !important; }

        /* Code inline */
        code {
            font-family: 'JetBrains Mono', 'Consolas', monospace;
            font-size: 0.8125rem;
            background: #f1f5f9;
            padding: 0.125rem 0.375rem;
            border-radius: 0.25rem;
        }

        @media (max-width: 768px) {
            .navbar-collapse {
                background: var(--kb-primary-dark);
                margin-top: 0.5rem;
                border-radius: 0.5rem;
                padding: 0.5rem;
            }
            .navbar .nav-link {
                padding: 0.625rem 0.875rem;
            }
        }
    </style>
    @stack('styles')
<link rel="stylesheet" href="{{ asset('css/refined.css') }}">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top" style="background-color: var(--kb-primary);">
        <div class="container">
            <a class="navbar-brand" href="{{ route('public.home') }}">
                <i class="bi bi-flower1 me-1"></i> Krishi<span>Byte</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('public.home') ? 'active' : '' }}" href="{{ route('public.home') }}">
                            <i class="bi bi-house-door me-1"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('public.prices') ? 'active' : '' }}" href="{{ route('public.prices') }}">
                            <i class="bi bi-graph-up me-1"></i> Prices
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('public.traceability*') ? 'active' : '' }}" href="{{ route('public.traceability') }}">
                            <i class="bi bi-route me-1"></i> Traceability
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('public.report*') ? 'active' : '' }}" href="{{ route('public.report') }}">
                            <i class="bi bi-exclamation-triangle me-1"></i> Report
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('public.faq') ? 'active' : '' }}" href="{{ route('public.faq') }}">
                            <i class="bi bi-info-circle me-1"></i> FAQ
                        </a>
                    </li>
                </ul>

                <ul class="navbar-nav ms-auto align-items-lg-center">
                    @auth
                        @if(auth()->user()->isFarmer())
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('farmer.*') ? 'active' : '' }}" href="{{ route('farmer.dashboard') }}">
                                    <i class="bi bi-tractor me-1"></i> Workspace
                                </a>
                            </li>
                        @endif

                        @if(auth()->user()->isVendor())
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('vendor.*') ? 'active' : '' }}" href="{{ route('vendor.dashboard') }}">
                                    <i class="bi bi-shop me-1"></i> Workspace
                                </a>
                            </li>
                        @endif

                        @if(auth()->user()->isAuthority())
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('authority.*') ? 'active' : '' }}" href="{{ route('authority.dashboard') }}">
                                    <i class="bi bi-shield-check me-1"></i> Review Desk
                                </a>
                            </li>
                        @endif

                        @if(auth()->user()->isAdmin())
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.*') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                                    <i class="bi bi-gear me-1"></i> Admin
                                </a>
                            </li>
                        @endif

                        <li class="nav-item"><a class="nav-link" href="{{ route('reports.mine') }}">My reports</a></li>
                        <li class="nav-item dropdown ms-lg-2">
                            <a class="nav-link dropdown-toggle d-flex align-items-center text-white" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle fs-5 me-1"></i>
                                <span class="d-none d-lg-inline ms-1">{{ auth()->user()->name }}</span> <x-verified-badge :user="auth()->user()" />
                                <span class="badge bg-warning text-dark ms-2 badge-role">{{ auth()->user()->role }}</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="userDropdown">
                                <li>
                                    <div class="dropdown-item-text text-muted small mb-1">Logged in as</div>
                                    <div class="dropdown-item-text fw-semibold">{{ auth()->user()->name }}</div>
                                    <div class="dropdown-item-text text-muted small">{{ auth()->user()->email }}</div>
                                </li>
                                <li><hr class="dropdown-divider bg-light"></li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item text-danger text-decoration-none">
                                            <i class="bi bi-box-arrow-right me-1"></i> Sign Out
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link text-white" href="{{ route('login') }}">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Login
                            </a>
                        </li>
                        <li class="nav-item ms-lg-2">
                            <a class="btn btn-warning btn-sm text-dark fw-semibold" href="{{ route('register') }}">
                                <i class="bi bi-person-plus me-1"></i> Register
                            </a>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    <a class="skip-link" href="#main-content">Skip to content</a>
    <main class="main-content" id="main-content">
        <div class="container">
            {{-- Alert Banners --}}
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show mb-4 shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show mb-4 shadow-sm" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('info'))
                <div class="alert alert-info alert-dismissible fade show mb-4 shadow-sm" role="alert">
                    <i class="bi bi-info-circle-fill me-2"></i> {{ session('info') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show mb-4 shadow-sm" role="alert">
                    <i class="bi bi-exclamation-octagon-fill me-1"></i> Please check the form errors:
                    <ul class="mb-0 mt-2 small">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-5">
                    <h5 class="text-white mb-2"><i class="bi bi-flower1 me-1 text-warning"></i> KrishiByte</h5>
                    <p class="small text-secondary mb-3">
                        Transparent agricultural supply chain tracking and fair-margin intelligence for Bangladesh. Connecting growers, aggregators, arats, and retailers with auditable batch journeys.
                    </p>
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <span class="badge bg-secondary border-0">Transparent provenance</span>
                        <span class="badge bg-dark border border-secondary text-warning">Illustrative demo data</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <h6 class="text-white mb-2">Quick Links</h6>
                    <ul class="list-unstyled small mb-0">
                        <li><a href="{{ route('public.prices') }}">Daily Reference Prices</a></li>
                        <li><a href="{{ route('public.traceability') }}">Batch Lookup</a></li>
                        <li><a href="{{ route('public.report') }}">Submit Price Report</a></li>
                        <li><a href="{{ route('public.faq') }}">Methodology & Disclaimers</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h6 class="text-white mb-2">Important Disclaimers</h6>
                    <p class="small text-secondary mb-0">
                        Indicative reference prices are calculated using trimmed means from verified field surveys. This platform is an illustrative transparency tool; anomaly flags indicate statistical deviation requiring review, not definitive proof of wrongdoing.
                    </p>
                </div>
            </div>
            <hr class="border-secondary mt-4 mb-3 opacity-50">
            <div class="d-flex flex-wrap justify-content-between align-items-center small text-secondary">
                <div>&copy; {{ date('Y') }} KrishiByte Project. Built for Bangladesh Agricultural Markets.</div>
                <div>Timezone: Asia/Dhaka | BDT (৳) Currency Standard</div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    @stack('scripts')
</body>
</html>
