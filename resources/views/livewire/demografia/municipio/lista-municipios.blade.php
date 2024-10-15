<div>
    <div class="mb-4 mt-4 flex justify-between items-center">
        <div>
            <p class="text-zinc-950 dark:text-white font-bold mb-1">
                Municipio
            </p>
            <p class="text-zinc-500 dark:text-gray-400 font-medium text-sm mt-0">
                Municipios pertenecientes a los departamentos del sistema
            </p>
        </div>
        <div>
            <x-filament::button color="info" icon="heroicon-o-document-arrow-up"     href="{{route('crearMunicipio')}}" tag="a" wire:navigate>
                Nuevo
            </x-filament::button>
        </div>

    </div>

    {{ $this->table }}
</div>
