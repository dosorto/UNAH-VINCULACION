<div>
    @php
        $registro = $this->registro;
        $value = fn ($data) => filled($data) ? $data : 'No registrado';
        $bool = fn (bool $data) => $data ? 'Si' : 'No';
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
        $puedeEditar = $puedeEnviarRevision;
        $puedeRevisarEtapa = $registro->usuarioPuedeRevisar(auth()->user()) && $registro->estaEnRevision();
        $puedeAprobar = $puedeRevisarEtapa && $registro->puedeAprobarse(auth()->id(), auth()->user());
        $puedeRechazar = $puedeRevisarEtapa && $registro->puedeRechazarse(auth()->id(), auth()->user());
        $puedeSubsanar = $registro->puedeSubsanarse(auth()->id());
        $puedeDescargarPdf = $registro->puedeDescargarPdf(auth()->id(), auth()->user());
        $puedeCrearRegistro = (bool) auth()->user()?->can('docente.crear-proyecto');

        $sections = [
            [
                'title' => '1. Informacion general',
                'items' => [
                    ['Facultad / Centro', $registro->facultad_centro],
                    ['Carrera', $registro->carrera],
                ],
            ],
            [
                'title' => '2. Datos del estudiante',
                'items' => [
                    ['Numero de cuenta', $registro->numero_cuenta],
                    ['Nombre completo', $registro->nombre_estudiante],
                    ['Celular', $registro->celular_estudiante],
                    ['Correo institucional', $registro->correo_institucional],
                    ['Correo personal', $registro->correo_personal],
                ],
            ],
            [
                'title' => '3. Informacion de la PPS / Servicio Social',
                'items' => [
                    ['Tipo', $registro->tipo_pps_ss],
                    ['Fecha de inicio', $registro->fecha_inicio?->format('d/m/Y')],
                    ['Fecha de finalizacion', $registro->fecha_finalizacion?->format('d/m/Y')],
                    ['Tipo de instrumento', $registro->tipo_instrumento],
                    ['Territorio de ejecucion', $registro->territorio_ejecucion],
                ],
            ],
            [
                'title' => '4. Datos territoriales',
                'items' => [
                    ['Departamento', $registro->departamento],
                    ['Municipio', $registro->municipio],
                    ['Aldea / ciudad', $registro->aldea_ciudad],
                    ['Caserio', $registro->caserio],
                ],
            ],
            [
                'title' => '5. Alcance de la PPS / Servicio Social',
                'items' => [
                    ['Descripcion del tipo de PPS', $registro->descripcion_tipo_pps],
                    ['Total de horas', $registro->total_horas],
                    ['Departamento o area', $registro->area_realizacion],
                    ['Responsabilidades y tareas', $registro->resumen_responsabilidades],
                    ['Modalidad', $registro->modalidad_ejecucion],
                ],
            ],
            [
                'title' => '6. Institucion / Organizacion',
                'items' => [
                    ['Nombre', $registro->nombre_institucion],
                    ['Compromisos asumidos', $registro->compromisos_institucion],
                    ['Direccion exacta', $registro->direccion_institucion],
                    ['Representante legal', $registro->representante_legal],
                    ['Telefono', $registro->telefono_representante],
                    ['Correo de RRHH', $registro->correo_rrhh],
                    ['Tipo de institucion', $registro->tipo_institucion],
                    ['Sector', $registro->sector_institucion],
                ],
            ],
            [
                'title' => '7. Jefe directo de la PPS / SS',
                'items' => [
                    ['Nombre completo', $registro->nombre_jefe_directo],
                    ['Celular', $registro->celular_jefe_directo],
                    ['Correo electronico', $registro->correo_jefe_directo],
                    ['Cargo', $registro->cargo_jefe_directo],
                    ['Grado academico', $registro->grado_academico_jefe_directo],
                ],
            ],
            [
                'title' => '8. Docente supervisor',
                'items' => [
                    ['Nombre completo', $registro->nombre_docente_supervisor],
                    ['Numero de empleado', $registro->numero_empleado_docente],
                    ['Celular', $registro->celular_docente],
                    ['Correo electronico', $registro->correo_docente],
                    ['Categoria', $registro->categoria_docente],
                    ['Departamento', $registro->departamento_docente],
                    ['Jornada laboral', $registro->jornada_laboral_docente],
                    ['Ubicacion del cubiculo', $registro->ubicacion_cubiculo_docente],
                ],
            ],
            [
                'title' => '9. Documentos adjuntos',
                'items' => [
                    ['Carta de formalizacion marcada', $bool($registro->adjunta_carta_formalizacion)],
                    ['Archivo carta de formalizacion', $registro->archivo_carta_formalizacion ? basename($registro->archivo_carta_formalizacion) : null],
                    ['Convenio marco marcado', $bool($registro->adjunta_convenio_marco)],
                    ['Archivo convenio marco', $registro->archivo_convenio_marco ? basename($registro->archivo_convenio_marco) : null],
                ],
            ],
        ];
    @endphp

    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-300">FORM-DVUS-015/016</p>
            <h1 class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">
                Detalle PPS / Servicio Social
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Registro {{ $registro->codigo_registro ?: '#' . $registro->id }} creado el {{ $registro->created_at?->format('d/m/Y H:i') ?? 'No registrado' }}.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('pps-servicio-social.index') }}" wire:navigate
               class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
                Volver al listado
            </a>
            @if($puedeEditar)
                <a href="{{ route('pps-servicio-social.edit', $registro->id) }}" wire:navigate
                   class="inline-flex items-center justify-center rounded-lg border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-700 shadow-sm transition hover:bg-amber-100 dark:border-amber-900/60 dark:bg-amber-900/30 dark:text-amber-200 dark:hover:bg-amber-900/50">
                    Editar
                </a>
            @endif
            @if($puedeEnviarRevision)
                <button type="button"
                        wire:click="enviarRevision"
                        wire:confirm="Al enviar este registro a revision ya no podra editarse. Desea continuar?"
                        wire:loading.attr="disabled"
                        wire:target="enviarRevision"
                        class="inline-flex items-center justify-center rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-70">
                    <span wire:loading.remove wire:target="enviarRevision">Enviar a revision</span>
                    <span wire:loading wire:target="enviarRevision">Enviando...</span>
                </button>
            @endif
            @if($puedeAprobar)
                @if($puedeRechazar)
                    <button type="button"
                            wire:click="abrirModalSubsanacion"
                            class="inline-flex items-center gap-1 rounded-lg bg-amber-600 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-amber-700">
                        Enviar a subsanacion
                    </button>
                @endif
                <button type="button"
                        wire:click="aprobarEtapa"
                        wire:confirm="Desea aprobar esta etapa del registro PPS / Servicio Social?"
                        wire:loading.attr="disabled"
                        wire:target="aprobarEtapa"
                        class="inline-flex items-center gap-1 rounded-lg bg-green-600 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-70">
                    <span wire:loading.remove wire:target="aprobarEtapa">Aprobar</span>
                    <span wire:loading wire:target="aprobarEtapa">Aprobando...</span>
                </button>
            @elseif($puedeRechazar)
                <button type="button"
                        wire:click="abrirModalSubsanacion"
                        class="inline-flex items-center gap-1 rounded-lg bg-amber-600 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-amber-700">
                    Enviar a subsanacion
                </button>
            @endif
            @if($puedeSubsanar)
                <button type="button"
                        wire:click="iniciarSubsanacion"
                        wire:confirm="Desea iniciar la subsanacion? El registro volvera a borrador para editarlo."
                        wire:loading.attr="disabled"
                        wire:target="iniciarSubsanacion"
                        class="inline-flex items-center justify-center rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-700 disabled:cursor-not-allowed disabled:opacity-70">
                    <span wire:loading.remove wire:target="iniciarSubsanacion">Subsanar</span>
                    <span wire:loading wire:target="iniciarSubsanacion">Abriendo...</span>
                </button>
            @endif
            @if($puedeDescargarPdf)
                <a href="{{ route('pps-servicio-social.pdf', $registro->id) }}"
                   class="inline-flex items-center justify-center rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-900 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white">
                    Descargar PDF
                </a>
            @endif
            @if($puedeCrearRegistro)
                <a href="{{ route('crearPpsServicioSocial') }}" wire:navigate
                   class="inline-flex items-center justify-center rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800">
                    Nuevo registro
                </a>
            @endif
        </div>
    </div>

    @if($camposFaltantesEnvio !== [])
        <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 shadow-sm dark:border-amber-900/70 dark:bg-amber-950/30 dark:text-amber-200">
            <p class="font-semibold">Complete la informacion obligatoria antes de enviar a revision.</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach($camposFaltantesEnvio as $campo)
                    <li>{{ $campo }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($registro->estado === 'rechazado')
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 shadow-sm dark:border-red-900/70 dark:bg-red-950/30 dark:text-red-200">
            <p class="font-semibold">Este registro fue devuelto para subsanacion.</p>
            <p class="mt-1">Revise el motivo y realice las correcciones necesarias.</p>
        </div>
    @endif

    <div class="mb-5 rounded-xl border border-blue-100 bg-gradient-to-r from-blue-50 to-white p-5 shadow-sm dark:border-blue-900/50 dark:from-blue-950/40 dark:to-gray-900">
        <div class="grid gap-4 md:grid-cols-4">
            <div class="md:col-span-2">
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-300">Estudiante</p>
                <p class="mt-1 text-xl font-bold text-gray-950 dark:text-white">{{ $registro->nombre_estudiante }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $registro->numero_cuenta }} · {{ $registro->correo_institucional }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Institucion</p>
                <p class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $registro->nombre_institucion }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Estado</p>
                <span class="mt-2 inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $estadoBadge }}">
                    {{ ucfirst($registro->estado ?: 'sin estado') }}
                </span>
                @if($registro->fecha_envio)
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Enviado: {{ $registro->fecha_envio->format('d/m/Y H:i') }}
                    </p>
                    @if($registro->enviado_por)
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Enviado por ID: {{ $registro->enviado_por }}
                        </p>
                    @endif
                @endif
                @if($registro->fecha_revision)
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Revisado: {{ $registro->fecha_revision->format('d/m/Y H:i') }}
                    </p>
                    @if($registro->revisado_por)
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Revisado por ID: {{ $registro->revisado_por }}
                        </p>
                    @endif
                @endif
            </div>
        </div>
    </div>

    @if($registro->motivo_rechazo)
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 shadow-sm dark:border-red-900/70 dark:bg-red-950/30 dark:text-red-200">
            <p class="font-semibold">Observaciones de subsanacion</p>
            <p class="mt-2 whitespace-pre-line">{{ $registro->motivo_rechazo }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
        @foreach($sections as $section)
            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <h2 class="mb-4 text-base font-bold text-gray-900 dark:text-white">{{ $section['title'] }}</h2>
                <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @foreach($section['items'] as [$label, $data])
                        <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800/70">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                            <dd class="mt-1 whitespace-pre-line text-sm text-gray-900 dark:text-gray-100">{{ $value($data) }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        @endforeach
    </div>

    @if($subsanarModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-lg rounded-xl bg-white shadow-xl dark:bg-gray-800">
                <div class="flex items-center justify-between border-b border-gray-200 p-4 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Enviar a subsanacion</h3>
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
