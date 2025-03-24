<div>
    <div class="mb-4 mt-4 flex justify-between items-center">
        <div>
            <p class="text-zinc-950 dark:text-white font-bold mb-1">
                {{ $titulo ?? '' }}
            </p>
            <p class="text-zinc-500 dark:text-gray-400 font-medium text-sm mt-0">
                {{ $descripcion ?? '' }}
            </p>
        </div>
    </div>

    {{ $this->table }}
</div>
