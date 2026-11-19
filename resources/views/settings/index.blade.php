@extends('layouts.app')

@section('page-title', 'Settings')
@section('page', 'settings')

@php
    $user = auth()->user();
    $tone = 'tone-'.(($user->id % 6) + 1);
@endphp

@section('content')
    <div class="page-heading">
        <div>
            <span class="eyebrow">Account</span>
            <h1>Profile &amp; settings</h1>
            <p class="page-subtitle">Manage your personal details, password and workspace appearance.</p>
        </div>
    </div>

    <div class="settings-grid" id="settingsPage">
        <aside class="card profile-card">
            <div class="profile-cover"></div>
            <div class="profile-body">
                <span class="avatar avatar-lg {{ $tone }}" id="settingsAvatar" aria-hidden="true">{{ $user->initials() }}</span>
                <h3 id="settingsProfileName">{{ $user->name }}</h3>
                <div class="profile-role" id="settingsProfileRole">{{ $user->isAdmin() ? 'Administrator' : 'Staff' }}</div>
                <div class="profile-email" id="settingsProfileEmail">{{ $user->email }}</div>

                <div class="profile-meta">
                    <div class="row">
                        <span>Phone</span>
                        <span id="settingsProfilePhone">{{ $user->phone ?: 'Not provided' }}</span>
                    </div>
                    <div class="row">
                        <span>Job title</span>
                        <span id="settingsProfileJob">{{ $user->job_title ?: 'Not provided' }}</span>
                    </div>
                    <div class="row">
                        <span>Member since</span>
                        <span>{{ $user->created_at?->format('M Y') ?? '—' }}</span>
                    </div>
                    <div class="row">
                        <span>Last sign-in</span>
                        <span>{{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'This session' }}</span>
                    </div>
                    <div class="row">
                        <span>Assigned projects</span>
                        <span>{{ $user->assignedProjects()->count() }}</span>
                    </div>
                </div>

                <div class="divider"></div>
                <div class="security-note">
                    <i class="bi bi-shield-check" aria-hidden="true"></i>
                    <span>Your account is protected by role-based access control. Keep your credentials private.</span>
                </div>
            </div>
        </aside>

        <div class="settings-stack">
            <section class="card settings-card">
                <div class="card-header">
                    <h2 class="card-title">Profile information</h2>
                    <p class="card-subtitle">These details appear alongside records you create.</p>
                </div>
                <div class="card-body">
                    <form id="profileForm" novalidate>
                        <div class="form-grid">
                            <div>
                                <label class="form-label" for="settingsName">Full name <span class="req">*</span></label>
                                <input type="text" class="form-control" id="settingsName" name="name" value="{{ $user->name }}" maxlength="120" required>
                                <div class="invalid-feedback" data-error-for="name"></div>
                            </div>
                            <div>
                                <label class="form-label" for="settingsEmail">Email address <span class="req">*</span></label>
                                <input type="email" class="form-control" id="settingsEmail" name="email" value="{{ $user->email }}" maxlength="150" required>
                                <div class="invalid-feedback" data-error-for="email"></div>
                            </div>
                            <div>
                                <label class="form-label" for="settingsPhone">Phone number</label>
                                <input type="text" class="form-control" id="settingsPhone" name="phone" value="{{ $user->phone }}" maxlength="30" placeholder="+977 98…">
                                <div class="invalid-feedback" data-error-for="phone"></div>
                            </div>
                            <div>
                                <label class="form-label" for="settingsJobTitle">Job title</label>
                                <input type="text" class="form-control" id="settingsJobTitle" name="job_title" value="{{ $user->job_title }}" maxlength="100" placeholder="e.g. Project Coordinator">
                                <div class="invalid-feedback" data-error-for="job_title"></div>
                            </div>
                        </div>
                        <div class="alert alert-danger mt-3" data-form-error hidden></div>
                        <div class="settings-actions">
                            <button type="submit" class="btn btn-primary" id="profileSave">
                                <i class="bi bi-check-lg" aria-hidden="true"></i> Save changes
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            <section class="card settings-card">
                <div class="card-header">
                    <h2 class="card-title">Change password</h2>
                    <p class="card-subtitle">Use at least 8 characters with a mix of letters and numbers.</p>
                </div>
                <div class="card-body">
                    <form id="passwordForm" novalidate>
                        <div class="form-grid">
                            <div class="col-span-2">
                                <label class="form-label" for="currentPassword">Current password <span class="req">*</span></label>
                                <div class="password-field">
                                    <input type="password" class="form-control" id="currentPassword" name="current_password" autocomplete="current-password" required>
                                    <button class="password-toggle" type="button" data-password-toggle="currentPassword" aria-label="Show current password">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback" data-error-for="current_password"></div>
                            </div>
                            <div>
                                <label class="form-label" for="newPassword">New password <span class="req">*</span></label>
                                <div class="password-field">
                                    <input type="password" class="form-control" id="newPassword" name="password" autocomplete="new-password" required>
                                    <button class="password-toggle" type="button" data-password-toggle="newPassword" aria-label="Show new password">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback" data-error-for="password"></div>
                            </div>
                            <div>
                                <label class="form-label" for="confirmPassword">Confirm new password <span class="req">*</span></label>
                                <div class="password-field">
                                    <input type="password" class="form-control" id="confirmPassword" name="password_confirmation" autocomplete="new-password" required>
                                    <button class="password-toggle" type="button" data-password-toggle="confirmPassword" aria-label="Show password confirmation">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback" data-error-for="password_confirmation"></div>
                            </div>
                        </div>
                        <div class="alert alert-danger mt-3" data-form-error hidden></div>
                        <div class="settings-actions">
                            <button type="submit" class="btn btn-primary" id="passwordSave">
                                <i class="bi bi-shield-lock" aria-hidden="true"></i> Update password
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            <section class="card settings-card">
                <div class="card-header">
                    <h2 class="card-title">Appearance</h2>
                    <p class="card-subtitle">Choose how Sajilo looks on this device.</p>
                </div>
                <div class="card-body">
                    <div class="appearance-options" role="group" aria-label="Colour theme">
                        <button type="button" class="appearance-option" data-theme-mode="light">
                            <span class="appearance-preview"><span class="bar side"></span><span class="bar main"></span></span>
                            <span><i class="bi bi-sun" aria-hidden="true"></i> Light</span>
                        </button>
                        <button type="button" class="appearance-option" data-theme-mode="dark">
                            <span class="appearance-preview dark"><span class="bar side"></span><span class="bar main"></span></span>
                            <span><i class="bi bi-moon-stars" aria-hidden="true"></i> Dark</span>
                        </button>
                        <button type="button" class="appearance-option" data-theme-mode="system">
                            <span class="appearance-preview system"><span class="bar side"></span><span class="bar main"></span></span>
                            <span><i class="bi bi-display" aria-hidden="true"></i> System</span>
                        </button>
                    </div>
                    <p class="kbd-note mt-2 mb-0">System follows your operating system's light or dark preference automatically.</p>
                </div>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/settings.js') }}"></script>
@endpush
