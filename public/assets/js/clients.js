/* ==========================================================================
   Sajilo CRM — Clients directory
   ========================================================================== */

(function () {
    "use strict";

    const S = window.Sajilo;
    const table = document.getElementById("clientsTable");
    if (!table) return;

    const els = {
        body: document.getElementById("clientsTableBody"),
        meta: document.getElementById("clientsMeta"),
        pagination: document.getElementById("clientsPagination"),
        search: document.getElementById("clientSearch"),
        status: document.getElementById("clientStatus"),
        perPage: document.getElementById("clientPerPage"),
        refresh: document.getElementById("clientRefresh"),
        filters: document.getElementById("clientFilters"),
        chips: document.getElementById("clientFilterChips"),
        clearFilters: document.getElementById("clientClearFilters"),
        exportLink: document.getElementById("exportClients"),
        form: document.getElementById("clientForm"),
        modal: document.getElementById("clientModal"),
        modalTitle: document.getElementById("clientModalTitle"),
        modalSubtitle: document.getElementById("clientModalSubtitle"),
        save: document.getElementById("clientSaveButton"),
        logo: document.getElementById("clientLogo"),
        logoPreview: document.getElementById("clientLogoPreview"),
        logoRemove: document.getElementById("clientLogoRemove"),
        removeLogo: document.getElementById("clientRemoveLogo"),
        detailModal: document.getElementById("clientDetailModal"),
        detailBody: document.getElementById("clientDetailBody"),
        detailEdit: document.getElementById("clientDetailEdit"),
    };

    const DEFAULT_PER_PAGE = 10;
    const SORTABLE = ["company_name", "status", "projects_count", "created_at"];

    const state = {
        filters: {
            q: "",
            status: "",
            sort: "created_at",
            direction: "desc",
            per_page: DEFAULT_PER_PAGE,
            page: 1,
        },
        items: new Map(),
        meta: null,
        detail: null,
        editingId: null,
        reopenDetail: null,
        requestToken: 0,
        detailToken: 0,
        started: false,
    };

    const statusLabel = (status) => S.config.clientStatuses?.[status]?.label || status || "—";

    function parseFilters() {
        const params = new URLSearchParams(window.location.search);
        const status = params.get("status") || "";
        const sort = params.get("sort") || "";
        const direction = params.get("direction") || "";
        const perPage = Number(params.get("per_page"));
        const page = Number(params.get("page"));

        state.filters = {
            q: (params.get("q") || "").trim(),
            status: Object.prototype.hasOwnProperty.call(S.config.clientStatuses || {}, status) ? status : "",
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
        params.set("sort", filters.sort);
        params.set("direction", filters.direction);
        params.set("per_page", String(filters.per_page));
        params.set("page", String(filters.page));
        return params.toString();
    }

    function syncControls() {
        if (els.search) els.search.value = state.filters.q;
        if (els.status) els.status.value = state.filters.status;
        if (els.perPage) els.perPage.value = String(state.filters.per_page);
    }

    function syncExportLink() {
        if (!els.exportLink) return;
        const params = new URLSearchParams();
        if (state.filters.q) params.set("q", state.filters.q);
        if (state.filters.status) params.set("status", state.filters.status);
        const query = params.toString();
        els.exportLink.href = `/clients/export${query ? `?${query}` : ""}`;
    }

    function renderFilterChips() {
        if (!els.filters || !els.chips) return;

        const chips = [];
        if (state.filters.q) {
            chips.push(`
                <span class="filter-chip">
                    Search: “${S.escapeHtml(state.filters.q)}”
                    <button type="button" data-clear-filter="q" aria-label="Remove search filter"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                </span>`);
        }
        if (state.filters.status) {
            chips.push(`
                <span class="filter-chip">
                    Status: ${S.escapeHtml(statusLabel(state.filters.status))}
                    <button type="button" data-clear-filter="status" aria-label="Remove status filter"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
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

    function projectRow(project) {
        const due = project.expected_completion_date ? `Due ${S.formatDate(project.expected_completion_date)}` : "No due date";
        const meta = [project.code, due].filter(Boolean).join(" · ");

        return `
            <a class="client-project-item" href="/projects/${project.id}">
                <span style="min-width:0;flex:1 1 auto;">
                    <span class="project-name d-block truncate">${S.escapeHtml(project.project_name)}</span>
                    <span class="project-meta d-block truncate">${S.escapeHtml(meta)}</span>
                </span>
                ${project.is_overdue ? '<span class="chip overdue">Overdue</span>' : ""}
                <span class="client-project-progress" title="Progress: ${Number(project.progress) || 0}%">
                    ${S.progressMarkup(project.progress, progressTone(project.status))}
                    <span class="fs-12 fw-600 tabular">${Number(project.progress) || 0}%</span>
                </span>
                <span class="d-none d-xl-inline-flex">${S.statusBadge(project.status, "project")}</span>
            </a>`;
    }

    function renderDetail(client) {
        if (!els.detailBody) return;

        const projects = Array.isArray(client.projects) ? client.projects : [];
        const projectCount = Number(client.projects_count ?? projects.length) || 0;
        const notes = client.notes
            ? S.escapeHtml(client.notes).replace(/\r?\n/g, "<br>")
            : '<span class="text-muted">No notes recorded.</span>';

        els.detailBody.innerHTML = `
            <div class="detail-hero">
                ${S.clientLogo(client, "lg")}
                <div style="min-width:0;">
                    <h3 class="truncate">${S.escapeHtml(client.company_name)}</h3>
                    <div class="detail-hero-sub">${S.escapeHtml(client.name)} · Client since ${S.formatDate(client.created_at)}</div>
                </div>
                <div class="detail-hero-actions">
                    ${S.statusBadge(client.status, "client")}
                </div>
            </div>

            <div class="detail-grid">
                <div class="detail-field">
                    <span class="label">Contact person</span>
                    <span class="value">${S.escapeHtml(client.name)}</span>
                </div>
                <div class="detail-field">
                    <span class="label">Email address</span>
                    <span class="value"><a href="mailto:${S.escapeAttr(client.email)}">${S.escapeHtml(client.email)}</a></span>
                </div>
                <div class="detail-field">
                    <span class="label">Phone number</span>
                    <span class="value"><a href="tel:${S.escapeAttr(client.phone)}">${S.escapeHtml(client.phone)}</a></span>
                </div>
                <div class="detail-field">
                    <span class="label">Projects</span>
                    <span class="value">${projectCount} ${projectCount === 1 ? "project" : "projects"}</span>
                </div>
                <div class="detail-field full">
                    <span class="label">Address</span>
                    <span class="value">${client.address ? S.escapeHtml(client.address) : '<span class="text-muted">Not provided</span>'}</span>
                </div>
                <div class="detail-field full">
                    <span class="label">Notes</span>
                    <span class="value">${notes}</span>
                </div>
                <div class="detail-field">
                    <span class="label">Added by</span>
                    <span class="value">${S.escapeHtml(client.creator?.name || "System")}</span>
                </div>
                <div class="detail-field">
                    <span class="label">Last updated</span>
                    <span class="value">${S.formatRelative(client.updated_at) || S.formatDate(client.updated_at)}</span>
                </div>
            </div>

            <div class="divider"></div>

            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                <h3 style="font-size:.92rem;margin:0;">Projects <span class="text-muted fw-500">(${projectCount})</span></h3>
                <a class="btn btn-soft btn-sm" href="/projects?new=1&amp;client_id=${client.id}">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i> New project
                </a>
            </div>

            <div class="client-project-list">
                ${projects.length
                    ? projects.map(projectRow).join("")
                    : `<div class="detail-empty">No projects linked to this client yet. <a href="/projects?new=1&amp;client_id=${client.id}">Create the first project</a>.</div>`}
            </div>

            ${projectCount > projects.length
                ? `<a class="btn btn-ghost btn-sm mt-2" href="/projects?client_id=${client.id}">View all ${projectCount} projects <i class="bi bi-arrow-right" aria-hidden="true"></i></a>`
                : ""}
        `;
    }

    async function openDetail(id) {
        if (!els.detailBody || !els.detailModal) return;

        state.detailToken += 1;
        const token = state.detailToken;
        state.detail = null;
        els.detailBody.innerHTML = '<div class="py-5 text-center"><span class="spinner-border text-primary" role="status" aria-label="Loading client"></span></div>';
        if (els.detailEdit) els.detailEdit.disabled = true;
        S.showModal("clientDetailModal");

        try {
            const response = await S.api(`/api/clients/${id}`);
            if (token !== state.detailToken) return;

            state.detail = response.data;
            renderDetail(response.data);
            if (els.detailEdit) els.detailEdit.disabled = false;
        } catch (error) {
            if (token !== state.detailToken) return;
            els.detailBody.innerHTML = `
                <div class="empty-state">
                    <span class="empty-icon" style="background:var(--danger-soft);color:var(--danger)"><i class="bi bi-person-x" aria-hidden="true"></i></span>
                    <h3>Client unavailable</h3>
                    <p>${S.escapeHtml(error.message || "This client could not be loaded.")}</p>
                    <button type="button" class="btn btn-soft btn-sm" data-retry-detail="${Number(id) || ""}">
                        <i class="bi bi-arrow-clockwise" aria-hidden="true"></i> Try again
                    </button>
                </div>`;
            if (els.detailEdit) els.detailEdit.disabled = true;
        }
    }

    function showLogoPreview(url) {
        if (!els.logoPreview) return;
        els.logoPreview.innerHTML = url
            ? `<img src="${S.escapeAttr(url)}" alt="Client logo preview">`
            : '<i class="bi bi-image" aria-hidden="true"></i>';
        if (els.logoRemove) els.logoRemove.hidden = !url;
    }

    function setField(name, value) {
        const field = els.form?.elements.namedItem(name);
        if (field) field.value = value ?? "";
    }

    function openClientForm(client = null, fromDetail = false) {
        if (!els.form || !els.modal) return;

        S.clearForm(els.form);
        S.clearFormErrors(els.form);
        state.editingId = client?.id ? Number(client.id) : null;
        state.reopenDetail = fromDetail && client?.id ? Number(client.id) : null;

        setField("client_id", client?.id || "");
        setField("company_name", client?.company_name || "");
        setField("name", client?.name || "");
        setField("email", client?.email || "");
        setField("phone", client?.phone || "");
        setField("status", client?.status || "active");
        setField("address", client?.address || "");
        setField("notes", client?.notes || "");
        if (els.removeLogo) els.removeLogo.value = "0";

        showLogoPreview(client?.logo_url || null);
        if (els.logo) els.logo.value = "";

        if (els.modalTitle) els.modalTitle.textContent = client ? "Edit client" : "Add client";
        if (els.modalSubtitle) {
            els.modalSubtitle.textContent = client
                ? `Update the relationship record for ${client.company_name}.`
                : "Create a new relationship record.";
        }
        if (els.save) {
            els.save.innerHTML = client
                ? '<i class="bi bi-check-lg" aria-hidden="true"></i> Save changes'
                : '<i class="bi bi-check-lg" aria-hidden="true"></i> Save client';
        }

        S.showModal("clientModal");
    }

    async function saveClient() {
        if (!els.form) return;
        S.clearFormErrors(els.form);

        if (!els.form.reportValidity()) return;

        const formData = new FormData(els.form);
        if (!els.logo?.files?.length) formData.delete("logo");
        formData.set("remove_logo", els.removeLogo?.value === "1" ? "1" : "0");

        const id = state.editingId;
        const endpoint = id ? `/api/clients/${id}` : "/api/clients";
        const method = id ? "PATCH" : "POST";

        S.setButtonLoading(els.save, true, id ? "Saving changes…" : "Creating client…");
        try {
            const response = await S.apiForm(endpoint, method, formData);
            S.hideModal("clientModal");
            S.toast(response.message || "Client saved successfully.", "success");
            S.clearForm(els.form);
            state.editingId = null;

            const reopenId = state.reopenDetail;
            state.reopenDetail = null;

            await load();
            if (reopenId) {
                setTimeout(() => openDetail(reopenId), 320);
            }
        } catch (error) {
            S.handleApiError(error, els.form);
        } finally {
            S.setButtonLoading(els.save, false);
        }
    }

    function deleteClient(client) {
        S.confirmAction({
            title: "Delete client",
            message: `Permanently delete “${client.company_name}”? Clients that still have projects cannot be deleted.`,
            confirmLabel: "Delete client",
            onConfirm: async () => {
                const response = await S.api(`/api/clients/${client.id}`, { method: "DELETE" });
                S.toast(response.message || "Client deleted successfully.", "success");
                await load();
            },
        });
    }

    function rowActions(client) {
        const canDelete = S.user?.role === "admin";
        return `
            <div class="row-actions">
                <button type="button" class="icon-button" data-client-action="view" data-id="${client.id}" title="View ${S.escapeAttr(client.company_name)}" aria-label="View ${S.escapeAttr(client.company_name)}">
                    <i class="bi bi-eye" aria-hidden="true"></i>
                </button>
                <button type="button" class="icon-button" data-client-action="edit" data-id="${client.id}" title="Edit ${S.escapeAttr(client.company_name)}" aria-label="Edit ${S.escapeAttr(client.company_name)}">
                    <i class="bi bi-pencil" aria-hidden="true"></i>
                </button>
                ${canDelete ? `
                <button type="button" class="icon-button text-danger" data-client-action="delete" data-id="${client.id}" title="Delete ${S.escapeAttr(client.company_name)}" aria-label="Delete ${S.escapeAttr(client.company_name)}">
                    <i class="bi bi-trash3" aria-hidden="true"></i>
                </button>` : ""}
            </div>`;
    }

    function emptyMessage() {
        if (state.filters.q || state.filters.status) {
            return {
                title: "No clients match these filters",
                message: "Try a different search term or clear the active filters.",
                action: '<button type="button" class="btn btn-soft btn-sm" data-clear-all>Clear filters</button>',
            };
        }
        return {
            title: "No clients yet",
            message: "Add your first client to start building the directory.",
            action: '<button type="button" class="btn btn-primary btn-sm" data-open-client-modal><i class="bi bi-person-plus" aria-hidden="true"></i> Add client</button>',
        };
    }

    function renderRows(items) {
        if (!els.body) return;

        if (!items.length) {
            const empty = emptyMessage();
            els.body.innerHTML = S.emptyTableRow(6, empty.title, empty.message, empty.action);
            return;
        }

        els.body.innerHTML = items.map((client) => {
            const projects = Number(client.projects_count) || 0;
            const contact = [
                `<a class="cell-person-sub d-block truncate" href="mailto:${S.escapeAttr(client.email)}">${S.escapeHtml(client.email)}</a>`,
                `<span class="cell-person-sub d-block">${S.escapeHtml(client.phone)}</span>`,
            ].join("");

            return `
                <tr>
                    <td data-label="Client">
                        <div class="cell-person">
                            ${S.clientLogo(client)}
                            <div class="cell-person-text">
                                <a class="cell-person-name d-block truncate" href="/clients?open=${client.id}" data-client-action="view" data-id="${client.id}">${S.escapeHtml(client.company_name)}</a>
                                <span class="cell-person-sub d-block truncate">${S.escapeHtml(client.name)}</span>
                            </div>
                        </div>
                    </td>
                    <td data-label="Contact">${contact}</td>
                    <td data-label="Status">${S.statusBadge(client.status, "client")}</td>
                    <td data-label="Projects">
                        <span class="cell-strong tabular">${projects}</span>
                        <span class="cell-person-sub d-block">${projects === 1 ? "project" : "projects"}</span>
                    </td>
                    <td data-label="Added">
                        <span class="cell-strong d-block">${S.formatDate(client.created_at)}</span>
                        <span class="cell-person-sub">${S.formatRelative(client.created_at)}</span>
                    </td>
                    <td class="text-end" data-label="Actions">${rowActions(client)}</td>
                </tr>`;
        }).join("");
    }

    function renderMeta(meta) {
        if (!els.meta) return;
        els.meta.innerHTML = S.metaText(meta, "clients");
        S.renderPagination(els.pagination, meta, (page) => {
            state.filters.page = page;
            syncUrl();
            load();
            els.body?.closest(".table-responsive")?.scrollIntoView({ behavior: "smooth", block: "start" });
        });
    }

    async function load() {
        state.requestToken += 1;
        const token = state.requestToken;

        if (els.meta) els.meta.textContent = "Loading clients…";
        table.setAttribute("aria-busy", "true");

        try {
            const response = await S.api(`/api/clients?${apiQuery()}`);
            if (token !== state.requestToken) return;

            state.items = new Map((response.data || []).map((client) => [Number(client.id), client]));
            state.meta = response.meta || null;

            renderRows(response.data || []);
            renderMeta(response.meta);
            renderFilterChips();
            syncExportLink();
            renderSortState();
        } catch (error) {
            if (token !== state.requestToken) return;
            if (els.body) els.body.innerHTML = S.errorTableRow(6, error.message);
            if (els.meta) els.meta.textContent = "Unable to load clients";
        } finally {
            if (token === state.requestToken) table.removeAttribute("aria-busy");
        }
    }

    function findClient(id) {
        return state.items.get(Number(id)) || null;
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
                state.filters.direction = ["created_at", "projects_count"].includes(sort) ? "desc" : "asc";
            }
            state.filters.page = 1;
            syncUrl();
            load();
        });
    });

    /* Table actions + modal buttons */
    document.addEventListener("click", (event) => {
        const openAdd = event.target.closest("[data-open-client-modal]");
        if (openAdd) {
            event.preventDefault();
            openClientForm();
            return;
        }

        const action = event.target.closest("[data-client-action]");
        if (action) {
            const client = findClient(action.dataset.id);
            if (!client) return;

            if (action.dataset.clientAction === "view") {
                event.preventDefault();
                openDetail(client.id);
            } else if (action.dataset.clientAction === "edit") {
                openClientForm(client);
            } else if (action.dataset.clientAction === "delete") {
                deleteClient(client);
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
                if (els.status) els.status.value = "";
            }
            state.filters.page = 1;
            syncUrl();
            load();
            return;
        }

        if (event.target.closest("[data-clear-all]")) {
            event.preventDefault();
            state.filters.q = "";
            state.filters.status = "";
            state.filters.page = 1;
            syncControls();
            syncUrl();
            load();
            return;
        }

        const retry = event.target.closest("[data-retry-load]");
        if (retry) {
            load();
            return;
        }

        const retryDetail = event.target.closest("[data-retry-detail]");
        if (retryDetail) {
            openDetail(Number(retryDetail.dataset.retryDetail));
        }
    });

    els.save?.addEventListener("click", saveClient);

    els.form?.addEventListener("submit", (event) => {
        event.preventDefault();
        saveClient();
    });

    els.logo?.addEventListener("change", () => {
        const file = els.logo.files?.[0];
        if (!file) return;

        const maxBytes = (Number(S.config.maxLogoKb) || 2048) * 1024;
        if (!["image/png", "image/jpeg", "image/webp"].includes(file.type)) {
            S.toast("Choose a PNG, JPG or WebP image.", "danger");
            els.logo.value = "";
            return;
        }
        if (file.size > maxBytes) {
            S.toast(`The logo must be smaller than ${Math.round(maxBytes / 1024 / 1024)} MB.`, "danger");
            els.logo.value = "";
            return;
        }

        if (els.removeLogo) els.removeLogo.value = "0";
        const reader = new FileReader();
        reader.addEventListener("load", () => showLogoPreview(String(reader.result || "")));
        reader.readAsDataURL(file);
    });

    els.logoRemove?.addEventListener("click", () => {
        if (els.removeLogo) els.removeLogo.value = "1";
        if (els.logo) els.logo.value = "";
        showLogoPreview(null);
    });

    els.detailEdit?.addEventListener("click", () => {
        if (!state.detail) return;
        const client = state.detail;
        S.hideModal("clientDetailModal");
        setTimeout(() => openClientForm(client, true), 260);
    });

    els.modal?.addEventListener("hidden.bs.modal", () => {
        state.editingId = null;
        if (els.logo) els.logo.value = "";
    });

    els.detailModal?.addEventListener("hidden.bs.modal", () => {
        state.detail = null;
        state.detailToken += 1;
    });

    window.addEventListener("popstate", () => {
        parseFilters();
        syncControls();
        load();
    });

    /* ---------------------------------------------------------------------
       Boot
       --------------------------------------------------------------------- */

    function boot() {
        if (state.started) return;
        state.started = true;

        const params = new URLSearchParams(window.location.search);
        const openAdd = params.get("new") === "1";
        const openId = Number(params.get("open"));

        parseFilters();
        syncControls();
        renderFilterChips();
        syncExportLink();
        renderSortState();
        syncUrl();
        load();

        if (openAdd) {
            openClientForm();
        } else if (Number.isInteger(openId) && openId > 0) {
            openDetail(openId);
        }
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", boot);
    } else {
        boot();
    }
})();
