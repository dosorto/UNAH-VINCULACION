<div>
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Proyectos Antes del Sistema</h2>
        <p class="text-gray-600 dark:text-gray-400 mt-2">
            Proyectos creados automáticamente desde códigos verificados que requieren completar información.
        </p>
    </div>

    <div class="mb-4">
        <input type="text" wire:model.live.debounce.300ms="search"
            placeholder="Buscar por código, nombre o dictamen..."
            class="w-full sm:w-80 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500" />
    </div>

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
                @forelse ($records as $record)
                    @php
                        $estado = $record->tipo_estado?->nombre;
                        $rol = $record->docentes_proyecto()->where('empleado_id', auth()->user()->empleado->id)->first()?->rol;
                        $badgeClass = match($estado) {
                            'PendienteInformacion' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300',
                            'Finalizado'           => 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300',
                            default                => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                        };
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="px-4 py-3 text-gray-900 dark:text-white font-mono text-xs">{{ $record->codigo_proyecto }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $record->numero_dictamen ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-900 dark:text-white max-w-xs">
                            <span title="{{ $record->nombre_proyecto }}">{{ Str::limit($record->nombre_proyecto, 50) }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $rol ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $badgeClass }}">{{ $estado ?? 'Sin estado' }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex gap-2">
                                @if ($estado === 'PendienteInformacion')
                                    <a href="{{ route('editarProyectoAntesDelSistema', $record) }}"
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                                        Completar Información
                                    </a>
                                @endif
                                <button wire:click="openView({{ $record->id }})"
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                                    Ver Detalles
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">
                            <p class="font-medium">No hay proyectos en esta sección</p>
                            <p class="text-xs mt-1">Los proyectos creados automáticamente desde códigos de investigación aparecerán aquí.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $records->links() }}</div>

    {{-- Modal Ver Detalles --}}
    @if ($viewModal && $viewProyecto)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" wire:click.self="closeView">
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-7xl max-h-[90vh] overflow-y-auto mx-4">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Detalles del Proyecto</h3>
                    <button wire:click="closeView" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-xl">&times;</button>
                </div>
                <div class="p-6">
                    @include('livewire.docente.proyectos.proyecto-antes-del-sistema-detalles', ['record' => $viewProyecto])
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
</div>
