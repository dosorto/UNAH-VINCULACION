<div>
    <div class="mb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <div>
            <p class="text-zinc-950 dark:text-white font-bold mb-1">Asignaturas</p>
            <p class="text-zinc-500 dark:text-gray-400 font-medium text-sm">Gestiona las asignaturas del sistema.</p>
        </div>
        <button wire:click="openCreate" class="inline-flex items-center px-4 py-2 bg-blue-700 hover:bg-blue-800 text-white text-sm font-medium rounded-lg">+ Nueva asignatura</button>
    </div>

    <div class="mb-3">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre o codigo..." class="w-full sm:w-80 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm" />
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Codigo</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Nombre</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Carrera</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($records as $r)
                    <tr>
                        <td class="px-4 py-3">{{ $r->codigo }}</td>
                        <td class="px-4 py-3">{{ $r->nombre }}</td>
                        <td class="px-4 py-3">{{ optional($r->carrera)->nombre }}</td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <button wire:click="openEdit({{ $r->id }})" class="px-2.5 py-1 text-xs text-blue-700 bg-blue-50 rounded-md">Editar</button>
                            <button wire:click="delete({{ $r->id }})" class="px-2.5 py-1 text-xs text-red-700 bg-red-50 rounded-md">Eliminar</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">No se encontraron asignaturas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $records->links() }}</div>

    @if($createModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:click.self="$set('createModal', false)">
            <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full max-w-lg p-6 mx-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Nueva asignatura</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Codigo</label>
                        <input type="text" wire:model="create_codigo" class="w-full rounded-md border px-3 py-2" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre *</label>
                        <input type="text" wire:model="create_nombre" class="w-full rounded-md border px-3 py-2" />
                        @error('create_nombre') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Carrera</label>
                        <select wire:model="create_carrera_id" class="w-full rounded-md border px-3 py-2">
                            <option value="">-- Seleccione una carrera --</option>
                            @foreach($carrerasList as $id => $nombre)
                                <option value="{{ $id }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button wire:click="$set('createModal', false)" class="px-4 py-2 bg-gray-100 rounded-lg">Cancelar</button>
                    <button wire:click="store" class="px-4 py-2 bg-blue-700 text-white rounded-lg">Guardar</button>
                </div>
            </div>
        </div>
    @endif

    @if($editModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:click.self="$set('editModal', false)">
            <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full max-w-lg p-6 mx-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Editar asignatura</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Codigo</label>
                        <input type="text" wire:model="edit_codigo" class="w-full rounded-md border px-3 py-2" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre *</label>
                        <input type="text" wire:model="edit_nombre" class="w-full rounded-md border px-3 py-2" />
                        @error('edit_nombre') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Carrera</label>
                        <select wire:model="edit_carrera_id" class="w-full rounded-md border px-3 py-2">
                            <option value="">-- Seleccione una carrera --</option>
                            @foreach($carrerasList as $id => $nombre)
                                <option value="{{ $id }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button wire:click="$set('editModal', false)" class="px-4 py-2 bg-gray-100 rounded-lg">Cancelar</button>
                    <button wire:click="save" class="px-4 py-2 bg-blue-700 text-white rounded-lg">Guardar</button>
                </div>
            </div>
        </div>
    @endif
</div>
