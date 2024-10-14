@props(['titulo' => 'titulo', 'icono' => 'icono', 'route' => 'route' ])

<li class="mb-4">
    <a href="{{route($route)}}"
        class="text-gray-900 text-sm flex items-center hover:text-[#f84525] before:contents-[''] before:w-1 before:h-1 before:rounded-full before:bg-gray-300 before:mr-3
            dark:text-gray-100 dark:hover:text-[#f84525] dark:before:bg-gray-700 dark:before:text-[#f84525] dark:before:hover:text-[#f84525] dark:before:hover:bg-gray-600
        ">
    {{$titulo}}
    </a>
</li>
