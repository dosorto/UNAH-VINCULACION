@props(['titulo' => 'titulo', 'icono' => 'icono' ])

<div>
    <li class="mb-1 group">
        <a href=""
            class="flex font-semibold items-center py-2 px-4 text-gray-900 hover:bg-gray-950 hover:text-gray-100 rounded-md group-[.active]:bg-gray-800 group-[.active]:text-white group-[.selected]:bg-gray-950 group-[.selected]:text-gray-100 sidebar-dropdown-toggle">
            <i class='{{$icono}}mr-3 text-lg'></i>
            <span class="text-sm">{{$titulo}}</span>
            <i class="ri-arrow-right-s-line ml-auto group-[.selected]:rotate-90"></i>
        </a>
        <ul class="pl-7 mt-2 hidden group-[.selected]:block">
            {{ $slot }}
        </ul>
    </li>
</div>
