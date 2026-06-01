<div>
    @php
        $registro = $this->registro;
        $value = fn ($data) => filled($data) ? $data : 'No registrado';
        $bool = fn (bool $data) => $data ? 'Si' : 'No';
        $estadoBadge = match($registro->estado) {
            'borrador' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-200',
            'enviado' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
            'aprobado' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200',
            'rechazado' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
        };
        $puedeEnviarRevision = $registro->puedeEnviarse(auth()->id());
        $puedeEditar = $puedeEnviarRevision;
        $puedeRevisar = $registro->puedeRevisarse(auth()->id(), auth()->user());

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
            @if($puedeRevisar)
                <button type="button"
                        wire:click="aprobar"
                        wire:confirm="Desea aprobar este registro PPS / Servicio Social?"
                        wire:loading.attr="disabled"
                        wire:target="aprobar"
                        class="inline-flex items-center justify-center rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-70">
                    <span wire:loading.remove wire:target="aprobar">Aprobar</span>
                    <span wire:loading wire:target="aprobar">Aprobando...</span>
                </button>
                <button type="button"
                        wire:click="abrirModalRechazo"
                        class="inline-flex items-center justify-center rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 shadow-sm transition hover:bg-red-100 dark:border-red-900/60 dark:bg-red-900/30 dark:text-red-200 dark:hover:bg-red-900/50">
                    Rechazar
                </button>
            @endif
            <a href="{{ route('crearPpsServicioSocial') }}" wire:navigate
               class="inline-flex items-center justify-center rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800">
                Nuevo registro
            </a>
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
            <p class="font-semibold">Motivo de rechazo</p>
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

    @if($rechazoModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/60 p-4">
            <div class="w-full max-w-xl rounded-xl bg-white p-6 shadow-xl dark:bg-gray-900">
                <div class="mb-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-red-600 dark:text-red-300">Revision FORM-DVUS-015/016</p>
                    <h2 class="mt-1 text-xl font-bold text-gray-950 dark:text-white">Rechazar registro</h2>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Indique el motivo del rechazo. Este texto quedara visible en el detalle del registro.
                    </p>
                </div>

                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Motivo de rechazo <span class="text-red-500">*</span></label>
                <textarea wire:model="motivoRechazo"
                          rows="5"
                          class="mt-1 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-red-500 focus:ring-1 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"></textarea>
                @error('motivoRechazo')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror

                <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button"
                            wire:click="cerrarModalRechazo"
                            class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
                        Cancelar
                    </button>
                    <button type="button"
                            wire:click="rechazar"
                            wire:loading.attr="disabled"
                            wire:target="rechazar"
                            class="inline-flex items-center justify-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-70">
                        <span wire:loading.remove wire:target="rechazar">Confirmar rechazo</span>
                        <span wire:loading wire:target="rechazar">Rechazando...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
