@extends('layouts.panel.base')

@php
    use Illuminate\Support\Facades\Storage;
    $esForm018 = ($accion->codigo_formulario ?? null) === 'FORM-DVUS-018';
    $esForm016 = ($accion->codigo_formulario ?? null) === 'FORM-DVUS-016';
    $esDocumentoEnf = $esForm018 || $esForm016;
    $estadoFlujoNormalizado = strtoupper((string) $accion->estado_flujo);
    $inscripcionAprobada = $estadoFlujoNormalizado === 'APROBADO';
    $estadoEnfVista = $inscripcionAprobada ? 'En curso' : str_replace('_', ' ', (string) $accion->estado_flujo);
    $puedeActualizarEquipoFechasEnf = $esDocumentoEnf
        && $inscripcionAprobada
        && auth()->id()
        && (int) $accion->creado_por_usuario_id === (int) auth()->id();
    $revisionesInscripcion = $accion->revisiones
        ->filter(fn ($revision) => blank($revision->proceso) || $revision->proceso === \App\Models\ENF\EnfAccion::PROCESO_INSCRIPCION)
        ->sortBy(fn ($revision) => sprintf('%06d-%06d', $revision->revision_ciclo, $revision->orden));
@endphp

@push('styles')
    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            .enf-document-viewer {
                border: 0 !important;
                box-shadow: none !important;
                padding: 0 !important;
            }
        }

        .enf-document-viewer {
            background: #f8fafc;
        }

        .enf-document-canvas {
            background:
                linear-gradient(90deg, rgba(148, 163, 184, .12) 1px, transparent 1px),
                linear-gradient(rgba(148, 163, 184, .10) 1px, transparent 1px);
            background-size: 28px 28px;
        }

        .enf-document-canvas .form016-page,
        .enf-document-canvas .form018-page {
            border: 1px solid #d1d5db;
        }
    </style>
@endpush

@section('main')
    <div class="mx-auto {{ $esDocumentoEnf ? 'w-full max-w-none' : 'max-w-5xl' }} space-y-6">
        <div class="no-print rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <a href="{{ $esDocumentoEnf ? route('listarProyectosVinculacion') : route('enf.acciones.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">
                        Volver al historial
                    </a>
                    <h1 class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ $accion->nombre_accion }}</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $accion->codigo_formulario ?? 'Registro ENF' }} #{{ $accion->id }} - Estado: {{ $estadoEnfVista }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    @if ($esDocumentoEnf)
                        @if ($esForm018)
                            <a href="{{ route('enf.acciones.pdf.ver', $accion) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg border border-sky-300 bg-white px-3 py-2 text-sm font-medium text-sky-700 hover:bg-sky-50">
                                @svg('heroicon-o-eye', ['class' => 'h-4 w-4'])
                                Ver PDF
                            </a>
                        @endif
                        <a href="{{ route('enf.acciones.pdf', $accion) }}" class="inline-flex items-center gap-2 rounded-lg bg-sky-600 px-3 py-2 text-sm font-medium text-white hover:bg-sky-700">
                            @svg('heroicon-o-arrow-down-tray', ['class' => 'h-4 w-4'])
                            Descargar PDF
                        </a>

                        @if ($puedeActualizarEquipoFechasEnf)
                            <a href="{{ route('enf.acciones.edit', $accion) }}" class="inline-flex items-center rounded-lg bg-violet-600 px-3 py-2 text-sm font-medium text-white hover:bg-violet-700">
                                Actualizar Equipo o Fechas
                            </a>
                        @endif

                        @if ($accion->constanciaRegistro?->puedeDescargarse())
                            <a href="{{ route('enf.constancias.registro.descargar', $accion->constanciaRegistro) }}" class="inline-flex items-center rounded-lg bg-blue-700 px-3 py-2 text-sm font-medium text-white hover:bg-blue-800">
                                Constancia de registro
                            </a>
                        @endif

                        @if ($accion->constanciaFinalizacion?->puedeDescargarse())
                            <a href="{{ route('enf.constancias.finalizacion.descargar', $accion->constanciaFinalizacion) }}" class="inline-flex items-center rounded-lg bg-emerald-700 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-800">
                                Constancia de finalizaci&oacute;n
                            </a>
                        @endif
                    @endif

                    @if ($puedeReenviar ?? false)
                        <form method="POST" action="{{ route('enf.acciones.reenviar-revision', $accion) }}">
                            @csrf
                            <button class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                Reenviar a revisi&oacute;n
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        @if ($esDocumentoEnf && $inscripcionAprobada)
            @php
                $informeIntermedio = $accion->informeIntermedio;
                $informeFinal = $accion->informeFinal;
            @endphp
            <div class="no-print space-y-5">
                <section class="rounded-xl border border-sky-200 bg-white p-5 shadow-sm dark:border-sky-900 dark:bg-gray-900">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-sky-700 dark:text-sky-400">SEGUIMIENTO DEL PROYECTO</p>
                            <h2 class="mt-1 text-lg font-bold text-gray-900 dark:text-white">Informe Intermedio</h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Estado: {{ str_replace('_', ' ', $informeIntermedio?->estado ?? 'Pendiente de carga') }}
                                @if($revisionActualIntermedio ?? null)
                                    &middot; Etapa actual: {{ $revisionActualIntermedio->etapa_nombre }}
                                @endif
                            </p>
                            @if($informeIntermedio?->observaciones_revision)
                                <p class="mt-2 rounded-md bg-amber-50 px-3 py-2 text-sm text-amber-900">{{ $informeIntermedio->observaciones_revision }}</p>
                            @endif
                        </div>
                        <div class="flex flex-wrap justify-end gap-2">
                            @if($informeIntermedio?->archivo_pdf)
                                <a href="{{ route('enf.informes-intermedios.ver', $informeIntermedio) }}" target="_blank" class="rounded-lg border border-sky-300 px-3 py-2 text-sm font-medium text-sky-700 dark:border-sky-700 dark:text-sky-300">Ver PDF</a>
                            @endif
                            @if(($puedeGestionarInformeIntermedio ?? false) && (! $informeIntermedio || $informeIntermedio->esEditable()))
                                <button type="button" data-open-enf-intermedio-upload class="rounded-lg bg-sky-600 px-3 py-2 text-sm font-medium text-white hover:bg-sky-700">
                                    {{ $informeIntermedio?->archivo_pdf ? 'Reemplazar PDF' : 'Cargar PDF' }}
                                </button>
                            @endif
                        </div>
                    </div>

                    @if($puedeGestionarInformeIntermedio ?? false)
                        @if(! $informeIntermedio || $informeIntermedio->esEditable())
                            <form method="POST" action="{{ route('enf.acciones.informe-intermedio.store', $accion) }}" enctype="multipart/form-data" class="hidden" id="enf-intermedio-upload-form-{{ $accion->id }}">
                                @csrf
                                <input type="file" name="archivo_pdf" accept="application/pdf" required data-enf-intermedio-file>
                            </form>
                        @endif
                        @if($informeIntermedio?->esEditable() && $informeIntermedio->archivo_pdf)
                            <form method="POST" action="{{ route('enf.informes-intermedios.enviar', $informeIntermedio) }}" class="mt-3 space-y-3">
                                @csrf
                                @if(($opcionesDestinatariosIntermedio ?? collect())->isNotEmpty())
                                    <div class="grid gap-3 md:grid-cols-2">
                                        @foreach($opcionesDestinatariosIntermedio as $etapaId => $opcion)
                                            <label class="text-sm text-gray-700 dark:text-gray-200">
                                                Destinatario para {{ $opcion['etapa']->nombre }}
                                                <select name="destinatarios[{{ $etapaId }}]" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                                                    <option value="">Seleccione un destinatario</option>
                                                    @foreach($opcion['usuarios'] as $usuario)
                                                        <option value="{{ $usuario->id }}">{{ $usuario->empleado?->nombre_completo ?? $usuario->name }}</option>
                                                    @endforeach
                                                </select>
                                            </label>
                                        @endforeach
                                    </div>
                                @endif
                                <button class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700">Enviar a revisi&oacute;n</button>
                            </form>
                        @endif
                    @else
                        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Disponible cuando la inscripci&oacute;n est&eacute; aprobada y el flujo tenga etapas de informe intermedio.</p>
                    @endif
                    @error('informe_intermedio')<p class="mt-3 text-sm text-red-600">{{ $message }}</p>@enderror
                </section>

                @if(($puedeGestionarInformeIntermedio ?? false) && (! $informeIntermedio || $informeIntermedio->esEditable()))
                    <div data-enf-intermedio-upload-modal class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
                        <div class="w-full max-w-lg rounded-xl bg-white shadow-xl dark:bg-gray-900">
                            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Subir Informe Intermedio</h3>
                                <button type="button" data-close-enf-intermedio-upload class="text-xl leading-none text-gray-400 hover:text-gray-600">&times;</button>
                            </div>
                            <form method="POST" action="{{ route('enf.acciones.informe-intermedio.store', $accion) }}" enctype="multipart/form-data">
                                @csrf
                                <div class="p-5">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Archivo PDF</label>
                                    <input type="file" name="archivo_pdf" accept="application/pdf" required class="mt-2 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                                </div>
                                <div class="flex justify-end gap-3 border-t border-gray-200 px-5 py-4 dark:border-gray-700">
                                    <button type="button" data-close-enf-intermedio-upload class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">Cancelar</button>
                                    <button class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700">Subir</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif

                @if(($cierreInformeFinal['visible'] ?? false))
                    <section class="rounded-xl border border-emerald-200 bg-white p-5 shadow-sm dark:border-emerald-900 dark:bg-gray-900">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-400">CIERRE DEL PROYECTO</p>
                                <h2 class="mt-1 text-lg font-bold text-gray-900 dark:text-white">Informe Final INF-001</h2>
                                <dl class="mt-3 grid gap-x-8 gap-y-2 text-sm sm:grid-cols-2 lg:grid-cols-3">
                                    <div><dt class="text-gray-500">Estado</dt><dd class="font-semibold text-gray-900 dark:text-gray-100">{{ $cierreInformeFinal['etiqueta'] }}</dd></div>
                                    @if(!empty($cierreInformeFinal['fecha_creacion']))
                                        <div><dt class="text-gray-500">Fecha de creaci&oacute;n</dt><dd class="text-gray-900 dark:text-gray-100">{{ $cierreInformeFinal['fecha_creacion']->format('d/m/Y H:i') }}</dd></div>
                                    @endif
                                    @if(!empty($cierreInformeFinal['fecha_envio']))
                                        <div><dt class="text-gray-500">Fecha de env&iacute;o</dt><dd class="text-gray-900 dark:text-gray-100">{{ $cierreInformeFinal['fecha_envio']->format('d/m/Y H:i') }}</dd></div>
                                    @endif
                                    @if(!empty($cierreInformeFinal['etapa_actual']))
                                        <div><dt class="text-gray-500">Etapa actual</dt><dd class="text-gray-900 dark:text-gray-100">{{ $cierreInformeFinal['etapa_actual'] }}</dd></div>
                                    @endif
                                    @if(!empty($cierreInformeFinal['revisor_actual']))
                                        <div><dt class="text-gray-500">Revisor actual</dt><dd class="text-gray-900 dark:text-gray-100">{{ $cierreInformeFinal['revisor_actual'] }}</dd></div>
                                    @endif
                                </dl>

                                @if(!empty($cierreInformeFinal['motivo_rechazo']))
                                    <p class="mt-3 rounded-lg bg-rose-50 p-3 text-sm text-rose-800 dark:bg-rose-950/40 dark:text-rose-200">
                                        <strong>Motivo de subsanaci&oacute;n:</strong> {{ $cierreInformeFinal['motivo_rechazo'] }}
                                    </p>
                                @endif

                                @if(($opcionesDestinatariosFinal ?? collect())->isNotEmpty() && ($cierreInformeFinal['accion'] ?? null) === 'enviar')
                                    <form id="enf-final-send-form-{{ $accion->id }}" method="POST" action="{{ route('enf.informes-finales.enviar', $cierreInformeFinal['informe']) }}" class="mt-4 grid gap-3 md:grid-cols-2">
                                        @csrf
                                        @foreach($opcionesDestinatariosFinal as $etapaId => $opcion)
                                            <label class="text-sm text-gray-700 dark:text-gray-200">
                                                Destinatario para {{ $opcion['etapa']->nombre }}
                                                <select name="destinatarios[{{ $etapaId }}]" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                                                    <option value="">Seleccione un destinatario</option>
                                                    @foreach($opcion['usuarios'] as $usuario)
                                                        <option value="{{ $usuario->id }}">{{ $usuario->empleado?->nombre_completo ?? $usuario->name }}</option>
                                                    @endforeach
                                                </select>
                                            </label>
                                        @endforeach
                                    </form>
                                @endif
                            </div>

                            <div class="flex flex-wrap justify-end gap-2">
                                @if(($cierreInformeFinal['accion'] ?? null) === 'crear')
                                    <a href="{{ route('enf.acciones.informe-final.edit', $accion) }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Crear informe final</a>
                                @elseif(($cierreInformeFinal['accion'] ?? null) === 'continuar')
                                    <a href="{{ route('enf.acciones.informe-final.edit', $accion) }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">{{ $cierreInformeFinal['texto_accion'] }}</a>
                                @elseif(($cierreInformeFinal['accion'] ?? null) === 'subsanar')
                                    <a href="{{ route('enf.acciones.informe-final.edit', $accion) }}" class="rounded-lg border border-emerald-300 px-4 py-2 text-sm font-medium text-emerald-700 dark:border-emerald-700 dark:text-emerald-300">Editar subsanaci&oacute;n</a>
                                    @if($cierreInformeFinal['puede_enviar'])
                                        <form method="POST" action="{{ route('enf.informes-finales.enviar', $cierreInformeFinal['informe']) }}">
                                            @csrf
                                            <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Reenviar informe final</button>
                                        </form>
                                    @endif
                                @elseif(($cierreInformeFinal['accion'] ?? null) === 'enviar' && $cierreInformeFinal['puede_enviar'])
                                    @if(($opcionesDestinatariosFinal ?? collect())->isNotEmpty())
                                        <button form="enf-final-send-form-{{ $accion->id }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">{{ $cierreInformeFinal['texto_accion'] }}</button>
                                    @else
                                        <form method="POST" action="{{ route('enf.informes-finales.enviar', $cierreInformeFinal['informe']) }}">
                                            @csrf
                                            <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">{{ $cierreInformeFinal['texto_accion'] }}</button>
                                        </form>
                                    @endif
                                @elseif(($cierreInformeFinal['accion'] ?? null) === 'ver')
                                    <a href="{{ route('enf.acciones.informe-final.preview-pdf', $accion) }}" target="_blank" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 dark:border-gray-600 dark:text-gray-200">Informe final en revisi&oacute;n</a>
                                @elseif(($cierreInformeFinal['accion'] ?? null) === 'aprobado')
                                    <a href="{{ route('enf.acciones.informe-final.preview-pdf', $accion) }}" target="_blank" class="rounded-lg border border-emerald-300 px-4 py-2 text-sm font-medium text-emerald-700 dark:border-emerald-700 dark:text-emerald-300">Ver informe final aprobado</a>
                                    <a href="{{ route('enf.acciones.informe-final.pdf', $accion) }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Descargar PDF final</a>
                                @endif
                            </div>
                        </div>
                        @error('informe_final')<p class="mt-3 text-sm text-red-600">{{ $message }}</p>@enderror
                    </section>
                @endif
            </div>
        @endif

        @if (session('status'))
            <div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('status') }}
            </div>
        @endif

        @if (($puedeRevisar ?? false) || ($puedeReenviar ?? false))
            <div class="no-print rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h2 class="mb-2 text-sm font-bold uppercase text-slate-500">Flujo de revisi&oacute;n</h2>
                        @if ($revisionActual ?? null)
                            <p class="text-sm text-slate-700 dark:text-slate-300">
                                Etapa actual: <span class="font-semibold">{{ $revisionActual->etapa_nombre }}</span>
                                @if ($revisionActual->rol_requerido)
                                    &middot; Rol: {{ $revisionActual->rol_requerido }}
                                @endif
                            </p>
                        @else
                            <p class="text-sm text-slate-700 dark:text-slate-300">No hay etapa pendiente en el ciclo actual.</p>
                        @endif
                    </div>

                    @if ($puedeReenviar ?? false)
                        <form method="POST" action="{{ route('enf.acciones.reenviar-revision', $accion) }}">
                            @csrf
                            <button class="rounded-md bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">
                                Reenviar a revisi&oacute;n
                            </button>
                        </form>
                    @endif
                </div>

                @if (($puedeRevisar ?? false) && ($revisionActual ?? null))
                    <div class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-2">
                        <form method="POST" action="{{ route('enf.acciones.revisiones.aprobar', [$accion, $revisionActual]) }}" class="space-y-3">
                            @csrf
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Observaci&oacute;n de aprobaci&oacute;n</label>
                            <textarea name="observaciones" rows="3" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white"></textarea>
                            <button class="rounded-md bg-green-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-800">
                                Aprobar etapa
                            </button>
                        </form>

                        <form method="POST" action="{{ route('enf.acciones.revisiones.subsanar', [$accion, $revisionActual]) }}" class="space-y-3">
                            @csrf
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Observaci&oacute;n de subsanaci&oacute;n</label>
                            <textarea name="observaciones" rows="3" required class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white"></textarea>
                            <button class="rounded-md bg-red-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-800">
                                Enviar a subsanaci&oacute;n
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        @endif

        @if ($esDocumentoEnf)
            <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
                <section class="enf-document-viewer min-w-0 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div class="border-b border-gray-200 bg-white px-5 py-4 dark:border-gray-700 dark:bg-gray-900">
                        <h2 class="text-base font-bold text-gray-900 dark:text-white">Ficha del proyecto</h2>
                    </div>
                    <div class="enf-document-canvas overflow-x-auto px-3 py-5 sm:px-5">
                        @if ($esForm018)
                            <iframe
                                src="{{ route('enf.acciones.pdf.ver', $accion) }}"
                                title="Vista previa PDF de FORM-DVUS-018"
                                class="mx-auto block min-h-[75vh] w-full border-0 bg-white"
                            ></iframe>
                        @else
                            @include('enf.acciones.partials.form-016-document', ['accion' => $accion])
                        @endif
                    </div>
                </section>

                <aside class="no-print rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <h2 class="mb-4 text-lg font-bold text-gray-900 dark:text-white">
                        Historial de movimientos
                    </h2>

                    <div class="max-h-[calc(100vh-12rem)] overflow-y-auto pr-2">
                        @if ($revisionesInscripcion->count() > 0)
                            <ol class="relative border-s border-yellow-600">
                                @foreach ($revisionesInscripcion->values() as $index => $revision)
                                    <li class="{{ $index < $revisionesInscripcion->count() - 1 ? 'mb-8' : '' }} ms-4">
                                        <div class="absolute -start-1.5 mt-1.5 h-3 w-3 rounded-full border border-white bg-yellow-600"></div>
                                        <time class="text-sm font-normal leading-none text-yellow-600">
                                            {{ $revision->firmado_en?->format('d/m/Y H:i') ?? $revision->created_at?->format('d/m/Y H:i') }}
                                        </time>
                                        <h3 class="mt-2 text-base font-semibold text-gray-900 dark:text-gray-200">
                                            {{ $revision->etapa_nombre ?: 'Movimiento de revisi&oacute;n' }}
                                        </h3>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            Estado: {{ $revision->estado }}
                                            @if ($revision->rol_requerido)
                                                &middot; Rol: {{ $revision->rol_requerido }}
                                            @endif
                                            {{ $revision->observaciones ? ' - '.$revision->observaciones : '' }}
                                        </p>
                                    </li>
                                @endforeach
                            </ol>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                No hay movimientos registrados para esta acci&oacute;n.
                            </p>
                        @endif
                    </div>
                </aside>
            </div>
        @else
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <h2 class="mb-3 text-sm font-bold uppercase text-slate-500">Datos generales</h2>
                <dl class="space-y-2 text-sm">
                    <div><dt class="font-semibold text-slate-700 dark:text-slate-200">Tipo</dt><dd>{{ $accion->tipoAccion?->nombre ?? 'Educaci&oacute;n No Formal' }}</dd></div>
                    <div><dt class="font-semibold text-slate-700 dark:text-slate-200">Modalidad</dt><dd>{{ $accion->modalidad?->nombre ?? 'Sin definir' }}</dd></div>
                    <div><dt class="font-semibold text-slate-700 dark:text-slate-200">Centro/Facultad</dt><dd>{{ $accion->centroFacultad?->nombre ?? 'Sin definir' }}</dd></div>
                    <div><dt class="font-semibold text-slate-700 dark:text-slate-200">Carrera</dt><dd>{{ $accion->carrera?->nombre ?? 'Sin definir' }}</dd></div>
                    <div><dt class="font-semibold text-slate-700 dark:text-slate-200">Unidad responsable</dt><dd>{{ $accion->unidad_academica_responsable_texto ?: ($accion->centroFacultad?->nombre ?? 'Sin definir') }}</dd></div>
                    <div><dt class="font-semibold text-slate-700 dark:text-slate-200">Escuela / Departamento</dt><dd>{{ $accion->escuela_departamento_texto ?: ($accion->departamentoAcademico?->nombre ?? 'Sin definir') }}</dd></div>
                </dl>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <h2 class="mb-3 text-sm font-bold uppercase text-slate-500">Fechas y horas</h2>
                <dl class="space-y-2 text-sm">
                    <div><dt class="font-semibold text-slate-700 dark:text-slate-200">Inicio</dt><dd>{{ $accion->fecha_inicio?->format('d/m/Y') ?? 'Sin definir' }}</dd></div>
                    <div><dt class="font-semibold text-slate-700 dark:text-slate-200">Finalizaci&oacute;n</dt><dd>{{ $accion->fecha_finalizacion?->format('d/m/Y') ?? 'Sin definir' }}</dd></div>
                    <div><dt class="font-semibold text-slate-700 dark:text-slate-200">Horas</dt><dd>{{ $accion->total_horas ?: ($accion->horas_teoricas + $accion->horas_practicas) }}</dd></div>
                    <div><dt class="font-semibold text-slate-700 dark:text-slate-200">Cr&eacute;ditos</dt><dd>{{ $accion->carga_horaria_creditos ?: 'Sin definir' }}</dd></div>
                </dl>
            </div>
        </div>

        @if ($accion->certificado)
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <h2 class="mb-3 text-sm font-bold uppercase text-slate-500">Certificado universitario</h2>
                <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-3">
                    <div><span class="font-semibold text-slate-700 dark:text-slate-200">C&oacute;digo DAFT</span><p>{{ $accion->certificado->codigo_certificado ?: 'Pendiente' }}</p></div>
                    <div><span class="font-semibold text-slate-700 dark:text-slate-200">Tipo</span><p>{{ $accion->certificado->tipoCertificado?->nombre ?? 'Sin definir' }}</p></div>
                    <div><span class="font-semibold text-slate-700 dark:text-slate-200">Vigencia</span><p>{{ $accion->certificado->vigencia_certificado ?: 'Sin definir' }}</p></div>
                    <div><span class="font-semibold text-slate-700 dark:text-slate-200">Fecha m&aacute;xima de emisi&oacute;n</span><p>{{ $accion->certificado->fecha_emision_maxima?->format('d/m/Y') ?? 'Sin definir' }}</p></div>
                    <div><span class="font-semibold text-slate-700 dark:text-slate-200">PAC</span><p>{{ $accion->certificado->pac_certificado ?: 'Sin definir' }}</p></div>
                    <div><span class="font-semibold text-slate-700 dark:text-slate-200">Horario</span><p>{{ collect([$accion->certificado->hora_inicio, $accion->certificado->hora_finalizacion])->filter()->implode(' - ') ?: 'Sin definir' }}</p></div>
                    <div class="md:col-span-3"><span class="font-semibold text-slate-700 dark:text-slate-200">D&iacute;as</span><p>{{ collect($accion->certificado->dias_imparticion ?? [])->implode(', ') ?: 'Sin definir' }}</p></div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                    <h2 class="mb-3 text-sm font-bold uppercase text-slate-500">Carreras aprobadas</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                            <thead><tr class="text-left text-xs uppercase text-slate-500"><th class="px-3 py-2">Carrera</th><th class="px-3 py-2">Acuerdo</th></tr></thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @forelse ($accion->certificado->carreras as $carrera)
                                    <tr>
                                        <td class="px-3 py-2">{{ $carrera->carrera?->nombre ?? $carrera->nombre_carrera ?? 'Sin nombre' }}</td>
                                        <td class="px-3 py-2">{{ $carrera->acuerdo_consejo_universitario ?: 'Sin acuerdo' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2" class="px-3 py-4 text-center text-slate-500">Sin carreras registradas.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                    <h2 class="mb-3 text-sm font-bold uppercase text-slate-500">Espacios de aprendizaje</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                            <thead><tr class="text-left text-xs uppercase text-slate-500"><th class="px-3 py-2">Nombre</th><th class="px-3 py-2">C&oacute;digo</th><th class="px-3 py-2">Cr&eacute;ditos</th><th class="px-3 py-2">Horas</th></tr></thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @forelse ($accion->espaciosAprendizaje as $espacio)
                                    <tr>
                                        <td class="px-3 py-2">{{ $espacio->nombre }}</td>
                                        <td class="px-3 py-2">{{ $espacio->codigo ?: '-' }}</td>
                                        <td class="px-3 py-2">{{ $espacio->creditos }}</td>
                                        <td class="px-3 py-2">{{ $espacio->horas }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-3 py-4 text-center text-slate-500">Sin espacios registrados.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <h2 class="mb-3 text-sm font-bold uppercase text-slate-500">Resumen</h2>
            <p class="whitespace-pre-line text-sm text-slate-700 dark:text-slate-300">{{ $accion->resumen ?: 'Sin resumen registrado.' }}</p>
            @if ($accion->impacto_esperado)
                <h3 class="mt-4 text-sm font-bold uppercase text-slate-500">Impacto esperado</h3>
                <p class="mt-2 whitespace-pre-line text-sm text-slate-700 dark:text-slate-300">{{ $accion->impacto_esperado }}</p>
            @endif
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <h2 class="mb-3 text-sm font-bold uppercase text-slate-500">Equipo ejecutor</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                    <thead>
                        <tr class="text-left text-xs uppercase text-slate-500">
                            <th class="px-3 py-2">Rol</th>
                            <th class="px-3 py-2">Nombre</th>
                            <th class="px-3 py-2">Correo</th>
                            <th class="px-3 py-2">Detalle</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($accion->equipo as $integrante)
                            <tr>
                                <td class="px-3 py-2">{{ $integrante->rol ?? 'Sin rol' }}</td>
                                <td class="px-3 py-2">{{ $integrante->nombre_completo ?? $integrante->empleado?->nombre_completo ?? 'Sin nombre' }}</td>
                                <td class="px-3 py-2">{{ $integrante->correo ?? 'Sin correo' }}</td>
                                <td class="px-3 py-2 text-slate-600 dark:text-slate-300">
                                    {{ collect([$integrante->numero_empleado, $integrante->categoria, $integrante->departamento, $integrante->profesion, $integrante->nacionalidad])->filter()->implode(' &middot; ') ?: 'Sin detalle' }}
                                    @if ($integrante->espacio_aprendizaje)
                                        <span class="block text-xs text-slate-500">Espacio: {{ $integrante->espacio_aprendizaje }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-3 py-4 text-center text-slate-500">Sin equipo registrado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <h2 class="mb-3 text-sm font-bold uppercase text-slate-500">Participaci&oacute;n universitaria</h2>
                <dl class="space-y-2 text-sm">
                    @forelse ($accion->participacionUniversitaria as $participacion)
                        <div>
                            <dt class="font-semibold text-slate-700 dark:text-slate-200">{{ $participacion->tipo_participacion }}</dt>
                            <dd>{{ $participacion->cantidad }} {{ $participacion->descripcion ? '? '.$participacion->descripcion : '' }}</dd>
                        </div>
                    @empty
                        <div class="text-slate-500">Sin participaci&oacute;n registrada.</div>
                    @endforelse
                </dl>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <h2 class="mb-3 text-sm font-bold uppercase text-slate-500">Pr&aacute;ctica de asignatura</h2>
                <dl class="space-y-2 text-sm">
                    @forelse ($accion->practicasAsignatura as $practica)
                        <div>
                            <dt class="font-semibold text-slate-700 dark:text-slate-200">{{ $practica->asignatura?->nombre ?? $practica->nombre_asignatura ?? 'Asignatura sin nombre' }}</dt>
                            <dd>{{ $practica->codigo_asignatura }} &middot; {{ $practica->periodoAcademico?->nombre ?? $practica->periodo_academico_texto }} &middot; Matr&iacute;cula: {{ $practica->cantidad_estudiantes }}</dd>
                        </div>
                    @empty
                        <div class="text-slate-500">Sin pr&aacute;cticas registradas.</div>
                    @endforelse
                </dl>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <h2 class="mb-3 text-sm font-bold uppercase text-slate-500">Documentos adjuntos</h2>
                <ul class="space-y-2 text-sm">
                    @forelse ($accion->documentos as $documento)
                        <li class="flex items-center justify-between gap-3 rounded-md border border-slate-100 px-3 py-2 dark:border-slate-800">
                            <span>{{ $documento->nombre }}</span>
                            @if ($documento->ruta && $documento->ruta !== 'pendiente')
                                <span class="flex shrink-0 items-center gap-3">
                                    <a href="{{ Storage::url($documento->ruta) }}" target="_blank" rel="noopener" class="font-semibold text-blue-700 hover:text-blue-900">Ver</a>
                                    <a href="{{ Storage::url($documento->ruta) }}" download class="font-semibold text-slate-700 hover:text-slate-900 dark:text-slate-200 dark:hover:text-white">Descargar</a>
                                </span>
                            @else
                                <span class="text-slate-500">Pendiente</span>
                            @endif
                        </li>
                    @empty
                        <li class="text-slate-500">Sin documentos registrados.</li>
                    @endforelse
                </ul>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <h2 class="mb-3 text-sm font-bold uppercase text-slate-500">Firmas</h2>
                <dl class="space-y-2 text-sm">
                    @forelse ($accion->firmas as $firma)
                        <div>
                            <dt class="font-semibold text-slate-700 dark:text-slate-200">{{ $firma->rol_firma ?: 'Firma' }}</dt>
                            <dd>{{ $firma->nombre_firmante ?: $firma->empleado?->nombre_completo ?: 'Sin nombre' }} &middot; {{ $firma->estado_revision }}</dd>
                        </div>
                    @empty
                        <div class="text-slate-500">Sin firmas registradas.</div>
                    @endforelse
                </dl>
            </div>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <h2 class="mb-3 text-sm font-bold uppercase text-slate-500">Historial de revisi&oacute;n</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                    <thead>
                        <tr class="text-left text-xs uppercase text-slate-500">
                            <th class="px-3 py-2">Orden</th>
                            <th class="px-3 py-2">Etapa</th>
                            <th class="px-3 py-2">Rol</th>
                            <th class="px-3 py-2">Estado</th>
                            <th class="px-3 py-2">Observaci&oacute;n</th>
                            <th class="px-3 py-2">Fecha</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($accion->revisiones->sortBy(fn ($revision) => sprintf('%06d-%06d', $revision->revision_ciclo, $revision->orden)) as $revision)
                            <tr>
                                <td class="px-3 py-2">{{ $revision->revision_ciclo }}.{{ $revision->orden }}</td>
                                <td class="px-3 py-2">{{ $revision->etapa_nombre }}</td>
                                <td class="px-3 py-2">{{ $revision->rol_requerido ?: '-' }}</td>
                                <td class="px-3 py-2">{{ $revision->estado }}</td>
                                <td class="px-3 py-2">{{ $revision->observaciones ?: '-' }}</td>
                                <td class="px-3 py-2">{{ $revision->firmado_en?->format('d/m/Y H:i') ?? $revision->created_at?->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-3 py-4 text-center text-slate-500">Sin movimientos registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.querySelector('[data-enf-intermedio-upload-modal]');
            const openButtons = document.querySelectorAll('[data-open-enf-intermedio-upload]');
            const closeButtons = document.querySelectorAll('[data-close-enf-intermedio-upload]');

            const openModal = () => {
                modal?.classList.remove('hidden');
                modal?.classList.add('flex');
            };

            const closeModal = () => {
                modal?.classList.add('hidden');
                modal?.classList.remove('flex');
            };

            openButtons.forEach((button) => button.addEventListener('click', openModal));
            closeButtons.forEach((button) => button.addEventListener('click', closeModal));
            modal?.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });
        });
    </script>
@endpush
