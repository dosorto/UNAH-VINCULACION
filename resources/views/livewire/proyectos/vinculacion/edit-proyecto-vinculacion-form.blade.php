<div>
    <div class="w-full rounded-lg border-2 border-red-500 shadow-lg p-4">
        <h2 class="text-xl font-semibold mb-2">Subsanacion</h2>
        <p class="text-gray-700">
            {{ $this->record->estado->comentario }}
        </p>
      </div>
      

    <form wire:submit="save">
        {{ $this->form }}

    </form>

    <x-filament-actions::modals />
</div>
