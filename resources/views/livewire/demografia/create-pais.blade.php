<div>
    <div class="mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Crear un nuevo País</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">Crea un nuevo país con sus datos asociados.</p>
    </div>

    <form wire:submit="create">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Código de área <span class="text-red-500">*</span></label>
                <input type="number" wire:model="codigo_area"
                    class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                @error('codigo_area') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Código ISO <span class="text-red-500">*</span></label>
                <input type="text" wire:model="codigo_iso"
                    class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                @error('codigo_iso') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Código ISO numérico <span class="text-red-500">*</span></label>
                <input type="number" wire:model="codigo_iso_numerico"
                    class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                @error('codigo_iso_numerico') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Código ISO alpha 2 <span class="text-red-500">*</span></label>
                <input type="text" wire:model="codigo_iso_alpha_2"
                    class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                @error('codigo_iso_alpha_2') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre <span class="text-red-500">*</span></label>
                <input type="text" wire:model="nombre"
                    class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                @error('nombre') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Gentilicio <span class="text-red-500">*</span></label>
                <input type="text" wire:model="gentilicio"
                    class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                @error('gentilicio') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

        </div>

        <div class="flex gap-3 mt-6">
            <button type="submit"
                class="inline-flex items-center px-4 py-2 bg-blue-700 hover:bg-blue-800 text-white text-sm font-medium rounded-lg focus:ring-4 focus:ring-blue-300">
                <span wire:loading.remove>Guardar</span>
                <span wire:loading>Guardando...</span>
            </button>
            <a href="{{ url()->previous() }}"
                class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg">
                Cancelar
            </a>
        </div>
    </form>
</div>
