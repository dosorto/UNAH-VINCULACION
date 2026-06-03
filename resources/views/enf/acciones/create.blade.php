@extends('layouts.panel.base')

@php
    $input = 'w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white';
    $label = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1';
    $card = 'rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900';
    $sectionTitle = 'mb-4 text-base font-semibold text-gray-900 dark:text-white';
    $catalog = fn (string $tipo) => $catalogos->get($tipo, collect());
    $programasAprobadosData = $programasAprobados->map(function ($programa) {
        $tipoAccionEnfId = $programa->accionCatalogos
            ->first(fn ($catalogo) => $catalogo->tipo === 'tipo_accion_enf')
            ?->enf_catalogo_id;

        return [
            'id' => $programa->id,
            'label' => trim(($programa->numero_registro ? $programa->numero_registro.' · ' : '').$programa->nombre_accion),
            'fields' => [
                'nombre_accion' => $programa->nombre_accion,
                'catalogos[tipo_accion_enf][]' => $tipoAccionEnfId,
                'resolucion_vra' => $programa->resolucion_vra,
                'resolucion_original' => $programa->resolucion_original,
                'resolucion_actualizacion' => $programa->resolucion_actualizacion,
                'numero_edicion' => $programa->numero_edicion,
                'fecha_inicio' => optional($programa->fecha_inicio)->format('Y-m-d'),
                'fecha_finalizacion' => optional($programa->fecha_finalizacion)->format('Y-m-d'),
                'modalidad_id' => $programa->modalidad_id,
                'centro_facultad_id' => $programa->centro_facultad_id,
                'departamento_academico_id' => $programa->departamento_academico_id,
                'carrera_id' => $programa->carrera_id,
                'horas_teoricas' => $programa->horas_teoricas,
                'horas_practicas' => $programa->horas_practicas,
            ],
        ];
    })->values();
    $stepLabels = [
        1 => 'Información',
        2 => 'Lugar',
        3 => 'Beneficiarios',
        4 => 'Equipo',
        5 => 'Contraparte',
        6 => 'Acción',
        7 => 'Resultados',
        8 => 'Presupuesto',
        9 => 'Documentos',
    ];
@endphp

@section('main')
    <div class="mx-auto max-w-7xl space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">FORM-DVUS-018 · Educación No Formal</h1>
                <p class="text-sm text-slate-600 dark:text-slate-300">Registro de acciones tipo programa/proyecto: diplomados, cursos, talleres, seminarios, congresos y educación continua.</p>
            </div>
            <a href="{{ route('selectorTipoAccion') }}" class="text-sm font-semibold text-blue-700 hover:text-blue-900">Volver al selector</a>
        </div>

        @if ($errors->any())
            <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                Hay campos pendientes o con formato inválido. Revisa la ficha antes de guardar.
            </div>
        @endif

        <form method="POST" action="{{ route('enf.acciones.store') }}" class="space-y-6" data-enf-wizard-form data-total-steps="{{ count($stepLabels) }}" data-storage-key="enf-accion-form-draft" data-clear-draft-on-load="{{ $clearDraftOnLoad ? '1' : '0' }}">
            @csrf
            <input type="hidden" name="tipo_accion_id" value="{{ old('tipo_accion_id', $tiposAccion->first()?->id) }}">
            <input type="hidden" name="codigo_formulario" value="FORM-DVUS-018">
            <input type="hidden" name="estado_flujo" value="BORRADOR">

            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-gray-900">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Registro por pasos</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400" data-autosave-status>Los cambios se autoguardan mientras escribe.</p>
                    </div>
                </div>
                <div class="flex items-center overflow-x-auto gap-0.5">
                    @foreach ($stepLabels as $step => $label)
                        <button type="button" data-step-button="{{ $step }}"
                            class="flex min-w-[70px] flex-1 flex-col items-center rounded-md p-1 transition hover:bg-slate-50 dark:hover:bg-white/5">
                            <span data-step-number="{{ $step }}" class="mb-1 flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold transition-colors">
                                {{ $step }}
                            </span>
                            <span data-step-label="{{ $step }}" class="hidden text-center text-[10px] leading-tight sm:block">{{ $label }}</span>
                        </button>
                        @if ($step < count($stepLabels))
                            <div data-step-divider="{{ $step }}" class="h-0.5 w-3 shrink-0"></div>
                        @endif
                    @endforeach
                </div>
            </div>

            <div class="{{ $card }}" data-step-panel="1">
                <h2 class="{{ $sectionTitle }}">1. Información general de la acción</h2>
                <div class="mb-4 rounded-md border border-blue-100 bg-blue-50 p-4 dark:border-blue-900/50 dark:bg-blue-950/30">
                    <label class="{{ $label }}">Programa aprobado de educación continua</label>
                    <select data-approved-program-select class="{{ $input }}">
                        <option value="">Crear acción desde cero</option>
                        @foreach ($programasAprobados as $programaAprobado)
                            <option value="{{ $programaAprobado->id }}">
                                {{ $programaAprobado->numero_registro ? $programaAprobado->numero_registro.' · ' : '' }}{{ $programaAprobado->nombre_accion }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-xs text-blue-800 dark:text-blue-200">
                        Al seleccionar un programa aprobado se llenan los datos del primer paso. Puedes ajustar edición, fechas y demás campos antes de guardar.
                    </p>
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="{{ $label }}">Fecha de solicitud</label>
                        <input type="date" name="fecha_solicitud" value="{{ old('fecha_solicitud', now()->format('Y-m-d')) }}" class="{{ $input }}">
                    </div>
                    <div class="md:col-span-2">
                        <label class="{{ $label }}">Nombre de la acción <span class="text-red-500">*</span></label>
                        <input name="nombre_accion" value="{{ old('nombre_accion') }}" required class="{{ $input }}">
                        @error('nombre_accion')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $label }}">Tipo de acción ENF</label>
                        <select name="catalogos[tipo_accion_enf][]" class="{{ $input }}">
                            <option value="">Seleccione...</option>
                            @foreach ($catalog('tipo_accion_enf') as $item)
                                <option value="{{ $item->id }}" @selected(old('catalogos.tipo_accion_enf.0', $selectedTipoAccionEnfId) == $item->id)>{{ $item->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $label }}">Resolución VRA</label>
                        <input name="resolucion_vra" value="{{ old('resolucion_vra') }}" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">No. resolución programa original</label>
                        <input name="resolucion_original" value="{{ old('resolucion_original') }}" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">No. resolución última actualización</label>
                        <input name="resolucion_actualizacion" value="{{ old('resolucion_actualizacion') }}" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Número de edición</label>
                        <input type="number" min="1" name="numero_edicion" value="{{ old('numero_edicion', 1) }}" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Fecha de inicio</label>
                        <input type="date" name="fecha_inicio" value="{{ old('fecha_inicio') }}" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Fecha de finalización</label>
                        <input type="date" name="fecha_finalizacion" value="{{ old('fecha_finalizacion') }}" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Modalidad</label>
                        <select name="modalidad_id" class="{{ $input }}">
                            <option value="">Seleccione...</option>
                            @foreach ($modalidades as $modalidad)
                                <option value="{{ $modalidad->id }}" @selected(old('modalidad_id') == $modalidad->id)>{{ $modalidad->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $label }}">Centro / Facultad</label>
                        <select name="centro_facultad_id" class="{{ $input }}">
                            <option value="">Seleccione...</option>
                            @foreach ($centrosFacultad as $centro)
                                <option value="{{ $centro->id }}" @selected(old('centro_facultad_id') == $centro->id)>{{ $centro->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $label }}">Departamento académico</label>
                        <select name="departamento_academico_id" class="{{ $input }}">
                            <option value="">Seleccione...</option>
                            @foreach ($departamentosAcademicos as $departamento)
                                <option value="{{ $departamento->id }}" @selected(old('departamento_academico_id') == $departamento->id)>{{ $departamento->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $label }}">Carrera</label>
                        <select name="carrera_id" class="{{ $input }}">
                            <option value="">Seleccione...</option>
                            @foreach ($carreras as $carrera)
                                <option value="{{ $carrera->id }}" @selected(old('carrera_id') == $carrera->id)>{{ $carrera->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $label }}">Horas teóricas</label>
                        <input type="number" min="0" name="horas_teoricas" value="{{ old('horas_teoricas', 0) }}" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Horas prácticas</label>
                        <input type="number" min="0" name="horas_practicas" value="{{ old('horas_practicas', 0) }}" class="{{ $input }}">
                    </div>
                </div>
            </div>

            <div class="{{ $card }} hidden" data-step-panel="2">
                <h2 class="{{ $sectionTitle }}">2. Lugar, modalidad virtual y antecedentes</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="{{ $label }}">Modalidad de ejecución</label>
                        <select name="modalidad_ejecucion" class="{{ $input }}">
                            <option value="">Seleccione...</option>
                            <option>Presencial</option>
                            <option>Semi presencial</option>
                            <option>100% virtual</option>
                            <option>Virtual sincrónico</option>
                        </select>
                    </div>
                    <div>
                        <label class="{{ $label }}">Campus</label>
                        <select name="campus_id" class="{{ $input }}">
                            <option value="">Seleccione...</option>
                            @foreach ($campus as $item)
                                <option value="{{ $item->id }}">{{ $item->nombre_campus }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $label }}">Aula / Auditorio</label>
                        <input name="aula_auditorio" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Edificio</label>
                        <input name="edificio" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Departamento</label>
                        <select name="departamento_id" class="{{ $input }}">
                            <option value="">Seleccione...</option>
                            @foreach ($departamentos as $departamento)
                                <option value="{{ $departamento->id }}">{{ $departamento->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $label }}">Municipio</label>
                        <select name="municipio_id" class="{{ $input }}">
                            <option value="">Seleccione...</option>
                            @foreach ($municipios as $municipio)
                                <option value="{{ $municipio->id }}">{{ $municipio->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-3">
                        <label class="{{ $label }}">Plataformas</label>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-4">
                            @foreach ($catalog('plataforma') as $item)
                                <label class="flex items-center gap-2 rounded-md border border-slate-200 px-3 py-2 text-sm dark:border-slate-700">
                                    <input type="checkbox" name="plataformas[]" value="{{ $item->id }}" class="rounded border-gray-300 text-blue-600">
                                    <span>{{ $item->nombre }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="md:col-span-3">
                        <label class="{{ $label }}">Antecedentes de la acción</label>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($catalog('antecedente') as $item)
                                <label class="flex items-center gap-2 rounded-md border border-slate-200 px-3 py-2 text-sm dark:border-slate-700">
                                    <input type="checkbox" name="catalogos[antecedente][]" value="{{ $item->id }}" class="rounded border-gray-300 text-blue-600">
                                    <span>{{ $item->nombre }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="{{ $card }} hidden" data-step-panel="3">
                <h2 class="{{ $sectionTitle }}">3. Perfil de beneficiarios</h2>
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                    <div>
                        <label class="{{ $label }}">Perfil de participantes</label>
                        <div class="space-y-2">
                            @foreach ($catalog('perfil_participante') as $item)
                                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="catalogos[perfil_participante][]" value="{{ $item->id }}" class="rounded border-gray-300 text-blue-600"> {{ $item->nombre }}</label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label class="{{ $label }}">Rango de edad</label>
                        <div class="space-y-2">
                            @foreach ($catalog('rango_edad') as $item)
                                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="catalogos[rango_edad][]" value="{{ $item->id }}" class="rounded border-gray-300 text-blue-600"> {{ $item->nombre }}</label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label class="{{ $label }}">Condición social</label>
                        <div class="space-y-2">
                            @foreach ($catalog('condicion_social') as $item)
                                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="catalogos[condicion_social][]" value="{{ $item->id }}" class="rounded border-gray-300 text-blue-600"> {{ $item->nombre }}</label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div><label class="{{ $label }}">Hombres</label><input type="number" min="0" name="beneficiarios[hombres]" value="0" class="{{ $input }}"></div>
                    <div><label class="{{ $label }}">Mujeres</label><input type="number" min="0" name="beneficiarios[mujeres]" value="0" class="{{ $input }}"></div>
                    <div><label class="{{ $label }}">Otros</label><input type="number" min="0" name="beneficiarios[otros]" value="0" class="{{ $input }}"></div>
                    <div><label class="{{ $label }}">Total cupos programados</label><input type="number" min="0" name="beneficiarios[total]" value="0" class="{{ $input }}"></div>
                    <div class="md:col-span-4"><label class="{{ $label }}">Descripción de participantes</label><textarea name="descripcion_participantes" rows="3" class="{{ $input }}"></textarea></div>
                </div>
            </div>

            <div class="{{ $card }} hidden" data-step-panel="4">
                <h2 class="{{ $sectionTitle }}">4. Equipo ejecutor</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="rounded-md border border-slate-200 p-4 dark:border-slate-700">
                        <h3 class="mb-3 text-sm font-semibold text-slate-800 dark:text-slate-100">Coordinador/a de la acción</h3>
                        <label class="{{ $label }}">Empleado registrado</label>
                        <select name="coordinador[empleado_id]" class="{{ $input }}">
                            <option value="">Seleccione...</option>
                            @foreach ($empleados as $empleado)
                                <option value="{{ $empleado->id }}">{{ $empleado->nombre_completo }} · {{ $empleado->numero_empleado }}</option>
                            @endforeach
                        </select>
                        <label class="{{ $label }} mt-3">Nombre manual</label>
                        <input name="coordinador[nombre_completo]" class="{{ $input }}">
                    </div>
                    <div class="rounded-md border border-slate-200 p-4 dark:border-slate-700">
                        <h3 class="mb-3 text-sm font-semibold text-slate-800 dark:text-slate-100">Responsable de sistematización</h3>
                        <label class="{{ $label }}">Empleado registrado</label>
                        <select name="sistematizador[empleado_id]" class="{{ $input }}">
                            <option value="">Seleccione...</option>
                            @foreach ($empleados as $empleado)
                                <option value="{{ $empleado->id }}">{{ $empleado->nombre_completo }} · {{ $empleado->numero_empleado }}</option>
                            @endforeach
                        </select>
                        <label class="{{ $label }} mt-3">Nombre manual</label>
                        <input name="sistematizador[nombre_completo]" class="{{ $input }}">
                    </div>
                </div>
            </div>

            <div class="{{ $card }} hidden" data-step-panel="5">
                <h2 class="{{ $sectionTitle }}">5. Entidad contraparte</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div><label class="{{ $label }}">Nombre de la contraparte</label><input name="contraparte[nombre]" class="{{ $input }}"></div>
                    <div><label class="{{ $label }}">Contacto directo</label><input name="contraparte[representante]" class="{{ $input }}"></div>
                    <div><label class="{{ $label }}">Correo</label><input type="email" name="contraparte[correo]" class="{{ $input }}"></div>
                    <div><label class="{{ $label }}">Teléfono</label><input name="contraparte[telefono]" class="{{ $input }}"></div>
                    <div>
                        <label class="{{ $label }}">Perfil contraparte</label>
                        <select name="contraparte[tipo_contraparte_id]" class="{{ $input }}">
                            <option value="">Seleccione...</option>
                            @foreach ($catalog('tipo_contraparte') as $item)
                                <option value="{{ $item->id }}">{{ $item->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $label }}">Instrumento de alianza</label>
                        <select name="contraparte[instrumento_alianza_id]" class="{{ $input }}">
                            <option value="">Seleccione...</option>
                            @foreach ($catalog('instrumento_alianza') as $item)
                                <option value="{{ $item->id }}">{{ $item->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-3"><label class="{{ $label }}">Compromisos asumidos</label><textarea name="contraparte[compromisos]" rows="3" class="{{ $input }}"></textarea></div>
                </div>
            </div>

            <div class="{{ $card }} hidden" data-step-panel="6">
                <h2 class="{{ $sectionTitle }}">6. Información de la acción</h2>
                <div class="grid grid-cols-1 gap-4">
                    <div><label class="{{ $label }}">Resumen de la acción</label><textarea name="resumen" rows="4" class="{{ $input }}"></textarea></div>
                    <div><label class="{{ $label }}">Definición del problema</label><textarea name="definicion_problema" rows="3" class="{{ $input }}"></textarea></div>
                    <div><label class="{{ $label }}">Objetivo general</label><textarea name="objetivo_general" rows="3" class="{{ $input }}"></textarea></div>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        @for ($i = 0; $i < 4; $i++)
                            <div><label class="{{ $label }}">Objetivo específico {{ $i + 1 }}</label><textarea name="objetivos_especificos[]" rows="2" class="{{ $input }}"></textarea></div>
                        @endfor
                    </div>
                    <div><label class="{{ $label }}">Alineamiento con la reforma UNAH</label><textarea name="alineamiento_reforma" rows="3" class="{{ $input }}"></textarea></div>
                    <div><label class="{{ $label }}">Metodología</label><textarea name="metodologia" rows="3" class="{{ $input }}"></textarea></div>
                    <div><label class="{{ $label }}">Resumen de logística</label><textarea name="logistica" rows="3" class="{{ $input }}"></textarea></div>
                    <div><label class="{{ $label }}">Bibliografía</label><textarea name="bibliografia" rows="2" class="{{ $input }}"></textarea></div>
                </div>
            </div>

            <div class="{{ $card }} hidden" data-step-panel="7">
                <h2 class="{{ $sectionTitle }}">7. Resultados, ODS y ejes UNAH</h2>
                <div class="space-y-4">
                    @foreach (['Corto plazo', 'Mediano plazo', 'Largo plazo / impacto'] as $index => $tipo)
                        <div class="rounded-md border border-slate-200 p-4 dark:border-slate-700">
                            <input type="hidden" name="resultados[{{ $index }}][tipo]" value="{{ $tipo }}">
                            <label class="{{ $label }}">{{ $tipo }} · descripción del resultado</label>
                            <textarea name="resultados[{{ $index }}][descripcion]" rows="2" class="{{ $input }}"></textarea>
                            <label class="{{ $label }} mt-3">Medio de verificación / indicador</label>
                            <textarea name="resultados[{{ $index }}][indicador]" rows="2" class="{{ $input }}"></textarea>
                        </div>
                    @endforeach
                    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                        <div>
                            <label class="{{ $label }}">ODS</label>
                            <div class="max-h-56 space-y-2 overflow-y-auto rounded-md border border-slate-200 p-3 dark:border-slate-700">
                                @foreach ($odsList as $ods)
                                    <label class="flex items-start gap-2 text-sm"><input type="checkbox" name="ods_ids[]" value="{{ $ods->id }}" class="mt-1 rounded border-gray-300 text-blue-600"> <span>{{ $ods->nombre }}</span></label>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <label class="{{ $label }}">Metas a las que contribuye</label>
                            <div class="max-h-56 space-y-2 overflow-y-auto rounded-md border border-slate-200 p-3 dark:border-slate-700">
                                @foreach ($metasContribuye as $meta)
                                    <label class="flex items-start gap-2 text-sm"><input type="checkbox" name="meta_contribuye_ids[]" value="{{ $meta->id }}" class="mt-1 rounded border-gray-300 text-blue-600"> <span>{{ $meta->ods?->nombre }} · {{ $meta->numero_meta }} {{ $meta->descripcion }}</span></label>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <label class="{{ $label }}">Ejes prioritarios UNAH</label>
                            <div class="max-h-56 space-y-2 overflow-y-auto rounded-md border border-slate-200 p-3 dark:border-slate-700">
                                @foreach ($ejesUnah as $eje)
                                    <label class="flex items-start gap-2 text-sm"><input type="checkbox" name="eje_unah_ids[]" value="{{ $eje->id }}" class="mt-1 rounded border-gray-300 text-blue-600"> <span>{{ $eje->nombre }}</span></label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="{{ $card }} hidden" data-step-panel="8">
                <h2 class="{{ $sectionTitle }}">8. Presupuesto</h2>
                <label class="mb-4 flex items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-200">
                    <input type="checkbox" name="genera_ingresos" value="1" class="rounded border-gray-300 text-blue-600">
                    Obtendrá ingresos por el desarrollo de la actividad
                </label>
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                    @foreach (['presupuesto_ingresos' => ['Ingresos', ['Cuotas de inscripción', 'Mensualidades / módulos', 'Gestión de becas', 'Otros']], 'presupuesto_egresos' => ['Egresos', ['Pago de personal docente', 'Materiales y suministros', 'Movilización', 'Manutención y hospedaje', 'Costos administrativos', 'Otros gastos']]] as $name => [$title, $rubros])
                        <div class="rounded-md border border-slate-200 p-4 dark:border-slate-700">
                            <h3 class="mb-3 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $title }}</h3>
                            <div class="space-y-3">
                                @foreach ($rubros as $i => $rubro)
                                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                                        <input type="text" name="{{ $name }}[{{ $i }}][rubro]" value="{{ $rubro }}" class="{{ $input }}">
                                        <input type="number" min="0" step="0.01" name="{{ $name }}[{{ $i }}][cantidad]" value="0" class="{{ $input }}" placeholder="Cantidad">
                                        <input type="number" min="0" step="0.01" name="{{ $name }}[{{ $i }}][costo_unitario]" value="0" class="{{ $input }}" placeholder="Costo unitario">
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

            <div class="{{ $card }} hidden" data-step-panel="9">
                <h2 class="{{ $sectionTitle }}">9. Cronograma y documentos</h2>
                <div class="space-y-3">
                    @for ($i = 0; $i < 5; $i++)
                        <div class="grid grid-cols-1 gap-2 md:grid-cols-4">
                            <input name="cronograma[{{ $i }}][actividad]" class="{{ $input }}" placeholder="Actividad">
                            <input type="date" name="cronograma[{{ $i }}][fecha_inicio]" class="{{ $input }}">
                            <input type="date" name="cronograma[{{ $i }}][fecha_finalizacion]" class="{{ $input }}">
                            <input name="cronograma[{{ $i }}][responsable]" class="{{ $input }}" placeholder="Responsable / producto">
                        </div>
                    @endfor
                </div>
                <div class="mt-5 grid grid-cols-1 gap-2 md:grid-cols-3">
                    @foreach (['Oficio de remisión del Decano/Director Centro Regional', 'Documento perfil del programa de formación', 'Otros documentos de respaldo'] as $doc)
                        <label class="flex items-center gap-2 rounded-md border border-slate-200 px-3 py-2 text-sm dark:border-slate-700">
                            <input type="checkbox" name="documentos_requeridos[]" value="{{ $doc }}" class="rounded border-gray-300 text-blue-600">
                            <span>{{ $doc }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <a href="{{ route('selectorTipoAccion') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Cancelar</a>
                <div class="flex justify-end gap-3">
                    <button type="button" data-previous-step
                        class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                        Anterior
                    </button>
                    <button type="button" data-next-step
                        class="rounded-md bg-blue-700 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">
                        Siguiente
                    </button>
                    <button data-submit-step class="rounded-md bg-blue-700 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">
                        Guardar acción ENF
                    </button>
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
            const storageKey = form.dataset.storageKey || 'enf-accion-form-draft';
            const clearDraftOnLoad = form.dataset.clearDraftOnLoad === '1';
            const approvedPrograms = @js($programasAprobadosData);
            const approvedProgramSelect = form.querySelector('[data-approved-program-select]');
            const panels = Array.from(form.querySelectorAll('[data-step-panel]'));
            const previousButton = form.querySelector('[data-previous-step]');
            const nextButton = form.querySelector('[data-next-step]');
            const submitButton = form.querySelector('[data-submit-step]');
            const status = form.querySelector('[data-autosave-status]');
            if (clearDraftOnLoad) {
                window.localStorage.removeItem(storageKey);
                window.localStorage.removeItem(`${storageKey}:step`);
            }

            let step = Number(window.localStorage.getItem(`${storageKey}:step`) || 1);
            let autosaveTimer = null;

            const clampStep = (value) => Math.min(Math.max(Number(value) || 1, 1), totalSteps);

            const save = () => {
                const data = {};

                form.querySelectorAll('input[name], select[name], textarea[name]').forEach((field) => {
                    if (field.type === 'hidden' || field.name === '_token') {
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

                    if (field.multiple) {
                        data[field.name] = Array.from(field.selectedOptions).map((option) => option.value);
                        return;
                    }

                    data[field.name] = field.value;
                });

                window.localStorage.setItem(storageKey, JSON.stringify(data));
                status.textContent = `Autoguardado ${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
            };

            const debouncedSave = () => {
                window.clearTimeout(autosaveTimer);
                autosaveTimer = window.setTimeout(save, 600);
            };

            const fieldSelector = (name) => `[name="${String(name).replace(/"/g, '\\"')}"]`;

            const fieldHasValue = (field) => {
                if (field.type === 'checkbox' || field.type === 'radio') {
                    return field.checked;
                }

                if (field.multiple) {
                    return Array.from(field.selectedOptions).some((option) => option.value !== '');
                }

                return String(field.value ?? '').trim() !== '';
            };

            const eitherFieldHasValue = (names) => names.some((name) => {
                const field = form.querySelector(fieldSelector(name));
                return field && fieldHasValue(field);
            });

            const stepIsComplete = (stepNumber) => {
                const panel = form.querySelector(`[data-step-panel="${stepNumber}"]`);

                if (!panel) {
                    return false;
                }

                const alternativeGroups = [
                    ['coordinador[empleado_id]', 'coordinador[nombre_completo]'],
                    ['sistematizador[empleado_id]', 'sistematizador[nombre_completo]'],
                ];
                const alternativeFieldNames = new Set(alternativeGroups.flat());
                const groupedChoices = new Map();
                const fields = Array.from(panel.querySelectorAll('input[name], select[name], textarea[name]'))
                    .filter((field) => {
                        if (field.disabled || field.type === 'hidden' || field.name === '_token') {
                            return false;
                        }

                        if (alternativeFieldNames.has(field.name)) {
                            return false;
                        }

                        return true;
                    });

                for (const group of alternativeGroups) {
                    if (group.some((name) => panel.querySelector(fieldSelector(name))) && !eitherFieldHasValue(group)) {
                        return false;
                    }
                }

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

                return fields.length > 0 || alternativeGroups.some((group) => group.some((name) => panel.querySelector(fieldSelector(name))));
            };

            const setFieldValue = (name, value) => {
                const field = form.querySelector(fieldSelector(name));

                if (!field || value === null || value === undefined) {
                    return;
                }

                field.value = value;
                field.dispatchEvent(new Event('input', { bubbles: true }));
                field.dispatchEvent(new Event('change', { bubbles: true }));
            };

            const applyApprovedProgram = (programId) => {
                const program = approvedPrograms.find((item) => String(item.id) === String(programId));

                if (!program) {
                    return;
                }

                Object.entries(program.fields || {}).forEach(([name, value]) => setFieldValue(name, value));
                save();
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
                    if (field.type === 'hidden' || field.name === '_token' || !(field.name in data)) {
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

                    if (field.multiple && Array.isArray(value)) {
                        Array.from(field.options).forEach((option) => {
                            option.selected = value.includes(option.value);
                        });
                        return;
                    }

                    field.value = value;
                });
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

                    if (divider) {
                        divider.classList.add(stepIsComplete(index) ? 'bg-green-500' : 'bg-gray-200');

                        if (!stepIsComplete(index)) {
                            divider.classList.add('dark:bg-gray-700');
                        }
                    }
                }

                previousButton?.classList.toggle('hidden', step === 1);
                nextButton?.classList.toggle('hidden', step === totalSteps);
                submitButton?.classList.toggle('hidden', step !== totalSteps);
            };

            const goTo = (targetStep) => {
                step = clampStep(targetStep);
                render();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            };

            restore();
            render();

            form.querySelectorAll('[data-step-button]').forEach((button) => {
                button.addEventListener('click', () => goTo(button.dataset.stepButton));
            });

            previousButton?.addEventListener('click', () => goTo(step - 1));
            nextButton?.addEventListener('click', () => goTo(step + 1));
            form.addEventListener('input', () => {
                render();
                debouncedSave();
            });
            form.addEventListener('change', () => {
                render();
                save();
            });
            approvedProgramSelect?.addEventListener('change', (event) => applyApprovedProgram(event.target.value));
        })();
    </script>
@endsection
