<div>
    @php
        $stepLabels = [
            1 => 'Info general',
            2 => 'Estudiante',
            3 => 'PPS / SS',
            4 => 'Territorio',
            5 => 'Alcance',
            6 => 'Institucion',
            7 => 'Jefe directo',
            8 => 'Supervisor',
            9 => 'Adjuntos',
            10 => 'Revision',
        ];

        $inputClass = 'w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500';
        $labelClass = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1';
        $errorClass = 'text-red-500 text-xs mt-1';
        $cardClass = 'rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5 shadow-sm';
        $modoEdicion = $modoEdicion ?? false;
        $registroEdicion = $registroEdicion ?? null;
    @endphp

    <div class="mb-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-300">FORM-DVUS-015/016</p>
        <h1 class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">
            {{ $modoEdicion ? 'Editar Registro de Practica Profesional Supervisada o Servicio Social' : 'Registro de Practica Profesional Supervisada o Servicio Social' }}
        </h1>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            {{ $modoEdicion ? 'Actualice la informacion del borrador antes de enviarlo a revision.' : 'Complete la informacion del FORM-DVUS-015/016 para guardarla como borrador.' }}
        </p>
    </div>

    @if($modoEdicion && $registroEdicion && filled($registroEdicion->motivo_rechazo))
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-5 text-sm text-red-800 shadow-sm dark:border-red-900/70 dark:bg-red-950/30 dark:text-red-200">
            <p class="font-semibold">Este registro fue rechazado anteriormente.</p>
            <p class="mt-1">Corrija las observaciones indicadas antes de reenviarlo a revision.</p>

            @if($registroEdicion->fecha_revision)
                <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-red-700 dark:text-red-300">
                    Fecha de revision: {{ $registroEdicion->fecha_revision->format('d/m/Y H:i') }}
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
                @php $complete = $this->isStepComplete($step); @endphp
                <button wire:click="goToStep({{ $step }})" type="button"
                    class="flex min-w-[44px] flex-1 flex-col items-center p-1 group">
                    <span class="mb-1 flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold transition-colors
                        {{ $currentStep === $step
                            ? 'bg-blue-600 text-white ring-2 ring-blue-300'
                            : ($complete
                                ? 'bg-green-500 text-white'
                                : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-400') }}">
                        @if($complete)
                            &#10003;
                        @else
                            {{ $step }}
                        @endif
                    </span>
                    <span class="hidden text-center text-[10px] leading-tight sm:block
                        {{ $currentStep === $step ? 'font-semibold text-blue-600' : ($complete ? 'text-green-600 dark:text-green-400' : 'text-gray-500') }}">
                        {{ $label }}
                    </span>
                </button>

                @if($step < count($stepLabels))
                    <div class="h-0.5 w-3 shrink-0 {{ $complete ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-700' }}"></div>
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
                <h2 class="mb-5 text-lg font-semibold text-gray-900 dark:text-white">Paso 1: Informacion general</h2>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="{{ $labelClass }}">Facultad / Centro Universitario Regional / Instituto Tecnologico <span class="text-red-500">*</span></label>
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
                            <p class="mt-1 text-xs text-amber-600">No hay carreras disponibles para la seleccion actual.</p>
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
                        <label class="{{ $labelClass }}">Numero de cuenta <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="numero_cuenta" class="{{ $inputClass }}">
                        @error('numero_cuenta') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Nombre completo <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="estudiante_nombre_completo" class="{{ $inputClass }}">
                        @error('estudiante_nombre_completo') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Numero de celular <span class="text-red-500">*</span></label>
                        <input type="tel" wire:model="estudiante_celular" class="{{ $inputClass }}">
                        @error('estudiante_celular') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Correo electronico institucional <span class="text-red-500">*</span></label>
                        <input type="email" wire:model="estudiante_correo_institucional" class="{{ $inputClass }}">
                        @error('estudiante_correo_institucional') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="{{ $labelClass }}">Correo electronico personal</label>
                        <input type="email" wire:model="estudiante_correo_personal" class="{{ $inputClass }}">
                        @error('estudiante_correo_personal') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>
        @endif

        @if($currentStep === 3)
            <section>
                <h2 class="mb-5 text-lg font-semibold text-gray-900 dark:text-white">Paso 3: Informacion de la PPS / Servicio Social</h2>

                <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                    <div class="{{ $cardClass }}">
                        <p class="mb-3 text-sm font-semibold text-gray-800 dark:text-gray-100">Tipo <span class="text-red-500">*</span></p>
                        <div class="space-y-2">
                            @foreach($tipoPpsOpciones as $opcion)
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input type="radio" wire:model="tipo_pps_ss" value="{{ $opcion }}" class="text-blue-600 focus:ring-blue-500">
                                    <span>{{ $opcion }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('tipo_pps_ss') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div class="{{ $cardClass }}">
                        <p class="mb-3 text-sm font-semibold text-gray-800 dark:text-gray-100">Territorio de ejecucion <span class="text-red-500">*</span></p>
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
                        <label class="{{ $labelClass }}">Fecha de finalizacion <span class="text-red-500">*</span></label>
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
                <h2 class="mb-5 text-lg font-semibold text-gray-900 dark:text-white">Paso 4: Datos territoriales</h2>

                @if($territorio_ejecucion === 'Internacional')
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900/70 dark:bg-amber-950/30 dark:text-amber-200">
                        Los datos internacionales requieren definicion de persistencia.
                    </div>
                    {{-- TODO: Definir campos y tabla para pais, ciudad y ubicacion internacional. --}}
                @else
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
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

                        <div>
                            <label class="{{ $labelClass }}">Aldea</label>
                            <input type="text" wire:model.live.debounce.500ms="aldea" maxlength="255" placeholder="Ingrese la aldea" class="{{ $inputClass }}">
                            @error('aldea') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="{{ $labelClass }}">Ciudad</label>
                            <input type="text" wire:model.live.debounce.500ms="ciudad" maxlength="255" placeholder="Ingrese la ciudad" class="{{ $inputClass }}">
                            @error('ciudad') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="{{ $labelClass }}">Caserio</label>
                            <input type="text" wire:model="caserio" class="{{ $inputClass }}">
                            @error('caserio') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                        </div>
                    </div>
                @endif
            </section>
        @endif

        @if($currentStep === 5)
            <section>
                <h2 class="mb-5 text-lg font-semibold text-gray-900 dark:text-white">Paso 5: Alcance de la PPS / Servicio Social</h2>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="{{ $labelClass }}">Descripcion del tipo de PPS</label>
                        <textarea wire:model="descripcion_tipo_pps" rows="3" class="{{ $inputClass }}"></textarea>
                        @error('descripcion_tipo_pps') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Total de horas <span class="text-red-500">*</span></label>
                        <input type="number" min="1" wire:model="total_horas" class="{{ $inputClass }}">
                        @error('total_horas') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Departamento o area donde se realizara</label>
                        <input type="text" wire:model="area_realizacion" class="{{ $inputClass }}">
                        @error('area_realizacion') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="{{ $labelClass }}">Resumen de responsabilidades y tareas</label>
                        <textarea wire:model="resumen_responsabilidades" rows="4" class="{{ $inputClass }}"></textarea>
                        @error('resumen_responsabilidades') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <p class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Modalidad de ejecucion <span class="text-red-500">*</span></p>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                            @foreach(['Presencial', '100% virtual', 'Hibrida'] as $modalidad)
                                <label class="flex items-center gap-2 rounded-md border border-gray-200 p-3 text-sm dark:border-gray-700">
                                    <input type="radio" wire:model="modalidad_ejecucion" value="{{ $modalidad }}" class="text-blue-600 focus:ring-blue-500">
                                    <span class="text-gray-700 dark:text-gray-300">{{ $modalidad }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('modalidad_ejecucion') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>
        @endif

        @if($currentStep === 6)
            <section>
                <h2 class="mb-5 text-lg font-semibold text-gray-900 dark:text-white">Paso 6: Institucion / Organizacion</h2>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="{{ $labelClass }}">Nombre completo de la institucion / organizacion <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="institucion_nombre" class="{{ $inputClass }}">
                        @error('institucion_nombre') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="{{ $labelClass }}">Compromisos asumidos</label>
                        <textarea wire:model="institucion_compromisos" rows="3" class="{{ $inputClass }}"></textarea>
                        @error('institucion_compromisos') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="{{ $labelClass }}">Direccion exacta</label>
                        <textarea wire:model="institucion_direccion" rows="2" class="{{ $inputClass }}"></textarea>
                        @error('institucion_direccion') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Representante legal</label>
                        <input type="text" wire:model="institucion_representante" class="{{ $inputClass }}">
                        @error('institucion_representante') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Telefono</label>
                        <input type="tel" wire:model="institucion_telefono" class="{{ $inputClass }}">
                        @error('institucion_telefono') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Correo de recursos humanos</label>
                        <input type="email" wire:model="institucion_correo_rrhh" class="{{ $inputClass }}">
                        @error('institucion_correo_rrhh') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Tipo de institucion</label>
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
                <h2 class="mb-5 text-lg font-semibold text-gray-900 dark:text-white">Paso 7: Jefe directo de la PPS / SS</h2>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="{{ $labelClass }}">Nombre completo <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="jefe_directo_nombre" class="{{ $inputClass }}">
                        @error('jefe_directo_nombre') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Numero de celular</label>
                        <input type="tel" wire:model="jefe_directo_celular" class="{{ $inputClass }}">
                        @error('jefe_directo_celular') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Correo electronico</label>
                        <input type="email" wire:model="jefe_directo_correo" class="{{ $inputClass }}">
                        @error('jefe_directo_correo') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Cargo</label>
                        <input type="text" wire:model="jefe_directo_cargo" class="{{ $inputClass }}">
                        @error('jefe_directo_cargo') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Grado academico</label>
                        <input type="text" wire:model="jefe_directo_grado" class="{{ $inputClass }}">
                        @error('jefe_directo_grado') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>
        @endif

        @if($currentStep === 8)
            <section>
                <h2 class="mb-5 text-lg font-semibold text-gray-900 dark:text-white">Paso 8: Docente supervisor</h2>

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
                        <label class="{{ $labelClass }}">Numero de empleado</label>
                        <input type="text" wire:model="docente_numero_empleado" class="{{ $inputClass }}">
                        @error('docente_numero_empleado') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Numero de celular</label>
                        <input type="tel" wire:model="docente_celular" class="{{ $inputClass }}">
                        @error('docente_celular') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Correo electronico</label>
                        <input type="email" wire:model="docente_correo" class="{{ $inputClass }}">
                        @error('docente_correo') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Categoria</label>
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
                        <label class="{{ $labelClass }}">Ubicacion del cubiculo</label>
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
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">Carta de formalizacion de la PPS firmada por la contraparte</p>
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
                                <p class="mt-2 text-xs text-green-600">Archivo seleccionado para revision visual.</p>
                            @endif
                        </div>
                    </div>

                    <div class="{{ $cardClass }}">
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">Convenio marco entre la UNAH y entidad</p>
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
                                <p class="mt-2 text-xs text-green-600">Archivo seleccionado para revision visual.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </section>
        @endif

        @if($currentStep === 10)
            <section>
                <div class="mb-5 flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Paso 10: Revision final</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Verifique la informacion antes de {{ $modoEdicion ? 'actualizar' : 'guardar' }}.</p>
                    </div>

                    @if($registroGuardado)
                        <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700 dark:bg-green-900/40 dark:text-green-200">
                            Guardado
                        </span>
                    @endif
                </div>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <div class="{{ $cardClass }}">
                        <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Informacion general</h3>
                        <dl class="space-y-2 text-sm">
                            <div><dt class="text-gray-500">Facultad / Centro</dt><dd class="text-gray-900 dark:text-white">{{ $facultadesCentros[$facultad_centro_id] ?? 'Pendiente' }}</dd></div>
                            <div><dt class="text-gray-500">Carrera</dt><dd class="text-gray-900 dark:text-white">{{ $carreras[$carrera_id] ?? 'Pendiente' }}</dd></div>
                            <div><dt class="text-gray-500">Tipo</dt><dd class="text-gray-900 dark:text-white">{{ $tipo_pps_ss ?: 'Pendiente' }}</dd></div>
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
                        <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Territorio y alcance</h3>
                        <dl class="space-y-2 text-sm">
                            <div><dt class="text-gray-500">Territorio</dt><dd class="text-gray-900 dark:text-white">{{ $territorio_ejecucion }}</dd></div>
                            <div><dt class="text-gray-500">Departamento</dt><dd class="text-gray-900 dark:text-white">{{ $departamentos[$departamento_id] ?? 'Pendiente o no aplica' }}</dd></div>
                            <div><dt class="text-gray-500">Municipio</dt><dd class="text-gray-900 dark:text-white">{{ $municipios[$municipio_id] ?? 'Pendiente o no aplica' }}</dd></div>
                            <div><dt class="text-gray-500">Horas</dt><dd class="text-gray-900 dark:text-white">{{ $total_horas ?: 'Pendiente' }}</dd></div>
                            <div><dt class="text-gray-500">Modalidad</dt><dd class="text-gray-900 dark:text-white">{{ $modalidad_ejecucion ?: 'Pendiente' }}</dd></div>
                        </dl>
                    </div>

                    <div class="{{ $cardClass }}">
                        <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Institucion</h3>
                        <dl class="space-y-2 text-sm">
                            <div><dt class="text-gray-500">Nombre</dt><dd class="text-gray-900 dark:text-white">{{ $institucion_nombre ?: 'Pendiente' }}</dd></div>
                            <div><dt class="text-gray-500">Representante</dt><dd class="text-gray-900 dark:text-white">{{ $institucion_representante ?: 'Pendiente' }}</dd></div>
                            <div><dt class="text-gray-500">Telefono</dt><dd class="text-gray-900 dark:text-white">{{ $institucion_telefono ?: 'Pendiente' }}</dd></div>
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
                            <div><dt class="text-gray-500">Numero de empleado</dt><dd class="text-gray-900 dark:text-white">{{ $docente_numero_empleado ?: 'Pendiente' }}</dd></div>
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
                                <dt class="text-gray-500">Carta formalizacion</dt>
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
                    <button type="button" wire:click="nextStep" class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        Siguiente &rarr;
                    </button>
                @elseif($currentStep === 9)
                    <button type="button" wire:click="goToReview" class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        Revision final &rarr;
                    </button>
                @else
                    <button type="submit" class="inline-flex items-center rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                        {{ $modoEdicion ? 'Actualizar' : 'Guardar' }}
                    </button>
                @endif
            </div>
        </div>
    </form>
</div>
