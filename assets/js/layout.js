import { getSession, saveSession } from './auth.js';
import { api } from './api.js';

const partialCache = new Map();

async function fetchPartial(path) {
    if (!partialCache.has(path)) {
        const htmlPromise = fetch(path).then((res) => {
            if (!res.ok) throw new Error(`Failed to load ${path}`);
            return res.text();
        });
        partialCache.set(path, htmlPromise);
    }
    return partialCache.get(path);
}

async function injectPartial(path, target) {
    if (!target) return;
    target.innerHTML = await fetchPartial(path);
}

export async function loadLayout() {
    const navHost = document.querySelector('[data-partial="nav"]');
    const footerHost = document.querySelector('[data-partial="footer"]');
    const tasks = [];

    if (navHost) tasks.push(injectPartial("./partials/nav.html", navHost));
    if (footerHost) tasks.push(injectPartial("./partials/footer.html", footerHost));

    await Promise.all(tasks);

    applyNavAuthState();

    const yearEl = document.getElementById("year");
    if (yearEl) {
        yearEl.textContent = new Date().getFullYear();
    }
}

function applyNavAuthState() {
    const session = getSession();
    const guestBlock = document.querySelector('[data-auth-section="guest"]');
    const userBlock = document.querySelector('[data-auth-section="user"]');
    const avatarEl = document.querySelector('[data-user-avatar]');
    const nameEl = document.querySelector('[data-user-name]');

    const isAuthed = !!session;

    if (guestBlock) guestBlock.style.display = isAuthed ? "none" : "flex";
    if (userBlock) userBlock.style.display = isAuthed ? "flex" : "none";

    if (!isAuthed) return;

    const displayName = session.user_name || session.user_email_address || "Profile";
    if (nameEl) nameEl.textContent = displayName;

    if (avatarEl) {
        const initial = displayName.trim().charAt(0).toUpperCase() || "U";
        avatarEl.textContent = initial;

        const applyImage = (rawSrc) => {
            if (!rawSrc) return;
            let src = rawSrc;
            if (!/^https?:\/\//i.test(src)) {
                if (src.indexOf('upload/') === 0) {
                    src = './' + src;
                } else if (!src.startsWith('./') && !src.startsWith('/')) {
                    src = './upload/' + src;
                }
            }
            avatarEl.innerHTML = `<img src="${src}" alt="${displayName} avatar" style="width:100%; height:100%; object-fit:cover; border-radius:50%;">`;
        };

        // Use profile from session if available
        if (session.user_profile) {
            applyImage(session.user_profile);
        } else {
            // Fallback: fetch fresh data from backend and persist it
            (async () => {
                try {
                    const user = await api.currentUser();
                    if (user && user.user_profile) {
                        applyImage(user.user_profile);
                        saveSession({
                            ...session,
                            user_profile: user.user_profile
                        });
                    }
                } catch (err) {
                    console.error('Failed to load avatar from API', err);
                }
            })();
        }
    }
}
