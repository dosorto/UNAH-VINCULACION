<!-- resources/views/validar.blade.php -->
@extends('layouts.aplicacion.app')

@section('title', 'Validar Constancias')

@section('content')
<div class="max-w-xl mx-auto mt-20 text-center">
    <h2 class="text-2xl font-bold mb-6">Validar Constancia</h2>
    <form>
        <input type="text" placeholder="Ingresa tu código de constancia"
               class="w-full px-4 py-3 border rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none shadow">
        <button type="submit" class="mt-4 bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">
            Validar
        </button>
    </form>
</div>
@endsection
