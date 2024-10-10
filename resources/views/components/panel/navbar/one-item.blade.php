@props(['titulo' => 'titulo', 'icono' => 'icono', 'route' => 'ruta', 'notificaciones' => 0])

<li class="mb-1 group">
    <a href="{{ route($route) }}"
        class="flex font-semibold items-center py-2 px-4 text-gray-900 hover:bg-gray-950 hover:text-gray-100 rounded-md group-[.active]:bg-gray-800 group-[.active]:text-white group-[.selected]:bg-gray-950 group-[.selected]:text-gray-100">
        <i class='bx {{$icono}} mr-3 text-lg'></i>
        <span class="text-sm">{{$titulo}}</span>
        @if ($notificaciones > 0)
            <span
                class=" md:block px-2 py-0.5 ml-auto text-xs font-medium tracking-wide text-red-600 bg-red-200 rounded-full">
                {{ $notificaciones }}
            </span>
        @endif
    </a>
</li>
