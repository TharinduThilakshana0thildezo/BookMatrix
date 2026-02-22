// Minimal API wrapper tuned to the existing PHP forms and new JSON facades.
// Frontend HTML lives at the project root alongside the PHP endpoints.
// Use "./" so calls like "api/login.php" resolve correctly.
export const API_BASE = "./";

async function postForm(endpoint, formData) {
    const res = await fetch(API_BASE + endpoint, {
        method: "POST",
        body: formData,
        credentials: "include"
    });
    const text = await res.text();
    return { res, text };
}

// Login now uses a dedicated JSON endpoint (api/login.php) so the
// frontend can get clear success/error codes and messages.

function parseRegisterResult(html) {
    if (html.includes("Verification Email sent")) return { success: true, message: "Verification email sent" };
    if (html.toLowerCase().includes("image")) return { success: false, message: "Profile image must be 225x225 and under 2MB" };
    if (html.toLowerCase().includes("already")) return { success: false, message: "Email already registered" };
    return { success: false, message: "Registration failed" };
}

export const api = {
    async login(payload) {
        const formData = new FormData();
        formData.append("user_email_address", payload.user_email_address);
        formData.append("user_password", payload.user_password);

        const res = await fetch(API_BASE + "api/login.php", {
            method: "POST",
            body: formData,
            credentials: "include"
        });

        let json;
        try {
            json = await res.json();
        } catch (_) {
            throw new Error("Login failed. Invalid server response.");
        }

        if (!res.ok || !json.success) {
            throw new Error(json.message || "Login failed");
        }

        return json;
    },
    async register(payload) {
        const formData = new FormData();
        formData.append("user_email_address", payload.user_email_address);
        formData.append("user_password", payload.user_password);
        formData.append("user_name", payload.user_name);
        formData.append("user_address", payload.user_address);
        formData.append("user_contact_no", payload.user_contact_no);
        if (payload.user_profile) {
            formData.append("user_profile", payload.user_profile);
        }

        const res = await fetch(API_BASE + "api/register.php", {
            method: "POST",
            body: formData,
            credentials: "include"
        });

        let json;
        try {
            json = await res.json();
        } catch (_) {
            throw new Error("Registration failed. Invalid server response.");
        }

        if (!res.ok || !json.success) {
            throw new Error(json.message || "Registration failed");
        }

        return json;
    },
    async fetchBooks() {
        // For the SPA user-facing Books page we want to show only the
        // books added by the currently logged-in user. Use the
        // books_mine facade which filters by owner_user_id.
        const res = await fetch(API_BASE + "api/books_mine.php", { credentials: "include" });
        const json = await res.json();
        return json.data || [];
    },
    async deleteBook(id) {
        const res = await fetch(API_BASE + "api/books.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id }),
            credentials: "include"
        });
        if (!res.ok) throw new Error("Delete failed");
        return res.json();
    },
    async createBook(payload) {
        const res = await fetch(API_BASE + "api/book_create.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload),
            credentials: "include"
        });
        let json;
        try {
            json = await res.json();
        } catch (_) {
            throw new Error("Failed to add book. Invalid server response.");
        }
        if (!res.ok || !json.success) {
            throw new Error(json.message || "Failed to add book");
        }
        return json.data;
    },
    async issueBook(payload) {
        const res = await fetch(API_BASE + "api/issue.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload),
            credentials: "include"
        });
        const json = await res.json();
        if (!res.ok || json.error) throw new Error(json.error || "Issue failed");
        return json;
    },
    async fetchMyBooks() {
        const res = await fetch(API_BASE + "api/my_books.php", { credentials: "include" });
        if (!res.ok) {
            throw new Error("Failed to load your books");
        }
        const json = await res.json();
        return json.data || [];
    },
    async updateProfile(payload) {
        const formData = new FormData();
        formData.append("user_email_address", payload.user_email_address);
        formData.append("user_password", payload.user_password);
        formData.append("user_name", payload.user_name);
        formData.append("user_address", payload.user_address);
        formData.append("user_contact_no", payload.user_contact_no);
        formData.append("hidden_user_profile", payload.hidden_user_profile || "");
        if (payload.user_profile) {
            formData.append("user_profile", payload.user_profile);
        }
        formData.append("save_button", "Save");
        const { text } = await postForm("profile.php", formData);
        if (text.includes("Data Change Successfully")) return { ok: true };
        throw new Error("Update failed");
    },
    async currentUser() {
        const res = await fetch(API_BASE + "api/user.php", { credentials: "include" });
        if (!res.ok) {
            throw new Error("Failed to load user");
        }
        const json = await res.json();
        if (json.error || !json.data) {
            throw new Error(json.error || "No user data");
        }
        return json.data;
    }
};
