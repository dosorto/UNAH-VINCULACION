<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <script>
        // Evitar parpadeo al detectar y aplicar modo oscuro
        if (
            localStorage.getItem('theme') === 'dark' ||
            (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)
        ) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <meta name="application-name" content="{{ config('app.name') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('images/Image/logo_nexo.png') }}" type="image/png">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.46.0/dist/apexcharts.min.js"></script>

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

<body class="sm:bg-gray-100 flex flex-col bg-white dark:bg-gray-950" >
    @if(env('TEST_MODE_BANNER', false) === true || env('TEST_MODE_BANNER') === 'true')
    <!-- Test Mode Banner - Parte superior completa -->
    <div class="w-full bg-gradient-to-r from-orange-500 to-red-600 text-white text-center py-2 px-4 shadow-sm z-10">
        <div class="flex items-center justify-center space-x-3 max-w-7xl mx-auto">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <span class="font-bold text-sm">🧪 MODO PRUEBA - RAMA TEST</span>
            </div>
            <div class="hidden md:flex items-center space-x-2 text-xs">
                <span>|</span>
                <span class="bg-white bg-opacity-20 px-2 py-1 rounded-full">
                    ENV: <strong>{{ strtoupper(env('APP_ENV', 'TEST')) }}</strong>
                </span>
                <span>|</span>
                <span class="opacity-90">Esta es una versión de prueba</span>
            </div>
        </div>
    </div>
    @endif

    <!-- Contenedor principal -->
    <div class="flex-1 flex">
        <div class="sm:flex w-full flex-col sm:flex-row" >
            <!-- Remover banner móvil anterior -->
            <x-panel.navbar.navbar />
            <!-- Barra superior en dispositivos móviles -->
        <div
            class="flex justify-between items-center p-4 bg-white  sm:hidden sticky top-0 dark:bg-gray-950 border-b border-gray-200 dark:border-gray-700 rounded-b-lg"
            style="z-index: 39;">
            <div class="flex items-center ">
                <div class="w-36 h-8 rounded-lg flex">
                    <x-logo size="sm"  displayText="true" displayIsotipo="true"/>
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
                    @include('components.panel.navbar-horizontal.navbar')
                    @yield('main')
                </div>
            </div>
        </main>
    </div>
   
    @livewire('notifications')
    @filamentScripts
    @vite('resources/js/app.js')
</body>

</html>
