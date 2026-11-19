/* ==========================================================================
   Sajilo CRM — Reports
   ========================================================================== */

(function () {
    "use strict";

    const S = window.Sajilo;
    const page = document.getElementById("reportsPage");
    if (!page) return;

    const state = {
        data: null,
        monthlyChart: null,
        statusChart: null,
        teamChart: null,
    };

    const number = new Intl.NumberFormat("en-GB");

    function setText(selector, value) {
        const element = document.querySelector(selector);
        if (element) element.textContent = value;
    }

    function renderKpis(data) {
        const kpis = data.kpis;

        setText('[data-report-kpi="total_projects"]', number.format(kpis.total_projects));
        setText('[data-report-hint="total_projects"]', `${number.format(kpis.active_projects)} active · ${number.format(kpis.completed_projects)} completed`);

        setText('[data-report-kpi="completion_rate"]', `${kpis.completion_rate}%`);
        setText('[data-report-hint="completion_rate"]', `${number.format(kpis.completed_projects)} of ${number.format(kpis.total_projects)} projects complete`);

        setText('[data-report-kpi="active_budget"]', S.formatCurrency(kpis.active_budget));
        setText('[data-report-hint="active_budget"]', `${number.format(kpis.active_projects)} active projects in the pipeline`);

        const overdue = document.querySelector('[data-report-kpi="overdue_projects"]');
        if (overdue) {
            overdue.textContent = number.format(kpis.overdue_projects);
            overdue.style.color = kpis.overdue_projects ? "var(--danger)" : "";
        }
        setText(
            '[data-report-hint="overdue_projects"]',
            kpis.overdue_projects ? "Past the expected completion date" : "All deadlines are on track"
        );
        const overdueHint = document.querySelector('[data-report-hint="overdue_projects"]');
        if (overdueHint) overdueHint.style.color = kpis.overdue_projects ? "var(--danger)" : "";

        const generated = document.getElementById("reportGenerated");
        if (generated) {
            generated.innerHTML = `<i class="bi bi-clock-history" aria-hidden="true"></i> Updated ${new Intl.DateTimeFormat("en-GB", {
                day: "2-digit",
                month: "short",
                hour: "2-digit",
                minute: "2-digit",
            }).format(new Date(data.generated_at))}`;
        }
    }

    function renderMonthly(data) {
        const canvas = document.getElementById("reportMonthlyChart");
        const empty = document.getElementById("reportMonthlyEmpty");
        if (!canvas || !window.Chart) return;

        const monthly = data.monthly_activity;
        const total = monthly.created.reduce((sum, value) => sum + value, 0)
            + monthly.completed.reduce((sum, value) => sum + value, 0);

        S.destroyChart(state.monthlyChart);
        state.monthlyChart = null;
        canvas.style.display = total ? "block" : "none";
        empty?.classList.toggle("show", !total);
        if (!total) return;

        const options = S.chartOptions();
        options.scales.x.stacked = false;
        options.scales.y.beginAtZero = true;
        options.plugins.tooltip.callbacks = {
            label: (context) => ` ${context.dataset.label}: ${context.parsed.y}`,
        };

        state.monthlyChart = new Chart(canvas, {
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
                        maxBarThickness: 24,
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

    function renderStatus(data) {
        const canvas = document.getElementById("reportStatusChart");
        const empty = document.getElementById("reportStatusEmpty");
        const legend = document.getElementById("reportStatusLegend");
        if (!canvas || !window.Chart) return;

        const series = data.project_status || [];
        const total = series.reduce((sum, item) => sum + Number(item.count || 0), 0);

        S.destroyChart(state.statusChart);
        state.statusChart = null;
        canvas.style.display = total ? "block" : "none";
        empty?.classList.toggle("show", !total);

        if (legend) {
            legend.innerHTML = series.map((item) => `
                <span class="legend-item">
                    <span class="legend-dot" style="background:${S.escapeAttr(item.color)}"></span>
                    ${S.escapeHtml(item.label)}
                    <span class="legend-value">${number.format(item.count)}</span>
                </span>`).join("");
        }
        if (!total) return;

        const surface = getComputedStyle(document.documentElement).getPropertyValue("--surface").trim();
        const centerLabel = {
            id: "reportCenterLabel",
            afterDraw(chart) {
                const { ctx, chartArea } = chart;
                if (!chartArea) return;
                const x = (chartArea.left + chartArea.right) / 2;
                const y = (chartArea.top + chartArea.bottom) / 2;
                ctx.save();
                ctx.textAlign = "center";
                ctx.textBaseline = "middle";
                ctx.fillStyle = getComputedStyle(document.documentElement).getPropertyValue("--ink").trim();
                ctx.font = '700 1.35rem "Inter", sans-serif';
                ctx.fillText(String(total), x, y - 7);
                ctx.fillStyle = getComputedStyle(document.documentElement).getPropertyValue("--muted").trim();
                ctx.font = '500 0.61rem "Inter", sans-serif';
                ctx.fillText("PROJECTS", x, y + 14);
                ctx.restore();
            },
        };

        state.statusChart = new Chart(canvas, {
            type: "doughnut",
            data: {
                labels: series.map((item) => item.label),
                datasets: [{
                    data: series.map((item) => item.count),
                    backgroundColor: series.map((item) => item.color),
                    borderColor: surface,
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

    function renderTeam(data) {
        const canvas = document.getElementById("reportTeamChart");
        const empty = document.getElementById("reportTeamEmpty");
        if (!canvas || !window.Chart) return;

        const team = data.team_workload || [];
        const totalAssigned = team.reduce((sum, member) => sum + Number(member.total_projects || 0), 0);

        S.destroyChart(state.teamChart);
        state.teamChart = null;
        canvas.style.display = totalAssigned ? "block" : "none";
        empty?.classList.toggle("show", !totalAssigned);
        if (!totalAssigned) return;

        const options = S.chartOptions();
        options.indexAxis = "y";
        options.interaction = { mode: "index", intersect: false };
        options.scales = {
            x: {
                stacked: true,
                beginAtZero: true,
                border: { display: false },
                grid: { color: getComputedStyle(document.documentElement).getPropertyValue("--border").trim() },
                ticks: { precision: 0, color: getComputedStyle(document.documentElement).getPropertyValue("--muted").trim() },
            },
            y: {
                stacked: true,
                border: { display: false },
                grid: { display: false },
                ticks: {
                    color: getComputedStyle(document.documentElement).getPropertyValue("--muted").trim(),
                    callback(value) {
                        const label = this.getLabelForValue(value);
                        return label.length > 18 ? `${label.slice(0, 17)}…` : label;
                    },
                },
            },
        };
        options.plugins.tooltip.callbacks = {
            label: (context) => ` ${context.dataset.label}: ${context.parsed.x}`,
        };

        state.teamChart = new Chart(canvas, {
            type: "bar",
            data: {
                labels: team.map((member) => member.name),
                datasets: [
                    {
                        label: "Active",
                        data: team.map((member) => member.active_projects),
                        backgroundColor: "#2563eb",
                        borderRadius: 4,
                        borderSkipped: false,
                        maxBarThickness: 20,
                    },
                    {
                        label: "Completed",
                        data: team.map((member) => member.completed_projects),
                        backgroundColor: "#16a34a",
                        borderRadius: 4,
                        borderSkipped: false,
                        maxBarThickness: 20,
                    },
                    {
                        label: "Other",
                        data: team.map((member) => Math.max(0, member.total_projects - member.active_projects - member.completed_projects)),
                        backgroundColor: "#cbd5e1",
                        borderRadius: 4,
                        borderSkipped: false,
                        maxBarThickness: 20,
                    },
                ],
            },
            options,
        });
    }

    function renderBudget(data) {
        const container = document.getElementById("reportBudgetBars");
        if (!container) return;

        const rows = data.budget_by_status || [];
        const max = Math.max(...rows.map((row) => Number(row.budget) || 0), 1);
        const hasBudget = rows.some((row) => Number(row.budget) > 0);

        container.innerHTML = rows.map((row) => {
            const budget = Number(row.budget) || 0;
            const width = hasBudget ? Math.round((budget / max) * 100) : 0;
            return `
                <div class="budget-bar-row">
                    <div class="budget-bar-head">
                        <span><span class="legend-dot" style="background:${S.escapeAttr(row.color)};display:inline-block;margin-right:6px;"></span>${S.escapeHtml(row.label)}</span>
                        <span>${S.formatCurrency(budget)} · ${number.format(row.count)} project${row.count === 1 ? "" : "s"}</span>
                    </div>
                    <span class="progress-track" role="progressbar" aria-valuenow="${width}" aria-valuemin="0" aria-valuemax="100" aria-label="${S.escapeAttr(row.label)} budget share">
                        <span class="progress-fill" style="width:${width}%;background:${S.escapeAttr(row.color)}"></span>
                    </span>
                </div>`;
        }).join("");

        setText('[data-report-total="total_budget"]', S.formatCurrency(data.kpis.total_budget));
        setText('[data-report-total="completed_budget"]', S.formatCurrency(data.kpis.completed_budget));
        setText('[data-report-total="average_progress"]', `${data.kpis.average_progress}%`);
    }

    function renderTopClients(data) {
        const body = document.getElementById("reportTopClientsBody");
        if (!body) return;

        const clients = data.top_clients || [];
        if (!clients.length) {
            body.innerHTML = S.emptyTableRow(5, "No client activity yet", "Add clients and projects to populate this report.");
            return;
        }

        body.innerHTML = clients.map((client) => `
            <tr>
                <td data-label="Client">
                    <div class="cell-person">
                        ${S.clientLogo(client, "sm")}
                        <div class="cell-person-text">
                            <a class="cell-person-name d-block truncate" href="/clients?open=${client.id}">${S.escapeHtml(client.company_name)}</a>
                            <span class="cell-person-sub d-block truncate">${S.escapeHtml(client.name)}</span>
                        </div>
                    </div>
                </td>
                <td data-label="Projects"><span class="cell-strong tabular">${number.format(client.projects_count)}</span></td>
                <td data-label="Active"><span class="tabular">${number.format(client.active_projects)}</span></td>
                <td data-label="Completed"><span class="tabular">${number.format(client.completed_projects)}</span></td>
                <td data-label="Portfolio value"><span class="cell-strong tabular">${S.formatCurrency(client.portfolio_budget)}</span></td>
            </tr>`).join("");
    }

    function renderRecentCompletions(data) {
        const container = document.getElementById("reportRecentCompletions");
        if (!container) return;

        const projects = data.recent_completions || [];
        if (!projects.length) {
            container.innerHTML = `
                <div class="empty-state" style="padding:28px 12px;">
                    <span class="empty-icon"><i class="bi bi-check2-circle" aria-hidden="true"></i></span>
                    <h3>Nothing completed yet</h3>
                    <p>Completed projects will appear here with their delivery date.</p>
                </div>`;
            return;
        }

        container.innerHTML = projects.map((project) => `
            <a class="client-project-item" href="/projects/${project.id}">
                <span class="stat-icon tone-success" style="width:32px;height:32px;font-size:.8rem;border-radius:8px;"><i class="bi bi-check2" aria-hidden="true"></i></span>
                <span style="min-width:0;flex:1 1 auto;">
                    <span class="project-name d-block truncate">${S.escapeHtml(project.project_name)}</span>
                    <span class="project-meta d-block truncate">${S.escapeHtml(project.client || "—")} · ${S.formatDate(project.completed_at)}</span>
                </span>
                <span class="cell-person-sub">${project.budget !== null ? S.formatCurrency(project.budget) : ""}</span>
            </a>`).join("");
    }

    function renderAll() {
        if (!state.data) return;
        renderMonthly(state.data);
        renderStatus(state.data);
        renderTeam(state.data);
    }

    async function load() {
        const button = document.getElementById("reportRefresh");
        if (button) button.disabled = true;
        try {
            const response = await S.api("/api/reports/summary");
            state.data = response.data;
            renderKpis(state.data);
            renderAll();
            renderBudget(state.data);
            renderTopClients(state.data);
            renderRecentCompletions(state.data);
        } catch (error) {
            S.toast(error.message || "Report data could not be loaded.", "danger");
            const body = document.getElementById("reportTopClientsBody");
            if (body) {
                body.innerHTML = S.errorTableRow(5, error.message).replace(
                    "data-retry-load",
                    "data-retry-report"
                );
            }
            document.querySelectorAll("[data-report-kpi]").forEach((element) => {
                if (element.textContent === "—") element.textContent = "—";
            });
            document.querySelectorAll("[data-report-hint]").forEach((element) => {
                element.textContent = "Unable to load report data";
            });
        } finally {
            if (button) button.disabled = false;
        }
    }

    document.getElementById("reportRefresh")?.addEventListener("click", load);

    document.addEventListener("click", (event) => {
        if (event.target.closest("[data-retry-report]")) load();
    });

    document.addEventListener("sajilo:theme", () => {
        if (state.data) renderAll();
    });

    load();
})();
