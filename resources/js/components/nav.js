/**
 * The command bar's menu button (from lg) collapses the menu pane to its icons and remembers it;
 * layouts/_theme restores it before the first paint. Below lg the other button opens the offcanvas.
 */
export function initNavCollapse() {
    const toggle = document.querySelector('[data-nav-toggle]');
    if (!toggle) {
        return;
    }

    const root = document.documentElement;
    const showState = () => toggle.setAttribute('aria-expanded', String(!root.hasAttribute('data-nav-collapsed')));

    toggle.addEventListener('click', () => {
        const collapsed = root.toggleAttribute('data-nav-collapsed');
        try {
            collapsed ? localStorage.setItem('nav', 'collapsed') : localStorage.removeItem('nav');
        } catch {
            // Storage is blocked: the menu stays as it is until the page reloads.
        }
        showState();
    });

    showState();
}
