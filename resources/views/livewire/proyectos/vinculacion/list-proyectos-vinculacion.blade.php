<div>
    <div class="mb-4">
        <p class="text-zinc-950 dark:text-white font-bold mb-1">Historial de Vinculación</p>
        <p class="text-zinc-500 dark:text-gray-400 font-medium text-sm">Listado de proyectos y registros de educación no formal creados en el sistema.</p>
    </div>

    {{-- Barra de búsqueda --}}
    <div class="mb-3 flex gap-3">
        <input type="text" wire:model.live.debounce.300ms="search"
               placeholder="Buscar por nombre, código o dictamen..."
               class="flex-1 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm">
        <button type="button" wire:click="exportExcel"
                class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md bg-emerald-600 text-white hover:bg-emerald-700">
            Exportar Excel
        </button>
    </div>

    {{-- Panel de filtros --}}
    <div x-data="{ open: false }" class="mb-4">
        <button @click="open = !open" type="button"
                class="flex items-center gap-2 px-3 py-1.5 text-sm font-medium rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
            </svg>
            Filtros
            @php $activeFilters = collect([$filterEstado,$filterCategoria,$filterModalidad,$filterOds,$filterFechaInicio,$filterFechaFin,$filterCentroFacultad,$filterDepartamento])->filter()->count(); @endphp
            @if($activeFilters > 0)
                <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-orange-500 rounded-full">{{ $activeFilters }}</span>
            @endif
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div x-show="open" x-cloak x-transition class="mt-2 p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">

                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Estado</label>
                    <select wire:model.live="filterEstado" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white">
                        <option value="">Todos los estados</option>
                        @foreach ($estadosTipo as $id => $nombre)
                            <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Categoría</label>
                    <select wire:model.live="filterCategoria" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white">
                        <option value="">Todas las categorías</option>
                        @foreach ($categorias as $id => $nombre)
                            <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Modalidad</label>
                    <select wire:model.live="filterModalidad" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white">
                        <option value="">Todas las modalidades</option>
                        @foreach ($modalidades as $id => $nombre)
                            <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">ODS</label>
                    <select wire:model.live="filterOds" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white">
                        <option value="">Todos los ODS</option>
                        @foreach ($odsList as $id => $nombre)
                            <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Centro/Facultad</label>
                    <select wire:model.live="filterCentroFacultad" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white">
                        <option value="">Todos los centros</option>
                        @foreach ($centros as $id => $nombre)
                            <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>

                @if ($filterCentroFacultad && $departamentos->count())
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Departamento</label>
                    <select wire:model.live="filterDepartamento" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white">
                        <option value="">Todos los departamentos</option>
                        @foreach ($departamentos as $id => $nombre)
                            <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Fecha Inicio desde</label>
                    <input type="date" wire:model.live="filterFechaInicio"
                           class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Fecha Fin hasta</label>
                    <input type="date" wire:model.live="filterFechaFin"
                           class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white">
                </div>

            </div>
            @if($activeFilters > 0)
            <div class="mt-3 flex justify-end">
                <button wire:click="$set('filterEstado',''); $set('filterCategoria',''); $set('filterModalidad',''); $set('filterOds',''); $set('filterFechaInicio',''); $set('filterFechaFin',''); $set('filterCentroFacultad', null); $set('filterDepartamento', null);"
                        type="button"
                        class="text-xs text-red-600 hover:text-red-800 dark:text-red-400 font-medium">
                    Limpiar filtros
                </button>
            </div>
            @endif
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Código</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">N° Dictamen / Registro</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Nombre</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Estado</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Fecha Inicio</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($records as $row)
                    @php
                        $estado = $row['estado'] ?? '';
                        $record = $row['record'];
                        $isEnf = ($row['kind'] ?? null) === 'enf';
                        $badge = match($estado) {
                            'En curso' => 'bg-green-100 text-green-800',
                            'Subsanacion', 'SUBSANACION' => 'bg-red-100 text-red-800',
                            'Borrador', 'BORRADOR' => 'bg-yellow-100 text-yellow-800',
                            'Finalizado' => 'bg-blue-100 text-blue-800',
                            'APROBADO', 'Aprobado' => 'bg-green-100 text-green-800',
                            'EN REVISION', 'EN_REVISION' => 'bg-blue-100 text-blue-800',
                            default => 'bg-gray-100 text-gray-800',
                        };
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $row['codigo'] ?: '-' }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $row['secondary_code'] ?: '-' }}</td>
                        <td class="px-4 py-3 text-gray-900 dark:text-white max-w-xs">
                            <a href="{{ $isEnf ? route('enf.acciones.show', $record) : route('historialproyecto', $record) }}" class="hover:underline text-blue-600 dark:text-blue-400">
                                {{ Str::limit($row['nombre'], 50) }}
                            </a>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $row['tipo'] }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $badge }}">{{ $estado ?: '-' }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $row['fecha'] ? \Carbon\Carbon::parse($row['fecha'])->format('d/m/Y') : '-' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex gap-2">
                                @if($isEnf && ($record->codigo_formulario ?? null) === 'FORM-DVUS-018')
                                    <a href="{{ route('enf.acciones.pdf.ver', $record) }}" target="_blank" rel="noopener"
                                       class="px-3 py-1.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg">Ver</a>
                                    <a href="{{ route('enf.acciones.pdf', $record) }}"
                                       class="px-3 py-1.5 text-sm font-medium text-white bg-sky-600 hover:bg-sky-700 rounded-lg">Descargar</a>
                                    <a href="{{ route('enf.acciones.show', $record) }}"
                                       class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg">Detalle</a>
                                @else
                                    <a href="{{ $isEnf ? route('enf.acciones.show', $record) : route('historialproyecto', $record) }}"
                                       class="px-3 py-1.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg">Ver</a>
                                @endif

                                @if($isEnf)
                                    @php
                                        $estadoEnf = strtoupper(str_replace(' ', '_', $record->estado_flujo ?? ''));
                                        $puedeEditarEnf = auth()->id() !== null
                                            && (int) $record->creado_por_usuario_id === (int) auth()->id()
                                            && in_array($estadoEnf, ['BORRADOR', 'SUBSANACION', 'SUBSANACIÓN'], true);
                                    @endphp

                                    @if($puedeEditarEnf)
                                        <a href="{{ route('enf.acciones.edit', $record) }}"
                                           class="px-3 py-1.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg">Editar</a>
                                    @endif
                                @elseif(auth()->user()->hasRole(['admin', 'Director/Enlace']))
                                    @if(!($row['flujo_adoptado'] ?? false) && !($row['flujo_iniciado'] ?? false))
                                        <button wire:click="openFirmas({{ $record->id }})"
                                                class="px-3 py-1.5 text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 rounded-lg">Firmas</button>
                                    @endif
                                    <button wire:click="openFlowModal({{ $record->id }})"
                                            class="px-3 py-1.5 text-sm font-medium text-white bg-teal-600 hover:bg-teal-700 rounded-lg">
                                        @if($row['flujo_adoptado'] ?? false)
                                            Ver adopción
                                        @elseif($row['flujo_iniciado'] ?? false)
                                            Flujo activo
                                        @else
                                            Adaptar flujo
                                        @endif
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No se encontraron proyectos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $records->links() }}</div>

    {{-- Modal Ver Proyecto --}}
    @if ($viewModal && $viewProyecto)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-7xl max-h-[90vh] flex flex-col">
            <div class="flex justify-between items-center p-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
                <h3 class="text-lg font-semibold dark:text-white">{{ $viewProyecto->nombre_proyecto }}</h3>
                <button wire:click="$set('viewModal', false)" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
            </div>
            <div class="flex-1 overflow-y-auto p-4">
                @include('components.fichas.ficha-proyecto-vinculacion', ['proyecto' => $viewProyecto])
            </div>
            <div class="flex justify-end p-4 border-t border-gray-200 dark:border-gray-700 flex-shrink-0">
                <button wire:click="$set('viewModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cerrar</button>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal Editar Firmas --}}
    @if ($firmasModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-xl">
            <div class="flex justify-between items-center p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold dark:text-white">Reasignar Jefes y Directores</h3>
                <button wire:click="$set('firmasModal', false)" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
            </div>
            <div class="p-4 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Jefe de Departamento <span class="text-red-500">*</span></label>
                    <select wire:model="firmas_jefe_id"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm">
                        <option value="">Seleccione...</option>
                        @foreach ($empleados as $id => $nombre)
                            <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                    @error('firmas_jefe_id') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Decano/Director de Centro <span class="text-red-500">*</span></label>
                    <select wire:model="firmas_director_id"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm">
                        <option value="">Seleccione...</option>
                        @foreach ($empleados as $id => $nombre)
                            <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                    @error('firmas_director_id') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Enlace de Vinculación <span class="text-red-500">*</span></label>
                    <select wire:model="firmas_enlace_id"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm">
                        <option value="">Seleccione...</option>
                        @foreach ($empleados as $id => $nombre)
                            <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                    @error('firmas_enlace_id') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button wire:click="$set('firmasModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancelar</button>
                    <button wire:click="saveFirmas()" class="px-4 py-2 text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 rounded-lg">Guardar Firmas</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal Configurar Flujo --}}
    @if ($flowModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-4xl max-h-[92vh] flex flex-col">
            <div class="flex justify-between items-center p-4 border-b border-gray-200 dark:border-gray-700">
                <div>
                    <h3 class="text-lg font-semibold dark:text-white">
                        {{ $flowIsLegacyAdoption ? 'Adaptar proyecto legacy al flujo' : 'Flujo del proyecto' }}
                    </h3>
                    @if($flowIsLegacyAdoption)
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Puente único de adopción: conserva el historial y continúa desde el punto real.
                        </p>
                    @endif
                </div>
                <button wire:click="$set('flowModal', false)" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
            </div>
            <div class="p-5 space-y-5 overflow-y-auto">
                @if(!empty($flowExistingAdoption))
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-200">
                        <p class="font-semibold">Este proyecto ya fue adoptado.</p>
                        <dl class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-3">
                            <div><dt class="text-xs opacity-70">Situación capturada</dt><dd>{{ str_replace('_', ' ', $flowExistingAdoption['modo']) }}</dd></div>
                            <div><dt class="text-xs opacity-70">Estado de origen</dt><dd>{{ $flowExistingAdoption['estado_origen'] ?: 'Sin estado' }}</dd></div>
                            <div><dt class="text-xs opacity-70">Fecha de adopción</dt><dd>{{ $flowExistingAdoption['adoptado_en'] }}</dd></div>
                        </dl>
                        @if($flowExistingAdoption['orden_inicio'])
                            <p class="mt-2">El ciclo configurable comenzó en la etapa de orden {{ $flowExistingAdoption['orden_inicio'] }}.</p>
                        @else
                            <p class="mt-2">No se creó un ciclo artificial; el flujo quedó fijado para la continuidad normal.</p>
                        @endif
                    </div>
                @elseif($flowHasStarted)
                    <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900 dark:border-blue-800 dark:bg-blue-950/30 dark:text-blue-200">
                        El proyecto ya tiene un ciclo configurable. Su flujo está bloqueado para proteger las revisiones y firmas existentes.
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Flujo de aprobación</label>
                    <select wire:model.live="flowSelectedId"
                            @disabled(!empty($flowExistingAdoption) || $flowHasStarted)
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm">
                        <option value="">Seleccione...</option>
                        @foreach ($flujos as $flujo)
                            <option value="{{ $flujo->id }}">{{ $flujo->nombre }}</option>
                        @endforeach
                    </select>
                    @error('flowSelectedId') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                @if($flowIsLegacyAdoption && !empty($flowDiagnosis))
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950/20">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-300">Diagnóstico legacy</p>
                                <p class="mt-1 font-semibold text-gray-900 dark:text-white">Estado actual: {{ $flowDiagnosis['estado'] ?? 'Sin estado' }}</p>
                            </div>
                            <div class="flex gap-2 text-xs">
                                <span class="rounded-full bg-white px-2.5 py-1 text-gray-600 shadow-sm dark:bg-gray-800 dark:text-gray-300">
                                    {{ $flowDiagnosis['legacy_pendientes'] ?? 0 }} firmas pendientes legacy
                                </span>
                                <span class="rounded-full bg-white px-2.5 py-1 text-gray-600 shadow-sm dark:bg-gray-800 dark:text-gray-300">
                                    {{ $flowDiagnosis['legacy_rechazadas'] ?? 0 }} rechazadas legacy
                                </span>
                            </div>
                        </div>
                        <p class="mt-2 text-sm text-amber-800 dark:text-amber-200">{{ $flowDiagnosis['razon_etapa'] ?? '' }}</p>
                    </div>

                    @php
                        $etapaDetectada = collect($flowDiagnosis['etapas'] ?? [])->first(
                            fn($etapa) => (int) ($etapa['id'] ?? 0) === (int) $flowStartStageId
                        );
                    @endphp

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Situación detectada</label>
                            <div class="flex min-h-[42px] items-center justify-between gap-3 rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-900 shadow-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                                <span>{{ $flowModes[$flowAdoptionMode] ?? 'No determinada' }}</span>
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700 dark:text-emerald-300">
                                    <span class="material-symbols-outlined text-[17px]">verified</span>
                                    Automática
                                </span>
                            </div>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Se obtiene del estado actual del expediente y no puede modificarse.</p>
                        </div>

                        @if(in_array($flowAdoptionMode, [
                            \App\Services\Proyecto\ProyectoLegacyWorkflowAdoptionService::MODO_EN_REVISION,
                            \App\Services\Proyecto\ProyectoLegacyWorkflowAdoptionService::MODO_SUBSANACION,
                        ], true))
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Etapa actual / etapa de retorno detectada</label>
                                <div class="flex min-h-[42px] items-center justify-between gap-3 rounded-md border px-3 py-2 text-sm shadow-sm {{ $etapaDetectada ? 'border-gray-300 bg-gray-50 text-gray-900 dark:border-gray-600 dark:bg-gray-900 dark:text-white' : 'border-red-300 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-950/30 dark:text-red-200' }}">
                                    <span>
                                        @if($etapaDetectada)
                                            {{ $etapaDetectada['orden'].'. '.$etapaDetectada['nombre'] }}
                                        @elseif(!empty($flowDiagnosis['cargo_estado_actual']))
                                            {{ $flowDiagnosis['cargo_estado_actual'] }} — falta configurarla en el flujo
                                        @else
                                            No fue posible determinar una etapa única
                                        @endif
                                    </span>
                                    <span class="material-symbols-outlined shrink-0 text-[18px]">{{ $etapaDetectada ? 'lock' : 'error' }}</span>
                                </div>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Se calcula con el estado, las firmas y el historial legacy; no admite selección manual.</p>
                            </div>
                        @endif
                    </div>

                    @if($flowAdoptionMode === \App\Services\Proyecto\ProyectoLegacyWorkflowAdoptionService::MODO_SUBSANACION)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Motivo histórico de la subsanación</label>
                            <textarea wire:model="flowSubsanacionReason" rows="3"
                                      placeholder="Copie el motivo registrado en el trámite legacy."
                                      class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm"></textarea>
                            @error('flowSubsanacionReason') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    @php
                        $etapasAnteriores = collect($flowDiagnosis['etapas'] ?? [])->filter(fn($etapa) => $etapa['es_anterior'] ?? false);
                        if ($flowAdoptionMode === \App\Services\Proyecto\ProyectoLegacyWorkflowAdoptionService::MODO_COMPLETADO) {
                            $etapasAnteriores = collect($flowDiagnosis['etapas'] ?? []);
                        }
                        $etapasRecorrido = collect($flowDiagnosis['etapas'] ?? [])->filter(fn($etapa) => $etapa['en_nuevo_recorrido'] ?? false);
                    @endphp

                    @if($etapasAnteriores->isNotEmpty())
                        <div class="rounded-lg border border-sky-200 bg-sky-50 p-4 dark:border-sky-800 dark:bg-sky-950/20">
                            <p class="text-sm font-semibold text-sky-900 dark:text-sky-200">Completadas antes de la adopción</p>
                            <p class="mt-1 text-xs text-sky-700 dark:text-sky-300">Se mostrarán como antecedente, sin crear aprobaciones ni firmas ficticias.</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach($etapasAnteriores as $etapa)
                                    <span class="rounded-full border border-sky-200 bg-white px-3 py-1 text-xs text-sky-800 dark:border-sky-700 dark:bg-gray-800 dark:text-sky-200">
                                        {{ $etapa['orden'] }}. {{ $etapa['nombre'] }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($etapasRecorrido->isNotEmpty())
                        <div>
                            <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Responsables del recorrido que continúa</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">El sistema conserva al revisor legacy cuando sigue siendo elegible; los demás deben confirmarse.</p>
                                </div>
                                <button type="button"
                                        wire:click="refreshFlowReviewerCandidates"
                                        wire:loading.attr="disabled"
                                        wire:target="refreshFlowReviewerCandidates"
                                        class="inline-flex shrink-0 items-center justify-center gap-2 rounded-md border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-60 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                    <span class="material-symbols-outlined text-[17px]">refresh</span>
                                    Actualizar usuarios
                                </button>
                            </div>
                            <div class="space-y-3">
                                @foreach($etapasRecorrido as $etapa)
                                    <div wire:key="legacy-stage-{{ $etapa['id'] }}" class="grid grid-cols-1 gap-3 rounded-lg border border-gray-200 p-3 dark:border-gray-700 md:grid-cols-[minmax(0,1fr)_minmax(260px,1fr)] md:items-center">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                                {{ $etapa['orden'] }}. {{ $etapa['nombre'] }}
                                                @if($etapa['es_inicio'])
                                                    <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-amber-700">Punto actual</span>
                                                @endif
                                            </p>
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                Rol: {{ $etapa['rol'] ?: 'sin rol específico' }} · Estado: {{ $etapa['estado'] ?: 'sin estado configurado' }}
                                            </p>
                                        </div>
                                        <div>
                                            <x-forms.searchable-user-select
                                                :model="'flowReviewers.'.$etapa['id']"
                                                :options="$etapa['candidatos']"
                                                :selected="$flowReviewers[$etapa['id']] ?? null"
                                                placeholder="Buscar y seleccionar revisor..."
                                                :wire-key="'legacy-reviewer-'.$etapa['id'].'-'.md5(json_encode([$etapa['candidatos'], $flowReviewers[$etapa['id']] ?? null]))"
                                            />
                                            @if(!empty($etapa['candidatos']))
                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                    {{ count($etapa['candidatos']) }} {{ count($etapa['candidatos']) === 1 ? 'usuario elegible' : 'usuarios elegibles' }}. Puede buscar por nombre o correo.
                                                </p>
                                            @else
                                                <div class="mt-2 rounded-md border border-amber-200 bg-amber-50 px-3 py-2.5 text-xs text-amber-800 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-200">
                                                    <p class="font-semibold">No hay usuarios disponibles para esta etapa.</p>
                                                    <p class="mt-1">
                                                        Asigne el rol <span class="font-semibold">{{ $etapa['rol'] ?: 'requerido por la etapa' }}</span>
                                                        a una cuenta con empleado activo y correo válido; luego presione “Actualizar usuarios”.
                                                    </p>
                                                    @can('usuarios.usuarios')
                                                        <a href="{{ route('Usuarios') }}" target="_blank" rel="noopener"
                                                           class="mt-2 inline-flex items-center gap-1 font-semibold text-amber-900 underline underline-offset-2 dark:text-amber-100">
                                                            Administrar usuarios y roles
                                                            <span class="material-symbols-outlined text-[15px]">open_in_new</span>
                                                        </a>
                                                    @endcan
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @error('flowReviewers') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    @if(!empty($flowDiagnosis['bloqueos']))
                        <div class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-950/20">
                            <p class="text-sm font-semibold text-red-800 dark:text-red-200">La adopción está bloqueada</p>
                            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-700 dark:text-red-300">
                                @foreach($flowDiagnosis['bloqueos'] as $bloqueo)
                                    <li>{{ $bloqueo }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="rounded-lg bg-gray-50 p-4 text-sm text-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        @if($flowAdoptionMode === \App\Services\Proyecto\ProyectoLegacyWorkflowAdoptionService::MODO_EN_REVISION)
                            Se creará el ciclo 1 desde la etapa detectada automáticamente. Solo esa etapa aparecerá en la bandeja del revisor actual y recibirá una notificación.
                        @elseif($flowAdoptionMode === \App\Services\Proyecto\ProyectoLegacyWorkflowAdoptionService::MODO_SUBSANACION)
                            La etapa detectada quedará como rechazada. Cuando el coordinador subsane y reenvíe, volverá a ese revisor y luego continuará con las etapas posteriores.
                        @elseif($flowAdoptionMode === \App\Services\Proyecto\ProyectoLegacyWorkflowAdoptionService::MODO_COMPLETADO)
                            No se creará ninguna tarea. Todas las etapas se registrarán únicamente como completadas antes de la adopción.
                        @else
                            No se creará ninguna tarea ahora. Al enviar el proyecto, el flujo normal comenzará desde su primera etapa.
                        @endif
                    </div>
                @endif

                <div class="flex justify-end gap-3 pt-2">
                    <button wire:click="$set('flowModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancelar</button>
                    @if(empty($flowExistingAdoption) && !$flowHasStarted)
                        <button wire:click="saveFlow"
                                wire:loading.attr="disabled"
                                wire:target="saveFlow"
                                @disabled($flowIsLegacyAdoption && !empty($flowDiagnosis['bloqueos'] ?? []))
                                class="px-4 py-2 text-sm font-medium text-white bg-teal-600 hover:bg-teal-700 disabled:cursor-not-allowed disabled:opacity-50 rounded-lg">
                            {{ $flowIsLegacyAdoption ? 'Confirmar adopción' : 'Guardar flujo' }}
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
