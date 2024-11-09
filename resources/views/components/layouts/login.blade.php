<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">

    <meta name="application-name" content="{{ config('app.name') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('images/LOGO_NX.png') }}" type="image/png">
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
    <div class="contenedor bg-gradient-to-r from-blue-800 to-indigo-900">
        <div class=" space space-1"></div>
        <div class="space space-2"></div>
        <div class="space space-3"></div>
        <div class="w-full h-full flex flex-col items-center">
            <div class="w-full h-[10vh]">
            <header class="bg-white dark:bg-gray-900 text-gray-800 dark:text-white p-3 shadow-md">
                <div class="container mx-auto flex justify-between items-center">
                    <!-- logo -->
                    <div class="flex items-left">
                        <img src="{{ asset('images/image/Imagen2.png') }}" alt="Logo"
                        class="mx-auto" style="width: 380px; height: auto; Al" >
                    </div>
                
                    <button id="theme-toggle" class="mr-2 p-2 rounded-full 
                        bg-gray-200 dark:bg-gray-700 
                        hover:bg-gray-300 dark:hover:bg-gray-600 
                        focus:outline-none focus:ring-2 focus:ring-gray-400">
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
                    <!-- buttons -->
                </div>
                </header>
            </div>
            <div class="w-full min-h-[70vh] flex items-center justify-center">
                <div class="w-full xl:w-3/4 flex flex-col-reverse md:flex-row items-center justify-center">

                    <div class="p-4 w-full xl:w-1/2 flex items-letf justify-left">
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


                    <div id="gallery" class="relative w-full" data-carousel="slide">
                        <!-- Carousel wrapper -->
                        <div class="relative h-56 overflow-hidden rounded-lg md:h-96">
                            <!-- Item 1 -->
                            <div class="hidden duration-700 ease-in-out" data-carousel-item>
                            <img src="{{ asset('images/Slide/1.jpeg') }}" class="absolute block max-w-full h-auto -translate-x-1/2 -translate-y-1/2 top-1/2 left-1/2" alt="">
                            </div>
                            <!-- Item 2 -->
                            <div class="hidden duration-700 ease-in-out" data-carousel-item="active">
                            <img src="{{ asset('images/Slide/2.jpg') }}" class="absolute block max-w-full h-auto -translate-x-1/2 -translate-y-1/2 top-1/2 left-1/2" alt="">
                            </div>
                            <!-- Item 3 -->
                            <div class="hidden duration-700 ease-in-out" data-carousel-item>
                            <img src="{{ asset('images/Slide/3.jpg') }}" class="absolute block max-w-full h-auto -translate-x-1/2 -translate-y-1/2 top-1/2 left-1/2" alt="">
                            </div>
                            <!-- Item 4 -->
                            <div class="hidden duration-700 ease-in-out" data-carousel-item>
                            <img src="{{ asset('images/Slide/4.jpg') }}" class="absolute block max-w-full h-auto -translate-x-1/2 -translate-y-1/2 top-1/2 left-1/2" alt="">
                            </div>
                            <!-- Item 5 -->
                            <div class="hidden duration-700 ease-in-out" data-carousel-item>
                            <img src="{{ asset('images/Slide/Yosoy.jpeg') }}" class="absolute block max-w-full h-auto -translate-x-1/2 -translate-y-1/2 top-1/2 left-1/2" alt="">
                            </div>
                        </div>
                        <!-- Slider controls -->
                        <button type="button" class="absolute top-0 start-0 z-30 flex items-center justify-center h-full px-4 cursor-pointer group focus:outline-none" data-carousel-prev>
                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white/30 dark:bg-gray-800/30 group-hover:bg-white/50 dark:group-hover:bg-gray-800/60 group-focus:ring-4 group-focus:ring-white dark:group-focus:ring-gray-800/70 group-focus:outline-none">
                                <svg class="w-4 h-4 text-white dark:text-gray-800 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 1 1 5l4 4"/>
                                </svg>
                                <span class="sr-only">Previous</span>
                            </span>
                        </button>
                        <button type="button" class="absolute top-0 end-0 z-30 flex items-center justify-center h-full px-4 cursor-pointer group focus:outline-none" data-carousel-next>
                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white/30 dark:bg-gray-800/30 group-hover:bg-white/50 dark:group-hover:bg-gray-800/60 group-focus:ring-4 group-focus:ring-white dark:group-focus:ring-gray-800/70 group-focus:outline-none">
                                <svg class="w-4 h-4 text-white dark:text-gray-800 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                                </svg>
                                <span class="sr-only">Next</span>
                            </span>
                        </button>
                    </div>



                </div>
            </div>
        </div>
    </div>
    @livewire('notifications')
    @filamentScripts
    @vite('resources/js/app.js')

<footer class="bg-yellow-300 dark:bg-gray-800 text-gray-800 dark:text-yellow-300 p-4 shadow-md">
    <div class="container mx-auto">
        <div class="flex flex-col md:flex-row justify-between items-start">
            <div class="w-full md:w-1/2 mb-4 md:mb-0 text-1xl font-bold text-gray-800 dark:text-white">
                <ul className="list-none text-sm md:text-base">
                    <li className="mb-1 flex items-center">
                        <span className="font-semibold mr-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                        </svg>
                        </span>
                            Ciudad Universitaria, Edificio Alma Máter, 5to piso
                    </li>

                    <li className="mb-1">
                        <span className="font-semibold">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                        </svg>
                        </span> (504) 2216-7070 Ext. 110576
                    </li>
                    <li className="mb-1">
                        <span className="font-semibold">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
                        </svg>
                        </span>
                        <a href="https://vinculacion.unah.edu.hn" className="underline hover:text-gray-600 dark:hover:text-yellow-200">
                        https://vinculacion.unah.edu.hn
                        </a>
                    </li>
                    <li className="mb-1">
                        <span className="font-semibold">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                        </svg>

                        </span>
                        <a href="mailto:vinculacion.sociedad@unah.edu.hn" className="underline hover:text-gray-600 dark:hover:text-yellow-200">
                        vinculacion.sociedad@unah.edu.hn
                        </a>
                    </li>
                </ul>
            </div>
            <div class="w-full md:w-1/2 flex justify-end items-end">
                <p class="text-sm md:text-base font-bold text-gray-800 dark:text-white">
                La vinculación académica con la sociedad, junto a la investigación científica y la docencia son funciones esenciales de una universidad. 
                Los vínculos académicos que la Universidad establece con el Estado, los sectores productivos y la sociedad civil posibilitan que los 
                conocimientos científicos, técnicos y humanistas de la Universidad sean útiles para orientar y resolver problemas en la nación. 
                Difundir ampliamente el conocimiento acumulado, haciéndolo llegar a diferentes sectores económicos y sociales en toda la geografía 
                nacional, es un deber universitario.
                </p>
            </div> 
        </div>
    </div>
</footer>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.4.7/flowbite.min.js"></script>


</html>
