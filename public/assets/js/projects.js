/* ==========================================================================
   Sajilo CRM — Projects directory
   ========================================================================== */

(function () {
    "use strict";

    const S = window.Sajilo;
    const table = document.getElementById("projectsTable");
    if (!table || !window.SajiloProjectForm) return;

    const els = {
        body: document.getElementById("projectsTableBody"),
        meta: document.getElementById("projectsMeta"),
        pagination: document.getElementById("projectsPagination"),
        search: document.getElementById("projectSearch"),
        status: document.getElementById("projectStatus"),
        priority: document.getElementById("projectPriority"),
        overdue: document.getElementById("projectOverdueToggle"),
        perPage: document.getElementById("projectPerPage"),
        refresh: document.getElementById("projectRefresh"),
        filters: document.getElementById("projectFilters"),
        chips: document.getElementById("projectFilterChips"),
        clearFilters: document.getElementById("projectClearFilters"),
        exportLink: document.getElementById("exportProjects"),
    };

    const DEFAULT_PER_PAGE = 10;
    const SORTABLE = ["project_name", "status", "priority", "progress", "expected_completion_date"];

    const state = {
        filters: {
            q: "",
            status: "",
            priority: "",
            overdue: false,
            client_id: "",
            sort: "created_at",
            direction: "desc",
            per_page: DEFAULT_PER_PAGE,
            page: 1,
        },
        items: new Map(),
        meta: null,
        requestToken: 0,
    };

    const statusLabel = (status) => S.config.projectStatuses?.[status]?.label || status || "—";
    const priorityLabel = (priority) => S.config.priorities?.[priority]?.label || priority || "—";

    function parseFilters() {
        const params = new URLSearchParams(window.location.search);
        const status = params.get("status") || "";
        const priority = params.get("priority") || "";
        const sort = params.get("sort") || "";
        const direction = params.get("direction") || "";
        const perPage = Number(params.get("per_page"));
        const page = Number(params.get("page"));
        const clientId = Number(params.get("client_id"));

        state.filters = {
            q: (params.get("q") || "").trim(),
            status: Object.prototype.hasOwnProperty.call(S.config.projectStatuses || {}, status) ? status : "",
            priority: Object.prototype.hasOwnProperty.call(S.config.priorities || {}, priority) ? priority : "",
            overdue: params.get("overdue") === "1",
            client_id: Number.isInteger(clientId) && clientId > 0 ? clientId : "",
            sort: SORTABLE.includes(sort) ? sort : "created_at",
            direction: ["asc", "desc"].includes(direction) ? direction : "desc",
            per_page: S.config.perPageOptions?.includes(perPage) ? perPage : DEFAULT_PER_PAGE,
            page: Number.isInteger(page) && page > 0 ? page : 1,
        };
    }

    function syncUrl() {
        const params = new URLSearchParams();
        const filters = state.filters;
        if (filters.q) params.set("q", filters.q);
        if (filters.status) params.set("status", filters.status);
        if (filters.priority) params.set("priority", filters.priority);
        if (filters.overdue) params.set("overdue", "1");
        if (filters.client_id) params.set("client_id", String(filters.client_id));
        params.set("sort", filters.sort);
        params.set("direction", filters.direction);
        if (filters.per_page !== DEFAULT_PER_PAGE) params.set("per_page", String(filters.per_page));
        if (filters.page > 1) params.set("page", String(filters.page));

        const query = params.toString();
        window.history.replaceState({}, "", `${window.location.pathname}${query ? `?${query}` : ""}`);
    }

    function apiQuery() {
        const params = new URLSearchParams();
        const filters = state.filters;
        if (filters.q) params.set("q", filters.q);
        if (filters.status) params.set("status", filters.status);
        if (filters.priority) params.set("priority", filters.priority);
        if (filters.overdue) params.set("overdue", "1");
        if (filters.client_id) params.set("client_id", String(filters.client_id));
        params.set("sort", filters.sort);
        params.set("direction", filters.direction);
        params.set("per_page", String(filters.per_page));
        params.set("page", String(filters.page));
        return params.toString();
    }

    function clientLabel(id) {
        if (!id) return "";
        const option = document.querySelector(`#projectClient option[value="${CSS.escape(String(id))}"]`);
        return option ? option.textContent.split("—")[0].trim() : `Client #${id}`;
    }

    function syncControls() {
        if (els.search) els.search.value = state.filters.q;
        if (els.status) els.status.value = state.filters.status;
        if (els.priority) els.priority.value = state.filters.priority;
        if (els.perPage) els.perPage.value = String(state.filters.per_page);
        els.overdue?.classList.toggle("active", state.filters.overdue);
        els.overdue?.setAttribute("aria-pressed", String(state.filters.overdue));
    }

    function syncExportLink() {
        if (!els.exportLink) return;
        const params = new URLSearchParams();
        const filters = state.filters;
        if (filters.q) params.set("q", filters.q);
        if (filters.status) params.set("status", filters.status);
        if (filters.priority) params.set("priority", filters.priority);
        if (filters.overdue) params.set("overdue", "1");
        if (filters.client_id) params.set("client_id", String(filters.client_id));
        const query = params.toString();
        els.exportLink.href = `/projects/export${query ? `?${query}` : ""}`;
    }

    function renderFilterChips() {
        if (!els.filters || !els.chips) return;

        const chips = [];
        const filters = state.filters;
        if (filters.q) {
            chips.push(`
                <span class="filter-chip">
                    Search: “${S.escapeHtml(filters.q)}”
                    <button type="button" data-clear-filter="q" aria-label="Remove search filter"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                </span>`);
        }
        if (filters.status) {
            chips.push(`
                <span class="filter-chip">
                    Status: ${S.escapeHtml(statusLabel(filters.status))}
                    <button type="button" data-clear-filter="status" aria-label="Remove status filter"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                </span>`);
        }
        if (filters.priority) {
            chips.push(`
                <span class="filter-chip">
                    Priority: ${S.escapeHtml(priorityLabel(filters.priority))}
                    <button type="button" data-clear-filter="priority" aria-label="Remove priority filter"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                </span>`);
        }
        if (filters.overdue) {
            chips.push(`
                <span class="filter-chip">
                    Overdue only
                    <button type="button" data-clear-filter="overdue" aria-label="Remove overdue filter"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                </span>`);
        }
        if (filters.client_id) {
            chips.push(`
                <span class="filter-chip">
                    Client: ${S.escapeHtml(clientLabel(filters.client_id))}
                    <button type="button" data-clear-filter="client_id" aria-label="Remove client filter"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                </span>`);
        }

        els.chips.innerHTML = chips.join("");
        els.filters.hidden = chips.length === 0;
    }

    function renderSortState() {
        S.$$("th[data-sort]", table).forEach((th) => {
            const sorted = th.dataset.sort === state.filters.sort;
            th.classList.toggle("sorted", sorted);
            th.setAttribute("aria-sort", sorted
                ? (state.filters.direction === "asc" ? "ascending" : "descending")
                : "none");

            const icon = th.querySelector(".sort-indicator i");
            if (icon) {
                icon.className = `bi ${sorted
                    ? (state.filters.direction === "asc" ? "bi-arrow-up" : "bi-arrow-down")
                    : "bi-arrow-down-up"}`;
            }
        });
    }

    function progressTone(status) {
        return { in_progress: "primary", completed: "success", on_hold: "warning", cancelled: "danger" }[status] || "";
    }

    function canEdit(project) {
        if (S.user?.role === "admin") return true;
        return Number(project.assigned_to?.id) === Number(S.user?.id);
    }

    function rowActions(project) {
        const allowed = canEdit(project);
        const canDelete = S.user?.role === "admin";

        return `
            <div class="row-actions">
                <a class="icon-button" href="/projects/${project.id}" title="View ${S.escapeAttr(project.project_name)}" aria-label="View ${S.escapeAttr(project.project_name)}">
                    <i class="bi bi-eye" aria-hidden="true"></i>
                </a>
                ${allowed ? `
                <button type="button" class="icon-button" data-project-action="edit" data-id="${project.id}" title="Edit ${S.escapeAttr(project.project_name)}" aria-label="Edit ${S.escapeAttr(project.project_name)}">
                    <i class="bi bi-pencil" aria-hidden="true"></i>
                </button>` : ""}
                ${canDelete ? `
                <button type="button" class="icon-button text-danger" data-project-action="delete" data-id="${project.id}" title="Delete ${S.escapeAttr(project.project_name)}" aria-label="Delete ${S.escapeAttr(project.project_name)}">
                    <i class="bi bi-trash3" aria-hidden="true"></i>
                </button>` : ""}
            </div>`;
    }

    function dueMarkup(project) {
        if (!project.expected_completion_date) {
            return '<span class="cell-person-sub">No due date</span>';
        }
        const overdue = project.is_overdue
            ? '<span class="chip overdue ms-1">Overdue</span>'
            : `<span class="cell-person-sub d-block">${S.formatRelative(project.expected_completion_date)}</span>`;
        return `<span class="cell-strong tabular">${S.formatDate(project.expected_completion_date)}</span>${overdue}`;
    }

    function renderRows(items) {
        if (!els.body) return;

        if (!items.length) {
            const filtered = ["q", "status", "priority", "overdue", "client_id"].some((key) => Boolean(state.filters[key]));
            els.body.innerHTML = filtered
                ? S.emptyTableRow(8, "No projects match these filters", "Try a different search or clear the active filters.", '<button type="button" class="btn btn-soft btn-sm" data-clear-all>Clear filters</button>')
                : S.emptyTableRow(8, "No projects yet", "Create your first project to start tracking delivery.", '<button type="button" class="btn btn-primary btn-sm" data-open-project-modal><i class="bi bi-plus-lg" aria-hidden="true"></i> Add project</button>');
            return;
        }

        els.body.innerHTML = items.map((project) => {
            const assignee = project.assigned_to
                ? `<span class="cell-person">${S.userAvatar(project.assigned_to)}<span class="cell-person-text"><span class="cell-person-name d-block truncate">${S.escapeHtml(project.assigned_to.name)}</span><span class="cell-person-sub d-block">${S.escapeHtml(project.assigned_to.role_label || "Team member")}</span></span></span>`
                : '<span class="cell-person-sub">Unassigned</span>';

            return `
                <tr>
                    <td data-label="Project">
                        <div class="cell-person-text">
                            <a class="cell-person-name d-block truncate" href="/projects/${project.id}">${S.escapeHtml(project.project_name)}</a>
                            <span class="cell-person-sub d-block">${S.escapeHtml(project.code)}</span>
                        </div>
                    </td>
                    <td data-label="Client">
                        <a class="cell-person-name d-block truncate" href="/clients?open=${project.client?.id || ""}">${S.escapeHtml(project.client?.company_name || "—")}</a>
                        <span class="cell-person-sub d-block truncate">${S.escapeHtml(project.client?.name || "")}</span>
                    </td>
                    <td data-label="Assigned to">${assignee}</td>
                    <td data-label="Status">${S.statusBadge(project.status, "project")}</td>
                    <td data-label="Priority">${S.priorityBadge(project.priority)}</td>
                    <td data-label="Progress" style="min-width:140px;">
                        <div class="d-flex align-items-center gap-2">
                            <div style="flex:1 1 auto;">${S.progressMarkup(project.progress, progressTone(project.status))}</div>
                            <span class="fs-12 fw-600 text-ink tabular">${Number(project.progress) || 0}%</span>
                        </div>
                    </td>
                    <td data-label="Due">${dueMarkup(project)}</td>
                    <td class="text-end" data-label="Actions">${rowActions(project)}</td>
                </tr>`;
        }).join("");
    }

    function renderMeta(meta) {
        if (!els.meta) return;
        els.meta.innerHTML = S.metaText(meta, "projects");
        S.renderPagination(els.pagination, meta, (page) => {
            state.filters.page = page;
            syncUrl();
            load();
            els.body?.closest(".table-responsive")?.scrollIntoView({ behavior: "smooth", block: "start" });
        });
    }

    function findProject(id) {
        return state.items.get(Number(id)) || null;
    }

    async function load() {
        state.requestToken += 1;
        const token = state.requestToken;

        if (els.meta) els.meta.textContent = "Loading projects…";
        table.setAttribute("aria-busy", "true");

        try {
            const response = await S.api(`/api/projects?${apiQuery()}`);
            if (token !== state.requestToken) return;

            state.items = new Map((response.data || []).map((project) => [Number(project.id), project]));
            state.meta = response.meta || null;

            renderRows(response.data || []);
            renderMeta(response.meta);
            renderFilterChips();
            syncExportLink();
            renderSortState();
        } catch (error) {
            if (token !== state.requestToken) return;
            if (els.body) els.body.innerHTML = S.errorTableRow(8, error.message);
            if (els.meta) els.meta.textContent = "Unable to load projects";
        } finally {
            if (token === state.requestToken) table.removeAttribute("aria-busy");
        }
    }

    /* ---------------------------------------------------------------------
       Events
       --------------------------------------------------------------------- */

    if (els.search) {
        const debouncedSearch = S.debounce(() => {
            state.filters.q = els.search.value.trim();
            state.filters.page = 1;
            syncUrl();
            load();
        }, 380);

        els.search.addEventListener("input", () => {
            state.filters.q = els.search.value.trim();
            debouncedSearch();
        });
        els.search.addEventListener("keydown", (event) => {
            if (event.key === "Enter") {
                event.preventDefault();
                state.filters.q = els.search.value.trim();
                state.filters.page = 1;
                syncUrl();
                load();
            }
        });
    }

    els.status?.addEventListener("change", () => {
        state.filters.status = els.status.value;
        state.filters.page = 1;
        syncUrl();
        load();
    });

    els.priority?.addEventListener("change", () => {
        state.filters.priority = els.priority.value;
        state.filters.page = 1;
        syncUrl();
        load();
    });

    els.overdue?.addEventListener("click", () => {
        state.filters.overdue = !state.filters.overdue;
        state.filters.page = 1;
        syncControls();
        syncUrl();
        load();
    });

    els.perPage?.addEventListener("change", () => {
        state.filters.per_page = Number(els.perPage.value) || DEFAULT_PER_PAGE;
        state.filters.page = 1;
        syncUrl();
        load();
    });

    els.refresh?.addEventListener("click", load);

    els.clearFilters?.addEventListener("click", () => {
        state.filters.q = "";
        state.filters.status = "";
        state.filters.priority = "";
        state.filters.overdue = false;
        state.filters.client_id = "";
        state.filters.page = 1;
        syncControls();
        syncUrl();
        load();
    });

    S.$$("th[data-sort]", table).forEach((th) => {
        th.style.cursor = "pointer";
        th.addEventListener("click", () => {
            const sort = th.dataset.sort;
            if (!SORTABLE.includes(sort)) return;

            if (state.filters.sort === sort) {
                state.filters.direction = state.filters.direction === "asc" ? "desc" : "asc";
            } else {
                state.filters.sort = sort;
                state.filters.direction = ["progress", "created_at"].includes(sort) ? "desc" : "asc";
            }
            state.filters.page = 1;
            syncUrl();
            load();
        });
    });

    document.addEventListener("click", (event) => {
        const action = event.target.closest("[data-project-action]");
        if (action) {
            const project = findProject(action.dataset.id);
            if (!project) return;

            if (action.dataset.projectAction === "edit") {
                window.SajiloProjectForm.open(project);
            } else if (action.dataset.projectAction === "delete") {
                S.confirmAction({
                    title: "Delete project",
                    message: `Permanently delete “${project.project_name}”? This removes the project and its history.`,
                    confirmLabel: "Delete project",
                    onConfirm: async () => {
                        const response = await S.api(`/api/projects/${project.id}`, { method: "DELETE" });
                        S.toast(response.message || "Project deleted successfully.", "success");
                        await load();
                    },
                });
            }
            return;
        }

        const clearFilter = event.target.closest("[data-clear-filter]");
        if (clearFilter) {
            const filter = clearFilter.dataset.clearFilter;
            if (filter === "q") {
                state.filters.q = "";
                if (els.search) els.search.value = "";
            } else if (filter === "status") {
                state.filters.status = "";
            } else if (filter === "priority") {
                state.filters.priority = "";
            } else if (filter === "overdue") {
                state.filters.overdue = false;
            } else if (filter === "client_id") {
                state.filters.client_id = "";
            }
            state.filters.page = 1;
            syncControls();
            syncUrl();
            load();
            return;
        }

        if (event.target.closest("[data-clear-all]")) {
            event.preventDefault();
            state.filters.q = "";
            state.filters.status = "";
            state.filters.priority = "";
            state.filters.overdue = false;
            state.filters.client_id = "";
            state.filters.page = 1;
            syncControls();
            syncUrl();
            load();
            return;
        }

        if (event.target.closest("[data-retry-load]")) {
            load();
        }
    });

    document.addEventListener("sajilo:project-saved", () => {
        load();
    });

    window.addEventListener("popstate", () => {
        parseFilters();
        syncControls();
        load();
    });

    /* ---------------------------------------------------------------------
       Boot
       --------------------------------------------------------------------- */

    const params = new URLSearchParams(window.location.search);
    const openAdd = params.get("new") === "1";

    parseFilters();
    syncControls();
    renderFilterChips();
    syncExportLink();
    renderSortState();
    syncUrl();
    load();

    if (openAdd) {
        const defaults = state.filters.client_id ? { client_id: state.filters.client_id } : {};
        window.SajiloProjectForm.open(null, defaults);
    }
})();
