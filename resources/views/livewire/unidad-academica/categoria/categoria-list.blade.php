<div>
    <div class="mb-4 flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
        <div>
            <p class="mb-1 font-bold text-zinc-950 dark:text-white">Categorías</p>
            <p class="text-sm font-medium text-zinc-500 dark:text-gray-400">
                Catálogo de categorías laborales y docentes utilizadas en los perfiles de empleados.
            </p>
        </div>
        <div class="flex gap-2">
            <button type="button" wire:click="exportExcel"
                    class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                Exportar Excel
            </button>
            <button type="button" wire:click="openCreate"
                    class="inline-flex items-center rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">
                + Nueva categoría
            </button>
        </div>
    </div>

    <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center">
        <input type="text" wire:model.live.debounce.300ms="search"
               placeholder="Buscar por nombre o descripción..."
               class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white sm:w-96">
        <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
            <input type="checkbox" wire:model.live="showTrashed" class="rounded border-gray-300">
            Mostrar eliminadas
        </label>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Nombre</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Descripción</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-300">Empleados</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-300">Estado</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-800 dark:bg-gray-900">
                @forelse ($records as $record)
                    <tr wire:key="categoria-{{ $record->id }}" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800 {{ $record->trashed() ? 'opacity-60' : '' }}">
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $record->nombre }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $record->descripcion ?: '—' }}</td>
                        <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">{{ $record->empleados_count }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium {{ $record->trashed() ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' }}">
                                {{ $record->trashed() ? 'Eliminada' : 'Activa' }}
                            </span>
                        </td>
                        <td class="space-x-2 px-4 py-3 text-right">
                            @if ($record->trashed())
                                <button type="button"
                                        x-on:click.prevent="confirmDialog('¿Restaurar esta categoría?').then((ok) => ok && $wire.restore({{ $record->id }}))"
                                        class="inline-flex items-center rounded-md bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700 hover:bg-green-100 dark:bg-green-900/30 dark:text-green-400">
                                    Restaurar
                                </button>
                            @else
                                <button type="button" wire:click="openEdit({{ $record->id }})"
                                        class="inline-flex items-center rounded-md bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400">
                                    Editar
                                </button>
                                <button type="button"
                                        x-on:click.prevent="confirmDialog('¿Eliminar esta categoría?', { type: 'danger' }).then((ok) => ok && $wire.delete({{ $record->id }}))"
                                        class="inline-flex items-center rounded-md bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700 hover:bg-red-100 dark:bg-red-900/30 dark:text-red-400">
                                    Eliminar
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            No se encontraron categorías.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $records->links() }}</div>

    @if ($createModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" wire:click.self="$set('createModal', false)">
            <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl dark:bg-gray-900">
                <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Nueva categoría</h3>
                <div class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre *</label>
                        <input type="text" wire:model="createNombre" maxlength="255"
                               class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                        @error('createNombre') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Descripción</label>
                        <textarea wire:model="createDescripcion" rows="4" maxlength="255"
                                  class="w-full resize-y rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white"></textarea>
                        @error('createDescripcion') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" wire:click="$set('createModal', false)"
                            class="rounded-lg bg-gray-100 px-4 py-2 text-sm text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">
                        Cancelar
                    </button>
                    <button type="button" wire:click="store" wire:loading.attr="disabled" wire:target="store"
                            class="rounded-lg bg-blue-700 px-4 py-2 text-sm text-white hover:bg-blue-800 disabled:opacity-50">
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if ($editModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" wire:click.self="$set('editModal', false)">
            <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl dark:bg-gray-900">
                <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Editar categoría</h3>
                <div class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre *</label>
                        <input type="text" wire:model="editNombre" maxlength="255"
                               class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                        @error('editNombre') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Descripción</label>
                        <textarea wire:model="editDescripcion" rows="4" maxlength="255"
                                  class="w-full resize-y rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white"></textarea>
                        @error('editDescripcion') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" wire:click="$set('editModal', false)"
                            class="rounded-lg bg-gray-100 px-4 py-2 text-sm text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">
                        Cancelar
                    </button>
                    <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save"
                            class="rounded-lg bg-blue-700 px-4 py-2 text-sm text-white hover:bg-blue-800 disabled:opacity-50">
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
