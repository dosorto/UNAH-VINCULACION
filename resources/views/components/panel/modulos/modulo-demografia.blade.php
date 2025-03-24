@extends('components.layouts.app')
@section('titulo')
    <div>
        <h1 class="text-2xl font-bold dark:text-white text-gray-800">Demografía</h1>
    </div>
    <x-panel.navbar-horizontal.navbar-horizontal>
        <x-panel.navbar-horizontal.item titulo="País" ruta="listarPaises" permiso="demografia-admin-pais" />
        <x-panel.navbar-horizontal.item titulo="Departamento" ruta="listarDepartamentos" permiso="demografia-admin-departamento" />
        <x-panel.navbar-horizontal.item titulo="Municipios" ruta="ListarMunicipios" permiso="demografia-admin-municipio" />
    </x-panel.navbar-horizontal.navbar-horizontal>
@endsection

