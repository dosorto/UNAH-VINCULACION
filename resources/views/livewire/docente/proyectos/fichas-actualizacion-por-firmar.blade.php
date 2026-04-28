<div>
    <div class="bg-white dark:bg-gray-900 shadow rounded-lg mb-6">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Fichas de Actualización por Firmar</h2>
                    <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">Fichas de actualización de proyectos que requieren su firma</p>
                </div>
                <span class="bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-300 px-3 py-1 rounded-full text-sm font-medium">Actualizaciones</span>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Nombre del Proyecto</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Código Proyecto</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Cargo de Firma</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Estado Firma</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($records as $record)
                    @php
                        $estadoBadge = match($record->estado_revision) {
                            'Aprobado'  => 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300',
                            'Pendiente' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300',
                            'Rechazado' => 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300',
                            default     => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                        };
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $record->ficha_actualizacion?->proyecto?->nombre_proyecto ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300 font-mono text-xs">{{ $record->ficha_actualizacion?->proyecto?->codigo_proyecto ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                                {{ $record->cargo_firma?->tipoCargoFirma?->nombre ?? '—' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $estadoBadge }}">{{ $record->estado_revision }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <button wire:click="openView({{ $record->id }})"
                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                                Ver Ficha
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">
                            No hay fichas de actualización por firmar.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $records->links() }}</div>

    {{-- Modal Ver Ficha --}}
    @if ($viewModal && $viewFirma)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" wire:click.self="closeView">
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-7xl max-h-[90vh] overflow-y-auto mx-4">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Ficha de Actualización</h3>
                    <button wire:click="closeView" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
                </div>
                <div class="p-6">
                    @if ($viewFicha && $viewProyecto)
                        @include('components.fichas.ficha-actualizacion-proyecto-vinculacion', [
                            'fichaActualizacion' => $viewFicha,
                            'proyecto' => $viewProyecto,
                        ])
                    @endif
                </div>
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <button wire:click="openRechazar({{ $viewFirma->id }})"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md bg-red-600 text-white hover:bg-red-700">
                        Rechazar
                    </button>
                    <button wire:click="aprobar({{ $viewFirma->id }})" wire:confirm="¿Estás seguro de que deseas aprobar la firma de esta ficha de actualización?"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md bg-green-600 text-white hover:bg-green-700">
                        Aprobar
                    </button>
                    <button wire:click="closeView"
                        class="px-4 py-2 text-sm font-medium rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal Rechazar --}}
    @if ($rechazarModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50">
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-xl mx-4">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Confirmar Rechazo</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">¿Estás seguro de que deseas rechazar la firma de esta ficha de actualización?</p>
                </div>
                <div class="p-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Comentario: Indique el motivo de rechazo <span class="text-red-500">*</span>
                    </label>
                    <textarea wire:model="rechazarComentario" rows="5"
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500"></textarea>
                    @error('rechazarComentario') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <button wire:click="$set('rechazarModal', false)"
                        class="px-4 py-2 text-sm font-medium rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">
                        Cancelar
                    </button>
                    <button wire:click="rechazar"
                        class="px-4 py-2 text-sm font-medium rounded-md bg-red-600 text-white hover:bg-red-700">
                        Rechazar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
