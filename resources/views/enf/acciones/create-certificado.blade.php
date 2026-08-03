@extends('layouts.panel.base')

@php
    $editingAccion = $accion ?? null;
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
    $formAction = $editingAccion ? route('enf.acciones.update', $editingAccion) : route('enf.acciones.store');
    $storageKey = $editingAccion ? "enf-form-dvus-016-draft-{$editingAccion->id}" : 'enf-form-dvus-016-draft';
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

        <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" class="space-y-6" data-enf-wizard-form data-total-steps="{{ count($stepLabels) }}" data-storage-key="{{ $storageKey }}" data-clear-draft-on-load="{{ $clearDraftOnLoad ? '1' : '0' }}" data-lock-step-navigation="{{ $editingAccion ? '0' : '1' }}" data-record-id="{{ $editingAccion?->id }}" data-autosave-url="{{ route('enf.acciones.autoguardar-borrador') }}" data-autosave-update-url-template="{{ route('enf.acciones.autoguardar-borrador.update', ['accion' => '__ID__']) }}" data-destinatarios-url-template="{{ route('enf.acciones.destinatarios-inscripcion', ['accion' => '__ID__']) }}" data-send-review-url-template="{{ route('enf.acciones.enviar-revision', ['accion' => '__ID__']) }}">
            @csrf
            @if ($editingAccion)
                @method('PUT')
            @endif
            <input type="hidden" name="borrador_autoguardado_id" value="{{ $editingAccion?->id }}">
            <input type="hidden" name="tipo_accion_id" value="{{ old('tipo_accion_id', $tipoAccionVinculacionEnfId ?: $tiposAccion->first()?->id) }}">
            <input type="hidden" name="codigo_formulario" value="FORM-DVUS-016">
            <input type="hidden" name="estado_flujo" value="BORRADOR">
            <input type="hidden" name="catalogos[tipo_accion_enf][]" value="{{ $tipoCertificadoId }}">
            <input type="hidden" name="certificado[nombre_certificado]" data-sync-certificate-name value="{{ old('certificado.nombre_certificado') }}">

            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-gray-900">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Registro por pasos</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400" data-autosave-status>Los cambios se autoguardan mientras escribe.</p>
                        <p class="mt-1 hidden text-xs font-semibold text-red-600 dark:text-red-400" data-step-validation-message></p>
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
                    <div class="md:col-start-1">
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
                    <section class="rounded-md border border-slate-200 p-4 dark:border-slate-700" data-required-collection="certificado_carreras">
                        <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100" data-required-collection-label-target>Carreras aprobadas por Consejo Universitario</h3>
                            <button type="button" data-open-career-modal class="rounded-md bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">Agregar carrera</button>
                        </div>
                        <div class="overflow-x-auto rounded-md border border-slate-100 dark:border-slate-800">
                            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500 dark:bg-slate-800/60">
                                    <tr>
                                        <th class="px-3 py-2">Carrera</th>
                                        <th class="px-3 py-2">Nombre</th>
                                        <th class="px-3 py-2">Acuerdo</th>
                                        <th class="px-3 py-2"></th>
                                    </tr>
                                </thead>
                                <tbody data-careers-list class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <tr><td colspan="4" class="px-3 py-4 text-center text-slate-500">Sin carreras agregadas.</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="hidden" data-careers-fields></div>
                    </section>

                    <section class="rounded-md border border-slate-200 p-4 dark:border-slate-700" data-required-collection="espacios_aprendizaje">
                        <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100" data-required-collection-label-target>Información general del certificado universitario</h3>
                            <button type="button" data-open-learning-space-modal class="rounded-md bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">Agregar espacio</button>
                        </div>
                        <div class="overflow-x-auto rounded-md border border-slate-100 dark:border-slate-800">
                            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500 dark:bg-slate-800/60">
                                    <tr>
                                        <th class="px-3 py-2">Nombre asignatura</th>
                                        <th class="px-3 py-2">Código</th>
                                        <th class="px-3 py-2">Créditos</th>
                                        <th class="px-3 py-2">Horas</th>
                                        <th class="px-3 py-2"></th>
                                    </tr>
                                </thead>
                                <tbody data-learning-spaces-list class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <tr><td colspan="5" class="px-3 py-4 text-center text-slate-500">Sin espacios agregados.</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="hidden" data-learning-spaces-fields></div>
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
                    <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Equipo docente</h3>
                        <button type="button" data-add-teacher class="rounded-md bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">Agregar docente</button>
                    </div>
                    <div class="space-y-4" data-teacher-team-list></div>
                    <template data-teacher-team-template>
                        <div class="rounded-md border border-slate-200 p-4 dark:border-slate-700" data-teacher-row>
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200" data-teacher-title>Docente</p>
                                <button type="button" data-remove-teacher class="rounded-md border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-red-800 dark:text-red-300 dark:hover:bg-red-950/40">Quitar</button>
                            </div>
                                <div class="mb-3 grid grid-cols-1 gap-3 md:grid-cols-3">
                                    <select data-teacher-field="perfil_docente" class="{{ $input }}">
                                        <option value="">Perfil del docente...</option>
                                        <option>Profesor de la UNAH</option>
                                        <option>Consultor Nacional</option>
                                        <option>Consultor Internacional</option>
                                    </select>
                                    <input data-teacher-field="nombre_completo" class="{{ $input }}" placeholder="Nombre completo">
                                    <input data-teacher-field="espacio_aprendizaje" class="{{ $input }}" placeholder="Espacio de aprendizaje que impartirá">
                                </div>
                                <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                                    <input data-teacher-field="numero_empleado" class="{{ $input }}" placeholder="No. empleado / identificación">
                                    <input type="email" data-teacher-field="correo" class="{{ $input }}" placeholder="Correo">
                                    <input data-teacher-field="categoria" class="{{ $input }}" placeholder="Categoría docente">
                                    <input data-teacher-field="departamento" class="{{ $input }}" placeholder="Departamento académico">
                                    <input data-teacher-field="ultimo_titulo" class="{{ $input }}" placeholder="Último título académico">
                                    <input data-teacher-field="pais_procedencia" class="{{ $input }}" placeholder="País de procedencia">
                                    <input data-teacher-field="universidad_procedencia" class="{{ $input }}" placeholder="Universidad de procedencia">
                                    <input type="number" min="0" data-teacher-field="horas_contratadas" class="{{ $input }}" placeholder="Horas">
                                </div>
                                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <label class="flex items-center gap-3 text-sm text-slate-700 dark:text-slate-300">
                                        <input type="checkbox" data-teacher-field="carga_academica_pac" value="Si" class="rounded border-gray-300 text-blue-600">
                                        Carga académica del PAC
                                    </label>
                                    <label class="flex items-center gap-3 text-sm text-slate-700 dark:text-slate-300">
                                        <input type="checkbox" data-teacher-field="contratacion_jornada_contraria" value="Si" class="rounded border-gray-300 text-blue-600">
                                        Contratación jornada contraria
                                    </label>
                                </div>
                            </div>
                    </template>
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
                    <div><label class="{{ $label }}">RTN / identificación internacional</label><input name="contraparte[rtn]" maxlength="50" class="{{ $input }}" data-contraparte-field></div>
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

        @include('enf.acciones.partials.send-review-modal')

        <div data-career-modal class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-2xl rounded-lg bg-white p-5 shadow-xl dark:bg-slate-900">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 data-career-modal-title class="text-base font-semibold text-slate-900 dark:text-slate-100">Agregar carrera</h2>
                    <button type="button" data-close-career-modal class="rounded-md px-3 py-1 text-sm font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">Cerrar</button>
                </div>
                <div class="grid grid-cols-1 gap-3">
                    <div>
                        <label class="{{ $label }}">Carrera registrada <span class="text-red-500">*</span></label>
                        <select data-career-id class="{{ $input }}">
                            <option value="">Carrera registrada...</option>
                            @foreach ($carreras as $carrera)
                                <option value="{{ $carrera->id }}">{{ $carrera->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $label }}">Nombre de la carrera <span class="text-red-500">*</span></label>
                        <input data-career-name class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">No. acuerdo de Consejo Universitario <span class="text-red-500">*</span></label>
                        <input data-career-agreement class="{{ $input }}">
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-3">
                    <button type="button" data-close-career-modal class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Cancelar</button>
                    <button type="button" data-save-career class="rounded-md bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">Guardar</button>
                </div>
            </div>
        </div>

        <div data-learning-space-modal class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-3xl rounded-lg bg-white p-5 shadow-xl dark:bg-slate-900">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 data-learning-space-modal-title class="text-base font-semibold text-slate-900 dark:text-slate-100">Agregar espacio de aprendizaje</h2>
                    <button type="button" data-close-learning-space-modal class="rounded-md px-3 py-1 text-sm font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">Cerrar</button>
                </div>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                    <div class="md:col-span-2">
                        <label class="{{ $label }}">Nombre asignatura <span class="text-red-500">*</span></label>
                        <input data-learning-space-name class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Código <span class="text-red-500">*</span></label>
                        <input data-learning-space-code class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Créditos <span class="text-red-500">*</span></label>
                        <input type="number" min="0" data-learning-space-credits class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Horas <span class="text-red-500">*</span></label>
                        <input type="number" min="0" data-learning-space-hours class="{{ $input }}">
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-3">
                    <button type="button" data-close-learning-space-modal class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Cancelar</button>
                    <button type="button" data-save-learning-space class="rounded-md bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">Guardar</button>
                </div>
            </div>
        </div>
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
            const shouldLockStepNavigation = form.dataset.lockStepNavigation === '1';
            const initialDraft = @js($initialDraft ?? []);
            const autosaveUrl = form.dataset.autosaveUrl;
            const autosaveUpdateUrlTemplate = form.dataset.autosaveUpdateUrlTemplate || '';
            const draftIdField = form.querySelector('[name="borrador_autoguardado_id"]');
            const panels = Array.from(form.querySelectorAll('[data-step-panel]'));
            const previousButton = form.querySelector('[data-previous-step]');
            const nextButton = form.querySelector('[data-next-step]');
            const submitButton = form.querySelector('[data-submit-step]');
            const status = form.querySelector('[data-autosave-status]');
            const stepValidationMessage = form.querySelector('[data-step-validation-message]');
            const certificateNameSource = form.querySelector('[data-certificate-name-source]');
            const certificateNameTarget = form.querySelector('[data-sync-certificate-name]');
            const certifiedHours = form.querySelector('[data-certified-hours]');
            const careerOptions = @js($carreras->map(fn ($carrera) => ['id' => (string) $carrera->id, 'nombre' => $carrera->nombre])->values());
            const oldCertificateCareers = @js(old('certificado_carreras', []));
            const oldLearningSpaces = @js(old('espacios_aprendizaje', []));
            const oldTeacherTeam = @js(old('equipo_docente', []));
            const careerModal = document.querySelector('[data-career-modal]');
            const careerModalTitle = document.querySelector('[data-career-modal-title]');
            const careerIdField = document.querySelector('[data-career-id]');
            const careerNameField = document.querySelector('[data-career-name]');
            const careerAgreementField = document.querySelector('[data-career-agreement]');
            const learningSpaceModal = document.querySelector('[data-learning-space-modal]');
            const learningSpaceModalTitle = document.querySelector('[data-learning-space-modal-title]');
            const learningSpaceNameField = document.querySelector('[data-learning-space-name]');
            const learningSpaceCodeField = document.querySelector('[data-learning-space-code]');
            const learningSpaceCreditsField = document.querySelector('[data-learning-space-credits]');
            const learningSpaceHoursField = document.querySelector('[data-learning-space-hours]');
            const careerFieldsContainer = form.querySelector('[data-careers-fields]');
            const careersList = form.querySelector('[data-careers-list]');
            const learningSpacesFieldsContainer = form.querySelector('[data-learning-spaces-fields]');
            const learningSpacesList = form.querySelector('[data-learning-spaces-list]');
            const teacherTeamList = form.querySelector('[data-teacher-team-list]');
            const teacherTeamTemplate = form.querySelector('[data-teacher-team-template]');
            const addTeacherButton = form.querySelector('[data-add-teacher]');

            if (clearDraftOnLoad) {
                window.localStorage.removeItem(storageKey);
                window.localStorage.removeItem(`${storageKey}:step`);
            }

            let step = Number(window.localStorage.getItem(`${storageKey}:step`) || 1);
            let autosaveTimer = null;
            let serverAutosaveTimer = null;
            let serverAutosavePromise = Promise.resolve();
            let serverAutosaveDirty = false;
            let serverAutosaveInFlight = false;
            let shouldPersistDraft = Boolean(form.dataset.recordId || draftIdField?.value);
            let draftRecordId = form.dataset.recordId || draftIdField?.value || '';
            let submittingAfterAutosave = false;
            let restoredDraftData = {};
            let sendReviewEtapas = [];
            let sendReviewStep = 0;
            const sendReviewModal = document.querySelector('[data-enf-send-modal]');
            const sendReviewSteps = document.querySelector('[data-enf-send-steps]');
            const sendReviewBody = document.querySelector('[data-enf-send-body]');
            const sendReviewPrev = document.querySelector('[data-enf-send-prev]');
            const sendReviewNext = document.querySelector('[data-enf-send-next]');
            const sendReviewConfirm = document.querySelector('[data-enf-send-confirm]');
            const destinatariosUrlTemplate = form.dataset.destinatariosUrlTemplate || '';
            const sendReviewUrlTemplate = form.dataset.sendReviewUrlTemplate || '';
            const hideSendReviewModal = () => {
                sendReviewModal?.classList.add('hidden');
                sendReviewModal?.classList.remove('flex');
            };
            const showSendReviewModal = () => {
                sendReviewModal?.classList.remove('hidden');
                sendReviewModal?.classList.add('flex');
            };
            const destinatariosUrl = () => draftRecordId && destinatariosUrlTemplate
                ? destinatariosUrlTemplate.replace('__ID__', encodeURIComponent(draftRecordId))
                : '';
            const selectedCandidate = (etapa) => {
                const value = etapa.selected_user_id || sendReviewModal?.querySelector(`[data-enf-destinatario-select="${etapa.id}"]`)?.value;
                return (etapa.candidatos || []).find((candidate) => String(candidate.user_id) === String(value));
            };
            const renderSendReviewModal = () => {
                if (!sendReviewSteps || !sendReviewBody) return;
                const total = sendReviewEtapas.length + 1;
                sendReviewSteps.innerHTML = [
                    ...sendReviewEtapas.map((etapa, index) => `
                        <div class="flex items-center gap-1.5 ${sendReviewStep < index ? 'opacity-40' : ''}">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold ${sendReviewStep === index ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900' : (sendReviewStep > index ? 'bg-emerald-500 text-white' : 'border border-slate-300 text-slate-400 dark:border-slate-600')}">${sendReviewStep > index ? '✓' : index + 1}</span>
                            <span class="hidden text-xs sm:block ${sendReviewStep === index ? 'font-semibold text-slate-900 dark:text-white' : 'text-slate-400'}">${etapa.nombre}</span>
                        </div>
                        <span class="text-xs text-slate-300 dark:text-slate-600">→</span>
                    `),
                    `<div class="flex items-center gap-1.5 ${sendReviewStep < sendReviewEtapas.length ? 'opacity-40' : ''}">
                        <span class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold ${sendReviewStep === sendReviewEtapas.length ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900' : 'border border-slate-300 text-slate-400 dark:border-slate-600'}">${total}</span>
                        <span class="hidden text-xs sm:block ${sendReviewStep === sendReviewEtapas.length ? 'font-semibold text-slate-900 dark:text-white' : 'text-slate-400'}">Confirmación</span>
                    </div>`,
                ].join('');
                if (sendReviewStep < sendReviewEtapas.length) {
                    const etapa = sendReviewEtapas[sendReviewStep];
                    sendReviewBody.innerHTML = `
                        <div class="mt-5 rounded-xl border border-slate-200 p-5 dark:border-slate-700">
                            <div class="mb-4">
                                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">${etapa.nombre}</h3>
                                ${etapa.codigo ? `<span class="mt-1 inline-block rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">${etapa.codigo}</span>` : ''}
                                ${etapa.rol_nombre ? `<p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Rol requerido: <span class="font-medium">${etapa.rol_nombre}</span></p>` : ''}
                            </div>
                            <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300">Seleccione el destinatario</label>
                            <select data-enf-destinatario-select="${etapa.id}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                <option value="">Seleccione un destinatario...</option>
                                ${(etapa.candidatos || []).map((candidate) => `<option value="${candidate.user_id}" ${String(etapa.selected_user_id || '') === String(candidate.user_id) ? 'selected' : ''}>${candidate.nombre}</option>`).join('')}
                            </select>
                            <p data-enf-send-error class="mt-2 hidden text-xs text-red-600">Seleccione un destinatario para continuar.</p>
                        </div>`;
                } else {
                    sendReviewBody.innerHTML = `
                        <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-5 dark:border-emerald-900/50 dark:bg-emerald-950/30">
                            <h3 class="font-semibold text-emerald-800 dark:text-emerald-200">Listo para enviar</h3>
                            <p class="mt-1 text-sm text-emerald-700 dark:text-emerald-300">La acción ENF será enviada al flujo de aprobación configurado.</p>
                            <div class="mt-4 space-y-2">
                                ${sendReviewEtapas.map((etapa) => {
                                    const selected = selectedCandidate(etapa);
                                    return `<div class="flex items-center gap-2 text-xs text-emerald-800 dark:text-emerald-200"><span class="font-medium">${etapa.nombre}:</span><span>${selected ? selected.nombre : '—'}</span></div>`;
                                }).join('')}
                            </div>
                        </div>`;
                }
                sendReviewPrev?.classList.toggle('hidden', sendReviewStep === 0);
                sendReviewNext?.classList.toggle('hidden', sendReviewStep >= sendReviewEtapas.length);
                sendReviewConfirm?.classList.toggle('hidden', sendReviewStep < sendReviewEtapas.length);
            };
            const appendDestinatariosToForm = () => {
                form.querySelectorAll('[data-enf-destinatario-hidden]').forEach((field) => field.remove());
                sendReviewEtapas.forEach((etapa) => {
                    const selected = selectedCandidate(etapa);
                    if (!selected) return;
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `destinatarios[${etapa.id}]`;
                    input.value = selected.user_id;
                    input.dataset.enfDestinatarioHidden = '1';
                    form.appendChild(input);
                });
            };
            const finalSubmit = () => {
                const sendUrl = draftRecordId && sendReviewUrlTemplate
                    ? sendReviewUrlTemplate.replace('__ID__', encodeURIComponent(draftRecordId))
                    : '';

                if (sendUrl) {
                    const flowForm = document.createElement('form');
                    flowForm.method = 'POST';
                    flowForm.action = sendUrl;

                    const token = form.querySelector('[name="_token"]')?.value || '';
                    const tokenInput = document.createElement('input');
                    tokenInput.type = 'hidden';
                    tokenInput.name = '_token';
                    tokenInput.value = token;
                    flowForm.appendChild(tokenInput);

                    appendDestinatariosToForm();
                    form.querySelectorAll('[data-enf-destinatario-hidden]').forEach((field) => {
                        flowForm.appendChild(field.cloneNode());
                    });

                    document.body.appendChild(flowForm);
                    window.localStorage.removeItem(storageKey);
                    window.localStorage.removeItem(`${storageKey}:step`);
                    flowForm.submit();
                    return;
                }

                window.localStorage.removeItem(storageKey);
                window.localStorage.removeItem(`${storageKey}:step`);
                submittingAfterAutosave = true;
                HTMLFormElement.prototype.submit.call(form);
            };
            const openSendReviewOrSubmit = () => {
                const url = destinatariosUrl();
                if (!url) {
                    finalSubmit();
                    return;
                }
                fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then((response) => response.ok ? response.json() : Promise.reject())
                    .then((payload) => {
                        sendReviewEtapas = payload.etapas || [];
                        if (sendReviewEtapas.length === 0) {
                            finalSubmit();
                            return;
                        }
                        sendReviewStep = 0;
                        renderSendReviewModal();
                        showSendReviewModal();
                        submitButton?.removeAttribute('disabled');
                    })
                    .catch(() => submitButton?.removeAttribute('disabled'));
            };
            document.querySelectorAll('[data-enf-send-close], [data-enf-send-cancel]').forEach((button) => button.addEventListener('click', hideSendReviewModal));
            sendReviewPrev?.addEventListener('click', () => {
                sendReviewStep = Math.max(0, sendReviewStep - 1);
                renderSendReviewModal();
            });
            sendReviewNext?.addEventListener('click', () => {
                const etapa = sendReviewEtapas[sendReviewStep];
                const select = sendReviewModal?.querySelector(`[data-enf-destinatario-select="${etapa.id}"]`);
                etapa.selected_user_id = select?.value || etapa.selected_user_id || '';
                const selected = selectedCandidate(etapa);
                const error = sendReviewBody?.querySelector('[data-enf-send-error]');
                if (!selected) {
                    error?.classList.remove('hidden');
                    return;
                }
                sendReviewStep++;
                renderSendReviewModal();
            });
            sendReviewConfirm?.addEventListener('click', () => {
                appendDestinatariosToForm();
                hideSendReviewModal();
                finalSubmit();
            });
            let certificateCareers = [];
            let learningSpaces = [];
            let editingCareerIndex = null;
            let editingLearningSpaceIndex = null;

            const clampStep = (value) => Math.min(Math.max(Number(value) || 1, 1), totalSteps);
            const fieldSelector = (name) => `[name="${String(name).replace(/"/g, '\\"')}"]`;
            const collectionFieldMap = {
                certificado_carreras: ['carrera_id', 'nombre_carrera', 'acuerdo_consejo_universitario'],
                espacios_aprendizaje: ['nombre', 'codigo', 'creditos', 'horas'],
            };
            const teacherFieldNames = [
                'perfil_docente',
                'nombre_completo',
                'espacio_aprendizaje',
                'numero_empleado',
                'correo',
                'categoria',
                'departamento',
                'ultimo_titulo',
                'pais_procedencia',
                'universidad_procedencia',
                'horas_contratadas',
                'carga_academica_pac',
                'contratacion_jornada_contraria',
            ];

            const showModal = (modal) => {
                modal?.classList.remove('hidden');
                modal?.classList.add('flex');
            };

            const hideModal = (modal) => {
                modal?.classList.add('hidden');
                modal?.classList.remove('flex');
            };

            const escapeHtml = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

            const normalizeCollectionRows = (rows, fields) => Object.values(rows || {})
                .map((row) => {
                    const normalized = {};

                    fields.forEach((field) => {
                        normalized[field] = String(row?.[field] ?? '').trim();
                    });

                    return normalized;
                })
                .filter((row) => fields.some((field) => row[field] !== ''));
            const collectionHasRows = (rows) => Object.keys(rows || {}).length > 0;

            const rowsFromDraftData = (data, prefix, fields) => {
                const rows = {};
                const directRows = Array.isArray(data?.[prefix]) ? data[prefix] : [];

                directRows.forEach((row, index) => {
                    rows[index] = { ...(rows[index] || {}), ...(row || {}) };
                });

                Object.entries(data || {}).forEach(([key, value]) => {
                    const match = key.match(new RegExp(`^${prefix}\\[(\\d+)\\]\\[([^\\]]+)\\]$`));

                    if (!match) {
                        return;
                    }

                    const [, index, field] = match;
                    rows[index] = rows[index] || {};
                    rows[index][field] = value;
                });

                return normalizeCollectionRows(rows, fields);
            };

            const valueIsChecked = (value) => {
                if (Array.isArray(value)) {
                    return value.includes('Si') || value.includes('1') || value.includes(true);
                }

                return ['si', 'sí', '1', 'true', 'on'].includes(String(value ?? '').trim().toLowerCase());
            };

            const teacherRowsFromDraftData = (data) => rowsFromDraftData(data, 'equipo_docente', teacherFieldNames);

            const renumberTeacherRows = () => {
                const rows = Array.from(teacherTeamList?.querySelectorAll('[data-teacher-row]') || []);

                rows.forEach((row, index) => {
                    row.dataset.teacherIndex = String(index);

                    const title = row.querySelector('[data-teacher-title]');
                    const removeButton = row.querySelector('[data-remove-teacher]');

                    if (title) {
                        title.textContent = `Docente ${index + 1}`;
                    }

                    if (removeButton) {
                        removeButton.disabled = rows.length <= 1;
                    }

                    row.querySelectorAll('[data-teacher-field]').forEach((field) => {
                        field.name = `equipo_docente[${index}][${field.dataset.teacherField}]`;
                    });
                });
            };

            const addTeacherRow = (values = {}) => {
                if (!teacherTeamList || !teacherTeamTemplate) {
                    return null;
                }

                const fragment = teacherTeamTemplate.content.cloneNode(true);
                const row = fragment.querySelector('[data-teacher-row]');

                row.querySelectorAll('[data-teacher-field]').forEach((field) => {
                    const value = values?.[field.dataset.teacherField];

                    if (field.type === 'checkbox') {
                        field.checked = valueIsChecked(value);
                        return;
                    }

                    field.value = value ?? '';
                });

                teacherTeamList.appendChild(fragment);
                renumberTeacherRows();

                return row;
            };

            const resetTeacherRows = (rows = []) => {
                if (!teacherTeamList) {
                    return;
                }

                teacherTeamList.innerHTML = '';

                const rowsToRender = rows.length > 0 ? rows : [{}];
                rowsToRender.forEach((row) => addTeacherRow(row));
                renumberTeacherRows();
            };

            const appendCollectionToData = (data, prefix, rows, fields) => {
                rows.forEach((row, index) => {
                    fields.forEach((field) => {
                        data[`${prefix}[${index}][${field}]`] = row[field] || '';
                    });
                });
            };

            const collectionRows = (name) => {
                if (name === 'certificado_carreras') {
                    return certificateCareers;
                }

                if (name === 'espacios_aprendizaje') {
                    return learningSpaces;
                }

                return [];
            };

            const collectionIsComplete = (name) => collectionRows(name).length > 0;

            const careerNameById = (id) => careerOptions.find((career) => String(career.id) === String(id))?.nombre || '';

            const renderHiddenCollectionFields = (container, prefix, rows, fields) => {
                if (!container) {
                    return;
                }

                container.innerHTML = rows.map((row, index) => fields.map((field) => {
                    const value = escapeHtml(row[field] || '');

                    return `<input type="hidden" name="${prefix}[${index}][${field}]" value="${value}">`;
                }).join('')).join('');
            };

            const renderCareers = () => {
                renderHiddenCollectionFields(careerFieldsContainer, 'certificado_carreras', certificateCareers, collectionFieldMap.certificado_carreras);

                if (!careersList) {
                    return;
                }

                if (certificateCareers.length === 0) {
                    careersList.innerHTML = '<tr><td colspan="4" class="px-3 py-4 text-center text-slate-500">Sin carreras agregadas.</td></tr>';
                    return;
                }

                careersList.innerHTML = certificateCareers.map((career, index) => {
                    const selectedCareer = careerNameById(career.carrera_id);

                    return `
                        <tr>
                            <td class="px-3 py-2 text-slate-700 dark:text-slate-300">${escapeHtml(selectedCareer || '-')}</td>
                            <td class="px-3 py-2 text-slate-700 dark:text-slate-300">${escapeHtml(career.nombre_carrera || '-')}</td>
                            <td class="px-3 py-2 text-slate-700 dark:text-slate-300">${escapeHtml(career.acuerdo_consejo_universitario || '-')}</td>
                            <td class="px-3 py-2 text-right">
                                <button type="button" data-edit-career="${index}" class="text-xs font-semibold text-blue-700 hover:text-blue-900 dark:text-blue-400">Editar</button>
                                <button type="button" data-delete-career="${index}" class="ml-3 text-xs font-semibold text-red-600 hover:text-red-800 dark:text-red-400">Eliminar</button>
                            </td>
                        </tr>
                    `;
                }).join('');
            };

            const renderLearningSpaces = () => {
                renderHiddenCollectionFields(learningSpacesFieldsContainer, 'espacios_aprendizaje', learningSpaces, collectionFieldMap.espacios_aprendizaje);

                if (!learningSpacesList) {
                    return;
                }

                if (learningSpaces.length === 0) {
                    learningSpacesList.innerHTML = '<tr><td colspan="5" class="px-3 py-4 text-center text-slate-500">Sin espacios agregados.</td></tr>';
                    return;
                }

                learningSpacesList.innerHTML = learningSpaces.map((space, index) => `
                    <tr>
                        <td class="px-3 py-2 text-slate-700 dark:text-slate-300">${escapeHtml(space.nombre || '-')}</td>
                        <td class="px-3 py-2 text-slate-700 dark:text-slate-300">${escapeHtml(space.codigo || '-')}</td>
                        <td class="px-3 py-2 text-slate-700 dark:text-slate-300">${escapeHtml(space.creditos || '0')}</td>
                        <td class="px-3 py-2 text-slate-700 dark:text-slate-300">${escapeHtml(space.horas || '0')}</td>
                        <td class="px-3 py-2 text-right">
                            <button type="button" data-edit-learning-space="${index}" class="text-xs font-semibold text-blue-700 hover:text-blue-900 dark:text-blue-400">Editar</button>
                            <button type="button" data-delete-learning-space="${index}" class="ml-3 text-xs font-semibold text-red-600 hover:text-red-800 dark:text-red-400">Eliminar</button>
                        </td>
                    </tr>
                `).join('');
            };

            const renderCollections = () => {
                renderCareers();
                renderLearningSpaces();
            };

            const hydrateCollections = (data) => {
                certificateCareers = normalizeCollectionRows(
                    collectionHasRows(oldCertificateCareers) ? oldCertificateCareers : rowsFromDraftData(data, 'certificado_carreras', collectionFieldMap.certificado_carreras),
                    collectionFieldMap.certificado_carreras
                );
                learningSpaces = normalizeCollectionRows(
                    collectionHasRows(oldLearningSpaces) ? oldLearningSpaces : rowsFromDraftData(data, 'espacios_aprendizaje', collectionFieldMap.espacios_aprendizaje),
                    collectionFieldMap.espacios_aprendizaje
                );
                renderCollections();
            };

            const clearModalInvalidStyles = (modal) => {
                modal?.querySelectorAll('[data-modal-invalid]').forEach((field) => {
                    field.classList.remove('border-red-500', 'ring-1', 'ring-red-500');
                    field.removeAttribute('data-modal-invalid');
                });
            };

            const markModalInvalid = (field) => {
                field?.classList.add('border-red-500', 'ring-1', 'ring-red-500');
                field?.setAttribute('data-modal-invalid', '1');
            };

            const modalFieldsComplete = (modal, fields) => {
                clearModalInvalidStyles(modal);
                let firstInvalid = null;

                fields.forEach((field) => {
                    if (String(field?.value ?? '').trim() !== '') {
                        return;
                    }

                    markModalInvalid(field);
                    firstInvalid = firstInvalid || field;
                });

                firstInvalid?.focus();

                return !firstInvalid;
            };

            const openCareerModal = (index = null) => {
                editingCareerIndex = index;
                const current = index === null ? {} : certificateCareers[index] || {};

                if (careerModalTitle) {
                    careerModalTitle.textContent = index === null ? 'Agregar carrera' : 'Editar carrera';
                }

                careerIdField.value = current.carrera_id || '';
                careerNameField.value = current.nombre_carrera || '';
                careerAgreementField.value = current.acuerdo_consejo_universitario || '';
                clearModalInvalidStyles(careerModal);
                showModal(careerModal);
                careerIdField?.focus();
            };

            const closeCareerModal = () => {
                editingCareerIndex = null;
                hideModal(careerModal);
            };

            const saveCareerModal = () => {
                if (!modalFieldsComplete(careerModal, [careerIdField, careerNameField, careerAgreementField])) {
                    return;
                }

                const row = {
                    carrera_id: careerIdField.value,
                    nombre_carrera: careerNameField.value.trim(),
                    acuerdo_consejo_universitario: careerAgreementField.value.trim(),
                };

                if (editingCareerIndex === null) {
                    certificateCareers.push(row);
                } else {
                    certificateCareers[editingCareerIndex] = row;
                }

                renderCollections();
                syncRequiredMarkers();
                render();
                save();
                closeCareerModal();
            };

            const openLearningSpaceModal = (index = null) => {
                editingLearningSpaceIndex = index;
                const current = index === null ? {} : learningSpaces[index] || {};

                if (learningSpaceModalTitle) {
                    learningSpaceModalTitle.textContent = index === null ? 'Agregar espacio de aprendizaje' : 'Editar espacio de aprendizaje';
                }

                learningSpaceNameField.value = current.nombre || '';
                learningSpaceCodeField.value = current.codigo || '';
                learningSpaceCreditsField.value = current.creditos || '';
                learningSpaceHoursField.value = current.horas || '';
                clearModalInvalidStyles(learningSpaceModal);
                showModal(learningSpaceModal);
                learningSpaceNameField?.focus();
            };

            const closeLearningSpaceModal = () => {
                editingLearningSpaceIndex = null;
                hideModal(learningSpaceModal);
            };

            const saveLearningSpaceModal = () => {
                if (!modalFieldsComplete(learningSpaceModal, [learningSpaceNameField, learningSpaceCodeField, learningSpaceCreditsField, learningSpaceHoursField])) {
                    return;
                }

                const row = {
                    nombre: learningSpaceNameField.value.trim(),
                    codigo: learningSpaceCodeField.value.trim(),
                    creditos: learningSpaceCreditsField.value,
                    horas: learningSpaceHoursField.value,
                };

                if (editingLearningSpaceIndex === null) {
                    learningSpaces.push(row);
                } else {
                    learningSpaces[editingLearningSpaceIndex] = row;
                }

                renderCollections();
                syncRequiredMarkers();
                render();
                save();
                closeLearningSpaceModal();
            };

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

            const updateDraftRecord = (payload) => {
                if (!payload?.id) {
                    return;
                }

                draftRecordId = String(payload.id);
                form.dataset.recordId = draftRecordId;
                shouldPersistDraft = true;

                if (draftIdField) {
                    draftIdField.value = draftRecordId;
                }

                if (payload.edit_url && !window.location.pathname.endsWith(`/enf/acciones/${draftRecordId}/edit`)) {
                    window.history.replaceState({}, '', payload.edit_url);
                }
            };

            const autosaveEndpoint = () => {
                if (draftRecordId && autosaveUpdateUrlTemplate) {
                    return autosaveUpdateUrlTemplate.replace('__ID__', encodeURIComponent(draftRecordId));
                }

                return autosaveUrl;
            };

            const buildServerAutosaveData = () => {
                renderCollections();
                syncCalculatedFields();

                const formData = new FormData(form);

                Array.from(formData.entries()).forEach(([key, value]) => {
                    if (key === '_method') {
                        formData.delete(key);
                        return;
                    }

                    if (value instanceof File) {
                        formData.delete(key);
                    }
                });

                formData.set('estado_flujo', 'BORRADOR');

                if (draftRecordId) {
                    formData.set('borrador_autoguardado_id', draftRecordId);
                }

                return formData;
            };

            const serverAutosave = ({ force = false, keepalive = false } = {}) => {
                window.clearTimeout(serverAutosaveTimer);

                if (!force && !serverAutosaveDirty) {
                    return serverAutosavePromise;
                }

                const endpoint = autosaveEndpoint();

                if (!endpoint) {
                    return Promise.resolve();
                }

                serverAutosaveDirty = false;
                serverAutosaveInFlight = true;

                if (status) {
                    status.textContent = 'Guardando borrador...';
                }

                serverAutosavePromise = fetch(endpoint, {
                    method: 'POST',
                    body: buildServerAutosaveData(),
                    keepalive,
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                })
                    .then((response) => {
                        if (!response.ok) {
                            throw new Error(`Autosave failed with status ${response.status}`);
                        }

                        return response.json();
                    })
                    .then((payload) => {
                        updateDraftRecord(payload);

                        if (status) {
                            status.textContent = `Borrador guardado ${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
                        }
                    })
                    .catch(() => {
                        serverAutosaveDirty = true;

                        if (status) {
                            status.textContent = 'No se pudo guardar el borrador. Se reintentará.';
                        }
                    })
                    .finally(() => {
                        serverAutosaveInFlight = false;
                    });

                return serverAutosavePromise;
            };

            const scheduleServerAutosave = () => {
                shouldPersistDraft = true;
                serverAutosaveDirty = true;
                window.clearTimeout(serverAutosaveTimer);
                serverAutosaveTimer = window.setTimeout(() => serverAutosave(), 1500);
            };

            const save = ({ persist = true } = {}) => {
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

                appendCollectionToData(data, 'certificado_carreras', certificateCareers, collectionFieldMap.certificado_carreras);
                appendCollectionToData(data, 'espacios_aprendizaje', learningSpaces, collectionFieldMap.espacios_aprendizaje);

                window.localStorage.setItem(storageKey, JSON.stringify(data));
                if (status) {
                    status.textContent = `Autoguardado ${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
                }

                if (persist) {
                    scheduleServerAutosave();
                }
            };

            const restore = () => {
                const stored = window.localStorage.getItem(storageKey);
                let data = { ...initialDraft };

                try {
                    data = stored ? { ...data, ...JSON.parse(stored) } : data;
                } catch (error) {
                    data = { ...initialDraft };
                }

                restoredDraftData = data;
                resetTeacherRows(collectionHasRows(oldTeacherTeam)
                    ? normalizeCollectionRows(oldTeacherTeam, teacherFieldNames)
                    : teacherRowsFromDraftData(data));

                if (Object.keys(data).length === 0) {
                    return;
                }

                form.querySelectorAll('input[name], select[name], textarea[name]').forEach((field) => {
                    if (field.type === 'hidden' || field.type === 'file' || field.name === '_token' || field.disabled || !(field.name in data)) {
                        return;
                    }

                    const value = data[field.name];

                    if (field.type === 'checkbox') {
                        field.checked = Array.isArray(value) ? value.includes(field.value) : valueIsChecked(value);
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

                if (field.type === 'file') {
                    return field.files?.length > 0 || String(field.value ?? '').trim() !== '';
                }

                if (field.multiple) {
                    return Array.from(field.selectedOptions).some((option) => option.value !== '');
                }

                return String(field.value ?? '').trim() !== '';
            };

            const invalidFieldClasses = ['border-red-500', 'ring-1', 'ring-red-500'];
            const invalidChoiceClasses = ['text-red-600', 'dark:text-red-400'];
            const requiredMarkerSelector = '[data-wizard-required-marker]';

            const isStepField = (field) => {
                return !field.disabled
                    && field.type !== 'hidden'
                    && field.name !== '_token'
                    && !field.classList.contains('hidden');
            };

            const setValidationMessage = (message = '') => {
                if (!stepValidationMessage) {
                    return;
                }

                stepValidationMessage.textContent = message;
                stepValidationMessage.classList.toggle('hidden', !message);
            };

            const clearInvalidStyles = () => {
                form.querySelectorAll('[data-wizard-invalid]').forEach((target) => {
                    target.classList.remove(...invalidFieldClasses, ...invalidChoiceClasses);
                    target.removeAttribute('data-wizard-invalid');
                });
            };

            const clearValidationFeedback = () => {
                clearInvalidStyles();
                setValidationMessage();
            };

            const markInvalidTarget = (target, classes) => {
                if (!target) {
                    return;
                }

                target.classList.add(...classes);
                target.setAttribute('data-wizard-invalid', '1');
            };

            const appendRequiredMarker = (target) => {
                if (!target
                    || target.querySelector(requiredMarkerSelector)
                    || target.textContent.trim().endsWith('*')) {
                    return;
                }

                const marker = document.createElement('span');
                marker.dataset.wizardRequiredMarker = '1';
                marker.className = 'text-red-500';
                marker.setAttribute('aria-hidden', 'true');
                marker.textContent = ' *';
                target.appendChild(marker);
            };

            const restoreRequiredFieldHints = () => {
                form.querySelectorAll('[data-wizard-required-placeholder]').forEach((field) => {
                    field.placeholder = field.dataset.wizardRequiredPlaceholder || '';
                    delete field.dataset.wizardRequiredPlaceholder;
                });

                form.querySelectorAll('option[data-wizard-required-option]').forEach((option) => {
                    option.textContent = option.dataset.wizardRequiredOption || '';
                    delete option.dataset.wizardRequiredOption;
                });
            };

            const appendRequiredPlaceholder = (field) => {
                if (field.tagName === 'SELECT') {
                    const firstOption = field.options?.[0];

                    if (!firstOption || firstOption.value !== '' || firstOption.dataset.wizardRequiredOption !== undefined) {
                        return;
                    }

                    firstOption.dataset.wizardRequiredOption = firstOption.textContent;
                    firstOption.textContent = `${firstOption.textContent.replace(/\s\*$/, '')} *`;
                    return;
                }

                if (!('placeholder' in field) || !field.placeholder || field.dataset.wizardRequiredPlaceholder !== undefined) {
                    return;
                }

                field.dataset.wizardRequiredPlaceholder = field.placeholder;
                field.placeholder = `${field.placeholder.replace(/\s\*$/, '')} *`;
            };

            const fieldRequiredTarget = (field, panel) => {
                const labelFor = field.id
                    ? panel.querySelector(`label[for="${String(field.id).replace(/"/g, '\\"')}"]`)
                    : null;

                if (labelFor) {
                    return labelFor;
                }

                let node = field.parentElement;

                while (node && node !== panel) {
                    const target = Array.from(node.children).find((child) => {
                        return child.matches?.('label, p')
                            && !child.contains(field)
                            && !child.querySelector('input, select, textarea');
                    });

                    if (target) {
                        return target;
                    }

                    node = node.parentElement;
                }

                return null;
            };

            const stepFields = (panel) => Array.from(panel.querySelectorAll('input[name], select[name], textarea[name]'))
                .filter(isStepField);
            const requiredCollections = (panel) => Array.from(panel.querySelectorAll('[data-required-collection]'));

            const stepIsComplete = (stepNumber) => {
                const panel = form.querySelector(`[data-step-panel="${stepNumber}"]`);

                if (!panel) {
                    return false;
                }

                const groupedChoices = new Map();
                const fields = stepFields(panel);
                const collections = requiredCollections(panel);

                for (const field of fields) {
                    if (field.type === 'checkbox' || field.type === 'radio') {
                        const group = groupedChoices.get(field.name) || [];
                        group.push(field);
                        groupedChoices.set(field.name, group);
                        continue;
                    }

                    if (!fieldHasValue(field)) {
                        return false;
                    }
                }

                for (const choices of groupedChoices.values()) {
                    if (choices.length > 1 && !choices.some((field) => field.checked)) {
                        return false;
                    }
                }

                for (const collection of collections) {
                    if (!collectionIsComplete(collection.dataset.requiredCollection)) {
                        return false;
                    }
                }

                return fields.length > 0 || collections.length > 0;
            };

            const syncRequiredMarkers = () => {
                form.querySelectorAll(requiredMarkerSelector).forEach((marker) => marker.remove());
                restoreRequiredFieldHints();

                if (!shouldLockStepNavigation) {
                    return;
                }

                panels.forEach((panel) => {
                    const groupedChoices = new Map();

                    requiredCollections(panel).forEach((collection) => {
                        appendRequiredMarker(collection.querySelector('[data-required-collection-label-target]'));
                    });

                    stepFields(panel).forEach((field) => {
                        if (field.type === 'checkbox' || field.type === 'radio') {
                            const group = groupedChoices.get(field.name) || [];
                            group.push(field);
                            groupedChoices.set(field.name, group);
                            return;
                        }

                        const target = fieldRequiredTarget(field, panel);

                        if (target) {
                            appendRequiredMarker(target);
                            return;
                        }

                        appendRequiredPlaceholder(field);
                    });

                    groupedChoices.forEach((choices) => {
                        if (choices.length <= 1) {
                            return;
                        }

                        const groupTarget = fieldRequiredTarget(choices[0], panel);

                        if (groupTarget) {
                            appendRequiredMarker(groupTarget);
                            return;
                        }

                        choices.forEach((field) => appendRequiredMarker(field.closest('label')));
                    });
                });
            };

            const markIncompleteFields = (stepNumber, focusFirst = false) => {
                clearInvalidStyles();

                const panel = form.querySelector(`[data-step-panel="${stepNumber}"]`);
                let firstInvalidField = null;

                if (!panel) {
                    return;
                }

                const groupedChoices = new Map();

                stepFields(panel).forEach((field) => {
                    if (field.type === 'checkbox' || field.type === 'radio') {
                        const group = groupedChoices.get(field.name) || [];
                        group.push(field);
                        groupedChoices.set(field.name, group);
                        return;
                    }

                    if (!fieldHasValue(field)) {
                        markInvalidTarget(field, invalidFieldClasses);
                        firstInvalidField = firstInvalidField || field;
                    }
                });

                groupedChoices.forEach((choices) => {
                    if (choices.length > 1 && !choices.some((field) => field.checked)) {
                        choices.forEach((field) => markInvalidTarget(field.closest('label') || field, invalidChoiceClasses));
                        firstInvalidField = firstInvalidField || choices[0];
                    }
                });

                requiredCollections(panel).forEach((collection) => {
                    if (collectionIsComplete(collection.dataset.requiredCollection)) {
                        return;
                    }

                    markInvalidTarget(collection, invalidFieldClasses);
                    firstInvalidField = firstInvalidField || collection.querySelector('button');
                });

                if (focusFirst && firstInvalidField) {
                    firstInvalidField.focus({ preventScroll: true });
                }
            };

            const firstIncompleteStepBefore = (targetStep) => {
                const limit = clampStep(targetStep);

                for (let index = 1; index < limit; index += 1) {
                    if (!stepIsComplete(index)) {
                        return index;
                    }
                }

                return null;
            };

            const firstIncompleteStepInForm = () => {
                for (let index = 1; index <= totalSteps; index += 1) {
                    if (!stepIsComplete(index)) {
                        return index;
                    }
                }

                return null;
            };

            const highestReachableStep = () => shouldLockStepNavigation
                ? firstIncompleteStepInForm() || totalSteps
                : totalSteps;

            const blockAtStep = (blockedStep, message = 'Completa los campos de este paso antes de continuar.') => {
                step = clampStep(blockedStep);
                render();
                setValidationMessage(message);
                markIncompleteFields(step, true);
                window.scrollTo({ top: 0, behavior: 'smooth' });
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
                    const stepButton = form.querySelector(`[data-step-button="${index}"]`);

                    number?.classList.remove('bg-blue-600', 'text-white', 'ring-2', 'ring-blue-200', 'bg-green-500', 'bg-gray-200', 'text-gray-600', 'dark:bg-gray-700', 'dark:text-gray-300');
                    label?.classList.remove('font-semibold', 'text-blue-600', 'text-green-600', 'dark:text-green-400', 'text-gray-500');
                    divider?.classList.remove('bg-green-500', 'bg-gray-200', 'dark:bg-gray-700');
                    stepButton?.classList.remove('opacity-60', 'cursor-not-allowed');

                    const isComplete = stepIsComplete(index);
                    const isReachable = index <= highestReachableStep();
                    const showComplete = isComplete && isReachable;

                    if (index === step) {
                        if (number) {
                            number.textContent = showComplete ? '✓' : index;
                        }
                        number?.classList.add('bg-blue-600', 'text-white', 'ring-2', 'ring-blue-200');
                        label?.classList.add('font-semibold', 'text-blue-600');
                    } else if (showComplete) {
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

                    stepButton?.setAttribute('aria-disabled', isReachable ? 'false' : 'true');

                    if (!isReachable) {
                        stepButton?.classList.add('opacity-60', 'cursor-not-allowed');
                    }

                    if (divider) {
                        divider.classList.add(showComplete ? 'bg-green-500' : 'bg-gray-200');

                        if (!showComplete) {
                            divider.classList.add('dark:bg-gray-700');
                        }
                    }
                }

                const currentStepComplete = stepIsComplete(step);
                const canLeaveCurrentStep = !shouldLockStepNavigation || currentStepComplete;

                previousButton?.classList.toggle('hidden', step === 1);
                nextButton?.classList.toggle('hidden', step === totalSteps);
                nextButton?.setAttribute('aria-disabled', canLeaveCurrentStep ? 'false' : 'true');
                nextButton?.classList.toggle('opacity-60', !canLeaveCurrentStep);
                nextButton?.classList.toggle('cursor-not-allowed', !canLeaveCurrentStep);
                submitButton?.classList.toggle('hidden', step !== totalSteps);
                submitButton?.setAttribute('aria-disabled', canLeaveCurrentStep ? 'false' : 'true');
                submitButton?.classList.toggle('opacity-60', step === totalSteps && !canLeaveCurrentStep);
                submitButton?.classList.toggle('cursor-not-allowed', step === totalSteps && !canLeaveCurrentStep);
            };

            const goTo = (targetStep) => {
                const nextStep = clampStep(targetStep);
                const blockedStep = shouldLockStepNavigation && nextStep > step
                    ? firstIncompleteStepBefore(nextStep)
                    : null;

                if (blockedStep) {
                    blockAtStep(blockedStep);
                    return;
                }

                clearValidationFeedback();
                step = nextStep;
                syncCalculatedFields();
                updateContraparteState();
                render();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            };

            const refreshValidationFeedback = () => {
                if (!stepValidationMessage || stepValidationMessage.classList.contains('hidden')) {
                    return;
                }

                if (stepIsComplete(step)) {
                    clearValidationFeedback();
                    return;
                }

                markIncompleteFields(step);
            };

            const debouncedSave = () => {
                window.clearTimeout(autosaveTimer);
                autosaveTimer = window.setTimeout(save, 500);
            };

            restore();
            hydrateCollections(restoredDraftData);
            syncCalculatedFields();
            updateContraparteState();
            syncRequiredMarkers();
            step = shouldLockStepNavigation ? firstIncompleteStepBefore(step) || step : step;
            render();

            form.querySelectorAll('[data-step-button]').forEach((button) => {
                button.addEventListener('click', () => goTo(button.dataset.stepButton));
            });

            previousButton?.addEventListener('click', () => goTo(step - 1));
            nextButton?.addEventListener('click', () => goTo(step + 1));
            document.querySelector('[data-open-career-modal]')?.addEventListener('click', () => openCareerModal());
            document.querySelectorAll('[data-close-career-modal]').forEach((button) => {
                button.addEventListener('click', closeCareerModal);
            });
            document.querySelector('[data-save-career]')?.addEventListener('click', saveCareerModal);
            careerIdField?.addEventListener('change', () => {
                careerNameField.value = careerNameById(careerIdField.value);
            });
            careersList?.addEventListener('click', (event) => {
                const editButton = event.target.closest('[data-edit-career]');
                const deleteButton = event.target.closest('[data-delete-career]');

                if (editButton) {
                    openCareerModal(Number(editButton.dataset.editCareer));
                    return;
                }

                if (!deleteButton) {
                    return;
                }

                certificateCareers.splice(Number(deleteButton.dataset.deleteCareer), 1);
                renderCollections();
                syncRequiredMarkers();
                render();
                refreshValidationFeedback();
                save();
            });

            document.querySelector('[data-open-learning-space-modal]')?.addEventListener('click', () => openLearningSpaceModal());
            document.querySelectorAll('[data-close-learning-space-modal]').forEach((button) => {
                button.addEventListener('click', closeLearningSpaceModal);
            });
            document.querySelector('[data-save-learning-space]')?.addEventListener('click', saveLearningSpaceModal);
            learningSpacesList?.addEventListener('click', (event) => {
                const editButton = event.target.closest('[data-edit-learning-space]');
                const deleteButton = event.target.closest('[data-delete-learning-space]');

                if (editButton) {
                    openLearningSpaceModal(Number(editButton.dataset.editLearningSpace));
                    return;
                }

                if (!deleteButton) {
                    return;
                }

                learningSpaces.splice(Number(deleteButton.dataset.deleteLearningSpace), 1);
                renderCollections();
                syncRequiredMarkers();
                render();
                refreshValidationFeedback();
                save();
            });
            addTeacherButton?.addEventListener('click', () => {
                const row = addTeacherRow();
                syncRequiredMarkers();
                render();
                refreshValidationFeedback();
                save();
                row?.querySelector('[data-teacher-field]')?.focus();
            });
            teacherTeamList?.addEventListener('click', (event) => {
                const removeButton = event.target.closest('[data-remove-teacher]');

                if (!removeButton) {
                    return;
                }

                const rows = teacherTeamList.querySelectorAll('[data-teacher-row]');

                if (rows.length <= 1) {
                    return;
                }

                removeButton.closest('[data-teacher-row]')?.remove();
                renumberTeacherRows();
                syncRequiredMarkers();
                render();
                refreshValidationFeedback();
                save();
            });
            form.addEventListener('input', () => {
                syncCalculatedFields();
                updateContraparteState();
                syncRequiredMarkers();
                render();
                refreshValidationFeedback();
                debouncedSave();
            });
            form.addEventListener('change', () => {
                syncCalculatedFields();
                updateContraparteState();
                syncRequiredMarkers();
                render();
                refreshValidationFeedback();
                save();
            });
            form.addEventListener('submit', (event) => {
                if (submittingAfterAutosave) {
                    return;
                }

                syncCalculatedFields();
                updateContraparteState();

                const blockedStep = shouldLockStepNavigation ? firstIncompleteStepInForm() : null;

                if (blockedStep) {
                    event.preventDefault();
                    blockAtStep(blockedStep, 'Completa los campos pendientes antes de guardar la acción.');
                    return;
                }

                save();
                event.preventDefault();
                submitButton?.setAttribute('disabled', 'disabled');

                serverAutosave({ force: true })
                    .finally(() => openSendReviewOrSubmit());
            });

            window.addEventListener('beforeunload', () => save({ persist: shouldPersistDraft }));
            window.addEventListener('pagehide', () => {
                save({ persist: shouldPersistDraft });
                window.clearTimeout(autosaveTimer);

                if (shouldPersistDraft && (serverAutosaveDirty || serverAutosaveInFlight)) {
                    serverAutosave({ force: true, keepalive: true });
                }
            });
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'hidden') {
                    save({ persist: shouldPersistDraft });

                    if (shouldPersistDraft) {
                        serverAutosave({ force: true, keepalive: true });
                    }
                }
            });
        })();
    </script>
@endsection
