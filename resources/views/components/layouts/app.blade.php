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

<body class="sm:bg-gray-100 flex bg-white">

    <div class="sm:flex w-full flex-col sm:flex-row">
        <div id="mobile-menu"
            class="fixed inset-0 z-50 bg-white shadow-lg transition-transform transform -translate-x-full sm:translate-x-0 sm:shadow-none  sm:flex h-screen flex-col justify-between py-4 pr-2 pl-4 w-3/4 sm:w-1/4 h-[100vh] sm:sticky top-0 sm:bg-gray-100">
            <div class="flex items-center  justify-between ">
                <div class="flex items-center ">
                    <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                        <div class="w-4 h-4 border-2 border-white rounded-full"></div>
                    </div>
                    <span class="ml-2 text-xl font-semibold text-gray-800">NEXO</span>
                </div>
                <div class="relative inline-block text-left">
                    <button id="toggleButton"
                        class="toggle-button inline-flex justify-center items-center px-2 py-2 border border-gray-300  text-sm font-medium rounded-md text-gray-700 bg-gray-100 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg id="chevronIcon" xmlns="http://www.w3.org/2000/svg" class="chevron-icon h-5 w-5"
                            viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div id="popup"
                        class="hidden popup origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                        <div class="py-1">
                            <a href="#"
                                class="group flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900">
                                <div class="flex-shrink-0 h-6 w-6 mr-3">
                                    <div class="w-full h-full bg-blue-600 rounded-md flex items-center justify-center">
                                        <div class="w-3 h-3 border-2 border-white rounded-full"></div>
                                    </div>
                                </div>
                                Untitled UI
                                <svg xmlns="http://www.w3.org/2000/svg" class="ml-auto h-5 w-5 text-gray-400"
                                    viewBox="0 0 20 20" fill="currentColor">
                                    <path
                                        d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" />
                                    <path
                                        d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z" />
                                </svg>
                            </a>
                        </div>
                        <div class="py-1">
                            <a href="#"
                                class="group flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900">
                                <div class="flex-shrink-0 h-6 w-6 mr-3">
                                    <div
                                        class="w-full h-full bg-gradient-to-br from-pink-500 via-red-500 to-yellow-500 rounded-md">
                                    </div>
                                </div>
                                Sisyphus Ventures
                                <svg xmlns="http://www.w3.org/2000/svg" class="ml-auto h-5 w-5 text-gray-400"
                                    viewBox="0 0 20 20" fill="currentColor">
                                    <path
                                        d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" />
                                    <path
                                        d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z" />
                                </svg>
                            </a>
                        </div>
                        <div class="py-1">
                            <a href="#"
                                class="group flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                                        clip-rule="evenodd" />
                                </svg>
                                New dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="h-[70vh]">
                <div class="p-2 mt-4">
                    <h2 class="text-sm font-medium text-gray-500">Untitled UI</h2>
                </div>
                <nav class="px-4">
                    <a href="#"
                        class="flex items-center py-2 px-4 text-gray-700 hover:bg-gray-200 rounded-md transition-colors duration-150 ease-in-out">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-500" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span>Overview</span>
                    </a>
                    <a href="#"
                        class="flex items-center py-2 px-4 text-gray-700 hover:bg-gray-200 rounded-md transition-colors duration-150 ease-in-out">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-500" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span>Dashboards</span>
                    </a>
                    <a href="#"
                        class="flex items-center py-2 px-4 text-gray-700 hover:bg-gray-200 rounded-md transition-colors duration-150 ease-in-out">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-500" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        <span>All projects</span>
                    </a>
                    <a href="#"
                        class="flex items-center py-2 px-4 text-gray-700 hover:bg-gray-200 rounded-md transition-colors duration-150 ease-in-out">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-500" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <span>Analyze</span>
                    </a>
                    <a href="#"
                        class="flex items-center py-2 px-4 text-gray-700 hover:bg-gray-200 rounded-md transition-colors duration-150 ease-in-out">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-500" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <span>Manage access</span>
                    </a>
                </nav>

            </div>
            <div>
                <div class=" ">

                    <a href="#"
                        class="mb-4 flex items-center py-2 px-2 text-gray-700 hover:bg-gray-200 rounded-md transition-colors duration-150 ease-in-out">
                        <div class="flex items-center space-x-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span class="text-gray-700">Configuracion</span>
                        </div>
                    </a>
                    <div class="bg-white rounded-lg p-2 border border-gray-300 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <img class="h-10 w-10 rounded-full" src="https://i.pravatar.cc/40?img=1"
                                    alt="Caitlyn Kling">
                                <div>
                                    <p class="text-sm font-medium text-gray-700">Francisco Paz</p>
                                    <p class="text-xs text-gray-500">fjpazf@unah.hn</p>
                                </div>
                            </div>
                            <div class="relative inline-block text-left">
                                <div id="popup"
                                    class="hidden popup origin-bottom-right absolute bottom-full right-0 mb-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                    <div class="py-1">
                                        <a href="#"
                                            class="group flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900">
                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500"
                                                viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd"
                                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            Ver perfil
                                        </a>
                                    </div>
                                    <div class="py-1">
                                        <a href="#"
                                            class="group flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900">
                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500"
                                                viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd"
                                                    d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            Configuración de cuenta
                                        </a>
                                    </div>
                                    <div class="py-1">
                                        <a href="#"
                                            class="group flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900">
                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500"
                                                viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd"
                                                    d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            Cambiar contraseña
                                        </a>
                                    </div>
                                    <div class="py-1">
                                        <a href="#"
                                            class="group flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900">
                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500"
                                                viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd"
                                                    d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            Cerrar sesión
                                        </a>
                                    </div>
                                </div>
                                <button id="toggleButton"
                                    class="toggle-button inline-flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <svg id="chevronIcon" xmlns="http://www.w3.org/2000/svg"
                                        class=" chevron-icon h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Barra superior en dispositivos móviles -->
        <div class="flex justify-between items-center p-4 bg-white  sm:hidden sticky top-0">
            <div class="flex items-center ">
                <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                    <div class="w-4 h-4 border-2 border-white rounded-full"></div>
                </div>
                <span class="ml-2 text-xl font-semibold text-gray-800">NEXO</span>
            </div>
            <button id="menu-button" class="focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7">
                    </path>
                </svg>
            </button>
        </div>

        <!-- Etiqueta principal-->

        <main class="w-full flex flex-col p-2 sm:py-4 sm:pl-2 sm:pr-4">
            <!-- Contenido del main -->
            <div class="bg-white p-6 border border-gray-300 rounded-lg min-h-screen">
                <div>
                    <h1 class="text-2xl font-bold">Demografia</h1>
                </div>
                <div class="mt-0 sm:mt-2 overflow-x-auto">
                    <div class="flex">
                        <ul class="flex  gap-2 mt-2 lg:mb-0 lg:mt-0 lg:flex-row lg:items-center ">
                            <li class="flex items-center p-1 text-sm gap-x-2 text-slate-600">
                                <a href="#"
                                    class="flex items-center font-semibold p-2 border border-transparent rounded hover:bg-gray-200 hover:border-gray-300 focus:bg-gray-200 focus:border-gray-300"
                                    aria-current="true">
                                    Pais
                                </a>
                            </li>
                            <li class="flex items-center p-1 text-sm gap-x-2 text-slate-600">
                                <a href="#"
                                    class="flex items-center font-semibold p-2 border border-transparent rounded hover:bg-gray-200 hover:border-gray-300">
                                    Departamento
                                </a>
                            </li>
                            <li class="flex items-center p-1 text-sm gap-x-2 text-slate-600">
                                <a href="#"
                                    class="flex items-center font-semibold p-2 border border-transparent rounded hover:bg-gray-200 hover:border-gray-300">
                                    Aldea
                                </a>
                            </li>
                            <li class="flex items-center p-1 text-sm gap-x-2 text-slate-600">
                                <a href="#"
                                    class="flex items-center font-semibold p-2 border border-transparent rounded hover:bg-gray-200 hover:border-gray-300">
                                    Municipio
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="sm:mt-2 mt-0">
                    <div>
                        <p class="text-zinc-950 dark:text-white font-bold mb-1">
                            Listado de paises
                        </p>
                        <p class="text-zinc-500 dark:text-zinc-400 font-medium text-sm mt-0">
                            A continuacion se muestra el listado de paises
                        </p>
                    </div>
                    
                </div>
                <div class="mt-6">
                    {{$slot}}

                </div>
            </div>
        </main>
    </div>
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
