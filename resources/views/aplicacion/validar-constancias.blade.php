@extends('layouts.aplicacion.app')

@section('title', 'Validar Constancia')

@section('content')


<!-- Contact Section -->
<x-aplicacion.hero-section
title="Validación de Constancias"
description="Verifica la autenticidad de las constancias emitidas por UNAH-VINCULACIÓN ingresando el código único para su validación."
theme="azul"
text-position="top-right"
contentPosition="opposide"
:backgroundImage="true"
:fullpage="true"
backgroundPosition="derecha"
>
<div class=" w-full md:w-xl  max-w-xl bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden mb-8">
    <div class="p-6 sm:p-8">
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-black text-[#235383] dark:text-white mb-4">Buscar por código</h2>
            @livewire('constancia.buscar-constancia')
        </div>
        
        
    </div>
</div>

</x-aplicacion.hero-section>

     
@endsection

@section('scripts')

@endsection