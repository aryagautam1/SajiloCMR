@extends('layouts.app')

@section('page-title', 'Clients')
@section('page', 'clients')

@section('content')
    <div class="page-heading">
        <div>
            <span class="eyebrow">Client directory</span>
            <h1>Clients</h1>
            <p class="page-subtitle">Keep contact details, account status and project history in one organised place.</p>
        </div>
        <div class="page-actions">
            <a class="btn btn-soft" id="exportClients" href="{{ route('clients.export') }}">
                <i class="bi bi-download" aria-hidden="true"></i> Export CSV
            </a>
            <button class="btn btn-primary" type="button" data-open-client-modal>
                <i class="bi bi-person-plus" aria-hidden="true"></i> Add client
            </button>
        </div>
    </div>

    <section class="card data-card" aria-labelledby="clientTableTitle">
        <h2 class="visually-hidden" id="clientTableTitle">Client records</h2>

        <div class="table-toolbar">
            <div class="toolbar-search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" class="form-control" id="clientSearch" placeholder="Search name, email or company" aria-label="Search clients" autocomplete="off">
            </div>
            <select class="form-select" id="clientStatus" aria-label="Filter by client status">
                <option value="">All statuses</option>
                @foreach (config('crm.client_statuses') as $key => $status)
                    <option value="{{ $key }}">{{ $status['label'] }}</option>
                @endforeach
            </select>
            <select class="form-select" id="clientPerPage" aria-label="Records per page">
                @foreach (config('crm.per_page_options') as $option)
                    <option value="{{ $option }}" {{ $option === 10 ? 'selected' : '' }}>{{ $option }} per page</option>
                @endforeach
            </select>
            <button class="icon-button" type="button" id="clientRefresh" title="Refresh client list" aria-label="Refresh client list">
                <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
            </button>
        </div>

        <div class="filter-chip-row" id="clientFilters" hidden>
            <span>Active filters:</span>
            <span id="clientFilterChips" class="d-inline-flex flex-wrap gap-2"></span>
            <button type="button" class="btn btn-ghost btn-sm ms-auto" id="clientClearFilters">Clear all</button>
        </div>

        <div class="table-responsive">
            <table class="table responsive-table" id="clientsTable" aria-label="Client directory">
                <thead>
                    <tr>
                        <th data-sort="company_name">Client <span class="sort-indicator"><i class="bi bi-arrow-down-up" aria-hidden="true"></i></span></th>
                        <th>Contact</th>
                        <th data-sort="status">Status <span class="sort-indicator"><i class="bi bi-arrow-down-up" aria-hidden="true"></i></span></th>
                        <th data-sort="projects_count">Projects <span class="sort-indicator"><i class="bi bi-arrow-down-up" aria-hidden="true"></i></span></th>
                        <th data-sort="created_at">Added <span class="sort-indicator"><i class="bi bi-arrow-down-up" aria-hidden="true"></i></span></th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody id="clientsTableBody">
                    <tr class="skeleton-row"><td colspan="6"><div class="skeleton-line"></div></td></tr>
                    <tr class="skeleton-row"><td colspan="6"><div class="skeleton-line short"></div></td></tr>
                    <tr class="skeleton-row"><td colspan="6"><div class="skeleton-line"></div></td></tr>
                    <tr class="skeleton-row"><td colspan="6"><div class="skeleton-line short"></div></td></tr>
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <span class="table-meta" id="clientsMeta">Loading clients…</span>
            <nav id="clientsPagination" aria-label="Client pagination"></nav>
        </div>
    </section>

    {{-- Add / edit client --}}
    <div class="modal fade" id="clientModal" tabindex="-1" aria-labelledby="clientModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title" id="clientModalTitle">Add client</h2>
                        <p class="card-subtitle" id="clientModalSubtitle">Create a new relationship record.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="clientForm" enctype="multipart/form-data" novalidate>
                        <input type="hidden" name="client_id" id="clientId" data-keep="true">
                        <input type="hidden" name="remove_logo" id="clientRemoveLogo" value="0" data-keep="true">

                        <div class="form-grid">
                            <div class="form-section-title">Client details</div>

                            <div>
                                <label class="form-label" for="clientCompanyName">Company name <span class="req">*</span></label>
                                <input type="text" class="form-control" id="clientCompanyName" name="company_name" maxlength="150" required>
                                <div class="invalid-feedback" data-error-for="company_name"></div>
                            </div>

                            <div>
                                <label class="form-label" for="clientName">Contact person <span class="req">*</span></label>
                                <input type="text" class="form-control" id="clientName" name="name" maxlength="120" required>
                                <div class="invalid-feedback" data-error-for="name"></div>
                            </div>

                            <div>
                                <label class="form-label" for="clientEmail">Email address <span class="req">*</span></label>
                                <input type="email" class="form-control" id="clientEmail" name="email" maxlength="150" required>
                                <div class="invalid-feedback" data-error-for="email"></div>
                            </div>

                            <div>
                                <label class="form-label" for="clientPhone">Phone number <span class="req">*</span></label>
                                <input type="text" class="form-control" id="clientPhone" name="phone" maxlength="30" placeholder="+977 98…" required>
                                <div class="invalid-feedback" data-error-for="phone"></div>
                            </div>

                            <div>
                                <label class="form-label" for="clientStatusField">Status <span class="req">*</span></label>
                                <select class="form-select" id="clientStatusField" name="status" required>
                                    @foreach (config('crm.client_statuses') as $key => $status)
                                        <option value="{{ $key }}">{{ $status['label'] }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" data-error-for="status"></div>
                            </div>

                            <div>
                                <label class="form-label" for="clientAddress">Address</label>
                                <input type="text" class="form-control" id="clientAddress" name="address" maxlength="500" placeholder="City, district">
                                <div class="invalid-feedback" data-error-for="address"></div>
                            </div>

                            <div class="col-span-2">
                                <label class="form-label" for="clientNotes">Notes</label>
                                <textarea class="form-control" id="clientNotes" name="notes" rows="3" maxlength="2000" placeholder="Preferences, context or important reminders…"></textarea>
                                <div class="invalid-feedback" data-error-for="notes"></div>
                            </div>

                            <div class="col-span-2">
                                <label class="form-label" for="clientLogo">Client logo</label>
                                <div class="logo-upload">
                                    <span class="logo-preview" id="clientLogoPreview" data-logo-preview><i class="bi bi-image" aria-hidden="true"></i></span>
                                    <div class="logo-upload-copy">
                                        <strong>Upload a logo</strong>
                                        <span>PNG, JPG or WebP · up to {{ config('crm.max_logo_kb') / 1024 }} MB</span>
                                        <input type="file" class="form-control" id="clientLogo" name="logo" accept="image/png,image/jpeg,image/webp">
                                        <div class="invalid-feedback" data-error-for="logo"></div>
                                    </div>
                                    <button type="button" class="btn btn-ghost btn-sm" id="clientLogoRemove" hidden>Remove</button>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-danger mt-3" data-form-error hidden></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-soft" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="clientSaveButton">
                        <i class="bi bi-check-lg" aria-hidden="true"></i> Save client
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Client detail --}}
    <div class="modal fade" id="clientDetailModal" tabindex="-1" aria-labelledby="clientDetailTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title" id="clientDetailTitle">Client details</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="clientDetailBody">
                    <div class="py-5 text-center"><span class="spinner-border text-primary" role="status" aria-label="Loading client"></span></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-soft" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="clientDetailEdit">
                        <i class="bi bi-pencil" aria-hidden="true"></i> Edit client
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/clients.js') }}"></script>
@endpush
