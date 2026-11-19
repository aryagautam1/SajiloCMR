/* ==========================================================================
   Sajilo CRM — Profile & settings
   ========================================================================== */

(function () {
    "use strict";

    const S = window.Sajilo;
    if (!document.getElementById("settingsPage")) return;

    const profileForm = document.getElementById("profileForm");
    const passwordForm = document.getElementById("passwordForm");
    const profileSave = document.getElementById("profileSave");
    const passwordSave = document.getElementById("passwordSave");

    function setText(id, value) {
        const element = document.getElementById(id);
        if (element) element.textContent = value || "Not provided";
    }

    function syncProfileCard(user) {
        if (!user) return;
        setText("settingsProfileName", user.name);
        setText("settingsProfileEmail", user.email);
        setText("settingsProfilePhone", user.phone);
        setText("settingsProfileJob", user.job_title);
        setText("settingsProfileRole", user.role === "admin" ? "Administrator" : "Staff");
        const avatar = document.getElementById("settingsAvatar");
        if (avatar) avatar.textContent = user.initials || S.initials(user.name);
        const email = document.querySelector(".user-menu .dropdown-header .text-ink");
        if (email) email.textContent = user.email;
        document.querySelectorAll(".sidebar-user .avatar, .user-trigger .avatar").forEach((element) => {
            element.textContent = user.initials || S.initials(user.name);
        });
    }

    profileForm?.addEventListener("submit", async (event) => {
        event.preventDefault();
        S.clearFormErrors(profileForm);
        if (!profileForm.reportValidity()) return;

        const payload = {
            name: profileForm.elements.namedItem("name").value.trim(),
            email: profileForm.elements.namedItem("email").value.trim(),
            phone: profileForm.elements.namedItem("phone").value.trim(),
            job_title: profileForm.elements.namedItem("job_title").value.trim(),
        };

        S.setButtonLoading(profileSave, true, "Saving…");
        try {
            const response = await S.api("/api/settings/profile", {
                method: "PUT",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload),
            });
            S.toast(response.message || "Profile updated successfully.", "success");
            S.updateUserChrome(response.data);
            syncProfileCard(response.data);
        } catch (error) {
            S.handleApiError(error, profileForm);
        } finally {
            S.setButtonLoading(profileSave, false);
        }
    });

    passwordForm?.addEventListener("submit", async (event) => {
        event.preventDefault();
        S.clearFormErrors(passwordForm);
        if (!passwordForm.reportValidity()) return;

        const newPassword = passwordForm.elements.namedItem("password").value;
        const confirmation = passwordForm.elements.namedItem("password_confirmation").value;
        if (newPassword !== confirmation) {
            S.showFormErrors(passwordForm, {
                password: ["The password confirmation does not match."],
                password_confirmation: ["The password confirmation does not match."],
            });
            return;
        }

        const payload = {
            current_password: passwordForm.elements.namedItem("current_password").value,
            password: newPassword,
            password_confirmation: confirmation,
        };

        S.setButtonLoading(passwordSave, true, "Updating…");
        try {
            const response = await S.api("/api/settings/password", {
                method: "PUT",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload),
            });
            S.toast(response.message || "Password changed successfully.", "success");
            passwordForm.reset();
            S.clearFormErrors(passwordForm);
        } catch (error) {
            S.handleApiError(error, passwordForm);
        } finally {
            S.setButtonLoading(passwordSave, false);
        }
    });

    function syncAppearance(mode) {
        document.querySelectorAll("[data-theme-mode]").forEach((button) => {
            const active = button.dataset.themeMode === mode;
            button.classList.toggle("active", active);
            button.setAttribute("aria-pressed", String(active));
        });
    }

    document.querySelectorAll("[data-theme-mode]").forEach((button) => {
        button.addEventListener("click", () => {
            const mode = button.dataset.themeMode;
            S.applyTheme(mode);
            syncAppearance(mode);
        });
    });

    document.addEventListener("sajilo:theme", (event) => {
        syncAppearance(event.detail?.mode || S.getThemeMode());
    });

    syncAppearance(S.getThemeMode());
})();
