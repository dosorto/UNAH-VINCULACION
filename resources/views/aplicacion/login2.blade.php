@extends('layouts.aplicacion.app')

@section('title', 'Iniciar Sesión')

@section('content')



<!-- Contact Section -->
<x-aplicacion.hero-section
title="{{ config('app.name') }} Login"
description="Para acceder a todas las funciones de {{ config('app.name') }}, inicia sesión con tu cuenta
        Institucional y completa el registro si aun no lo has hecho."
theme="blanco"
text-position="top-right"
contentPosition="beside"
:backgroundImage="true"
:fullpage="true"
>
<div class="w-full  max-w-md mx-auto bg-white dark:bg-gray-800 rounded-xl  overflow-hidden md:max-w-2xl ">
    <div class="p-8">
       
        
        <div class="space-y-6">
           
            
            <div class="relative">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-600 dark:border-gray-700"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-2 bg-white dark:bg-gray-800  text-gray-500 dark:text-gray-400">Iniciar sesión con</span>
                </div>
                
            </div>
            {{$slot}}
         
            
           
        </div>
    </div>
</div>
</x-aplicacion.hero-section>
@endsection


@section('scripts')

@endsection



