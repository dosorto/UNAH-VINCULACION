@extends('layouts.panel.base')
@section('main')
    <main class="w-full flex flex-col p-2 sm:py-4 sm:pl-2 sm:pr-4">
        <!-- Contenido del main -->
        <div class="bg-white p-6 border border-gray-300 rounded-lg min-h-screen dark:bg-white/5 dark:border-gray-700">
            @yield('titulo')
            <div class="mt-4">
                {{ $slot }}
            </div>
        </div>
    </main>
@endsection
