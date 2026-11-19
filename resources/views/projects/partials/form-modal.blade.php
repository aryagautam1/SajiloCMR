{{-- Shared add / edit project modal. Expects $clients and $staff. --}}
<div class="modal fade" id="projectModal" tabindex="-1" aria-labelledby="projectModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title" id="projectModalTitle">Add project</h2>
                    <p class="card-subtitle" id="projectModalSubtitle">Plan a new engagement and assign it to your team.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="projectForm" novalidate>
                    <input type="hidden" name="project_id" id="projectId" data-keep="true">

                    <div class="form-grid">
                        <div class="form-section-title">Project details</div>

                        <div class="col-span-2">
                            <label class="form-label" for="projectName">Project name <span class="req">*</span></label>
                            <input type="text" class="form-control" id="projectName" name="project_name" maxlength="160" required>
                            <div class="invalid-feedback" data-error-for="project_name"></div>
                        </div>

                        <div>
                            <label class="form-label" for="projectClient">Client <span class="req">*</span></label>
                            <select class="form-select" id="projectClient" name="client_id" required>
                                <option value="">Select a client…</option>
                                @foreach ($clients as $clientOption)
                                    <option value="{{ $clientOption->id }}">{{ $clientOption->company_name }} — {{ $clientOption->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" data-error-for="client_id"></div>
                        </div>

                        @if (auth()->user()->isAdmin())
                            <div>
                                <label class="form-label" for="projectAssignee">Assigned to</label>
                                <select class="form-select" id="projectAssignee" name="assigned_to">
                                    <option value="">Unassigned</option>
                                    @foreach ($staff as $member)
                                        <option value="{{ $member->id }}">{{ $member->name }}{{ $member->role === 'admin' ? ' (Admin)' : '' }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" data-error-for="assigned_to"></div>
                            </div>
                        @endif

                        <div>
                            <label class="form-label" for="projectStatusField">Status <span class="req">*</span></label>
                            <select class="form-select" id="projectStatusField" name="status" required>
                                @foreach (config('crm.project_statuses') as $key => $status)
                                    <option value="{{ $key }}">{{ $status['label'] }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" data-error-for="status"></div>
                        </div>

                        <div>
                            <label class="form-label" for="projectPriorityField">Priority <span class="req">*</span></label>
                            <select class="form-select" id="projectPriorityField" name="priority" required>
                                @foreach (config('crm.priorities') as $key => $priority)
                                    <option value="{{ $key }}">{{ $priority['label'] }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" data-error-for="priority"></div>
                        </div>

                        <div>
                            <label class="form-label" for="projectStartDate">Start date</label>
                            <input type="date" class="form-control" id="projectStartDate" name="start_date">
                            <div class="invalid-feedback" data-error-for="start_date"></div>
                        </div>

                        <div>
                            <label class="form-label" for="projectDueDate">Expected completion</label>
                            <input type="date" class="form-control" id="projectDueDate" name="expected_completion_date">
                            <div class="invalid-feedback" data-error-for="expected_completion_date"></div>
                        </div>

                        <div>
                            <label class="form-label" for="projectBudget">Budget ({{ config('crm.currency') }})</label>
                            <input type="number" class="form-control" id="projectBudget" name="budget" min="0" step="0.01" placeholder="e.g. 150000">
                            <div class="invalid-feedback" data-error-for="budget"></div>
                        </div>

                        <div>
                            <label class="form-label" for="projectProgress">Progress</label>
                            <div class="range-field">
                                <input type="range" class="form-range" id="projectProgress" name="progress" min="0" max="100" step="5" value="0" data-range>
                                <output class="range-output" data-range-output="progress">0%</output>
                            </div>
                            <div class="invalid-feedback" data-error-for="progress"></div>
                        </div>

                        <div class="col-span-2">
                            <label class="form-label" for="projectDescription">Description</label>
                            <textarea class="form-control" id="projectDescription" name="description" rows="4" maxlength="5000" placeholder="Scope, deliverables, key requirements…"></textarea>
                            <div class="invalid-feedback" data-error-for="description"></div>
                        </div>
                    </div>

                    <div class="alert alert-danger mt-3" data-form-error hidden></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-soft" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="projectSaveButton">
                    <i class="bi bi-check-lg" aria-hidden="true"></i> Save project
                </button>
            </div>
        </div>
    </div>
</div>
