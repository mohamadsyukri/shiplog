{{-- Light or dark from the system setting, before the first paint (resources/css/theme.css). Inline on
     purpose: a Vite module runs after the page has painted, so a dark-mode visitor would see it flash white. --}}
<script>
    (() => {
        const dark = window.matchMedia('(prefers-color-scheme: dark)');
        const apply = () => document.documentElement.setAttribute('data-bs-theme', dark.matches ? 'dark' : 'light');
        apply();
        dark.addEventListener('change', apply);
    })();
</script>
