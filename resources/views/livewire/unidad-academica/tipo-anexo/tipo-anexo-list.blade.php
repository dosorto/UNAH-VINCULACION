<div>
    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="mb-1 font-bold text-zinc-950 dark:text-white">Tipos de anexos</p>
            <p class="text-sm font-medium text-zinc-500 dark:text-gray-400">
                Catálogo de documentos disponibles en el paso de anexos de los proyectos de vinculación.
            </p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Buscar por nombre o código..."
                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 sm:w-64"
            >
            <label class="inline-flex items-center gap-2 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                <input type="checkbox" wire:model.live="showTrashed" class="rounded border-gray-300 dark:border-gray-600">
                Ver eliminados
            </label>
            <button
                type="button"
                wire:click="openCreate"
                class="inline-flex items-center justify-center whitespace-nowrap rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800"
            >
                + Nuevo tipo
            </button>
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Código</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Nombre</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-300">Orden</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-300">Detalle adicional</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-300">Anexos</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Estado</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-800 dark:bg-gray-900">
                @forelse ($records as $record)
                    <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800 {{ $record->trashed() ? 'opacity-60' : '' }}">
                        <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $record->codigo }}</td>
                        <td class="min-w-80 px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $record->nombre }}</td>
                        <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">{{ $record->orden }}</td>
                        <td class="px-4 py-3 text-center">
                            @if ($record->requiere_detalle)
                                <span class="inline-flex rounded-md bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">Sí</span>
                            @else
                                <span class="text-gray-500 dark:text-gray-400">No</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">{{ $record->anexos_count }}</td>
                        <td class="px-4 py-3">
                            @if ($record->activo)
                                <span class="inline-flex rounded-md bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">Activo</span>
                            @else
                                <span class="inline-flex rounded-md bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">Inactivo</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            @if ($record->trashed())
                                <button
                                    type="button"
                                    x-on:click.prevent="confirmDialog('¿Restaurar este tipo de anexo?').then((ok) => ok && $wire.restore({{ $record->id }}))"
                                    class="inline-flex rounded-md bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700 hover:bg-green-100 dark:bg-green-900/30 dark:text-green-400"
                                >
                                    Restaurar
                                </button>
                            @else
                                <button
                                    type="button"
                                    wire:click="openEdit({{ $record->id }})"
                                    class="mr-2 inline-flex rounded-md bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400"
                                >
                                    Editar
                                </button>
                                <button
                                    type="button"
                                    x-on:click.prevent="confirmDialog('¿Eliminar este tipo de anexo?', { type: 'danger' }).then((ok) => ok && $wire.delete({{ $record->id }}))"
                                    class="inline-flex rounded-md bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700 hover:bg-red-100 dark:bg-red-900/30 dark:text-red-400"
                                >
                                    Eliminar
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            No se encontraron tipos de anexos.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $records->links() }}</div>

    @if ($createModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" wire:click.self="$set('createModal', false)">
            <div class="mx-4 w-full max-w-2xl rounded-lg bg-white p-6 shadow-xl dark:bg-gray-900">
                <h3 class="mb-1 text-lg font-semibold text-gray-900 dark:text-white">Nuevo tipo de anexo</h3>
                <p class="mb-5 text-xs text-gray-500 dark:text-gray-400">El código es el identificador interno y se normaliza automáticamente.</p>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Código *</label>
                        <input type="text" wire:model="create_codigo" placeholder="ejemplo_tipo_documento"
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800">
                        @error('create_codigo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Orden *</label>
                        <input type="number" min="0" max="65535" wire:model="create_orden"
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800">
                        @error('create_orden') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre *</label>
                        <textarea wire:model="create_nombre" rows="3"
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800"></textarea>
                        @error('create_nombre') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" wire:model="create_requiere_detalle" class="rounded border-gray-300 dark:border-gray-600">
                        Solicitar detalle adicional al cargar
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" wire:model="create_activo" class="rounded border-gray-300 dark:border-gray-600">
                        Activo
                    </label>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" wire:click="$set('createModal', false)"
                        class="rounded-lg bg-gray-100 px-4 py-2 text-sm text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">
                        Cancelar
                    </button>
                    <button type="button" wire:click="store" wire:loading.attr="disabled" wire:target="store"
                        class="rounded-lg bg-blue-700 px-4 py-2 text-sm text-white hover:bg-blue-800 disabled:opacity-60">
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if ($editModal)
        @php
            $editandoTipoBase = in_array($edit_codigo, array_column(\App\Models\Proyecto\TipoAnexo::TIPOS_BASE, 'codigo'), true);
        @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" wire:click.self="$set('editModal', false)">
            <div class="mx-4 w-full max-w-2xl rounded-lg bg-white p-6 shadow-xl dark:bg-gray-900">
                <h3 class="mb-5 text-lg font-semibold text-gray-900 dark:text-white">Editar tipo de anexo</h3>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Código *</label>
                        <input type="text" wire:model="edit_codigo" readonly
                            class="w-full cursor-not-allowed rounded-md border border-gray-300 bg-gray-100 px-3 py-2 text-sm text-gray-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Identificador interno; no puede modificarse después de crear el tipo.</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Orden *</label>
                        <input type="number" min="0" max="65535" wire:model="edit_orden"
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800">
                        @error('edit_orden') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre *</label>
                        <textarea wire:model="edit_nombre" rows="3"
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800"></textarea>
                        @error('edit_nombre') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" wire:model="edit_requiere_detalle" @disabled($editandoTipoBase) class="rounded border-gray-300 dark:border-gray-600 disabled:cursor-not-allowed disabled:opacity-60">
                        Solicitar detalle adicional al cargar
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" wire:model="edit_activo" @disabled($editandoTipoBase) class="rounded border-gray-300 dark:border-gray-600 disabled:cursor-not-allowed disabled:opacity-60">
                        Activo
                    </label>
                    @if ($editandoTipoBase)
                        <p class="text-xs text-gray-500 dark:text-gray-400 sm:col-span-2">
                            Los tipos institucionales permanecen activos; la solicitud de detalle también está fijada por el formato oficial.
                        </p>
                    @endif
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" wire:click="$set('editModal', false)"
                        class="rounded-lg bg-gray-100 px-4 py-2 text-sm text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">
                        Cancelar
                    </button>
                    <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save"
                        class="rounded-lg bg-blue-700 px-4 py-2 text-sm text-white hover:bg-blue-800 disabled:opacity-60">
                        Guardar cambios
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
