<div>
    @php
        $puedeCompletar = in_array($estadoBusqueda, [
            \App\Livewire\Personal\Empleado\CreateEmpleado::ESTADO_USUARIO_NUEVO,
            \App\Livewire\Personal\Empleado\CreateEmpleado::ESTADO_VINCULAR,
        ], true);
        $esUsuarioNuevo = $estadoBusqueda === \App\Livewire\Personal\Empleado\CreateEmpleado::ESTADO_USUARIO_NUEVO;
        $esVinculacion = $estadoBusqueda === \App\Livewire\Personal\Empleado\CreateEmpleado::ESTADO_VINCULAR;
        $inputClass = 'w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500';
    @endphp

    <div class="mb-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Crear Empleado</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Busque primero la cuenta institucional para crear o vincular su perfil laboral sin duplicar usuarios.</p>
    </div>

    <form wire:submit.prevent="create" class="space-y-6">
        {{-- Cuenta institucional --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Cuenta institucional</p>
            <div class="grid grid-cols-1 sm:grid-cols-[minmax(0,1fr)_auto] gap-3 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Correo institucional <span class="text-red-500">*</span></label>
                    <input type="email" wire:model="email" placeholder="usuario@unah.edu.hn" class="{{ $inputClass }}" />
                    @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <button type="button" wire:click="buscarCorreo" wire:loading.attr="disabled" wire:target="buscarCorreo"
                    class="inline-flex min-h-10 items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:cursor-wait disabled:opacity-60">
                    <span wire:loading.remove wire:target="buscarCorreo">Buscar</span>
                    <span wire:loading wire:target="buscarCorreo">Buscando...</span>
                </button>
            </div>

            @if($estadoBusqueda === \App\Livewire\Personal\Empleado\CreateEmpleado::ESTADO_USUARIO_NUEVO)
                <div class="mt-4 rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-800 dark:border-blue-900/70 dark:bg-blue-950/30 dark:text-blue-200">
                    No existe una cuenta con este correo. Se creará el usuario y su perfil de empleado.
                </div>
            @elseif($estadoBusqueda === \App\Livewire\Personal\Empleado\CreateEmpleado::ESTADO_VINCULAR)
                <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-900/70 dark:bg-amber-950/30 dark:text-amber-200">
                    El usuario ya está registrado, pero todavía no posee perfil laboral.
                </div>
            @elseif($estadoBusqueda === \App\Livewire\Personal\Empleado\CreateEmpleado::ESTADO_CON_EMPLEADO)
                <div class="mt-4 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800 dark:border-green-900/70 dark:bg-green-950/30 dark:text-green-200">
                    Este usuario ya posee un perfil de empleado.
                    <a href="{{ route('ListarEmpleados') }}" wire:navigate class="ml-1 font-semibold underline">Abrir listado para editar</a>
                </div>
            @elseif($estadoBusqueda === \App\Livewire\Personal\Empleado\CreateEmpleado::ESTADO_USUARIO_ELIMINADO)
                <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800 dark:border-red-900/70 dark:bg-red-950/30 dark:text-red-200">
                    La cuenta está eliminada y requiere recuperación explícita antes de vincularla.
                </div>
            @elseif($estadoBusqueda === \App\Livewire\Personal\Empleado\CreateEmpleado::ESTADO_EMPLEADO_ELIMINADO)
                <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800 dark:border-red-900/70 dark:bg-red-950/30 dark:text-red-200">
                    El usuario posee un perfil laboral eliminado. Debe recuperarse explícitamente.
                </div>
            @endif

            @if($esUsuarioNuevo)
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre de Usuario <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="name" class="{{ $inputClass }}" />
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            @elseif($usuarioExistenteId)
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Usuario existente</p>
                        <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $name }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Roles actuales</p>
                        <div class="mt-1 flex flex-wrap gap-1">
                            @forelse($rolesExistentes as $rol)
                                <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/40 dark:text-blue-200">{{ $rol }}</span>
                            @empty
                                <span class="text-sm text-gray-500">Sin roles asignados</span>
                            @endforelse
                        </div>
                        <p class="mt-1 text-xs text-gray-500">La vinculación no modificará estos roles ni la contraseña.</p>
                    </div>
                </div>
            @endif
        </div>

        @if($puedeCompletar)
            {{-- Empleado --}}
            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Empleado</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre Completo <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="nombre_completo" class="{{ $inputClass }}" />
                        @error('nombre_completo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">N° de Empleado <span class="text-red-500">*</span></label>
                        <input type="text" inputmode="numeric" wire:model="numero_empleado" class="{{ $inputClass }}" />
                        @error('numero_empleado') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Celular <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="celular" class="{{ $inputClass }}" />
                        @error('celular') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Jornada laboral</label>
                        <input type="text" wire:model="jornada_laboral" class="{{ $inputClass }}" />
                        @error('jornada_laboral') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo de empleado <span class="text-red-500">*</span></label>
                        <select wire:model="tipo_empleado" class="{{ $inputClass }}">
                            <option value="docente">Docente</option>
                            <option value="administrativo">Administrativo</option>
                        </select>
                        @error('tipo_empleado') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Categoría</label>
                        <select wire:model="categoria_id" class="{{ $inputClass }}">
                            <option value="">Sin categoría</option>
                            @foreach($categorias as $id => $nombre)<option value="{{ $id }}">{{ $nombre }}</option>@endforeach
                        </select>
                        @error('categoria_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Facultad o Centro <span class="text-red-500">*</span></label>
                        <select wire:model.live="centro_facultad_id" class="{{ $inputClass }}">
                            <option value="">Seleccione...</option>
                            @foreach($centros as $id => $nombre)<option value="{{ $id }}">{{ $nombre }}</option>@endforeach
                        </select>
                        @error('centro_facultad_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    @if($departamentos->count())
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Departamento Académico</label>
                            <select wire:model="departamento_academico_id" class="{{ $inputClass }}">
                                <option value="">Sin departamento</option>
                                @foreach($departamentos as $id => $nombre)<option value="{{ $id }}">{{ $nombre }}</option>@endforeach
                            </select>
                            @error('departamento_academico_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    @endif
                </div>
            </div>

            @if($esUsuarioNuevo)
                {{-- Roles para cuentas nuevas --}}
                <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Roles <span class="text-red-500">*</span></p>
                    <p class="mb-4 text-xs text-gray-500">Los roles solo se asignan al crear una cuenta nueva.</p>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                        @foreach($allRoles as $role)
                            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input type="checkbox" wire:model="create_roles" value="{{ $role->id }}" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                                {{ $role->name }}
                            </label>
                        @endforeach
                    </div>
                    @error('create_roles') <p class="text-red-500 text-xs mt-2">{{ $message }}</p> @enderror
                    @error('create_roles.*') <p class="text-red-500 text-xs mt-2">{{ $message }}</p> @enderror
                </div>
            @endif

            <div class="flex flex-wrap gap-3">
                <button type="submit" wire:loading.attr="disabled" wire:target="create"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-60">
                    <span wire:loading.remove wire:target="create">{{ $esVinculacion ? 'Vincular perfil laboral' : 'Crear usuario y empleado' }}</span>
                    <span wire:loading wire:target="create">Guardando...</span>
                </button>
                <a href="{{ route('ListarEmpleados') }}" wire:navigate class="inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800">Cancelar</a>
            </div>
        @elseif($estadoBusqueda === \App\Livewire\Personal\Empleado\CreateEmpleado::ESTADO_SIN_BUSCAR)
            <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-900/50">
                Ingrese el correo institucional y pulse Buscar para continuar.
            </div>
        @endif
    </form>
</div>
