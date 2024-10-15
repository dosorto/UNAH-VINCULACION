<div>
    <div class="mb-4 mt-4 flex justify-between items-center">
        <div>
            <p class="text-zinc-950 dark:text-white font-bold mb-1">
               Aldea
            </p>
            <p class="text-zinc-500 dark:text-gray-400 font-medium text-sm mt-0">
               Aldeas pertenecientes a los municipios del sistema
            </p>
        </div>
        <div>
            <x-filament::button color="info" icon="heroicon-o-document-arrow-up"     href="{{route('crearAldea')}}" tag="a" wire:navigate>
                Nueva
            </x-filament::button>
        </div>

    </div>
    {{ $this->table }}
</div>
