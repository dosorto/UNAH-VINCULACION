<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">

    <meta name="application-name" content="{{ config('app.name') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name') }}</title>

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    @filamentStyles
    @vite('resources/css/app.css')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="sm:bg-gray-100 flex bg-white dark:bg-gray-950">
    <div class="sm:flex w-full flex-col sm:flex-row">
        <x-panel.navbar.navbar />
        <!-- Barra superior en dispositivos móviles -->
        <div class="flex justify-between items-center p-4 bg-white  sm:hidden sticky top-0 bg-white dark:bg-gray-950">
            <div class="flex items-center ">
                <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                    <div class="w-4 h-4 border-2 border-white rounded-full"></div>
                </div>
                <span class="ml-2 text-xl font-semibold text-gray-800
                dark:text-gray-200   
                ">NEXO</span>
            </div>
            <button id="menu-button" class="focus:outline-none">
                <svg class="w-6 h-6
                    dark:text-gray-200
                " fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7">
                    </path>
                </svg>
            </button>
        </div>

        <!-- Etiqueta principal-->

        <main class="w-full flex flex-col p-2 sm:py-4 sm:pl-2 sm:pr-4">
            <!-- Contenido del main -->
            <div
                class="bg-white p-6 border border-gray-300 rounded-lg min-h-screen dark:bg-white/5 dark:border-gray-700">
                <div>
                    <h1 class="text-2xl font-bold dark:text-white text-gray-800
                    
                    ">Demografia</h1>
                </div>
                <div class="mt-0 sm:mt-2 overflow-x-auto">
                    <div class="flex">
                        <ul class="flex  gap-2 mt-2 lg:mb-0 lg:mt-0 lg:flex-row lg:items-center ">
                            <li class="flex items-center p-1 text-sm gap-x-2 text-slate-600">
                                <a href="#"
                                    class="flex items-center font-semibold p-2  rounded hover:bg-gray-100 hover:border-gray-300 focus:bg-gray-200 focus:border-gray-300
                                          
                                    dark:hover:bg-white/5 dark:focus-visible:bg-white/5 dark:text-gray-200
                                        dark:bg-white/5 dark:focus-visible:bg-white/10 dark:border-gray-700
                                         bg-gray-100 text-primary-600 dark:text-primary-400
                                    "
                                    aria-current="true">
                                    Pais
                                </a>
                            </li>
                            <li class="flex items-center p-1 text-sm gap-x-2 text-slate-600">
                                <a href="#"
                                    class="flex items-center font-semibold p-2  rounded hover:bg-gray-100 hover:border-gray-300 focus:bg-gray-200 focus:border-gray-300
                                          
                                    dark:hover:bg-white/5 dark:focus-visible:bg-white/5 dark:text-gray-200
                                        dark:bg-white/5 dark:focus-visible:bg-white/10 dark:border-gray-700">
                                    Departamento
                                </a>
                            </li>
                            <li class="flex items-center p-1 text-sm gap-x-2 text-slate-600">
                                <a href="#"
                                    class="flex items-center font-semibold p-2  rounded hover:bg-gray-100 hover:border-gray-300 focus:bg-gray-200 focus:border-gray-300
                                          
                                    dark:hover:bg-white/5 dark:focus-visible:bg-white/5 dark:text-gray-200
                                        dark:bg-white/5 dark:focus-visible:bg-white/10 dark:border-gray-700">
                                    Aldea
                                </a>
                            </li>
                            <li class="flex items-center p-1 text-sm gap-x-2 text-slate-600">
                                <a href="#"
                                    class="flex items-center font-semibold p-2  rounded hover:bg-gray-100 hover:border-gray-300 focus:bg-gray-200 focus:border-gray-300
                                          
                                    dark:hover:bg-white/5 dark:focus-visible:bg-white/5 dark:text-gray-200
                                        dark:bg-white/5 dark:focus-visible:bg-white/10 dark:border-gray-700">
                                    Municipio
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class=" mt-4">
                    <div>
                        <p class="text-zinc-950 dark:text-white font-bold mb-1">
                            Listado de paises
                        </p>
                        <p class="text-zinc-500 dark:text-gray-400 font-medium text-sm mt-0">
                            A continuacion se muestra el listado de paises
                        </p>
                    </div>

                </div>
                <div class="mt-6">
                    {{ $slot }}

                </div>
            </div>
        </main>
    </div>
    <script>
        const themeToggleBtn = document.getElementById('theme-toggle');
        const themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
        const themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');

        // Change the icons inside the button based on previous settings
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia(
                '(prefers-color-scheme: dark)').matches)) {
            themeToggleLightIcon.classList.remove('hidden');
            document.documentElement.classList.add('dark');
        } else {
            themeToggleDarkIcon.classList.remove('hidden');
        }

        themeToggleBtn.addEventListener('click', function() {
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
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
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
                        if (otherPopup !== popup && !otherPopup.classList.contains(
                                'hidden')) {
                            otherPopup.classList.add('hidden');
                            const otherChevronIcon = otherPopup.previousElementSibling
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
                        const button = popup.previousElementSibling; // El botón relacionado
                        if (!popup.contains(event.target) && !button.contains(event.target)) {
                            popup.classList.add('hidden');
                            const chevronIcon = button.querySelector('.chevron-icon');
                            chevronIcon.innerHTML =
                                '<path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />';
                        }
                    }
                });
            });
        });
    </script>
    <script>
        const menuButton = document.getElementById('menu-button');
        const mobileMenu = document.getElementById('mobile-menu');
        const closeButton = document.getElementById('close-button');

        menuButton.addEventListener('click', () => {
            mobileMenu.classList.toggle('-translate-x-full');
        });

        closeButton.addEventListener('click', () => {
            mobileMenu.classList.add('-translate-x-full');
        });
    </script>
    @livewire('notifications')
    @filamentScripts
    @vite('resources/js/app.js')
</body>

</html>
