<div>
    <div class="mb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <div>
            <p class="text-zinc-950 dark:text-white font-bold mb-1">Listado de municipios</p>
            <p class="text-zinc-500 dark:text-gray-400 font-medium text-sm">A continuación se muestra el listado de municipios.</p>
        </div>
        <a href="{{ route('crearMunicipio') }}" wire:navigate class="inline-flex items-center px-4 py-2 bg-blue-700 hover:bg-blue-800 text-white text-sm font-medium rounded-lg">+ Nuevo</a>
    </div>
    <div class="mb-3">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar..."
            class="w-full sm:w-80 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm shadow-sm" />
    </div>
    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Departamento</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Municipio</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Código</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($records as $record)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $record->departamento->nombre ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $record->nombre }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $record->codigo_municipio }}</td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <button wire:click="openEdit({{ $record->id }})" class="px-2.5 py-1 text-xs text-blue-700 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400 rounded-md">Editar</button>
                            <button wire:click="delete({{ $record->id }})" wire:confirm="¿Eliminar?" class="px-2.5 py-1 text-xs text-red-700 bg-red-50 hover:bg-red-100 dark:bg-red-900/30 dark:text-red-400 rounded-md">Eliminar</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">Sin registros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $records->links() }}</div>
    @if ($editModal)
    <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:click.self="$set('editModal', false)">
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full max-w-lg p-6 mx-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Editar Municipio</h3>
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Departamento *</label>
                    <select wire:model="edit_departamento_id" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                        <option value="">Selecciona un departamento</option>
                        @foreach ($departamentos as $id => $nombre)
                            <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                    @error('edit_departamento_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre *</label>
                    <input type="text" wire:model="edit_nombre" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm" />
                    @error('edit_nombre') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Código</label>
                    <input type="text" wire:model="edit_codigo_municipio" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm" />
                    @error('edit_codigo_municipio') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button wire:click="$set('editModal', false)" class="px-4 py-2 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg">Cancelar</button>
                <button wire:click="save" class="px-4 py-2 text-sm text-white bg-blue-700 hover:bg-blue-800 rounded-lg">Guardar</button>
            </div>
        </div>
    </div>
    @endif
</div>
