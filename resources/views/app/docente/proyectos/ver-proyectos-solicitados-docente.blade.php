@extends('layouts.panel.base')
@section('main')
    <div>
        <h1 class="text-2xl font-bold dark:text-white text-gray-800">Proyectos</h1>
    </div>

    <x-panel.navbar-horizontal.navbar-horizontal>
        <x-panel.navbar-horizontal.item titulo="Proyectos" ruta="SolicitudProyectosDocente" permiso="docente-admin-proyectos" />
    </x-panel.navbar-horizontal.navbar-horizontal>

    @livewire('docente.proyectos.solicitud-proyectos-docente-list', ['docente' => $docenteLogeado])
@endsection