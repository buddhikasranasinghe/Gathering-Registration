const THEME_KEY = 'theme';

const applyTheme = (isDark) => {
    document.documentElement.classList.toggle('dark', isDark);
    document.querySelectorAll('[data-theme-toggle]').forEach((toggle) => {
        toggle.setAttribute('aria-pressed', String(isDark));
    });
};

const storedTheme = () => {
    try {
        return localStorage.getItem(THEME_KEY);
    } catch {
        return null;
    }
};

const prefersDark = () => window.matchMedia?.('(prefers-color-scheme: dark)').matches ?? false;

applyTheme(storedTheme() ? storedTheme() === 'dark' : prefersDark());

document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-theme-toggle]');

    if (!toggle) {
        return;
    }

    const isDark = ! document.documentElement.classList.contains('dark');

    applyTheme(isDark);

    try {
        localStorage.setItem(THEME_KEY, isDark ? 'dark' : 'light');
    } catch {
        // Storage can be unavailable in private browsing; the theme still applies for this page.
    }
});

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-auto-filter]').forEach((form) => {
        let timer = null;

        const submit = () => form.requestSubmit();

        form.querySelectorAll('select, input[type="checkbox"]').forEach((field) => {
            field.addEventListener('change', submit);
        });

        const search = form.querySelector('input[name="search"]');

        if (search) {
            search.addEventListener('input', () => {
                window.clearTimeout(timer);
                timer = window.setTimeout(submit, 400);
            });
        }
    });

    document.querySelectorAll('[data-tabs]').forEach((group) => {
        const tabs = Array.from(group.querySelectorAll('[data-tab]'));
        const panels = Array.from(group.querySelectorAll('[data-panel]'));

        const selectTab = (name) => {
            tabs.forEach((tab) => {
                const isActive = tab.dataset.tab === name;

                tab.setAttribute('aria-selected', String(isActive));
                tab.tabIndex = isActive ? 0 : -1;
                tab.classList.toggle('tab-button-active', isActive);
            });

            panels.forEach((panel) => {
                panel.hidden = panel.dataset.panel !== name;
            });

            const url = new URL(window.location.href);
            url.searchParams.set('tab', name);
            window.history.replaceState(null, '', url);
        };

        tabs.forEach((tab, index) => {
            tab.addEventListener('click', () => selectTab(tab.dataset.tab));

            tab.addEventListener('keydown', (event) => {
                if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
                    return;
                }

                event.preventDefault();

                const offset = event.key === 'ArrowRight' ? 1 : -1;
                const nextTab = tabs[(index + offset + tabs.length) % tabs.length];

                nextTab.focus();
                selectTab(nextTab.dataset.tab);
            });
        });
    });
});
