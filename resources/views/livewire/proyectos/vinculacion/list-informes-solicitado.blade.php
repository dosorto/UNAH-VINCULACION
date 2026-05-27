<div>
    <div class="mb-4">
        <p class="text-zinc-950 dark:text-white font-bold mb-1">Informes por Revisar</p>
        <p class="text-zinc-500 dark:text-gray-400 font-medium text-sm">Informes intermedios y finales en estado de revisión.</p>
    </div>

    <div class="mb-3">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre de proyecto..."
               class="w-full sm:w-80 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Nombre del Proyecto</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Tipo de Informe</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Estado</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($records as $doc)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $doc->proyecto?->nombre_proyecto }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $doc->tipo_documento }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $doc->estadoActual?->tipoestado?->nombre ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <button wire:click="openView({{ $doc->id }})"
                                        class="px-3 py-1.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg">
                                    Ver
                                </button>
                                @if ($this->puedeAprobarInforme($doc))
                                    <button wire:click="openAprobar({{ $doc->id }})"
                                            class="px-3 py-1.5 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg">
                                        {{ $this->textoAprobarInforme($doc) }}
                                    </button>
                                    <button wire:click="openRechazar({{ $doc->id }})"
                                            class="px-3 py-1.5 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg">
                                        Subsanar
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No hay informes pendientes de revisión.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $records->links() }}</div>

    {{-- Modal Ver Informe --}}
    @if ($viewModal && $viewDocumento)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-7xl max-h-[90vh] flex flex-col">
            <div class="flex flex-wrap justify-between items-center gap-3 p-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
                <h3 class="text-lg font-semibold dark:text-white">{{ $viewDocumento->tipo_documento }}</h3>
                <div class="flex items-center gap-2">
                    @if ($this->puedeAprobarInforme($viewDocumento))
                        <button wire:click="openRechazar({{ $viewDocumento->id }})"
                                class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg">
                            Subsanar
                        </button>
                        <button wire:click="openAprobar({{ $viewDocumento->id }})"
                                class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg">
                            {{ $this->textoAprobarInforme($viewDocumento) }}
                        </button>
                    @endif
                    <button wire:click="$set('viewModal', false)" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
                </div>
            </div>
            <div class="flex-1 overflow-y-auto p-4">
                @include('components.fichas.informe', ['documentoProyecto' => $viewDocumento])
            </div>
            <div class="flex justify-end gap-3 p-4 border-t border-gray-200 dark:border-gray-700 flex-shrink-0">
                @if ($this->puedeAprobarInforme($viewDocumento))
                    <button wire:click="openRechazar({{ $viewDocumento->id }})"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg">
                        Subsanar
                    </button>
                    <button wire:click="openAprobar({{ $viewDocumento->id }})"
                            class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg">
                        {{ $this->textoAprobarInforme($viewDocumento) }}
                    </button>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Modal Rechazar --}}
    @if ($rechazarModal)
    <div class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/50">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-lg">
            <div class="flex justify-between items-center p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold dark:text-white">Rechazar Informe</h3>
                <button wire:click="$set('rechazarModal', false)" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
            </div>
            <div class="p-4 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Comentario <span class="text-red-500">*</span></label>
                    <textarea wire:model="rechazarComentario" rows="4"
                              class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                    @error('rechazarComentario') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button wire:click="$set('rechazarModal', false)"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button wire:click="rechazar()"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg">
                        Confirmar Rechazo
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal Confirmar Aprobación --}}
    @if ($aprobarModal)
    <div class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/50">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md">
            <div class="flex justify-between items-center p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold dark:text-white">Confirmar Aprobación</h3>
                <button wire:click="$set('aprobarModal', false)" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
            </div>
            <div class="p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">¿Confirmar la aprobación de este informe?</p>
                <div class="flex justify-end gap-3 mt-4">
                    <button wire:click="$set('aprobarModal', false)"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button wire:click="aprobar()"
                            class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg">
                        Aprobar
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
