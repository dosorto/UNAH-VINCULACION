<div>
    <div>
        <p class="text-zinc-950 dark:text-white font-bold mb-1">
            Proyectos de vinculacion solicitados
        </p>
        <p class="text-zinc-500 dark:text-gray-400 font-medium text-sm mt-0">
            Listado de proyectos de vinculacion en los que se le solicita
        </p>
    </div>
    <div>
        <x-filament::button color="info" icon="heroicon-o-document-arrow-up"
            href="{{ route('SolicitudProyectosDocente') }}" tag="a" wire:navigate>
            Nuevo
        </x-filament::button>
    </div>
    {{ $this->table }}
</div>