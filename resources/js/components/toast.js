/**
 * Fluent toasts in #toast-container: window.toast('Saved', 'success' | 'danger' | 'warning' | 'info').
 * Session flashes are rendered by layouts/_toasts with the same markup and shown on page load.
 */
const ICONS = {
    success: 'bi-check-circle-fill text-success',
    danger: 'bi-x-circle-fill text-danger',
    warning: 'bi-exclamation-triangle-fill text-warning-emphasis',
    info: 'bi-info-circle-fill text-primary',
};

export function toast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) {
        return;
    }

    const element = document.createElement('div');
    element.className = 'toast app-toast';
    element.setAttribute('role', 'status');
    element.setAttribute('aria-live', 'polite');
    element.innerHTML = `<div class="d-flex align-items-start gap-2"><i class="bi ${ICONS[type] ?? ICONS.info} app-toast-icon" aria-hidden="true"></i>`
        + '<div class="toast-body flex-grow-1 p-0"></div>'
        + '<button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button></div>';
    element.querySelector('.toast-body').textContent = message;
    container.appendChild(element);

    show(element);
}

export function showFlashToasts() {
    document.querySelectorAll('#toast-container .toast').forEach(show);
}

function show(element) {
    element.addEventListener('hidden.bs.toast', () => element.remove());
    window.bootstrap.Toast.getOrCreateInstance(element, { delay: 4000 }).show();
}
