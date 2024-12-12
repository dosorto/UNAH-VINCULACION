@extends('components.layouts.app')
@section('titulo')
    <div>
        <h1 class="text-2xl font-bold dark:text-white text-gray-800">Proyectos</h1>
    </div>
    <x-panel.navbar-horizontal.navbar-horizontal>
        <x-panel.navbar-horizontal.item titulo="Proyectos" ruta="listarProyectosVinculacion" permiso="proyectos-admin-proyectos" />
        <x-panel.navbar-horizontal.item titulo="Solicitud Proyecto" ruta="listarProyectosSolicitado" permiso="proyectos-admin-solicitados" />
        <x-panel.navbar-horizontal.item titulo="Informe Intermedio" ruta="listarInformesSolicitado" permiso="proyectos-admin-solicitados" />
        <x-panel.navbar-horizontal.item titulo="Revisión Final" ruta="listarProyectoRevisionFinal" permiso="proyectos-admin-solicitados" />
    </x-panel.navbar-horizontal.navbar-horizontal>
@endsection

