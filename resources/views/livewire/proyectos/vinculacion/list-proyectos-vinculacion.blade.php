<div>
    <div class="mb-4">
        <p class="text-zinc-950 dark:text-white font-bold mb-1">Proyectos de Vinculación</p>
        <p class="text-zinc-500 dark:text-gray-400 font-medium text-sm">Listado de todos los proyectos registrados en el sistema.</p>
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
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">N° Dictamen</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Nombre del Proyecto</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Estado</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Fecha Inicio</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($records as $proyecto)
                    @php $estado = $proyecto->estado?->tipoestado?->nombre ?? ''; @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $proyecto->codigo_proyecto ?: '-' }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $proyecto->numero_dictamen ?: '-' }}</td>
                        <td class="px-4 py-3 text-gray-900 dark:text-white max-w-xs">
                            <a href="{{ route('historialproyecto', $proyecto) }}" class="hover:underline text-blue-600 dark:text-blue-400">
                                {{ Str::limit($proyecto->nombre_proyecto, 50) }}
                            </a>
                        </td>
                        <td class="px-4 py-3">
                            @php $badge = match($estado) {
                                'En curso' => 'bg-green-100 text-green-800',
                                'Subsanacion' => 'bg-red-100 text-red-800',
                                'Borrador' => 'bg-yellow-100 text-yellow-800',
                                'Finalizado' => 'bg-blue-100 text-blue-800',
                                default => 'bg-gray-100 text-gray-800',
                            }; @endphp
                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $badge }}">{{ $estado ?: '-' }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $proyecto->fecha_inicio?->format('d/m/Y') ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex gap-2">
                                <a href="{{ route('historialproyecto', $proyecto) }}"
                                   class="px-3 py-1.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg">Ver</a>
                                @if(auth()->user()->hasRole(['admin', 'Director/Enlace']))
                                <button wire:click="openFirmas({{ $proyecto->id }})"
                                        class="px-3 py-1.5 text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 rounded-lg">Firmas</button>
                                <button wire:click="openFlowModal({{ $proyecto->id }})"
                                        class="px-3 py-1.5 text-sm font-medium text-white bg-teal-600 hover:bg-teal-700 rounded-lg">Flujo</button>
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
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-xl">
            <div class="flex justify-between items-center p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold dark:text-white">Asignar flujo al proyecto</h3>
                <button wire:click="$set('flowModal', false)" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
            </div>
            <div class="p-4 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Flujo de aprobacion</label>
                    <select wire:model="flowSelectedId"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm">
                        <option value="">Seleccione...</option>
                        @foreach ($flujos as $flujo)
                            <option value="{{ $flujo->id }}">{{ $flujo->nombre }}</option>
                        @endforeach
                    </select>
                    @error('flowSelectedId') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button wire:click="$set('flowModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancelar</button>
                    <button wire:click="saveFlow" class="px-4 py-2 text-sm font-medium text-white bg-teal-600 hover:bg-teal-700 rounded-lg">Guardar flujo</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
