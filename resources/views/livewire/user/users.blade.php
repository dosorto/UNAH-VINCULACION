<div>
    <div class="mb-4 mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="mb-1 font-bold text-zinc-950 dark:text-white">Usuarios</p>
            <p class="text-sm font-medium text-zinc-500 dark:text-gray-400">Listado de usuarios registrados en el sistema.</p>
        </div>
        <button type="button" wire:click="openCreate"
            class="inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
            Nuevo usuario
        </button>
    </div>

    {{-- Filtros --}}
    <div class="mb-4 flex flex-wrap gap-3">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre o correo..."
            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white sm:w-72" />

        <select wire:model.live="filterRoleId"
            class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
            <option value="">Todos los roles</option>
            @foreach($allRoles as $role)
                <option value="{{ $role->id }}">{{ $role->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="filterActiveRoleId"
            class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
            <option value="">Todos los roles activos</option>
            @foreach($allRoles as $role)
                <option value="{{ $role->id }}">{{ $role->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="filterProfile"
            class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
            <option value="">Todos los perfiles</option>
            <option value="empleado">Empleado</option>
            <option value="estudiante">Estudiante</option>
            <option value="ambos">Empleado y estudiante</option>
            <option value="sin_perfil">Sin perfil</option>
        </select>

        <select wire:model.live="filterAccess"
            class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
            <option value="">Todos los estados</option>
            <option value="valido">Con acceso válido</option>
            <option value="sin_roles">Sin roles</option>
            <option value="sin_rol_activo">Sin rol activo</option>
        </select>

        @if($search !== '' || $filterRoleId !== '' || $filterActiveRoleId !== '' || $filterProfile !== '' || $filterAccess !== '')
            <button type="button" wire:click="resetFilters"
                class="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800">
                Limpiar filtros
            </button>
        @endif
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Nombre</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Correo</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Perfil</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Roles</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Rol activo</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Estado de acceso</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Fecha de registro</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-800 dark:bg-gray-900">
                @forelse($records as $record)
                    @php
                        $tieneEmpleado = $record->empleado !== null;
                        $tieneEstudiante = $record->estudiante !== null;
                        $rolActivo = $record->activeRole;
                        $rolActivoInvalido = $record->active_role_id !== null && $rolActivo === null;
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="whitespace-nowrap px-4 py-3 text-gray-900 dark:text-white">{{ $record->name }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-gray-700 dark:text-gray-300">{{ $record->email }}</td>
                        <td class="whitespace-nowrap px-4 py-3">
                            @if($tieneEmpleado && $tieneEstudiante)
                                <span class="rounded-full bg-violet-100 px-2 py-0.5 text-xs font-medium text-violet-800 dark:bg-violet-900/30 dark:text-violet-300">Empleado y estudiante</span>
                            @elseif($tieneEmpleado)
                                <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">Empleado</span>
                            @elseif($tieneEstudiante)
                                <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300">Estudiante</span>
                            @else
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">Sin perfil</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex min-w-36 flex-wrap gap-1">
                                @foreach($record->roles->take(2) as $role)
                                    <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/20 dark:text-blue-300">{{ $role->name }}</span>
                                @endforeach
                                @if($record->roles->count() > 2)
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">+{{ $record->roles->count() - 2 }}</span>
                                @endif
                                @if($record->roles->isEmpty())
                                    <span class="text-xs text-gray-500">Sin roles</span>
                                @endif
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            @if($rolActivo)
                                <span class="text-gray-900 dark:text-white">{{ $rolActivo->name }}</span>
                            @elseif($rolActivoInvalido)
                                <span class="font-medium text-red-600 dark:text-red-400">Rol activo inválido</span>
                            @else
                                <span class="text-gray-500">Sin rol activo</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            @if($record->roles->isEmpty())
                                <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900/30 dark:text-red-300">Sin roles</span>
                            @elseif(!$rolActivo)
                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">Sin rol activo</span>
                            @else
                                <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-300">Activo</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-gray-700 dark:text-gray-300">{{ $record->created_at?->format('d/m/Y') ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex min-w-56 flex-wrap gap-2">
                                <button type="button" wire:click="openEdit({{ $record->id }})"
                                    class="rounded-md bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700">Editar usuario</button>
                                <button type="button" wire:click="openRoles({{ $record->id }})"
                                    class="rounded-md border border-blue-300 px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-50 dark:border-blue-700 dark:text-blue-300 dark:hover:bg-blue-950/30">Roles y rol activo</button>

                                @can('empleados.empleados')
                                    @if($tieneEmpleado)
                                        <a href="{{ route('ListarEmpleados') }}" wire:navigate class="text-xs font-medium text-blue-700 underline dark:text-blue-300">Ver empleado</a>
                                    @else
                                        <a href="{{ route('crearEmpleado') }}" wire:navigate class="text-xs font-medium text-blue-700 underline dark:text-blue-300">Vincular perfil laboral</a>
                                    @endif
                                @endcan

                                @if($tieneEstudiante)
                                    @can('estudiante.admin')
                                        <a href="{{ route('listarEstudiante') }}" wire:navigate class="text-xs font-medium text-blue-700 underline dark:text-blue-300">Ver estudiante</a>
                                    @endcan
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No se encontraron usuarios.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $records->links() }}</div>

    {{-- Modal crear --}}
    @if($createModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" wire:click.self="$set('createModal', false)">
            <div class="mx-4 max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-xl bg-white shadow-xl dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Nuevo usuario</h3>
                    <button type="button" wire:click="$set('createModal', false)" class="text-xl text-gray-400 hover:text-gray-600">&times;</button>
                </div>
                <div class="space-y-6 p-6">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="create_name" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white" />
                            @error('create_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Correo <span class="text-red-500">*</span></label>
                            <input type="email" wire:model="create_email" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white" />
                            @error('create_email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Contraseña <span class="text-red-500">*</span></label>
                            <input type="password" wire:model="create_password" autocomplete="new-password" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white" />
                            @error('create_password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Confirmar contraseña <span class="text-red-500">*</span></label>
                            <input type="password" wire:model="create_password_confirmation" autocomplete="new-password" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white" />
                        </div>
                    </div>

                    <div>
                        <p class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Roles <span class="text-red-500">*</span></p>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach($allRoles as $role)
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input type="checkbox" wire:model.live="create_roles" value="{{ $role->id }}" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                                    {{ $role->name }}
                                </label>
                            @endforeach
                        </div>
                        @error('create_roles') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        @error('create_roles.*') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Rol activo</label>
                        <select wire:model="create_active_role_id" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                            <option value="">{{ count($create_roles) === 1 ? 'Se asignará el único rol seleccionado' : 'Seleccione...' }}</option>
                            @foreach($allRoles->whereIn('id', array_map('intval', $create_roles)) as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                        @error('create_active_role_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                    <button type="button" wire:click="$set('createModal', false)" class="rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">Cancelar</button>
                    <button type="button" wire:click="store" wire:loading.attr="disabled" wire:target="store" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-60">Crear usuario</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal identidad --}}
    @if($editModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" wire:click.self="$set('editModal', false)">
            <div class="mx-4 w-full max-w-3xl rounded-xl bg-white shadow-xl dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Editar usuario</h3>
                    <button type="button" wire:click="$set('editModal', false)" class="text-xl text-gray-400 hover:text-gray-600">&times;</button>
                </div>
                <div class="space-y-4 p-6">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="edit_name" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white" />
                            @error('edit_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Correo <span class="text-red-500">*</span></label>
                            <input type="email" wire:model.live="edit_email" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white" />
                            @error('edit_email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    @if($edit_has_microsoft && mb_strtolower(trim($edit_email)) !== mb_strtolower(trim($edit_original_email)))
                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-200">
                            <p>Este usuario está vinculado con Microsoft. Cambiar el correo puede afectar el inicio de sesión.</p>
                            <label class="mt-2 flex items-start gap-2 font-medium">
                                <input type="checkbox" wire:model="confirm_microsoft_email_change" class="mt-0.5 rounded border-gray-300 text-blue-600" />
                                Confirmo que deseo cambiar el correo de esta cuenta vinculada.
                            </label>
                            @error('confirm_microsoft_email_change') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    <p class="text-xs text-gray-500 dark:text-gray-400">La contraseña, el identificador Microsoft, el rol activo y los perfiles relacionados no se modifican desde este formulario.</p>
                </div>
                <div class="flex justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                    <button type="button" wire:click="$set('editModal', false)" class="rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">Cancelar</button>
                    <button type="button" wire:click="saveIdentity" wire:loading.attr="disabled" wire:target="saveIdentity" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-60">Guardar cambios</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal roles --}}
    @if($rolesModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" wire:click.self="$set('rolesModal', false)">
            <div class="mx-4 max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-xl bg-white shadow-xl dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Roles y rol activo</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $rolesUserName }}</p>
                    </div>
                    <button type="button" wire:click="$set('rolesModal', false)" class="text-xl text-gray-400 hover:text-gray-600">&times;</button>
                </div>
                <div class="space-y-6 p-6">
                    @if(auth()->id() === $rolesUserId)
                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-200">
                            Está modificando sus propios roles. El sistema impedirá que retire su último acceso a la administración de usuarios.
                        </div>
                    @endif

                    <div>
                        <p class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Roles disponibles</p>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach($allRoles as $role)
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input type="checkbox" wire:model.live="manage_roles" value="{{ $role->id }}" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                                    {{ $role->name }}
                                </label>
                            @endforeach
                        </div>
                        @error('manage_roles') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        @error('manage_roles.*') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="rounded-lg border border-green-200 bg-green-50 p-3 dark:border-green-900 dark:bg-green-950/20">
                            <p class="text-xs font-semibold uppercase tracking-wide text-green-800 dark:text-green-300">Se agregarán</p>
                            <p class="mt-1 text-sm text-green-900 dark:text-green-200">{{ $addedRoleNames->isEmpty() ? 'Ninguno' : $addedRoleNames->implode(', ') }}</p>
                        </div>
                        <div class="rounded-lg border border-red-200 bg-red-50 p-3 dark:border-red-900 dark:bg-red-950/20">
                            <p class="text-xs font-semibold uppercase tracking-wide text-red-800 dark:text-red-300">Se retirarán</p>
                            <p class="mt-1 text-sm text-red-900 dark:text-red-200">{{ $removedRoleNames->isEmpty() ? 'Ninguno' : $removedRoleNames->implode(', ') }}</p>
                        </div>
                    </div>

                    @if($removedRoleNames->isNotEmpty())
                        <div>
                            <label class="flex items-start gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                <input type="checkbox" wire:model="confirm_roles_removal" class="mt-0.5 rounded border-gray-300 text-blue-600" />
                                Confirmo la retirada de los roles indicados.
                            </label>
                            @error('confirm_roles_removal') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

                            @if(auth()->id() === $rolesUserId)
                                <label class="mt-3 flex items-start gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    <input type="checkbox" wire:model="confirm_administrative_removal" class="mt-0.5 rounded border-gray-300 text-blue-600" />
                                    Confirmo la retirada de roles administrativos de mi propio usuario.
                                </label>
                                @error('confirm_administrative_removal') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            @endif
                        </div>
                    @endif

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Rol activo <span class="text-red-500">*</span></label>
                        <select wire:model="manage_active_role_id" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                            <option value="">{{ count($manage_roles) === 1 ? 'Se asignará el único rol seleccionado' : 'Seleccione...' }}</option>
                            @foreach($allRoles->whereIn('id', array_map('intval', $manage_roles)) as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                        @error('manage_active_role_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                    <button type="button" wire:click="$set('rolesModal', false)" class="rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">Cancelar</button>
                    <button type="button" wire:click="saveRoles" wire:loading.attr="disabled" wire:target="saveRoles" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-60">Guardar roles</button>
                </div>
            </div>
        </div>
    @endif
</div>
