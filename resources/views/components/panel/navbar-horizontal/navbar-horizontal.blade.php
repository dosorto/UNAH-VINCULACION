@props(['titulo' => ''])

<div class="mt-0 sm:mt-2">
    @if($titulo)
        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200">
            {{ $titulo }}
        </h2>
    @endif

    <!-- Contenedor con scroll horizontal solo si no cabe -->
    <div class="overflow-x-auto">
        <ul class="flex gap-2 px-2 py-2 min-w-max mb-2">
            {{ $slot }}
        </ul>
    </div>
</div>
