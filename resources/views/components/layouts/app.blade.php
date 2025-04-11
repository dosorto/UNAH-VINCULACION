@extends('layouts.panel.base')
@section('main')
@php
    $menu = $menu = config('navbar');
    $rutaActual = Route::currentRouteName();

    $moduloSeleccionado = collect($menu[0]['items'])->first(function ($item) use ($rutaActual) {
        // Buscar si el módulo o alguno de sus hijos contiene la ruta actual
        if (isset($item['routes']) && in_array($rutaActual, $item['routes'])) {
            return true;
        }

        if (!empty($item['children'])) {
            foreach ($item['children'] as $child) {
                if (isset($child['route']) && $child['route'] === $rutaActual) {
                    return true;
                }
            }
        }

        return false;
    });
@endphp

<x-panel.navbar-horizontal.navbar-horizontal>
    @if ($moduloSeleccionado)
        {{-- Si tiene hijos, solo mostrar hijos --}}
        @if (!empty($moduloSeleccionado['children']))
            @foreach ($moduloSeleccionado['children'] as $child)
                <x-panel.navbar-horizontal.item 
                    titulo="{{ $child['texto'] }}" 
                    ruta="{{ $child['route'] }}" 
                    permiso="{{ $child['permiso'] }}"
                />
            @endforeach
        @else
            {{-- Si no tiene hijos, mostrar el módulo como item único --}}
            @if (!empty($moduloSeleccionado['parametro']))
                <x-panel.navbar-horizontal.item 
                    titulo="{{ $moduloSeleccionado['titulo'] }}" 
                    ruta="{{ $moduloSeleccionado['route'] }}" 
                    parametro="{{ $moduloSeleccionado['parametro'] }}"
                    permiso="{{ $moduloSeleccionado['permisos'][0] }}"
                />
            @else
                <x-panel.navbar-horizontal.item 
                    titulo="{{ $moduloSeleccionado['titulo'] }}" 
                    ruta="{{ $moduloSeleccionado['route'] }}" 
                    permiso="{{ $moduloSeleccionado['permisos'][0] }}"
                />
            @endif
        @endif
    @endif
</x-panel.navbar-horizontal.navbar-horizontal>

    {{ $slot }}
@endsection
