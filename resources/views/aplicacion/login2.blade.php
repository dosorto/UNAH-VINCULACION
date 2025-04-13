@extends('layouts.aplicacion.app')

@section('title', 'Iniciar Sesión')

@section('content')
<div class="min-h-screen pt-32 pb-12">
    <div class="max-w-md mx-auto bg-white dark:bg-gray-900 rounded-xl shadow-lg overflow-hidden md:max-w-2xl m-4">
        <div class="p-8">
            <div class="text-center mb-10">
                <h2 class="text-3xl font-bold text-black dark:text-white mb-2">Iniciar Sesión</h2>
                <p class="text-gray-700 dark:text-gray-400">

            Para acceder a todas las funciones de {{ config('app.name') }}, inicia sesión con tu cuenta
            Institucional y completa el registro si aun no lo has hecho.

                </p>
            </div>
            
            <div class="space-y-6">
               
                
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300 dark:border-gray-700"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white dark:bg-gray-900 text-gray-500 dark:text-gray-400">Continúa con</span>
                    </div>
                    
                </div>
                {{$slot}}
             
                
               
            </div>
        </div>
    </div>
</div>
@endsection