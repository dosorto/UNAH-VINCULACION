<div>
    <div class="bg-white dark:bg-gray-900 shadow rounded-lg mb-6">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Mis Fichas de Actualización</h2>
                    <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">Fichas de actualización que has creado para tus proyectos de vinculación</p>
                </div>
                <span class="bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-300 px-3 py-1 rounded-full text-sm font-medium">Mis Actualizaciones</span>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Nombre del Proyecto</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Coordinador</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Fecha de Creación</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Estado</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($records as $record)
                    @php
                        $ultimoEstado = $record->obtenerUltimoEstado();
                        $estadoNombre = $ultimoEstado ? $ultimoEstado->tipoestado->nombre : 'Sin estado';
                        $badgeClass = match($estadoNombre) {
                            'Actualizacion realizada' => 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300',
                            'Rechazado'               => 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300',
                            'Borrador'                => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300',
                            default                   => 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300',
                        };
                        $puedeEliminar = $record->puedeSerEliminada();
                        $firmasRequeridas = $record->firma_proyecto()->count();
                        $firmasAprobadas = $record->firma_proyecto()->where('estado_revision', 'Aprobado')->count();
                        $puedeConstancia = $estadoNombre === 'Actualizacion realizada' && $firmasRequeridas > 0 && $firmasRequeridas === $firmasAprobadas;
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="px-4 py-3 text-gray-900 dark:text-white">
                            <div>{{ $record->proyecto?->nombre_proyecto }}</div>
                            <div class="text-xs text-gray-500">Código: {{ $record->proyecto?->codigo_proyecto }}</div>
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $record->proyecto?->coordinador?->nombre_completo ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $record->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $badgeClass }}">{{ $estadoNombre }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex gap-2 flex-wrap">
                                <button wire:click="openView({{ $record->id }})"
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                                    Ver Ficha
                                </button>
                                @if ($puedeEliminar)
                                    <button wire:click="openDelete({{ $record->id }})"
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-red-600 text-white hover:bg-red-700">
                                        Eliminar
                                    </button>
                                @endif
                                @if ($puedeConstancia)
                                    <button wire:click="constancia({{ $record->id }})"
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">
                                        Constancia
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">
                            No hay fichas de actualización.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $records->links() }}</div>

    {{-- Modal Ver Ficha --}}
    @if ($viewModal && $viewFicha)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" wire:click.self="closeView">
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-7xl max-h-[90vh] overflow-y-auto mx-4">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Ficha de Actualización</h3>
                    <button wire:click="closeView" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
                </div>
                <div class="p-6">
                    @include('components.fichas.ficha-actualizacion-proyecto-vinculacion', [
                        'fichaActualizacion' => $viewFicha,
                        'proyecto' => $viewFicha->proyecto,
                    ])
                </div>
                <div class="flex justify-end px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <button wire:click="closeView"
                        class="px-4 py-2 text-sm font-medium rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal Eliminar --}}
    @if ($deleteModal && $deleteFicha)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-md mx-4">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">¿Eliminar Ficha de Actualización?</h3>
                </div>
                <div class="p-6 text-sm text-gray-700 dark:text-gray-300">
                    Esta acción eliminará permanentemente la ficha de actualización y todas sus solicitudes asociadas. Esta acción no se puede deshacer.
                </div>
                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <button wire:click="$set('deleteModal', false)"
                        class="px-4 py-2 text-sm font-medium rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">
                        Cancelar
                    </button>
                    <button wire:click="delete"
                        class="px-4 py-2 text-sm font-medium rounded-md bg-red-600 text-white hover:bg-red-700">
                        Sí, Eliminar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
