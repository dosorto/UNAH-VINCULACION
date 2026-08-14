<div>
    {{-- Cabecera --}}
    <div class="mb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <div>
            <p class="text-zinc-950 dark:text-white font-bold mb-1">Niveles Académicos</p>
            <p class="text-zinc-500 dark:text-gray-400 font-medium text-sm">Catálogo de niveles académicos disponibles para los integrantes internacionales de voluntariado.</p>
        </div>
        <div class="flex gap-2">
            <label class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                <input type="checkbox" wire:model.live="showTrashed" class="rounded border-gray-300 dark:border-gray-600">
                Ver eliminados
            </label>
            <button wire:click="openCreate"
                class="inline-flex items-center px-4 py-2 bg-blue-700 hover:bg-blue-800 text-white text-sm font-medium rounded-lg">
                + Nuevo
            </button>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Nombre</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Orden</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Estado</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($records as $record)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors {{ $record->trashed() ? 'opacity-60' : '' }}">
                        <td class="px-4 py-3 text-gray-900 dark:text-white font-medium">{{ $record->nombre }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $record->orden }}</td>
                        <td class="px-4 py-3">
                            @if($record->activo)
                                <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium text-green-700 bg-green-50 dark:bg-green-900/30 dark:text-green-400 rounded-md">Activo</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium text-gray-600 bg-gray-100 dark:bg-gray-800 dark:text-gray-400 rounded-md">Inactivo</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right space-x-2">
                            @if($record->trashed())
                                <button x-on:click.prevent="confirmDialog('¿Restaurar este nivel académico?').then((ok) => ok && $wire.restore({{ $record->id }}))"
                                    class="inline-flex items-center px-2.5 py-1 text-xs font-medium text-green-700 bg-green-50 hover:bg-green-100 dark:bg-green-900/30 dark:text-green-400 rounded-md">
                                    Restaurar
                                </button>
                            @else
                                <button wire:click="openEdit({{ $record->id }})"
                                    class="inline-flex items-center px-2.5 py-1 text-xs font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400 rounded-md">
                                    Editar
                                </button>
                                <button x-on:click.prevent="confirmDialog('¿Eliminar este nivel académico?', { type: 'danger' }).then((ok) => ok && $wire.delete({{ $record->id }}))"
                                    class="inline-flex items-center px-2.5 py-1 text-xs font-medium text-red-700 bg-red-50 hover:bg-red-100 dark:bg-red-900/30 dark:text-red-400 rounded-md">
                                    Eliminar
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                            No se encontraron registros.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $records->links() }}</div>

    {{-- Modal Crear --}}
    @if ($createModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:click.self="$set('createModal', false)">
            <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full max-w-md p-6 mx-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Nuevo Nivel Académico</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Ej: Estudiante de grado, Maestría, Doctorado/Posgrado.</p>
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre *</label>
                        <input type="text" wire:model="create_nombre"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm" />
                        @error('create_nombre') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Orden</label>
                        <input type="number" min="0" wire:model="create_orden"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm" />
                        @error('create_orden') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-end pb-2">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input type="checkbox" wire:model="create_activo" class="rounded border-gray-300 dark:border-gray-600">
                            Activo
                        </label>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button wire:click="$set('createModal', false)"
                        class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 rounded-lg">
                        Cancelar
                    </button>
                    <button wire:click="store"
                        class="px-4 py-2 text-sm text-white bg-blue-700 hover:bg-blue-800 rounded-lg">
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal Editar --}}
    @if ($editModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:click.self="$set('editModal', false)">
            <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full max-w-md p-6 mx-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Editar Nivel Académico</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre *</label>
                        <input type="text" wire:model="edit_nombre"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm" />
                        @error('edit_nombre') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Orden</label>
                        <input type="number" min="0" wire:model="edit_orden"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm" />
                        @error('edit_orden') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-end pb-2">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input type="checkbox" wire:model="edit_activo" class="rounded border-gray-300 dark:border-gray-600">
                            Activo
                        </label>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button wire:click="$set('editModal', false)"
                        class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 rounded-lg">
                        Cancelar
                    </button>
                    <button wire:click="save"
                        class="px-4 py-2 text-sm text-white bg-blue-700 hover:bg-blue-800 rounded-lg">
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
