<div>
    <div class="w-full  mx-auto rounded-lg border-2 border-gray-300 dark:border-gray-700 shadow-sm p-6 bg-white dark:bg-gray-800">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4">
            Estado del proyecto:
        </h2>
        <p class="text-gray-700 dark:text-gray-300 mb-2">
            <strong class="text-gray-900 dark:text-gray-200">Último estado:</strong> 
            <span class="text-gray-800 dark:text-gray-100">  {{ $this->record->estado->tipoestado->nombre }} </span>
        </p>
        <p class="text-gray-700 dark:text-gray-300 mb-2">
            <strong class="text-gray-900 dark:text-gray-200">Último estado realizado por:</strong>
            <span class="text-gray-800 dark:text-gray-100">{{ $this->record->estado->empleado->nombre_completo }}</span>
        </p>
        <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg shadow-inner">
            <p class="text-gray-700 dark:text-gray-200">
                <strong class="text-gray-900 dark:text-gray-200">Comentario:</strong>
                {{ $this->record->estado->comentario }}
            </p>
        </div>
    </div>
    
    
    <br>

    <form wire:submit="save">
        {{ $this->form }}

    </form>

    <x-filament-actions::modals />
</div>
