/* ==========================================================================
   Sajilo CRM — Project detail page
   ========================================================================== */

(function () {
    "use strict";

    const S = window.Sajilo;
    const page = window.__PROJECT_PAGE__;
    if (!page || !document.getElementById("projectTabs")) return;

    const editButton = document.getElementById("projectShowEdit");
    const deleteButton = document.getElementById("projectShowDelete");

    editButton?.addEventListener("click", () => {
        if (!window.SajiloProjectForm) return;
        window.SajiloProjectForm.open(page.project);
    });

    deleteButton?.addEventListener("click", () => {
        S.confirmAction({
            title: "Delete project",
            message: `Permanently delete “${page.name}”? This removes the project and its history.`,
            confirmLabel: "Delete project",
            onConfirm: async () => {
                const response = await S.api(`/api/projects/${page.id}`, { method: "DELETE" });
                S.toast(response.message || "Project deleted successfully.", "success");
                window.setTimeout(() => {
                    window.location.href = page.indexUrl || "/projects";
                }, 500);
            },
        });
    });

    document.addEventListener("sajilo:project-saved", () => {
        window.setTimeout(() => window.location.reload(), 450);
    });

    // Keep the active tab in the URL hash so deep links and refreshes work.
    const tabMap = {
        "#overview": "overviewTab",
        "#client": "clientTab",
        "#details": "detailsTab",
    };

    function activateFromHash() {
        const target = tabMap[window.location.hash];
        if (!target || !window.bootstrap) return;
        const trigger = document.getElementById(target);
        if (trigger) window.bootstrap.Tab.getOrCreateInstance(trigger).show();
    }

    document.querySelectorAll('[data-bs-toggle="tab"]').forEach((trigger) => {
        trigger.addEventListener("shown.bs.tab", (event) => {
            const hash = Object.keys(tabMap).find((key) => tabMap[key] === event.target.id) || "";
            if (hash && window.location.hash !== hash) {
                window.history.replaceState({}, "", `${window.location.pathname}${window.location.search}${hash}`);
            }
        });
    });

    window.addEventListener("hashchange", activateFromHash);
    activateFromHash();
})();
