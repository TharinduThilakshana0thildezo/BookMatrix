const THEME_KEY = "lms_theme";
const root = document.documentElement;

function getInitialTheme() {
    const saved = localStorage.getItem(THEME_KEY);
    if (saved === "dark" || saved === "light") return saved;
    const prefersDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
    return prefersDark ? "dark" : "light";
}

function updateIcons(mode) {
    document.querySelectorAll('[data-theme-icon]').forEach((icon) => {
        icon.textContent = mode === "dark" ? "☀" : "☾";
        icon.classList.toggle("theme-toggle__icon--flip", mode === "dark");
    });
}

export function initThemeToggle() {
    const toggles = document.querySelectorAll("[data-theme-toggle]");
    const preferred = getInitialTheme();
    applyTheme(preferred);

    if (!toggles.length) return;
    toggles.forEach((toggle) => {
        toggle.addEventListener("click", () => {
            const next = root.classList.contains("dark") ? "light" : "dark";
            applyTheme(next);
        });
    });
}

export function applyTheme(mode) {
    const isDark = mode === "dark";
    root.classList.toggle("dark", isDark);
    root.dataset.theme = mode;
    localStorage.setItem(THEME_KEY, mode);
    updateIcons(mode);
}
