<div>
    @php $canEdit = \App\Support\ProfileCompletion::isRequired(auth()->user()); @endphp

    <form wire:submit="save" class="space-y-6">
        {{-- Datos de Usuario/Empleado --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-6 {{ !$canEdit ? 'opacity-60' : '' }}">
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Perfil de Empleado</p>
            @if (!$canEdit)
                <p class="text-xs text-yellow-600 dark:text-yellow-400 mb-3">No tiene permiso para editar estos datos actualmente.</p>
            @endif
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre de Usuario <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="name" {{ !$canEdit ? 'disabled' : '' }}
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50" />
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Correo Electrónico <span class="text-red-500">*</span></label>
                    <input type="email" wire:model="email" {{ (!$canEdit || auth()->user()->microsoft_id) ? 'disabled' : '' }}
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50" />
                    @if (auth()->user()->microsoft_id)
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Correo verificado por Microsoft.</p>
                    @endif
                    @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre Completo <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="nombre_completo" {{ !$canEdit ? 'disabled' : '' }}
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50" />
                    @error('nombre_completo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">N° de Empleado <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="numero_empleado" {{ !$canEdit ? 'disabled' : '' }}
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50" />
                    @error('numero_empleado') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Celular <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="celular" {{ !$canEdit ? 'disabled' : '' }}
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50" />
                    @error('celular') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
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
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Categoría <span class="text-red-500">*</span></label>
                    <select wire:model="categoria_id" {{ !$canEdit ? 'disabled' : '' }}
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50">
                        <option value="">Seleccione...</option>
                        @foreach ($categorias as $id => $nombre)
                            <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                    @error('categoria_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
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
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Departamento Académico <span class="text-red-500">*</span></label>
                        <select wire:model.live="departamento_academico_id" {{ (!$canEdit || $departamentos->isEmpty()) ? 'disabled' : '' }}
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50">
                            <option value="">Seleccione...</option>
                            @foreach ($departamentos as $id => $nombre)
                                <option value="{{ $id }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                        @if ($departamentos->isEmpty())
                            <p class="mt-1 text-xs text-amber-600">La facultad o centro seleccionado no tiene departamentos académicos configurados.</p>
                        @endif
                        @error('departamento_academico_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                @endif
                @if ($departamento_academico_id)
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Carrera <span class="text-red-500">*</span></label>
                        <select wire:model.live="carrera_id" {{ (!$canEdit || $carreras->isEmpty()) ? 'disabled' : '' }}
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50">
                            <option value="">Seleccione...</option>
                            @foreach ($carreras as $id => $nombre)
                                <option value="{{ $id }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                        @if ($carreras->isEmpty())
                            <p class="mt-1 text-xs text-amber-600">El departamento seleccionado no tiene carreras configuradas.</p>
                        @endif
                        @error('carrera_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                @endif
            </div>
        </div>

    {{-- Firma --}}
    <div class="mt-6 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Firma (Requerido)</p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Visualizar o agregar una nueva Firma.</p>
        @if ($firma)
            <div class="mb-3">
                <img src="{{ Storage::url($firma->ruta_storage) }}" alt="Firma" class="max-h-24 border border-gray-200 rounded" />
            </div>
        @endif
        @if ($canEdit)
            <div class="flex items-center gap-3">
                <input type="file" wire:model="firmaUpload" accept="image/*" class="text-sm text-gray-600 dark:text-gray-400" />
                <button wire:click="subirFirma" type="button"
                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                    Subir Firma
                </button>
            </div>
        @endif
        @error('firmaUpload') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Sello --}}
    <div class="mt-6 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Sello (Requerido)</p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Visualizar o agregar un nuevo Sello.</p>
        @if ($sello)
            <div class="mb-3">
                <img src="{{ Storage::url($sello->ruta_storage) }}" alt="Sello" class="max-h-24 border border-gray-200 rounded" />
            </div>
        @endif
        @if ($canEdit)
            <div class="flex items-center gap-3">
                <input type="file" wire:model="selloUpload" accept="image/*" class="text-sm text-gray-600 dark:text-gray-400" />
                <button wire:click="subirSello" type="button"
                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                    Subir Sello
                </button>
            </div>
        @endif
        @error('selloUpload') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Códigos de Investigación (solo docentes) --}}
    @if (auth()->user()->empleado?->tipo_empleado === 'docente')
        <div class="mt-6 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    ¿Participó en proyectos de vinculación previos a este sistema? <span class="text-red-500">*</span>
                </label>
                <select wire:model.live="tiene_proyectos_previos" {{ !$canEdit ? 'disabled' : '' }}
                    class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50">
                    <option value="">Seleccione...</option>
                    <option value="si">Sí</option>
                    <option value="no">No</option>
                </select>
                @error('tiene_proyectos_previos') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            @if ($tiene_proyectos_previos === 'si')
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Registro de proyectos de vinculación previos al sistema</p>
                </div>
                @if ($canEdit)
                    <button wire:click="openAddCodigo" type="button"
                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                        Agregar Proyecto
                    </button>
                @endif
            </div>
            @forelse ($codigos as $codigo)
                @php
                    $badgeClass = match($codigo->estado_verificacion) {
                        'pendiente'  => 'bg-yellow-100 text-yellow-800',
                        'verificado' => 'bg-green-100 text-green-800',
                        'rechazado'  => 'bg-red-100 text-red-800',
                        default      => 'bg-gray-100 text-gray-800',
                    };
                    $badgeLabel = match($codigo->estado_verificacion) {
                        'pendiente'  => 'Pendiente de verificación',
                        'verificado' => 'Verificado',
                        'rechazado'  => 'Rechazado',
                        default      => 'Estado desconocido',
                    };
                @endphp
                <div class="mb-4 p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $codigo->codigo_proyecto }}</p>
                            <p class="text-sm text-gray-500">Año: {{ $codigo->año }} | Registrado: {{ $codigo->created_at->format('d/m/Y') }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $badgeClass }}">{{ $badgeLabel }}</span>
                            @if ($codigo->estado_verificacion === 'pendiente')
                                <button x-on:click.prevent="confirmDialog('¿Eliminar este código?', { type: 'danger' }).then((ok) => ok && $wire.eliminarCodigo({{ $codigo->id }}))" type="button"
                                    class="px-2 py-1 text-xs rounded-md bg-red-600 text-white hover:bg-red-700">
                                    Eliminar
                                </button>
                            @endif
                        </div>
                    </div>
                    <div class="mt-2 text-sm text-gray-700 dark:text-gray-300">
                        @if ($codigo->nombre_proyecto)
                            <p><strong>Nombre:</strong> {{ $codigo->nombre_proyecto }}</p>
                        @endif
                        @if ($codigo->rol_docente)
                            <p><strong>Rol:</strong> {{ $codigo->rol_docente === 'coordinador' ? 'Coordinador' : 'Integrante' }}</p>
                        @endif
                        @if ($codigo->descripcion)
                            <p><strong>Descripción:</strong> {{ $codigo->descripcion }}</p>
                        @endif
                        @if ($codigo->observaciones_admin)
                            <div class="mt-2 p-2 bg-blue-50 border border-blue-200 rounded text-xs">
                                <strong class="text-blue-800">Observaciones admin:</strong><br>
                                <span class="text-blue-700">{{ $codigo->observaciones_admin }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">No hay códigos registrados. Usa el botón "Agregar Proyecto" para registrar tus proyectos previos.</p>
            @endforelse
            @endif
        </div>
    @endif

        @if ($errors->any())
            <div class="mt-6 rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-800" role="alert">
                <p class="font-semibold">No se pudo finalizar el registro.</p>
                <p class="mt-1">Revisa los siguientes datos:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach (collect($errors->all())->unique() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($canEdit)
            <button type="submit" wire:loading.attr="disabled" wire:target="save,firmaUpload,selloUpload"
                class="mt-6 inline-flex items-center px-4 py-2 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60">
                <span wire:loading.remove wire:target="save">Finalizar registro</span>
                <span wire:loading wire:target="save">Guardando...</span>
            </button>
            <p wire:loading wire:target="firmaUpload,selloUpload" class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                Preparando los archivos seleccionados...
            </p>
        @endif
    </form>

    {{-- Modal Agregar Código --}}
    @if ($addCodigoModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-lg mx-4">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Agregar Proyecto</h3>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Código del Proyecto <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="add_codigo" placeholder="Ej: PI-2024-001"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500" />
                        @error('add_codigo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre del Proyecto <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="add_nombre"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500" />
                        @error('add_nombre') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Participación en el Proyecto <span class="text-red-500">*</span></label>
                        <select wire:model="add_rol"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Seleccione el tipo de participación</option>
                            <option value="coordinador">Coordinador</option>
                            <option value="integrante">Integrante</option>
                        </select>
                        @error('add_rol') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descripción (Opcional)</label>
                        <textarea wire:model="add_descripcion" rows="3"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Año del Proyecto <span class="text-red-500">*</span></label>
                        <select wire:model="add_año"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach ($anios as $y)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endforeach
                        </select>
                        @error('add_año') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <button wire:click="$set('addCodigoModal', false)" type="button"
                        class="px-4 py-2 text-sm font-medium rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">
                        Cancelar
                    </button>
                    <button wire:click="agregarCodigo" type="button"
                        class="px-4 py-2 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                        Agregar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
