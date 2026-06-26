<div>
    @php
        $stepLabels = [
            1 => 'Información general',
            2 => 'Estudiante',
            3 => 'PPS / SS',
            4 => 'Territorio',
            5 => 'Alcance',
            6 => 'Institución',
            7 => 'Jefe directo',
            8 => 'Supervisor',
            9 => 'Adjuntos',
        ];

        $inputClass = 'w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500';
        $labelClass = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1';
        $errorClass = 'text-red-500 text-xs mt-1';
        $cardClass = 'rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5 shadow-sm';
        $modoEdicion = $modoEdicion ?? false;
        $registroEdicion = $registroEdicion ?? null;
        $fechaRegistroVisible = ($modoEdicion && $registroEdicion?->created_at)
            ? $registroEdicion->created_at->format('d/m/Y H:i')
            : now()->format('d/m/Y');
        $modalidadActual = $this->modalidadEtiqueta($modalidad_ejecucion);
        $hayDatosTeletrabajo = filled($pais_sede_principal)
            || filled($departamento_provincia_sede_principal)
            || filled($municipio_sede_principal)
            || filled($aldea_ciudad_sede_principal)
            || filled($horas_teletrabajo);
        $mostrarHorasPresenciales = !filled($modalidad_ejecucion)
            || in_array($modalidadActual, ['100% presencial', 'Híbrida'], true)
            || filled($horas_presenciales);
        $mostrarBloqueTerritorial = true;
        $mostrarTeletrabajo = !filled($modalidad_ejecucion)
            || in_array($modalidadActual, ['Teletrabajo', 'Híbrida'], true)
            || $hayDatosTeletrabajo;
        $paisResumen = $territorio_ejecucion === 'Nacional' ? 'Honduras' : $pais;
        $departamentoLabelResumen = $territorio_ejecucion === 'Nacional' ? 'Departamento' : 'Departamento / provincia';
        $departamentoProvinciaResumen = $territorio_ejecucion === 'Nacional'
            ? ($departamentos[$departamento_id] ?? null)
            : $departamento_provincia;
        $municipioResumen = $territorio_ejecucion === 'Nacional'
            ? ($municipios[$municipio_id] ?? null)
            : $municipio_texto;
        $aldeaCiudadResumen = $aldea_ciudad ?: collect([$aldea, $ciudad])->filter()->implode(' / ');
    @endphp

    <div class="mb-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-300">FORM-DVUS-014</p>
        <h1 class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">
            {{ $modoEdicion ? 'Editar Registro de Práctica Profesional Supervisada o Servicio Social' : 'Registro de Práctica Profesional Supervisada o Servicio Social' }}
        </h1>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            {{ $modoEdicion ? 'Actualice la información del borrador antes de enviarlo a revisión.' : 'Complete la información del FORM-DVUS-014 para guardarla como borrador.' }}
        </p>
    </div>

    @if($modoEdicion && $registroEdicion && filled($registroEdicion->motivo_rechazo))
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-5 text-sm text-red-800 shadow-sm dark:border-red-900/70 dark:bg-red-950/30 dark:text-red-200">
            <p class="font-semibold">Este registro fue rechazado anteriormente.</p>
            <p class="mt-1">Corrija las observaciones indicadas antes de reenviarlo a revisión.</p>

            @if($registroEdicion->fecha_revision)
                <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-red-700 dark:text-red-300">
                    Fecha de revisión: {{ $registroEdicion->fecha_revision->format('d/m/Y H:i') }}
                </p>
            @endif

            <div class="mt-3 rounded-lg border border-red-200 bg-white/70 p-3 dark:border-red-900/60 dark:bg-gray-900/60">
                <p class="text-xs font-semibold uppercase tracking-wide text-red-700 dark:text-red-300">Motivo de rechazo</p>
                <p class="mt-1 whitespace-pre-line text-sm">{{ $registroEdicion->motivo_rechazo }}</p>
            </div>
        </div>
    @endif

    <div class="mb-6 rounded-lg bg-white p-4 shadow dark:bg-gray-900">
        <div class="flex items-center overflow-x-auto gap-0.5">
            @foreach($stepLabels as $step => $label)
                @php
                    $complete = $this->isStepComplete($step);
                    $accessible = $this->canAccessStep($step);
                    $showComplete = $this->shouldShowStepComplete($step);
                @endphp
                <button wire:click="goToStep({{ $step }})" type="button"
                    aria-disabled="{{ $accessible ? 'false' : 'true' }}"
                    class="group flex min-w-[44px] flex-1 flex-col items-center rounded-md p-1 transition hover:bg-gray-50 dark:hover:bg-white/5 {{ $accessible ? 'cursor-pointer' : 'cursor-not-allowed opacity-60' }}">
                    <span class="mb-1 flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold transition-colors
                        {{ $currentStep === $step
                            ? 'bg-blue-600 text-white ring-2 ring-blue-300'
                            : ($showComplete
                                ? 'bg-green-500 text-white'
                                : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-400') }}">
                        @if($showComplete)
                            &#10003;
                        @else
                            {{ $step }}
                        @endif
                    </span>
                    <span class="hidden text-center text-[10px] leading-tight sm:block
                        {{ $currentStep === $step ? 'font-semibold text-blue-600' : ($showComplete ? 'text-green-600 dark:text-green-400' : 'text-gray-500') }}">
                        {{ $label }}
                    </span>
                </button>

                @if($step < count($stepLabels))
                    <div class="h-0.5 w-3 shrink-0 {{ $showComplete ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-700' }}"></div>
                @endif
            @endforeach
        </div>

        <div class="mt-3 flex min-h-5 justify-end text-xs font-medium">
            @if($estadoAutoGuardado === 'guardando')
                <span class="text-gray-500 dark:text-gray-400">Guardando...</span>
            @elseif($estadoAutoGuardado === 'guardado')
                <span class="text-green-600 dark:text-green-400">Guardado</span>
            @elseif($estadoAutoGuardado === 'error')
                <span class="text-red-600 dark:text-red-400">Error al guardar</span>
            @endif
        </div>
    </div>

    <form wire:submit.prevent="guardar" class="rounded-lg bg-white p-6 shadow dark:bg-gray-900">
        @if($currentStep === 1)
            <section>
                <h2 class="mb-5 text-lg font-semibold text-gray-900 dark:text-white">Paso 1: Información general</h2>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="{{ $labelClass }}">Fecha de registro</label>
                        <input type="text" value="{{ $fechaRegistroVisible }}" readonly class="{{ $inputClass }} cursor-not-allowed bg-gray-50 text-gray-600 dark:bg-gray-800/70 dark:text-gray-300">
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Facultad / Centro Universitario Regional / Instituto Tecnológico <span class="text-red-500">*</span></label>
                        <select wire:model.live="facultad_centro_id" class="{{ $inputClass }}">
                            <option value="">Seleccione...</option>
                            @foreach($facultadesCentros as $id => $nombre)
                                <option value="{{ $id }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                        @error('facultad_centro_id') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Carrera <span class="text-red-500">*</span></label>
                        <select wire:model="carrera_id" class="{{ $inputClass }}" @disabled(!$facultad_centro_id || $carreras->isEmpty())>
                            <option value="">Seleccione...</option>
                            @foreach($carreras as $id => $nombre)
                                <option value="{{ $id }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                        @if(!$facultad_centro_id)
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Seleccione primero una facultad o centro.</p>
                        @elseif($carreras->isEmpty())
                            <p class="mt-1 text-xs text-amber-600">No hay carreras disponibles para la selección actual.</p>
                        @endif
                        @error('carrera_id') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>
        @endif

        @if($currentStep === 2)
            <section>
                <h2 class="mb-5 text-lg font-semibold text-gray-900 dark:text-white">Paso 2: Datos del estudiante</h2>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="{{ $labelClass }}">Número de cuenta <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="numero_cuenta" class="{{ $inputClass }}">
                        @error('numero_cuenta') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Nombre completo <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="estudiante_nombre_completo" class="{{ $inputClass }}">
                        @error('estudiante_nombre_completo') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Número de celular <span class="text-red-500">*</span></label>
                        <input type="tel" wire:model="estudiante_celular" class="{{ $inputClass }}">
                        @error('estudiante_celular') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Correo electrónico institucional <span class="text-red-500">*</span></label>
                        <input type="email" wire:model="estudiante_correo_institucional" class="{{ $inputClass }}">
                        @error('estudiante_correo_institucional') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="{{ $labelClass }}">Correo electrónico personal</label>
                        <input type="email" wire:model="estudiante_correo_personal" class="{{ $inputClass }}">
                        @error('estudiante_correo_personal') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>
        @endif

        @if($currentStep === 3)
            <section>
                <h2 class="mb-5 text-lg font-semibold text-gray-900 dark:text-white">Paso 3: Información de la Práctica Profesional / Servicio Social</h2>

                <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                    <div class="{{ $cardClass }}">
                        <p class="mb-3 text-sm font-semibold text-gray-800 dark:text-gray-100">Tipo <span class="text-red-500">*</span></p>
                        <div class="space-y-2">
                            @foreach($tipoPpsOpciones as $value => $label)
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input type="radio" wire:model="tipo_pps_ss" value="{{ $value }}" class="text-blue-600 focus:ring-blue-500">
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('tipo_pps_ss') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div class="{{ $cardClass }}">
                        <p class="mb-3 text-sm font-semibold text-gray-800 dark:text-gray-100">Territorio de ejecución <span class="text-red-500">*</span></p>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                            @foreach(['Nacional', 'Internacional'] as $territorio)
                                <label class="flex items-center gap-2 rounded-md border border-gray-200 p-3 text-sm dark:border-gray-700">
                                    <input type="radio" wire:model.live="territorio_ejecucion" value="{{ $territorio }}" class="text-blue-600 focus:ring-blue-500">
                                    <span class="text-gray-700 dark:text-gray-300">{{ $territorio }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('territorio_ejecucion') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Fecha de inicio <span class="text-red-500">*</span></label>
                        <input type="date" wire:model="fecha_inicio" class="{{ $inputClass }}">
                        @error('fecha_inicio') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Fecha de finalización <span class="text-red-500">*</span></label>
                        <input type="date" wire:model="fecha_finalizacion" class="{{ $inputClass }}">
                        @error('fecha_finalizacion') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div class="lg:col-span-2">
                        <label class="{{ $labelClass }}">Tipo de instrumento que formaliza la PPS / SS <span class="text-red-500">*</span></label>
                        <select wire:model="tipo_instrumento" class="{{ $inputClass }}">
                            <option value="">Seleccione...</option>
                            @foreach($instrumentoOpciones as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('tipo_instrumento') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>
        @endif

        @if($currentStep === 4)
            <section>
                <h2 class="mb-5 text-lg font-semibold text-gray-900 dark:text-white">Paso 4: Datos territoriales de la PPS / Servicio Social</h2>

                <div class="{{ $cardClass }} mb-5">
                    <p class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Modalidad <span class="text-red-500">*</span></p>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                        @foreach($modalidadOpciones as $value => $label)
                            <label class="flex items-center gap-2 rounded-md border border-gray-200 p-3 text-sm dark:border-gray-700">
                                <input type="radio" wire:model="modalidad_ejecucion" value="{{ $value }}" class="text-blue-600 focus:ring-blue-500">
                                <span class="text-gray-700 dark:text-gray-300">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('modalidad_ejecucion') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-5">
                    @if($mostrarBloqueTerritorial)
                        <div class="{{ $cardClass }}">
                            <h3 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">Práctica presencial</h3>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label class="{{ $labelClass }}">Región</label>
                                    <input type="text" wire:model="region" maxlength="255" class="{{ $inputClass }}">
                                    @error('region') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="{{ $labelClass }}">País</label>
                                    @if($territorio_ejecucion === 'Nacional')
                                        <input type="text" value="Honduras" readonly class="{{ $inputClass }} cursor-not-allowed bg-gray-50 text-gray-600 dark:bg-gray-800/70 dark:text-gray-300">
                                    @else
                                        <input type="text" wire:model="pais" maxlength="255" class="{{ $inputClass }}">
                                        @error('pais') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                                    @endif
                                </div>

                                @if($territorio_ejecucion === 'Nacional')
                                    <div>
                                        <label class="{{ $labelClass }}">Departamento <span class="text-red-500">*</span></label>
                                        <select wire:model.live="departamento_id" class="{{ $inputClass }}">
                                            <option value="">Seleccione...</option>
                                            @foreach($departamentos as $id => $nombre)
                                                <option value="{{ $id }}">{{ $nombre }}</option>
                                            @endforeach
                                        </select>
                                        @error('departamento_id') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <label class="{{ $labelClass }}">Municipio <span class="text-red-500">*</span></label>
                                        <select wire:model.live="municipio_id" class="{{ $inputClass }}" @disabled(!$departamento_id || $municipios->isEmpty())>
                                            <option value="">Seleccione...</option>
                                            @foreach($municipios as $id => $nombre)
                                                <option value="{{ $id }}">{{ $nombre }}</option>
                                            @endforeach
                                        </select>
                                        @error('municipio_id') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                                    </div>
                                @else
                                    <div>
                                        <label class="{{ $labelClass }}">Departamento / provincia</label>
                                        <input type="text" wire:model="departamento_provincia" maxlength="255" class="{{ $inputClass }}">
                                        @error('departamento_provincia') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <label class="{{ $labelClass }}">Municipio</label>
                                        <input type="text" wire:model="municipio_texto" maxlength="255" class="{{ $inputClass }}">
                                        @error('municipio_texto') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                                    </div>
                                @endif

                                <div>
                                    <label class="{{ $labelClass }}">Aldea / ciudad</label>
                                    <input type="text" wire:model.live.debounce.500ms="aldea_ciudad" maxlength="255" class="{{ $inputClass }}">
                                    @error('aldea_ciudad') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="{{ $labelClass }}">Caserío</label>
                                    <input type="text" wire:model="caserio" maxlength="255" class="{{ $inputClass }}">
                                    @error('caserio') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($mostrarTeletrabajo)
                        <div class="{{ $cardClass }}">
                            <h3 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">Práctica en modalidad teletrabajo</h3>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label class="{{ $labelClass }}">País de la sede principal</label>
                                    <input type="text" wire:model="pais_sede_principal" maxlength="255" class="{{ $inputClass }}">
                                    @error('pais_sede_principal') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="{{ $labelClass }}">Departamento / provincia sede principal</label>
                                    <input type="text" wire:model="departamento_provincia_sede_principal" maxlength="255" class="{{ $inputClass }}">
                                    @error('departamento_provincia_sede_principal') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="{{ $labelClass }}">Municipio sede principal</label>
                                    <input type="text" wire:model="municipio_sede_principal" maxlength="255" class="{{ $inputClass }}">
                                    @error('municipio_sede_principal') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="{{ $labelClass }}">Aldea / ciudad sede principal</label>
                                    <input type="text" wire:model="aldea_ciudad_sede_principal" maxlength="255" class="{{ $inputClass }}">
                                    @error('aldea_ciudad_sede_principal') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="{{ $cardClass }}">
                        <h3 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">Distribución de la jornada</h3>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            @if($mostrarHorasPresenciales)
                                <div>
                                    <label class="{{ $labelClass }}">Horas presenciales</label>
                                    <input type="number" min="0" wire:model="horas_presenciales" class="{{ $inputClass }}">
                                    @error('horas_presenciales') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                                </div>
                            @endif

                            @if($mostrarTeletrabajo)
                                <div>
                                    <label class="{{ $labelClass }}">Horas teletrabajo</label>
                                    <input type="number" min="0" wire:model="horas_teletrabajo" class="{{ $inputClass }}">
                                    @error('horas_teletrabajo') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </section>
        @endif

        @if($currentStep === 5)
            <section>
                <h2 class="mb-5 text-lg font-semibold text-gray-900 dark:text-white">Paso 5: Alcance de la PPS / Servicio Social</h2>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="{{ $labelClass }}">Descripción del tipo de PPS</label>
                        <textarea wire:model="descripcion_tipo_pps" rows="3" class="{{ $inputClass }}"></textarea>
                        @error('descripcion_tipo_pps') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="{{ $labelClass }}">Descripción de las horas del tipo de PPS/SS</label>
                        <textarea wire:model="descripcion_horas_tipo_pps_ss" rows="3" class="{{ $inputClass }}"></textarea>
                        @error('descripcion_horas_tipo_pps_ss') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Total de horas <span class="text-red-500">*</span></label>
                        <input type="number" min="1" wire:model="total_horas" class="{{ $inputClass }}">
                        @error('total_horas') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Departamento o área donde se realizará</label>
                        <input type="text" wire:model="area_realizacion" class="{{ $inputClass }}">
                        @error('area_realizacion') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="{{ $labelClass }}">Resumen de responsabilidades y tareas</label>
                        <textarea wire:model="resumen_responsabilidades" rows="4" class="{{ $inputClass }}"></textarea>
                        @error('resumen_responsabilidades') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>
        @endif

        @if($currentStep === 6)
            <section>
                <h2 class="mb-5 text-lg font-semibold text-gray-900 dark:text-white">Paso 6: Información de la institución / empresa</h2>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="{{ $labelClass }}">Nombre completo de la institución / organización <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="institucion_nombre" class="{{ $inputClass }}">
                        @error('institucion_nombre') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Nacionalidad</label>
                        <select wire:model="institucion_nacionalidad" class="{{ $inputClass }}">
                            <option value="">Seleccione...</option>
                            @foreach($institucionNacionalidadOpciones as $opcion)
                                <option value="{{ $opcion }}">{{ $opcion }}</option>
                            @endforeach
                        </select>
                        @error('institucion_nacionalidad') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">País</label>
                        <input type="text" wire:model="institucion_pais" maxlength="255" class="{{ $inputClass }}">
                        @error('institucion_pais') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="{{ $labelClass }}">Compromisos asumidos</label>
                        <textarea wire:model="institucion_compromisos" rows="3" class="{{ $inputClass }}"></textarea>
                        @error('institucion_compromisos') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="{{ $labelClass }}">Dirección exacta</label>
                        <textarea wire:model="institucion_direccion" rows="2" class="{{ $inputClass }}"></textarea>
                        @error('institucion_direccion') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Representante legal</label>
                        <input type="text" wire:model="institucion_representante" class="{{ $inputClass }}">
                        @error('institucion_representante') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Teléfono</label>
                        <input type="tel" wire:model="institucion_telefono" class="{{ $inputClass }}">
                        @error('institucion_telefono') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Correo de recursos humanos</label>
                        <input type="email" wire:model="institucion_correo_rrhh" class="{{ $inputClass }}">
                        @error('institucion_correo_rrhh') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Tipo de institución / organización</label>
                        <select wire:model="institucion_tipo" class="{{ $inputClass }}">
                            <option value="">Seleccione...</option>
                            @foreach($tipoInstitucionOpciones as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('institucion_tipo') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="{{ $labelClass }}">Sector</label>
                        <select wire:model="institucion_sector" class="{{ $inputClass }}">
                            <option value="">Seleccione...</option>
                            @foreach($sectorOpciones as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('institucion_sector') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>
        @endif

        @if($currentStep === 7)
            <section>
                <h2 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">Paso 7: Información del jefe directo</h2>
                <p class="mb-5 text-sm text-gray-500 dark:text-gray-400">Datos asociados a la institución / empresa donde se realiza la PPS / Servicio Social.</p>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="{{ $labelClass }}">Nombre completo <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="jefe_directo_nombre" class="{{ $inputClass }}">
                        @error('jefe_directo_nombre') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Número de celular</label>
                        <input type="tel" wire:model="jefe_directo_celular" class="{{ $inputClass }}">
                        @error('jefe_directo_celular') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Correo electrónico</label>
                        <input type="email" wire:model="jefe_directo_correo" class="{{ $inputClass }}">
                        @error('jefe_directo_correo') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Cargo</label>
                        <input type="text" wire:model="jefe_directo_cargo" class="{{ $inputClass }}">
                        @error('jefe_directo_cargo') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Grado académico</label>
                        <input type="text" wire:model="jefe_directo_grado" class="{{ $inputClass }}">
                        @error('jefe_directo_grado') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>
        @endif

        @if($currentStep === 8)
            <section>
                <h2 class="mb-5 text-lg font-semibold text-gray-900 dark:text-white">Paso 8: Información del docente supervisor</h2>

                <div class="mb-4">
                    <label class="{{ $labelClass }}">Seleccionar docente registrado</label>
                    <select wire:model.live="docente_supervisor_id" class="{{ $inputClass }}">
                        <option value="">Llenar manualmente o seleccionar...</option>
                        @foreach($docentes as $docente)
                            <option value="{{ $docente->id }}">
                                {{ $docente->nombre_completo }} @if($docente->numero_empleado) ({{ $docente->numero_empleado }}) @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="{{ $labelClass }}">Nombre completo <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="docente_supervisor_nombre" class="{{ $inputClass }}">
                        @error('docente_supervisor_nombre') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Número de empleado</label>
                        <input type="text" wire:model="docente_numero_empleado" class="{{ $inputClass }}">
                        @error('docente_numero_empleado') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Número de celular</label>
                        <input type="tel" wire:model="docente_celular" class="{{ $inputClass }}">
                        @error('docente_celular') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Correo electrónico</label>
                        <input type="email" wire:model="docente_correo" class="{{ $inputClass }}">
                        @error('docente_correo') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Categoría</label>
                        <input type="text" wire:model="docente_categoria" class="{{ $inputClass }}">
                        @error('docente_categoria') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Departamento al que pertenece</label>
                        <input type="text" wire:model="docente_departamento" class="{{ $inputClass }}">
                        @error('docente_departamento') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Jornada laboral</label>
                        <input type="text" wire:model="docente_jornada" class="{{ $inputClass }}">
                        @error('docente_jornada') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="{{ $labelClass }}">Ubicación del cubículo</label>
                        <input type="text" wire:model="docente_cubiculo" class="{{ $inputClass }}">
                        @error('docente_cubiculo') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>
        @endif

        @if($currentStep === 9)
            <section>
                <h2 class="mb-5 text-lg font-semibold text-gray-900 dark:text-white">Paso 9: Documentos adjuntos</h2>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <div class="{{ $cardClass }}">
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">Carta de formalización de la PPS firmada por la contraparte <span class="text-red-500">*</span></p>
                        <div class="mt-3 flex gap-4">
                            @foreach(['Si', 'No'] as $opcion)
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input type="radio" wire:model="carta_formalizacion_aplica" value="{{ $opcion }}" class="text-blue-600 focus:ring-blue-500">
                                    <span>{{ $opcion }}</span>
                                </label>
                            @endforeach
                        </div>

                        <div class="mt-4">
                            <label class="{{ $labelClass }}">Archivo adjunto</label>
                            <input type="file" wire:model="carta_formalizacion_archivo" class="w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100 dark:text-gray-300">
                            {{-- TODO: Confirmar storage final, permisos de descarga y politica de reemplazo del archivo. --}}
                            @error('carta_formalizacion_archivo') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                            @if($modoEdicion && isset($archivo_carta_formalizacion_actual) && filled($archivo_carta_formalizacion_actual))
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    Archivo actual:
                                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($archivo_carta_formalizacion_actual) }}"
                                       target="_blank"
                                       class="font-semibold text-blue-700 hover:underline dark:text-blue-300">
                                        {{ basename($archivo_carta_formalizacion_actual) }}
                                    </a>
                                </p>
                            @endif
                            @if($carta_formalizacion_archivo)
                                <p class="mt-2 text-xs text-green-600">Archivo seleccionado para revisión visual.</p>
                            @endif
                        </div>
                    </div>

                    <div class="{{ $cardClass }}">
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">Convenio marco entre la UNAH y entidad <span class="text-red-500">*</span></p>
                        <div class="mt-3 flex gap-4">
                            @foreach(['Si', 'No'] as $opcion)
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input type="radio" wire:model="convenio_marco_aplica" value="{{ $opcion }}" class="text-blue-600 focus:ring-blue-500">
                                    <span>{{ $opcion }}</span>
                                </label>
                            @endforeach
                        </div>

                        <div class="mt-4">
                            <label class="{{ $labelClass }}">Archivo adjunto</label>
                            <input type="file" wire:model="convenio_marco_archivo" class="w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100 dark:text-gray-300">
                            {{-- TODO: Confirmar storage final, permisos de descarga y politica de reemplazo del archivo. --}}
                            @error('convenio_marco_archivo') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                            @if($modoEdicion && isset($archivo_convenio_marco_actual) && filled($archivo_convenio_marco_actual))
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    Archivo actual:
                                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($archivo_convenio_marco_actual) }}"
                                       target="_blank"
                                       class="font-semibold text-blue-700 hover:underline dark:text-blue-300">
                                        {{ basename($archivo_convenio_marco_actual) }}
                                    </a>
                                </p>
                            @endif
                            @if($convenio_marco_archivo)
                                <p class="mt-2 text-xs text-green-600">Archivo seleccionado para revisión visual.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </section>
        @endif

        @if(false)
            <section>
                <div class="mb-5 flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                    <div>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <div class="{{ $cardClass }}">
                        <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Información general</h3>
                        <dl class="space-y-2 text-sm">
                            <div><dt class="text-gray-500">Fecha de registro</dt><dd class="text-gray-900 dark:text-white">{{ $fechaRegistroVisible }}</dd></div>
                            <div><dt class="text-gray-500">Facultad / Centro</dt><dd class="text-gray-900 dark:text-white">{{ $facultadesCentros[$facultad_centro_id] ?? 'Pendiente' }}</dd></div>
                            <div><dt class="text-gray-500">Carrera</dt><dd class="text-gray-900 dark:text-white">{{ $carreras[$carrera_id] ?? 'Pendiente' }}</dd></div>
                            <div><dt class="text-gray-500">Tipo</dt><dd class="text-gray-900 dark:text-white">{{ $this->tipoPpsEtiqueta($tipo_pps_ss) }}</dd></div>
                            <div><dt class="text-gray-500">Periodo</dt><dd class="text-gray-900 dark:text-white">{{ $fecha_inicio ?: 'Pendiente' }} / {{ $fecha_finalizacion ?: 'Pendiente' }}</dd></div>
                        </dl>
                    </div>

                    <div class="{{ $cardClass }}">
                        <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Estudiante</h3>
                        <dl class="space-y-2 text-sm">
                            <div><dt class="text-gray-500">Cuenta</dt><dd class="text-gray-900 dark:text-white">{{ $numero_cuenta ?: 'Pendiente' }}</dd></div>
                            <div><dt class="text-gray-500">Nombre</dt><dd class="text-gray-900 dark:text-white">{{ $estudiante_nombre_completo ?: 'Pendiente' }}</dd></div>
                            <div><dt class="text-gray-500">Celular</dt><dd class="text-gray-900 dark:text-white">{{ $estudiante_celular ?: 'Pendiente' }}</dd></div>
                            <div><dt class="text-gray-500">Correo institucional</dt><dd class="text-gray-900 dark:text-white">{{ $estudiante_correo_institucional ?: 'Pendiente' }}</dd></div>
                        </dl>
                    </div>

                    <div class="{{ $cardClass }}">
                        <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Datos territoriales</h3>
                        <dl class="space-y-2 text-sm">
                            <div><dt class="text-gray-500">Territorio</dt><dd class="text-gray-900 dark:text-white">{{ $territorio_ejecucion }}</dd></div>
                            <div><dt class="text-gray-500">Modalidad</dt><dd class="text-gray-900 dark:text-white">{{ $this->modalidadEtiqueta($modalidad_ejecucion) }}</dd></div>
                            <div><dt class="text-gray-500">Región</dt><dd class="text-gray-900 dark:text-white">{{ $region ?: 'No especificado' }}</dd></div>
                            <div><dt class="text-gray-500">País</dt><dd class="text-gray-900 dark:text-white">{{ $paisResumen ?: 'No especificado' }}</dd></div>
                            <div><dt class="text-gray-500">{{ $departamentoLabelResumen }}</dt><dd class="text-gray-900 dark:text-white">{{ $departamentoProvinciaResumen ?: 'No especificado' }}</dd></div>
                            <div><dt class="text-gray-500">Municipio</dt><dd class="text-gray-900 dark:text-white">{{ $municipioResumen ?: 'No especificado' }}</dd></div>
                            <div><dt class="text-gray-500">Aldea / ciudad</dt><dd class="text-gray-900 dark:text-white">{{ $aldeaCiudadResumen ?: 'No especificado' }}</dd></div>
                            <div><dt class="text-gray-500">Caserío</dt><dd class="text-gray-900 dark:text-white">{{ $caserio ?: 'No especificado' }}</dd></div>
                            <div><dt class="text-gray-500">País sede principal</dt><dd class="text-gray-900 dark:text-white">{{ $pais_sede_principal ?: 'No especificado' }}</dd></div>
                            <div><dt class="text-gray-500">Departamento / provincia sede principal</dt><dd class="text-gray-900 dark:text-white">{{ $departamento_provincia_sede_principal ?: 'No especificado' }}</dd></div>
                            <div><dt class="text-gray-500">Municipio sede principal</dt><dd class="text-gray-900 dark:text-white">{{ $municipio_sede_principal ?: 'No especificado' }}</dd></div>
                            <div><dt class="text-gray-500">Aldea / ciudad sede principal</dt><dd class="text-gray-900 dark:text-white">{{ $aldea_ciudad_sede_principal ?: 'No especificado' }}</dd></div>
                            <div><dt class="text-gray-500">Horas presenciales</dt><dd class="text-gray-900 dark:text-white">{{ $horas_presenciales !== '' ? $horas_presenciales : 'No especificado' }}</dd></div>
                            <div><dt class="text-gray-500">Horas teletrabajo</dt><dd class="text-gray-900 dark:text-white">{{ $horas_teletrabajo !== '' ? $horas_teletrabajo : 'No especificado' }}</dd></div>
                        </dl>
                    </div>

                    <div class="{{ $cardClass }}">
                        <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Alcance</h3>
                        <dl class="space-y-2 text-sm">
                            <div><dt class="text-gray-500">Descripción del tipo de PPS</dt><dd class="whitespace-pre-line text-gray-900 dark:text-white">{{ $descripcion_tipo_pps ?: 'No especificado' }}</dd></div>
                            <div><dt class="text-gray-500">Descripción de horas PPS/SS</dt><dd class="whitespace-pre-line text-gray-900 dark:text-white">{{ $descripcion_horas_tipo_pps_ss ?: 'No especificado' }}</dd></div>
                            <div><dt class="text-gray-500">Total de horas</dt><dd class="text-gray-900 dark:text-white">{{ $total_horas ?: 'Pendiente' }}</dd></div>
                            <div><dt class="text-gray-500">Departamento o área</dt><dd class="text-gray-900 dark:text-white">{{ $area_realizacion ?: 'No especificado' }}</dd></div>
                            <div><dt class="text-gray-500">Responsabilidades y tareas</dt><dd class="whitespace-pre-line text-gray-900 dark:text-white">{{ $resumen_responsabilidades ?: 'No especificado' }}</dd></div>
                        </dl>
                    </div>

                    <div class="{{ $cardClass }}">
                        <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Institución</h3>
                        <dl class="space-y-2 text-sm">
                            <div><dt class="text-gray-500">Nombre</dt><dd class="text-gray-900 dark:text-white">{{ $institucion_nombre ?: 'Pendiente' }}</dd></div>
                            <div><dt class="text-gray-500">Nacionalidad</dt><dd class="text-gray-900 dark:text-white">{{ $institucion_nacionalidad ?: 'No especificado' }}</dd></div>
                            <div><dt class="text-gray-500">País</dt><dd class="text-gray-900 dark:text-white">{{ $institucion_pais ?: 'No especificado' }}</dd></div>
                            <div><dt class="text-gray-500">Representante</dt><dd class="text-gray-900 dark:text-white">{{ $institucion_representante ?: 'Pendiente' }}</dd></div>
                            <div><dt class="text-gray-500">Teléfono</dt><dd class="text-gray-900 dark:text-white">{{ $institucion_telefono ?: 'Pendiente' }}</dd></div>
                            <div><dt class="text-gray-500">Tipo</dt><dd class="text-gray-900 dark:text-white">{{ $tipoInstitucionOpciones[$institucion_tipo] ?? 'Pendiente' }}</dd></div>
                        </dl>
                    </div>

                    <div class="{{ $cardClass }}">
                        <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Jefe directo</h3>
                        <dl class="space-y-2 text-sm">
                            <div><dt class="text-gray-500">Nombre</dt><dd class="text-gray-900 dark:text-white">{{ $jefe_directo_nombre ?: 'Pendiente' }}</dd></div>
                            <div><dt class="text-gray-500">Cargo</dt><dd class="text-gray-900 dark:text-white">{{ $jefe_directo_cargo ?: 'Pendiente' }}</dd></div>
                            <div><dt class="text-gray-500">Correo</dt><dd class="text-gray-900 dark:text-white">{{ $jefe_directo_correo ?: 'Pendiente' }}</dd></div>
                        </dl>
                    </div>

                    <div class="{{ $cardClass }}">
                        <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Docente supervisor</h3>
                        <dl class="space-y-2 text-sm">
                            <div><dt class="text-gray-500">Nombre</dt><dd class="text-gray-900 dark:text-white">{{ $docente_supervisor_nombre ?: 'Pendiente' }}</dd></div>
                            <div><dt class="text-gray-500">Número de empleado</dt><dd class="text-gray-900 dark:text-white">{{ $docente_numero_empleado ?: 'Pendiente' }}</dd></div>
                            <div><dt class="text-gray-500">Departamento</dt><dd class="text-gray-900 dark:text-white">{{ $docente_departamento ?: 'Pendiente' }}</dd></div>
                        </dl>
                    </div>

                    <div class="{{ $cardClass }} lg:col-span-2">
                        @php
                            $archivoCartaActual = ($modoEdicion && isset($archivo_carta_formalizacion_actual)) ? $archivo_carta_formalizacion_actual : null;
                            $archivoConvenioActual = ($modoEdicion && isset($archivo_convenio_marco_actual)) ? $archivo_convenio_marco_actual : null;
                            $cartaTieneDocumento = (bool) $carta_formalizacion_archivo || filled($archivoCartaActual);
                            $convenioTieneDocumento = (bool) $convenio_marco_archivo || filled($archivoConvenioActual);
                            $cartaResumen = ($carta_formalizacion_aplica === 'Si' || $cartaTieneDocumento) ? 'Si' : 'No';
                            $convenioResumen = ($convenio_marco_aplica === 'Si' || $convenioTieneDocumento) ? 'Si' : 'No';
                            $cartaNombre = $carta_formalizacion_archivo
                                ? $carta_formalizacion_archivo->getClientOriginalName()
                                : (filled($archivoCartaActual) ? basename($archivoCartaActual) : null);
                            $convenioNombre = $convenio_marco_archivo
                                ? $convenio_marco_archivo->getClientOriginalName()
                                : (filled($archivoConvenioActual) ? basename($archivoConvenioActual) : null);
                        @endphp
                        <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Documentos</h3>
                        <dl class="grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
                            <div>
                                <dt class="text-gray-500">Carta de formalización</dt>
                                <dd class="text-gray-900 dark:text-white">{{ $cartaResumen }}</dd>
                                @if($cartaNombre)
                                    <dd class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        @if(filled($archivoCartaActual) && !$carta_formalizacion_archivo)
                                            <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($archivoCartaActual) }}"
                                               target="_blank"
                                               class="font-semibold text-blue-700 hover:underline dark:text-blue-300">
                                                {{ $cartaNombre }}
                                            </a>
                                        @else
                                            {{ $cartaNombre }}
                                        @endif
                                    </dd>
                                @endif
                            </div>
                            <div>
                                <dt class="text-gray-500">Convenio marco</dt>
                                <dd class="text-gray-900 dark:text-white">{{ $convenioResumen }}</dd>
                                @if($convenioNombre)
                                    <dd class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        @if(filled($archivoConvenioActual) && !$convenio_marco_archivo)
                                            <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($archivoConvenioActual) }}"
                                               target="_blank"
                                               class="font-semibold text-blue-700 hover:underline dark:text-blue-300">
                                                {{ $convenioNombre }}
                                            </a>
                                        @else
                                            {{ $convenioNombre }}
                                        @endif
                                    </dd>
                                @endif
                            </div>
                        </dl>
                    </div>
                </div>
            </section>
        @endif

        <div class="mt-8 flex items-center justify-between border-t border-gray-200 pt-4 dark:border-gray-700">
            <div>
                @if($currentStep > 1)
                    <button type="button" wire:click="prevStep" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                        &larr; Anterior
                    </button>
                @endif
            </div>

            <div class="flex items-center gap-3">
                @if($currentStep < 9)
                    <button type="button" wire:click="nextStep"
                        aria-disabled="{{ (!$this->shouldLockStepNavigation() || $this->isStepComplete($currentStep)) ? 'false' : 'true' }}"
                        class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 {{ $this->shouldLockStepNavigation() && !$this->isStepComplete($currentStep) ? 'cursor-not-allowed opacity-60' : '' }}">
                        Siguiente &rarr;
                    </button>
                @elseif($currentStep === 9)
                    <button type="button" wire:click="guardarBorrador"
                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                        Guardar como borrador
                    </button>
                    <button type="button" wire:click="abrirModalEnviar"
                        aria-disabled="{{ (!$this->shouldLockStepNavigation() || $this->isStepComplete($currentStep)) ? 'false' : 'true' }}"
                        class="inline-flex items-center rounded-md bg-amber-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-600 {{ $this->shouldLockStepNavigation() && !$this->isStepComplete($currentStep) ? 'cursor-not-allowed opacity-60' : '' }}">
                        Enviar a firmar
                    </button>
                @endif
            </div>
        </div>
    </form>

    @if ($showEnviarModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900">

                {{-- Header --}}
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">Enviar a revisión</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            @if (count($modalEtapas) > 0)
                                Configura los destinatarios solo en las etapas donde el flujo indica que el emisor debe definirlos.
                            @else
                                El registro será enviado al flujo de revisión configurado.
                            @endif
                        </p>
                    </div>
                    <button wire:click="cancelarModal" class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full border border-slate-200 text-slate-500 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-400">
                        &times;
                    </button>
                </div>

                @if (count($modalEtapas) > 0)
                    {{-- Indicador de pasos del modal --}}
                    <div class="mt-5 flex flex-wrap items-center gap-2">
                        @foreach ($modalEtapas as $i => $etapa)
                            <div class="flex items-center gap-2">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold {{ $modalStep === $i + 1 ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900' : 'border border-slate-300 text-slate-400 dark:border-slate-600' }}">{{ $i + 1 }}</span>
                                <span class="text-sm {{ $modalStep === $i + 1 ? 'font-semibold text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-500' }}">{{ $etapa['nombre'] }}</span>
                                <span class="text-slate-300 dark:text-slate-600">&rarr;</span>
                            </div>
                        @endforeach
                        <div class="flex items-center gap-2">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold {{ $modalStep === count($modalEtapas) + 1 ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900' : 'border border-slate-300 text-slate-400 dark:border-slate-600' }}">{{ count($modalEtapas) + 1 }}</span>
                            <span class="text-sm {{ $modalStep === count($modalEtapas) + 1 ? 'font-semibold text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-500' }}">Confirmación</span>
                        </div>
                    </div>

                    {{-- Contenido por etapa --}}
                    @foreach ($modalEtapas as $i => $etapa)
                        @if ($modalStep === $i + 1)
                            <div class="mt-6 rounded-xl border border-slate-200 p-4 dark:border-slate-700">
                                <h3 class="font-semibold text-slate-900 dark:text-white">Etapa {{ $i + 1 }} &middot; {{ $etapa['nombre'] }}</h3>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                    Selecciona el usuario que recibirá el registro en esta etapa. Rol de la etapa: <strong>{{ $etapa['rol_nombre'] }}</strong>.
                                </p>
                                <div class="mt-4">
                                    <select wire:model="modalDestinatarios.{{ $etapa['id'] }}"
                                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                        <option value="">Buscar por nombre o correo</option>
                                        @foreach ($etapa['usuarios'] as $usuario)
                                            <option value="{{ $usuario['id'] }}">{{ $usuario['name'] }}{{ filled($usuario['email']) ? ' — '.$usuario['email'] : '' }}</option>
                                        @endforeach
                                    </select>
                                    @error('modal_destinatario_'.($i + 1))
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                    @if (empty($etapa['usuarios']))
                                        <p class="mt-2 text-xs text-amber-600 dark:text-amber-400">No hay usuarios con el rol {{ $etapa['rol_nombre'] }} disponibles.</p>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endforeach

                    {{-- Paso de confirmación --}}
                    @if ($modalStep === count($modalEtapas) + 1)
                        <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/30">
                            <h3 class="font-semibold text-emerald-800 dark:text-emerald-200">Listo para enviar</h3>
                            <p class="mt-1 text-sm text-emerald-700 dark:text-emerald-300">
                                Se asignarán los destinatarios seleccionados y el registro pasará al flujo de revisión.
                            </p>
                        </div>
                    @endif

                @else
                    {{-- Sin etapas con emisor_define_destinatario — confirmación directa --}}
                    <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/30">
                        <p class="text-sm text-emerald-700 dark:text-emerald-300">
                            El flujo de revisión asignará los responsables automáticamente según la configuración.
                        </p>
                    </div>
                @endif

                {{-- Footer --}}
                <div class="mt-6 flex items-center justify-between">
                    <button wire:click="cancelarModal" class="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                        Cancelar
                    </button>
                    <div class="flex items-center gap-2">
                        @if ($modalStep > 1)
                            <button wire:click="modalAnterior" class="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                                &larr; Anterior
                            </button>
                        @endif
                        @if (count($modalEtapas) > 0 && $modalStep <= count($modalEtapas))
                            <button wire:click="modalSiguiente" class="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-slate-200">
                                Siguiente &rarr;
                            </button>
                        @else
                            <button wire:click="confirmarEnvio" class="inline-flex items-center rounded-full bg-amber-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-600">
                                Confirmar envío
                            </button>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    @endif
</div>
