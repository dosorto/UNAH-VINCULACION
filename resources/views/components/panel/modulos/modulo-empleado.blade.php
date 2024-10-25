@extends('components.layouts.app')
@section('titulo')
    <div>
        <h1 class="text-2xl font-bold dark:text-white text-gray-800">Empleado</h1>
    </div>
    <x-panel.navbar-horizontal.navbar-horizontal>
        <x-panel.navbar-horizontal.item titulo="Empleados" ruta="ListarEmpleados" permiso="empleados-admin-empleados" />
    </x-panel.navbar-horizontal.navbar-horizontal>
@endsection

