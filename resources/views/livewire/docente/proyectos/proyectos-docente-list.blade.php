<div>
    @php
        $incluyeProyectos = $filterTipoAccion === 'proyectos';
    @endphp

    <div class="mb-4 flex flex-wrap gap-2 border-b border-gray-200 pb-3 dark:border-gray-700">
        <button type="button"
                wire:click="$set('filterTipoAccion', 'todas')"
                class="inline-flex items-center rounded-md px-3 py-2 text-sm font-semibold transition {{ $filterTipoAccion === 'todas' ? 'bg-blue-700 text-white shadow-sm' : 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-200 dark:ring-gray-700 dark:hover:bg-gray-800' }}">
            Todas
        </button>
        <button type="button"
                wire:click="$set('filterTipoAccion', 'proyectos')"
                class="inline-flex items-center rounded-md px-3 py-2 text-sm font-semibold transition {{ $filterTipoAccion === 'proyectos' ? 'bg-blue-700 text-white shadow-sm' : 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-200 dark:ring-gray-700 dark:hover:bg-gray-800' }}">
            Desarrollo Local y Regional
        </button>
        <button type="button"
                wire:click="$set('filterTipoAccion', 'educacion_no_formal')"
                class="inline-flex items-center rounded-md px-3 py-2 text-sm font-semibold transition {{ $filterTipoAccion === 'educacion_no_formal' ? 'bg-blue-700 text-white shadow-sm' : 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-200 dark:ring-gray-700 dark:hover:bg-gray-800' }}">
            Educación no formal
        </button>
        <button type="button"
                wire:click="$set('filterTipoAccion', 'pps_servicio_social')"
                class="inline-flex items-center rounded-md px-3 py-2 text-sm font-semibold transition {{ $filterTipoAccion === 'pps_servicio_social' ? 'bg-blue-700 text-white shadow-sm' : 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-200 dark:ring-gray-700 dark:hover:bg-gray-800' }}">
            PPS / Servicio Social
        </button>
        <button type="button"
                wire:click="$set('filterTipoAccion', 'voluntariado')"
                class="inline-flex items-center rounded-md px-3 py-2 text-sm font-semibold transition {{ $filterTipoAccion === 'voluntariado' ? 'bg-blue-700 text-white shadow-sm' : 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-200 dark:ring-gray-700 dark:hover:bg-gray-800' }}">
            Voluntariado Académico
        </button>
                <button type="button"
                wire:click="$set('filterTipoAccion', 'seguimiento_a_egresados')"
                class="inline-flex items-center rounded-md px-3 py-2 text-sm font-semibold transition {{ $filterTipoAccion === 'seguimiento_a_egresados' ? 'bg-blue-700 text-white shadow-sm' : 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-200 dark:ring-gray-700 dark:hover:bg-gray-800' }}">
            Seguimiento a egresados
        </button>
                <button type="button"
                wire:click="$set('filterTipoAccion', 'vinculos_academicos')"
                class="inline-flex items-center rounded-md px-3 py-2 text-sm font-semibold transition {{ $filterTipoAccion === 'vinculos_academicos' ? 'bg-blue-700 text-white shadow-sm' : 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-200 dark:ring-gray-700 dark:hover:bg-gray-800' }}">
            Vínculo academicos
        </button>
                <button type="button"
                wire:click="$set('filterTipoAccion', 'cultura_y_comunicacion')"
                class="inline-flex items-center rounded-md px-3 py-2 text-sm font-semibold transition {{ $filterTipoAccion === 'cultura_y_comunicacion' ? 'bg-blue-700 text-white shadow-sm' : 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-200 dark:ring-gray-700 dark:hover:bg-gray-800' }}">
            Cultura y comunicación
        </button>
    </div>

    {{-- Filtros --}}
    <div class="mb-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre, código, cuenta o institución..."
               class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">

        @if ($incluyeProyectos)
        <select wire:model.live="filterRol"
                class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm">
            <option value="">Todos los roles</option>
            <option value="Coordinador">Coordinador</option>
            <option value="Integrante">Integrante</option>
        </select>

        <select wire:model.live="filterCategoria"
                class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm">
            <option value="">Todas las categorías</option>
            @foreach ($categorias as $id => $nombre)
                <option value="{{ $id }}">{{ $nombre }}</option>
            @endforeach
        </select>

        <select wire:model.live="filterEstado"
                class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm">
            <option value="">Todos los estados</option>
            @foreach ($estadosTipo as $id => $nombre)
                <option value="{{ $id }}">{{ $nombre }}</option>
            @endforeach
        </select>
        @endif
    </div>

    {{-- Tabla --}}
    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Código / ID</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Nombre / descripción</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Tipo de acción</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Rol / participación</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Estado</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Fecha</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($records as $row)
                    @php
                        $estado = $row['estado'] ?? '';
                        $estadoBadge = match($estado) {
                            'En curso'    => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                            'Subsanacion' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                            'Borrador'    => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                            'Finalizado'  => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                            'Aprobado', 'APROBADO' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                            'Rechazado' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                            default       => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                        };
                        $esSubsanacion = $estado === 'Subsanacion';
                        $rowClass = $esSubsanacion ? 'bg-red-50 dark:bg-red-950 border-l-4 border-red-500' : '';
                    @endphp
                    <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800 {{ $rowClass }}">
                        <td class="px-4 py-3 text-gray-900 dark:text-white max-w-xs">
                            <span class="font-medium">{{ $row['codigo'] }}</span>
                            @if (!empty($row['secondary_code']))
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $row['secondary_code'] }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-900 dark:text-white max-w-sm">
                            <p class="font-medium">{{ Str::limit($row['nombre'], 70) }}</p>
                            @if (!empty($row['descripcion']))
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ Str::limit($row['descripcion'], 80) }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $row['tipo_accion'] }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $row['rol'] }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $estadoBadge }}">{{ $estado ?: '-' }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                            {{ $row['fecha'] ? \Carbon\Carbon::parse($row['fecha'])->format('d/m/Y') : '-' }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                @if ($row['kind'] === 'proyectos')
                                    @php
                                        $proyecto = $row['record'];
                                        $estadoProyecto = $proyecto->estado?->tipoestado?->nombre ?? '';
                                        $esCoordinador = $proyecto->coordinador?->id === $docente->id;
                                        $puedeSeguirEditando = $esCoordinador && (
                                            in_array($estadoProyecto, ['Borrador', 'Subsanacion', 'Subsanación'], true)
                                            || $proyecto->proyectoIsInEstadoByName('Autoguardado')
                                        );
                                        $puedeBorrar = $esCoordinador && (
                                            in_array($estadoProyecto, ['Autoguardado', 'Borrador', 'Subsanacion', 'Subsanación'], true)
                                        );
                                        $tieneFlujoIntermedio = $proyecto->tieneFlujoInformeIntermedio();
                                        $tieneFlujoCierre = $proyecto->tieneFlujoCierreProyecto();
                                        $documentoIntermedioEstado = $proyecto->documento_intermedio()?->estado?->tipoestado?->nombre;
                                        $documentoFinalEstado = $proyecto->documento_final()?->estado?->tipoestado?->nombre;
                                        $intermedioPendiente = $tieneFlujoIntermedio && $documentoIntermedioEstado !== 'Aprobado';
                                        $puedeSubirIntermedio = $esCoordinador
                                            && $tieneFlujoIntermedio
                                            && $estadoProyecto === 'En curso'
                                            && (is_null($proyecto->documento_intermedio()) || $documentoIntermedioEstado === 'Subsanacion');
                                        $puedeSubirFinal = $esCoordinador
                                            && $tieneFlujoCierre
                                            && $estadoProyecto === 'En curso'
                                            && ((! $intermedioPendiente && is_null($proyecto->documento_final())) || $documentoFinalEstado === 'Subsanacion');
                                    @endphp

                                    <a href="{{ route('historialproyecto', $proyecto) }}"
                                       class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 bg-white text-gray-600 shadow-sm transition hover:bg-gray-50 hover:text-blue-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-blue-300"
                                       title="Ver proyecto"
                                       aria-label="Ver proyecto">
                                        @svg('heroicon-o-eye', ['class' => 'h-4 w-4'])
                                    </a>

                                    @if ($puedeSeguirEditando)
                                        <a href="{{ route('crearProyectoVinculacion', $proyecto) }}"
                                           class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-blue-200 bg-blue-50 text-blue-700 shadow-sm transition hover:bg-blue-100 hover:text-blue-800 dark:border-blue-900/60 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/50"
                                           title="Seguir editando"
                                           aria-label="Seguir editando">
                                            @svg('heroicon-o-pencil-square', ['class' => 'h-4 w-4'])
                                        </a>
                                    @endif

                                    @if ($puedeBorrar)
                                        <button type="button"
                                                wire:click="openDelete({{ $proyecto->id }})"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-red-200 bg-red-50 text-red-700 shadow-sm transition hover:bg-red-100 hover:text-red-800 dark:border-red-900/60 dark:bg-red-900/20 dark:text-red-300 dark:hover:bg-red-900/40"
                                                title="Borrar proyecto"
                                                aria-label="Borrar proyecto">
                                            @svg('heroicon-o-trash', ['class' => 'h-4 w-4'])
                                        </button>
                                    @endif

                                    @if ($puedeSubirIntermedio)
                                        <button type="button"
                                                wire:click="openSubirIntermedio({{ $proyecto->id }})"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-yellow-200 bg-yellow-50 text-yellow-700 shadow-sm transition hover:bg-yellow-100 hover:text-yellow-800 dark:border-yellow-900/60 dark:bg-yellow-900/20 dark:text-yellow-300 dark:hover:bg-yellow-900/40"
                                                title="{{ $documentoIntermedioEstado === 'Subsanacion' ? 'Subsanar informe intermedio' : 'Subir informe intermedio' }}"
                                                aria-label="{{ $documentoIntermedioEstado === 'Subsanacion' ? 'Subsanar informe intermedio' : 'Subir informe intermedio' }}">
                                            @svg('heroicon-o-arrow-up-tray', ['class' => 'h-4 w-4'])
                                        </button>
                                    @endif

                                    @if ($puedeSubirFinal)
                                        <button type="button"
                                                wire:click="openSubirFinal({{ $proyecto->id }})"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-emerald-200 bg-emerald-50 text-emerald-700 shadow-sm transition hover:bg-emerald-100 hover:text-emerald-800 dark:border-emerald-900/60 dark:bg-emerald-900/20 dark:text-emerald-300 dark:hover:bg-emerald-900/40"
                                                title="{{ $documentoFinalEstado === 'Subsanacion' ? 'Subsanar informe final' : 'Subir informe final' }}"
                                                aria-label="{{ $documentoFinalEstado === 'Subsanacion' ? 'Subsanar informe final' : 'Subir informe final' }}">
                                            @svg('heroicon-o-arrow-up-tray', ['class' => 'h-4 w-4'])
                                        </button>
                                    @endif
                                @elseif ($row['kind'] === 'pps_servicio_social')
                                    @php
                                        $registro = $row['record'];
                                        $puedeEditarPps = $registro->estado === 'borrador'
                                            && $registro->created_by !== null
                                            && auth()->id() !== null
                                            && (int) $registro->created_by === (int) auth()->id();
                                    @endphp

                                    <a href="{{ route('pps-servicio-social.show', $registro->id) }}" wire:navigate
                                       class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 bg-white text-gray-600 shadow-sm transition hover:bg-gray-50 hover:text-blue-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-blue-300"
                                       title="Ver detalle PPS/SS"
                                       aria-label="Ver detalle PPS/SS">
                                        @svg('heroicon-o-eye', ['class' => 'h-4 w-4'])
                                    </a>

                                    @if ($puedeEditarPps)
                                        <a href="{{ route('pps-servicio-social.edit', $registro->id) }}" wire:navigate
                                           class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-blue-200 bg-blue-50 text-blue-700 shadow-sm transition hover:bg-blue-100 hover:text-blue-800 dark:border-blue-900/60 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/50"
                                           title="Editar PPS/SS"
                                           aria-label="Editar PPS/SS">
                                            @svg('heroicon-o-pencil-square', ['class' => 'h-4 w-4'])
                                        </a>
                                    @endif
                                @else
                                    @php
                                        $accionEnf = $row['record'];
                                        $estadoEnf = strtoupper(str_replace(' ', '_', $accionEnf->estado_flujo ?? ''));
                                        $esCreadorEnf = auth()->id() !== null && (int) $accionEnf->creado_por_usuario_id === (int) auth()->id();
                                        $puedeEditarEnf = $esCreadorEnf && in_array($estadoEnf, ['BORRADOR', 'SUBSANACION', 'SUBSANACIÓN'], true);
                                    @endphp

                                    <a href="{{ route('enf.acciones.show', $accionEnf->id) }}"
                                       class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 bg-white text-gray-600 shadow-sm transition hover:bg-gray-50 hover:text-blue-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-blue-300"
                                       title="Ver detalle ENF"
                                       aria-label="Ver detalle ENF">
                                        @svg('heroicon-o-eye', ['class' => 'h-4 w-4'])
                                    </a>

                                    @if ($puedeEditarEnf)
                                        <a href="{{ route('enf.acciones.edit', $accionEnf->id) }}"
                                           class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-blue-200 bg-blue-50 text-blue-700 shadow-sm transition hover:bg-blue-100 hover:text-blue-800 dark:border-blue-900/60 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/50"
                                           title="Editar ENF"
                                           aria-label="Editar ENF">
                                            @svg('heroicon-o-pencil-square', ['class' => 'h-4 w-4'])
                                        </a>

                                        <button type="button"
                                                wire:click="openDeleteEnf({{ $accionEnf->id }})"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-red-200 bg-red-50 text-red-700 shadow-sm transition hover:bg-red-100 hover:text-red-800 dark:border-red-900/60 dark:bg-red-900/20 dark:text-red-300 dark:hover:bg-red-900/40"
                                                title="Borrar ENF"
                                                aria-label="Borrar ENF">
                                            @svg('heroicon-o-trash', ['class' => 'h-4 w-4'])
                                        </button>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No hay registros para los filtros seleccionados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $records->links() }}</div>

    {{-- Modal Subir Informe Intermedio --}}
    @if ($informeIntermedioModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-lg">
            <div class="flex justify-between items-center p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold dark:text-white">Subir Informe Intermedio</h3>
                <button wire:click="$set('informeIntermedioModal', false)" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
            </div>
            <div class="p-4 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Archivo PDF</label>
                    <input type="file" wire:model="informeIntermedioFile" accept=".pdf"
                           class="w-full text-sm text-gray-700 dark:text-gray-300 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    @error('informeIntermedioFile') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    <div wire:loading wire:target="informeIntermedioFile" class="mt-1 text-sm text-gray-500">Cargando archivo...</div>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button wire:click="$set('informeIntermedioModal', false)"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button wire:click="subirInformeIntermedio()" wire:loading.attr="disabled" wire:target="subirInformeIntermedio"
                            class="px-4 py-2 text-sm font-medium text-white bg-yellow-500 hover:bg-yellow-600 rounded-lg disabled:opacity-50">
                        <span wire:loading.remove wire:target="subirInformeIntermedio">Subir</span>
                        <span wire:loading wire:target="subirInformeIntermedio">Subiendo...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal Subir Informe Final --}}
    @if ($informeFinalModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-lg">
            <div class="flex justify-between items-center p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold dark:text-white">Subir Informe Final</h3>
                <button wire:click="$set('informeFinalModal', false)" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
            </div>
            <div class="p-4 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Archivo PDF</label>
                    <input type="file" wire:model="informeFinalFile" accept=".pdf"
                           class="w-full text-sm text-gray-700 dark:text-gray-300 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    @error('informeFinalFile') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    <div wire:loading wire:target="informeFinalFile" class="mt-1 text-sm text-gray-500">Cargando archivo...</div>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button wire:click="$set('informeFinalModal', false)"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button wire:click="subirInformeFinal()" wire:loading.attr="disabled" wire:target="subirInformeFinal"
                            class="px-4 py-2 text-sm font-medium text-white bg-sky-600 hover:bg-sky-700 rounded-lg disabled:opacity-50">
                        <span wire:loading.remove wire:target="subirInformeFinal">Subir</span>
                        <span wire:loading wire:target="subirInformeFinal">Subiendo...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal Confirmar Borrado --}}
    @if ($deleteModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md">
            <div class="flex justify-between items-center p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold dark:text-white">Confirmar borrado del proyecto</h3>
                <button wire:click="$set('deleteModal', false)" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
            </div>
            <div class="p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Esta acción moverá el proyecto a eliminado (borrado suave). Podrás recuperarlo desde base de datos si fuese necesario.</p>
                <div class="flex justify-end gap-3 mt-4">
                    <button wire:click="$set('deleteModal', false)"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button wire:click="deleteProyecto()"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg">
                        Sí, borrar proyecto
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if ($deleteEnfModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md">
            <div class="flex justify-between items-center p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold dark:text-white">Confirmar borrado de ENF</h3>
                <button wire:click="$set('deleteEnfModal', false)" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
            </div>
            <div class="p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Esta acción moverá el registro de Educación No Formal a eliminado.</p>
                <div class="flex justify-end gap-3 mt-4">
                    <button wire:click="$set('deleteEnfModal', false)"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button wire:click="deleteEnfAccion()"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg">
                        Sí, borrar ENF
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
