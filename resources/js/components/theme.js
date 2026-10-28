/**
 * The Light / Dark / Auto switch in the command bar (buttons with data-theme-choice). The choice is kept under
 * Bootstrap's own localStorage key, 'theme'; window.applyTheme (layouts/_theme) puts it on <html>.
 */
export function initThemeSwitch() {
    const buttons = document.querySelectorAll('[data-theme-choice]');
    if (buttons.length === 0) {
        return;
    }

    let choice = savedChoice();
    const showChoice = () => buttons.forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.themeChoice === choice)));

    buttons.forEach((button) => button.addEventListener('click', () => {
        choice = button.dataset.themeChoice;
        try {
            choice === 'auto' ? localStorage.removeItem('theme') : localStorage.setItem('theme', choice);
        } catch {
            // Storage is blocked (a private window, say): the choice holds until the page reloads.
        }
        window.applyTheme(choice);
        showChoice();
    }));

    showChoice();
}

function savedChoice() {
    try {
        return localStorage.getItem('theme') ?? 'auto';
    } catch {
        return 'auto';
    }
}
