@extends('components.layouts.app')
@section('titulo')
    <div>
        <h1 class="text-2xl font-bold dark:text-white text-gray-800">Usuarios</h1>
    </div>
    <x-panel.navbar-horizontal.navbar-horizontal>
        <x-panel.navbar-horizontal.item titulo="Proyectos" ruta="Usuarios" />
        <x-panel.navbar-horizontal.item titulo="Roles" ruta="roles" />
        <x-panel.navbar-horizontal.item titulo="Permisos" ruta="listPermisos" />
    </x-panel.navbar-horizontal.navbar-horizontal>
@endsection
