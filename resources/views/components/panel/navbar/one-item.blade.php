@props([
    'titulo' => 'titulo',
    'icono' => 'heroicon-o-home',
    'routes' => [],
    'notificaciones' => 0,
    'route' => 'home',
    'class' => '',
    'permisos' => [],
    'parametro' => ''
])

@if(auth()->user()->activeRole && auth()->user()->activeRole->hasAnyPermission($permisos))

<a href="{{ route($route, $parametro) }}"
     wire:navigate
    class="mt-1 flex items-center py-2 px-4  hover:bg-gray-200 rounded-md transition-colors duration-150 ease-in-out 
     dark:hover:bg-white/5 dark:focus-visible:bg-white/10 dark:text-gray-200  {{ $class }}
     @if (in_array(request()->route()->getName(), $routes)) text-primary-600 dark:text-primary-400 text-primary-600 text-primary-400 bg-gray-200 dark:bg-white/5 @endif
    " wire:navigate>

    @if (in_array(request()->route()->getName(), $routes))
        @svg($icono, ['class' => 'fi-sidebar-item-icon h-6 w-6  dark:text-gray-400 text-primary-600 dark:text-primary-400 text-primary-600 '])
    @else
        @svg($icono, ['class' => 'fi-sidebar-item-icon h-6 w-6 text-gray-400 dark:text-gray-400'])
    @endif
    <span class="ml-4">
        {{ $titulo }}
    </span>
</a>
@endif
