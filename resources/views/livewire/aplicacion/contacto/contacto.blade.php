<div>
    <form wire:submit="submit">
        {{ $this->form }}

        <x-filament::button type="submit" class="mt-2 px-6">
          Enviar
        </x-filament::button>
    </form>

    <x-filament-actions::modals />
</div>
