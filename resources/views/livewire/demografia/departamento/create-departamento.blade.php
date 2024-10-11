<div>
    <form wire:submit="create">
        {{ $this->form }}

        <x-filament::button type="submit" color="info" icon="heroicon-c-arrow-up-on-square" class="mt-4">
            Guardar
        </x-filament::button>
        
        <x-filament::button color="danger" icon="heroicon-o-x-circle" onclick="window.location.href='{{ url()->previous() }}'">
                Cancelar
        </x-filament::button>
        <div wire:loading>
            <ul>
                <li></li>
                <li></li>
                <li></li>
              </ul>
        </div>
    </form>

    <x-filament-actions::modals />
</div>
