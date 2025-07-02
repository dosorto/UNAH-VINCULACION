import './apexcharts.min.js';
import './flowbite.min.js';

document.addEventListener('livewire:navigated', () => {
    const themeToggleBtn = document.getElementById('theme-toggle');
    const themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
    const themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');

    // Change the icons inside the button based on previous settings
    if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window
        .matchMedia(
            '(prefers-color-scheme: dark)').matches)) {
        themeToggleLightIcon.classList.remove('hidden');
        document.documentElement.classList.add('dark');
    } else {
        themeToggleDarkIcon.classList.remove('hidden');
    }

    themeToggleBtn.addEventListener('click', function () {
        // Toggle icons inside button
        themeToggleDarkIcon.classList.toggle('hidden');
        themeToggleLightIcon.classList.toggle('hidden');

        // If set via local storage previously
        if (localStorage.getItem('color-theme')) {
            if (localStorage.getItem('color-theme') === 'light') {
                document.documentElement.classList.add('dark');
                localStorage.setItem('color-theme', 'dark');
            } else {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('color-theme', 'light');
            }
        } else {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('color-theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('color-theme', 'dark');
            }
        }
    });


    const toggleButtons = document.querySelectorAll('.toggle-button');
    const popups = document.querySelectorAll('.popup');

    toggleButtons.forEach(button => {
        button.addEventListener('click', (event) => {
            const popup = button.parentElement.querySelector('.popup');
            const chevronIcon = button.querySelector('.chevron-icon');

            const isHidden = popup.classList.contains('hidden');
            popup.classList.toggle('hidden');

            chevronIcon.innerHTML = isHidden ?
                '<path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" />' :
                '<path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />';

            // Cerrar otros popups
            popups.forEach(otherPopup => {
                if (otherPopup !== popup && !otherPopup.classList
                    .contains(
                        'hidden')) {
                    otherPopup.classList.add('hidden');
                    const otherChevronIcon = otherPopup
                        .previousElementSibling
                        .querySelector('.chevron-icon');
                    otherChevronIcon.innerHTML =
                        '<path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />';
                }
            });

            event.stopPropagation(); // Prevenir el clic del botón de propagarse
        });
    });

    // Cerrar el popup al hacer clic fuera
    document.addEventListener('click', (event) => {
        popups.forEach(popup => {
            if (!popup.classList.contains('hidden')) {
                const button = popup
                    .previousElementSibling; // El botón relacionado
                if (!popup.contains(event.target) && !button.contains(event
                    .target)) {
                    popup.classList.add('hidden');
                    const chevronIcon = button.querySelector('.chevron-icon');
                    chevronIcon.innerHTML =
                        '<path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />';
                }
            }
        });
    });

    const mobileMenu = document.getElementById('mobile-menu');
    mobileMenu.classList.add('-translate-x-full');

    const menuButton = document.getElementById('menu-button');

    menuButton.addEventListener('click', () => {
        mobileMenu.classList.toggle('-translate-x-full');
    });

    
});