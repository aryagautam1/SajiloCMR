@extends('layouts.app')

@section('page-title', 'Reports')
@section('page', 'reports')

@section('content')
    <div class="page-heading">
        <div>
            <span class="eyebrow">Portfolio intelligence</span>
            <h1>Reports</h1>
            <p class="page-subtitle">A live view of delivery performance, team workload and portfolio value across the business.</p>
        </div>
        <div class="page-actions">
            <a class="btn btn-soft" href="{{ route('clients.export') }}">
                <i class="bi bi-download" aria-hidden="true"></i> Clients CSV
            </a>
            <a class="btn btn-soft" href="{{ route('projects.export') }}">
                <i class="bi bi-download" aria-hidden="true"></i> Projects CSV
            </a>
            <button class="icon-button" type="button" id="reportRefresh" title="Refresh report data" aria-label="Refresh report data">
                <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    <div id="reportsPage" aria-live="polite">
        <div class="report-kpis">
            <article class="report-kpi">
                <div class="label">Total projects</div>
                <div class="value" data-report-kpi="total_projects">—</div>
                <div class="hint" data-report-hint="total_projects">Loading…</div>
            </article>
            <article class="report-kpi">
                <div class="label">Completion rate</div>
                <div class="value" data-report-kpi="completion_rate">—</div>
                <div class="hint" data-report-hint="completion_rate">Loading…</div>
            </article>
            <article class="report-kpi">
                <div class="label">Active pipeline value</div>
                <div class="value" data-report-kpi="active_budget">—</div>
                <div class="hint" data-report-hint="active_budget">Loading…</div>
            </article>
            <article class="report-kpi">
                <div class="label">Overdue projects</div>
                <div class="value" data-report-kpi="overdue_projects">—</div>
                <div class="hint" data-report-hint="overdue_projects">Loading…</div>
            </article>
        </div>

        <div class="dashboard-grid">
            <section class="card chart-card span-2">
                <div class="card-header d-flex align-items-start justify-content-between gap-3">
                    <div>
                        <h2 class="card-title">Delivery activity</h2>
                        <p class="card-subtitle">Projects created and completed over the last 12 months</p>
                    </div>
                    <span class="chip" id="reportGenerated">Live snapshot</span>
                </div>
                <div class="card-body">
                    <div class="chart-wrap">
                        <canvas id="reportMonthlyChart" aria-label="Monthly project activity report chart" role="img"></canvas>
                        <div class="chart-empty" id="reportMonthlyEmpty">
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
                    <p class="card-subtitle">Where every project currently sits</p>
                </div>
                <div class="card-body">
                    <div class="chart-wrap sm">
                        <canvas id="reportStatusChart" aria-label="Project status report chart" role="img"></canvas>
                        <div class="chart-empty" id="reportStatusEmpty">
                            <i class="bi bi-pie-chart" aria-hidden="true"></i>
                            <span>No projects to summarise yet.</span>
                        </div>
                    </div>
                    <div class="chart-legend" id="reportStatusLegend"></div>
                </div>
            </section>
        </div>

        <div class="dashboard-grid equal">
            <section class="card chart-card">
                <div class="card-header">
                    <h2 class="card-title">Team workload</h2>
                    <p class="card-subtitle">Assigned projects by team member</p>
                </div>
                <div class="card-body">
                    <div class="chart-wrap sm">
                        <canvas id="reportTeamChart" aria-label="Team workload chart" role="img"></canvas>
                        <div class="chart-empty" id="reportTeamEmpty">
                            <i class="bi bi-people" aria-hidden="true"></i>
                            <span>No project assignments yet.</span>
                        </div>
                    </div>
                    <div class="chart-legend">
                        <span class="legend-item"><span class="legend-dot" style="background:#2563eb"></span> Active</span>
                        <span class="legend-item"><span class="legend-dot" style="background:#16a34a"></span> Completed</span>
                        <span class="legend-item"><span class="legend-dot" style="background:#cbd5e1"></span> Other</span>
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card-header">
                    <h2 class="card-title">Portfolio value</h2>
                    <p class="card-subtitle">Budget distribution by project status</p>
                </div>
                <div class="card-body">
                    <div class="budget-bars" id="reportBudgetBars">
                        <div class="skeleton-line"></div>
                        <div class="skeleton-line short"></div>
                        <div class="skeleton-line"></div>
                    </div>
                    <div class="divider"></div>
                    <div class="info-line">
                        <span class="info-label">Total portfolio budget</span>
                        <span class="info-value" data-report-total="total_budget">—</span>
                    </div>
                    <div class="info-line">
                        <span class="info-label">Completed value</span>
                        <span class="info-value" data-report-total="completed_budget">—</span>
                    </div>
                    <div class="info-line">
                        <span class="info-label">Average progress</span>
                        <span class="info-value" data-report-total="average_progress">—</span>
                    </div>
                </div>
            </section>
        </div>

        <div class="dashboard-grid">
            <section class="card data-card span-2">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div>
                        <h2 class="card-title">Top clients by portfolio</h2>
                        <p class="card-subtitle">Clients with the most project activity</p>
                    </div>
                    <a class="btn btn-ghost btn-sm" href="{{ route('clients.index') }}">View all</a>
                </div>
                <div class="table-responsive">
                    <table class="table responsive-table" aria-label="Top clients by project count">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Projects</th>
                                <th>Active</th>
                                <th>Completed</th>
                                <th>Portfolio value</th>
                            </tr>
                        </thead>
                        <tbody id="reportTopClientsBody">
                            <tr class="skeleton-row"><td colspan="5"><div class="skeleton-line"></div></td></tr>
                            <tr class="skeleton-row"><td colspan="5"><div class="skeleton-line short"></div></td></tr>
                            <tr class="skeleton-row"><td colspan="5"><div class="skeleton-line"></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="card">
                <div class="card-header">
                    <h2 class="card-title">Recently completed</h2>
                    <p class="card-subtitle">Latest projects marked as complete</p>
                </div>
                <div class="card-body">
                    <div class="client-project-list" id="reportRecentCompletions">
                        <div class="skeleton-line"></div>
                        <div class="skeleton-line short"></div>
                        <div class="skeleton-line"></div>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/reports.js') }}"></script>
@endpush
