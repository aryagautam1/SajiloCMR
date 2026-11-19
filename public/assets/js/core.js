/* ==========================================================================
   Sajilo CRM — shared frontend runtime
   Vanilla JavaScript. No framework, no build step required.
   ========================================================================== */

(function () {
    "use strict";

    const CRM = window.CRM || {};
    const config = CRM.config || {};
    const Sajilo = (window.Sajilo = window.Sajilo || {});

    const $ = (selector, root = document) => root.querySelector(selector);
    const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));

    const ICONS = {
        success: "bi-check-circle-fill",
        danger: "bi-exclamation-octagon-fill",
        warning: "bi-exclamation-triangle-fill",
        info: "bi-info-circle-fill",
    };

    function escapeHtml(value) {
        return String(value ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function escapeAttr(value) {
        return escapeHtml(value).replace(/`/g, "&#096;");
    }

    function formatDate(value, options = {}) {
        if (!value) return "—";
        let date;
        if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
            const [year, month, day] = value.split("-").map(Number);
            date = new Date(year, month - 1, day);
        } else {
            date = new Date(value);
        }
        if (Number.isNaN(date.getTime())) return "—";
        return new Intl.DateTimeFormat("en-GB", Object.assign({
            day: "2-digit",
            month: "short",
            year: "numeric",
        }, options)).format(date);
    }

    function formatDateTime(value) {
        if (!value) return "—";
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return "—";
        return new Intl.DateTimeFormat("en-GB", {
            day: "2-digit",
            month: "short",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        }).format(date);
    }

    function formatRelative(value) {
        if (!value) return "";
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return "";
        const diff = Math.round((date.getTime() - Date.now()) / 1000);
        const rtf = new Intl.RelativeTimeFormat("en", { numeric: "auto" });
        const units = [
            ["year", 31536000],
            ["month", 2592000],
            ["week", 604800],
            ["day", 86400],
            ["hour", 3600],
            ["minute", 60],
        ];
        for (const [unit, seconds] of units) {
            if (Math.abs(diff) >= seconds || unit === "minute") {
                return rtf.format(Math.round(diff / seconds), unit);
            }
        }
        return "just now";
    }

    function formatCurrency(value, withDecimals = false) {
        if (value === null || value === undefined || value === "") return "—";
        const number = Number(value);
        if (Number.isNaN(number)) return "—";
        return `${config.currency || "NPR"} ${new Intl.NumberFormat("en-US", {
            minimumFractionDigits: withDecimals ? 2 : 0,
            maximumFractionDigits: withDecimals ? 2 : 0,
        }).format(number)}`;
    }

    function initials(name) {
        return String(name || "")
            .trim()
            .split(/\s+/)
            .slice(0, 2)
            .map((part) => part.charAt(0).toUpperCase())
            .join("") || "?";
    }

    function toneFor(id) {
        return `tone-${((Number(id) || 1) % 6) + 1}`;
    }

    function userAvatar(user, size = "sm") {
        const sizeClass = size === "lg" ? "avatar-lg" : size === "sm" ? "avatar-sm" : "";
        return `<span class="avatar ${sizeClass} ${toneFor(user?.id)}" title="${escapeAttr(user?.name || "User")}" aria-hidden="true">${escapeHtml(user?.initials || initials(user?.name))}</span>`;
    }

    function clientLogo(client, size = "md") {
        const sizeStyle = size === "sm" ? "width:32px;height:32px;font-size:.65rem;" : "";
        if (client?.logo_url) {
            return `<span class="client-logo" style="${sizeStyle}"><img src="${escapeAttr(client.logo_url)}" alt="${escapeAttr(client.company_name || "Client logo")}" loading="lazy"></span>`;
        }
        return `<span class="client-logo avatar ${toneFor(client?.id)}" style="${sizeStyle}" aria-hidden="true">${escapeHtml(client?.initials || initials(client?.company_name || client?.name))}</span>`;
    }

    function optionLabel(group, key) {
        if (!group || !group[key]) return key ? key.charAt(0).toUpperCase() + key.slice(1) : "—";
        return group[key].label;
    }

    function statusBadge(status, type = "project") {
        const group = type === "client" ? config.clientStatuses : config.projectStatuses;
        const definition = group?.[status] || { label: status, tone: "secondary" };
        return `<span class="badge badge-soft tone-${escapeAttr(definition.tone || "secondary")}">${escapeHtml(definition.label)}</span>`;
    }

    function priorityBadge(priority) {
        const definition = config.priorities?.[priority] || { label: priority };
        return `<span class="priority"><span class="priority-dot ${escapeAttr(priority)}" aria-hidden="true"></span>${escapeHtml(definition.label)}</span>`;
    }

    function progressMarkup(value, tone = "", label = "Progress") {
        const progress = Math.max(0, Math.min(100, Number(value) || 0));
        return `<span class="progress-track" role="progressbar" aria-label="${escapeAttr(label)}" aria-valuenow="${progress}" aria-valuemin="0" aria-valuemax="100" aria-valuetext="${progress}%"><span class="progress-fill ${tone ? "tone-" + tone : ""}" style="width:${progress}%"></span></span>`;
    }

    function debounce(fn, wait = 300) {
        let timer;
        return function debounced(...args) {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, args), wait);
        };
    }

    function toast(message, tone = "success", title = "") {
        const stack = $("#toastStack");
        if (!stack || !message) return;
        const item = document.createElement("div");
        item.className = `toast-item tone-${tone}`;
        item.setAttribute("role", tone === "danger" ? "alert" : "status");
        item.innerHTML = `
            <i class="bi ${ICONS[tone] || ICONS.info} toast-icon" aria-hidden="true"></i>
            <div class="toast-message">${title ? `<strong class="d-block">${escapeHtml(title)}</strong>` : ""}${escapeHtml(message)}</div>
            <button type="button" class="toast-close" aria-label="Dismiss notification"><i class="bi bi-x-lg" aria-hidden="true"></i></button>`;
        stack.appendChild(item);
        const remove = () => {
            item.classList.add("is-hiding");
            setTimeout(() => item.remove(), 220);
        };
        const timer = setTimeout(remove, 5200);
        $(".toast-close", item)?.addEventListener("click", () => {
            clearTimeout(timer);
            remove();
        });
    }

    class ApiError extends Error {
        constructor(message, status, errors, payload) {
            super(message);
            this.name = "ApiError";
            this.status = status;
            this.errors = errors || {};
            this.payload = payload;
        }
    }

    async function api(url, options = {}) {
        const settings = Object.assign({}, options);
        const method = (settings.method || "GET").toUpperCase();
        const headers = Object.assign({
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest",
        }, settings.headers || {});

        if (method !== "GET" && method !== "HEAD") {
            headers["X-CSRF-TOKEN"] = CRM.csrf
                || $('meta[name="csrf-token"]')?.getAttribute("content")
                || "";
        }

        if (settings.body && !(settings.body instanceof FormData) && !headers["Content-Type"]) {
            headers["Content-Type"] = "application/json";
        }

        settings.headers = headers;
        settings.credentials = "same-origin";

        let response;
        try {
            response = await fetch(url, settings);
        } catch (networkError) {
            throw new ApiError("Network error. Check your connection and try again.", 0, {}, null);
        }

        const text = await response.text();
        let payload = null;
        if (text) {
            try {
                payload = JSON.parse(text);
            } catch (parseError) {
                payload = null;
            }
        }

        if (!response.ok) {
            if (response.status === 401) {
                toast("Your session has expired. Redirecting to sign in…", "warning");
                setTimeout(() => { window.location.href = "/login"; }, 1100);
            }
            const message = payload?.message
                || (response.status === 419 ? "The page expired. Please refresh and try again." : "Something went wrong. Please try again.");
            const error = new ApiError(message, response.status, payload?.errors || {}, payload);
            throw error;
        }

        return payload;
    }

    async function apiForm(url, method, formData) {
        if (method && method !== "POST") {
            formData.append("_method", method);
        }
        return api(url, {
            method: "POST",
            body: formData,
            headers: method && method !== "POST" ? { "X-HTTP-Method-Override": method } : {},
        });
    }

    function handleApiError(error, form = null) {
        if (form && error?.errors && Object.keys(error.errors).length) {
            showFormErrors(form, error.errors, error.message);
            return;
        }
        if (error?.status === 422 && error.message) {
            toast(error.message, "danger");
            return;
        }
        toast(error?.message || "Something went wrong. Please try again.", "danger");
    }

    function clearFormErrors(form) {
        if (!form) return;
        $$(".is-invalid", form).forEach((input) => input.classList.remove("is-invalid"));
        $$("[data-error-for]", form).forEach((el) => {
            el.textContent = "";
            el.classList.remove("show");
        });
        const general = $("[data-form-error]", form);
        if (general) {
            general.textContent = "";
            general.hidden = true;
        }
    }

    function showFormErrors(form, errors, message = "") {
        if (!form) return;
        clearFormErrors(form);
        let firstField = null;

        Object.entries(errors || {}).forEach(([field, messages]) => {
            const input = form.querySelector(`[name="${CSS.escape(field)}"]`);
            const target = form.querySelector(`[data-error-for="${CSS.escape(field)}"]`);
            const text = Array.isArray(messages) ? messages[0] : String(messages);
            if (input) {
                input.classList.add("is-invalid");
                if (!firstField) firstField = input;
            }
            if (target) {
                target.textContent = text;
                target.classList.add("show");
            }
        });

        const general = $("[data-form-error]", form);
        if (general) {
            general.textContent = message || "Please correct the highlighted fields and try again.";
            general.hidden = false;
        }

        if (firstField) {
            firstField.focus({ preventScroll: false });
            firstField.scrollIntoView({ behavior: "smooth", block: "center" });
        }
    }

    function setButtonLoading(button, loading, loadingText = "Saving…") {
        if (!button) return;
        if (loading) {
            if (!button.dataset.originalHtml) button.dataset.originalHtml = button.innerHTML;
            button.disabled = true;
            button.innerHTML = `<span class="spinner-border spinner-border-sm" aria-hidden="true"></span><span>${escapeHtml(loadingText)}</span>`;
        } else {
            button.disabled = false;
            if (button.dataset.originalHtml) {
                button.innerHTML = button.dataset.originalHtml;
                delete button.dataset.originalHtml;
            }
        }
    }

    function clearForm(form) {
        if (!form) return;
        form.reset();
        clearFormErrors(form);
        $$("input[type=hidden]", form).forEach((input) => {
            if (input.dataset.keep !== "true") input.value = "";
        });
        const logoPreview = $("[data-logo-preview]", form);
        if (logoPreview) logoPreview.innerHTML = '<i class="bi bi-image" aria-hidden="true"></i>';
        const range = $('input[type="range"]', form);
        if (range) {
            const output = $(`[data-range-output="${range.name}"]`, form);
            if (output) output.textContent = `${range.value}%`;
        }
    }

    function showModal(id) {
        const element = typeof id === "string" ? document.getElementById(id) : id;
        if (!element || !window.bootstrap) return null;
        return window.bootstrap.Modal.getOrCreateInstance(element).show();
    }

    function hideModal(id) {
        const element = typeof id === "string" ? document.getElementById(id) : id;
        if (!element || !window.bootstrap) return;
        window.bootstrap.Modal.getOrCreateInstance(element).hide();
    }

    let confirmCallback = null;
    let confirmButtonOriginalClass = "btn-danger";

    function confirmAction(options = {}) {
        const modalElement = document.getElementById("confirmModal");
        if (!modalElement) {
            if (window.confirm(options.message || "Are you sure?")) {
                Promise.resolve(options.onConfirm?.()).catch((error) => handleApiError(error));
            }
            return;
        }
        $("#confirmModalTitle").textContent = options.title || "Confirm action";
        $("#confirmModalMessage").textContent = options.message || "This action cannot be undone.";
        const button = $("#confirmModalConfirm");
        button.textContent = options.confirmLabel || "Confirm";
        button.className = `btn ${options.tone === "primary" ? "btn-primary" : "btn-danger"}`;
        confirmCallback = options.onConfirm;
        showModal(modalElement);
    }

    function initConfirmModal() {
        const button = $("#confirmModalConfirm");
        if (!button) return;
        confirmButtonOriginalClass = button.className;
        button.addEventListener("click", async () => {
            if (!confirmCallback) return;
            const callback = confirmCallback;
            setButtonLoading(button, true, "Please wait…");
            try {
                await callback();
                hideModal("confirmModal");
            } catch (error) {
                hideModal("confirmModal");
                handleApiError(error);
            } finally {
                setButtonLoading(button, false);
                button.className = confirmButtonOriginalClass;
                confirmCallback = null;
            }
        });
        $("#confirmModal")?.addEventListener("hidden.bs.modal", () => {
            confirmCallback = null;
        });
    }

    function emptyTableRow(colspan, title, message, action = "") {
        return `<tr><td colspan="${colspan}" style="padding:0!important;border:0!important;">
            <div class="empty-state">
                <span class="empty-icon"><i class="bi bi-inbox" aria-hidden="true"></i></span>
                <h3>${escapeHtml(title)}</h3>
                <p>${escapeHtml(message)}</p>
                ${action}
            </div>
        </td></tr>`;
    }

    function errorTableRow(colspan, message = "We could not load this data.") {
        return `<tr><td colspan="${colspan}" style="padding:0!important;border:0!important;">
            <div class="empty-state">
                <span class="empty-icon" style="background:var(--danger-soft);color:var(--danger)"><i class="bi bi-wifi-off" aria-hidden="true"></i></span>
                <h3>Unable to load data</h3>
                <p>${escapeHtml(message)}</p>
                <button type="button" class="btn btn-soft btn-sm" data-retry-load><i class="bi bi-arrow-clockwise" aria-hidden="true"></i> Try again</button>
            </div>
        </td></tr>`;
    }

    function skeletonRows(colspan, rows = 6) {
        let html = "";
        for (let i = 0; i < rows; i += 1) {
            html += `<tr class="skeleton-row"><td colspan="${colspan}"><div class="skeleton-line ${i % 2 ? "short" : ""}"></div></td></tr>`;
        }
        return html;
    }

    function renderPagination(container, meta, onPage) {
        if (!container) return;
        if (!meta || meta.last_page <= 1) {
            container.innerHTML = "";
            return;
        }
        const current = Number(meta.current_page);
        const last = Number(meta.last_page);
        const pages = new Set([1, last, current, current - 1, current + 1]);
        if (current <= 3) [2, 3, 4].forEach((page) => pages.add(page));
        if (current >= last - 2) [last - 1, last - 2, last - 3].forEach((page) => pages.add(page));
        const list = Array.from(pages).filter((page) => page >= 1 && page <= last).sort((a, b) => a - b);

        let html = '<ul class="pagination">';
        html += `<li class="page-item ${current === 1 ? "disabled" : ""}"><button type="button" class="page-link" data-page="${current - 1}" aria-label="Previous page"><i class="bi bi-chevron-left" aria-hidden="true"></i></button></li>`;
        let previous = null;
        for (const page of list) {
            if (previous !== null && page - previous > 1) {
                html += '<li class="page-item disabled"><span class="page-link">…</span></li>';
            }
            html += `<li class="page-item ${page === current ? "active" : ""}"><button type="button" class="page-link" data-page="${page}" ${page === current ? 'aria-current="page"' : ""}>${page}</button></li>`;
            previous = page;
        }
        html += `<li class="page-item ${current === last ? "disabled" : ""}"><button type="button" class="page-link" data-page="${current + 1}" aria-label="Next page"><i class="bi bi-chevron-right" aria-hidden="true"></i></button></li>`;
        html += "</ul>";
        container.innerHTML = html;

        $$("[data-page]", container).forEach((button) => {
            button.addEventListener("click", () => {
                const page = Number(button.dataset.page);
                if (page >= 1 && page <= last && page !== current) onPage(page);
            });
        });
    }

    function metaText(meta, noun = "records") {
        if (!meta || !meta.total) return `No ${noun} found`;
        const from = meta.from ?? 0;
        const to = meta.to ?? 0;
        return `Showing <strong>${from}–${to}</strong> of <strong>${meta.total}</strong> ${noun}`;
    }

    /* ---------------------------------------------------------------------
       Theme
       --------------------------------------------------------------------- */

    const THEME_KEY = "sajilo-theme";
    const media = window.matchMedia("(prefers-color-scheme: dark)");

    function resolvedTheme(mode) {
        if (mode === "system") return media.matches ? "dark" : "light";
        return mode === "dark" ? "dark" : "light";
    }

    function applyTheme(mode, persist = true) {
        const nextMode = ["light", "dark", "system"].includes(mode) ? mode : "light";
        const resolved = resolvedTheme(nextMode);
        document.documentElement.setAttribute("data-theme", resolved);
        document.documentElement.setAttribute("data-bs-theme", resolved);
        $('meta[name="theme-color"]')?.setAttribute("content", resolved === "dark" ? "#0b1120" : "#2563eb");
        const icon = $("#themeIcon");
        if (icon) icon.className = `bi ${resolved === "dark" ? "bi-sun" : "bi-moon-stars"}`;
        if (persist) localStorage.setItem(THEME_KEY, nextMode);
        document.dispatchEvent(new CustomEvent("sajilo:theme", { detail: { mode: nextMode, theme: resolved } }));
    }

    function getThemeMode() {
        return localStorage.getItem(THEME_KEY) || "light";
    }

    function initTheme() {
        const mode = getThemeMode();
        applyTheme(mode, false);
        $("#themeToggle")?.addEventListener("click", () => {
            const next = resolvedTheme(getThemeMode()) === "dark" ? "light" : "dark";
            applyTheme(next);
        });
        media.addEventListener("change", () => {
            if (getThemeMode() === "system") applyTheme("system", false);
        });
    }

    /* ---------------------------------------------------------------------
       Sidebar + shortcuts
       --------------------------------------------------------------------- */

    function isDesktop() {
        return window.matchMedia("(min-width: 992px)").matches;
    }

    function initSidebar() {
        const body = document.body;
        const toggle = $("#sidebarToggle");
        const close = $("#sidebarClose");
        const backdrop = $("#sidebarBackdrop");

        if (localStorage.getItem("sajilo-sidebar-collapsed") === "1" && isDesktop()) {
            body.classList.add("sidebar-collapsed");
        }

        const syncExpanded = () => {
            if (!toggle) return;
            toggle.setAttribute("aria-expanded", String(isDesktop()
                ? !body.classList.contains("sidebar-collapsed")
                : body.classList.contains("sidebar-open")));
        };

        const closeMobile = () => {
            body.classList.remove("sidebar-open");
            syncExpanded();
        };

        toggle?.addEventListener("click", () => {
            if (isDesktop()) {
                body.classList.toggle("sidebar-collapsed");
                localStorage.setItem("sajilo-sidebar-collapsed", body.classList.contains("sidebar-collapsed") ? "1" : "0");
            } else {
                body.classList.toggle("sidebar-open");
            }
            syncExpanded();
        });
        close?.addEventListener("click", closeMobile);
        backdrop?.addEventListener("click", closeMobile);
        syncExpanded();

        window.addEventListener("resize", () => {
            if (isDesktop()) closeMobile();
            syncExpanded();
        });

        $$(".sidebar .nav-link").forEach((link) => {
            link.addEventListener("click", () => {
                if (!isDesktop()) closeMobile();
            });
        });
    }

    function initShortcuts() {
        document.addEventListener("keydown", (event) => {
            const target = event.target;
            const typing = target && (target.tagName === "INPUT" || target.tagName === "TEXTAREA" || target.tagName === "SELECT" || target.isContentEditable);
            if (event.key === "/" && !typing && !event.metaKey && !event.ctrlKey && !event.altKey) {
                const search = $(".global-search input");
                if (search) {
                    event.preventDefault();
                    search.focus();
                }
            }
            if (event.key === "Escape") {
                document.body.classList.remove("sidebar-open");
            }
        });
    }

    /* ---------------------------------------------------------------------
       Charts
       --------------------------------------------------------------------- */

    function cssVar(name) {
        return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    }

    function chartDefaults() {
        if (!window.Chart) return;
        Chart.defaults.font.family = '"Inter", system-ui, sans-serif';
        Chart.defaults.font.size = 11;
        Chart.defaults.color = cssVar("--muted");
        Chart.defaults.borderColor = cssVar("--border");
        Chart.defaults.plugins.legend.labels.usePointStyle = true;
        Chart.defaults.plugins.legend.labels.boxWidth = 7;
        Chart.defaults.plugins.legend.labels.boxHeight = 7;
        Chart.defaults.plugins.legend.labels.padding = 16;
    }

    function chartOptions() {
        return {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: "index", intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: cssVar("--ink"),
                    titleColor: cssVar("--surface"),
                    bodyColor: cssVar("--surface"),
                    padding: 10,
                    cornerRadius: 8,
                    displayColors: true,
                    boxPadding: 4,
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: { color: cssVar("--muted"), font: { size: 10.5 } },
                },
                y: {
                    beginAtZero: true,
                    border: { display: false },
                    grid: { color: cssVar("--border") },
                    ticks: { color: cssVar("--muted"), precision: 0, font: { size: 10.5 } },
                },
            },
        };
    }

    function destroyChart(chart) {
        if (chart && typeof chart.destroy === "function") chart.destroy();
    }

    function initUtilityListeners() {
        document.addEventListener("click", (event) => {
            const toggle = event.target.closest("[data-password-toggle]");
            if (toggle) {
                const input = document.getElementById(toggle.dataset.passwordToggle);
                if (input) {
                    const show = input.type === "password";
                    input.type = show ? "text" : "password";
                    toggle.innerHTML = `<i class="bi ${show ? "bi-eye-slash" : "bi-eye"}" aria-hidden="true"></i>`;
                    toggle.setAttribute("aria-label", show ? "Hide password" : "Show password");
                }
            }
        });

        document.addEventListener("input", (event) => {
            const input = event.target;
            if (input.classList?.contains("is-invalid")) {
                input.classList.remove("is-invalid");
                const form = input.closest("form");
                const target = form?.querySelector(`[data-error-for="${CSS.escape(input.name)}"]`);
                if (target) {
                    target.textContent = "";
                    target.classList.remove("show");
                }
            }
        });

        $$('input[type="range"][data-range]').forEach((range) => {
            const output = document.querySelector(`[data-range-output="${range.name}"]`);
            const sync = () => { if (output) output.textContent = `${range.value}%`; };
            range.addEventListener("input", sync);
            sync();
        });
    }

    function initFlash() {
        const flash = $("#flashMessages");
        if (!flash) return;
        setTimeout(() => {
            if (flash.dataset.success) toast(flash.dataset.success, "success");
            if (flash.dataset.error) toast(flash.dataset.error, "danger");
        }, 120);
    }

    function updateUserChrome(user) {
        if (!user) return;
        $$(".user-trigger .user-name").forEach((el) => { el.textContent = user.name; });
        $$(".sidebar-user .user-name").forEach((el) => { el.textContent = user.name; });
        $$(".user-trigger .user-role").forEach((el) => { el.textContent = user.role === "admin" ? "Administrator" : "Staff"; });
        $$(".sidebar-user .user-role").forEach((el) => { el.textContent = user.role === "admin" ? "Administrator" : "Staff"; });
        if (CRM.user) Object.assign(CRM.user, user);
    }

    function init() {
        initTheme();
        initSidebar();
        initShortcuts();
        initConfirmModal();
        initUtilityListeners();
        initFlash();
        chartDefaults();
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }

    Object.assign(Sajilo, {
        $, $$, api, apiForm, toast, confirmAction, showModal, hideModal,
        setButtonLoading, clearForm, clearFormErrors, showFormErrors, handleApiError,
        emptyTableRow, errorTableRow, skeletonRows, renderPagination, metaText,
        escapeHtml, escapeAttr, formatDate, formatDateTime, formatRelative, formatCurrency,
        initials, userAvatar, clientLogo, statusBadge, priorityBadge, progressMarkup,
        debounce, applyTheme, getThemeMode, resolvedTheme,
        chartOptions, destroyChart, chartDefaults, updateUserChrome,
        toneFor, config, user: CRM.user,
    });
})();
