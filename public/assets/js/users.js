/* ==========================================================================
   Sajilo CRM — Team members (admin)
   ========================================================================== */

(function () {
    "use strict";

    const S = window.Sajilo;
    const table = document.getElementById("usersTable");
    if (!table) return;

    const els = {
        body: document.getElementById("usersTableBody"),
        meta: document.getElementById("usersMeta"),
        pagination: document.getElementById("usersPagination"),
        search: document.getElementById("userSearch"),
        role: document.getElementById("userRoleFilter"),
        perPage: document.getElementById("userPerPage"),
        refresh: document.getElementById("userRefresh"),
        filters: document.getElementById("userFilters"),
        chips: document.getElementById("userFilterChips"),
        clearFilters: document.getElementById("userClearFilters"),
        add: document.getElementById("addUserButton"),
        modal: document.getElementById("userModal"),
        modalTitle: document.getElementById("userModalTitle"),
        modalSubtitle: document.getElementById("userModalSubtitle"),
        form: document.getElementById("userForm"),
        save: document.getElementById("userSaveButton"),
        id: document.getElementById("userId"),
        name: document.getElementById("userName"),
        email: document.getElementById("userEmail"),
        roleField: document.getElementById("userRoleField"),
        jobTitle: document.getElementById("userJobTitle"),
        phone: document.getElementById("userPhone"),
        password: document.getElementById("userPassword"),
        confirm: document.getElementById("userPasswordConfirm"),
        passwordLabel: document.getElementById("userPasswordLabel"),
        passwordHint: document.getElementById("userPasswordHint"),
        confirmRequired: document.getElementById("userConfirmRequired"),
    };

    const DEFAULT_PER_PAGE = 10;
    const SORTABLE = ["name", "email", "role", "created_at"];

    const state = {
        filters: {
            q: "",
            role: "",
            sort: "name",
            direction: "asc",
            per_page: DEFAULT_PER_PAGE,
            page: 1,
        },
        items: new Map(),
        editingId: null,
        requestToken: 0,
    };

    const roleBadge = (role) => role === "admin"
        ? '<span class="badge badge-soft tone-primary">Administrator</span>'
        : '<span class="badge badge-soft tone-secondary">Staff</span>';

    function parseFilters() {
        const params = new URLSearchParams(window.location.search);
        const role = params.get("role") || "";
        const sort = SORTABLE.includes(params.get("sort")) ? params.get("sort") : "name";
        const direction = params.get("direction") || "";
        const perPage = Number(params.get("per_page"));
        const page = Number(params.get("page"));

        state.filters = {
            q: (params.get("q") || "").trim(),
            role: ["admin", "staff"].includes(role) ? role : "",
            sort,
            direction: ["asc", "desc"].includes(direction)
                ? direction
                : (sort === "created_at" ? "desc" : "asc"),
            per_page: S.config.perPageOptions?.includes(perPage) ? perPage : DEFAULT_PER_PAGE,
            page: Number.isInteger(page) && page > 0 ? page : 1,
        };
    }

    function syncUrl() {
        const params = new URLSearchParams();
        const filters = state.filters;
        if (filters.q) params.set("q", filters.q);
        if (filters.role) params.set("role", filters.role);
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
        if (filters.role) params.set("role", filters.role);
        params.set("sort", filters.sort);
        params.set("direction", filters.direction);
        params.set("per_page", String(filters.per_page));
        params.set("page", String(filters.page));
        return params.toString();
    }

    function syncControls() {
        if (els.search) els.search.value = state.filters.q;
        if (els.role) els.role.value = state.filters.role;
        if (els.perPage) els.perPage.value = String(state.filters.per_page);
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
        if (state.filters.role) {
            chips.push(`
                <span class="filter-chip">
                    Role: ${state.filters.role === "admin" ? "Administrators" : "Staff"}
                    <button type="button" data-clear-filter="role" aria-label="Remove role filter"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
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

    function renderRows(items) {
        if (!els.body) return;

        if (!items.length) {
            const filtered = state.filters.q || state.filters.role;
            els.body.innerHTML = filtered
                ? S.emptyTableRow(7, "No team members match these filters", "Try a different search or clear the active filters.", '<button type="button" class="btn btn-soft btn-sm" data-clear-all>Clear filters</button>')
                : S.emptyTableRow(7, "No team members yet", "Add a colleague to give them workspace access.", '<button type="button" class="btn btn-primary btn-sm" data-add-user><i class="bi bi-person-plus" aria-hidden="true"></i> Add team member</button>');
            return;
        }

        els.body.innerHTML = items.map((user) => {
            const isSelf = Number(user.id) === Number(S.user?.id);
            const projects = Number(user.assigned_projects_count) || 0;
            return `
                <tr>
                    <td data-label="Member">
                        <div class="cell-person">
                            ${S.userAvatar(user)}
                            <div class="cell-person-text">
                                <span class="cell-person-name d-block truncate">${S.escapeHtml(user.name)}${isSelf ? ' <span class="chip ms-1">You</span>' : ""}</span>
                                <a class="cell-person-sub d-block truncate" href="mailto:${S.escapeAttr(user.email)}">${S.escapeHtml(user.email)}</a>
                            </div>
                        </div>
                    </td>
                    <td data-label="Role">${roleBadge(user.role)}</td>
                    <td data-label="Job title">
                        <span class="cell-strong d-block">${S.escapeHtml(user.job_title || "—")}</span>
                        <span class="cell-person-sub">${S.escapeHtml(user.phone || "")}</span>
                    </td>
                    <td data-label="Assigned projects">
                        <span class="cell-strong tabular">${projects}</span>
                        <span class="cell-person-sub d-block">${projects === 1 ? "project" : "projects"}</span>
                    </td>
                    <td data-label="Last sign-in">
                        <span class="cell-strong d-block">${user.last_login_at ? S.formatDate(user.last_login_at) : "Never"}</span>
                        <span class="cell-person-sub">${user.last_login_at ? S.formatRelative(user.last_login_at) : "—"}</span>
                    </td>
                    <td data-label="Added">${S.formatDate(user.created_at)}</td>
                    <td class="text-end" data-label="Actions">
                        <div class="row-actions">
                            <button type="button" class="icon-button" data-user-action="edit" data-id="${user.id}" title="Edit ${S.escapeAttr(user.name)}" aria-label="Edit ${S.escapeAttr(user.name)}">
                                <i class="bi bi-pencil" aria-hidden="true"></i>
                            </button>
                            ${isSelf ? "" : `
                            <button type="button" class="icon-button text-danger" data-user-action="delete" data-id="${user.id}" title="Remove ${S.escapeAttr(user.name)}" aria-label="Remove ${S.escapeAttr(user.name)}">
                                <i class="bi bi-trash3" aria-hidden="true"></i>
                            </button>`}
                        </div>
                    </td>
                </tr>`;
        }).join("");
    }

    function renderMeta(meta) {
        if (!els.meta) return;
        els.meta.innerHTML = S.metaText(meta, "team members");
        S.renderPagination(els.pagination, meta, (page) => {
            state.filters.page = page;
            syncUrl();
            load();
        });
    }

    async function load() {
        state.requestToken += 1;
        const token = state.requestToken;

        if (els.meta) els.meta.textContent = "Loading team members…";
        table.setAttribute("aria-busy", "true");

        try {
            const response = await S.api(`/api/users?${apiQuery()}`);
            if (token !== state.requestToken) return;

            state.items = new Map((response.data || []).map((user) => [Number(user.id), user]));
            renderRows(response.data || []);
            renderMeta(response.meta);
            renderFilterChips();
            renderSortState();
        } catch (error) {
            if (token !== state.requestToken) return;
            if (els.body) els.body.innerHTML = S.errorTableRow(7, error.message);
            if (els.meta) els.meta.textContent = "Unable to load team members";
        } finally {
            if (token === state.requestToken) table.removeAttribute("aria-busy");
        }
    }

    function setField(field, value) {
        if (field) field.value = value === null || value === undefined ? "" : String(value);
    }

    function syncPasswordRequirement() {
        const editing = state.editingId !== null;
        const hasNewPassword = Boolean(els.password?.value);
        if (els.password) els.password.required = !editing;
        if (els.confirm) els.confirm.required = !editing || hasNewPassword;
        if (els.confirmRequired) els.confirmRequired.hidden = editing && !hasNewPassword;
    }

    function openUserForm(user = null) {
        if (!els.form) return;
        S.clearForm(els.form);
        S.clearFormErrors(els.form);
        state.editingId = user?.id ? Number(user.id) : null;

        setField(els.id, user?.id || "");
        setField(els.name, user?.name || "");
        setField(els.email, user?.email || "");
        setField(els.roleField, user?.role || "staff");
        setField(els.jobTitle, user?.job_title || "");
        setField(els.phone, user?.phone || "");

        if (els.modalTitle) els.modalTitle.textContent = user ? "Edit team member" : "Add team member";
        if (els.modalSubtitle) {
            els.modalSubtitle.textContent = user
                ? `Update access and profile details for ${user.name}.`
                : "Create a workspace account for a colleague.";
        }
        if (els.passwordLabel) {
            els.passwordLabel.innerHTML = user
                ? "New password"
                : 'Password <span class="req">*</span>';
        }
        if (els.passwordHint) {
            els.passwordHint.textContent = user
                ? "Leave blank to keep the current password."
                : "At least 8 characters with letters and numbers.";
        }
        if (els.save) {
            els.save.innerHTML = user
                ? '<i class="bi bi-check-lg" aria-hidden="true"></i> Save changes'
                : '<i class="bi bi-check-lg" aria-hidden="true"></i> Save member';
        }

        syncPasswordRequirement();
        S.showModal("userModal");
    }

    async function saveUser() {
        if (!els.form) return;
        S.clearFormErrors(els.form);
        if (!els.form.reportValidity()) return;

        const formData = new FormData(els.form);
        const id = state.editingId;
        const endpoint = id ? `/api/users/${id}` : "/api/users";
        const method = id ? "PATCH" : "POST";

        S.setButtonLoading(els.save, true, id ? "Saving changes…" : "Creating account…");
        try {
            const response = await S.apiForm(endpoint, method, formData);
            S.hideModal("userModal");
            S.toast(response.message || "Team member saved successfully.", "success");
            S.clearForm(els.form);
            state.editingId = null;

            if (Number(response.data?.id) === Number(S.user?.id)) {
                S.updateUserChrome(response.data);
                document.querySelectorAll(".sidebar-user .avatar, .user-trigger .avatar").forEach((element) => {
                    element.textContent = response.data.initials || S.initials(response.data.name);
                });
            }

            await load();
        } catch (error) {
            S.handleApiError(error, els.form);
        } finally {
            S.setButtonLoading(els.save, false);
        }
    }

    function findUser(id) {
        return state.items.get(Number(id)) || null;
    }

    /* ---------------------------------------------------------------------
       Events
       --------------------------------------------------------------------- */

    els.add?.addEventListener("click", () => openUserForm());

    els.save?.addEventListener("click", saveUser);
    els.form?.addEventListener("submit", (event) => {
        event.preventDefault();
        saveUser();
    });

    els.password?.addEventListener("input", syncPasswordRequirement);

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

    els.role?.addEventListener("change", () => {
        state.filters.role = els.role.value;
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
        state.filters.role = "";
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
                state.filters.direction = sort === "created_at" ? "desc" : "asc";
            }
            state.filters.page = 1;
            syncUrl();
            load();
        });
    });

    document.addEventListener("click", (event) => {
        if (event.target.closest("[data-add-user]")) {
            event.preventDefault();
            openUserForm();
            return;
        }

        const action = event.target.closest("[data-user-action]");
        if (action) {
            const user = findUser(action.dataset.id);
            if (!user) return;

            if (action.dataset.userAction === "edit") {
                openUserForm(user);
            } else if (action.dataset.userAction === "delete") {
                S.confirmAction({
                    title: "Remove team member",
                    message: `Remove ${user.name} from the workspace? Their assigned projects will become unassigned.`,
                    confirmLabel: "Remove member",
                    onConfirm: async () => {
                        const response = await S.api(`/api/users/${user.id}`, { method: "DELETE" });
                        S.toast(response.message || "Team member removed successfully.", "success");
                        await load();
                    },
                });
            }
            return;
        }

        const clearFilter = event.target.closest("[data-clear-filter]");
        if (clearFilter) {
            if (clearFilter.dataset.clearFilter === "q") {
                state.filters.q = "";
                if (els.search) els.search.value = "";
            } else if (clearFilter.dataset.clearFilter === "role") {
                state.filters.role = "";
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
            state.filters.role = "";
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

    els.modal?.addEventListener("hidden.bs.modal", () => {
        state.editingId = null;
    });

    window.addEventListener("popstate", () => {
        parseFilters();
        syncControls();
        load();
    });

    /* ---------------------------------------------------------------------
       Boot
       --------------------------------------------------------------------- */

    parseFilters();
    syncControls();
    renderFilterChips();
    renderSortState();
    syncUrl();
    load();
})();
