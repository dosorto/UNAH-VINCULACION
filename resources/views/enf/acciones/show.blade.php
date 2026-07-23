@extends('layouts.panel.base')

@php
    use Illuminate\Support\Facades\Storage;
    $esForm018 = ($accion->codigo_formulario ?? null) === 'FORM-DVUS-018';
    $esForm016 = ($accion->codigo_formulario ?? null) === 'FORM-DVUS-016';
    $esDocumentoEnf = $esForm018 || $esForm016;
@endphp

@push('styles')
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
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
                        {{ $accion->codigo_formulario ?? 'Registro ENF' }} #{{ $accion->id }} · Estado: {{ $accion->estado_flujo }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    @if ($esDocumentoEnf)
                        <a href="{{ route('enf.acciones.pdf', $accion) }}" class="inline-flex items-center gap-2 rounded-lg bg-slate-800 px-3 py-2 text-sm font-medium text-white hover:bg-slate-900 dark:bg-slate-700 dark:hover:bg-slate-600">
                            @svg('heroicon-o-arrow-down-tray', ['class' => 'h-4 w-4'])
                            Descargar PDF
                        </a>
                    @endif

                    @if ($puedeReenviar ?? false)
                        <form method="POST" action="{{ route('enf.acciones.reenviar-revision', $accion) }}">
                            @csrf
                            <button class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                Reenviar a revisión
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        @if (session('status'))
            <div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('status') }}
            </div>
        @endif

        @if (($revisionActual ?? null) || ($puedeRevisar ?? false))
            <div class="no-print rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h2 class="mb-2 text-sm font-bold uppercase text-slate-500">Flujo de revisión</h2>
                        @if ($revisionActual ?? null)
                            <p class="text-sm text-slate-700 dark:text-slate-300">
                                Etapa actual: <span class="font-semibold">{{ $revisionActual->etapa_nombre }}</span>
                                @if ($revisionActual->rol_requerido)
                                    · Rol: {{ $revisionActual->rol_requerido }}
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
                                Reenviar a revisión
                            </button>
                        </form>
                    @endif
                </div>

                @if (($puedeRevisar ?? false) && ($revisionActual ?? null))
                    <div class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-2">
                        <form method="POST" action="{{ route('enf.acciones.revisiones.aprobar', [$accion, $revisionActual]) }}" class="space-y-3">
                            @csrf
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Observación de aprobación</label>
                            <textarea name="observaciones" rows="3" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white"></textarea>
                            <button class="rounded-md bg-green-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-800">
                                Aprobar etapa
                            </button>
                        </form>

                        <form method="POST" action="{{ route('enf.acciones.revisiones.subsanar', [$accion, $revisionActual]) }}" class="space-y-3">
                            @csrf
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Observación de subsanación</label>
                            <textarea name="observaciones" rows="3" required class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white"></textarea>
                            <button class="rounded-md bg-red-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-800">
                                Enviar a subsanación
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        @endif

        @if ($esDocumentoEnf)
            <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
                <section class="min-w-0">
                    @if ($esForm018)
                        @include('enf.acciones.partials.form-018-document', ['accion' => $accion])
                    @else
                        @include('enf.acciones.partials.form-016-document', ['accion' => $accion])
                    @endif
                </section>

                <aside class="no-print rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <h2 class="mb-4 text-lg font-bold text-gray-900 dark:text-white">
                        Historial de movimientos
                    </h2>

                    <div class="max-h-[calc(100vh-12rem)] overflow-y-auto pr-2">
                        @if ($accion->revisiones->count() > 0)
                            <ol class="relative border-s border-yellow-600">
                                @foreach ($accion->revisiones->sortBy(fn ($revision) => sprintf('%06d-%06d', $revision->revision_ciclo, $revision->orden)) as $index => $revision)
                                    <li class="{{ $index < $accion->revisiones->count() - 1 ? 'mb-8' : '' }} ms-4">
                                        <div class="absolute -start-1.5 mt-1.5 h-3 w-3 rounded-full border border-white bg-yellow-600"></div>
                                        <time class="text-sm font-normal leading-none text-yellow-600">
                                            {{ $revision->firmado_en?->format('d/m/Y H:i') ?? $revision->created_at?->format('d/m/Y H:i') }}
                                        </time>
                                        <h3 class="mt-2 text-base font-semibold text-gray-900 dark:text-gray-200">
                                            {{ $revision->etapa_nombre ?: 'Movimiento de revisión' }}
                                        </h3>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            Estado: {{ $revision->estado }}
                                            @if ($revision->rol_requerido)
                                                · Rol: {{ $revision->rol_requerido }}
                                            @endif
                                            {{ $revision->observaciones ? ' - '.$revision->observaciones : '' }}
                                        </p>
                                    </li>
                                @endforeach
                            </ol>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                No hay movimientos registrados para esta acción.
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
                    <div><dt class="font-semibold text-slate-700 dark:text-slate-200">Tipo</dt><dd>{{ $accion->tipoAccion?->nombre ?? 'Educación No Formal' }}</dd></div>
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
                    <div><dt class="font-semibold text-slate-700 dark:text-slate-200">Finalización</dt><dd>{{ $accion->fecha_finalizacion?->format('d/m/Y') ?? 'Sin definir' }}</dd></div>
                    <div><dt class="font-semibold text-slate-700 dark:text-slate-200">Horas</dt><dd>{{ $accion->total_horas ?: ($accion->horas_teoricas + $accion->horas_practicas) }}</dd></div>
                    <div><dt class="font-semibold text-slate-700 dark:text-slate-200">Créditos</dt><dd>{{ $accion->carga_horaria_creditos ?: 'Sin definir' }}</dd></div>
                </dl>
            </div>
        </div>

        @if ($accion->certificado)
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <h2 class="mb-3 text-sm font-bold uppercase text-slate-500">Certificado universitario</h2>
                <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-3">
                    <div><span class="font-semibold text-slate-700 dark:text-slate-200">Código DAFT</span><p>{{ $accion->certificado->codigo_certificado ?: 'Pendiente' }}</p></div>
                    <div><span class="font-semibold text-slate-700 dark:text-slate-200">Tipo</span><p>{{ $accion->certificado->tipoCertificado?->nombre ?? 'Sin definir' }}</p></div>
                    <div><span class="font-semibold text-slate-700 dark:text-slate-200">Vigencia</span><p>{{ $accion->certificado->vigencia_certificado ?: 'Sin definir' }}</p></div>
                    <div><span class="font-semibold text-slate-700 dark:text-slate-200">Fecha máxima de emisión</span><p>{{ $accion->certificado->fecha_emision_maxima?->format('d/m/Y') ?? 'Sin definir' }}</p></div>
                    <div><span class="font-semibold text-slate-700 dark:text-slate-200">PAC</span><p>{{ $accion->certificado->pac_certificado ?: 'Sin definir' }}</p></div>
                    <div><span class="font-semibold text-slate-700 dark:text-slate-200">Horario</span><p>{{ collect([$accion->certificado->hora_inicio, $accion->certificado->hora_finalizacion])->filter()->implode(' - ') ?: 'Sin definir' }}</p></div>
                    <div class="md:col-span-3"><span class="font-semibold text-slate-700 dark:text-slate-200">Días</span><p>{{ collect($accion->certificado->dias_imparticion ?? [])->implode(', ') ?: 'Sin definir' }}</p></div>
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
                            <thead><tr class="text-left text-xs uppercase text-slate-500"><th class="px-3 py-2">Nombre</th><th class="px-3 py-2">Código</th><th class="px-3 py-2">Créditos</th><th class="px-3 py-2">Horas</th></tr></thead>
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
                                    {{ collect([$integrante->numero_empleado, $integrante->categoria, $integrante->departamento, $integrante->profesion, $integrante->nacionalidad])->filter()->implode(' · ') ?: 'Sin detalle' }}
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
                <h2 class="mb-3 text-sm font-bold uppercase text-slate-500">Participación universitaria</h2>
                <dl class="space-y-2 text-sm">
                    @forelse ($accion->participacionUniversitaria as $participacion)
                        <div>
                            <dt class="font-semibold text-slate-700 dark:text-slate-200">{{ $participacion->tipo_participacion }}</dt>
                            <dd>{{ $participacion->cantidad }} {{ $participacion->descripcion ? '· '.$participacion->descripcion : '' }}</dd>
                        </div>
                    @empty
                        <div class="text-slate-500">Sin participación registrada.</div>
                    @endforelse
                </dl>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <h2 class="mb-3 text-sm font-bold uppercase text-slate-500">Práctica de asignatura</h2>
                <dl class="space-y-2 text-sm">
                    @forelse ($accion->practicasAsignatura as $practica)
                        <div>
                            <dt class="font-semibold text-slate-700 dark:text-slate-200">{{ $practica->asignatura?->nombre ?? $practica->nombre_asignatura ?? 'Asignatura sin nombre' }}</dt>
                            <dd>{{ $practica->codigo_asignatura }} · {{ $practica->periodoAcademico?->nombre ?? $practica->periodo_academico_texto }} · Matrícula: {{ $practica->cantidad_estudiantes }}</dd>
                        </div>
                    @empty
                        <div class="text-slate-500">Sin prácticas registradas.</div>
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
                            <dd>{{ $firma->nombre_firmante ?: $firma->empleado?->nombre_completo ?: 'Sin nombre' }} · {{ $firma->estado_revision }}</dd>
                        </div>
                    @empty
                        <div class="text-slate-500">Sin firmas registradas.</div>
                    @endforelse
                </dl>
            </div>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <h2 class="mb-3 text-sm font-bold uppercase text-slate-500">Historial de revisión</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                    <thead>
                        <tr class="text-left text-xs uppercase text-slate-500">
                            <th class="px-3 py-2">Orden</th>
                            <th class="px-3 py-2">Etapa</th>
                            <th class="px-3 py-2">Rol</th>
                            <th class="px-3 py-2">Estado</th>
                            <th class="px-3 py-2">Observación</th>
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
