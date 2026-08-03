<div>
    @php $canEdit = \App\Support\ProfileCompletion::isRequired(auth()->user()); @endphp

    <form wire:submit="save" class="space-y-6">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-6 {{ !$canEdit ? 'opacity-60' : '' }}">
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Perfil Estudiante</p>
            @if (!$canEdit)
                <p class="text-xs text-yellow-600 dark:text-yellow-400 mb-3">No tiene permiso para editar estos datos actualmente.</p>
            @endif
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombres <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="nombre" {{ !$canEdit ? 'disabled' : '' }}
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50" />
                    @error('nombre') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Apellidos <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="apellido" {{ !$canEdit ? 'disabled' : '' }}
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50" />
                    @error('apellido') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sexo <span class="text-red-500">*</span></label>
                    <select wire:model="sexo" {{ !$canEdit ? 'disabled' : '' }}
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50">
                        <option value="">Seleccione...</option>
                        <option value="Masculino">Masculino</option>
                        <option value="Femenino">Femenino</option>
                    </select>
                    @error('sexo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Número de Cuenta <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="cuenta" {{ !$canEdit ? 'disabled' : '' }}
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50" />
                    @error('cuenta') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Facultad o Centro <span class="text-red-500">*</span></label>
                    <select wire:model.live="centro_facultad_id" {{ !$canEdit ? 'disabled' : '' }}
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50">
                        <option value="">Seleccione...</option>
                        @foreach ($centros as $id => $nombre)
                            <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                    @error('centro_facultad_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                @if ($centro_facultad_id)
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Carrera <span class="text-red-500">*</span></label>
                    <select wire:model.live="carrera_id" {{ (!$canEdit || $carreras->isEmpty()) ? 'disabled' : '' }}
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50">
                        <option value="">Seleccione...</option>
                        @foreach ($carreras as $id => $nombre)
                            <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                    @if ($carreras->isEmpty())
                        <p class="mt-1 text-xs text-amber-600">La facultad o centro seleccionado no tiene carreras configuradas.</p>
                    @endif
                    @error('carrera_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                @endif
            </div>
        </div>

        @if ($errors->any())
            <div class="rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-800" role="alert">
                <p class="font-semibold">No se pudo finalizar el registro.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach (collect($errors->all())->unique() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($canEdit)
            <button type="submit" wire:loading.attr="disabled" wire:target="save"
                class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60">
                <span wire:loading.remove wire:target="save">Finalizar registro</span>
                <span wire:loading wire:target="save">Guardando...</span>
            </button>
        @endif
    </form>
</div>
