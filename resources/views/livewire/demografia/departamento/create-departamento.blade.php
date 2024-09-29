<div>
    <form wire:submit="create">
        {{ $this->form }}

        <x-filament::button type="submit" class="mt-2">
            enviar
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
