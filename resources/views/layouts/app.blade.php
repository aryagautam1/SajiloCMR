<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#2563eb">
    <meta name="description" content="Sajilo CRM is a client and project management workspace for tracking relationships, delivery progress, budgets and team workload.">
    <title>@yield('page-title', 'Dashboard') · Sajilo CRM</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="preload" href="{{ asset('assets/fonts/inter-latin-400-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('assets/fonts/inter-latin-600-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
    <script>
        (function () {
            var saved = localStorage.getItem('sajilo-theme') || 'light';
            var dark = saved === 'dark' || (saved === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
            document.documentElement.setAttribute('data-bs-theme', dark ? 'dark' : 'light');
        })();
    </script>
    @stack('head')
</head>
<body data-page="@yield('page', 'dashboard')">
<a class="skip-link" href="#main-content">Skip to main content</a>

<div class="app-shell">
    @include('partials.sidebar')

    <div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>

    <div class="main-shell">
        <header class="topbar">
            <div class="topbar-start">
                <button class="icon-button" id="sidebarToggle" type="button" aria-label="Toggle navigation" aria-controls="sidebar" aria-expanded="false">
                    <i class="bi bi-list" aria-hidden="true"></i>
                </button>

                <a class="brand topbar-mobile-brand" href="{{ route('dashboard') }}">
                    <img src="{{ asset('assets/img/logo-mark.svg') }}" alt="" class="brand-mark" width="30" height="30">
                    <span class="brand-name">Sajilo</span>
                </a>

                <form class="global-search" action="{{ route('clients.index') }}" method="GET" role="search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input type="search" name="q" placeholder="Search clients…" aria-label="Search clients" autocomplete="off">
                    <kbd>/</kbd>
                </form>
            </div>

            <div class="topbar-end">
                <button class="icon-button" id="themeToggle" type="button" aria-label="Switch colour theme" title="Switch colour theme">
                    <i class="bi bi-moon-stars" id="themeIcon" aria-hidden="true"></i>
                </button>

                <div class="dropdown user-menu">
                    <button class="user-trigger" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="visually-hidden">Open user menu</span>
                        <x-user-avatar :user="auth()->user()" size="sm" />
                        <span class="text-start d-none d-sm-block">
                            <span class="user-name d-block">{{ auth()->user()->name }}</span>
                            <span class="user-role d-block">{{ auth()->user()->isAdmin() ? 'Administrator' : 'Staff' }}</span>
                        </span>
                        <i class="bi bi-chevron-down" aria-hidden="true"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li class="dropdown-header">
                            Signed in as<br><span class="fw-600 text-ink">{{ auth()->user()->email }}</span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="{{ route('settings.index') }}">
                                <i class="bi bi-person-gear" aria-hidden="true"></i> Profile &amp; settings
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('clients.index') }}">
                                <i class="bi bi-people" aria-hidden="true"></i> Client directory
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button class="dropdown-item text-danger w-100" type="submit">
                                    <i class="bi bi-box-arrow-right" aria-hidden="true"></i> Sign out
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="app-content" id="main-content">
            @yield('content')
        </main>

        <footer class="app-footer">
            <span>&copy; {{ date('Y') }} Sajilo CRM. Client &amp; project management, made simple.</span>
        </footer>
    </div>
</div>

<div class="toast-stack" id="toastStack" aria-live="polite" aria-atomic="false"></div>

<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center" style="padding: 26px 24px 18px;">
                <span class="confirm-icon" aria-hidden="true"><i class="bi bi-exclamation-triangle"></i></span>
                <h2 class="modal-title" id="confirmModalTitle" style="font-size: 1.02rem; margin: 12px 0 6px;">Confirm action</h2>
                <p id="confirmModalMessage" class="text-muted" style="font-size: .82rem; margin: 0;">This action cannot be undone.</p>
            </div>
            <div class="modal-footer" style="justify-content: center; border-top: 0; padding-top: 4px;">
                <button type="button" class="btn btn-soft" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmModalConfirm">Confirm</button>
            </div>
        </div>
    </div>
</div>

@if (session('success') || session('error'))
    <div id="flashMessages"
         data-success="{{ session('success') }}"
         data-error="{{ session('error') }}"
         hidden></div>
@endif

@php
    $crmUser = [
        'id' => auth()->id(),
        'name' => auth()->user()->name,
        'email' => auth()->user()->email,
        'role' => auth()->user()->role,
        'initials' => auth()->user()->initials(),
    ];
    $crmConfig = [
        'clientStatuses' => config('crm.client_statuses'),
        'projectStatuses' => config('crm.project_statuses'),
        'priorities' => config('crm.priorities'),
        'chartColors' => config('crm.chart_colors'),
        'perPageOptions' => config('crm.per_page_options'),
        'currency' => config('crm.currency'),
        'maxLogoKb' => config('crm.max_logo_kb'),
    ];
@endphp
<script>
    window.CRM = {
        user: @json($crmUser),
        csrf: @json(csrf_token()),
        config: @json($crmConfig),
        routes: {
            dashboard: @json(route('dashboard')),
            clients: @json(route('clients.index')),
            projects: @json(route('projects.index')),
            reports: @json(route('reports.index')),
            settings: @json(route('settings.index')),
            users: @json(auth()->user()->isAdmin() ? route('users.index') : null),
        },
    };
</script>
<script src="{{ asset('assets/vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/vendor/chartjs/chart.umd.js') }}"></script>
<script src="{{ asset('assets/js/core.js') }}"></script>
@stack('scripts')
</body>
</html>
