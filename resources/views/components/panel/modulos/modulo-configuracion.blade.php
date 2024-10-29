@extends('components.layouts.app')
@section('titulo')

    <div>
        <h1 class="text-2xl font-bold dark:text-white text-gray-800">Configuración</h1>
    </div>
    <x-panel.navbar-horizontal.navbar-horizontal>
        <x-panel.navbar-horizontal.item titulo="Logs" ruta="listarLogs" permiso="configuracion-admin-logs" />
        <x-panel.navbar-horizontal.item titulo="Configuracion" ruta="listarDepartamentos" />
        <x-panel.navbar-horizontal.item titulo="Mi perfil" ruta="ListarMunicipios" />
    </x-panel.navbar-horizontal.navbar-horizontal>
@endsection
