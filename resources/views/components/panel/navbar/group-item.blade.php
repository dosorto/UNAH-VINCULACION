@props(['titulo' => 'titulo', 'icono' => 'icono'])

<div class="mt-2 sm:group-data-[sb=closed]/sb:mt-1">

    {{-- Título de sección: visible expandido, oculto en colapsado (sm+) --}}
    <div class="p-2 mt-2 sm:group-data-[sb=closed]/sb:hidden">
        <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $titulo }}</h2>
    </div>

    {{-- Separador visual cuando el sidebar está colapsado (sm+) --}}
    <div class="hidden sm:group-data-[sb=closed]/sb:block my-1 mx-2">
        <div class="border-t border-gray-200 dark:border-gray-700"></div>
    </div>

    {{-- Nav: padding horizontal en expandido, sin padding en colapsado --}}
    <nav class="px-4 sm:group-data-[sb=closed]/sb:px-0">
        {{ $slot }}
    </nav>

</div>
