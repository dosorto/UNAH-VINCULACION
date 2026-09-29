<div>
    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="mb-1 font-bold text-zinc-950 dark:text-white">Entidades contraparte</p>
            <p class="text-sm font-medium text-zinc-500 dark:text-gray-400">
                Catálogo del que los docentes eligen la contraparte de sus proyectos. Desde el proyecto no pueden modificar estos datos.
            </p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Buscar por nombre, RTN, contacto o correo..."
                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 sm:w-72"
            >
            <select wire:model.live="filtroTipo"
                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 sm:w-48">
                <option value="">Todos los tipos</option>
                @foreach ($tipos as $valor => $etiqueta)
                    <option value="{{ $valor }}">{{ $etiqueta }}</option>
                @endforeach
            </select>
            <label class="inline-flex items-center gap-2 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                <input type="checkbox" wire:model.live="showTrashed" class="rounded border-gray-300 dark:border-gray-600">
                Ver eliminadas
            </label>
            <button
                type="button"
                wire:click="openCreate"
                class="inline-flex items-center justify-center whitespace-nowrap rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800"
            >
                + Nueva contraparte
            </button>
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Nombre</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">RTN</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Tipo</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Contacto</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Teléfono</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Correo</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-300">Proyectos</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-800 dark:bg-gray-900">
                @forelse ($records as $record)
                    <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800 {{ $record->trashed() ? 'opacity-60' : '' }}">
                        <td class="min-w-64 px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $record->nombre }}</td>
                        <td class="whitespace-nowrap px-4 py-3">
                            @if (filled($record->rtn))
                                <span class="font-mono text-xs text-gray-700 dark:text-gray-300">{{ $record->rtn }}</span>
                            @else
                                <span class="inline-flex rounded-md bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">Sin RTN</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-gray-700 dark:text-gray-300">{{ $tipos[$record->tipo_entidad] ?? ($record->tipo_entidad ?: '—') }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                            {{ $record->nombre_contacto ?: '—' }}
                            @if (filled($record->cargo_contacto))
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $record->cargo_contacto }}</p>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-gray-700 dark:text-gray-300">{{ $record->telefono ?: '—' }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $record->correo ?: '—' }}</td>
                        <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">{{ $record->vinculaciones_proyecto_count }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            @if ($record->trashed())
                                <button
                                    type="button"
                                    x-on:click.prevent="confirmDialog('¿Restaurar esta contraparte?').then((ok) => ok && $wire.restore({{ $record->id }}))"
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
                                    x-on:click.prevent="confirmDialog('¿Eliminar esta contraparte del catálogo?', { type: 'danger' }).then((ok) => ok && $wire.delete({{ $record->id }}))"
                                    class="inline-flex rounded-md bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700 hover:bg-red-100 dark:bg-red-900/30 dark:text-red-400"
                                >
                                    Eliminar
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            No se encontraron contrapartes.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $records->links() }}</div>

    @if ($formModal)
        @php
            $campo = 'w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800';
        @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" wire:click.self="$set('formModal', false)">
            <div class="mx-4 max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-lg bg-white p-6 shadow-xl dark:bg-gray-900">
                <h3 class="{{ $editId ? 'mb-1' : 'mb-5' }} text-lg font-semibold text-gray-900 dark:text-white">{{ $editId ? 'Editar contraparte' : 'Nueva contraparte' }}</h3>
                @if ($editId)
                    <p class="mb-5 text-xs text-gray-500 dark:text-gray-400">
                        El nombre y el RTN se actualizan en las fichas de todos los proyectos que usan esta contraparte. El tipo y los datos de contacto se aplican a los proyectos que la seleccionen desde ahora.
                    </p>
                @endif

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre *</label>
                        <input type="text" wire:model="form.nombre" class="{{ $campo }}">
                        @error('form.nombre') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de contraparte *</label>
                        <select wire:model="form.tipo_entidad" class="{{ $campo }}">
                            <option value="">Seleccione...</option>
                            @foreach ($tipos as $valor => $etiqueta)
                                <option value="{{ $valor }}">{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                        @error('form.tipo_entidad') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">RTN / identificador fiscal</label>
                        <input type="text" wire:model="form.rtn" maxlength="50" class="{{ $campo }}">
                        @error('form.rtn') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre del contacto directo</label>
                        <input type="text" wire:model="form.nombre_contacto" class="{{ $campo }}">
                        @error('form.nombre_contacto') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Cargo del contacto</label>
                        <input type="text" wire:model="form.cargo_contacto" class="{{ $campo }}">
                        @error('form.cargo_contacto') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Teléfono</label>
                        <input type="text" wire:model="form.telefono" class="{{ $campo }}">
                        @error('form.telefono') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Correo electrónico</label>
                        <input type="email" wire:model="form.correo" class="{{ $campo }}">
                        @error('form.correo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" wire:click="$set('formModal', false)"
                        class="rounded-lg bg-gray-100 px-4 py-2 text-sm text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">
                        Cancelar
                    </button>
                    <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save"
                        class="rounded-lg bg-blue-700 px-4 py-2 text-sm text-white hover:bg-blue-800 disabled:opacity-60">
                        {{ $editId ? 'Guardar cambios' : 'Guardar' }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
