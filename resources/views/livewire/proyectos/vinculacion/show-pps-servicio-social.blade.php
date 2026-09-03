<div class="space-y-6">
    @php
        use Carbon\Carbon;

        $registro = $this->registro;
        $estadoTexto = ($registro->estado === 'enviado' && $registro->etapaActual)
            ? $registro->etapaActual->nombre
            : ucfirst(str_replace('_', ' ', $registro->estado ?: 'sin estado'));
        $estadoBadge = match($registro->estado) {
            'borrador' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-200',
            'enviado' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
            'en_revision' => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/40 dark:text-cyan-200',
            'aprobado' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200',
            'rechazado' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
            'subsanacion' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
        };

        $puedeEnviarRevision = $registro->puedeEnviarse(auth()->id());
        $puedeEditar = $registro->perteneceAlUsuario(auth()->id())
            && in_array($registro->estado, ['borrador', 'subsanacion'], true)
            || (in_array($registro->estado, ['enviado', 'en_revision'], true)
                && $registro->usuarioPuedeRevisar(auth()->user()));
        $puedeRevisarEtapa = $registro->usuarioPuedeRevisar(auth()->user()) && $registro->estaEnRevision();
        $puedeAprobar = $puedeRevisarEtapa && $registro->puedeAprobarse(auth()->id(), auth()->user());
        $puedeRechazar = $puedeRevisarEtapa && $registro->puedeRechazarse(auth()->id(), auth()->user());
        $puedeSubsanar = $registro->puedeSubsanarse(auth()->id());
        $puedeDescargarPdf = $registro->puedeDescargarPdf(auth()->id(), auth()->user());
        $puedeCrearRegistro = (bool) auth()->user()?->can('docente.crear-proyecto');
    @endphp

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <a href="{{ route($historialRouteName) }}" wire:navigate
                   class="text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">
                    Volver al historial
                </a>
                <p class="mt-2 text-xs font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-300">
                    FORM-DVUS-014
                </p>
                <h1 class="mt-1 text-xl font-bold text-gray-900 dark:text-white">
                    {{ $registro->codigo_registro ?: 'Registro PPS/SS #'.$registro->id }}
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $registro->nombre_estudiante ?: 'Estudiante no registrado' }}
                    @if($registro->numero_cuenta)
                        · {{ $registro->numero_cuenta }}
                    @endif
                </p>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $estadoBadge }}">
                        {{ $estadoTexto }}
                    </span>
                    @if($registro->etapaActual && $registro->estado !== 'enviado')
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            Etapa: {{ $registro->etapaActual->nombre }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                @if($puedeEditar)
                    <a href="{{ route('pps-servicio-social.edit', $registro->id) }}" wire:navigate
                       class="inline-flex items-center rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-700 shadow-sm transition hover:bg-amber-100 dark:border-amber-900/60 dark:bg-amber-900/30 dark:text-amber-200 dark:hover:bg-amber-900/50">
                        Editar
                    </a>
                @endif

                @if($puedeEnviarRevision)
                    <button type="button"
                            x-on:click.prevent="confirmDialog('Al enviar este registro a revisión ya no podrá editarse. ¿Desea continuar?').then((ok) => ok && $wire.enviarRevision())"
                            wire:loading.attr="disabled"
                            wire:target="enviarRevision"
                            class="inline-flex items-center rounded-lg bg-green-600 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-70">
                        <span wire:loading.remove wire:target="enviarRevision">Enviar a revisión</span>
                        <span wire:loading wire:target="enviarRevision">Enviando...</span>
                    </button>
                @endif

                @if($puedeRechazar)
                    <button type="button"
                            wire:click="abrirModalSubsanacion"
                            class="inline-flex items-center rounded-lg bg-amber-600 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-amber-700">
                        Enviar a subsanación
                    </button>
                @endif

                @if($puedeAprobar)
                    <button type="button"
                            x-on:click.prevent="confirmDialog('¿Desea aprobar esta etapa del registro PPS / Servicio Social?').then((ok) => ok && $wire.aprobarEtapa())"
                            wire:loading.attr="disabled"
                            wire:target="aprobarEtapa"
                            class="inline-flex items-center rounded-lg bg-green-600 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-70">
                        <span wire:loading.remove wire:target="aprobarEtapa">Aprobar</span>
                        <span wire:loading wire:target="aprobarEtapa">Aprobando...</span>
                    </button>
                @endif

                @if($puedeSubsanar)
                    <button type="button"
                            x-on:click.prevent="confirmDialog('¿Desea iniciar la subsanación? El registro volverá a borrador para editarlo.').then((ok) => ok && $wire.iniciarSubsanacion())"
                            wire:loading.attr="disabled"
                            wire:target="iniciarSubsanacion"
                            class="inline-flex items-center rounded-lg bg-amber-600 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-amber-700 disabled:cursor-not-allowed disabled:opacity-70">
                        <span wire:loading.remove wire:target="iniciarSubsanacion">Iniciar subsanación</span>
                        <span wire:loading wire:target="iniciarSubsanacion">Abriendo...</span>
                    </button>
                @endif

                @if($puedeDescargarPdf)
                    <a href="{{ route('pps-servicio-social.pdf', $registro->id) }}"
                       class="inline-flex items-center rounded-lg bg-sky-600 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-sky-700">
                        Descargar PDF
                    </a>
                @endif

                @if($puedeCrearRegistro)
                    <a href="{{ route('crearPpsServicioSocial') }}" wire:navigate
                       class="inline-flex items-center rounded-lg bg-blue-700 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-800">
                        Nuevo registro
                    </a>
                @endif
            </div>
        </div>
    </div>

    @if($camposFaltantesEnvio !== [])
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 shadow-sm dark:border-amber-900/70 dark:bg-amber-950/30 dark:text-amber-200">
            <p class="font-semibold">Complete la información obligatoria antes de enviar a revisión.</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach($camposFaltantesEnvio as $campo)
                    <li>{{ $campo }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($registro->motivo_rechazo && in_array($registro->estado, ['rechazado', 'subsanacion'], true))
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 shadow-sm dark:border-red-900/70 dark:bg-red-950/30 dark:text-red-200">
            <p class="font-semibold">Observaciones de subsanación</p>
            <p class="mt-2 whitespace-pre-line">{{ $registro->motivo_rechazo }}</p>
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <section class="min-w-0 rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <div>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">Ficha FORM-DVUS-014</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Datos registrados para revisión.</p>
                </div>
            </div>

            @include('components.pps-servicio-social.form-014', [
                'registro' => $registro,
                'formData' => $formData ?? null,
                'isPdf' => false,
            ])
        </section>

        <aside class="space-y-6">
            <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <h2 class="mb-4 text-lg font-bold text-gray-900 dark:text-white">Historial de movimientos</h2>
                @if($movimientos->isNotEmpty())
                    <ol class="relative border-s border-yellow-600">
                        @foreach($movimientos as $movimiento)
                            @php
                                $estadoMovimiento = $movimiento->tipoestado?->nombre ?? 'Cambio de estado';
                                $comentarioMovimiento = trim((string) $movimiento->comentario);
                                $tipoMovimiento = match (true) {
                                    $estadoMovimiento === 'Rechazado' => 'Solicitud de subsanación',
                                    str_starts_with(mb_strtolower($comentarioMovimiento), 'edición de revisor:') => 'Edición por revisor',
                                    str_contains(mb_strtolower($comentarioMovimiento), 'inicio de subsanación') => 'Inicio de subsanación',
                                    str_contains(mb_strtolower($comentarioMovimiento), 'reenvío posterior') => 'Reenvío posterior a subsanación',
                                    $estadoMovimiento === 'Aprobado' => 'Aprobación de etapa',
                                    str_contains(mb_strtolower($comentarioMovimiento), 'enviado a revisión') => 'Envío a revisión',
                                    default => 'Cambio de estado',
                                };
                            @endphp
                            <li class="mb-6 ms-4">
                                <div class="absolute -start-1.5 mt-1.5 h-3 w-3 rounded-full border border-white bg-yellow-600"></div>
                                <time class="text-xs text-yellow-700 dark:text-yellow-400">{{ optional($movimiento->created_at)->format('d/m/Y H:i') }}</time>
                                <h3 class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-200">Estado: {{ $estadoMovimiento }}</h3>
                                <p class="text-xs font-medium text-blue-700 dark:text-blue-300">Tipo: {{ $tipoMovimiento }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Etapa: {{ $registro->etapaActual?->nombre ?? 'No registrada' }}
                                    @if($movimiento->empleado) · Responsable: {{ $movimiento->empleado->nombre_completo }} @endif
                                </p>
                                @if($comentarioMovimiento !== '')
                                    @php
                                        $comentarioVisible = str_starts_with(mb_strtolower($comentarioMovimiento), 'edición de revisor:')
                                            ? trim(mb_substr($comentarioMovimiento, mb_strlen('Edición de revisor:')))
                                            : $comentarioMovimiento;
                                    @endphp
                                    <p class="mt-1 whitespace-pre-line break-words text-sm text-gray-600 dark:text-gray-300">{{ in_array($tipoMovimiento, ['Solicitud de subsanación', 'Edición por revisor'], true) ? 'Observación: ' : '' }}{{ $comentarioVisible }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">No hay movimientos registrados.</p>
                @endif
            </section>

            @if($registro->documentosGenerados->isNotEmpty())
                <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <h2 class="mb-3 text-lg font-bold text-gray-900 dark:text-white">Documentos generados</h2>
                    <div class="space-y-2">
                        @foreach($registro->documentosGenerados->sortByDesc('generado_en') as $documento)
                            <a class="flex items-center justify-between rounded-md border border-gray-200 px-3 py-2 text-sm text-blue-700 hover:bg-blue-50 dark:border-gray-700 dark:text-blue-300" href="{{ route('pps-servicio-social.documento-generado', $documento) }}">
                                <span>{{ $documento->tipo === 'solicitud_practica' ? 'Solicitud de práctica' : 'Autorización de PPS' }} · v{{ $documento->version }}</span>
                                <span>Descargar</span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <h2 class="mb-4 text-lg font-bold text-gray-900 dark:text-white">
                    Anexos
                </h2>

                @if(count($anexos) > 0)
                    <div class="space-y-3">
                        @foreach($anexos as $anexo)
                            <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $anexo['titulo'] }}</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $anexo['archivo'] ?: ($anexo['marcado'] ? 'Marcado como adjunto, sin archivo registrado' : 'Sin archivo') }}
                                </p>

                                @if($anexo['exists'])
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <a href="{{ $anexo['view_url'] }}" target="_blank" rel="noopener"
                                           class="inline-flex items-center rounded-md border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-100 dark:border-blue-900/60 dark:bg-blue-900/30 dark:text-blue-200 dark:hover:bg-blue-900/50">
                                            Ver anexo
                                        </a>
                                        <a href="{{ $anexo['download_url'] }}"
                                           class="inline-flex items-center rounded-md border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                            Descargar anexo
                                        </a>
                                    </div>
                                @else
                                    <p class="mt-2 text-xs text-amber-600 dark:text-amber-300">
                                        No hay archivo disponible para abrir o descargar.
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        No hay anexos registrados.
                    </p>
                @endif
            </section>
        </aside>
    </div>

    @if($subsanarModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-lg rounded-xl bg-white shadow-xl dark:bg-gray-800">
                <div class="flex items-center justify-between border-b border-gray-200 p-4 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Enviar a subsanación</h3>
                    <button type="button"
                            wire:click="cerrarModalSubsanacion"
                            class="text-2xl leading-none text-gray-400 hover:text-gray-600">
                        &times;
                    </button>
                </div>

                <div class="space-y-4 p-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Correcciones requeridas <span class="text-red-500">*</span>
                        </label>
                        <textarea wire:model="subsanarComentario"
                                  rows="5"
                                  class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-amber-500 focus:ring-1 focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"></textarea>
                        @error('subsanarComentario')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button"
                                wire:click="cerrarModalSubsanacion"
                                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                            Cancelar
                        </button>
                        <button type="button"
                                wire:click="enviarASubsanar"
                                wire:loading.attr="disabled"
                                wire:target="enviarASubsanar"
                                class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-amber-700 disabled:cursor-not-allowed disabled:opacity-70">
                            <span wire:loading.remove wire:target="enviarASubsanar">Subsanar</span>
                            <span wire:loading wire:target="enviarASubsanar">Enviando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
