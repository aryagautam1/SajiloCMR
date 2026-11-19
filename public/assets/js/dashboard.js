(function () {
    "use strict";

    const S = window.Sajilo;
    if (!document.getElementById("dashboardStats")) return;

    const state = {
        stats: null,
        charts: null,
        monthlyChart: null,
        statusChart: null,
    };

    const numberFormatter = new Intl.NumberFormat("en-GB");
    const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    function animateNumber(element, target) {
        const value = Number(target) || 0;
        if (reduceMotion) {
            element.textContent = numberFormatter.format(value);
            return;
        }
        const start = performance.now();
        const duration = 520;
        const initial = Number(String(element.textContent).replace(/[^0-9.-]/g, "")) || 0;

        function frame(now) {
            const progress = Math.min(1, (now - start) / duration);
            const eased = 1 - Math.pow(1 - progress, 3);
            element.textContent = numberFormatter.format(Math.round(initial + (value - initial) * eased));
            if (progress < 1) requestAnimationFrame(frame);
        }
        requestAnimationFrame(frame);
    }

    function setStat(key, value) {
        const element = document.querySelector(`[data-stat="${key}"]`);
        if (element) animateNumber(element, value);
    }

    function setMeta(key, html, tone = "tone-muted") {
        const element = document.querySelector(`[data-stat-meta="${key}"]`);
        if (!element) return;
        element.className = `stat-meta ${tone}`;
        element.innerHTML = html;
    }

    function renderCards(data) {
        const cards = data.cards;
        setStat("total_clients", cards.total_clients);
        setStat("active_clients", cards.active_clients);
        setStat("total_projects", cards.total_projects);
        setStat("ongoing_projects", cards.ongoing_projects);
        setStat("completed_projects", cards.completed_projects);
        setStat("pending_projects", cards.pending_projects);

        const activeShare = cards.total_clients ? Math.round((cards.active_clients / cards.total_clients) * 100) : 0;
        setMeta("total_clients", `<span class="dot"></span>${cards.prospect_clients} prospects · ${cards.inactive_clients} inactive`);
        setMeta("active_clients", `<span class="dot"></span>${activeShare}% of the client base`, "tone-success");
        setMeta("total_projects", `<span class="dot"></span>Across ${cards.total_clients} clients`);
        setMeta(
            "ongoing_projects",
            `<span class="dot"></span>${cards.on_hold_projects} on hold${cards.overdue_projects ? " · " + cards.overdue_projects + " overdue" : ""}`,
            cards.overdue_projects ? "tone-warning" : "tone-muted"
        );
        setMeta("completed_projects", `<span class="dot"></span>${cards.cancelled_projects} cancelled`, "tone-muted");
        setMeta(
            "pending_projects",
            `<span class="dot"></span>${cards.overdue_projects ? cards.overdue_projects + " overdue · " : ""}Awaiting kick-off`,
            cards.overdue_projects ? "tone-danger" : "tone-muted"
        );

        const completionRate = cards.total_projects ? Math.round((cards.completed_projects / cards.total_projects) * 100) : 0;
        const snapshot = {
            completion_rate: `${completionRate}%`,
            overdue_projects: numberFormatter.format(cards.overdue_projects),
            active_budget: S.formatCurrency(cards.active_budget),
            total_budget: S.formatCurrency(cards.total_budget),
            team_members: numberFormatter.format(cards.team_members),
        };
        Object.entries(snapshot).forEach(([key, value]) => {
            const element = document.querySelector(`[data-snapshot="${key}"]`);
            if (element) element.textContent = value;
        });
    }

    function renderRecentProjects(projects) {
        const body = document.getElementById("recentProjectsBody");
        if (!body) return;

        if (!projects.length) {
            body.innerHTML = S.emptyTableRow(
                4,
                "No projects yet",
                "Create your first project to start tracking progress and deadlines.",
                `<a class="btn btn-primary btn-sm" href="${S.config.routes?.projects || "/projects"}?new=1"><i class="bi bi-plus-lg"></i> New project</a>`
            );
            return;
        }

        body.innerHTML = projects.map((project) => {
            const clientName = project.client?.company_name || "—";
            return `
                <tr>
                    <td data-label="Project">
                        <a class="cell-person" href="/projects/${project.id}" style="color:inherit;">
                            <span class="client-logo avatar ${S.toneFor(project.id)}" aria-hidden="true">${S.escapeHtml(S.initials(project.project_name))}</span>
                            <span class="cell-person-text">
                                <span class="cell-person-name d-block truncate">${S.escapeHtml(project.project_name)}</span>
                                <span class="cell-person-sub">${S.escapeHtml(project.code)}</span>
                            </span>
                        </a>
                    </td>
                    <td data-label="Client"><span class="cell-strong">${S.escapeHtml(clientName)}</span></td>
                    <td data-label="Status">${S.statusBadge(project.status, "project")}</td>
                    <td data-label="Progress" style="min-width: 150px;">
                        <div class="d-flex align-items-center gap-2">
                            <div style="flex:1 1 auto;">${S.progressMarkup(project.progress, progressTone(project.status))}</div>
                            <span class="fs-12 fw-600 text-ink tabular">${project.progress}%</span>
                        </div>
                    </td>
                </tr>`;
        }).join("");
    }

    function progressTone(status) {
        return { in_progress: "primary", completed: "success", on_hold: "warning", cancelled: "danger" }[status] || "";
    }

    function renderRecentClients(clients) {
        const list = document.getElementById("recentClientsList");
        if (!list) return;

        if (!clients.length) {
            list.innerHTML = `<div class="empty-state"><span class="empty-icon"><i class="bi bi-people"></i></span><h3>No clients yet</h3><p>Add your first client to begin building the directory.</p></div>`;
            return;
        }

        list.innerHTML = clients.map((client) => `
            <a class="client-project-item" href="/clients?open=${client.id}" style="border:0;border-bottom:1px solid var(--border);border-radius:0;padding:11px 8px;">
                ${S.clientLogo(client)}
                <span style="min-width:0;flex:1 1 auto;">
                    <span class="project-name d-block truncate">${S.escapeHtml(client.company_name)}</span>
                    <span class="project-meta d-block truncate">${S.escapeHtml(client.name)} · ${client.projects_count || 0} project${client.projects_count === 1 ? "" : "s"}</span>
                </span>
                <span class="ms-auto">${S.statusBadge(client.status, "client")}</span>
            </a>
        `).join("");
    }

    function renderProgress(projects) {
        const container = document.getElementById("progressOverview");
        if (!container) return;

        if (!projects.length) {
            container.innerHTML = `<div class="empty-state"><span class="empty-icon"><i class="bi bi-activity"></i></span><h3>No active projects</h3><p>Projects that are planning, in progress or on hold will appear here.</p></div>`;
            return;
        }

        container.innerHTML = projects.map((project) => `
            <div class="progress-row">
                <div class="progress-row-head">
                    <div style="min-width:0;">
                        <a class="progress-row-title d-block truncate" href="/projects/${project.id}">${S.escapeHtml(project.project_name)}</a>
                        <span class="progress-row-sub">${S.escapeHtml(project.client?.company_name || "—")} · ${S.escapeHtml(project.status_label)}</span>
                    </div>
                    <span class="progress-row-value">${project.progress}%</span>
                </div>
                ${S.progressMarkup(project.progress, progressTone(project.status))}
            </div>
        `).join("");
    }

    function renderCharts() {
        if (!state.charts) return;
        if (!window.Chart) return;

        const data = state.charts;
        const monthly = data.monthly_activity;
        const totalActivity = monthly.created.reduce((sum, value) => sum + value, 0) + monthly.completed.reduce((sum, value) => sum + value, 0);
        const monthlyCanvas = document.getElementById("monthlyChart");
        const monthlyEmpty = document.getElementById("monthlyEmpty");

        S.destroyChart(state.monthlyChart);
        state.monthlyChart = null;
        if (monthlyCanvas) monthlyCanvas.style.display = totalActivity ? "block" : "none";
        monthlyEmpty?.classList.toggle("show", !totalActivity);

        if (totalActivity) {
            const options = S.chartOptions();
            options.scales.x.stacked = false;
            options.scales.y.beginAtZero = true;
            options.scales.y.ticks.precision = 0;
            options.plugins.tooltip.callbacks = {
                label: (context) => ` ${context.dataset.label}: ${context.parsed.y}`,
            };

            state.monthlyChart = new Chart(monthlyCanvas, {
                type: "bar",
                data: {
                    labels: monthly.labels,
                    datasets: [
                        {
                            label: "Created",
                            data: monthly.created,
                            backgroundColor: "rgba(37, 99, 235, 0.78)",
                            hoverBackgroundColor: "#1d4ed8",
                            borderRadius: 5,
                            borderSkipped: false,
                            maxBarThickness: 26,
                        },
                        {
                            label: "Completed",
                            type: "line",
                            data: monthly.completed,
                            borderColor: "#16a34a",
                            backgroundColor: "rgba(22, 163, 74, 0.1)",
                            borderWidth: 2,
                            pointRadius: 3,
                            pointHoverRadius: 5,
                            pointBackgroundColor: "#16a34a",
                            pointBorderColor: "#ffffff",
                            pointBorderWidth: 2,
                            tension: 0.35,
                            fill: false,
                        },
                    ],
                },
                options,
            });
        }

        const status = data.project_status || [];
        const statusTotal = status.reduce((sum, item) => sum + item.count, 0);
        const statusCanvas = document.getElementById("statusChart");
        const statusEmpty = document.getElementById("statusEmpty");
        S.destroyChart(state.statusChart);
        state.statusChart = null;
        if (statusCanvas) statusCanvas.style.display = statusTotal ? "block" : "none";
        statusEmpty?.classList.toggle("show", !statusTotal);

        const legend = document.getElementById("statusLegend");
        if (legend) {
            legend.innerHTML = status.map((item) => `
                <span class="legend-item">
                    <span class="legend-dot" style="background:${S.escapeAttr(item.color)}"></span>
                    ${S.escapeHtml(item.label)}
                    <span class="legend-value">${item.count}</span>
                </span>
            `).join("");
        }

        if (statusTotal) {
            const centerLabel = {
                id: "centerLabel",
                afterDraw(chart) {
                    const { ctx, chartArea } = chart;
                    if (!chartArea) return;
                    const x = (chartArea.left + chartArea.right) / 2;
                    const y = (chartArea.top + chartArea.bottom) / 2;
                    ctx.save();
                    ctx.textAlign = "center";
                    ctx.textBaseline = "middle";
                    ctx.fillStyle = getComputedStyle(document.documentElement).getPropertyValue("--ink").trim();
                    ctx.font = '700 1.45rem "Inter", sans-serif';
                    ctx.fillText(String(statusTotal), x, y - 7);
                    ctx.fillStyle = getComputedStyle(document.documentElement).getPropertyValue("--muted").trim();
                    ctx.font = '500 0.63rem "Inter", sans-serif';
                    ctx.fillText("PROJECTS", x, y + 15);
                    ctx.restore();
                },
            };

            state.statusChart = new Chart(statusCanvas, {
                type: "doughnut",
                data: {
                    labels: status.map((item) => item.label),
                    datasets: [{
                        data: status.map((item) => item.count),
                        backgroundColor: status.map((item) => item.color),
                        borderColor: getComputedStyle(document.documentElement).getPropertyValue("--surface").trim(),
                        borderWidth: 3,
                        hoverOffset: 5,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: "68%",
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: getComputedStyle(document.documentElement).getPropertyValue("--ink").trim(),
                            padding: 9,
                            cornerRadius: 8,
                            callbacks: {
                                label: (context) => ` ${context.label}: ${context.parsed} project${context.parsed === 1 ? "" : "s"}`,
                            },
                        },
                    },
                },
                plugins: [centerLabel],
            });
        }
    }

    async function load() {
        const results = await Promise.allSettled([
            S.api("/api/dashboard/statistics"),
            S.api("/api/dashboard/charts"),
        ]);

        if (results[0].status === "fulfilled") {
            state.stats = results[0].value.data;
            renderCards(state.stats);
            renderRecentProjects(state.stats.recent_projects || []);
            renderRecentClients(state.stats.recent_clients || []);
            renderProgress(state.stats.progress_overview || []);
        } else {
            S.toast(results[0].reason?.message || "Dashboard statistics could not be loaded.", "danger");
            const body = document.getElementById("recentProjectsBody");
            if (body) body.innerHTML = S.errorTableRow(4);
        }

        if (results[1].status === "fulfilled") {
            state.charts = results[1].value.data;
            renderCharts();
        } else {
            S.toast(results[1].reason?.message || "Dashboard charts could not be loaded.", "danger");
        }
    }

    document.addEventListener("sajilo:theme", () => {
        if (state.charts) renderCharts();
    });

    document.addEventListener("click", (event) => {
        if (event.target.closest("[data-retry-load]")) load();
    });

    load();
})();
