@extends('components.layouts.app')
@section('titulo')
    <div>
        <h1 class="text-2xl font-bold dark:text-white text-gray-800">Unidad Academica</h1>
    </div>
   

    <x-panel.navbar-horizontal.navbar-horizontal>
        <x-panel.navbar-horizontal.item titulo="Facultad Centro" ruta="facultad-centro" permiso="unidad-academica-admin-facultad" />
        <x-panel.navbar-horizontal.item titulo="Departamento Academico" ruta="departamento-academico" permiso="unidad-academica-admin-departamento" />
        <x-panel.navbar-horizontal.item titulo="Carrera" ruta="carrera" permiso="unidad-academica-admin-carrera" />
        <x-panel.navbar-horizontal.item titulo="Campus" ruta="campus" permiso="unidad-academica-admin-campus" />
       
    </x-panel.navbar-horizontal.navbar-horizontal>
@endsection

