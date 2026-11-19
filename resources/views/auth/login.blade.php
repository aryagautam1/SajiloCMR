<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#2563eb">
    <title>Sign in · Sajilo CRM</title>
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
</head>
<body class="auth-body">
<a class="skip-link" href="#login-form">Skip to sign in form</a>

<main class="auth-page">
    <section class="auth-brand" aria-label="About Sajilo CRM">
        <div>
            <a class="brand" href="{{ route('login') }}" aria-label="Sajilo CRM">
                <img src="{{ asset('assets/img/logo-mark.svg') }}" alt="" class="brand-mark" width="40" height="40">
                <span class="brand-text">
                    <span class="brand-name">Sajilo</span>
                    <span class="brand-tag">CRM</span>
                </span>
            </a>
        </div>

        <div class="auth-brand-copy">
            <span class="auth-eyebrow"><i class="bi bi-stars" aria-hidden="true"></i> Client &amp; project management</span>
            <h1>Every client.<br>Every project.<br><span>One clear view.</span></h1>
            <p>Sajilo gives your team a single place to keep client details accurate, track project progress and understand the health of the business at a glance.</p>

            <div class="auth-feature-list">
                <div class="auth-feature">
                    <i class="bi bi-people" aria-hidden="true"></i>
                    <span>A searchable client directory with clear ownership</span>
                </div>
                <div class="auth-feature">
                    <i class="bi bi-kanban" aria-hidden="true"></i>
                    <span>Projects with priorities, deadlines and real progress</span>
                </div>
                <div class="auth-feature">
                    <i class="bi bi-graph-up-arrow" aria-hidden="true"></i>
                    <span>Live dashboards and exportable business reports</span>
                </div>
            </div>
        </div>

        <div class="auth-brand-footer">
            <span>Sajilo CRM · Client &amp; project management</span>
        </div>
    </section>

    <section class="auth-panel">
        <button class="auth-theme-toggle icon-button" id="authThemeToggle" type="button" aria-label="Switch colour theme" title="Switch colour theme">
            <i class="bi bi-moon-stars" id="authThemeIcon" aria-hidden="true"></i>
        </button>

        <div class="auth-card">
            <a class="brand mobile-brand" href="{{ route('login') }}">
                <img src="{{ asset('assets/img/logo-mark.svg') }}" alt="" class="brand-mark" width="36" height="36">
                <span class="brand-text">
                    <span class="brand-name">Sajilo</span>
                    <span class="brand-tag">CRM</span>
                </span>
            </a>

            <span class="eyebrow">Welcome back</span>
            <h2>Sign in to your workspace</h2>
            <p class="auth-sub">Use your Sajilo CRM account to manage clients and projects.</p>

            @if (session('success'))
                <div class="alert alert-success d-flex align-items-start gap-2" role="status">
                    <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger d-flex align-items-start gap-2" role="alert">
                    <i class="bi bi-exclamation-octagon-fill" aria-hidden="true"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST" id="login-form" novalidate>
                @csrf

                <div class="mb-3">
                    <label class="form-label" for="email">Email address</label>
                    <input
                        type="email"
                        class="form-control @error('email') is-invalid @enderror"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="you@sajilocrm.test"
                        autocomplete="email"
                        inputmode="email"
                        required
                        autofocus
                    >
                    @error('email')
                        <div class="invalid-feedback show">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-1">
                    <div class="d-flex align-items-center justify-content-between">
                        <label class="form-label" for="password">Password</label>
                        <span class="auth-help">Minimum 8 characters</span>
                    </div>
                    <div class="password-field">
                        <input
                            type="password"
                            class="form-control @error('password') is-invalid @enderror"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >
                        <button class="password-toggle" type="button" data-password-toggle="password" aria-label="Show password">
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                    @error('password')
                        <div class="invalid-feedback show">{{ $message }}</div>
                    @enderror
                </div>

                <div class="auth-options">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                        <label class="form-check-label" for="remember">Keep me signed in</label>
                    </div>
                    <span class="auth-help">Access is managed by your administrator</span>
                </div>

                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>
                    Sign in
                </button>
            </form>

            <p class="auth-footnote">
                Access is managed by your workspace administrator.<br>
                Contact your administrator if you need an account.
            </p>
        </div>
    </section>
</main>

<script src="{{ asset('assets/js/login.js') }}"></script>
</body>
</html>
