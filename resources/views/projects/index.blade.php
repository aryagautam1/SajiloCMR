@extends('layouts.app')

@section('page-title', 'Projects')
@section('page', 'projects')

@section('content')
    <div class="page-heading">
        <div>
            <span class="eyebrow">Delivery pipeline</span>
            <h1>Projects</h1>
            <p class="page-subtitle">Track every engagement from kick-off to completion, with owners, deadlines and progress.</p>
        </div>
        <div class="page-actions">
            <a class="btn btn-soft" id="exportProjects" href="{{ route('projects.export') }}">
                <i class="bi bi-download" aria-hidden="true"></i> Export CSV
            </a>
            <button class="btn btn-primary" type="button" data-open-project-modal>
                <i class="bi bi-plus-lg" aria-hidden="true"></i> Add project
            </button>
        </div>
    </div>

    <section class="card data-card" aria-labelledby="projectTableTitle">
        <h2 class="visually-hidden" id="projectTableTitle">Project records</h2>

        <div class="table-toolbar">
            <div class="toolbar-search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" class="form-control" id="projectSearch" placeholder="Search project, client or code" aria-label="Search projects" autocomplete="off">
            </div>
            <select class="form-select" id="projectStatus" aria-label="Filter by project status">
                <option value="">All statuses</option>
                @foreach (config('crm.project_statuses') as $key => $status)
                    <option value="{{ $key }}">{{ $status['label'] }}</option>
                @endforeach
            </select>
            <select class="form-select" id="projectPriority" aria-label="Filter by priority">
                <option value="">All priorities</option>
                @foreach (config('crm.priorities') as $key => $priority)
                    <option value="{{ $key }}">{{ $priority['label'] }}</option>
                @endforeach
            </select>
            <button class="btn btn-soft btn-sm" type="button" id="projectOverdueToggle" aria-pressed="false" title="Show overdue projects only">
                <i class="bi bi-exclamation-triangle" aria-hidden="true"></i> Overdue
            </button>
            <select class="form-select" id="projectPerPage" aria-label="Records per page">
                @foreach (config('crm.per_page_options') as $option)
                    <option value="{{ $option }}" {{ $option === 10 ? 'selected' : '' }}>{{ $option }} per page</option>
                @endforeach
            </select>
            <button class="icon-button" type="button" id="projectRefresh" title="Refresh project list" aria-label="Refresh project list">
                <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
            </button>
        </div>

        <div class="filter-chip-row" id="projectFilters" hidden>
            <span>Active filters:</span>
            <span id="projectFilterChips" class="d-inline-flex flex-wrap gap-2"></span>
            <button type="button" class="btn btn-ghost btn-sm ms-auto" id="projectClearFilters">Clear all</button>
        </div>

        <div class="table-responsive">
            <table class="table responsive-table" id="projectsTable" aria-label="Project directory">
                <thead>
                    <tr>
                        <th data-sort="project_name">Project <span class="sort-indicator"><i class="bi bi-arrow-down-up" aria-hidden="true"></i></span></th>
                        <th>Client</th>
                        <th>Assigned to</th>
                        <th data-sort="status">Status <span class="sort-indicator"><i class="bi bi-arrow-down-up" aria-hidden="true"></i></span></th>
                        <th data-sort="priority">Priority <span class="sort-indicator"><i class="bi bi-arrow-down-up" aria-hidden="true"></i></span></th>
                        <th data-sort="progress">Progress <span class="sort-indicator"><i class="bi bi-arrow-down-up" aria-hidden="true"></i></span></th>
                        <th data-sort="expected_completion_date">Due <span class="sort-indicator"><i class="bi bi-arrow-down-up" aria-hidden="true"></i></span></th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody id="projectsTableBody">
                    <tr class="skeleton-row"><td colspan="8"><div class="skeleton-line"></div></td></tr>
                    <tr class="skeleton-row"><td colspan="8"><div class="skeleton-line short"></div></td></tr>
                    <tr class="skeleton-row"><td colspan="8"><div class="skeleton-line"></div></td></tr>
                    <tr class="skeleton-row"><td colspan="8"><div class="skeleton-line short"></div></td></tr>
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <span class="table-meta" id="projectsMeta">Loading projects…</span>
            <nav id="projectsPagination" aria-label="Project pagination"></nav>
        </div>
    </section>

    @include('projects.partials.form-modal')
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/project-form.js') }}"></script>
    <script src="{{ asset('assets/js/projects.js') }}"></script>
@endpush
