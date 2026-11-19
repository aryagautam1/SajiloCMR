/* ==========================================================================
   Sajilo CRM — shared project add/edit modal
   Requires the projects.partials.form-modal partial on the page.
   ========================================================================== */

(function () {
    "use strict";

    const S = window.Sajilo;
    const modal = document.getElementById("projectModal");
    const form = document.getElementById("projectForm");
    if (!modal || !form) return;

    const els = {
        title: document.getElementById("projectModalTitle"),
        subtitle: document.getElementById("projectModalSubtitle"),
        save: document.getElementById("projectSaveButton"),
        id: document.getElementById("projectId"),
        name: document.getElementById("projectName"),
        client: document.getElementById("projectClient"),
        assignee: document.getElementById("projectAssignee"),
        status: document.getElementById("projectStatusField"),
        priority: document.getElementById("projectPriorityField"),
        startDate: document.getElementById("projectStartDate"),
        dueDate: document.getElementById("projectDueDate"),
        budget: document.getElementById("projectBudget"),
        progress: document.getElementById("projectProgress"),
        description: document.getElementById("projectDescription"),
    };

    const state = { editingId: null };

    function setField(field, value) {
        if (!field) return;
        field.value = value === null || value === undefined ? "" : String(value);
    }

    function syncProgress() {
        if (!els.progress) return;
        const output = form.querySelector('[data-range-output="progress"]');
        if (output) output.textContent = `${Number(els.progress.value) || 0}%`;
    }

    function setProgress(value) {
        if (!els.progress) return;
        els.progress.value = String(Math.max(0, Math.min(100, Number(value) || 0)));
        syncProgress();
    }

    function open(project = null, defaults = {}) {
        S.clearForm(form);
        S.clearFormErrors(form);
        state.editingId = project?.id ? Number(project.id) : null;

        setField(els.id, project?.id || "");
        setField(els.name, project?.project_name || "");
        setField(els.client, project?.client_id ?? project?.client?.id ?? defaults.client_id ?? "");
        setField(els.assignee, project?.assigned_to?.id ?? project?.assigned_to ?? defaults.assigned_to ?? "");
        setField(els.status, project?.status || "planning");
        setField(els.priority, project?.priority || "medium");
        setField(els.startDate, project?.start_date || "");
        setField(els.dueDate, project?.expected_completion_date || "");
        setField(els.budget, project?.budget ?? "");
        setField(els.description, project?.description || "");
        setProgress(project?.progress ?? 0);

        if (els.title) els.title.textContent = project ? "Edit project" : "Add project";
        if (els.subtitle) {
            els.subtitle.textContent = project
                ? `Update the plan and delivery details for ${project.project_name}.`
                : "Plan a new engagement and assign it to your team.";
        }
        if (els.save) {
            els.save.innerHTML = project
                ? '<i class="bi bi-check-lg" aria-hidden="true"></i> Save changes'
                : '<i class="bi bi-check-lg" aria-hidden="true"></i> Save project';
        }

        S.showModal("projectModal");
    }

    async function save() {
        S.clearFormErrors(form);
        if (!form.reportValidity()) return;

        const formData = new FormData(form);
        const id = state.editingId;
        const endpoint = id ? `/api/projects/${id}` : "/api/projects";
        const method = id ? "PATCH" : "POST";

        // An empty assignee should be sent as a null value, not an empty string.
        if (formData.has("assigned_to") && formData.get("assigned_to") === "") {
            formData.set("assigned_to", "");
        }

        S.setButtonLoading(els.save, true, id ? "Saving changes…" : "Creating project…");
        try {
            const response = await S.apiForm(endpoint, method, formData);
            S.hideModal("projectModal");
            S.toast(response.message || "Project saved successfully.", "success");
            S.clearForm(form);
            state.editingId = null;
            document.dispatchEvent(new CustomEvent("sajilo:project-saved", {
                detail: { project: response.data, created: !id },
            }));
        } catch (error) {
            S.handleApiError(error, form);
        } finally {
            S.setButtonLoading(els.save, false);
        }
    }

    els.save?.addEventListener("click", save);
    form.addEventListener("submit", (event) => {
        event.preventDefault();
        save();
    });

    els.progress?.addEventListener("input", syncProgress);

    els.status?.addEventListener("change", () => {
        if (els.status.value === "completed") setProgress(100);
    });

    modal.addEventListener("hidden.bs.modal", () => {
        state.editingId = null;
    });

    document.addEventListener("click", (event) => {
        if (event.target.closest("[data-open-project-modal]")) {
            event.preventDefault();
            open();
        }
    });

    window.SajiloProjectForm = {
        open,
        isEditing: () => state.editingId !== null,
    };
})();
