@extends('components.layouts.app')
@section('titulo')
    <div>
        <h1 class="text-2xl font-bold dark:text-white text-gray-800">Firmas</h1>
    </div>
    <x-panel.navbar-horizontal.navbar-horizontal>
        <x-panel.navbar-horizontal.item titulo="Solicitudes de Firma" ruta="SolicitudProyectosDocente" permiso="docente-admin-proyectos" />
        <x-panel.navbar-horizontal.item titulo="Firmas Aprobadas" ruta="AprobadoProyectosDocente" permiso="docente-admin-proyectos" />
        <x-panel.navbar-horizontal.item titulo="Firmas Rechazadas" ruta="RechazadoProyectosDocente" permiso="docente-admin-proyectos" />
    </x-panel.navbar-horizontal.navbar-horizontal>
@endsection

