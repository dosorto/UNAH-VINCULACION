<!-- resources/views/home.blade.php -->
@extends('layouts.aplicacion.app')

@section('title', 'Inicio')

@section('content')
<div class="text-center py-20">
    <h1 class="text-4xl md:text-6xl font-bold text-blue-700 mb-8">UNAH - VINCULACIÓN</h1>
    <div class="space-x-4">
        <a href="#" class="bg-green-600 hover:bg-green-700 text-white font-semibold px-6 py-3 rounded transition">
            Registrar Proyectos
        </a>
        <a href="{{ route('login') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded transition">
            Iniciar Sesión
        </a>
    </div>
</div>

@include('partials.acercade')
@include('partials.desarrolladores')

@endsection
