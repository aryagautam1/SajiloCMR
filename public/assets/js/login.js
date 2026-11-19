(function () {
    "use strict";

    const KEY = "sajilo-theme";
    const media = window.matchMedia("(prefers-color-scheme: dark)");
    const root = document.documentElement;
    const icon = document.getElementById("authThemeIcon");

    function resolved(mode) {
        if (mode === "system") return media.matches ? "dark" : "light";
        return mode === "dark" ? "dark" : "light";
    }

    function apply(mode, persist) {
        const theme = resolved(mode);
        root.setAttribute("data-theme", theme);
        root.setAttribute("data-bs-theme", theme);
        document.querySelector('meta[name="theme-color"]')?.setAttribute("content", theme === "dark" ? "#0b1120" : "#2563eb");
        if (icon) icon.className = `bi ${theme === "dark" ? "bi-sun" : "bi-moon-stars"}`;
        if (persist) localStorage.setItem(KEY, mode);
    }

    apply(localStorage.getItem(KEY) || "light", false);

    document.getElementById("authThemeToggle")?.addEventListener("click", () => {
        apply(resolved(localStorage.getItem(KEY) || "light") === "dark" ? "light" : "dark", true);
    });

    media.addEventListener("change", () => {
        if ((localStorage.getItem(KEY) || "light") === "system") apply("system", false);
    });

    document.querySelectorAll("[data-password-toggle]").forEach((button) => {
        button.addEventListener("click", () => {
            const input = document.getElementById(button.dataset.passwordToggle);
            if (!input) return;
            const show = input.type === "password";
            input.type = show ? "text" : "password";
            button.innerHTML = `<i class="bi ${show ? "bi-eye-slash" : "bi-eye"}" aria-hidden="true"></i>`;
            button.setAttribute("aria-label", show ? "Hide password" : "Show password");
        });
    });

})()
