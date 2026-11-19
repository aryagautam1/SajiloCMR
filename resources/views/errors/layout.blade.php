<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#2563eb">
    <title>@yield('code') · @yield('title') · Sajilo CRM</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
</head>
<body class="auth-body error-body">
<main class="error-page">
    <div class="card error-card">
        <a class="brand justify-content-center" href="{{ auth()->check() ? route('dashboard') : route('login') }}">
            <img src="{{ asset('assets/img/logo-mark.svg') }}" alt="" class="brand-mark" width="38" height="38">
            <span class="brand-text">
                <span class="brand-name">Sajilo</span>
                <span class="brand-tag">CRM</span>
            </span>
        </a>

        <div class="error-code">@yield('code')</div>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>

        <div class="error-actions">
            <a class="btn btn-primary" href="{{ auth()->check() ? route('dashboard') : route('login') }}">
                <i class="bi bi-house-door" aria-hidden="true"></i>
                {{ auth()->check() ? 'Back to dashboard' : 'Go to sign in' }}
            </a>
            <button class="btn btn-soft" type="button" onclick="history.back()">
                <i class="bi bi-arrow-left" aria-hidden="true"></i> Go back
            </button>
        </div>

        <p class="error-help">If this keeps happening, contact your workspace administrator.</p>
    </div>
</main>
</body>
</html>
