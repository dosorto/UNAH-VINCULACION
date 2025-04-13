<!-- resources/views/home.blade.php -->
@extends('layouts.aplicacion.app')

@section('title', 'Inicio')

@section('content')
@livewire('notifications')
@filamentScripts
@vite('resources/js/app.js')


<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">

    <meta name="application-name" content="{{ config('app.name') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('images/Logo_Nexo.png') }}" type="image/png">

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
</head>

<body>
  
    <body class="min-h-screen  dark:bg-gray-900 flex flex-col bg-gray-200">
        <!-- Header -->

        <div class="bg-amber-500  h-2 p-2 w-full dark:bg-gray-900">
        </div>
        <header class="shadow-lg p-2 bg-white dark:bg-gray-800">

            <div class="container mx-auto flex flex-col md:flex-row items-center justify-between gap-5 ">
                <div class="flex items-center flex-wrap justify-center">
                    <img src="{{ asset('images/Image/Imagen2.png') }}" alt="UNAH Logo"
                        class="max-w-full h-auto  md:w-[65%]">
                </div>

                <button id="darkModeToggle" class="p-2 rounded-full bg-gray-200 dark:bg-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z" />
                    </svg>
                </button>
            </div>
        </header>

        <!-- Main Content -->
        <main class="  p-4  h-[calc(50vh)] p-2">
            <!-- Login Form -->
            <div class="w-full  md:w-3/4  mx-auto flex flex-col md:flex-row h-full ">
                <div class="bg-white dark:bg-gray-800  rounded-lg shadow-lg max-w-md w-full mx-auto h-full shadow-lg">
                    <div class="px-4 py-2 bg-gray-100 dark:bg-gray-700">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white ">Iniciar sesión</h3>
                    </div>
                    <div class="p-2 md:p-4">
                        {{ $slot }}
                    </div>
                </div>

                <!-- Banner Image -->
                <div id="gallery" class="pl-3 w-full h-full hidden md:block md:relative" data-carousel="slide">
                    <!-- Carousel wrapper -->
                    <div class="relative  overflow-hidden rounded-lg h-full">
                        <!-- Itera sobre las imágenes de la variable $slides -->
                        @forelse ($slides as $index => $slide)
                            <div class="hidden duration-700 ease-in-out" data-carousel-item
                                @if ($index == 0) data-carousel-item="active" @endif>
                                <img src="{{ asset('storage/' . $slide->image_url) }}"
                                    class="absolute block w-full h-full -translate-x-1/2 -translate-y-1/2 top-1/2 left-1/2"
                                    alt="Slide {{ $index + 1 }}">
                            </div>
                        @empty
                            <div class="hidden duration-700 ease-in-out" data-carousel-item data-carousel-item="active">
                                <img src="{{ asset('images/Slide/1.jpeg') }}"
                                    class="absolute block w-full h-full -translate-x-1/2 -translate-y-1/2 top-1/2 left-1/2"
                                    alt="Slide 1">
                            </div>
                            <div class="hidden duration-700 ease-in-out" data-carousel-item data-carousel-item="active">
                                <img src="{{ asset('images/Slide/2.jpg') }}"
                                    class="absolute block w-full h-full -translate-x-1/2 -translate-y-1/2 top-1/2 left-1/2"
                                    alt="Slide 1">
                            </div>
                            <div class="hidden duration-700 ease-in-out" data-carousel-item data-carousel-item="active">
                                <img src="{{ asset('images/Slide/3.jpg') }}"
                                    class="absolute block w-full h-full -translate-x-1/2 -translate-y-1/2 top-1/2 left-1/2"
                                    alt="Slide 1">
                            </div>
                            <div class="hidden duration-700 ease-in-out" data-carousel-item data-carousel-item="active">
                                <img src="{{ asset('images/Slide/4.jpg') }}"
                                    class="absolute block w-full h-full -translate-x-1/2 -translate-y-1/2 top-1/2 left-1/2"
                                    alt="Slide 1">
                            </div>
                        @endforelse
                    </div>
                    <!-- Slider controls -->
                    <button type="button"
                        class="absolute top-0 start-0 z-30 flex items-center justify-center h-full px-4 cursor-pointer group focus:outline-none"
                        data-carousel-prev>
                        <span
                            class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white/30 dark:bg-gray-800/30 group-hover:bg-white/50 dark:group-hover:bg-gray-800/60 group-focus:ring-4 group-focus:ring-white dark:group-focus:ring-gray-800/70 group-focus:outline-none">
                            <svg class="w-4 h-4 text-white dark:text-gray-800 rtl:rotate-180" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="M5 1 1 5l4 4" />
                            </svg>
                            <span class="sr-only">Previous</span>
                        </span>
                    </button>
                    <button type="button"
                        class="absolute top-0 end-0 z-30 flex items-center justify-center h-full px-4 cursor-pointer group focus:outline-none"
                        data-carousel-next>
                        <span
                            class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white/30 dark:bg-gray-800/30 group-hover:bg-white/50 dark:group-hover:bg-gray-800/60 group-focus:ring-4 group-focus:ring-white dark:group-focus:ring-gray-800/70 group-focus:outline-none">
                            <svg class="w-4 h-4 text-white dark:text-gray-800 rtl:rotate-180" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="m1 9 4-4-4-4" />
                            </svg>
                            <span class="sr-only">Next</span>
                        </span>
                    </button>
                </div>


            </div>
        </main>

        <!-- Help Section -->
        <div class="bg-blue-900  text-white p-2 mb-2 dark:bg-gray-800">
            <div class="container mx-auto">
                <h4 class="font-bold mb-2">En caso de no poder ingresar:</h4>
                <ol class="list-decimal list-inside ">
                    <li>Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod
                        tincidunt ut laoreet dolore magna aliquam erat volutpat.</li>
                    <li>Ut wisi enim ad minim veniam, quis nostrud exerci tation ullamcorper.</li>
                    <li>Suscipit lobortis nisl ut aliquip ex ea commodo consequat. Duis autem vel eum iriure dolor in
                        hendrerit in vulputate velit esse molestie consequat.</li>
                </ol>
            </div>
        </div>

        <!-- Footer -->
        <footer class="bg-amber-400  p-6 w-full h-full dark:bg-gray-900">
            <div class="container mx-auto grid md:grid-cols-2 gap-8">
                <div class="space-y-2">
                    <p class="flex items-center gap-2">
                        <span class="w-6">📍</span>
                        Ciudad Universitaria, Edificio Alma Máter, 5to piso.
                    </p>
                    <p class="flex items-center gap-2">
                        <span class="w-6">📞</span>
                        (504) 2216-7070 Ext. 110576
                    </p>
                    <p class="flex items-center gap-2">
                        <span class="w-6">🌐</span>
                        https://vinculacion.unah.edu.hn
                    </p>
                    <p class="flex items-center gap-2">
                        <span class="w-6">✉️</span>
                        vinculacion.sociedad@unah.edu.hn
                    </p>
                </div>
                <div class="text-navy-900 dark:text-gray-900">
                    <p class="text-justify dark:text-gray-200">
                        La vinculación académica con la sociedad, junto a la investigación científica y la
                        docencia son funciones esenciales de una universidad. Los vínculos académicos
                        que la Universidad establece con el Estado, los sectores productivos y la sociedad
                        civil posibilitan que los conocimientos científicos, técnicos y humanistas de la
                        Universidad sean útiles para orientar y resolver problemas en la nación. Difundir
                        ampliamente el conocimiento acumulado, haciéndolo llegar a diferentes sectores
                        económicos y sociales en toda la geografía nacional, es un deber universitario.
                    </p>
                </div>
            </div>
        </footer>

        <script>
            const darkModeToggle = document.getElementById('darkModeToggle');
            darkModeToggle.addEventListener('click', () => {
                document.documentElement.classList.toggle('dark');
                if (document.documentElement.classList.contains('dark')) {
                    localStorage.setItem('color-theme', 'dark');
                } else {
                    localStorage.setItem('color-theme', 'light');
                }
            });

            if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window
                    .matchMedia(
                        '(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        </script>
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
@endsection