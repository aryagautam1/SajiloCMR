@extends('layouts.app')

@section('page-title', 'Team members')
@section('page', 'users')

@section('content')
    <div class="page-heading">
        <div>
            <span class="eyebrow">Administration</span>
            <h1>Team members</h1>
            <p class="page-subtitle">Manage workspace access, roles and the people who own client projects.</p>
        </div>
        <div class="page-actions">
            <button class="btn btn-primary" type="button" id="addUserButton">
                <i class="bi bi-person-plus" aria-hidden="true"></i> Add team member
            </button>
        </div>
    </div>

    <div class="security-note mb-3">
        <i class="bi bi-shield-lock" aria-hidden="true"></i>
        <span><strong>Administrators</strong> have full access, including deleting records and managing the team. <strong>Staff</strong> can manage clients and projects but cannot delete clients or administer user accounts.</span>
    </div>

    <section class="card data-card" aria-labelledby="userTableTitle">
        <h2 class="visually-hidden" id="userTableTitle">Team member records</h2>

        <div class="table-toolbar">
            <div class="toolbar-search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" class="form-control" id="userSearch" placeholder="Search name, email or job title" aria-label="Search team members" autocomplete="off">
            </div>
            <select class="form-select" id="userRoleFilter" aria-label="Filter by role">
                <option value="">All roles</option>
                <option value="admin">Administrators</option>
                <option value="staff">Staff</option>
            </select>
            <select class="form-select" id="userPerPage" aria-label="Records per page">
                @foreach (config('crm.per_page_options') as $option)
                    <option value="{{ $option }}" {{ $option === 10 ? 'selected' : '' }}>{{ $option }} per page</option>
                @endforeach
            </select>
            <button class="icon-button" type="button" id="userRefresh" title="Refresh team list" aria-label="Refresh team list">
                <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
            </button>
        </div>

        <div class="filter-chip-row" id="userFilters" hidden>
            <span>Active filters:</span>
            <span id="userFilterChips" class="d-inline-flex flex-wrap gap-2"></span>
            <button type="button" class="btn btn-ghost btn-sm ms-auto" id="userClearFilters">Clear all</button>
        </div>

        <div class="table-responsive">
            <table class="table responsive-table" id="usersTable" aria-label="Team member directory">
                <thead>
                    <tr>
                        <th data-sort="name">Member <span class="sort-indicator"><i class="bi bi-arrow-down-up" aria-hidden="true"></i></span></th>
                        <th data-sort="role">Role <span class="sort-indicator"><i class="bi bi-arrow-down-up" aria-hidden="true"></i></span></th>
                        <th>Job title</th>
                        <th>Assigned projects</th>
                        <th>Last sign-in</th>
                        <th data-sort="created_at">Added <span class="sort-indicator"><i class="bi bi-arrow-down-up" aria-hidden="true"></i></span></th>
                        <th class="text-end"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    <tr class="skeleton-row"><td colspan="7"><div class="skeleton-line"></div></td></tr>
                    <tr class="skeleton-row"><td colspan="7"><div class="skeleton-line short"></div></td></tr>
                    <tr class="skeleton-row"><td colspan="7"><div class="skeleton-line"></div></td></tr>
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <span class="table-meta" id="usersMeta">Loading team members…</span>
            <nav id="usersPagination" aria-label="Team pagination"></nav>
        </div>
    </section>

    {{-- Add / edit member --}}
    <div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title" id="userModalTitle">Add team member</h2>
                        <p class="card-subtitle" id="userModalSubtitle">Create a workspace account for a colleague.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="userForm" novalidate>
                        <input type="hidden" name="user_id" id="userId" data-keep="true">

                        <div class="form-grid">
                            <div class="form-section-title">Account details</div>

                            <div>
                                <label class="form-label" for="userName">Full name <span class="req">*</span></label>
                                <input type="text" class="form-control" id="userName" name="name" maxlength="120" required>
                                <div class="invalid-feedback" data-error-for="name"></div>
                            </div>

                            <div>
                                <label class="form-label" for="userEmail">Email address <span class="req">*</span></label>
                                <input type="email" class="form-control" id="userEmail" name="email" maxlength="150" required>
                                <div class="invalid-feedback" data-error-for="email"></div>
                            </div>

                            <div>
                                <label class="form-label" for="userRoleField">Role <span class="req">*</span></label>
                                <select class="form-select" id="userRoleField" name="role" required>
                                    <option value="staff">Staff</option>
                                    <option value="admin">Administrator</option>
                                </select>
                                <div class="invalid-feedback" data-error-for="role"></div>
                            </div>

                            <div>
                                <label class="form-label" for="userJobTitle">Job title</label>
                                <input type="text" class="form-control" id="userJobTitle" name="job_title" maxlength="100" placeholder="e.g. Project Coordinator">
                                <div class="invalid-feedback" data-error-for="job_title"></div>
                            </div>

                            <div>
                                <label class="form-label" for="userPhone">Phone number</label>
                                <input type="text" class="form-control" id="userPhone" name="phone" maxlength="30" placeholder="+977 98…">
                                <div class="invalid-feedback" data-error-for="phone"></div>
                            </div>

                            <div class="form-section-title">Password</div>

                            <div>
                                <label class="form-label" for="userPassword" id="userPasswordLabel">Password <span class="req">*</span></label>
                                <div class="password-field">
                                    <input type="password" class="form-control" id="userPassword" name="password" autocomplete="new-password" required>
                                    <button class="password-toggle" type="button" data-password-toggle="userPassword" aria-label="Show password">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <div class="form-text" id="userPasswordHint">At least 8 characters with letters and numbers.</div>
                                <div class="invalid-feedback" data-error-for="password"></div>
                            </div>

                            <div>
                                <label class="form-label" for="userPasswordConfirm">Confirm password <span class="req" id="userConfirmRequired">*</span></label>
                                <div class="password-field">
                                    <input type="password" class="form-control" id="userPasswordConfirm" name="password_confirmation" autocomplete="new-password" required>
                                    <button class="password-toggle" type="button" data-password-toggle="userPasswordConfirm" aria-label="Show password confirmation">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback" data-error-for="password_confirmation"></div>
                            </div>
                        </div>

                        <div class="alert alert-danger mt-3" data-form-error hidden></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-soft" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="userSaveButton">
                        <i class="bi bi-check-lg" aria-hidden="true"></i> Save member
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/users.js') }}"></script>
@endpush
