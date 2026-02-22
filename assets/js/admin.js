const ADMIN_SESSION_KEY = "lms_admin_session";

export function getAdminSession() {
  try {
    return JSON.parse(localStorage.getItem(ADMIN_SESSION_KEY) || "null");
  } catch (_) {
    return null;
  }
}

export function saveAdminSession(session) {
  localStorage.setItem(ADMIN_SESSION_KEY, JSON.stringify(session));
}

export function clearAdminSession() {
  localStorage.removeItem(ADMIN_SESSION_KEY);
}

export function requireAdmin() {
  const session = getAdminSession();
  if (!session) {
    window.location.href = "./admin-login.html";
    return false;
  }
  return true;
}

export async function adminLogin(credentials) {
  const res = await fetch("./api/admin_login.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "include",
    body: JSON.stringify(credentials),
  });
  const json = await res.json();
  if (!res.ok || !json.success) {
    // Prefer detailed server message when available so issues are visible.
    throw new Error(json.error || json.details || "Login failed");
  }
  saveAdminSession(json.data);
  return json.data;
}

export async function fetchAdminOverview() {
  const res = await fetch("./api/admin_overview.php", { credentials: "include" });
  const json = await res.json();
  if (!res.ok || !json.success) {
    throw new Error(json.error || "Failed to load admin data");
  }
  return json.data;
}

export async function fetchAdminUsers() {
  const res = await fetch("./api/admin_users.php", { credentials: "include" });
  const json = await res.json();
  if (!res.ok || !json.success) {
    throw new Error(json.error || "Failed to load users");
  }
  return json.data;
}

export async function deleteAdminUser(userId) {
  const res = await fetch("./api/admin_users.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "include",
    body: JSON.stringify({ action: "delete", user_id: userId }),
  });
  const json = await res.json();
  if (!res.ok || !json.success) {
    throw new Error(json.error || "Failed to delete user");
  }
  return true;
}

export function bindAdminLogout(buttonSelector = "[data-admin-logout]") {
  const btn = document.querySelector(buttonSelector);
  if (!btn) return;
  btn.addEventListener("click", () => {
    clearAdminSession();
    // also clear PHP session server-side by hitting logout endpoint if needed
    fetch("./logout.php", { credentials: "include" }).finally(() => {
      window.location.href = "./admin-login.html";
    });
  });
}
