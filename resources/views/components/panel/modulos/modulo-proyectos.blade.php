@extends('components.layouts.app')
@section('titulo')
    <div>
        <h1 class="text-2xl font-bold dark:text-white text-gray-800">Proyectos</h1>
    </div>
    <x-panel.navbar-horizontal.navbar-horizontal>
        <x-panel.navbar-horizontal.item titulo="Proyectos" ruta="listarProyectosVinculacion" />
        <x-panel.navbar-horizontal.item titulo="Solicitados" ruta="listarProyectosSolicitado" />
        <x-panel.navbar-horizontal.item titulo="Aprobados" />
    </x-panel.navbar-horizontal.navbar-horizontal>
@endsection
