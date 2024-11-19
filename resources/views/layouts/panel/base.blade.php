<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">

    <meta name="application-name" content="{{ config('app.name') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('images/LOGO_NX.png') }}" type="image/png">

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
    @stack('styles')
    <style>
        #fondoimagen::before {
            content: "";
            /* Necesario para que el pseudo-elemento aparezca */
            position: absolute;
            /* Lo posicionamos de manera absoluta */
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            /* Lo hacemos llenar todo el contenedor */
            background-image: url('{{ asset('images/Sol.svg') }}');
            background-size: cover;
            background-position: center;
            opacity: 0.3;
            /* Opacidad del pseudo-elemento */
            z-index: -1;
            /* Aseguramos que quede detrás del contenido */
        }

      
    </style>
</head>

<body class="sm:bg-gray-100 flex bg-white dark:bg-gray-950">
    <div class="sm:flex w-full flex-col sm:flex-row">
        <x-panel.navbar.navbar />
        <!-- Barra superior en dispositivos móviles -->
        <div
            class="flex justify-between items-center p-4 bg-white  sm:hidden sticky top-0 bg-white dark:bg-gray-950 z-49">
            <div class="flex items-center ">
                <div class="w-36 h-8 rounded-lg flex items-center justify-center">
                    <img src="{{ asset('images/LOGO.png') }}" alt="Logo" class="mx-auto"
                        style="width: auto; height: auto;">
                </div>
                
            </div>
            <button id="menu-button" class="focus:outline-none">
                <svg class="w-6 h-6
                    dark:text-gray-200
                " fill="none"
                    stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7">
                    </path>
                </svg>
            </button>
        </div>

        <!-- Etiqueta principal-->
        <main class="w-full flex flex-col p-2 sm:py-4 sm:pl-2 sm:pr-4 overflow-x-auto ">
            <!-- Contenido del main -->
            <div class="bg-white p-6 border border-gray-300 rounded-lg dark:bg-white/5 dark:border-gray-700 h-full">
                @yield('titulo')
                <div class="mt-4">
                    @yield('main')
                </div>
            </div>
        </main>
    </div>
    <script>
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
    </script>
    @livewire('notifications')
    @filamentScripts
    @vite('resources/js/app.js')
</body>

</html>
