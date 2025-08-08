<div>
    <div class="w-full flex justify-between items-center mb-4">
        <div>
            <p class="text-zinc-950 dark:text-white font-bold mb-1">
                Proyectos de vinculación.
            </p>
            <p class="text-zinc-500 dark:text-gray-400 font-medium text-sm mt-0">
                Listado de proyectos de vinculación en los que participa:
            </p>
        </div>
        <div class="flex gap-2">
            <x-filament::button color="info" icon="heroicon-o-document-arrow-up"
                href="{{ route('crearProyectoVinculacion') }}" tag="a" wire:navigate>
                Nuevo
            </x-filament::button>
        </div>
    </div>
    {{ $this->table }}
</div>
