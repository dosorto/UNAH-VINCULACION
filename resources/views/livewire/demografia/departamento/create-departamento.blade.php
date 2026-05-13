<div>
    <div class="mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Crear un nuevo Departamento</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">Crea un nuevo departamento asociado a un país.</p>
    </div>

    <form wire:submit="create">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">País <span class="text-red-500">*</span></label>
                <select wire:model="pais_id"
                    class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Selecciona un país</option>
                    @foreach ($paises as $id => $nombre)
                        <option value="{{ $id }}">{{ $nombre }}</option>
                    @endforeach
                </select>
                @error('pais_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre <span class="text-red-500">*</span></label>
                <input type="text" wire:model="nombre"
                    class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                @error('nombre') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Código del Departamento</label>
                <input type="text" wire:model="codigo_departamento"
                    class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                @error('codigo_departamento') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
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
