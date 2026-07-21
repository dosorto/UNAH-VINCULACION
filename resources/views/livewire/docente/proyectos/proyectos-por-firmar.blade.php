<div>
    <div class="bg-white dark:bg-gray-900 shadow rounded-lg mb-6">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Proyectos por Firmar</h2>
                    <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">Proyectos que requieren su firma</p>
                </div>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Nombre del Proyecto</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Cargo de Firma</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Estado Firma</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Tipo Firma</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($records as $record)
                    @php
                        $estadoBadge = match($record->estado_revision) {
                            'Aprobado'  => 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300',
                            'Pendiente' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300',
                            'Rechazado' => 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300',
                            default     => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                        };
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $record->proyecto?->nombre_proyecto ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                                {{ $record->cargo_firma?->tipoCargoFirma?->nombre ?? '—' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $estadoBadge }}">{{ $record->estado_revision }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $record->cargo_firma?->descripcion ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <button wire:click="openView({{ $record->id }})"
                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                                Ver
                            </button>
                        </td>
                    </tr>
                @endforeach

                @foreach ($enfRevisiones as $revision)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $revision->accion?->nombre_accion ?? 'Registro ENF' }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                                {{ $revision->rol_requerido ?: 'ENF' }}
                            </span>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $revision->etapa_nombre }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300">
                                {{ str_replace('_', ' ', $revision->estado) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                            Educación No Formal
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $revision->accion?->codigo_formulario ?? '-' }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-2">
                                @if ($revision->accion)
                                    <a href="{{ route('enf.acciones.show', $revision->accion) }}"
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                                        Ver
                                    </a>
                                @endif
                                <button wire:click="openEnfSubsanar({{ $revision->id }})"
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-amber-600 text-white hover:bg-amber-700">
                                    Subsanar
                                </button>
                                <button x-on:click.prevent="confirmDialog('¿Está seguro de aprobar esta etapa ENF?').then((ok) => ok && $wire.aprobarEnfRevision({{ $revision->id }}))"
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-green-600 text-white hover:bg-green-700">
                                    Aprobar
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach

                @foreach ($ppsRegistros as $registro)
                    @php($firmaPpsPendiente = $this->firmaPendienteDePps($registro))
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="px-4 py-3 text-gray-900 dark:text-white">
                            {{ $registro->nombre_estudiante ?: ($registro->codigo_registro ?: 'Registro PPS/SS') }}
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ $registro->codigo_registro ?: 'FORM-DVUS-014 #'.$registro->id }}
                                @if ($registro->nombre_institucion)
                                    · {{ $registro->nombre_institucion }}
                                @endif
                            </p>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                                {{ $registro->etapaActual?->rolRevisor?->name ?? 'PPS/SS' }}
                            </span>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $registro->etapaActual?->nombre ?? 'Etapa actual' }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300">
                                {{ ucfirst(str_replace('_', ' ', $registro->estado ?: 'pendiente')) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                            PPS / Servicio Social
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">FORM-DVUS-014</p>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('pps-servicio-social.show', $registro->id) }}" wire:navigate
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                                    Ver
                                </a>
                                <button wire:click="openPpsSubsanar({{ $registro->id }})"
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-amber-600 text-white hover:bg-amber-700">
                                    Subsanar
                                </button>
                                <button x-on:click.prevent="confirmDialog('¿Está seguro de aprobar esta etapa PPS/SS?').then((ok) => ok && $wire.aprobarPpsRegistro({{ $registro->id }}))"
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-green-600 text-white hover:bg-green-700">
                                    Aprobar
                                </button>
                                @if ($this->puedeReasignar($firmaPpsPendiente))
                                    <button wire:click="openReasignar({{ $firmaPpsPendiente->id }})"
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
                                        Reasignar
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach

                @if ($records->count() === 0 && $enfRevisiones->isEmpty() && $ppsRegistros->isEmpty())
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">No hay proyectos por firmar.</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $records->links() }}</div>

    {{-- Modal Ver Ficha --}}
    @if ($viewModal && $viewFirma)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" wire:click.self="closeView">
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-7xl max-h-[90vh] overflow-y-auto mx-4">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $viewDocumento ? $viewDocumento->tipo_documento : 'Ficha del Proyecto' }}
                    </h3>
                    <button wire:click="closeView" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
                </div>
                <div class="p-6">
                    @if ($viewDocumento)
                        @include('components.fichas.informe', ['documentoProyecto' => $viewDocumento])
                    @elseif ($viewProyecto)
                        @include('components.fichas.ficha-proyecto-vinculacion', ['proyecto' => $viewProyecto])
                    @endif
                </div>
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    @if ($this->puedeSubsanar($viewFirma->id))
                        <button wire:click="openRechazar({{ $viewFirma->id }})"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md bg-amber-600 text-white hover:bg-amber-700">
                            Subsanar
                        </button>
                        <button x-on:click.prevent="confirmDialog('¿Estás seguro de que deseas aprobar la firma de este proyecto?').then((ok) => ok && $wire.aprobar({{ $viewFirma->id }}))"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md bg-green-600 text-white hover:bg-green-700">
                            Aprobar
                        </button>
                    @endif
                    @if ($this->puedeReasignar($viewFirma))
                        <button wire:click="openReasignar({{ $viewFirma->id }})"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
                            Reasignar
                        </button>
                    @endif
                    <button wire:click="closeView"
                        class="px-4 py-2 text-sm font-medium rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal Subsanar --}}
    @if ($rechazarModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50">
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-xl mx-4">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Enviar a subsanacion</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">¿Estás seguro de que deseas devolver este proyecto para correcciones?</p>
                </div>
                <div class="p-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Comentario: Indique las correcciones requeridas <span class="text-red-500">*</span>
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
                        class="px-4 py-2 text-sm font-medium rounded-md bg-amber-600 text-white hover:bg-amber-700">
                        Subsanar
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if ($enfSubsanarModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50">
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-xl mx-4">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Enviar ENF a subsanación</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Indique las correcciones requeridas para este registro.</p>
                </div>
                <div class="p-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Observación <span class="text-red-500">*</span>
                    </label>
                    <textarea wire:model="enfSubsanarComentario" rows="5"
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500"></textarea>
                    @error('enfSubsanarComentario') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <button wire:click="$set('enfSubsanarModal', false)"
                        class="px-4 py-2 text-sm font-medium rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">
                        Cancelar
                    </button>
                    <button wire:click="subsanarEnfRevision"
                        class="px-4 py-2 text-sm font-medium rounded-md bg-amber-600 text-white hover:bg-amber-700">
                        Enviar a subsanación
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if ($ppsSubsanarModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50">
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-xl mx-4">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Enviar PPS/SS a subsanación</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Indique las correcciones requeridas para este registro.</p>
                </div>
                <div class="p-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Observación <span class="text-red-500">*</span>
                    </label>
                    <textarea wire:model="ppsSubsanarComentario" rows="5"
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500"></textarea>
                    @error('ppsSubsanarComentario') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <button wire:click="$set('ppsSubsanarModal', false)"
                        class="px-4 py-2 text-sm font-medium rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">
                        Cancelar
                    </button>
                    <button wire:click="subsanarPpsRegistro"
                        class="px-4 py-2 text-sm font-medium rounded-md bg-amber-600 text-white hover:bg-amber-700">
                        Enviar a subsanación
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal Reasignar --}}
    @if ($reasignarModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50">
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-xl mx-4">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Reasignar etapa</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Elige a otra persona con el mismo rol de revisor de esta etapa para que continúe con la revisión.</p>
                </div>
                <div class="p-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Nuevo responsable <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="reasignarNuevoUsuarioId"
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Seleccione una persona</option>
                        @foreach ($reasignarCandidatos as $candidato)
                            <option value="{{ $candidato['id'] }}">{{ $candidato['name'] }}</option>
                        @endforeach
                    </select>
                    @if (empty($reasignarCandidatos))
                        <p class="text-xs text-amber-600 dark:text-amber-400 mt-2">No hay otras personas con el rol requerido para esta etapa.</p>
                    @endif
                    @error('reasignarNuevoUsuarioId') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <button wire:click="closeReasignar"
                        class="px-4 py-2 text-sm font-medium rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">
                        Cancelar
                    </button>
                    <button x-on:click.prevent="confirmDialog('¿Confirmas reasignar esta etapa a la persona seleccionada?').then((ok) => ok && $wire.confirmarReasignacion())"
                        class="px-4 py-2 text-sm font-medium rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
                        Confirmar reasignación
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
