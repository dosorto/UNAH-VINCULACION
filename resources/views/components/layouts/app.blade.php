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
</head>

<body class="antialiased bg-gray-100">
    <nav class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <a href="{{ url('/') }}"
                            class="text-xl font-bold text-gray-800">{{ config('app.name') }}</a>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex">
                        <a href="{{route('crear-pais')}}"
                            class="text-gray-600 hover:bg-gray-200 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                            crearPais
                        </a>
                        <a href="{{route('lista-paises')}}"
                            class="text-gray-600 hover:bg-gray-200 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                            ListaPaises
                        </a>
                        <a href="{{route('crearDepartamento')}}"
                            class="text-gray-600 hover:bg-gray-200 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                           CrearDepartamento
                        </a>
                        <a href="{{route('ListarDepartamentos')}}"
                            class="text-gray-600 hover:bg-gray-200 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                            ListarDepartamento
                        </a>
                        <a href="{{route('crearMunicipio')}}"
                            class="text-gray-600 hover:bg-gray-200 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                           crearMunicipio
                        </a>
                        <a href="{{route('ListarMunicipios')}}"
                            class="text-gray-600 hover:bg-gray-200 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                           ListarMunicipios
                        </a>
                        <a href="{{route('crearAldea')}}"
                            class="text-gray-600 hover:bg-gray-200 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                           CrearAldea
                        </a>
                        <a href="{{route('ListarAldeas')}}"
                            class="text-gray-600 hover:bg-gray-200 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                           ListarAldea
                        </a>
                        <a href="{{route('crearCiudad')}}"
                            class="text-gray-600 hover:bg-gray-200 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                           CrearCiudad
                        </a>
                        <a href="{{route('ListarCiudades')}}"
                            class="text-gray-600 hover:bg-gray-200 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                           ListarCiudad
                        </a>
                    </div>
                </div>
                <div class="hidden sm:ml-6 sm:flex sm:items-center">
                    <a href="#"
                        class="text-gray-600 hover:bg-gray-200 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                        Login
                    </a>
                    <a href="#"
                        class="text-gray-600 hover:bg-gray-200 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                        Register
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="p-6">
        {{ $slot }}
    </main>
    @livewire('notifications')
    @filamentScripts
    @vite('resources/js/app.js')
</body>

</html>
