<!-- resources/views/validar.blade.php -->
@extends('layouts.aplicacion.app')

@section('title', 'Validar Constancias')

@section('content')
<div class="max-w-xl mx-auto mt-20 text-center">
    <h2 class="text-2xl font-bold mb-6">Validar Constancia</h2>
    @livewire('constancia.buscar-constancia')
</div>
@endsection
