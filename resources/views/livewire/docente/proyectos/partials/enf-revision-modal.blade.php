@php
    $hasEnfRevision = $viewEnfModal && $viewEnfRevision && $viewEnfRevision->accion;
    $viewEnfAccion = $hasEnfRevision ? $viewEnfRevision->accion : null;
    $viewEnfForm018 = $viewEnfAccion && ($viewEnfAccion->codigo_formulario ?? null) === 'FORM-DVUS-018';
    $viewEnfEsIntermedio = $hasEnfRevision
        && ($viewEnfRevision->proceso ?? null) === \App\Models\ENF\EnfAccion::PROCESO_INFORME_INTERMEDIO;
    $viewEnfEsFinal = $hasEnfRevision
        && ($viewEnfRevision->proceso ?? null) === \App\Models\ENF\EnfAccion::PROCESO_INFORME_FINAL;
    $viewEnfInformeIntermedio = $viewEnfAccion ? $viewEnfAccion->informeIntermedio : null;
@endphp

@if($hasEnfRevision)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" wire:click.self="closeEnfView">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl w-[94vw] max-w-[92rem] max-h-[90vh] overflow-y-auto mx-2">
            <style>
                .enf-review-document-wrap {
                    background: #fff;
                    overflow-x: auto;
                    padding: 0;
                }

                .enf-review-document-wrap .form016-shell,
                .enf-review-document-wrap .form018-shell {
                    margin: 0 auto;
                    max-width: none;
                    overflow: visible;
                    width: calc(8.5in * 1.32);
                }

                .enf-review-document-wrap .form016-shell.screen-document,
                .enf-review-document-wrap .form018-shell.screen-document {
                    gap: 0;
                    justify-items: center;
                }

                .enf-review-document-wrap .form016-shell.screen-document {
                    --form016-screen-scale: 1.32 !important;
                }

                .enf-review-document-wrap .form018-shell.screen-document {
                    --form018-screen-scale: 1.32 !important;
                }

                .enf-review-document-wrap .form016-shell.screen-document .form016-page,
                .enf-review-document-wrap .form018-shell.screen-document .form018-page {
                    box-shadow: none;
                    margin: 0 auto;
                }

                .enf-review-document-card {
                    border-radius: 8px;
                }
            </style>
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $viewEnfEsIntermedio ? 'Informe Intermedio' : 'Ficha del Proyecto' }}</h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $viewEnfAccion->codigo_formulario }} - {{ $viewEnfRevision->etapa_nombre }}</p>
                </div>
                <button wire:click="closeEnfView" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
            </div>

            <div class="p-6">
                @if($viewEnfEsIntermedio)
                    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                    Documento Informe Intermedio: Estado: {{ str_replace('_', ' ', ucfirst(strtolower($viewEnfInformeIntermedio->estado ?? 'EN_REVISION'))) }}
                                </h4>
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $viewEnfInformeIntermedio->nombre_original ?? 'Documento creado' }}
                                </p>
                            </div>
                            @if($viewEnfInformeIntermedio && $viewEnfInformeIntermedio->archivo_pdf)
                                <a href="{{ route('enf.informes-intermedios.ver', $viewEnfInformeIntermedio) }}" target="_blank"
                                   class="inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                                    Ver PDF
                                </a>
                            @endif
                        </div>
                    </section>
                @else
                    <section class="enf-review-document-card overflow-hidden border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div class="border-b border-gray-200 bg-white px-5 py-4 dark:border-gray-700 dark:bg-gray-900">
                            <h4 class="text-base font-bold text-gray-900 dark:text-white">Ficha del proyecto</h4>
                        </div>
                        <div class="enf-review-document-wrap">
                            @if($viewEnfForm018)
                                @include('enf.acciones.partials.form-018-document', ['accion' => $viewEnfAccion])
                            @else
                                @include('enf.acciones.partials.form-016-document', ['accion' => $viewEnfAccion])
                            @endif
                        </div>
                    </section>
                @endif
            </div>

            <div class="px-6 pb-5">
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Observación de aprobación</label>
                <textarea wire:model.defer="enfAprobacionComentario" rows="3"
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"></textarea>
                @error('enfAprobacionComentario')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror

                @if($viewEnfEsFinal)
                    <div class="mt-5 border-t border-gray-200 pt-5 dark:border-gray-700">
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Documento de revisión (PDF opcional)</label>
                        <input type="file" wire:model="documentoRevision" accept=".pdf,application/pdf"
                            class="block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100 dark:text-gray-200">
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Puede adjuntar rúbrica, dictamen u otro documento de revisión del informe final.</p>
                        @if ($documentoRevision)
                            <button type="button" wire:click="$set('documentoRevision', null)" class="mt-2 text-xs font-semibold text-red-600 hover:text-red-700">Quitar archivo</button>
                        @endif
                        @error('documentoRevision')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror

                        @if ($this->enfDocumentosRevisionView->isNotEmpty())
                            <div class="mt-4 rounded-md border border-gray-200 bg-gray-50 p-3 text-sm dark:border-gray-700 dark:bg-gray-800">
                                <p class="mb-2 font-semibold text-gray-800 dark:text-gray-100">Documentos de revisión cargados</p>
                                <ul class="space-y-1">
                                    @foreach($this->enfDocumentosRevisionView as $documentoRevisionEnf)
                                        <li>
                                            <a class="text-blue-700 underline" href="{{ route('enf.informes-finales.documentos-revision.descargar', $documentoRevisionEnf) }}">{{ $documentoRevisionEnf->nombre_original }}</a>
                                            <span class="text-gray-500"> · {{ $documentoRevisionEnf->revision?->etapa_nombre ?: 'Etapa de revisión' }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                <button wire:click="openEnfSubsanar({{ $viewEnfRevision->id }})"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md bg-amber-600 text-white hover:bg-amber-700">
                    Subsanar
                </button>
                <button x-on:click.prevent="confirmDialog('¿Está seguro de aprobar esta etapa ENF?').then((ok) => ok && $wire.aprobarEnfRevision({{ $viewEnfRevision->id }}))"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md bg-green-600 text-white hover:bg-green-700">
                    Aprobar
                </button>
                @if ($this->puedeReasignarEnf($viewEnfRevision))
                    <button wire:click="openEnfReasignar({{ $viewEnfRevision->id }})"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
                        Reasignar
                    </button>
                @endif
                <button wire:click="closeEnfView"
                    class="px-4 py-2 text-sm font-medium rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
@endif
