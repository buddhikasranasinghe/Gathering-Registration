const clearDismissalTimer = (toast) => {
    if (toast.dismissalTimer) {
        window.clearTimeout(toast.dismissalTimer);
        toast.dismissalTimer = null;
    }
};

const dismissToast = (toast) => {
    if (toast.dataset.dismissing === 'true') {
        return;
    }

    clearDismissalTimer(toast);
    toast.dataset.dismissing = 'true';
    toast.classList.add('toast-exit');

    window.setTimeout(() => toast.remove(), 180);
};

const scheduleDismissal = (toast) => {
    clearDismissalTimer(toast);

    const duration = Number(toast.dataset.toastDuration ?? 0);

    if (! Number.isFinite(duration) || duration <= 0) {
        return;
    }

    toast.dismissalTimer = window.setTimeout(() => dismissToast(toast), duration);
};

document.querySelectorAll('[data-toast]').forEach((toast) => {
    scheduleDismissal(toast);

    toast.querySelector('[data-toast-dismiss]')?.addEventListener('click', () => dismissToast(toast));

    toast.addEventListener('mouseenter', () => clearDismissalTimer(toast));
    toast.addEventListener('mouseleave', () => scheduleDismissal(toast));
    toast.addEventListener('focusin', () => clearDismissalTimer(toast));
    toast.addEventListener('focusout', () => scheduleDismissal(toast));
});
