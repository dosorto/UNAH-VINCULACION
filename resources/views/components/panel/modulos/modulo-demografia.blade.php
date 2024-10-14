@extends('components.layouts.app')
@section('titulo')
    <div>
        <h1 class="text-2xl font-bold dark:text-white text-gray-800">Demografia</h1>
    </div>
    <x-panel.navbar-horizontal.navbar-horizontal>
        <x-panel.navbar-horizontal.item titulo="Pais" ruta="listarPaises" />
        <x-panel.navbar-horizontal.item titulo="Departamento" ruta="listarDepartamentos" />
        <x-panel.navbar-horizontal.item titulo="Municipios" ruta="ListarMunicipios" />
        <x-panel.navbar-horizontal.item titulo="Aldea" ruta="ListarAldeas" />
        <x-panel.navbar-horizontal.item titulo="Ciudades" ruta="ListarCiudades" />
    </x-panel.navbar-horizontal.navbar-horizontal>
@endsection
