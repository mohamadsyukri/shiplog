{{-- Before the first paint, so nothing flashes: the theme (Light, Dark or Auto, which follows the system) saved by
     resources/js/components/theme.js under Bootstrap's own 'theme' key, and the menu collapsed by nav.js. Inline on
     purpose: a Vite module runs after the page has painted, so a dark-mode visitor would see it flash white. --}}
<script>
    (() => {
        const saved = (key) => {
            try {
                return localStorage.getItem(key);
            } catch (e) {
                return null;
            }
        };
        const dark = window.matchMedia('(prefers-color-scheme: dark)');

        window.applyTheme = (choice = saved('theme')) => {
            const theme = choice === 'light' || choice === 'dark' ? choice : (dark.matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-bs-theme', theme);
        };
        window.applyTheme();
        dark.addEventListener('change', () => window.applyTheme());

        document.documentElement.toggleAttribute('data-nav-collapsed', saved('nav') === 'collapsed');
    })();
</script>
