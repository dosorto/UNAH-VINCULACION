import ApexCharts from 'apexcharts';

// Hacer ApexCharts disponible globalmente
window.ApexCharts = ApexCharts;

const THEME_KEY = 'theme';
const LEGACY_THEME_KEY = 'color-theme';

function getStoredTheme() {
    const storedTheme = localStorage.getItem(THEME_KEY);
    const legacyTheme = localStorage.getItem(LEGACY_THEME_KEY);

    if (!storedTheme && legacyTheme) {
        localStorage.setItem(THEME_KEY, legacyTheme);
        localStorage.removeItem(LEGACY_THEME_KEY);
    }

    return storedTheme || legacyTheme;
}

function preferredTheme() {
    const storedTheme = getStoredTheme();

    if (storedTheme) {
        return storedTheme;
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

function applyTheme(theme) {
    document.documentElement.classList.toggle('dark', theme === 'dark');
}

function syncThemeIcons(darkIcon, lightIcon, theme) {
    darkIcon.classList.toggle('hidden', theme === 'dark');
    lightIcon.classList.toggle('hidden', theme !== 'dark');
}

function initThemeToggle() {
    const themeToggleBtn = document.getElementById('theme-toggle');
    const themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
    const themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');
    const theme = preferredTheme();

    applyTheme(theme);

    if (!themeToggleBtn || !themeToggleDarkIcon || !themeToggleLightIcon) {
        return;
    }

    syncThemeIcons(themeToggleDarkIcon, themeToggleLightIcon, theme);

    if (themeToggleBtn.dataset.themeInitialized === 'true') {
        return;
    }

    themeToggleBtn.dataset.themeInitialized = 'true';
    themeToggleBtn.addEventListener('click', () => {
        const nextTheme = document.documentElement.classList.contains('dark') ? 'light' : 'dark';

        localStorage.setItem(THEME_KEY, nextTheme);
        localStorage.removeItem(LEGACY_THEME_KEY);
        applyTheme(nextTheme);
        syncThemeIcons(themeToggleDarkIcon, themeToggleLightIcon, nextTheme);
    });
}

function initDropdowns() {

    const toggleButtons = document.querySelectorAll('.toggle-button');

    toggleButtons.forEach(button => {
        if (button.dataset.dropdownInitialized === 'true') {
            return;
        }

        button.dataset.dropdownInitialized = 'true';
        button.addEventListener('click', (event) => {
            const popup = button.parentElement.querySelector('.popup');
            const chevronIcon = button.querySelector('.chevron-icon');

            if (!popup || !chevronIcon) {
                return;
            }

            const isHidden = popup.classList.contains('hidden');
            popup.classList.toggle('hidden');

            chevronIcon.innerHTML = isHidden ?
                '<path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" />' :
                '<path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />';

            // Cerrar otros popups
            const popups = document.querySelectorAll('.popup');
            popups.forEach(otherPopup => {
                if (otherPopup !== popup && !otherPopup.classList
                    .contains(
                        'hidden')) {
                    otherPopup.classList.add('hidden');
                    const otherChevronIcon = otherPopup
                        .previousElementSibling
                        ?.querySelector('.chevron-icon');

                    if (otherChevronIcon) {
                        otherChevronIcon.innerHTML =
                            '<path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />';
                    }
                }
            });

            event.stopPropagation(); // Prevenir el clic del botón de propagarse
        });
    });

    // Cerrar el popup al hacer clic fuera
    if (window.nexoDropdownDocumentListenerInitialized) {
        return;
    }

    window.nexoDropdownDocumentListenerInitialized = true;
    document.addEventListener('click', (event) => {
        const popups = document.querySelectorAll('.popup');

        popups.forEach(popup => {
            if (!popup.classList.contains('hidden')) {
                const button = popup
                    .previousElementSibling; // El botón relacionado
                if (button && !popup.contains(event.target) && !button.contains(event
                    .target)) {
                    popup.classList.add('hidden');
                    const chevronIcon = button.querySelector('.chevron-icon');

                    if (chevronIcon) {
                        chevronIcon.innerHTML =
                            '<path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />';
                    }
                }
            }
        });
    });
}

function initMobileMenu() {
    const mobileMenu = document.getElementById('mobile-menu');
    const menuButton = document.getElementById('menu-button');

    if (!mobileMenu || !menuButton) {
        return;
    }

    mobileMenu.classList.add('-translate-x-full');

    if (menuButton.dataset.mobileMenuInitialized === 'true') {
        return;
    }

    menuButton.dataset.mobileMenuInitialized = 'true';
    menuButton.addEventListener('click', () => {
        mobileMenu.classList.toggle('-translate-x-full');
    });
}

const sidebarScrollStorageKey = 'nexoSidebarScrollTop';

function sidebarScrollElement() {
    return document.querySelector('[data-sidebar-scroll]');
}

function saveSidebarScrollPosition() {
    const sidebar = sidebarScrollElement();

    if (sidebar) {
        sessionStorage.setItem(sidebarScrollStorageKey, String(sidebar.scrollTop));
    }
}

function restoreSidebarScrollPosition() {
    const sidebar = sidebarScrollElement();

    if (!sidebar) {
        return;
    }

    const storedPosition = Number(sessionStorage.getItem(sidebarScrollStorageKey) || 0);

    sidebar.scrollTop = storedPosition;
    requestAnimationFrame(() => {
        sidebar.scrollTop = storedPosition;
    });
}

function initSidebarScrollPersistence() {
    restoreSidebarScrollPosition();

    if (window.nexoSidebarScrollPersistenceInitialized) {
        return;
    }

    window.nexoSidebarScrollPersistenceInitialized = true;

    document.addEventListener('scroll', (event) => {
        if (event.target instanceof Element && event.target.matches('[data-sidebar-scroll]')) {
            saveSidebarScrollPosition();
        }
    }, true);

    document.addEventListener('click', (event) => {
        if (event.target instanceof Element && event.target.closest('[data-sidebar-scroll] a')) {
            saveSidebarScrollPosition();
        }
    }, true);

    document.addEventListener('livewire:navigating', saveSidebarScrollPosition);
}

function initNexoUi() {
    initThemeToggle();
    initDropdowns();
    initMobileMenu();
    initSidebarScrollPersistence();
}

document.addEventListener('DOMContentLoaded', initNexoUi);
document.addEventListener('livewire:navigated', initNexoUi);
