@extends('layouts.app')

@section('page-title', 'Dashboard')
@section('page', 'dashboard')

@php
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $firstName = strtok(auth()->user()->name, ' ');
@endphp

@section('content')
    <section class="welcome-heading">
        <div>
            <span class="welcome-date">{{ now()->format('l, d F Y') }}</span>
            <h1>{{ $greeting }}, {{ $firstName }}.</h1>
            <p>Here is what is happening across your client portfolio today.</p>
        </div>
        <div class="page-actions">
            <a class="btn btn-soft" href="{{ route('clients.index', ['new' => 1]) }}">
                <i class="bi bi-person-plus" aria-hidden="true"></i> Add client
            </a>
            <a class="btn btn-primary" href="{{ route('projects.index', ['new' => 1]) }}">
                <i class="bi bi-plus-lg" aria-hidden="true"></i> New project
            </a>
        </div>
    </section>

    <div class="stat-grid" id="dashboardStats" aria-live="polite">
        <article class="stat-card">
            <div class="stat-card-head">
                <span class="stat-label">Total clients</span>
                <span class="stat-icon tone-primary"><i class="bi bi-people" aria-hidden="true"></i></span>
            </div>
            <div class="stat-value" data-stat="total_clients">—</div>
            <div class="stat-meta" data-stat-meta="total_clients">Loading portfolio…</div>
        </article>

        <article class="stat-card">
            <div class="stat-card-head">
                <span class="stat-label">Active clients</span>
                <span class="stat-icon tone-success"><i class="bi bi-person-check" aria-hidden="true"></i></span>
            </div>
            <div class="stat-value" data-stat="active_clients">—</div>
            <div class="stat-meta" data-stat-meta="active_clients">Loading portfolio…</div>
        </article>

        <article class="stat-card">
            <div class="stat-card-head">
                <span class="stat-label">Total projects</span>
                <span class="stat-icon tone-info"><i class="bi bi-kanban" aria-hidden="true"></i></span>
            </div>
            <div class="stat-value" data-stat="total_projects">—</div>
            <div class="stat-meta" data-stat-meta="total_projects">Loading portfolio…</div>
        </article>

        <article class="stat-card">
            <div class="stat-card-head">
                <span class="stat-label">Ongoing projects</span>
                <span class="stat-icon tone-primary"><i class="bi bi-arrow-repeat" aria-hidden="true"></i></span>
            </div>
            <div class="stat-value" data-stat="ongoing_projects">—</div>
            <div class="stat-meta" data-stat-meta="ongoing_projects">Loading portfolio…</div>
        </article>

        <article class="stat-card">
            <div class="stat-card-head">
                <span class="stat-label">Completed projects</span>
                <span class="stat-icon tone-success"><i class="bi bi-check2-circle" aria-hidden="true"></i></span>
            </div>
            <div class="stat-value" data-stat="completed_projects">—</div>
            <div class="stat-meta" data-stat-meta="completed_projects">Loading portfolio…</div>
        </article>

        <article class="stat-card">
            <div class="stat-card-head">
                <span class="stat-label">Pending projects</span>
                <span class="stat-icon tone-warning"><i class="bi bi-hourglass-split" aria-hidden="true"></i></span>
            </div>
            <div class="stat-value" data-stat="pending_projects">—</div>
            <div class="stat-meta" data-stat-meta="pending_projects">Loading portfolio…</div>
        </article>
    </div>

    <div class="dashboard-grid">
        <section class="card chart-card span-2">
            <div class="card-header d-flex align-items-start justify-content-between gap-3">
                <div>
                    <h2 class="card-title">Project activity</h2>
                    <p class="card-subtitle">Projects created and completed over the last 12 months</p>
                </div>
                <span class="chip"><i class="bi bi-calendar3" aria-hidden="true"></i> 12 months</span>
            </div>
            <div class="card-body">
                <div class="chart-wrap">
                    <canvas id="monthlyChart" aria-label="Monthly project activity chart" role="img"></canvas>
                    <div class="chart-empty" id="monthlyEmpty">
                        <i class="bi bi-bar-chart-line" aria-hidden="true"></i>
                        <span>No project activity has been recorded yet.</span>
                    </div>
                </div>
                <div class="chart-legend">
                    <span class="legend-item"><span class="legend-dot" style="background:#2563eb"></span> Created</span>
                    <span class="legend-item"><span class="legend-dot" style="background:#16a34a"></span> Completed</span>
                </div>
            </div>
        </section>

        <section class="card chart-card">
            <div class="card-header">
                <h2 class="card-title">Project status</h2>
                <p class="card-subtitle">Current distribution across all projects</p>
            </div>
            <div class="card-body">
                <div class="chart-wrap sm">
                    <canvas id="statusChart" aria-label="Project status distribution chart" role="img"></canvas>
                    <div class="chart-empty" id="statusEmpty">
                        <i class="bi bi-pie-chart" aria-hidden="true"></i>
                        <span>No projects to summarise yet.</span>
                    </div>
                </div>
                <div class="chart-legend" id="statusLegend"></div>
            </div>
        </section>
    </div>

    <div class="dashboard-grid">
        <section class="card span-2">
            <div class="card-header d-flex align-items-center justify-content-between gap-3">
                <div>
                    <h2 class="card-title">Recent projects</h2>
                    <p class="card-subtitle">The latest projects added to the workspace</p>
                </div>
                <a class="card-link" href="{{ route('projects.index') }}">View all <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </div>
            <div class="table-responsive">
                <table class="table responsive-table" aria-label="Recently added projects">
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th>Client</th>
                            <th>Status</th>
                            <th>Progress</th>
                        </tr>
                    </thead>
                    <tbody id="recentProjectsBody">
                        <tr class="skeleton-row"><td colspan="4"><div class="skeleton-line"></div></td></tr>
                        <tr class="skeleton-row"><td colspan="4"><div class="skeleton-line short"></div></td></tr>
                        <tr class="skeleton-row"><td colspan="4"><div class="skeleton-line"></div></td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card">
            <div class="card-header d-flex align-items-center justify-content-between gap-3">
                <div>
                    <h2 class="card-title">Recently added clients</h2>
                    <p class="card-subtitle">Newest relationships in the directory</p>
                </div>
                <a class="card-link" href="{{ route('clients.index') }}">View all <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </div>
            <div class="card-body" style="padding: 8px 14px 14px;" id="recentClientsList">
                <div class="p-3"><div class="skeleton-line"></div></div>
                <div class="p-3"><div class="skeleton-line short"></div></div>
                <div class="p-3"><div class="skeleton-line"></div></div>
            </div>
        </section>
    </div>

    <div class="dashboard-grid">
        <section class="card span-2">
            <div class="card-header d-flex align-items-center justify-content-between gap-3">
                <div>
                    <h2 class="card-title">Project progress overview</h2>
                    <p class="card-subtitle">Active work ranked by completion</p>
                </div>
                <a class="card-link" href="{{ route('projects.index') }}">Open projects <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </div>
            <div class="card-body" id="progressOverview" style="padding-top: 8px;">
                <div class="p-3"><div class="skeleton-line"></div></div>
                <div class="p-3"><div class="skeleton-line short"></div></div>
            </div>
        </section>

        <section class="card">
            <div class="card-header">
                <h2 class="card-title">Portfolio snapshot</h2>
                <p class="card-subtitle">A quick health check of the workspace</p>
            </div>
            <div class="card-body" style="padding-top: 8px;">
                <div class="info-line">
                    <span class="info-label">Completion rate</span>
                    <span class="info-value" data-snapshot="completion_rate">—</span>
                </div>
                <div class="info-line">
                    <span class="info-label">Overdue projects</span>
                    <span class="info-value" data-snapshot="overdue_projects">—</span>
                </div>
                <div class="info-line">
                    <span class="info-label">Active budget</span>
                    <span class="info-value" data-snapshot="active_budget">—</span>
                </div>
                <div class="info-line">
                    <span class="info-label">Total portfolio value</span>
                    <span class="info-value" data-snapshot="total_budget">—</span>
                </div>
                <div class="info-line">
                    <span class="info-label">Team members</span>
                    <span class="info-value" data-snapshot="team_members">—</span>
                </div>
                <a class="btn btn-soft btn-sm w-100 mt-3" href="{{ route('reports.index') }}">
                    <i class="bi bi-bar-chart-line" aria-hidden="true"></i> Open reports
                </a>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/dashboard.js') }}"></script>
@endpush
