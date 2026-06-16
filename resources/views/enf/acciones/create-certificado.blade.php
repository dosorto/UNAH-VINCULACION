@extends('layouts.panel.base')

@php
    $input = 'w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white';
    $label = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1';
    $card = 'rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900';
    $sectionTitle = 'mb-4 text-base font-semibold text-gray-900 dark:text-white';
    $catalog = fn (string $tipo) => $catalogos->get($tipo, collect());
    $tiposCertificadoForm016 = $catalog('tipo_certificado')->whereIn('nombre', ['Basico', 'Avanzado']);
    $gradosAcademicosForm016 = $catalog('grado_academico')->whereIn('nombre', [
        'Titulo de Educacion Media',
        'Titulo Universitario',
        'Acreditar experiencia comprobada en el area',
    ]);
    $tipoCertificadoId = old('catalogos.tipo_accion_enf.0', $selectedTipoAccionEnfId);
    $stepLabels = [
        1 => 'Certificado',
        2 => 'Plan',
        3 => 'Periodo',
        4 => 'Modalidad',
        5 => 'Beneficiarios',
        6 => 'Docentes',
        7 => 'Contraparte',
        8 => 'Académica',
        9 => 'Presupuesto',
        10 => 'Firmas',
    ];
@endphp

@section('main')
    <div class="mx-auto max-w-7xl space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">FORM-DVUS-016 · Registro de Certificados Universitarios</h1>
                <p class="text-sm text-slate-600 dark:text-slate-300">Educación no formal · registro oficial de certificado universitario.</p>
            </div>
            <a href="{{ route('selectorTipoAccion', ['grupo' => 'educacion-no-formal']) }}" class="text-sm font-semibold text-blue-700 hover:text-blue-900">Volver al selector</a>
        </div>

        @if ($errors->any())
            <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                Hay campos pendientes o con formato inválido. Revisa la ficha antes de guardar.
            </div>
        @endif

        <form method="POST" action="{{ route('enf.acciones.store') }}" enctype="multipart/form-data" class="space-y-6" data-enf-wizard-form data-total-steps="{{ count($stepLabels) }}" data-storage-key="enf-form-dvus-016-draft" data-clear-draft-on-load="{{ $clearDraftOnLoad ? '1' : '0' }}">
            @csrf
            <input type="hidden" name="tipo_accion_id" value="{{ old('tipo_accion_id', $tiposAccion->first()?->id) }}">
            <input type="hidden" name="codigo_formulario" value="FORM-DVUS-016">
            <input type="hidden" name="estado_flujo" value="BORRADOR">
            <input type="hidden" name="catalogos[tipo_accion_enf][]" value="{{ $tipoCertificadoId }}">
            <input type="hidden" name="certificado[nombre_certificado]" data-sync-certificate-name value="{{ old('certificado.nombre_certificado') }}">

            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-gray-900">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Registro por pasos</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400" data-autosave-status>Los cambios se autoguardan mientras escribe.</p>
                    </div>
                </div>
                <div class="flex items-center gap-0.5 overflow-x-auto">
                    @foreach ($stepLabels as $step => $stepLabel)
                        <button type="button" data-step-button="{{ $step }}" class="flex min-w-[76px] flex-1 flex-col items-center rounded-md p-1 transition hover:bg-slate-50 dark:hover:bg-white/5">
                            <span data-step-number="{{ $step }}" class="mb-1 flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold transition-colors">{{ $step }}</span>
                            <span data-step-label="{{ $step }}" class="hidden text-center text-[10px] leading-tight sm:block">{{ $stepLabel }}</span>
                        </button>
                        @if ($step < count($stepLabels))
                            <div data-step-divider="{{ $step }}" class="h-0.5 w-3 shrink-0"></div>
                        @endif
                    @endforeach
                </div>
            </div>

            <div class="{{ $card }}" data-step-panel="1">
                <h2 class="{{ $sectionTitle }}">1. Información general del certificado universitario</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="{{ $label }}">Fecha de solicitud</label>
                        <input type="date" name="fecha_solicitud" value="{{ old('fecha_solicitud', now()->format('Y-m-d')) }}" class="{{ $input }}">
                    </div>
                    <div class="md:col-span-2">
                        <label class="{{ $label }}">Nombre completo del certificado <span class="text-red-500">*</span></label>
                        <input name="nombre_accion" value="{{ old('nombre_accion') }}" required class="{{ $input }}" data-certificate-name-source>
                        @error('nombre_accion')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $label }}">Código de certificado</label>
                        <input name="certificado[codigo_certificado]" value="{{ old('certificado.codigo_certificado') }}" class="{{ $input }}" placeholder="Asignado por la DAFT">
                    </div>
                    <div>
                        <label class="{{ $label }}">Número de edición</label>
                        <input type="number" min="1" name="numero_edicion" value="{{ old('numero_edicion', 1) }}" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Tipo de certificado</label>
                        <select name="certificado[tipo_certificado_id]" class="{{ $input }}">
                            <option value="">Seleccione...</option>
                            @foreach ($tiposCertificadoForm016 as $item)
                                <option value="{{ $item->id }}" @selected(old('certificado.tipo_certificado_id') == $item->id)>{{ $item->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $label }}">Unidad académica responsable</label>
                        <select name="centro_facultad_id" class="{{ $input }}">
                            <option value="">Seleccione...</option>
                            @foreach ($centrosFacultad as $centro)
                                <option value="{{ $centro->id }}" @selected(old('centro_facultad_id') == $centro->id)>{{ $centro->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $label }}">Unidad responsable manual</label>
                        <input name="unidad_academica_responsable_texto" value="{{ old('unidad_academica_responsable_texto') }}" class="{{ $input }}" placeholder="Facultad, centro o instituto">
                    </div>
                    <div>
                        <label class="{{ $label }}">Escuela / Departamento académico</label>
                        <select name="departamento_academico_id" class="{{ $input }}">
                            <option value="">Seleccione...</option>
                            @foreach ($departamentosAcademicos as $departamento)
                                <option value="{{ $departamento->id }}" @selected(old('departamento_academico_id') == $departamento->id)>{{ $departamento->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $label }}">Escuela / departamento manual</label>
                        <input name="escuela_departamento_texto" value="{{ old('escuela_departamento_texto') }}" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Créditos académicos</label>
                        <input type="number" min="0" name="carga_horaria_creditos" value="{{ old('carga_horaria_creditos', 0) }}" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Horas teóricas</label>
                        <input type="number" min="0" name="horas_teoricas" value="{{ old('horas_teoricas', 0) }}" class="{{ $input }}" data-hours-field>
                    </div>
                    <div>
                        <label class="{{ $label }}">Horas prácticas</label>
                        <input type="number" min="0" name="horas_practicas" value="{{ old('horas_practicas', 0) }}" class="{{ $input }}" data-hours-field>
                    </div>
                    <div>
                        <label class="{{ $label }}">Total horas</label>
                        <input type="number" min="0" name="total_horas" value="{{ old('total_horas', 0) }}" class="{{ $input }}" data-total-hours>
                    </div>
                    <input type="hidden" name="certificado[horas_certificadas]" value="{{ old('certificado.horas_certificadas', old('total_horas', 0)) }}" data-certified-hours>
                </div>
            </div>

            <div class="{{ $card }} hidden" data-step-panel="2">
                <h2 class="{{ $sectionTitle }}">2. Carreras aprobadas y espacios de aprendizaje</h2>
                <div class="space-y-6">
                    <section>
                        <h3 class="mb-3 text-sm font-semibold text-slate-800 dark:text-slate-100">Carreras aprobadas por Consejo Universitario</h3>
                        <div class="space-y-3">
                            @for ($i = 0; $i < 4; $i++)
                                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                                    <select name="certificado_carreras[{{ $i }}][carrera_id]" class="{{ $input }}">
                                        <option value="">Carrera registrada...</option>
                                        @foreach ($carreras as $carrera)
                                            <option value="{{ $carrera->id }}">{{ $carrera->nombre }}</option>
                                        @endforeach
                                    </select>
                                    <input name="certificado_carreras[{{ $i }}][nombre_carrera]" class="{{ $input }}" placeholder="Nombre de la carrera">
                                    <input name="certificado_carreras[{{ $i }}][acuerdo_consejo_universitario]" class="{{ $input }}" placeholder="No. acuerdo de Consejo Universitario">
                                </div>
                            @endfor
                        </div>
                    </section>

                    <section>
                        <h3 class="mb-3 text-sm font-semibold text-slate-800 dark:text-slate-100">Información general del certificado universitario</h3>
                        <div class="space-y-3">
                            @for ($i = 0; $i < 6; $i++)
                                <div class="grid grid-cols-1 gap-3 md:grid-cols-5">
                                    <input name="espacios_aprendizaje[{{ $i }}][nombre]" class="{{ $input }} md:col-span-2" placeholder="Nombre asignatura">
                                    <input name="espacios_aprendizaje[{{ $i }}][codigo]" class="{{ $input }}" placeholder="Código">
                                    <input type="number" min="0" name="espacios_aprendizaje[{{ $i }}][creditos]" class="{{ $input }}" placeholder="Créditos">
                                    <input type="number" min="0" name="espacios_aprendizaje[{{ $i }}][horas]" class="{{ $input }}" placeholder="Horas">
                                </div>
                            @endfor
                        </div>
                    </section>
                </div>
            </div>

            <div class="{{ $card }} hidden" data-step-panel="3">
                <h2 class="{{ $sectionTitle }}">3. Período de ejecución, vigencia y horario</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="{{ $label }}">Fecha de inicio</label>
                        <input type="date" name="fecha_inicio" value="{{ old('fecha_inicio') }}" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Fecha de finalización</label>
                        <input type="date" name="fecha_finalizacion" value="{{ old('fecha_finalizacion') }}" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Vigencia del certificado</label>
                        <input name="certificado[vigencia_certificado]" value="{{ old('certificado.vigencia_certificado') }}" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Fecha máxima de emisión</label>
                        <input type="date" name="certificado[fecha_emision_maxima]" value="{{ old('certificado.fecha_emision_maxima') }}" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">PAC y año</label>
                        <input name="certificado[pac_certificado]" value="{{ old('certificado.pac_certificado') }}" class="{{ $input }}" placeholder="Ej. I PAC 2026">
                    </div>
                    <div>
                        <label class="{{ $label }}">Horario</label>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="time" name="certificado[hora_inicio]" value="{{ old('certificado.hora_inicio') }}" class="{{ $input }}">
                            <input type="time" name="certificado[hora_finalizacion]" value="{{ old('certificado.hora_finalizacion') }}" class="{{ $input }}">
                        </div>
                    </div>
                    <div class="md:col-span-3">
                        <label class="{{ $label }}">Días de impartición</label>
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-7">
                            @foreach (['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'] as $dia)
                                <label class="flex items-center gap-2 rounded-md border border-slate-200 px-3 py-2 text-sm dark:border-slate-700">
                                    <input type="checkbox" name="certificado[dias_imparticion][]" value="{{ $dia }}" class="rounded border-gray-300 text-blue-600">
                                    <span>{{ $dia }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="{{ $card }} hidden" data-step-panel="4">
                <h2 class="{{ $sectionTitle }}">4. Modalidad de ejecución y lugar de impartición</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="{{ $label }}">Modalidad de ejecución</label>
                        <select name="modalidad_ejecucion" class="{{ $input }}">
                            <option value="">Seleccione...</option>
                            <option>Presencial</option>
                            <option>Semi presencial (Virtual + presencial)</option>
                            <option>100% virtual</option>
                            <option>Virtual sincrónico (teledocencia)</option>
                        </select>
                    </div>
                    <div>
                        <label class="{{ $label }}">Campus / centro registrado</label>
                        <select name="campus_id" class="{{ $input }}">
                            <option value="">Seleccione...</option>
                            @foreach ($campus as $item)
                                <option value="{{ $item->id }}">{{ $item->nombre_campus }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $label }}">Lugar de impartición</label>
                        <input name="nombre_lugar" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">No. de aula</label>
                        <input name="aula_auditorio" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Edificio</label>
                        <input name="edificio" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Centro</label>
                        <input name="centro_lugar" class="{{ $input }}">
                    </div>
                    <div class="md:col-span-3">
                        <label class="{{ $label }}">Plataformas para modalidad presencial</label>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-5">
                            @foreach ($catalog('plataforma')->whereIn('nombre', ['Teams', 'Zoom', 'Meet', 'Webex', 'Otro']) as $item)
                                <label class="flex items-center gap-2 rounded-md border border-slate-200 px-3 py-2 text-sm dark:border-slate-700">
                                    <input type="checkbox" name="plataformas_presencial[]" value="{{ $item->id }}" class="rounded border-gray-300 text-blue-600">
                                    <span>{{ $item->nombre }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="md:col-span-3">
                        <label class="{{ $label }}">Plataformas para modalidad a distancia</label>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-5">
                            @foreach ($catalog('plataforma')->whereIn('nombre', ['Campus Virtual UNAH', 'Moodle', 'Classroom Google', 'Teams', 'Otro']) as $item)
                                <label class="flex items-center gap-2 rounded-md border border-slate-200 px-3 py-2 text-sm dark:border-slate-700">
                                    <input type="checkbox" name="plataformas_distancia[]" value="{{ $item->id }}" class="rounded border-gray-300 text-blue-600">
                                    <span>{{ $item->nombre }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="md:col-span-3">
                        <label class="{{ $label }}">Descripción de plataformas y recursos de teletrabajo</label>
                        <textarea name="descripcion_plataformas" rows="3" class="{{ $input }}"></textarea>
                    </div>
                </div>
            </div>

            <div class="{{ $card }} hidden" data-step-panel="5">
                <h2 class="{{ $sectionTitle }}">5. Antecedentes y perfil de beneficiarios</h2>
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div>
                        <label class="{{ $label }}">Antecedentes de la acción</label>
                        <div class="space-y-2">
                            @foreach ($catalog('antecedente') as $item)
                                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="catalogos[antecedente][]" value="{{ $item->id }}" class="rounded border-gray-300 text-blue-600"> {{ $item->nombre }}</label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label class="{{ $label }}">Grado académico requerido</label>
                        <div class="space-y-2">
                            @foreach ($gradosAcademicosForm016 as $item)
                                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="catalogos[grado_academico][]" value="{{ $item->id }}" class="rounded border-gray-300 text-blue-600"> {{ $item->nombre }}</label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label class="{{ $label }}">Perfil de participantes</label>
                        <div class="space-y-2">
                            @foreach ($catalog('perfil_participante') as $item)
                                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="catalogos[perfil_participante][]" value="{{ $item->id }}" class="rounded border-gray-300 text-blue-600"> {{ $item->nombre }}</label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div><label class="{{ $label }}">Cupos mujeres</label><input type="number" min="0" name="beneficiarios[mujeres]" value="0" class="{{ $input }}" data-cupos-field></div>
                    <div><label class="{{ $label }}">Cupos hombres</label><input type="number" min="0" name="beneficiarios[hombres]" value="0" class="{{ $input }}" data-cupos-field></div>
                    <div><label class="{{ $label }}">Total cupos</label><input type="number" min="0" name="beneficiarios[total]" value="0" class="{{ $input }}" data-cupos-total></div>
                    <div class="md:col-span-4"><label class="{{ $label }}">Perfil de los principales participantes</label><textarea name="descripcion_participantes" rows="3" class="{{ $input }}"></textarea></div>
                </div>
            </div>

            <div class="{{ $card }} hidden" data-step-panel="6">
                <h2 class="{{ $sectionTitle }}">6. Equipo docente del certificado</h2>
                <section>
                    <h3 class="mb-3 text-sm font-semibold text-slate-800 dark:text-slate-100">Coordinador/a del certificado universitario</h3>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                        <select name="coordinador[empleado_id]" class="{{ $input }}">
                            <option value="">Empleado registrado...</option>
                            @foreach ($empleados as $empleado)
                                <option value="{{ $empleado->id }}">{{ $empleado->nombre_completo }} · {{ $empleado->numero_empleado }}</option>
                            @endforeach
                        </select>
                        <input name="coordinador[nombre_completo]" class="{{ $input }}" placeholder="Nombre completo">
                        <input name="coordinador[numero_empleado]" class="{{ $input }}" placeholder="No. empleado">
                        <input name="coordinador[identidad]" class="{{ $input }}" placeholder="Identidad">
                        <input type="email" name="coordinador[correo]" class="{{ $input }}" placeholder="Correo electrónico">
                        <input name="coordinador[celular]" class="{{ $input }}" placeholder="Celular">
                        <input name="coordinador[categoria]" class="{{ $input }}" placeholder="Categoría">
                        <input name="coordinador[departamento]" class="{{ $input }}" placeholder="Departamento">
                    </div>
                </section>

                <section class="mt-6">
                    <h3 class="mb-3 text-sm font-semibold text-slate-800 dark:text-slate-100">Equipo docente</h3>
                    <div class="space-y-4">
                        @for ($i = 0; $i < 3; $i++)
                            <div class="rounded-md border border-slate-200 p-4 dark:border-slate-700">
                                <div class="mb-3 grid grid-cols-1 gap-3 md:grid-cols-3">
                                    <select name="equipo_docente[{{ $i }}][perfil_docente]" class="{{ $input }}">
                                        <option value="">Perfil del docente...</option>
                                        <option>Profesor de la UNAH</option>
                                        <option>Consultor Nacional</option>
                                        <option>Consultor Internacional</option>
                                    </select>
                                    <input name="equipo_docente[{{ $i }}][nombre_completo]" class="{{ $input }}" placeholder="Nombre completo">
                                    <input name="equipo_docente[{{ $i }}][espacio_aprendizaje]" class="{{ $input }}" placeholder="Espacio de aprendizaje que impartirá">
                                </div>
                                <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                                    <input name="equipo_docente[{{ $i }}][numero_empleado]" class="{{ $input }}" placeholder="No. empleado / identificación">
                                    <input type="email" name="equipo_docente[{{ $i }}][correo]" class="{{ $input }}" placeholder="Correo">
                                    <input name="equipo_docente[{{ $i }}][categoria]" class="{{ $input }}" placeholder="Categoría docente">
                                    <input name="equipo_docente[{{ $i }}][departamento]" class="{{ $input }}" placeholder="Departamento académico">
                                    <input name="equipo_docente[{{ $i }}][ultimo_titulo]" class="{{ $input }}" placeholder="Último título académico">
                                    <input name="equipo_docente[{{ $i }}][pais_procedencia]" class="{{ $input }}" placeholder="País de procedencia">
                                    <input name="equipo_docente[{{ $i }}][universidad_procedencia]" class="{{ $input }}" placeholder="Universidad de procedencia">
                                    <input type="number" min="0" name="equipo_docente[{{ $i }}][horas_contratadas]" class="{{ $input }}" placeholder="Horas">
                                </div>
                                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <label class="flex items-center gap-3 text-sm text-slate-700 dark:text-slate-300">
                                        <input type="checkbox" name="equipo_docente[{{ $i }}][carga_academica_pac]" value="Si" class="rounded border-gray-300 text-blue-600">
                                        Carga académica del PAC
                                    </label>
                                    <label class="flex items-center gap-3 text-sm text-slate-700 dark:text-slate-300">
                                        <input type="checkbox" name="equipo_docente[{{ $i }}][contratacion_jornada_contraria]" value="Si" class="rounded border-gray-300 text-blue-600">
                                        Contratación jornada contraria
                                    </label>
                                </div>
                            </div>
                        @endfor
                    </div>
                </section>
            </div>

            <div class="{{ $card }} hidden" data-step-panel="7">
                <h2 class="{{ $sectionTitle }}">7. Información de la entidad contraparte</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="md:col-span-3">
                        <label class="{{ $label }}">La actividad tiene contraparte</label>
                        <div class="flex items-center gap-6">
                            <label class="inline-flex items-center gap-2 text-sm"><input type="radio" name="contraparte[tiene_contraparte]" value="Si" class="text-blue-600" data-contraparte-toggle> Sí</label>
                            <label class="inline-flex items-center gap-2 text-sm"><input type="radio" name="contraparte[tiene_contraparte]" value="No" class="text-blue-600" data-contraparte-toggle checked> No</label>
                        </div>
                    </div>
                    <div>
                        <label class="{{ $label }}">Perfil contraparte</label>
                        <select name="contraparte[tipo_contraparte_id]" class="{{ $input }}" data-contraparte-field>
                            <option value="">Seleccione...</option>
                            @foreach ($catalog('tipo_contraparte') as $item)
                                <option value="{{ $item->id }}">{{ $item->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label class="{{ $label }}">Nombre de la contraparte</label><input name="contraparte[nombre]" class="{{ $input }}" data-contraparte-field></div>
                    <div><label class="{{ $label }}">Contacto directo</label><input name="contraparte[representante]" class="{{ $input }}" data-contraparte-field></div>
                    <div><label class="{{ $label }}">Cargo del contacto</label><input name="contraparte[cargo_contacto]" class="{{ $input }}" data-contraparte-field></div>
                    <div><label class="{{ $label }}">Correo</label><input type="email" name="contraparte[correo]" class="{{ $input }}" data-contraparte-field></div>
                    <div><label class="{{ $label }}">Teléfono</label><input name="contraparte[telefono]" class="{{ $input }}" data-contraparte-field></div>
                    <div class="md:col-span-2"><label class="{{ $label }}">Dirección exacta</label><input name="contraparte[direccion]" class="{{ $input }}" data-contraparte-field></div>
                    <div>
                        <label class="{{ $label }}">Instrumento de alianza</label>
                        <select name="contraparte[instrumento_alianza_id]" class="{{ $input }}" data-contraparte-field>
                            <option value="">Seleccione...</option>
                            @foreach ($catalog('instrumento_alianza') as $item)
                                <option value="{{ $item->id }}">{{ $item->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-3"><label class="{{ $label }}">Compromisos asumidos</label><textarea name="contraparte[compromisos]" rows="3" class="{{ $input }}" data-contraparte-field></textarea></div>
                </div>
            </div>

            <div class="{{ $card }} hidden" data-step-panel="8">
                <h2 class="{{ $sectionTitle }}">8. Información académica del certificado</h2>
                <div class="grid grid-cols-1 gap-4">
                    <div><label class="{{ $label }}">Resultados de aprendizaje</label><textarea name="resumen" rows="4" class="{{ $input }}"></textarea></div>
                    <div><label class="{{ $label }}">Impacto esperado</label><textarea name="impacto_esperado" rows="3" class="{{ $input }}" placeholder="Mencione al menos tres cambios esperados."></textarea></div>
                    <div><label class="{{ $label }}">Resumen de logística</label><textarea name="logistica" rows="3" class="{{ $input }}"></textarea></div>
                    <div><label class="{{ $label }}">Requisitos de emisión del certificado</label><textarea name="certificado[requisitos_emision]" rows="2" class="{{ $input }}"></textarea></div>
                </div>
            </div>

            <div class="{{ $card }} hidden" data-step-panel="9">
                <h2 class="{{ $sectionTitle }}">9. Detalle del presupuesto</h2>
                <label class="mb-4 flex items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-200">
                    <input type="checkbox" name="genera_ingresos" value="1" class="rounded border-gray-300 text-blue-600">
                    Obtendrá ingresos por la actividad
                </label>
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                    @foreach ([
                        'presupuesto_ingresos' => ['Ingresos', ['Cuotas de inscripción', 'Gestión de becas', 'Otros']],
                        'presupuesto_egresos' => ['Egresos', ['Pago de conferencistas / facilitadores', 'Materiales y suministros', 'Movilización', 'Manutención y hospedaje', 'Costos administrativos / financieros', 'Otros']],
                        'aporte_unah' => ['Aporte UNAH', ['Personal docente', 'Horas de estudiantes', 'Horas de voluntarios', 'Útiles y materiales de oficina', 'Depreciación de equipo', 'Servicios públicos']],
                    ] as $name => [$title, $rubros])
                        <div class="rounded-md border border-slate-200 p-4 dark:border-slate-700">
                            <h3 class="mb-3 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $title }}</h3>
                            <div class="space-y-3">
                                @foreach ($rubros as $i => $rubro)
                                    <div class="grid grid-cols-1 gap-2">
                                        <input type="text" name="{{ $name }}[{{ $i }}][rubro]" value="{{ $rubro }}" class="{{ $input }}">
                                        <div class="grid grid-cols-2 gap-2">
                                            <input type="number" min="0" step="0.01" name="{{ $name }}[{{ $i }}][cantidad]" value="0" class="{{ $input }}" placeholder="Cantidad">
                                            <input type="number" min="0" step="0.01" name="{{ $name }}[{{ $i }}][costo_unitario]" value="0" class="{{ $input }}" placeholder="Costo unitario">
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div><label class="{{ $label }}">Mecanismo de administración</label><select name="mecanismo_administracion" class="{{ $input }}"><option value="">Seleccione...</option><option>FUNDAUNAH</option><option>Tesorería de la UNAH</option></select></div>
                    <div><label class="{{ $label }}">Destino del excedente</label><input name="descripcion_excedente" class="{{ $input }}"></div>
                </div>
            </div>

            <div class="{{ $card }} hidden" data-step-panel="10">
                <h2 class="{{ $sectionTitle }}">10. Documentos adjuntos y firmas</h2>
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <section>
                        <label class="{{ $label }}">Descripciones mínimas del plan de estudios oficial</label>
                        <input type="file" name="documentos_archivos[descripcion_plan_estudios]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100 dark:text-gray-300">
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">PDF, Word o imagen. Máximo 10 MB.</p>
                        @error('documentos_archivos.descripcion_plan_estudios')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </section>
                    <section>
                        <h3 class="mb-3 text-sm font-semibold text-slate-800 dark:text-slate-100">Firmas requeridas</h3>
                        <div class="space-y-3">
                            @foreach (['Jefe de Departamento', 'Comité de vinculación', 'Decano(a) o Director(a) del Centro Regional'] as $i => $rolFirma)
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    <input type="hidden" name="firmas[{{ $i }}][rol_firma]" value="{{ $rolFirma }}">
                                    <input value="{{ $rolFirma }}" class="{{ $input }}" disabled>
                                    <input name="firmas[{{ $i }}][nombre_firmante]" class="{{ $input }}" placeholder="Nombre">
                                </div>
                            @endforeach
                        </div>
                    </section>
                </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <a href="{{ route('selectorTipoAccion', ['grupo' => 'educacion-no-formal']) }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Cancelar</a>
                <div class="flex justify-end gap-3">
                    <button type="button" data-previous-step class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Anterior</button>
                    <button type="button" data-next-step class="rounded-md bg-blue-700 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">Siguiente</button>
                    <button data-submit-step class="rounded-md bg-blue-700 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">Guardar y enviar a revisión</button>
                </div>
            </div>
        </form>
    </div>

    <script>
        (() => {
            const form = document.querySelector('[data-enf-wizard-form]');

            if (!form) {
                return;
            }

            const totalSteps = Number(form.dataset.totalSteps || 1);
            const storageKey = form.dataset.storageKey || 'enf-form-dvus-016-draft';
            const clearDraftOnLoad = form.dataset.clearDraftOnLoad === '1';
            const panels = Array.from(form.querySelectorAll('[data-step-panel]'));
            const previousButton = form.querySelector('[data-previous-step]');
            const nextButton = form.querySelector('[data-next-step]');
            const submitButton = form.querySelector('[data-submit-step]');
            const status = form.querySelector('[data-autosave-status]');
            const certificateNameSource = form.querySelector('[data-certificate-name-source]');
            const certificateNameTarget = form.querySelector('[data-sync-certificate-name]');
            const certifiedHours = form.querySelector('[data-certified-hours]');

            if (clearDraftOnLoad) {
                window.localStorage.removeItem(storageKey);
                window.localStorage.removeItem(`${storageKey}:step`);
            }

            let step = Number(window.localStorage.getItem(`${storageKey}:step`) || 1);
            let autosaveTimer = null;

            const clampStep = (value) => Math.min(Math.max(Number(value) || 1, 1), totalSteps);
            const fieldSelector = (name) => `[name="${String(name).replace(/"/g, '\\"')}"]`;

            const syncCalculatedFields = () => {
                if (certificateNameSource && certificateNameTarget) {
                    certificateNameTarget.value = certificateNameSource.value;
                }

                const teoricas = Number(form.querySelector('[name="horas_teoricas"]')?.value || 0);
                const practicas = Number(form.querySelector('[name="horas_practicas"]')?.value || 0);
                const totalHours = form.querySelector('[data-total-hours]');

                if (totalHours) {
                    totalHours.value = String(teoricas + practicas);
                }

                if (certifiedHours) {
                    certifiedHours.value = totalHours?.value || String(teoricas + practicas);
                }

                const hombres = Number(form.querySelector('[name="beneficiarios[hombres]"]')?.value || 0);
                const mujeres = Number(form.querySelector('[name="beneficiarios[mujeres]"]')?.value || 0);
                const cuposTotal = form.querySelector('[data-cupos-total]');

                if (cuposTotal) {
                    cuposTotal.value = String(hombres + mujeres);
                }
            };

            const save = () => {
                const data = {};

                syncCalculatedFields();

                form.querySelectorAll('input[name], select[name], textarea[name]').forEach((field) => {
                    if (field.type === 'hidden' || field.type === 'file' || field.name === '_token' || field.disabled) {
                        return;
                    }

                    if (field.type === 'checkbox') {
                        data[field.name] = data[field.name] || [];

                        if (field.checked) {
                            data[field.name].push(field.value);
                        }

                        return;
                    }

                    if (field.type === 'radio') {
                        if (field.checked) {
                            data[field.name] = field.value;
                        }

                        return;
                    }

                    data[field.name] = field.value;
                });

                window.localStorage.setItem(storageKey, JSON.stringify(data));
                if (status) {
                    status.textContent = `Autoguardado ${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
                }
            };

            const restore = () => {
                const stored = window.localStorage.getItem(storageKey);

                if (!stored) {
                    return;
                }

                let data = {};

                try {
                    data = JSON.parse(stored);
                } catch (error) {
                    return;
                }

                form.querySelectorAll('input[name], select[name], textarea[name]').forEach((field) => {
                    if (field.type === 'hidden' || field.type === 'file' || field.name === '_token' || field.disabled || !(field.name in data)) {
                        return;
                    }

                    const value = data[field.name];

                    if (field.type === 'checkbox') {
                        field.checked = Array.isArray(value) && value.includes(field.value);
                        return;
                    }

                    if (field.type === 'radio') {
                        field.checked = value === field.value;
                        return;
                    }

                    field.value = value;
                });
            };

            const updateContraparteState = () => {
                const hasContraparte = form.querySelector('[data-contraparte-toggle][value="Si"]')?.checked === true;

                form.querySelectorAll('[data-contraparte-field]').forEach((field) => {
                    field.disabled = !hasContraparte;
                    field.classList.toggle('opacity-60', !hasContraparte);
                    field.classList.toggle('cursor-not-allowed', !hasContraparte);
                });
            };

            const fieldHasValue = (field) => {
                if (field.type === 'checkbox' || field.type === 'radio') {
                    return field.checked;
                }

                return String(field.value ?? '').trim() !== '';
            };

            const stepIsComplete = (stepNumber) => {
                const panel = form.querySelector(`[data-step-panel="${stepNumber}"]`);

                if (!panel) {
                    return false;
                }

                const required = Array.from(panel.querySelectorAll('[required]'))
                    .filter((field) => !field.disabled);

                return required.every(fieldHasValue);
            };

            const render = () => {
                step = clampStep(step);
                window.localStorage.setItem(`${storageKey}:step`, step);

                panels.forEach((panel) => {
                    panel.classList.toggle('hidden', Number(panel.dataset.stepPanel) !== step);
                });

                for (let index = 1; index <= totalSteps; index += 1) {
                    const number = form.querySelector(`[data-step-number="${index}"]`);
                    const label = form.querySelector(`[data-step-label="${index}"]`);
                    const divider = form.querySelector(`[data-step-divider="${index}"]`);

                    number?.classList.remove('bg-blue-600', 'text-white', 'ring-2', 'ring-blue-200', 'bg-green-500', 'bg-gray-200', 'text-gray-600', 'dark:bg-gray-700', 'dark:text-gray-300');
                    label?.classList.remove('font-semibold', 'text-blue-600', 'text-green-600', 'dark:text-green-400', 'text-gray-500');
                    divider?.classList.remove('bg-green-500', 'bg-gray-200', 'dark:bg-gray-700');

                    const isComplete = stepIsComplete(index);

                    if (index === step) {
                        if (number) {
                            number.textContent = isComplete ? '✓' : index;
                        }
                        number?.classList.add('bg-blue-600', 'text-white', 'ring-2', 'ring-blue-200');
                        label?.classList.add('font-semibold', 'text-blue-600');
                    } else if (isComplete) {
                        if (number) {
                            number.textContent = '✓';
                        }
                        number?.classList.add('bg-green-500', 'text-white');
                        label?.classList.add('text-green-600', 'dark:text-green-400');
                    } else {
                        if (number) {
                            number.textContent = index;
                        }
                        number?.classList.add('bg-gray-200', 'text-gray-600', 'dark:bg-gray-700', 'dark:text-gray-300');
                        label?.classList.add('text-gray-500');
                    }

                    divider?.classList.add(stepIsComplete(index) ? 'bg-green-500' : 'bg-gray-200', 'dark:bg-gray-700');
                }

                previousButton?.classList.toggle('hidden', step === 1);
                nextButton?.classList.toggle('hidden', step === totalSteps);
                submitButton?.classList.toggle('hidden', step !== totalSteps);
            };

            const goTo = (targetStep) => {
                step = clampStep(targetStep);
                syncCalculatedFields();
                updateContraparteState();
                render();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            };

            const debouncedSave = () => {
                window.clearTimeout(autosaveTimer);
                autosaveTimer = window.setTimeout(save, 500);
            };

            restore();
            syncCalculatedFields();
            updateContraparteState();
            render();

            form.querySelectorAll('[data-step-button]').forEach((button) => {
                button.addEventListener('click', () => goTo(button.dataset.stepButton));
            });

            previousButton?.addEventListener('click', () => goTo(step - 1));
            nextButton?.addEventListener('click', () => goTo(step + 1));
            form.addEventListener('input', () => {
                syncCalculatedFields();
                updateContraparteState();
                render();
                debouncedSave();
            });
            form.addEventListener('change', () => {
                syncCalculatedFields();
                updateContraparteState();
                render();
                save();
            });
            form.addEventListener('submit', () => {
                syncCalculatedFields();
                window.localStorage.removeItem(storageKey);
                window.localStorage.removeItem(`${storageKey}:step`);
            });
        })();
    </script>
@endsection
