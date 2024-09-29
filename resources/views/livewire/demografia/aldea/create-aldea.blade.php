<div>
    <form wire:submit="create">
        {{ $this->form }}

        <x-filament::button type="submit" class="mt-2">
            enviar
        </x-filament::button>
    </form>

    <x-filament-actions::modals />
</div>
