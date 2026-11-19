@extends('layouts.app')

@section('page-title', $project->project_name)
@section('page', 'projects')

@php
    $canEdit = auth()->user()->isAdmin() || $project->assigned_to === auth()->id();
    $canDelete = auth()->user()->isAdmin();
    $client = $project->client;
    $progressTone = ['in_progress' => 'primary', 'completed' => 'success', 'on_hold' => 'warning', 'cancelled' => 'danger'][$project->status] ?? null;
    $projectPageData = [
        'id' => $project->id,
        'name' => $project->project_name,
        'canEdit' => $canEdit,
        'canDelete' => $canDelete,
        'project' => $projectData,
        'indexUrl' => route('projects.index'),
    ];
@endphp

@section('content')
    <a class="back-link" href="{{ route('projects.index') }}">
        <i class="bi bi-arrow-left" aria-hidden="true"></i> All projects
    </a>

    <div class="project-header">
        <div>
            <div class="project-header-meta mb-2">
                <x-status-badge :status="$project->status" type="project" />
                <x-priority :priority="$project->priority" />
                <span class="project-code">{{ $project->code() }}</span>
                @if ($project->isOverdue())
                    <span class="chip overdue">Overdue</span>
                @endif
            </div>
            <h1>{{ $project->project_name }}</h1>
            <div class="text-muted" style="font-size:.82rem;">
                @if ($client)
                    <a href="{{ route('clients.index') }}?open={{ $client->id }}">{{ $client->company_name }}</a>
                    <span class="mx-1">·</span>
                @endif
                Created {{ $project->created_at?->format('d M Y') }}
            </div>
        </div>
        <div class="page-actions">
            @if ($canEdit)
                <button class="btn btn-soft" type="button" id="projectShowEdit">
                    <i class="bi bi-pencil" aria-hidden="true"></i> Edit project
                </button>
            @endif
            @if ($canDelete)
                <button class="btn btn-soft text-danger" type="button" id="projectShowDelete">
                    <i class="bi bi-trash3" aria-hidden="true"></i> Delete
                </button>
            @endif
        </div>
    </div>

    @if ($project->isOverdue())
        <div class="alert alert-warning d-flex align-items-start gap-2" role="alert" style="font-size:.82rem;">
            <i class="bi bi-exclamation-triangle-fill mt-1" aria-hidden="true"></i>
            <span>This project passed its expected completion date on <strong>{{ $project->expected_completion_date->format('d M Y') }}</strong>. Update the timeline or progress to keep the record accurate.</span>
        </div>
    @endif

    <div class="project-summary-grid">
        <div class="summary-tile">
            <span class="tile-icon"><i class="bi bi-activity" aria-hidden="true"></i></span>
            <span style="min-width:0;">
                <span class="tile-label">Progress</span>
                <span class="tile-value">{{ $project->progress }}% · {{ $project->statusLabel() }}</span>
            </span>
        </div>
        <div class="summary-tile">
            <span class="tile-icon"><i class="bi bi-wallet2" aria-hidden="true"></i></span>
            <span style="min-width:0;">
                <span class="tile-label">Budget</span>
                <span class="tile-value">{{ $project->budget !== null ? config('crm.currency').' '.number_format((float) $project->budget, 2) : 'Not set' }}</span>
            </span>
        </div>
        <div class="summary-tile">
            <span class="tile-icon"><i class="bi bi-calendar-event" aria-hidden="true"></i></span>
            <span style="min-width:0;">
                <span class="tile-label">Expected completion</span>
                <span class="tile-value">{{ $project->expected_completion_date?->format('d M Y') ?? 'Not set' }}</span>
            </span>
        </div>
        <div class="summary-tile">
            <span class="tile-icon"><i class="bi bi-person-check" aria-hidden="true"></i></span>
            <span style="min-width:0;">
                <span class="tile-label">Assigned to</span>
                <span class="tile-value">{{ $project->assignedTo?->name ?? 'Unassigned' }}</span>
            </span>
        </div>
    </div>

    <ul class="nav nav-tabs project-tabs" id="projectTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="overviewTab" data-bs-toggle="tab" data-bs-target="#overviewPane" type="button" role="tab" aria-controls="overviewPane" aria-selected="true">
                <i class="bi bi-grid-1x2" aria-hidden="true"></i> Overview
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="clientTab" data-bs-toggle="tab" data-bs-target="#clientPane" type="button" role="tab" aria-controls="clientPane" aria-selected="false">
                <i class="bi bi-person-vcard" aria-hidden="true"></i> Client information
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="detailsTab" data-bs-toggle="tab" data-bs-target="#detailsPane" type="button" role="tab" aria-controls="detailsPane" aria-selected="false">
                <i class="bi bi-list-check" aria-hidden="true"></i> Project details
            </button>
        </li>
    </ul>

    <div class="card tab-panel-card">
        <div class="tab-content">
            {{-- Overview --}}
            <div class="tab-pane fade show active" id="overviewPane" role="tabpanel" aria-labelledby="overviewTab" tabindex="0">
                <div class="tab-body">
                    <div class="row g-4">
                        <div class="col-lg-7">
                            <h3>Project overview</h3>
                            <p class="section-lead">The brief, scope and current delivery position.</p>

                            @if ($project->description)
                                <div class="description-text">{{ $project->description }}</div>
                            @else
                                <div class="detail-empty">No description has been added for this project yet.</div>
                            @endif

                            <div class="divider"></div>

                            <h3 style="font-size:.88rem;">Delivery progress</h3>
                            <p class="section-lead">Last updated {{ $project->updated_at?->diffForHumans() }}.</p>
                            <div class="big-progress">
                                <span class="progress-number">{{ $project->progress }}%</span>
                                <x-progress-bar :value="$project->progress" :tone="$progressTone" height="9" />
                            </div>
                            <div class="d-flex justify-content-between mt-2">
                                <span class="fs-12 text-muted">Not started</span>
                                <span class="fs-12 text-muted">Completed</span>
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <h3 style="font-size:.88rem;">At a glance</h3>
                            <p class="section-lead">Key delivery parameters for this engagement.</p>
                            <div class="detail-stack">
                                <div class="info-line">
                                    <span class="info-label">Status</span>
                                    <span class="info-value"><x-status-badge :status="$project->status" type="project" /></span>
                                </div>
                                <div class="info-line">
                                    <span class="info-label">Priority</span>
                                    <span class="info-value"><x-priority :priority="$project->priority" /></span>
                                </div>
                                <div class="info-line">
                                    <span class="info-label">Start date</span>
                                    <span class="info-value">{{ $project->start_date?->format('d M Y') ?? 'Not set' }}</span>
                                </div>
                                <div class="info-line">
                                    <span class="info-label">Expected completion</span>
                                    <span class="info-value">{{ $project->expected_completion_date?->format('d M Y') ?? 'Not set' }}</span>
                                </div>
                                <div class="info-line">
                                    <span class="info-label">Budget</span>
                                    <span class="info-value">{{ $project->budget !== null ? config('crm.currency').' '.number_format((float) $project->budget, 2) : 'Not set' }}</span>
                                </div>
                                <div class="info-line">
                                    <span class="info-label">Project lead</span>
                                    <span class="info-value">{{ $project->assignedTo?->name ?? 'Unassigned' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Client --}}
            <div class="tab-pane fade" id="clientPane" role="tabpanel" aria-labelledby="clientTab" tabindex="0">
                <div class="tab-body">
                    @if ($client)
                        <div class="detail-hero">
                            @if ($client->logo_path)
                                <span class="client-logo avatar avatar-lg"><img src="{{ \Illuminate\Support\Facades\Storage::disk('uploads')->url($client->logo_path) }}" alt="{{ $client->company_name }} logo"></span>
                            @else
                                <span class="client-logo avatar avatar-lg tone-{{ ($client->id % 6) + 1 }}" aria-hidden="true">{{ $client->initials() }}</span>
                            @endif
                            <div style="min-width:0;">
                                <h3 class="truncate">{{ $client->company_name }}</h3>
                                <div class="detail-hero-sub">{{ $client->name }} · {{ $client->projects_count ?? $client->projects()->count() }} project(s) in total</div>
                            </div>
                            <div class="detail-hero-actions">
                                <x-status-badge :status="$client->status" type="client" />
                                <a class="btn btn-soft btn-sm" href="{{ route('clients.index') }}?open={{ $client->id }}">
                                    <i class="bi bi-arrow-up-right" aria-hidden="true"></i> Open client
                                </a>
                            </div>
                        </div>

                        <div class="detail-grid">
                            <div class="detail-field">
                                <span class="label">Contact person</span>
                                <span class="value">{{ $client->name }}</span>
                            </div>
                            <div class="detail-field">
                                <span class="label">Email address</span>
                                <span class="value"><a href="mailto:{{ $client->email }}">{{ $client->email }}</a></span>
                            </div>
                            <div class="detail-field">
                                <span class="label">Phone number</span>
                                <span class="value"><a href="tel:{{ $client->phone }}">{{ $client->phone }}</a></span>
                            </div>
                            <div class="detail-field">
                                <span class="label">Client since</span>
                                <span class="value">{{ $client->created_at?->format('d M Y') ?? '—' }}</span>
                            </div>
                            <div class="detail-field full">
                                <span class="label">Address</span>
                                <span class="value">{{ $client->address ?: 'Not provided' }}</span>
                            </div>
                            @if ($client->notes)
                                <div class="detail-field full">
                                    <span class="label">Notes</span>
                                    <span class="value">{!! nl2br(e($client->notes)) !!}</span>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="detail-empty">This project has no linked client record.</div>
                    @endif
                </div>
            </div>

            {{-- Details --}}
            <div class="tab-pane fade" id="detailsPane" role="tabpanel" aria-labelledby="detailsTab" tabindex="0">
                <div class="tab-body">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <h3 style="font-size:.88rem;">Project information</h3>
                            <p class="section-lead">Reference data stored against this project.</p>
                            <div class="detail-stack">
                                <div class="info-line">
                                    <span class="info-label">Project code</span>
                                    <span class="info-value">{{ $project->code() }}</span>
                                </div>
                                <div class="info-line">
                                    <span class="info-label">Client</span>
                                    <span class="info-value">{{ $client?->company_name ?? '—' }}</span>
                                </div>
                                <div class="info-line">
                                    <span class="info-label">Project lead</span>
                                    <span class="info-value">{{ $project->assignedTo?->name ?? 'Unassigned' }}</span>
                                </div>
                                <div class="info-line">
                                    <span class="info-label">Status</span>
                                    <span class="info-value"><x-status-badge :status="$project->status" type="project" /></span>
                                </div>
                                <div class="info-line">
                                    <span class="info-label">Priority</span>
                                    <span class="info-value"><x-priority :priority="$project->priority" /></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <h3 style="font-size:.88rem;">Timeline &amp; value</h3>
                            <p class="section-lead">Dates, budget and record history.</p>
                            <div class="detail-stack">
                                <div class="info-line">
                                    <span class="info-label">Start date</span>
                                    <span class="info-value">{{ $project->start_date?->format('d M Y') ?? 'Not set' }}</span>
                                </div>
                                <div class="info-line">
                                    <span class="info-label">Expected completion</span>
                                    <span class="info-value">{{ $project->expected_completion_date?->format('d M Y') ?? 'Not set' }}</span>
                                </div>
                                <div class="info-line">
                                    <span class="info-label">Completed on</span>
                                    <span class="info-value">{{ $project->completed_at?->format('d M Y') ?? 'Not completed' }}</span>
                                </div>
                                <div class="info-line">
                                    <span class="info-label">Budget</span>
                                    <span class="info-value">{{ $project->budget !== null ? config('crm.currency').' '.number_format((float) $project->budget, 2) : 'Not set' }}</span>
                                </div>
                                <div class="info-line">
                                    <span class="info-label">Created</span>
                                    <span class="info-value">{{ $project->created_at?->format('d M Y, H:i') ?? '—' }}</span>
                                </div>
                                <div class="info-line">
                                    <span class="info-label">Last updated</span>
                                    <span class="info-value">{{ $project->updated_at?->format('d M Y, H:i') ?? '—' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('projects.partials.form-modal')
@endsection

@push('scripts')
    <script>
        window.__PROJECT_PAGE__ = @json($projectPageData);
    </script>
    <script src="{{ asset('assets/js/project-form.js') }}"></script>
    <script src="{{ asset('assets/js/project-show.js') }}"></script>
@endpush
