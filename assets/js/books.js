import { api } from "./api.js";
import { showToast, renderSkeleton } from "./ui.js";

const state = {
    books: [],
    filtered: [],
    query: "",
    filter: "all"
};

function filterBooks() {
    const term = state.query.toLowerCase();
    state.filtered = state.books.filter((book) => {
        const matchesQuery = !term || (book.title || "").toLowerCase().includes(term) || (book.author || "").toLowerCase().includes(term);
        const matchesFilter = state.filter === "all" || (book.status === state.filter);
        return matchesQuery && matchesFilter;
    });
    renderBooks();
}

function renderBooks() {
    const grid = document.querySelector("#books-grid");
    const tableBody = document.querySelector("#books-table-body");
    if (!grid || !tableBody) return;

    grid.innerHTML = "";
    tableBody.innerHTML = "";

    state.filtered.forEach((book) => {
        const card = document.createElement("article");
        card.className = "card";
        card.innerHTML = `
            <div class="card__label">${book.category || "General"}</div>
            <div class="card__value">${book.title || "Untitled"}</div>
            <p class="text-muted">${book.author || "Unknown Author"}</p>
            <div class="chip" style="margin-top: 12px;">${book.status || "available"}</div>
            <button class="btn btn--primary" data-borrow="${book.id || ""}" data-isbn="${book.isbn || ""}" style="margin-top: 12px;">Borrow</button>
        `;
        grid.appendChild(card);

        const row = document.createElement("tr");
        row.innerHTML = `
            <td>${book.id || "-"}</td>
            <td>${book.title || ""}</td>
            <td>${book.author || ""}</td>
            <td>${book.category || ""}</td>
            <td><span class="badge badge--${book.status === "issued" ? "warning" : "success"}">${book.status || "available"}</span></td>
            <td class="table__actions">
                <button class="btn btn--primary" data-borrow="${book.id || ""}" data-isbn="${book.isbn || ""}">Borrow</button>
                <button class="btn btn--ghost" data-edit="${book.id}">Edit</button>
                <button class="btn btn--danger" data-delete="${book.id}">Delete</button>
            </td>
        `;
        tableBody.appendChild(row);
    });
}

async function loadBooks() {
    const grid = document.querySelector("#books-grid");
    renderSkeleton(grid, 6);
    try {
        const response = await api.fetchBooks();
        state.books = Array.isArray(response) ? response : (response.data || []);
        state.filtered = state.books;
        renderBooks();
    } catch (err) {
        showToast(err.message || "Failed to load books", "error");
    }
}

function bindControls() {
    const searchInput = document.querySelector("#book-search");
    const filterSelect = document.querySelector("#book-filter");

        if (searchInput) {
            searchInput.addEventListener("input", (e) => {
                state.query = e.target.value;
                filterBooks();
            });
        }
    
        if (filterSelect) {
            filterSelect.addEventListener("change", (e) => {
                state.filter = e.target.value;
                filterBooks();
            });
        }

    document.addEventListener("click", async (e) => {
        const target = e.target;
        if (target.matches("[data-delete]")) {
            const id = target.getAttribute("data-delete");
            try {
                await api.deleteBook(id);
                showToast("Book archived", "success");
                loadBooks();
            } catch (err) {
                showToast(err.message || "Delete failed", "error");
            }
        } else if (target.matches("[data-borrow]")) {
            const isbn = target.getAttribute("data-isbn");
            let session = null;
            try {
                const raw = localStorage.getItem("lms_session");
                if (raw) {
                    session = JSON.parse(raw);
                }
            } catch (_) {
                session = null;
            }

            if (!session || !session.user_unique_id) {
                showToast("Please log in again to borrow books", "error");
                return;
            }

            if (!isbn) {
                showToast("Book is missing ISBN", "error");
                return;
            }

            try {
                await api.issueBook({ book_id: isbn, user_id: session.user_unique_id });
                showToast("Book borrowed successfully", "success");
            } catch (err) {
                showToast(err.message || "Failed to borrow book", "error");
            }
        }
    });

    const addForm = document.querySelector("#add-book-form");
    if (addForm) {
        addForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            const formData = new FormData(addForm);
            const payload = {
                title: formData.get("title") || "",
                author: formData.get("author") || "",
                category: formData.get("category") || "",
                location: formData.get("location") || "",
                isbn: formData.get("isbn") || "",
                copies: Number(formData.get("copies") || "0")
            };
            try {
                console.log("Submitting new book", payload);
                showToast("Submitting book...", "info");
                await api.createBook(payload);
                showToast("Book added", "success");
                addForm.reset();
                const modal = document.getElementById("modal-add");
                if (modal) {
                    modal.classList.remove("modal--open");
                }
                loadBooks();
            } catch (err) {
                showToast(err.message || "Failed to add book", "error");
            }
        });
    }
}

export function initBooksPage() {
    loadBooks();
    bindControls();
}
