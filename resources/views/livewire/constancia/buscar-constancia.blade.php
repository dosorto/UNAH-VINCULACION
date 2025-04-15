<div>
    <form wire:submit="submit">
        
        {{ $this->form }}

        <x-filament::button type="submit" class="mt-4">
    Buscar
</x-filament::button>
    </form>

    <x-filament-actions::modals />
</div>
