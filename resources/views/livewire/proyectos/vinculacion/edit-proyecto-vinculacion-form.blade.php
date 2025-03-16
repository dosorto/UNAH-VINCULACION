<div>
    <div class="w-full rounded-lg border-2 border-red-500 shadow-lg p-4 dark:bg-gray-800">
        <h2 class="text-xl font-semibold mb-2
            dark:text-gray-200
        ">Descripcion/Subsanacion</h2>
        <p class="text-gray-700 dark:text-gray-100">
            {{ $this->record->estado->comentario }}
        </p>
      </div>
      <br>

    <form wire:submit="save">
        {{ $this->form }}

    </form>

    <x-filament-actions::modals />
</div>
