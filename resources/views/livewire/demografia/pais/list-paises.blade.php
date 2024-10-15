<div>
    <div class="mb-4 mt-4 flex justify-between items-center">
        <div>
            <p class="text-zinc-950 dark:text-white font-bold mb-1">
                Listado de paises
            </p>
            <p class="text-zinc-500 dark:text-gray-400 font-medium text-sm mt-0">
                A continuacion se muestra el listado de paises
            </p>
        </div>
        <div>
            <x-filament::button color="info" icon="heroicon-o-document-arrow-up"     href="{{route('crearPais')}}" tag="a" wire:navigate>
                Nuevo
            </x-filament::button>
        </div>

    </div>
    {{ $this->table }}
</div>
