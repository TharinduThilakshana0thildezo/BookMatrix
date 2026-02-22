import { api } from "./api.js";
import { showToast } from "./ui.js";

const SESSION_KEY = "lms_session";

export function getSession() {
    const raw = localStorage.getItem(SESSION_KEY);
    if (!raw) return null;
    try {
        return JSON.parse(raw);
    } catch (_) {
        // Corrupt session data; clear it so it doesn't break pages
        localStorage.removeItem(SESSION_KEY);
        return null;
    }
}

export function saveSession(session) {
    localStorage.setItem(SESSION_KEY, JSON.stringify(session));
}

export function clearSession() {
    localStorage.removeItem(SESSION_KEY);
}

export function requireAuth() {
    const session = getSession();
    if (!session) {
        window.location.href = "./login.html";
    }
}

export function bindLoginForm() {
    const form = document.querySelector("#login-form");
    if (!form) return;

    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        const formData = new FormData(form);
        try {
            await api.login(Object.fromEntries(formData));
            // After successful login, fetch full user profile so we can
            // display their real name across the SPA.
            let userMeta = null;
            try {
                userMeta = await api.currentUser();
            } catch (_) {
                // Fallback: at least store email if profile fetch fails.
            }

            const email = formData.get("user_email_address");
            const userName = userMeta && userMeta.user_name ? userMeta.user_name : email;
            const userProfile = userMeta && userMeta.user_profile ? userMeta.user_profile : null;
            const userUniqueId = userMeta && userMeta.user_unique_id ? userMeta.user_unique_id : null;
            const userContact = userMeta && userMeta.user_contact_no ? userMeta.user_contact_no : null;
            const userAddress = userMeta && userMeta.user_address ? userMeta.user_address : null;
            saveSession({
                user_email_address: email,
                user_name: userName,
                user_profile: userProfile,
                user_unique_id: userUniqueId,
                user_contact_no: userContact,
                user_address: userAddress,
                token: "session"
            });
            showToast("Welcome back", "success");
            setTimeout(() => window.location.href = "./profile.html", 400);
        } catch (err) {
            showToast(err.message || "Login failed", "error");
        }
    });
}

export function bindRegisterForm() {
    const form = document.querySelector("#register-form");
    if (!form) return;

    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        const formData = new FormData(form);
        const payload = {
            user_email_address: formData.get("user_email_address"),
            user_password: formData.get("user_password"),
            user_name: formData.get("user_name"),
            user_address: formData.get("user_address"),
            user_contact_no: formData.get("user_contact_no"),
            user_profile: formData.get("user_profile")
        };
        try {
            const result = await api.register(payload);
            const user = result.data || {};
            const regUserName = user.user_name || payload.user_name;
            const regUserProfile = user.user_profile || null;
            const regUserUniqueId = user.user_unique_id || null;
            const regUserContact = user.user_contact_no || payload.user_contact_no || null;
            const regUserAddress = user.user_address || payload.user_address || null;
            saveSession({
                user_email_address: user.user_email_address || payload.user_email_address,
                user_name: regUserName,
                user_profile: regUserProfile,
                user_unique_id: regUserUniqueId,
                user_contact_no: regUserContact,
                user_address: regUserAddress,
                token: "session"
            });

            showToast("Account created. Redirecting to profile...", "success");
            setTimeout(() => window.location.href = "./profile.html", 600);
        } catch (err) {
            showToast(err.message || "Registration failed", "error");
        }
    });
}

export function bindProfileForm() {
    const form = document.querySelector("#profile-form");
    if (!form) return;

    // Immediately hydrate from stored session so something shows even
    // if the API call fails (for example when the PHP session expired).
    const session = getSession();
    if (session) {
        if (session.user_name && form.elements["user_name"]) {
            form.elements["user_name"].value = session.user_name;
        }
        if (session.user_email_address && form.elements["user_email_address"]) {
            form.elements["user_email_address"].value = session.user_email_address;
        }
        if (session.user_contact_no && form.elements["user_contact_no"]) {
            form.elements["user_contact_no"].value = session.user_contact_no;
        }
        if (session.user_address && form.elements["user_address"]) {
            form.elements["user_address"].value = session.user_address;
        }

        const idElSession = document.getElementById("user-detail-id");
        const nameElSession = document.getElementById("user-detail-name");
        const emailElSession = document.getElementById("user-detail-email");
        const phoneElSession = document.getElementById("user-detail-phone");
        const addressElSession = document.getElementById("user-detail-address");

        if (idElSession) idElSession.textContent = session.user_unique_id || "-";
        if (nameElSession) nameElSession.textContent = session.user_name || "-";
        if (emailElSession) emailElSession.textContent = session.user_email_address || "-";
        if (phoneElSession) phoneElSession.textContent = session.user_contact_no || "-";
        if (addressElSession) addressElSession.textContent = session.user_address || "-";
    }

    // Pre-fill profile form from backend when available so that the
    // user sees their saved details immediately after login.
    (async () => {
        try {
            const user = await api.currentUser();
            if (user.user_name) form.elements["user_name"].value = user.user_name;
            if (user.user_email_address) form.elements["user_email_address"].value = user.user_email_address;
            if (user.user_contact_no) form.elements["user_contact_no"].value = user.user_contact_no;
            if (user.user_address) form.elements["user_address"].value = user.user_address;
            if (user.user_profile && form.elements["hidden_user_profile"]) {
                form.elements["hidden_user_profile"].value = user.user_profile;
            }

            const idEl = document.getElementById("user-detail-id");
            const nameEl = document.getElementById("user-detail-name");
            const emailEl = document.getElementById("user-detail-email");
            const phoneEl = document.getElementById("user-detail-phone");
            const addressEl = document.getElementById("user-detail-address");

            if (idEl) idEl.textContent = user.user_unique_id || "-";
            if (nameEl) nameEl.textContent = user.user_name || "-";
            if (emailEl) emailEl.textContent = user.user_email_address || "-";
            if (phoneEl) phoneEl.textContent = user.user_contact_no || "-";
            if (addressEl) addressEl.textContent = user.user_address || "-";
        } catch (err) {
            console.error("Failed to load user from API, falling back to session", err);

            const session = getSession();
            if (!session) return;

            if (session.user_name && form.elements["user_name"]) {
                form.elements["user_name"].value = session.user_name;
            }
            if (session.user_email_address && form.elements["user_email_address"]) {
                form.elements["user_email_address"].value = session.user_email_address;
            }

            const idEl = document.getElementById("user-detail-id");
            const nameEl = document.getElementById("user-detail-name");
            const emailEl = document.getElementById("user-detail-email");
            const phoneEl = document.getElementById("user-detail-phone");
            const addressEl = document.getElementById("user-detail-address");

            if (idEl) idEl.textContent = session.user_unique_id || "-";
            if (nameEl) nameEl.textContent = session.user_name || "-";
            if (emailEl) emailEl.textContent = session.user_email_address || "-";
            if (phoneEl) phoneEl.textContent = session.user_contact_no || "-";
            if (addressEl) addressEl.textContent = session.user_address || "-";
        }
    })();
    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        const formData = new FormData(form);
        const payload = {
            user_email_address: formData.get("user_email_address"),
            user_password: formData.get("user_password"),
            user_name: formData.get("user_name"),
            user_address: formData.get("user_address"),
            user_contact_no: formData.get("user_contact_no"),
            hidden_user_profile: formData.get("hidden_user_profile"),
            user_profile: formData.get("user_profile")
        };
        try {
            await api.updateProfile(payload);
            const session = getSession();
            if (session) {
                saveSession({
                    ...session,
                    user_email_address: payload.user_email_address,
                    user_name: payload.user_name || session.user_name,
                    user_profile: payload.hidden_user_profile || session.user_profile,
                });
            }
            showToast("Profile updated", "success");
        } catch (err) {
            showToast(err.message || "Update failed", "error");
        }
    });
}

export function bindLogout() {
    const logoutBtn = document.querySelector("[data-logout]");
    if (!logoutBtn) return;
    logoutBtn.addEventListener("click", () => {
        // Clear backend PHP session as well as frontend session storage.
        fetch("./logout.php", { credentials: "include" }).finally(() => {
            clearSession();
            window.location.href = "./login.html";
        });
    });
}
