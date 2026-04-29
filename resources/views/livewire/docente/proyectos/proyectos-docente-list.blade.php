<div>
    <div class="w-full flex justify-between items-center mb-4">
        <div>
            <p class="text-zinc-950 dark:text-white font-bold mb-1">Proyectos de vinculación.</p>
            <p class="text-zinc-500 dark:text-gray-400 font-medium text-sm mt-0">Listado de proyectos de vinculación en los que participa:</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('crearProyectoVinculacion') }}"
               class="inline-flex items-center gap-1 px-3 py-2 text-sm font-medium text-white bg-sky-600 hover:bg-sky-700 rounded-lg">
                Nuevo
            </a>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="mb-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre, código o dictamen..."
               class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">

        <select wire:model.live="filterRol"
                class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm">
            <option value="">Todos los roles</option>
            <option value="Coordinador">Coordinador</option>
            <option value="Integrante">Integrante</option>
        </select>

        <select wire:model.live="filterCategoria" multiple
                class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm h-10">
            <option value="">Todas las categorías</option>
            @foreach ($categorias as $id => $nombre)
                <option value="{{ $id }}">{{ $nombre }}</option>
            @endforeach
        </select>

        <select wire:model.live="filterEstado" multiple
                class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm h-10">
            <option value="">Todos los estados</option>
            @foreach ($estadosTipo as $id => $nombre)
                <option value="{{ $id }}">{{ $nombre }}</option>
            @endforeach
        </select>
    </div>

    {{-- Tabla --}}
    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Código</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">N° Dictamen</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Nombre del Proyecto</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Rol</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Estado</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($records as $proyecto)
                    @php
                        $estado = $proyecto->estado?->tipoestado?->nombre ?? '';
                        $estadoBadge = match($estado) {
                            'En curso'    => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                            'Subsanacion' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                            'Borrador'    => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                            'Finalizado'  => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                            default       => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                        };
                        $esSubsanacion = $estado === 'Subsanacion';
                        $rowClass = $esSubsanacion ? 'bg-red-50 dark:bg-red-950 border-l-4 border-red-500' : '';
                        $rolDocente = $proyecto->docentes_proyecto()->where('empleado_id', $docente->id)->first()?->rol ?? '-';
                        $esCoordinador = $proyecto->coordinador?->id === $docente->id;
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 {{ $rowClass }}">
                        <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $proyecto->codigo_proyecto ?: '-' }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $proyecto->numero_dictamen ?: '-' }}</td>
                        <td class="px-4 py-3 text-gray-900 dark:text-white max-w-xs">
                            <a href="{{ route('historialproyecto', $proyecto) }}" class="hover:underline text-blue-600 dark:text-blue-400">
                                {{ Str::limit($proyecto->nombre_proyecto, 60) }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $rolDocente }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $estadoBadge }}">{{ $estado ?: '-' }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div x-data="{ open: false }" class="relative">
                                <button @click="open = !open"
                                        class="px-3 py-1.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg">
                                    Acciones ▾
                                </button>
                                <div x-show="open" x-cloak @click.outside="open = false"
                                     class="absolute right-0 z-20 mt-1 w-52 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1">

                                    {{-- Ver historial --}}
                                    <a href="{{ route('historialproyecto', $proyecto) }}"
                                       class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                        Ver Proyecto
                                    </a>

                                    @if ($esCoordinador)
                                        {{-- Subir Informe Intermedio --}}
                                        @if (($estado === 'En curso' && is_null($proyecto->documento_intermedio())) ||
                                             ($proyecto->documento_intermedio()?->estado?->tipoestado?->nombre === 'Subsanacion'))
                                            <button wire:click="openSubirIntermedio({{ $proyecto->id }})" @click="open = false"
                                                    class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                {{ $proyecto->documento_intermedio()?->estado?->tipoestado?->nombre === 'Subsanacion' ? 'Subsanar Inf. Intermedio' : 'Subir Inf. Intermedio' }}
                                            </button>
                                        @endif

                                        {{-- Subir Informe Final --}}
                                        @if (($estado === 'En curso' &&
                                              $proyecto->documento_intermedio()?->estado?->tipoestado?->nombre === 'Aprobado' &&
                                              is_null($proyecto->documento_final())) ||
                                             ($proyecto->documento_final()?->estado?->tipoestado?->nombre === 'Subsanacion'))
                                            <button wire:click="openSubirFinal({{ $proyecto->id }})" @click="open = false"
                                                    class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                {{ $proyecto->documento_final()?->estado?->tipoestado?->nombre === 'Subsanacion' ? 'Subsanar Inf. Final' : 'Subir Inf. Final' }}
                                            </button>
                                        @endif

                                        {{-- Actualizar equipo --}}
                                        @if ($estado === 'En curso')
                                            <a href="{{ route('ficha-actualizacion', ['proyecto' => $proyecto->id]) }}"
                                               class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                Actualizar Equipo o Fechas
                                            </a>
                                        @endif

                                        {{-- Editar borrador / Subsanar --}}
                                        @if (in_array($estado, ['Borrador', 'Subsanacion']))
                                            <a href="{{ route('crearProyectoVinculacion', $proyecto) }}"
                                               class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                {{ $estado === 'Subsanacion' ? 'Subsanar' : 'Editar Borrador' }}
                                            </a>
                                        @endif

                                        {{-- Continuar editando (Autoguardado) --}}
                                        @if ($proyecto->proyectoIsInEstadoByName('Autoguardado'))
                                            <a href="{{ route('crearProyectoVinculacion', $proyecto) }}"
                                               class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                Continuar Editando
                                            </a>
                                        @endif

                                        {{-- Borrar proyecto --}}
                                        @if (in_array($estado, ['Autoguardado', 'Borrador', 'Subsanacion', 'Subsanación']))
                                            <button wire:click="openDelete({{ $proyecto->id }})" @click="open = false"
                                                    class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                Borrar Proyecto
                                            </button>
                                        @endif
                                    @endif

                                    {{-- Constancias --}}
                                    @if (VerificarConstancia::validarConstancia($proyecto->docentes_proyecto()->where('empleado_id', $docente->id)->first()))
                                        <button wire:click="constanciaInscripcion({{ $proyecto->id }})"
                                                class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                            Constancia de Inscripción
                                        </button>
                                    @endif
                                    @if (VerificarConstancia::validarConstancia($proyecto->docentes_proyecto()->where('empleado_id', $docente->id)->first(), 'finalizacion'))
                                        <button wire:click="constanciaFinalizacion({{ $proyecto->id }})"
                                                class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                            Constancia de Finalización
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No tienes proyectos registrados.</td>
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
</div>
