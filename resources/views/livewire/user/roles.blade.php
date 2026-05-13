<div>
    {{-- Cabecera --}}
    <div class="mb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <div>
            <p class="text-zinc-950 dark:text-white font-bold mb-1">Roles</p>
            <p class="text-zinc-500 dark:text-gray-400 font-medium text-sm">Administración de roles y permisos.</p>
        </div>
        <button wire:click="openCreate"
            class="inline-flex items-center px-4 py-2 bg-blue-700 hover:bg-blue-800 text-white text-sm font-medium rounded-lg">
            + Nuevo rol
        </button>
    </div>

    {{-- Búsqueda --}}
    <div class="mb-3">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre..."
            class="w-full sm:w-80 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500" />
    </div>

    {{-- Tabla --}}
    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Nombre</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Usuarios</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Creado</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($records as $record)
                    @php $protected = $this->isProtected($record->name); @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        <td class="px-4 py-3 text-gray-900 dark:text-white">
                            {{ $record->name }}
                            @if($protected)
                                <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">protegido</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $record->users_count }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $record->created_at->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-right space-x-2">
                            @unless($protected || $record->users_count > 0)
                                <button wire:click="openEdit({{ $record->id }})"
                                    class="inline-flex items-center px-2.5 py-1 text-xs font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400 rounded-md">
                                    Editar
                                </button>
                                <button wire:click="delete({{ $record->id }})" wire:confirm="¿Eliminar este rol?"
                                    class="inline-flex items-center px-2.5 py-1 text-xs font-medium text-red-700 bg-red-50 hover:bg-red-100 dark:bg-red-900/30 dark:text-red-400 rounded-md">
                                    Eliminar
                                </button>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No se encontraron roles.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $records->links() }}</div>

    {{-- Modal Crear --}}
    @if ($createModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:click.self="$set('createModal', false)">
            <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full max-w-lg p-6 mx-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Nuevo Rol</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre *</label>
                        <input type="text" wire:model="create_name"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm" />
                        @error('create_name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Permisos</label>
                        <div class="max-h-72 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-md p-2 space-y-3">
                            @foreach($permissionsGrouped as $module => $perms)
                                <div>
                                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">{{ $module }}</p>
                                    <div class="grid grid-cols-2 gap-1">
                                        @foreach($perms as $perm)
                                            <label class="inline-flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300">
                                                <input type="checkbox" wire:model="create_permissions" value="{{ $perm->id }}" class="rounded border-gray-300">
                                                {{ $perm->display_name ?? $perm->name }}
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button wire:click="$set('createModal', false)"
                        class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 rounded-lg">Cancelar</button>
                    <button wire:click="store"
                        class="px-4 py-2 text-sm text-white bg-blue-700 hover:bg-blue-800 rounded-lg">Guardar</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal Editar --}}
    @if ($editModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:click.self="$set('editModal', false)">
            <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full max-w-lg p-6 mx-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Editar Rol</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre *</label>
                        <input type="text" wire:model="edit_name"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm" />
                        @error('edit_name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Permisos</label>
                        <div class="max-h-72 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-md p-2 space-y-3">
                            @foreach($permissionsGrouped as $module => $perms)
                                <div>
                                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">{{ $module }}</p>
                                    <div class="grid grid-cols-2 gap-1">
                                        @foreach($perms as $perm)
                                            <label class="inline-flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300">
                                                <input type="checkbox" wire:model="edit_permissions" value="{{ $perm->id }}" class="rounded border-gray-300">
                                                {{ $perm->display_name ?? $perm->name }}
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button wire:click="$set('editModal', false)"
                        class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 rounded-lg">Cancelar</button>
                    <button wire:click="save"
                        class="px-4 py-2 text-sm text-white bg-blue-700 hover:bg-blue-800 rounded-lg">Guardar</button>
                </div>
            </div>
        </div>
    @endif
</div>
