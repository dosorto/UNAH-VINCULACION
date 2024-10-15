<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">

    <meta name="application-name" content="{{ config('app.name') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/styles-app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/filament/filament/app.css') }}">

    </script>
    @yield('styles')
    @stack('styles')
    <style>
        /* Modo claro */
        :root {
            --body-bg: rgb(255, 255, 255);
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('scripts')
    @stack('js')

    @filamentStyles
    @yield('scripts')
    <script src="https://cdn.tailwindcss.com"></script>
 
    <link rel="stylesheet" href="{{ asset('css/filament/filament/app.css') }}">
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    @filamentStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .space {
            --size: 2px;
            --space-layer:
                4vw 50vh 0 #fff,
                18vw 88vh 0 #fff,
                73vw 14vh 0 #fff;

            width: var(--size);
            height: var(--size);
            background: white;
            box-shadow: var(--space-layer);
            position: absolute;
            top: 0;
            left: 0;
            opacity: 0.75;
        }

        .space {
            animation: move var(--duration) linear infinite;
        }

        @keyframes move {
            from {
                transform: translateY(0);
            }

            to {
                transform: translateY(-100vh);
            }
        }
    </style>

    <link rel="stylesheet" href="{{ asset('css/styles-app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/filament/filament/app.css') }}">
</head>

<body>
    <div
        class="contenedor bg-gradient-to-r from-blue-800 to-indigo-900">
        <div class=" space space-1"></div>
        <div class="space space-2"></div>
        <div class="space space-3"></div>
        <div class="w-full h-full flex flex-col items-center">
            <div class="w-full h-[10vh]">
                <header class=" w-full flex  justify-between sm:px-8 sm:py-4 p-4">
                    <!-- logo -->
                    <h1 class="w-3/12">
                        <a href>
                            <h2 class="text-2xl font-bold text-white">
                                Vinculacion
                            </h2>
                        </a>
                    </h1>
                    <button id="theme-toggle"
                        class="mr-2 p-2 rounded-full 
                            bg-blue-800
                      dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-400">
                        <svg id="theme-toggle-dark-icon" class="w-5 h-5 text-gray-800 dark:text-gray-200 hidden"
                            fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                        </svg>
                        <svg id="theme-toggle-light-icon" class="w-5 h-5 text-gray-800 dark:text-gray-200 hidden"
                            fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"
                                fill-rule="evenodd" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                    <!-- navigation -->


                    <!-- buttons --->

                </header>
            </div>
            <div class="w-full min-h-[80vh] flex items-center justify-center">
                <div class="w-full xl:w-3/4 flex flex-col-reverse md:flex-row items-center justify-center">
                    <div class="p-4 w-full xl:w-1/2 flex items-center justify-center">
                        <div class="md:max-w-sm w-full lg:max-w-full lg:flex">
                            <div
                                class="triangulo mt-1 lg:mt-4 p-4 w-full rounded-lg bg-white border-gray-400 flex flex-col justify-between leading-normal
                                    dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 

                                ">
                                <div>
                                    <div class="text-gray-900 font-bold text-xl mb-2">
                                        UNAH
                                    </div>
                                    <!-- Aquí va el contenido de la imagen -->
                                    <div class="mb-4">
                                        <img src="{{ asset('images/UNAH-version-horizontal.png') }}" alt="Logo"
                                            class="mx-auto" style="width: 500px; height: auto;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 w-full xl:w-1/2 flex items-center justify-center">
                        <div
                            class="w-full bg-white rounded-lg dark:border md:mt-0 md:max-w-md xl:p-0 dark:bg-gray-800 dark:border-gray-700">
                            <div class="p-6 space-y-4 md:space-y-6 sm:p-8">
                                <h1
                                    class="text-xl font-bold leading-tight tracking-tight text-gray-900 md:text-2xl dark:text-white">
                                    Login
                                </h1>
                                {{ $slot }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="w-full h-[10vh] flex items-center justify-center">
                <div class="md:col-span-1">
                    <div class="px-4 sm:px-0">
                        <h3
                            class="text-lg font-medium leading-6 text-gray-900
                            flex items-center justify-center text-white
                          ">
                        </h3>
                        <!-- <div class="mb-4">
                            <img src="{{ asset('images/DVUS3.jpg') }}" alt="Logo" class="mx-auto w-32 h-auto">
                        </div> -->
                        <p class="mt-1 text-sm text-gray-600 text-white">
                            Desarrollado por UNAH Campus Choluteca 2024
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>
    @livewire('notifications')
    @filamentScripts
    @vite('resources/js/app.js')
</body>
<script>
    const COLORS = ["#fff2", "#fff4", "#fff7", "#fffc"];

    const generateSpaceLayer = (size, selector, totalStars, duration) => {
        const layer = [];
        for (let i = 0; i < totalStars; i++) {
            const color = COLORS[~~(Math.random() * COLORS.length)];
            const x = Math.floor(Math.random() * 100);
            const y = Math.floor(Math.random() * 100);
            layer.push(`${x}vw ${y}vh 0 ${color}, ${x}vw ${y + 100}vh 0 ${color}`);
        }
        const container = document.querySelector(selector);
        container.style.setProperty("--size", size);
        container.style.setProperty("--duration", duration);
        container.style.setProperty("--space-layer", layer.join(","));
    }

    generateSpaceLayer("2px", ".space-1", 250, "25s");
    generateSpaceLayer("3px", ".space-2", 100, "20s");
    generateSpaceLayer("6px", ".space-3", 25, "15s");
</script>

</html>
