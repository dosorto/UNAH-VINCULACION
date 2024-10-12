@props(['titulo' => 'titulo', 'icono' => 'icono' ])

<div class="h-[80vh]">
    <div class="p-2 mt-2">
        <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400 
        ">
        {{ $titulo }}
        </h2>
    </div>
    <nav class="px-4">
        {{$slot}}
    </nav>
</div>