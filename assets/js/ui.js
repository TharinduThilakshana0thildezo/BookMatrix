const toastStackId = "toast-stack";

export function ensureToastStack() {
    let stack = document.getElementById(toastStackId);
    if (!stack) {
        stack = document.createElement("div");
        stack.id = toastStackId;
        stack.className = "toast-stack";
        document.body.appendChild(stack);
    }
    return stack;
}

export function showToast(message, variant = "info", duration = 2600) {
    const stack = ensureToastStack();
    const toast = document.createElement("div");
    toast.className = `toast toast--${variant}`;
    toast.innerHTML = `<span class="toast__message">${message}</span><button aria-label="Close">×</button>`;
    stack.appendChild(toast);

    const close = () => toast.remove();
    const closeBtn = toast.querySelector("button");
    if (closeBtn) {
        closeBtn.addEventListener("click", close);
    }
    setTimeout(close, duration);
}

export function bindModals() {
    document.querySelectorAll("[data-modal-open]").forEach((trigger) => {
        const target = document.getElementById(trigger.dataset.modalOpen);
        trigger.addEventListener("click", () => {
            if (target) {
                target.classList.add("modal--open");
            }
        });
    });

    document.querySelectorAll("[data-modal-close]").forEach((trigger) => {
        const modal = trigger.closest(".modal");
        trigger.addEventListener("click", () => {
            if (modal) {
                modal.classList.remove("modal--open");
            }
        });
    });
}

export function renderSkeleton(element, lines = 3) {
    if (!element) return;
    element.innerHTML = "";
    for (let i = 0; i < lines; i += 1) {
        const bar = document.createElement("div");
        bar.className = "skeleton";
        bar.style.height = "12px";
        bar.style.marginBottom = "10px";
        element.appendChild(bar);
    }
}

export function setPageTransition() {
    document.body.style.opacity = "0";
    requestAnimationFrame(() => {
        document.body.style.transition = "opacity 320ms ease";
        document.body.style.opacity = "1";
    });
}
