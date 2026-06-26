<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="application-name" content="{{ config('app.name') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script>
        (() => {
            const storedTheme = localStorage.getItem('theme');
            const legacyTheme = localStorage.getItem('color-theme');
            const theme = storedTheme || legacyTheme;

            if (!storedTheme && legacyTheme) {
                localStorage.setItem('theme', legacyTheme);
                localStorage.removeItem('color-theme');
            }

            if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>

    <title>{{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('images/Image/logo_nexo.png') }}" type="image/png">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,400,0,0" />

    <style>
        [x-cloak] { display: none !important; }
    </style>
    @vite('resources/css/app.css')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    @stack('styles')
    <style>
        #fondoimagen::before {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: url('{{ asset('images/Sol.svg') }}');
            background-size: cover;
            background-position: center;
            opacity: 0.3;
            z-index: -1;
        }
    </style>
</head>

<body class="flex flex-col min-h-screen bg-white dark:bg-gray-950 sm:bg-gray-100">

    @if(env('TEST_MODE_BANNER', false) === true || env('TEST_MODE_BANNER') === 'true')
    <div class="w-full bg-gradient-to-r from-orange-500 to-red-600 text-white text-center py-2 px-4 shadow-sm z-10">
        <div class="flex items-center justify-center space-x-3 max-w-7xl mx-auto">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
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

    {{-- ── Alpine wrapper (cubre topbar + sidebar + main) ── --}}
    <div
        class="flex-1 flex flex-col min-w-0"
        x-data="{
            desktopSidebarOpen: localStorage.getItem('nexoSidebarOpen') !== 'false',
            mobileSidebarOpen: false,
        }"
        x-init="$watch('desktopSidebarOpen', value => localStorage.setItem('nexoSidebarOpen', value ? 'true' : 'false'))"
        @keydown.escape.window="mobileSidebarOpen = false">

        {{-- ══════════════════════════════════════════════════════════════════
             TOPBAR  –  [Logo] [Toggle] ────── [Tema] [Usuario]
        ══════════════════════════════════════════════════════════════════ --}}
        <header class="sticky top-0 z-30 h-14 shrink-0 flex items-center gap-3 px-4
                        bg-white border-b border-gray-200
                        dark:bg-gray-950 dark:border-gray-700">

            {{-- Logo --}}
            <a href="{{ route('inicio') }}" class="flex items-center shrink-0">
                <x-logo size="sm" displayText="true" displayIsotipo="true" />
            </a>

            {{-- ── Botón sidebar – ESCRITORIO ── --}}
            <button
                type="button"
                @click="desktopSidebarOpen = !desktopSidebarOpen"
                :aria-label="desktopSidebarOpen ? 'Ocultar menú lateral' : 'Mostrar menú lateral'"
                :title="desktopSidebarOpen ? 'Ocultar menú lateral' : 'Mostrar menú lateral'"
                class="hidden sm:inline-flex h-9 w-9 shrink-0 items-center justify-center
                       rounded-xl border border-slate-200 bg-white text-slate-500 shadow-sm
                       transition-all duration-200
                       hover:-translate-y-0.5 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-600 hover:shadow-md
                       active:translate-y-0 active:shadow-sm
                       dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400
                       dark:hover:border-blue-500 dark:hover:bg-slate-700 dark:hover:text-blue-400
                       focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-1">

                {{-- Icono cuando el sidebar está ABIERTO: panel con flecha ← --}}
                <svg x-show="desktopSidebarOpen" class="h-5 w-5" fill="none" stroke="currentColor"
                     stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <rect x="3" y="3" width="18" height="18" rx="2" stroke-width="1.5"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 3v18"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.5 10.5l-2 1.5 2 1.5"/>
                </svg>

                {{-- Icono cuando el sidebar está CERRADO: panel con flecha → --}}
                <svg x-show="!desktopSidebarOpen" class="h-5 w-5" fill="none" stroke="currentColor"
                     stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <rect x="3" y="3" width="18" height="18" rx="2" stroke-width="1.5"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 3v18"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.5 10.5l2 1.5-2 1.5"/>
                </svg>
            </button>

            {{-- ── Botón sidebar – MÓVIL (hamburger) ── --}}
            <button
                type="button"
                @click="mobileSidebarOpen = !mobileSidebarOpen"
                aria-label="Abrir menú lateral"
                class="sm:hidden h-9 w-9 shrink-0 inline-flex items-center justify-center
                       rounded-xl border border-slate-200 bg-white text-slate-500 shadow-sm
                       transition-all duration-200
                       hover:bg-blue-50 hover:text-blue-600 hover:border-blue-300
                       active:scale-95
                       dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700
                       focus:outline-none">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            {{-- ── Lado derecho: Tema + Usuario ── --}}
            <div class="ml-auto flex items-center gap-2">

                {{-- Botón modo claro / oscuro --}}
                <button
                    id="theme-toggle"
                    title="Cambiar tema"
                    class="h-9 w-9 shrink-0 inline-flex items-center justify-center
                           rounded-xl border border-slate-200 bg-white text-slate-500 shadow-sm
                           transition-all duration-200
                           hover:-translate-y-0.5 hover:bg-amber-50 hover:text-amber-500 hover:border-amber-300 hover:shadow-md
                           active:translate-y-0
                           dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400
                           dark:hover:bg-slate-700 dark:hover:text-amber-400 dark:hover:border-amber-500
                           focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-1">
                    {{-- Luna (modo claro activo → click para oscuro) --}}
                    <svg id="theme-toggle-dark-icon" class="h-5 w-5 hidden" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
                    </svg>
                    {{-- Sol (modo oscuro activo → click para claro) --}}
                    <svg id="theme-toggle-light-icon" class="h-5 w-5 hidden" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd"/>
                    </svg>
                </button>

                {{-- Usuario + roles en un solo dropdown --}}
                @php
                    $navRoles  = Auth::user()->roles;
                    $rolActual = Auth::user()->active_role_id;
                @endphp
                <div class="relative inline-block text-left">

                    <button
                        class="toggle-button flex items-center gap-2 rounded-xl border border-slate-200 bg-white
                               px-2 py-1.5 text-sm shadow-sm
                               transition-all duration-200
                               hover:bg-slate-50 hover:border-slate-300 hover:shadow-md
                               active:scale-95
                               dark:border-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700
                               focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-1">
                        {{-- Avatar --}}
                        <span class="h-7 w-7 shrink-0 rounded-full bg-blue-700 text-xs font-semibold text-white
                                     flex items-center justify-center select-none">
                            {{ Auth::user()->getInitials() }}
                        </span>
                        {{-- Nombre + correo (ocultos en pantallas muy pequeñas) --}}
                        <span class="hidden sm:flex flex-col min-w-0 text-left leading-tight">
                            <span class="truncate max-w-[120px] text-sm font-medium text-gray-800 dark:text-gray-200">
                                {{ Auth::user()->name }}
                            </span>
                            <span class="truncate max-w-[120px] text-xs text-gray-500 dark:text-gray-400">
                                {{ Auth::user()->email }}
                            </span>
                        </span>
                        {{-- Chevron --}}
                        <svg class="chevron-icon h-4 w-4 shrink-0 text-gray-400 dark:text-gray-500 transition-transform duration-200"
                             xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                  d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                  clip-rule="evenodd"/>
                        </svg>
                    </button>

                    {{-- Dropdown --}}
                    <div class="hidden popup absolute right-0 top-full mt-2 w-60 rounded-xl bg-white
                                border border-gray-200 shadow-xl
                                focus:outline-none
                                divide-y divide-gray-100
                                dark:bg-gray-900 dark:border-gray-700 dark:divide-gray-700"
                         style="z-index: 50;">

                        {{-- Encabezado con info del usuario --}}
                        <div class="px-4 py-3">
                            <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wider font-semibold mb-0.5">Cuenta</p>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-200 truncate">{{ Auth::user()->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ Auth::user()->email }}</p>
                        </div>

                        {{-- Roles --}}
                        @if($navRoles->count() > 0)
                            <div class="py-1">
                                <p class="px-4 py-1.5 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                                    Cambiar rol
                                </p>
                                @foreach($navRoles as $rol)
                                    <a href="{{ route('setrole', $rol->id) }}"
                                       class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100
                                              dark:text-gray-200 dark:hover:bg-white/5 transition-colors
                                              @if($rolActual && $rol->id == $rolActual)
                                                  bg-blue-50 dark:bg-blue-900/20 font-semibold text-blue-700 dark:text-blue-300
                                              @endif">
                                        @if($rolActual && $rol->id == $rolActual)
                                            <svg class="h-4 w-4 shrink-0 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                        @else
                                            <svg class="h-4 w-4 shrink-0 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z"/>
                                                <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z"/>
                                            </svg>
                                        @endif
                                        <span class="truncate">{{ $rol->name }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        {{-- Perfil y logout --}}
                        <div class="py-1">
                            <a href="{{ route('mi_perfil') }}"
                               class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100
                                      dark:text-gray-200 dark:hover:bg-white/5 transition-colors">
                                <svg class="h-4 w-4 shrink-0 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd"/>
                                </svg>
                                Ver perfil
                            </a>
                            <a href="{{ route('logout') }}"
                               class="flex items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50
                                      dark:text-red-400 dark:hover:bg-red-900/20 transition-colors">
                                <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd"/>
                                </svg>
                                Cerrar sesión
                            </a>
                        </div>
                    </div>
                </div>
                {{-- /Usuario --}}

            </div>
            {{-- /Lado derecho --}}

        </header>
        {{-- /TOPBAR --}}

        {{-- ══════════════════════════════════════════════════════════════════
             CUERPO  –  Sidebar (izquierda) + Main (derecha)
        ══════════════════════════════════════════════════════════════════ --}}
        <div class="flex flex-1 min-w-0 flex-col sm:flex-row">

            {{-- Sidebar (solo navegación) --}}
            <x-panel.navbar.navbar />

            {{-- Contenido principal --}}
            <main class="w-full sm:flex-1 sm:min-w-0 flex flex-col p-2 sm:py-4 sm:pl-2 sm:pr-4 overflow-x-auto">
                <div class="bg-white p-6 border border-gray-300 rounded-lg dark:bg-white/5 dark:border-gray-700 h-full">
                    @yield('titulo')
                    <div class="mt-4">
                        @if (empty($hideHorizontalNav))
                            @include('components.panel.navbar-horizontal.navbar')
                        @endif
                        @yield('main')
                    </div>
                </div>
            </main>

        </div>
        {{-- /CUERPO --}}

    </div>
    {{-- /Alpine wrapper --}}

    @livewire('components.notifications')
    @vite('resources/js/app.js')
</body>

</html>
