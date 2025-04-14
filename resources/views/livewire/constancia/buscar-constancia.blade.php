<div>
    <form wire:submit="submit">
        <p class="text-lg font-semibold text-gray-800 text-white">
            Codigo
        </p>
        {{ $this->form }}

        <x-filament::button type="submit" class="mt-4">
    Buscar
</x-filament::button>
    </form>

    <x-filament-actions::modals />
</div>
