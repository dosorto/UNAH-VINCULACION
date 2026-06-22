@extends('layouts.panel.base')

@php
    $input = 'w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white';
    $label = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1';
    $card = 'rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900';
    $sectionTitle = 'mb-4 text-base font-semibold text-gray-900 dark:text-white';
    $catalog = fn (string $tipo) => $catalogos->get($tipo, collect());
    $centrosFacultadOptions = $centrosFacultad->pluck('nombre', 'id');
    $departamentosAcademicosOptions = $departamentosAcademicos
        ->mapWithKeys(fn ($departamento) => [
            (string) $departamento->id => [
                'centro_facultad_id' => (string) $departamento->centro_facultad_id,
                'label' => $departamento->nombre,
            ],
        ]);
    $carrerasOptions = $carreras
        ->mapWithKeys(fn ($carrera) => [
            (string) $carrera->id => [
                'centro_ids' => collect([$carrera->facultad_centro_id])
                    ->merge($carrera->facultadCentros->pluck('id'))
                    ->filter()
                    ->map(fn ($id) => (string) $id)
                    ->unique()
                    ->values()
                    ->all(),
                'departamento_ids' => collect([$carrera->departamento_academico_id])
                    ->merge($carrera->departamentosAcademicos->pluck('id'))
                    ->filter()
                    ->map(fn ($id) => (string) $id)
                    ->unique()
                    ->values()
                    ->all(),
                'label' => $carrera->nombre,
            ],
        ]);
    $ejesUnahOptions = $ejesUnah->pluck('nombre', 'id');
    $odsOptions = $odsList->pluck('nombre', 'id');
    $metasContribuyeOptions = $metasContribuye
        ->mapWithKeys(fn ($meta) => [
            (string) $meta->id => [
                'ods_id' => (string) $meta->ods_id,
                'label' => trim(($meta->ods?->nombre ? $meta->ods->nombre.' · ' : '').$meta->numero_meta.' '.$meta->descripcion),
            ],
        ]);
    $periodoAcademicoLabel = fn ($periodo) => collect([$periodo->nombre, $periodo->anio ?? null])
        ->filter()
        ->implode(' ');
    $empleadosModalData = $empleados->map(fn ($empleado) => [
        'id' => $empleado->id,
        'nombre_completo' => $empleado->nombre_completo,
        'numero_empleado' => $empleado->numero_empleado,
        'celular' => $empleado->celular,
        'correo' => $empleado->user?->email,
        'categoria' => $empleado->categoria?->nombre,
        'departamento' => $empleado->departamento_academico?->nombre,
        'jornada_laboral' => $empleado->jornada_laboral,
    ])->values();
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
        9 => 'Cronograma',
        10 => 'Documentos y firmas',
    ];
    $firmaForm018Roles = [
        [
            'rol' => 'Coordinador de la acción por la UNAH',
            'placeholder' => 'Nombre del coordinador de la acción',
        ],
        [
            'rol' => 'Jefe de la Unidad Académica que lidera la acción',
            'placeholder' => 'Nombre del jefe(a) de la unidad académica',
        ],
        [
            'rol' => 'Coordinador(a) del Comité Local',
            'placeholder' => 'Nombre del coordinador(a) del comité local',
        ],
        [
            'rol' => 'Decano(a) o Director(a) del Centro Regional',
            'placeholder' => 'Nombre del decano(a) o director(a)',
        ],
    ];
    $editingAccion = $accion ?? null;
    $formAction = $editingAccion ? route('enf.acciones.update', $editingAccion) : route('enf.acciones.store');
    $storageKey = $editingAccion ? "enf-accion-form-draft-{$editingAccion->id}" : 'enf-accion-form-draft';
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

        <script>
            window.__enfInitialDrafts = Object.assign(window.__enfInitialDrafts || {}, {
                @js($storageKey): @js($initialDraft ?? []),
            });
        </script>

        <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" class="space-y-6" data-enf-wizard-form data-total-steps="{{ count($stepLabels) }}" data-storage-key="{{ $storageKey }}" data-clear-draft-on-load="{{ $clearDraftOnLoad ? '1' : '0' }}">
            @csrf
            @if ($editingAccion)
                @method('PUT')
            @endif
            <input type="hidden" name="tipo_accion_id" value="{{ old('tipo_accion_id', $selectedTipoAccionEnfId ?: $tiposAccion->first()?->id) }}">
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
                    <div
                        x-data="{
                            openCentros: false,
                            openDepartamentos: false,
                            openCarreras: false,
                            searchCentros: '',
                            searchDepartamentos: '',
                            searchCarreras: '',
                            centrosOptions: @js($centrosFacultadOptions),
                            departamentosOptions: @js($departamentosAcademicosOptions),
                            carrerasOptions: @js($carrerasOptions),
                            selectedCentros: @js(array_values(array_filter(array_map('strval', (array) old('centro_facultad_ids', old('centro_facultad_id') ? [old('centro_facultad_id')] : []))))),
                            selectedDepartamentos: @js(array_values(array_filter(array_map('strval', (array) old('departamento_academico_ids', old('departamento_academico_id') ? [old('departamento_academico_id')] : []))))),
                            selectedCarreras: @js(array_values(array_filter(array_map('strval', (array) old('carrera_ids', old('carrera_id') ? [old('carrera_id')] : []))))),
                            init() {
                                const key = this.$root.closest('form')?.dataset.storageKey;
                                if (key) {
                                    try {
                                        const initial = window.__enfInitialDrafts?.[key] || {};
                                        const stored = JSON.parse(window.localStorage.getItem(key) || '{}');
                                        const data = { ...initial, ...stored };
                                        this.selectedCentros = this.normalized(data['centro_facultad_ids[]'] ?? (data.centro_facultad_id ? [data.centro_facultad_id] : this.selectedCentros));
                                        this.selectedDepartamentos = this.normalized(data['departamento_academico_ids[]'] ?? (data.departamento_academico_id ? [data.departamento_academico_id] : this.selectedDepartamentos));
                                        this.selectedCarreras = this.normalized(data['carrera_ids[]'] ?? (data.carrera_id ? [data.carrera_id] : this.selectedCarreras));
                                    } catch (error) {}
                                }
                                this.filterSelections();
                            },
                            normalized(values) {
                                return Array.isArray(values) ? values.map(String).filter(Boolean) : [];
                            },
                            optionEntries(options, search = '') {
                                const term = search.trim().toLowerCase();
                                return Object.entries(options || {}).filter(([id, value]) => {
                                    const label = typeof value === 'object' ? value.label : value;
                                    return !term || String(label).toLowerCase().includes(term);
                                });
                            },
                            departamentoEntries() {
                                if (!this.selectedCentros.length) {
                                    return [];
                                }
                                return this.optionEntries(this.departamentosOptions, this.searchDepartamentos)
                                    .filter(([id, value]) => this.selectedCentros.includes(String(value.centro_facultad_id)));
                            },
                            carreraEntries() {
                                if (!this.selectedCentros.length) {
                                    return [];
                                }
                                return this.optionEntries(this.carrerasOptions, this.searchCarreras)
                                    .filter(([id, value]) => {
                                        const centroMatch = (value.centro_ids || []).some((centroId) => this.selectedCentros.includes(String(centroId)));
                                        const departamentoMatch = !this.selectedDepartamentos.length
                                            || (value.departamento_ids || []).some((departamentoId) => this.selectedDepartamentos.includes(String(departamentoId)));

                                        return centroMatch && departamentoMatch;
                                    });
                            },
                            toggle(listName, id) {
                                id = String(id);
                                const values = this[listName].map(String);
                                const index = values.indexOf(id);
                                if (index === -1) {
                                    values.push(id);
                                } else {
                                    values.splice(index, 1);
                                }
                                this[listName] = values;
                                this.filterSelections();
                                this.notifyChange();
                            },
                            remove(listName, id) {
                                id = String(id);
                                this[listName] = this[listName].map(String).filter((value) => value !== id);
                                this.filterSelections();
                                this.notifyChange();
                            },
                            filterSelections() {
                                const departamentoIds = this.departamentoEntries().map(([id]) => String(id));
                                this.selectedDepartamentos = this.selectedDepartamentos.filter((id) => departamentoIds.includes(String(id)));

                                const carreraIds = this.carreraEntries().map(([id]) => String(id));
                                this.selectedCarreras = this.selectedCarreras.filter((id) => carreraIds.includes(String(id)));
                            },
                            isSelected(listName, id) {
                                return this[listName].map(String).includes(String(id));
                            },
                            label(options, id) {
                                const value = options[String(id)] ?? options[id] ?? id;
                                return typeof value === 'object' ? value.label : value;
                            },
                            notifyChange() {
                                this.$nextTick(() => this.$root.dispatchEvent(new Event('change', { bubbles: true })));
                            },
                        }"
                        class="md:col-span-3 grid grid-cols-1 gap-4 md:grid-cols-3"
                    >
                        <div>
                            <label class="{{ $label }}">Centro / Facultad</label>
                            <div @click.outside="openCentros = false" class="relative">
                                <div @click="openCentros = true; $nextTick(() => $refs.searchCentros?.focus())"
                                    class="min-h-[42px] w-full cursor-text rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm transition focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500 dark:border-gray-600 dark:bg-gray-800">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <template x-for="id in selectedCentros" :key="`centro-${id}`">
                                            <span class="inline-flex max-w-full items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                                <span class="truncate" x-text="label(centrosOptions, id)"></span>
                                                <button type="button" @click.stop="remove('selectedCentros', id)" class="font-bold leading-none hover:text-blue-950 dark:hover:text-blue-100">×</button>
                                            </span>
                                        </template>
                                        <input x-ref="searchCentros" x-model="searchCentros" @focus="openCentros = true" @keydown.escape="openCentros = false"
                                            :placeholder="selectedCentros.length ? '' : 'Buscar o seleccionar centros/facultades...'"
                                            class="min-w-[180px] flex-1 border-0 bg-transparent p-0 text-sm text-gray-900 placeholder:text-gray-400 focus:ring-0 dark:text-white"
                                            type="text">
                                        <span class="ml-auto text-xs text-gray-400" x-text="openCentros ? '▴' : '▾'"></span>
                                    </div>
                                    <input type="hidden" name="centro_facultad_id" :value="selectedCentros[0] || ''">
                                    <template x-for="id in selectedCentros" :key="`centro-input-${id}`">
                                        <input type="checkbox" name="centro_facultad_ids[]" :value="id" checked class="hidden">
                                    </template>
                                </div>
                                <div x-show="openCentros" x-cloak class="absolute z-50 mt-1 max-h-56 w-full overflow-y-auto rounded-md border border-blue-200 bg-white shadow-lg dark:border-blue-700 dark:bg-gray-800">
                                    <template x-if="optionEntries(centrosOptions, searchCentros).length === 0">
                                        <div class="px-3 py-2 text-sm text-gray-500">Sin resultados.</div>
                                    </template>
                                    <template x-for="[id, name] in optionEntries(centrosOptions, searchCentros)" :key="id">
                                        <div @click="toggle('selectedCentros', id)" class="flex cursor-pointer items-center justify-between px-3 py-2 text-sm hover:bg-blue-50 dark:hover:bg-gray-700"
                                            :class="isSelected('selectedCentros', id) ? 'bg-blue-50 font-medium text-blue-700 dark:bg-blue-900/20 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300'">
                                            <span x-text="name"></span>
                                            <span x-show="isSelected('selectedCentros', id)" class="text-xs">✓</span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="{{ $label }}">Departamento académico</label>
                            <div @click.outside="openDepartamentos = false" class="relative">
                                <div @click="if (selectedCentros.length) { openDepartamentos = true; $nextTick(() => $refs.searchDepartamentos?.focus()) }"
                                    class="min-h-[42px] w-full rounded-md border px-3 py-2 text-sm shadow-sm transition"
                                    :class="selectedCentros.length ? 'cursor-text border-gray-300 bg-white focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500 dark:border-gray-600 dark:bg-gray-800' : 'cursor-not-allowed border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/60'">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <template x-for="id in selectedDepartamentos" :key="`departamento-${id}`">
                                            <span class="inline-flex max-w-full items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                                <span class="truncate" x-text="label(departamentosOptions, id)"></span>
                                                <button type="button" @click.stop="remove('selectedDepartamentos', id)" class="font-bold leading-none hover:text-blue-950 dark:hover:text-blue-100">×</button>
                                            </span>
                                        </template>
                                        <input x-ref="searchDepartamentos" x-model="searchDepartamentos" @focus="if (selectedCentros.length) openDepartamentos = true" @keydown.escape="openDepartamentos = false"
                                            :disabled="!selectedCentros.length"
                                            :placeholder="selectedDepartamentos.length ? '' : (selectedCentros.length ? 'Buscar o seleccionar departamentos...' : 'Seleccione primero Centro / Facultad.')"
                                            class="min-w-[180px] flex-1 border-0 bg-transparent p-0 text-sm text-gray-900 placeholder:text-gray-400 focus:ring-0 disabled:cursor-not-allowed dark:text-white"
                                            type="text">
                                        <span class="ml-auto text-xs text-gray-400" x-text="openDepartamentos ? '▴' : '▾'"></span>
                                    </div>
                                    <input type="hidden" name="departamento_academico_id" :value="selectedDepartamentos[0] || ''">
                                    <template x-for="id in selectedDepartamentos" :key="`departamento-input-${id}`">
                                        <input type="checkbox" name="departamento_academico_ids[]" :value="id" checked class="hidden">
                                    </template>
                                </div>
                                <div x-show="openDepartamentos && selectedCentros.length" x-cloak class="absolute z-50 mt-1 max-h-56 w-full overflow-y-auto rounded-md border border-blue-200 bg-white shadow-lg dark:border-blue-700 dark:bg-gray-800">
                                    <template x-if="departamentoEntries().length === 0">
                                        <div class="px-3 py-2 text-sm text-gray-500">Sin resultados.</div>
                                    </template>
                                    <template x-for="[id, departamento] in departamentoEntries()" :key="id">
                                        <div @click="toggle('selectedDepartamentos', id)" class="flex cursor-pointer items-center justify-between px-3 py-2 text-sm hover:bg-blue-50 dark:hover:bg-gray-700"
                                            :class="isSelected('selectedDepartamentos', id) ? 'bg-blue-50 font-medium text-blue-700 dark:bg-blue-900/20 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300'">
                                            <span x-text="departamento.label"></span>
                                            <span x-show="isSelected('selectedDepartamentos', id)" class="text-xs">✓</span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="{{ $label }}">Carrera</label>
                            <div @click.outside="openCarreras = false" class="relative">
                                <div @click="if (selectedCentros.length) { openCarreras = true; $nextTick(() => $refs.searchCarreras?.focus()) }"
                                    class="min-h-[42px] w-full rounded-md border px-3 py-2 text-sm shadow-sm transition"
                                    :class="selectedCentros.length ? 'cursor-text border-gray-300 bg-white focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500 dark:border-gray-600 dark:bg-gray-800' : 'cursor-not-allowed border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/60'">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <template x-for="id in selectedCarreras" :key="`carrera-${id}`">
                                            <span class="inline-flex max-w-full items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                                <span class="truncate" x-text="label(carrerasOptions, id)"></span>
                                                <button type="button" @click.stop="remove('selectedCarreras', id)" class="font-bold leading-none hover:text-blue-950 dark:hover:text-blue-100">×</button>
                                            </span>
                                        </template>
                                        <input x-ref="searchCarreras" x-model="searchCarreras" @focus="if (selectedCentros.length) openCarreras = true" @keydown.escape="openCarreras = false"
                                            :disabled="!selectedCentros.length"
                                            :placeholder="selectedCarreras.length ? '' : (selectedCentros.length ? 'Buscar o seleccionar carreras...' : 'Seleccione primero Centro / Facultad.')"
                                            class="min-w-[180px] flex-1 border-0 bg-transparent p-0 text-sm text-gray-900 placeholder:text-gray-400 focus:ring-0 disabled:cursor-not-allowed dark:text-white"
                                            type="text">
                                        <span class="ml-auto text-xs text-gray-400" x-text="openCarreras ? '▴' : '▾'"></span>
                                    </div>
                                    <input type="hidden" name="carrera_id" :value="selectedCarreras[0] || ''">
                                    <template x-for="id in selectedCarreras" :key="`carrera-input-${id}`">
                                        <input type="checkbox" name="carrera_ids[]" :value="id" checked class="hidden">
                                    </template>
                                </div>
                                <div x-show="openCarreras && selectedCentros.length" x-cloak class="absolute z-50 mt-1 max-h-56 w-full overflow-y-auto rounded-md border border-blue-200 bg-white shadow-lg dark:border-blue-700 dark:bg-gray-800">
                                    <template x-if="carreraEntries().length === 0">
                                        <div class="px-3 py-2 text-sm text-gray-500">Sin resultados.</div>
                                    </template>
                                    <template x-for="[id, carrera] in carreraEntries()" :key="id">
                                        <div @click="toggle('selectedCarreras', id)" class="flex cursor-pointer items-center justify-between px-3 py-2 text-sm hover:bg-blue-50 dark:hover:bg-gray-700"
                                            :class="isSelected('selectedCarreras', id) ? 'bg-blue-50 font-medium text-blue-700 dark:bg-blue-900/20 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300'">
                                            <span x-text="carrera.label"></span>
                                            <span x-show="isSelected('selectedCarreras', id)" class="text-xs">✓</span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="{{ $label }}">Horas teóricas</label>
                        <input type="number" min="0" name="horas_teoricas" value="{{ old('horas_teoricas', 0) }}" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Horas prácticas</label>
                        <input type="number" min="0" name="horas_practicas" value="{{ old('horas_practicas', 0) }}" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Total horas</label>
                        <input type="number" min="0" name="total_horas" value="{{ old('total_horas', 0) }}" class="{{ $input }}">
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
                            <option>Semi presencial (Virtual + presencial)</option>
                            <option>100% virtual</option>
                            <option>Virtual sincrónico (teledocencia)</option>
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
                        <label class="{{ $label }}">Descripción de las plataformas virtuales y de teledocencia</label>
                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                            <div class="rounded-md border border-slate-200 p-3 dark:border-slate-700">
                                <h3 class="mb-3 text-sm font-semibold text-slate-800 dark:text-slate-100">Teledocencia</h3>
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    @foreach ($catalog('plataforma')->filter(fn ($item) => in_array($item->nombre, ['Teams', 'Zoom', 'Meet', 'Webex', 'Otro'], true)) as $item)
                                        <label class="flex items-center gap-2 rounded-md border border-slate-200 px-3 py-2 text-sm dark:border-slate-700">
                                            <input type="checkbox" name="catalogos[plataforma_teledocencia][]" value="{{ $item->id }}" @checked(in_array((string) $item->id, array_map('strval', (array) old('catalogos.plataforma_teledocencia', [])), true)) class="rounded border-gray-300 text-blue-600">
                                            <span>{{ $item->nombre }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="rounded-md border border-slate-200 p-3 dark:border-slate-700">
                                <h3 class="mb-3 text-sm font-semibold text-slate-800 dark:text-slate-100">Campus virtual</h3>
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    @foreach ($catalog('plataforma')->filter(fn ($item) => in_array($item->nombre, ['Campus Virtual UNAH', 'Moodle', 'Classroom Google', 'Teams', 'Otro'], true)) as $item)
                                        <label class="flex items-center gap-2 rounded-md border border-slate-200 px-3 py-2 text-sm dark:border-slate-700">
                                            <input type="checkbox" name="catalogos[plataforma_campus_virtual][]" value="{{ $item->id }}" @checked(in_array((string) $item->id, array_map('strval', (array) old('catalogos.plataforma_campus_virtual', [])), true)) class="rounded border-gray-300 text-blue-600">
                                            <span>{{ $item->nombre }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="md:col-span-3">
                        <label class="{{ $label }}">Antecedentes de la acción</label>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($catalog('antecedente') as $item)
                                <label class="flex items-center gap-2 rounded-md border border-slate-200 px-3 py-2 text-sm dark:border-slate-700">
                                    <input type="checkbox" name="catalogos[antecedente][]" value="{{ $item->id }}" @checked(in_array((string) $item->id, array_map('strval', (array) old('catalogos.antecedente', [])), true)) class="rounded border-gray-300 text-blue-600">
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
                        <select name="coordinador[empleado_id]" class="{{ $input }}" data-registered-employee-select="coordinador">
                            <option value="">Seleccione...</option>
                            @foreach ($empleados as $empleado)
                                <option
                                    value="{{ $empleado->id }}"
                                    data-nombre="{{ $empleado->nombre_completo }}"
                                    data-numero-empleado="{{ $empleado->numero_empleado }}"
                                    data-correo="{{ $empleado->user?->email }}"
                                    data-celular="{{ $empleado->celular }}"
                                    data-categoria="{{ $empleado->categoria?->nombre }}"
                                    data-departamento="{{ $empleado->departamento_academico?->nombre }}"
                                >
                                    {{ $empleado->nombre_completo }} · {{ $empleado->numero_empleado }}
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="coordinador[nombre_completo]">
                        <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <input name="coordinador[numero_empleado]" class="{{ $input }}" placeholder="No. empleado/a" readonly>
                            <input type="email" name="coordinador[correo]" class="{{ $input }}" placeholder="Correo electrónico" readonly>
                            <input name="coordinador[celular]" class="{{ $input }}" placeholder="Celular" readonly>
                            <input name="coordinador[categoria]" class="{{ $input }}" placeholder="Categoría" readonly>
                            <input name="coordinador[departamento]" class="{{ $input }} sm:col-span-2" placeholder="Departamento al que pertenece" readonly>
                        </div>
                    </div>
                    <div class="rounded-md border border-slate-200 p-4 dark:border-slate-700">
                        <h3 class="mb-3 text-sm font-semibold text-slate-800 dark:text-slate-100">Responsable de sistematización</h3>
                        <label class="{{ $label }}">Empleado registrado</label>
                        <select name="sistematizador[empleado_id]" class="{{ $input }}" data-registered-employee-select="sistematizador">
                            <option value="">Seleccione...</option>
                            @foreach ($empleados as $empleado)
                                <option
                                    value="{{ $empleado->id }}"
                                    data-nombre="{{ $empleado->nombre_completo }}"
                                    data-numero-empleado="{{ $empleado->numero_empleado }}"
                                    data-correo="{{ $empleado->user?->email }}"
                                    data-celular="{{ $empleado->celular }}"
                                    data-categoria="{{ $empleado->categoria?->nombre }}"
                                    data-departamento="{{ $empleado->departamento_academico?->nombre }}"
                                >
                                    {{ $empleado->nombre_completo }} · {{ $empleado->numero_empleado }}
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="sistematizador[nombre_completo]">
                        <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <input name="sistematizador[numero_empleado]" class="{{ $input }}" placeholder="No. empleado/a" readonly>
                            <input type="email" name="sistematizador[correo]" class="{{ $input }}" placeholder="Correo electrónico" readonly>
                            <input name="sistematizador[celular]" class="{{ $input }}" placeholder="Celular" readonly>
                            <input name="sistematizador[categoria]" class="{{ $input }}" placeholder="Categoría" readonly>
                            <input name="sistematizador[departamento]" class="{{ $input }} sm:col-span-2" placeholder="Departamento al que pertenece" readonly>
                        </div>
                    </div>
                </div>

                <div class="mt-5 rounded-md border border-slate-200 p-4 dark:border-slate-700">
                    <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Equipo docente de la UNAH</h3>
                        <button type="button" data-open-employee-modal
                            class="rounded-md bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">
                            Agregar docente
                        </button>
                    </div>
                    <div class="overflow-x-auto rounded-md border border-slate-100 dark:border-slate-800">
                        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500 dark:bg-slate-800/60">
                                <tr>
                                    <th class="px-3 py-2">Nombre</th>
                                    <th class="px-3 py-2">No. empleado</th>
                                    <th class="px-3 py-2">Correo</th>
                                    <th class="px-3 py-2">Categoría</th>
                                    <th class="px-3 py-2">Departamento</th>
                                    <th class="px-3 py-2">Jornada</th>
                                    <th class="px-3 py-2"></th>
                                </tr>
                            </thead>
                            <tbody data-equipo-docente-list class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr><td colspan="7" class="px-3 py-4 text-center text-slate-500">Sin docentes agregados.</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="hidden" data-equipo-docente-fields>
                        @for ($i = 0; $i < 5; $i++)
                            <div data-equipo-docente-row="{{ $i }}">
                                <input name="equipo_docente[{{ $i }}][nombre_completo]" class="{{ $input }} md:col-span-2" placeholder="Nombre completo">
                                <input name="equipo_docente[{{ $i }}][numero_empleado]" class="{{ $input }}" placeholder="No. empleado/a">
                                <input type="email" name="equipo_docente[{{ $i }}][correo]" class="{{ $input }}" placeholder="Correo">
                                <input name="equipo_docente[{{ $i }}][categoria]" class="{{ $input }}" placeholder="Categoría">
                                <input name="equipo_docente[{{ $i }}][departamento]" class="{{ $input }}" placeholder="Departamento">
                                <input name="equipo_docente[{{ $i }}][jornada_laboral]" class="{{ $input }}" placeholder="Jornada laboral">
                            </div>
                        @endfor
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-2">
                    @foreach ([
                        'consultores_nacionales' => ['Consultores nacionales', ['Profesión', 'profesion']],
                        'consultores_internacionales' => ['Consultores internacionales', ['Nacionalidad', 'nacionalidad']],
                    ] as $name => [$title, $extra])
                        <div class="rounded-md border border-slate-200 p-4 dark:border-slate-700">
                            <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $title }}</h3>
                                <button type="button" data-open-consultor-modal="{{ $name }}" data-extra-label="{{ $extra[0] }}" data-extra-field="{{ $extra[1] }}"
                                    class="rounded-md bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">
                                    Agregar
                                </button>
                            </div>
                            <div class="overflow-x-auto rounded-md border border-slate-100 dark:border-slate-800">
                                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                                    <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500 dark:bg-slate-800/60">
                                        <tr>
                                            <th class="px-3 py-2">Nombre</th>
                                            <th class="px-3 py-2">{{ $extra[0] }}</th>
                                            <th class="px-3 py-2">Correo</th>
                                            <th class="px-3 py-2">Horas</th>
                                            <th class="px-3 py-2"></th>
                                        </tr>
                                    </thead>
                                    <tbody data-consultor-list="{{ $name }}" data-extra-label="{{ $extra[0] }}" data-extra-field="{{ $extra[1] }}" class="divide-y divide-slate-100 dark:divide-slate-800">
                                        <tr><td colspan="5" class="px-3 py-4 text-center text-slate-500">Sin consultores agregados.</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="hidden" data-consultor-fields="{{ $name }}">
                                @for ($i = 0; $i < 5; $i++)
                                    <div data-consultor-row="{{ $name }}" data-index="{{ $i }}">
                                        <input name="{{ $name }}[{{ $i }}][nombre_completo]" class="{{ $input }}" placeholder="Nombre completo">
                                        <input name="{{ $name }}[{{ $i }}][{{ $extra[1] }}]" class="{{ $input }}" placeholder="{{ $extra[0] }}">
                                        <input type="email" name="{{ $name }}[{{ $i }}][correo]" class="{{ $input }}" placeholder="Correo electrónico">
                                        <input type="number" min="0" name="{{ $name }}[{{ $i }}][horas_contratadas]" class="{{ $input }}" placeholder="Horas contratadas">
                                    </div>
                                @endfor
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 rounded-md border border-slate-200 p-4 dark:border-slate-700">
                    <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Participación de la comunidad universitaria</h3>
                    </div>
                    <div class="overflow-x-auto rounded-md border border-slate-100 dark:border-slate-800">
                        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500 dark:bg-slate-800/60">
                                <tr>
                                    <th class="px-3 py-2">Tipo</th>
                                    <th class="px-3 py-2">Total</th>
                                    <th class="px-3 py-2">Hombres</th>
                                    <th class="px-3 py-2">Mujeres</th>
                                    <th class="px-3 py-2"></th>
                                </tr>
                            </thead>
                            <tbody data-participacion-list class="divide-y divide-slate-100 dark:divide-slate-800"></tbody>
                        </table>
                    </div>
                    <div class="hidden" data-participacion-fields>
                        @foreach ([
                            'Estudiantes de grado / posgrado',
                            'Práctica de asignatura',
                            'Servicio Social o PPS',
                            'Voluntariado',
                            'Personal docente',
                            'Profesores por hora',
                            'Profesores horarios',
                            'Profesores permanentes',
                            'Personal administrativo',
                            'Administrativo servicios',
                            'Asistentes técnicos laboratorios / instructores',
                        ] as $i => $tipoParticipacion)
                            <div data-participacion-row="{{ $i }}">
                                <input type="hidden" name="participacion_universitaria[{{ $i }}][tipo_participacion]" value="{{ $tipoParticipacion }}">
                                <input type="number" min="0" name="participacion_universitaria[{{ $i }}][cantidad]" class="{{ $input }}" placeholder="Total">
                                <input type="number" min="0" name="participacion_universitaria[{{ $i }}][hombres]" class="{{ $input }}" placeholder="H">
                                <input type="number" min="0" name="participacion_universitaria[{{ $i }}][mujeres]" class="{{ $input }}" placeholder="M">
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-5 rounded-md border border-slate-200 p-4 dark:border-slate-700">
                    <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Detalle de la práctica de asignatura / posgrado</h3>
                        <button type="button" data-open-practica-modal
                            class="rounded-md bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">
                            Agregar práctica
                        </button>
                    </div>
                    <div class="overflow-x-auto rounded-md border border-slate-100 dark:border-slate-800">
                        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500 dark:bg-slate-800/60">
                                <tr>
                                    <th class="px-3 py-2">Código</th>
                                    <th class="px-3 py-2">Nombre</th>
                                    <th class="px-3 py-2">Período</th>
                                    <th class="px-3 py-2">Matrícula</th>
                                    <th class="px-3 py-2">H</th>
                                    <th class="px-3 py-2">M</th>
                                    <th class="px-3 py-2"></th>
                                </tr>
                            </thead>
                            <tbody data-practicas-list class="divide-y divide-slate-100 dark:divide-slate-800"></tbody>
                        </table>
                    </div>
                    <div class="hidden" data-practicas-fields>
                        @for ($i = 0; $i < 3; $i++)
                            <div data-practica-row="{{ $i }}">
                                <input name="practicas_asignatura[{{ $i }}][codigo]" class="{{ $input }}" placeholder="Código">
                                <input name="practicas_asignatura[{{ $i }}][nombre]" class="{{ $input }}" placeholder="Nombre asignatura / posgrado">
                                <select name="practicas_asignatura[{{ $i }}][periodo_academico_id]" class="{{ $input }}">
                                    <option value="">Período registrado...</option>
                                    @foreach ($periodosAcademicos as $periodo)
                                        <option value="{{ $periodo->id }}">{{ $periodoAcademicoLabel($periodo) }}</option>
                                    @endforeach
                                </select>
                                <input name="practicas_asignatura[{{ $i }}][periodo_academico]" class="{{ $input }}" placeholder="Período académico">
                                <input type="number" min="0" name="practicas_asignatura[{{ $i }}][matricula_total]" class="{{ $input }}" placeholder="Matrícula">
                                <input type="number" min="0" name="practicas_asignatura[{{ $i }}][hombres]" class="{{ $input }}" placeholder="H">
                                <input type="number" min="0" name="practicas_asignatura[{{ $i }}][mujeres]" class="{{ $input }}" placeholder="M">
                            </div>
                        @endfor
                    </div>
                </div>
            </div>

            <div class="{{ $card }} hidden" data-step-panel="5">
                <h2 class="{{ $sectionTitle }}">5. Entidad contraparte</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="md:col-span-3">
                        <label class="{{ $label }}">La actividad tiene contraparte</label>
                        <div class="flex items-center gap-6">
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                                <input type="radio" name="contraparte[tiene_contraparte]" value="Si" class="text-blue-600 focus:ring-blue-500" data-contraparte-toggle>
                                Sí
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                                <input type="radio" name="contraparte[tiene_contraparte]" value="No" class="text-blue-600 focus:ring-blue-500" data-contraparte-toggle checked>
                                No
                            </label>
                        </div>
                    </div>
                    <div><label class="{{ $label }}">Nombre de la contraparte</label><input name="contraparte[nombre]" class="{{ $input }}" data-contraparte-field></div>
                    <div><label class="{{ $label }}">Contacto directo</label><input name="contraparte[representante]" class="{{ $input }}" data-contraparte-field></div>
                    <div><label class="{{ $label }}">Cargo del contacto</label><input name="contraparte[cargo_contacto]" class="{{ $input }}" data-contraparte-field></div>
                    <div><label class="{{ $label }}">Correo</label><input type="email" name="contraparte[correo]" class="{{ $input }}" data-contraparte-field></div>
                    <div><label class="{{ $label }}">Teléfono</label><input name="contraparte[telefono]" class="{{ $input }}" data-contraparte-field></div>
                    <div><label class="{{ $label }}">Dirección exacta de la sede principal</label><input name="contraparte[direccion]" class="{{ $input }}" data-contraparte-field></div>
                    <div>
                        <label class="{{ $label }}">Perfil contraparte</label>
                        <select name="contraparte[tipo_contraparte_id]" class="{{ $input }}" data-contraparte-field>
                            <option value="">Seleccione...</option>
                            @foreach ($catalog('tipo_contraparte') as $item)
                                <option value="{{ $item->id }}">{{ $item->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
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

            <div class="{{ $card }} hidden" data-step-panel="6">
                <h2 class="{{ $sectionTitle }}">6. Información de la acción</h2>
                <div class="grid grid-cols-1 gap-4">
                    <div><label class="{{ $label }}">Resumen de la acción</label><textarea name="resumen" rows="4" class="{{ $input }}"></textarea></div>
                    <div><label class="{{ $label }}">Definición del problema</label><textarea name="definicion_problema" rows="3" class="{{ $input }}"></textarea></div>
                    <div><label class="{{ $label }}">Objetivo general</label><textarea name="objetivo_general" rows="3" class="{{ $input }}"></textarea></div>
                    <div class="rounded-md border border-slate-200 p-4 dark:border-slate-700">
                        <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <h3 class="text-base font-semibold text-slate-800 dark:text-slate-100">Objetivos específicos</h3>
                            <button type="button" data-add-objetivo-especifico class="rounded-md bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">Agregar objetivo</button>
                        </div>
                        <div data-objetivos-especificos-list class="space-y-3"></div>
                        <template data-objetivo-especifico-template>
                            <div data-objetivo-especifico-row class="rounded-md border border-slate-200 p-3 dark:border-slate-700">
                                <div class="mb-2 flex items-center justify-between gap-3">
                                    <label class="{{ $label }} mb-0">Objetivo específico <span data-objetivo-especifico-number></span></label>
                                    <button type="button" data-remove-objetivo-especifico class="text-sm font-semibold text-red-600 hover:text-red-700">Quitar</button>
                                </div>
                                <textarea name="objetivos_especificos[]" rows="2" class="{{ $input }}"></textarea>
                            </div>
                        </template>
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
                    <div
                        x-data="{
                            openEjes: false,
                            openOds: false,
                            openMetas: false,
                            searchEjes: '',
                            searchOds: '',
                            searchMetas: '',
                            ejesOptions: @js($ejesUnahOptions),
                            odsOptions: @js($odsOptions),
                            metasOptions: @js($metasContribuyeOptions),
                            selectedEjes: @js(array_map('strval', (array) old('eje_unah_ids', []))),
                            selectedOds: @js(array_map('strval', (array) old('ods_ids', []))),
                            selectedMetas: @js(array_map('strval', (array) old('meta_contribuye_ids', []))),
                            init() {
                                const key = this.$root.closest('form')?.dataset.storageKey;
                                if (key) {
                                    try {
                                        const initial = window.__enfInitialDrafts?.[key] || {};
                                        const stored = JSON.parse(window.localStorage.getItem(key) || '{}');
                                        const data = { ...initial, ...stored };
                                        this.selectedEjes = this.normalized(data['eje_unah_ids[]'] ?? this.selectedEjes);
                                        this.selectedOds = this.normalized(data['ods_ids[]'] ?? this.selectedOds);
                                        this.selectedMetas = this.normalized(data['meta_contribuye_ids[]'] ?? this.selectedMetas);
                                    } catch (error) {}
                                }
                                this.filterSelectedMetas();
                            },
                            normalized(values) {
                                return Array.isArray(values) ? values.map(String) : [];
                            },
                            optionEntries(options, search = '') {
                                const term = search.trim().toLowerCase();
                                return Object.entries(options || {}).filter(([id, value]) => {
                                    const label = typeof value === 'object' ? value.label : value;
                                    return !term || String(label).toLowerCase().includes(term);
                                });
                            },
                            metaEntries() {
                                if (!this.selectedOds.length) {
                                    return [];
                                }
                                return this.optionEntries(this.metasOptions, this.searchMetas)
                                    .filter(([id, value]) => this.selectedOds.includes(String(value.ods_id)));
                            },
                            toggle(listName, id) {
                                id = String(id);
                                const values = this[listName].map(String);
                                const index = values.indexOf(id);
                                if (index === -1) {
                                    values.push(id);
                                } else {
                                    values.splice(index, 1);
                                }
                                this[listName] = values;
                                if (listName === 'selectedOds') {
                                    this.filterSelectedMetas();
                                }
                                this.notifyChange();
                            },
                            remove(listName, id) {
                                id = String(id);
                                this[listName] = this[listName].map(String).filter(value => value !== id);
                                if (listName === 'selectedOds') {
                                    this.filterSelectedMetas();
                                }
                                this.notifyChange();
                            },
                            filterSelectedMetas() {
                                this.selectedMetas = this.selectedMetas
                                    .map(String)
                                    .filter(id => this.selectedOds.includes(String(this.metasOptions[id]?.ods_id)));
                            },
                            isSelected(listName, id) {
                                return this[listName].map(String).includes(String(id));
                            },
                            label(options, id) {
                                const value = options[String(id)] ?? options[id] ?? id;
                                return typeof value === 'object' ? value.label : value;
                            },
                            notifyChange() {
                                this.$nextTick(() => this.$root.dispatchEvent(new Event('change', { bubbles: true })));
                            },
                        }"
                        class="space-y-8 rounded-md border border-slate-200 p-4 dark:border-slate-700"
                    >
                        <div>
                            <label class="{{ $label }}">Ejes prioritarios UNAH</label>
                            <div @click.outside="openEjes = false" class="relative">
                                <div @click="openEjes = true; $nextTick(() => $refs.searchEjes?.focus())"
                                    class="min-h-[42px] w-full cursor-text rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm transition focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500 dark:border-gray-600 dark:bg-gray-800">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <template x-for="id in selectedEjes" :key="`eje-${id}`">
                                            <span class="inline-flex max-w-full items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                                <span class="truncate" x-text="label(ejesOptions, id)"></span>
                                                <button type="button" @click.stop="remove('selectedEjes', id)" class="font-bold leading-none hover:text-blue-950 dark:hover:text-blue-100">×</button>
                                            </span>
                                        </template>
                                        <input x-ref="searchEjes" x-model="searchEjes" @focus="openEjes = true" @keydown.escape="openEjes = false"
                                            :placeholder="selectedEjes.length ? '' : 'Buscar o seleccionar ejes prioritarios...'"
                                            class="min-w-[180px] flex-1 border-0 bg-transparent p-0 text-sm text-gray-900 placeholder:text-gray-400 focus:ring-0 dark:text-white"
                                            type="text">
                                        <span class="ml-auto text-xs text-gray-400" x-text="openEjes ? '▴' : '▾'"></span>
                                    </div>
                                    <template x-for="id in selectedEjes" :key="`eje-input-${id}`">
                                        <input type="checkbox" name="eje_unah_ids[]" :value="id" checked class="hidden">
                                    </template>
                                </div>
                                <div x-show="openEjes" x-cloak class="absolute z-50 mt-1 max-h-56 w-full overflow-y-auto rounded-md border border-blue-200 bg-white shadow-lg dark:border-blue-700 dark:bg-gray-800">
                                    <template x-if="optionEntries(ejesOptions, searchEjes).length === 0">
                                        <div class="px-3 py-2 text-sm text-gray-500">Sin resultados.</div>
                                    </template>
                                    <template x-for="[id, name] in optionEntries(ejesOptions, searchEjes)" :key="id">
                                        <div @click="toggle('selectedEjes', id)" class="flex cursor-pointer items-center justify-between px-3 py-2 text-sm hover:bg-blue-50 dark:hover:bg-gray-700"
                                            :class="isSelected('selectedEjes', id) ? 'bg-blue-50 font-medium text-blue-700 dark:bg-blue-900/20 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300'">
                                            <span x-text="name"></span>
                                            <span x-show="isSelected('selectedEjes', id)" class="text-xs">✓</span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                            <div>
                                <label class="{{ $label }}">ODS</label>
                                <div @click.outside="openOds = false" class="relative">
                                    <div @click="openOds = true; $nextTick(() => $refs.searchOds?.focus())"
                                        class="min-h-[42px] w-full cursor-text rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm transition focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500 dark:border-gray-600 dark:bg-gray-800">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <template x-for="id in selectedOds" :key="`ods-${id}`">
                                                <span class="inline-flex max-w-full items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                                    <span class="truncate" x-text="label(odsOptions, id)"></span>
                                                    <button type="button" @click.stop="remove('selectedOds', id)" class="font-bold leading-none hover:text-blue-950 dark:hover:text-blue-100">×</button>
                                                </span>
                                            </template>
                                            <input x-ref="searchOds" x-model="searchOds" @focus="openOds = true" @keydown.escape="openOds = false"
                                                :placeholder="selectedOds.length ? '' : 'Buscar o seleccionar ODS...'"
                                                class="min-w-[180px] flex-1 border-0 bg-transparent p-0 text-sm text-gray-900 placeholder:text-gray-400 focus:ring-0 dark:text-white"
                                                type="text">
                                            <span class="ml-auto text-xs text-gray-400" x-text="openOds ? '▴' : '▾'"></span>
                                        </div>
                                        <template x-for="id in selectedOds" :key="`ods-input-${id}`">
                                            <input type="checkbox" name="ods_ids[]" :value="id" checked class="hidden">
                                        </template>
                                    </div>
                                    <div x-show="openOds" x-cloak class="absolute z-50 mt-1 max-h-56 w-full overflow-y-auto rounded-md border border-blue-200 bg-white shadow-lg dark:border-blue-700 dark:bg-gray-800">
                                        <template x-if="optionEntries(odsOptions, searchOds).length === 0">
                                            <div class="px-3 py-2 text-sm text-gray-500">Sin resultados.</div>
                                        </template>
                                        <template x-for="[id, name] in optionEntries(odsOptions, searchOds)" :key="id">
                                            <div @click="toggle('selectedOds', id)" class="flex cursor-pointer items-center justify-between px-3 py-2 text-sm hover:bg-blue-50 dark:hover:bg-gray-700"
                                                :class="isSelected('selectedOds', id) ? 'bg-blue-50 font-medium text-blue-700 dark:bg-blue-900/20 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300'">
                                                <span x-text="name"></span>
                                                <span x-show="isSelected('selectedOds', id)" class="text-xs">✓</span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="{{ $label }}">Metas a las que contribuye</label>
                                <div @click.outside="openMetas = false" class="relative">
                                    <div @click="if (selectedOds.length) { openMetas = true; $nextTick(() => $refs.searchMetas?.focus()) }"
                                        class="min-h-[42px] w-full rounded-md border px-3 py-2 text-sm shadow-sm transition"
                                        :class="selectedOds.length ? 'cursor-text border-gray-300 bg-white focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500 dark:border-gray-600 dark:bg-gray-800' : 'cursor-not-allowed border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/60'">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <template x-for="id in selectedMetas" :key="`meta-${id}`">
                                                <span class="inline-flex max-w-full items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                                    <span class="truncate" x-text="label(metasOptions, id)"></span>
                                                    <button type="button" @click.stop="remove('selectedMetas', id)" class="font-bold leading-none hover:text-blue-950 dark:hover:text-blue-100">×</button>
                                                </span>
                                            </template>
                                            <input x-ref="searchMetas" x-model="searchMetas" @focus="if (selectedOds.length) openMetas = true" @keydown.escape="openMetas = false"
                                                :disabled="!selectedOds.length"
                                                :placeholder="selectedMetas.length ? '' : (selectedOds.length ? 'Buscar o seleccionar metas...' : 'Seleccione uno o más ODS para ver sus metas relacionadas.')"
                                                class="min-w-[180px] flex-1 border-0 bg-transparent p-0 text-sm text-gray-900 placeholder:text-gray-400 focus:ring-0 disabled:cursor-not-allowed dark:text-white"
                                                type="text">
                                            <span class="ml-auto text-xs text-gray-400" x-text="openMetas ? '▴' : '▾'"></span>
                                        </div>
                                        <template x-for="id in selectedMetas" :key="`meta-input-${id}`">
                                            <input type="checkbox" name="meta_contribuye_ids[]" :value="id" checked class="hidden">
                                        </template>
                                    </div>
                                    <div x-show="openMetas && selectedOds.length" x-cloak class="absolute z-50 mt-1 max-h-56 w-full overflow-y-auto rounded-md border border-blue-200 bg-white shadow-lg dark:border-blue-700 dark:bg-gray-800">
                                        <template x-if="metaEntries().length === 0">
                                            <div class="px-3 py-2 text-sm text-gray-500">Sin resultados.</div>
                                        </template>
                                        <template x-for="[id, meta] in metaEntries()" :key="id">
                                            <div @click="toggle('selectedMetas', id)" class="flex cursor-pointer items-start gap-2 px-3 py-2 text-xs hover:bg-blue-50 dark:hover:bg-gray-700"
                                                :class="isSelected('selectedMetas', id) ? 'bg-blue-50 font-medium text-blue-700 dark:bg-blue-900/20 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300'">
                                                <span class="mt-0.5 shrink-0" x-show="isSelected('selectedMetas', id)">✓</span>
                                                <span x-text="meta.label"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="{{ $card }} hidden" data-step-panel="8">
                <h2 class="{{ $sectionTitle }}">8. Presupuesto</h2>
                <label class="mb-4 flex items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-200">
                    <input type="checkbox" name="genera_ingresos" value="1" @checked(old('genera_ingresos')) class="rounded border-gray-300 text-blue-600" data-genera-ingresos>
                    Obtendrá ingresos por el desarrollo de la actividad
                </label>
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                    @foreach (['presupuesto_ingresos' => ['Ingresos', ['Cuotas de inscripción', 'Mensualidades / módulos', 'Gestión de becas', 'Otros']], 'presupuesto_egresos' => ['Egresos', ['Pago de personal docente', 'Materiales y suministros', 'Movilización', 'Manutención y hospedaje', 'Costos administrativos', 'Otros gastos']]] as $name => [$title, $rubros])
                        <div class="rounded-md border border-slate-200 p-4 dark:border-slate-700" data-presupuesto-card="{{ $name }}">
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $title }}</h3>
                                <button type="button" data-open-presupuesto-modal="{{ $name }}"
                                    class="rounded-md bg-blue-700 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-50">
                                    Agregar {{ $name === 'presupuesto_ingresos' ? 'ingreso' : 'egreso' }}
                                </button>
                            </div>
                            <div class="overflow-x-auto rounded-md border border-slate-100 dark:border-slate-800">
                                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                                    <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500 dark:bg-slate-800/60">
                                        <tr>
                                            <th class="px-3 py-2">Rubro</th>
                                            <th class="px-3 py-2">Cantidad</th>
                                            <th class="px-3 py-2">Costo unitario</th>
                                            <th class="px-3 py-2">Total</th>
                                            <th class="px-3 py-2"></th>
                                        </tr>
                                    </thead>
                                    <tbody data-presupuesto-list="{{ $name }}" class="divide-y divide-slate-100 dark:divide-slate-800"></tbody>
                                </table>
                            </div>
                            <div class="hidden" data-presupuesto-fields="{{ $name }}" data-title="{{ $title }}" data-rubros='@json($rubros)'>
                                @for ($i = 0; $i < 20; $i++)
                                    <div data-presupuesto-row="{{ $name }}" data-index="{{ $i }}">
                                        <input type="text" name="{{ $name }}[{{ $i }}][rubro]" value="{{ old("{$name}.{$i}.rubro") }}" class="{{ $input }}">
                                        <input type="number" min="0" step="0.01" name="{{ $name }}[{{ $i }}][cantidad]" value="{{ old("{$name}.{$i}.cantidad") }}" class="{{ $input }}" placeholder="Cantidad">
                                        <input type="number" min="0" step="0.01" name="{{ $name }}[{{ $i }}][costo_unitario]" value="{{ old("{$name}.{$i}.costo_unitario") }}" class="{{ $input }}" placeholder="Costo unitario">
                                    </div>
                                @endfor
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
                <h2 class="{{ $sectionTitle }}">9. Cronograma</h2>
                <div class="mb-3 flex justify-end">
                    <button type="button" data-open-cronograma-modal
                        class="rounded-md bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">
                        Agregar actividad
                    </button>
                </div>
                <div class="overflow-x-auto rounded-md border border-slate-100 dark:border-slate-800">
                    <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                        <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500 dark:bg-slate-800/60">
                            <tr>
                                <th class="px-3 py-2">Actividad</th>
                                <th class="px-3 py-2">Producto</th>
                                <th class="px-3 py-2">Fecha inicio</th>
                                <th class="px-3 py-2">Responsable</th>
                                <th class="px-3 py-2">Horas</th>
                                <th class="px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody data-cronograma-list class="divide-y divide-slate-100 dark:divide-slate-800"></tbody>
                    </table>
                </div>
                <div class="hidden" data-cronograma-fields>
                    @for ($i = 0; $i < 5; $i++)
                        <div data-cronograma-row="{{ $i }}">
                            <input name="cronograma[{{ $i }}][actividad]" class="{{ $input }}" placeholder="Actividad">
                            <input name="cronograma[{{ $i }}][producto]" class="{{ $input }}" placeholder="Producto">
                            <input type="date" name="cronograma[{{ $i }}][fecha_inicio]" class="{{ $input }}">
                            <input name="cronograma[{{ $i }}][responsable]" class="{{ $input }}" placeholder="Responsable">
                            <input type="number" min="0" name="cronograma[{{ $i }}][horas_requeridas]" class="{{ $input }}" placeholder="Horas requeridas">
                        </div>
                    @endfor
                </div>
            </div>

            <div class="{{ $card }} hidden" data-step-panel="10">
                <h2 class="{{ $sectionTitle }}">10. Documentos adjuntos y firmas</h2>
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    @foreach ([
                        [
                            'label' => 'Oficio de remisión del Decano/Director Centro Regional',
                            'slug' => 'oficio_remision_decano',
                        ],
                        [
                            'label' => 'Documento perfil del programa de formación',
                            'slug' => 'documento_perfil_programa',
                        ],
                        [
                            'label' => 'Otros documentos de respaldo',
                            'slug' => 'otros_documentos_respaldo',
                        ],
                    ] as $documentoSupervisor)
                    <section class="rounded-md border border-slate-200 p-4 shadow-sm dark:border-slate-700" data-doc-upload-card>
                        <label class="flex items-center gap-2 text-sm font-semibold text-slate-900 dark:text-slate-100">
                            <input type="checkbox" name="documentos_requeridos[]" value="{{ $documentoSupervisor['label'] }}" class="rounded border-gray-300 text-blue-600" data-doc-upload-check>
                            <span>{{ $documentoSupervisor['label'] }}</span>
                        </label>

                        <div class="mt-4 space-y-4">
                            <div>
                                <p class="mb-2 text-sm font-medium text-slate-700 dark:text-slate-300">¿Desea subir archivo?</p>
                                <div class="flex items-center gap-5">
                                    <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                                        <input type="radio" name="supervisor_documentos[{{ $documentoSupervisor['slug'] }}][aplica]" value="Si" class="text-blue-600 focus:ring-blue-500" data-doc-upload-radio>
                                        Sí
                                    </label>
                                    <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                                        <input type="radio" name="supervisor_documentos[{{ $documentoSupervisor['slug'] }}][aplica]" value="No" class="text-blue-600 focus:ring-blue-500" data-doc-upload-radio checked>
                                        No
                                    </label>
                                </div>
                                @error("supervisor_documentos.{$documentoSupervisor['slug']}.aplica")<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label class="{{ $label }}">Archivo adjunto</label>
                                <input type="file" name="supervisor_documentos_archivos[{{ $documentoSupervisor['slug'] }}]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100 disabled:cursor-not-allowed disabled:opacity-50 dark:text-gray-300" data-doc-upload-file disabled>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Disponible solo cuando seleccione “Sí”.</p>
                                @error("supervisor_documentos_archivos.{$documentoSupervisor['slug']}")<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </section>
                    @endforeach
                </div>

                <div class="mt-6 border-t border-slate-200 pt-5 dark:border-slate-700">
                    <h3 class="mb-3 text-sm font-semibold text-slate-800 dark:text-slate-100">Firmas requeridas</h3>
                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        @foreach ($firmaForm018Roles as $i => $firmaRol)
                            <section class="rounded-md border border-slate-200 p-4 shadow-sm dark:border-slate-700">
                                <input type="hidden" name="firmas[{{ $i }}][rol_firma]" value="{{ $firmaRol['rol'] }}">
                                <label class="{{ $label }}">{{ $firmaRol['rol'] }}</label>
                                <input name="firmas[{{ $i }}][nombre_firmante]" class="{{ $input }}" placeholder="{{ $firmaRol['placeholder'] }}">
                            </section>
                        @endforeach
                    </div>
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
                        {{ $editingAccion ? 'Actualizar acción ENF' : 'Guardar acción ENF' }}
                    </button>
                </div>
            </div>
        </form>

        <div data-employee-modal class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-3xl rounded-lg bg-white p-5 shadow-xl dark:bg-slate-900">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Agregar docente UNAH</h2>
                    <button type="button" data-close-employee-modal class="rounded-md px-3 py-1 text-sm font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">Cerrar</button>
                </div>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="{{ $label }}">Buscar empleado</label>
                        <input type="search" data-employee-search class="{{ $input }}" placeholder="Nombre o número de empleado">
                    </div>
                    <div class="md:col-span-2 max-h-64 overflow-y-auto rounded-md border border-slate-200 dark:border-slate-700">
                        <div data-employee-results class="divide-y divide-slate-100 dark:divide-slate-800"></div>
                    </div>
                    <p class="md:col-span-2 text-xs text-slate-500 dark:text-slate-400">La jornada laboral se toma del registro del empleado seleccionado.</p>
                </div>
                <div class="mt-5 flex justify-end gap-3">
                    <button type="button" data-close-employee-modal class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Cancelar</button>
                </div>
            </div>
        </div>

        <div data-consultor-modal class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-2xl rounded-lg bg-white p-5 shadow-xl dark:bg-slate-900">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 data-consultor-modal-title class="text-base font-semibold text-slate-900 dark:text-slate-100">Agregar consultor</h2>
                    <button type="button" data-close-consultor-modal class="rounded-md px-3 py-1 text-sm font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">Cerrar</button>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="{{ $label }}">Nombre completo</label>
                        <input data-consultor-nombre class="{{ $input }}">
                    </div>
                    <div>
                        <label data-consultor-extra-label class="{{ $label }}">Detalle</label>
                        <input data-consultor-extra class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Correo electrónico</label>
                        <input type="email" data-consultor-correo class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Horas contratadas</label>
                        <input type="number" min="0" data-consultor-horas class="{{ $input }}">
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-3">
                    <button type="button" data-close-consultor-modal class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Cancelar</button>
                    <button type="button" data-add-consultor class="rounded-md bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">Agregar</button>
                </div>
            </div>
        </div>

        <div data-participacion-modal class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-xl rounded-lg bg-white p-5 shadow-xl dark:bg-slate-900">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 data-participacion-modal-title class="text-base font-semibold text-slate-900 dark:text-slate-100">Editar participación</h2>
                    <button type="button" data-close-participacion-modal class="rounded-md px-3 py-1 text-sm font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">Cerrar</button>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="sm:col-span-3">
                        <label class="{{ $label }}">Tipo de participación</label>
                        <input data-participacion-tipo class="{{ $input }}" readonly>
                    </div>
                    <div>
                        <label class="{{ $label }}">Total</label>
                        <input type="number" min="0" data-participacion-cantidad class="{{ $input }}" readonly>
                    </div>
                    <div>
                        <label class="{{ $label }}">Hombres</label>
                        <input type="number" min="0" data-participacion-hombres class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Mujeres</label>
                        <input type="number" min="0" data-participacion-mujeres class="{{ $input }}">
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-3">
                    <button type="button" data-close-participacion-modal class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Cancelar</button>
                    <button type="button" data-save-participacion class="rounded-md bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">Guardar</button>
                </div>
            </div>
        </div>

        <div data-practica-modal class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-4xl rounded-lg bg-white p-5 shadow-xl dark:bg-slate-900">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 data-practica-modal-title class="text-base font-semibold text-slate-900 dark:text-slate-100">Agregar práctica</h2>
                    <button type="button" data-close-practica-modal class="rounded-md px-3 py-1 text-sm font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">Cerrar</button>
                </div>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-6">
                    <div class="md:col-span-2">
                        <label class="{{ $label }}">Código</label>
                        <input data-practica-codigo class="{{ $input }}">
                    </div>
                    <div class="md:col-span-4">
                        <label class="{{ $label }}">Nombre asignatura / posgrado</label>
                        <input data-practica-nombre class="{{ $input }}">
                    </div>
                    <div class="md:col-span-3">
                        <label class="{{ $label }}">Período registrado</label>
                        <select data-practica-periodo-id class="{{ $input }}">
                            <option value="">Período registrado...</option>
                            @foreach ($periodosAcademicos as $periodo)
                                <option value="{{ $periodo->id }}">{{ $periodoAcademicoLabel($periodo) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-3">
                        <label class="{{ $label }}">Período académico</label>
                        <input data-practica-periodo-texto class="{{ $input }}" readonly>
                    </div>
                    <div>
                        <label class="{{ $label }}">Matrícula</label>
                        <input type="number" min="0" data-practica-matricula class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Hombres</label>
                        <input type="number" min="0" data-practica-hombres class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Mujeres</label>
                        <input type="number" min="0" data-practica-mujeres class="{{ $input }}">
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-3">
                    <button type="button" data-close-practica-modal class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Cancelar</button>
                    <button type="button" data-save-practica class="rounded-md bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">Guardar</button>
                </div>
            </div>
        </div>

        <div data-presupuesto-modal class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-2xl rounded-lg bg-white p-5 shadow-xl dark:bg-slate-900">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 data-presupuesto-modal-title class="text-base font-semibold text-slate-900 dark:text-slate-100">Editar presupuesto</h2>
                    <button type="button" data-close-presupuesto-modal class="rounded-md px-3 py-1 text-sm font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">Cerrar</button>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="sm:col-span-3">
                        <label class="{{ $label }}">Rubro</label>
                        <select data-presupuesto-rubro class="{{ $input }}">
                            <option value="">Seleccione...</option>
                        </select>
                    </div>
                    <div>
                        <label class="{{ $label }}">Cantidad</label>
                        <input type="number" min="0" step="0.01" data-presupuesto-cantidad class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Costo unitario</label>
                        <input type="number" min="0" step="0.01" data-presupuesto-costo-unitario class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Total</label>
                        <input data-presupuesto-total class="{{ $input }}" readonly>
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-3">
                    <button type="button" data-close-presupuesto-modal class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Cancelar</button>
                    <button type="button" data-save-presupuesto class="rounded-md bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">Guardar</button>
                </div>
            </div>
        </div>

        <div data-cronograma-modal class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-3xl rounded-lg bg-white p-5 shadow-xl dark:bg-slate-900">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 data-cronograma-modal-title class="text-base font-semibold text-slate-900 dark:text-slate-100">Agregar actividad</h2>
                    <button type="button" data-close-cronograma-modal class="rounded-md px-3 py-1 text-sm font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">Cerrar</button>
                </div>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="{{ $label }}">Actividad</label>
                        <input data-cronograma-actividad class="{{ $input }}">
                    </div>
                    <div class="md:col-span-2">
                        <label class="{{ $label }}">Producto</label>
                        <input data-cronograma-producto class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Fecha inicio</label>
                        <input type="date" data-cronograma-fecha-inicio class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Responsable</label>
                        <input data-cronograma-responsable class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Horas requeridas</label>
                        <input type="number" min="0" data-cronograma-horas class="{{ $input }}">
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-3">
                    <button type="button" data-close-cronograma-modal class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Cancelar</button>
                    <button type="button" data-save-cronograma class="rounded-md bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">Guardar</button>
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
            const storageKey = form.dataset.storageKey || 'enf-accion-form-draft';
            const clearDraftOnLoad = form.dataset.clearDraftOnLoad === '1';
            const approvedPrograms = @js($programasAprobadosData);
            const empleados = @js($empleadosModalData);
            const initialDraft = @js($initialDraft ?? []);
            const oldObjetivosEspecificos = @js(array_values((array) old('objetivos_especificos', [])));
            const approvedProgramSelect = form.querySelector('[data-approved-program-select]');
            const panels = Array.from(form.querySelectorAll('[data-step-panel]'));
            const previousButton = form.querySelector('[data-previous-step]');
            const nextButton = form.querySelector('[data-next-step]');
            const submitButton = form.querySelector('[data-submit-step]');
            const status = form.querySelector('[data-autosave-status]');
            const objetivosEspecificosList = form.querySelector('[data-objetivos-especificos-list]');
            const objetivoEspecificoTemplate = form.querySelector('[data-objetivo-especifico-template]');
            const addObjetivoEspecificoButton = form.querySelector('[data-add-objetivo-especifico]');
            const employeeModal = document.querySelector('[data-employee-modal]');
            const employeeSearch = document.querySelector('[data-employee-search]');
            const employeeResults = document.querySelector('[data-employee-results]');
            const consultorModal = document.querySelector('[data-consultor-modal]');
            const consultorModalTitle = document.querySelector('[data-consultor-modal-title]');
            const consultorExtraLabel = document.querySelector('[data-consultor-extra-label]');
            const consultorInputs = {
                nombre: document.querySelector('[data-consultor-nombre]'),
                extra: document.querySelector('[data-consultor-extra]'),
                correo: document.querySelector('[data-consultor-correo]'),
                horas: document.querySelector('[data-consultor-horas]'),
            };
            const participacionModal = document.querySelector('[data-participacion-modal]');
            const participacionModalTitle = document.querySelector('[data-participacion-modal-title]');
            const participacionInputs = {
                tipo: document.querySelector('[data-participacion-tipo]'),
                cantidad: document.querySelector('[data-participacion-cantidad]'),
                hombres: document.querySelector('[data-participacion-hombres]'),
                mujeres: document.querySelector('[data-participacion-mujeres]'),
            };
            const practicaModal = document.querySelector('[data-practica-modal]');
            const practicaModalTitle = document.querySelector('[data-practica-modal-title]');
            const practicaInputs = {
                codigo: document.querySelector('[data-practica-codigo]'),
                nombre: document.querySelector('[data-practica-nombre]'),
                periodo_academico_id: document.querySelector('[data-practica-periodo-id]'),
                periodo_academico: document.querySelector('[data-practica-periodo-texto]'),
                matricula_total: document.querySelector('[data-practica-matricula]'),
                hombres: document.querySelector('[data-practica-hombres]'),
                mujeres: document.querySelector('[data-practica-mujeres]'),
            };
            const presupuestoModal = document.querySelector('[data-presupuesto-modal]');
            const presupuestoModalTitle = document.querySelector('[data-presupuesto-modal-title]');
            const presupuestoInputs = {
                rubro: document.querySelector('[data-presupuesto-rubro]'),
                cantidad: document.querySelector('[data-presupuesto-cantidad]'),
                costo_unitario: document.querySelector('[data-presupuesto-costo-unitario]'),
                total: document.querySelector('[data-presupuesto-total]'),
            };
            const cronogramaModal = document.querySelector('[data-cronograma-modal]');
            const cronogramaModalTitle = document.querySelector('[data-cronograma-modal-title]');
            const cronogramaInputs = {
                actividad: document.querySelector('[data-cronograma-actividad]'),
                producto: document.querySelector('[data-cronograma-producto]'),
                fecha_inicio: document.querySelector('[data-cronograma-fecha-inicio]'),
                responsable: document.querySelector('[data-cronograma-responsable]'),
                horas_requeridas: document.querySelector('[data-cronograma-horas]'),
            };
            let selectedEmployeeId = null;
            let currentConsultorGroup = null;
            let currentConsultorExtraField = null;
            let currentParticipacionIndex = null;
            let currentPracticaIndex = null;
            let currentPresupuestoGroup = null;
            let currentPresupuestoIndex = null;
            let currentCronogramaIndex = null;
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
                    if (field.type === 'file' || field.name === '_token' || field.disabled) {
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

                    if (field.name.endsWith('[]')) {
                        data[field.name] = data[field.name] || [];
                        data[field.name].push(field.value);
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

            const renumberObjetivosEspecificos = () => {
                objetivosEspecificosList?.querySelectorAll('[data-objetivo-especifico-row]').forEach((row, index) => {
                    const number = row.querySelector('[data-objetivo-especifico-number]');
                    const removeButton = row.querySelector('[data-remove-objetivo-especifico]');

                    if (number) {
                        number.textContent = index + 1;
                    }

                    if (removeButton) {
                        removeButton.disabled = objetivosEspecificosList.querySelectorAll('[data-objetivo-especifico-row]').length <= 1;
                        removeButton.classList.toggle('opacity-50', removeButton.disabled);
                        removeButton.classList.toggle('cursor-not-allowed', removeButton.disabled);
                    }
                });
            };

            const addObjetivoEspecifico = (value = '') => {
                if (!objetivosEspecificosList || !objetivoEspecificoTemplate) {
                    return;
                }

                const fragment = objetivoEspecificoTemplate.content.cloneNode(true);
                const textarea = fragment.querySelector('textarea[name="objetivos_especificos[]"]');

                if (textarea) {
                    textarea.value = value ?? '';
                }

                objetivosEspecificosList.appendChild(fragment);
                renumberObjetivosEspecificos();
            };

            const resetObjetivosEspecificos = (values = []) => {
                if (!objetivosEspecificosList) {
                    return;
                }

                const normalizedValues = Array.isArray(values)
                    ? values.map((value) => value ?? '').filter((value) => String(value).trim() !== '')
                    : [];

                objetivosEspecificosList.innerHTML = '';
                (normalizedValues.length ? normalizedValues : ['']).forEach((value) => addObjetivoEspecifico(value));
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

                if (stepNumber === 9) {
                    return Array.from(panel.querySelectorAll('[data-cronograma-row]')).some((row) => {
                        return Array.from(row.querySelectorAll('input[name]')).some((field) => String(field.value ?? '').trim() !== '');
                    });
                }

                const alternativeGroups = [];
                const alternativeFieldNames = new Set(alternativeGroups.flat());
                const groupedChoices = new Map();
                const fields = Array.from(panel.querySelectorAll('input[name], select[name], textarea[name]'))
                    .filter((field) => {
                        if (field.disabled || field.type === 'hidden' || field.name === '_token') {
                            return false;
                        }

                        if (field.closest('[data-equipo-docente-fields]')
                            || field.closest('[data-consultor-fields]')
                            || field.closest('[data-participacion-fields]')
                            || field.closest('[data-practicas-fields]')
                            || field.closest('[data-presupuesto-fields]')
                            || field.closest('[data-cronograma-fields]')) {
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

            const setRegisteredEmployeeField = (group, fieldName, value) => {
                const field = form.querySelector(fieldSelector(`${group}[${fieldName}]`));

                if (!field) {
                    return;
                }

                field.value = value || '';
            };

            const updateRegisteredEmployeeDetails = (select) => {
                const group = select?.dataset.registeredEmployeeSelect;
                const option = select?.selectedOptions?.[0];

                if (!group || !option) {
                    return;
                }

                const hasEmployee = Boolean(option.value);
                setRegisteredEmployeeField(group, 'nombre_completo', hasEmployee ? option.dataset.nombre : '');
                setRegisteredEmployeeField(group, 'numero_empleado', hasEmployee ? option.dataset.numeroEmpleado : '');
                setRegisteredEmployeeField(group, 'correo', hasEmployee ? option.dataset.correo : '');
                setRegisteredEmployeeField(group, 'celular', hasEmployee ? option.dataset.celular : '');
                setRegisteredEmployeeField(group, 'categoria', hasEmployee ? option.dataset.categoria : '');
                setRegisteredEmployeeField(group, 'departamento', hasEmployee ? option.dataset.departamento : '');
            };

            const updateRegisteredEmployeesDetails = () => {
                form.querySelectorAll('[data-registered-employee-select]').forEach(updateRegisteredEmployeeDetails);
            };

            const applyApprovedProgram = (programId) => {
                const program = approvedPrograms.find((item) => String(item.id) === String(programId));

                if (!program) {
                    return;
                }

                Object.entries(program.fields || {}).forEach(([name, value]) => setFieldValue(name, value));
                save();
            };

            const showModal = (modal) => {
                modal?.classList.remove('hidden');
                modal?.classList.add('flex');
            };

            const hideModal = (modal) => {
                modal?.classList.add('hidden');
                modal?.classList.remove('flex');
            };

            const rowField = (row, fieldName) => row?.querySelector(`[name$="[${fieldName}]"]`);

            const rowValue = (row, fieldName) => rowField(row, fieldName)?.value?.trim() || '';

            const rowSelectText = (row, fieldName) => {
                const field = rowField(row, fieldName);
                const selected = field?.selectedOptions?.[0]?.textContent?.trim();

                return selected && field.value ? selected : '';
            };

            const escapeHtml = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

            const setRowValues = (row, values) => {
                Object.entries(values).forEach(([fieldName, value]) => {
                    const field = rowField(row, fieldName);

                    if (field) {
                        field.value = value || '';
                        field.dispatchEvent(new Event('input', { bubbles: true }));
                        field.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
            };

            const clearRow = (row) => setRowValues(row, {
                nombre_completo: '',
                numero_empleado: '',
                correo: '',
                categoria: '',
                departamento: '',
                jornada_laboral: '',
                profesion: '',
                nacionalidad: '',
                horas_contratadas: '',
                codigo: '',
                nombre: '',
                periodo_academico_id: '',
                periodo_academico: '',
                matricula_total: '',
                hombres: '',
                mujeres: '',
                actividad: '',
                producto: '',
                fecha_inicio: '',
                responsable: '',
                horas_requeridas: '',
            });

            const clearPresupuestoRow = (row) => setRowValues(row, {
                rubro: '',
                cantidad: '',
                costo_unitario: '',
            });

            const nextEmptyRow = (selector) => Array.from(form.querySelectorAll(selector))
                .find((row) => !rowValue(row, 'nombre_completo'));

            const renderEquipoDocente = () => {
                const target = form.querySelector('[data-equipo-docente-list]');
                const rows = Array.from(form.querySelectorAll('[data-equipo-docente-row]'))
                    .map((row, index) => ({ row, index }))
                    .filter(({ row }) => rowValue(row, 'nombre_completo'));

                if (!target) {
                    return;
                }

                if (rows.length === 0) {
                    target.innerHTML = '<tr><td colspan="7" class="px-3 py-4 text-center text-slate-500">Sin docentes agregados.</td></tr>';
                    return;
                }

                target.innerHTML = rows.map(({ row, index }) => `
                    <tr>
                        <td class="px-3 py-2">${escapeHtml(rowValue(row, 'nombre_completo'))}</td>
                        <td class="px-3 py-2">${escapeHtml(rowValue(row, 'numero_empleado') || 'Sin dato')}</td>
                        <td class="px-3 py-2">${escapeHtml(rowValue(row, 'correo') || 'Sin dato')}</td>
                        <td class="px-3 py-2">${escapeHtml(rowValue(row, 'categoria') || 'Sin dato')}</td>
                        <td class="px-3 py-2">${escapeHtml(rowValue(row, 'departamento') || 'Sin dato')}</td>
                        <td class="px-3 py-2">${escapeHtml(rowValue(row, 'jornada_laboral') || 'Sin dato')}</td>
                        <td class="px-3 py-2 text-right">
                            <button type="button" data-remove-equipo-docente="${index}" class="text-sm font-semibold text-red-600 hover:text-red-800">Quitar</button>
                        </td>
                    </tr>
                `).join('');
            };

            const renderConsultores = (group) => {
                const target = form.querySelector(`[data-consultor-list="${group}"]`);
                const extraField = target?.dataset.extraField;
                const rows = Array.from(form.querySelectorAll(`[data-consultor-row="${group}"]`))
                    .map((row, index) => ({ row, index }))
                    .filter(({ row }) => rowValue(row, 'nombre_completo'));

                if (!target || !extraField) {
                    return;
                }

                if (rows.length === 0) {
                    target.innerHTML = '<tr><td colspan="5" class="px-3 py-4 text-center text-slate-500">Sin consultores agregados.</td></tr>';
                    return;
                }

                target.innerHTML = rows.map(({ row, index }) => `
                    <tr>
                        <td class="px-3 py-2">${escapeHtml(rowValue(row, 'nombre_completo'))}</td>
                        <td class="px-3 py-2">${escapeHtml(rowValue(row, extraField) || 'Sin dato')}</td>
                        <td class="px-3 py-2">${escapeHtml(rowValue(row, 'correo') || 'Sin dato')}</td>
                        <td class="px-3 py-2">${escapeHtml(rowValue(row, 'horas_contratadas') || '0')}</td>
                        <td class="px-3 py-2 text-right">
                            <button type="button" data-remove-consultor="${group}" data-index="${index}" class="text-sm font-semibold text-red-600 hover:text-red-800">Quitar</button>
                        </td>
                    </tr>
                `).join('');
            };

            const renderParticipacion = () => {
                const target = form.querySelector('[data-participacion-list]');
                const rows = Array.from(form.querySelectorAll('[data-participacion-row]'))
                    .map((row, index) => ({ row, index }));

                if (!target) {
                    return;
                }

                target.innerHTML = rows.map(({ row, index }) => `
                    <tr>
                        <td class="px-3 py-2 font-medium text-slate-700 dark:text-slate-200">${escapeHtml(rowValue(row, 'tipo_participacion'))}</td>
                        <td class="px-3 py-2">${escapeHtml(rowValue(row, 'cantidad') || '0')}</td>
                        <td class="px-3 py-2">${escapeHtml(rowValue(row, 'hombres') || '0')}</td>
                        <td class="px-3 py-2">${escapeHtml(rowValue(row, 'mujeres') || '0')}</td>
                        <td class="px-3 py-2 text-right">
                            <button type="button" data-edit-participacion="${index}" class="text-sm font-semibold text-blue-700 hover:text-blue-900">Editar</button>
                        </td>
                    </tr>
                `).join('');
            };

            const practicaHasValue = (row) => [
                'codigo',
                'nombre',
                'periodo_academico_id',
                'periodo_academico',
                'matricula_total',
                'hombres',
                'mujeres',
            ].some((fieldName) => rowValue(row, fieldName));

            const renderPracticas = () => {
                const target = form.querySelector('[data-practicas-list]');
                const rows = Array.from(form.querySelectorAll('[data-practica-row]'))
                    .map((row, index) => ({ row, index }))
                    .filter(({ row }) => practicaHasValue(row));

                if (!target) {
                    return;
                }

                if (rows.length === 0) {
                    target.innerHTML = '<tr><td colspan="7" class="px-3 py-4 text-center text-slate-500">Sin prácticas agregadas.</td></tr>';
                    return;
                }

                target.innerHTML = rows.map(({ row, index }) => `
                    <tr>
                        <td class="px-3 py-2">${escapeHtml(rowValue(row, 'codigo') || 'Sin dato')}</td>
                        <td class="px-3 py-2">${escapeHtml(rowValue(row, 'nombre') || 'Sin dato')}</td>
                        <td class="px-3 py-2">${escapeHtml(rowSelectText(row, 'periodo_academico_id') || rowValue(row, 'periodo_academico') || 'Sin dato')}</td>
                        <td class="px-3 py-2">${escapeHtml(rowValue(row, 'matricula_total') || '0')}</td>
                        <td class="px-3 py-2">${escapeHtml(rowValue(row, 'hombres') || '0')}</td>
                        <td class="px-3 py-2">${escapeHtml(rowValue(row, 'mujeres') || '0')}</td>
                        <td class="px-3 py-2 text-right">
                            <button type="button" data-edit-practica="${index}" class="text-sm font-semibold text-blue-700 hover:text-blue-900">Editar</button>
                            <button type="button" data-remove-practica="${index}" class="ml-3 text-sm font-semibold text-red-600 hover:text-red-800">Quitar</button>
                        </td>
                    </tr>
                `).join('');
            };

            const money = (value) => {
                const number = Number(value || 0);

                return Number.isFinite(number)
                    ? number.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                    : '0.00';
            };

            const renderPresupuesto = (group) => {
                const target = form.querySelector(`[data-presupuesto-list="${group}"]`);
                const disabled = group === 'presupuesto_ingresos' && !form.querySelector('[data-genera-ingresos]')?.checked;
                const rows = Array.from(form.querySelectorAll(`[data-presupuesto-row="${group}"]`))
                    .map((row, index) => ({ row, index }))
                    .filter(({ row }) => rowValue(row, 'rubro'));

                if (!target) {
                    return;
                }

                if (disabled) {
                    target.innerHTML = '<tr><td colspan="5" class="px-3 py-4 text-center text-slate-500">Ingresos desactivados.</td></tr>';
                    return;
                }

                if (rows.length === 0) {
                    target.innerHTML = '<tr><td colspan="5" class="px-3 py-4 text-center text-slate-500">Sin rubros agregados.</td></tr>';
                    return;
                }

                target.innerHTML = rows.map(({ row, index }) => {
                    const cantidad = Number(rowValue(row, 'cantidad') || 0);
                    const costoUnitario = Number(rowValue(row, 'costo_unitario') || 0);
                    const total = cantidad * costoUnitario;

                    return `
                        <tr>
                            <td class="px-3 py-2 font-medium text-slate-700 dark:text-slate-200">${escapeHtml(rowValue(row, 'rubro'))}</td>
                            <td class="px-3 py-2">${escapeHtml(rowValue(row, 'cantidad') || '0')}</td>
                            <td class="px-3 py-2">${money(costoUnitario)}</td>
                            <td class="px-3 py-2">${money(total)}</td>
                            <td class="px-3 py-2 text-right">
                                <button type="button" data-edit-presupuesto="${group}" data-index="${index}" class="text-sm font-semibold text-blue-700 hover:text-blue-900">Editar</button>
                                <button type="button" data-remove-presupuesto="${group}" data-index="${index}" class="ml-3 text-sm font-semibold text-red-600 hover:text-red-800">Quitar</button>
                            </td>
                        </tr>
                    `;
                }).join('');
            };

            const updateIngresosState = () => {
                const enabled = Boolean(form.querySelector('[data-genera-ingresos]')?.checked);
                const card = form.querySelector('[data-presupuesto-card="presupuesto_ingresos"]');

                card?.querySelectorAll('input[name^="presupuesto_ingresos"]').forEach((input) => {
                    input.disabled = !enabled;
                });
                card?.querySelectorAll('[data-open-presupuesto-modal]').forEach((button) => {
                    button.disabled = !enabled;
                });
                card?.classList.toggle('opacity-60', !enabled);
                renderPresupuesto('presupuesto_ingresos');
            };

            const cronogramaHasValue = (row) => [
                'actividad',
                'producto',
                'fecha_inicio',
                'responsable',
                'horas_requeridas',
            ].some((fieldName) => rowValue(row, fieldName));

            const renderCronograma = () => {
                const target = form.querySelector('[data-cronograma-list]');
                const rows = Array.from(form.querySelectorAll('[data-cronograma-row]'))
                    .map((row, index) => ({ row, index }))
                    .filter(({ row }) => cronogramaHasValue(row));

                if (!target) {
                    return;
                }

                if (rows.length === 0) {
                    target.innerHTML = '<tr><td colspan="6" class="px-3 py-4 text-center text-slate-500">Sin actividades agregadas.</td></tr>';
                    return;
                }

                target.innerHTML = rows.map(({ row, index }) => `
                    <tr>
                        <td class="px-3 py-2 font-medium text-slate-700 dark:text-slate-200">${escapeHtml(rowValue(row, 'actividad'))}</td>
                        <td class="px-3 py-2">${escapeHtml(rowValue(row, 'producto') || 'Sin dato')}</td>
                        <td class="px-3 py-2">${escapeHtml(rowValue(row, 'fecha_inicio') || 'Sin fecha')}</td>
                        <td class="px-3 py-2">${escapeHtml(rowValue(row, 'responsable') || 'Sin dato')}</td>
                        <td class="px-3 py-2">${escapeHtml(rowValue(row, 'horas_requeridas') || '0')}</td>
                        <td class="px-3 py-2 text-right">
                            <button type="button" data-edit-cronograma="${index}" class="text-sm font-semibold text-blue-700 hover:text-blue-900">Editar</button>
                            <button type="button" data-remove-cronograma="${index}" class="ml-3 text-sm font-semibold text-red-600 hover:text-red-800">Quitar</button>
                        </td>
                    </tr>
                `).join('');
            };

            const renderDynamicLists = () => {
                renderEquipoDocente();
                renderConsultores('consultores_nacionales');
                renderConsultores('consultores_internacionales');
                renderParticipacion();
                renderPracticas();
                updateIngresosState();
                renderPresupuesto('presupuesto_egresos');
                renderCronograma();
            };

            const renderEmployeeResults = () => {
                const term = (employeeSearch?.value || '').toLowerCase().trim();
                const matches = empleados
                    .filter((empleado) => {
                        if (!term) {
                            return true;
                        }

                        return [
                            empleado.nombre_completo,
                            empleado.numero_empleado,
                            empleado.correo,
                            empleado.categoria,
                            empleado.departamento,
                            empleado.jornada_laboral,
                        ].filter(Boolean).some((value) => String(value).toLowerCase().includes(term));
                    })
                    .slice(0, 30);

                if (!employeeResults) {
                    return;
                }

                if (matches.length === 0) {
                    employeeResults.innerHTML = '<p class="px-3 py-4 text-center text-sm text-slate-500">Sin resultados.</p>';
                    return;
                }

                employeeResults.innerHTML = matches.map((empleado) => `
                    <button type="button" data-select-employee="${escapeHtml(empleado.id)}" class="block w-full px-3 py-3 text-left hover:bg-slate-50 dark:hover:bg-slate-800">
                        <span class="block text-sm font-semibold text-slate-900 dark:text-slate-100">${escapeHtml(empleado.nombre_completo || 'Sin nombre')}</span>
                        <span class="block text-xs text-slate-500">${escapeHtml(empleado.numero_empleado || 'Sin número')} · ${escapeHtml(empleado.correo || 'Sin correo')} · ${escapeHtml(empleado.departamento || 'Sin departamento')} · ${escapeHtml(empleado.jornada_laboral || 'Sin jornada')}</span>
                    </button>
                `).join('');
            };

            const addEmployeeToEquipoDocente = (employeeId) => {
                const empleado = empleados.find((item) => String(item.id) === String(employeeId));
                const row = nextEmptyRow('[data-equipo-docente-row]');

                if (!empleado || !row) {
                    return;
                }

                setRowValues(row, {
                    nombre_completo: empleado.nombre_completo,
                    numero_empleado: empleado.numero_empleado,
                    correo: empleado.correo,
                    categoria: empleado.categoria,
                    departamento: empleado.departamento,
                    jornada_laboral: empleado.jornada_laboral,
                });

                selectedEmployeeId = null;
                if (employeeSearch) {
                    employeeSearch.value = '';
                }
                renderDynamicLists();
                hideModal(employeeModal);
                save();
                render();
            };

            const openConsultorModal = (group, title, extraLabel, extraField) => {
                currentConsultorGroup = group;
                currentConsultorExtraField = extraField;
                if (consultorModalTitle) {
                    consultorModalTitle.textContent = `Agregar ${title.toLowerCase()}`;
                }
                if (consultorExtraLabel) {
                    consultorExtraLabel.textContent = extraLabel;
                }
                Object.values(consultorInputs).forEach((input) => {
                    if (input) {
                        input.value = '';
                    }
                });
                showModal(consultorModal);
                consultorInputs.nombre?.focus();
            };

            const addConsultor = () => {
                const row = currentConsultorGroup
                    ? nextEmptyRow(`[data-consultor-row="${currentConsultorGroup}"]`)
                    : null;

                if (!row || !currentConsultorExtraField || !consultorInputs.nombre?.value.trim()) {
                    return;
                }

                setRowValues(row, {
                    nombre_completo: consultorInputs.nombre.value,
                    [currentConsultorExtraField]: consultorInputs.extra?.value || '',
                    correo: consultorInputs.correo?.value || '',
                    horas_contratadas: consultorInputs.horas?.value || '',
                });

                renderDynamicLists();
                hideModal(consultorModal);
                save();
                render();
            };

            const numericValue = (value) => {
                const number = Number(value || 0);

                return Number.isFinite(number) ? number : 0;
            };

            const updateParticipacionTotal = () => {
                const total = numericValue(participacionInputs.hombres?.value) + numericValue(participacionInputs.mujeres?.value);

                if (participacionInputs.cantidad) {
                    participacionInputs.cantidad.value = String(total);
                }
            };

            const openParticipacionModal = (index) => {
                const row = form.querySelector(`[data-participacion-row="${index}"]`);

                if (!row) {
                    return;
                }

                currentParticipacionIndex = index;
                if (participacionModalTitle) {
                    participacionModalTitle.textContent = `Editar ${rowValue(row, 'tipo_participacion').toLowerCase()}`;
                }
                participacionInputs.tipo.value = rowValue(row, 'tipo_participacion');
                participacionInputs.cantidad.value = rowValue(row, 'cantidad');
                participacionInputs.hombres.value = rowValue(row, 'hombres');
                participacionInputs.mujeres.value = rowValue(row, 'mujeres');
                updateParticipacionTotal();
                showModal(participacionModal);
                participacionInputs.hombres?.focus();
            };

            const saveParticipacion = () => {
                const row = currentParticipacionIndex !== null
                    ? form.querySelector(`[data-participacion-row="${currentParticipacionIndex}"]`)
                    : null;

                if (!row) {
                    return;
                }

                setRowValues(row, {
                    cantidad: String(numericValue(participacionInputs.hombres?.value) + numericValue(participacionInputs.mujeres?.value)),
                    hombres: participacionInputs.hombres?.value || '',
                    mujeres: participacionInputs.mujeres?.value || '',
                });

                hideModal(participacionModal);
                currentParticipacionIndex = null;
                renderDynamicLists();
                save();
                render();
            };

            const nextEmptyPracticaRow = () => Array.from(form.querySelectorAll('[data-practica-row]'))
                .find((row) => !practicaHasValue(row));

            const selectedOptionText = (select) => {
                const option = select?.selectedOptions?.[0];
                return option?.value ? option.textContent.trim() : '';
            };

            const updatePracticaPeriodoTexto = () => {
                if (practicaInputs.periodo_academico) {
                    practicaInputs.periodo_academico.value = selectedOptionText(practicaInputs.periodo_academico_id);
                }
            };

            const openPracticaModal = (index = null) => {
                const row = index === null
                    ? nextEmptyPracticaRow()
                    : form.querySelector(`[data-practica-row="${index}"]`);

                if (!row) {
                    return;
                }

                currentPracticaIndex = Number(row.dataset.practicaRow);
                if (practicaModalTitle) {
                    practicaModalTitle.textContent = practicaHasValue(row) ? 'Editar práctica' : 'Agregar práctica';
                }

                Object.entries(practicaInputs).forEach(([fieldName, input]) => {
                    if (input) {
                        input.value = rowValue(row, fieldName);
                    }
                });

                if (practicaInputs.periodo_academico_id?.value && !practicaInputs.periodo_academico?.value) {
                    updatePracticaPeriodoTexto();
                }

                showModal(practicaModal);
                practicaInputs.codigo?.focus();
            };

            const savePractica = () => {
                const row = currentPracticaIndex !== null
                    ? form.querySelector(`[data-practica-row="${currentPracticaIndex}"]`)
                    : null;

                if (!row) {
                    return;
                }

                updatePracticaPeriodoTexto();

                setRowValues(row, Object.fromEntries(
                    Object.entries(practicaInputs).map(([fieldName, input]) => [fieldName, input?.value || '']),
                ));

                hideModal(practicaModal);
                currentPracticaIndex = null;
                renderDynamicLists();
                save();
                render();
            };

            const updatePresupuestoTotal = () => {
                const cantidad = Number(presupuestoInputs.cantidad?.value || 0);
                const costoUnitario = Number(presupuestoInputs.costo_unitario?.value || 0);

                if (presupuestoInputs.total) {
                    presupuestoInputs.total.value = money(cantidad * costoUnitario);
                }
            };

            const nextEmptyPresupuestoRow = (group) => Array.from(form.querySelectorAll(`[data-presupuesto-row="${group}"]`))
                .find((row) => !rowValue(row, 'rubro'));

            const presupuestoRubros = (group) => {
                const source = form.querySelector(`[data-presupuesto-fields="${group}"]`);

                try {
                    return JSON.parse(source?.dataset.rubros || '[]');
                } catch (error) {
                    return [];
                }
            };

            const setPresupuestoRubroOptions = (group, selectedValue = '') => {
                if (!presupuestoInputs.rubro) {
                    return;
                }

                const selected = selectedValue || '';
                const rubros = presupuestoRubros(group);
                presupuestoInputs.rubro.innerHTML = '';

                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = 'Seleccione...';
                presupuestoInputs.rubro.appendChild(placeholder);

                rubros.forEach((rubro) => {
                    const option = document.createElement('option');
                    option.value = rubro;
                    option.textContent = rubro;
                    presupuestoInputs.rubro.appendChild(option);
                });

                if (selected && !rubros.includes(selected)) {
                    const option = document.createElement('option');
                    option.value = selected;
                    option.textContent = selected;
                    presupuestoInputs.rubro.appendChild(option);
                }

                presupuestoInputs.rubro.value = selected;
            };

            const openPresupuestoModal = (group, index = null) => {
                const row = index === null
                    ? nextEmptyPresupuestoRow(group)
                    : form.querySelector(`[data-presupuesto-row="${group}"][data-index="${index}"]`);
                const title = form.querySelector(`[data-presupuesto-fields="${group}"]`)?.dataset.title || 'Presupuesto';

                if (!row) {
                    return;
                }

                currentPresupuestoGroup = group;
                currentPresupuestoIndex = row.dataset.index;
                if (presupuestoModalTitle) {
                    presupuestoModalTitle.textContent = `${index === null ? 'Agregar' : 'Editar'} ${title.toLowerCase()}`;
                }
                setPresupuestoRubroOptions(group, index === null ? '' : rowValue(row, 'rubro'));
                presupuestoInputs.cantidad.value = index === null ? '' : rowValue(row, 'cantidad');
                presupuestoInputs.costo_unitario.value = index === null ? '' : rowValue(row, 'costo_unitario');
                updatePresupuestoTotal();
                showModal(presupuestoModal);
                presupuestoInputs.rubro?.focus();
            };

            const savePresupuesto = () => {
                const row = currentPresupuestoGroup !== null && currentPresupuestoIndex !== null
                    ? form.querySelector(`[data-presupuesto-row="${currentPresupuestoGroup}"][data-index="${currentPresupuestoIndex}"]`)
                    : null;

                if (!row) {
                    return;
                }

                if (!presupuestoInputs.rubro?.value) {
                    presupuestoInputs.rubro?.focus();
                    return;
                }

                setRowValues(row, {
                    rubro: presupuestoInputs.rubro?.value || '',
                    cantidad: presupuestoInputs.cantidad?.value || '',
                    costo_unitario: presupuestoInputs.costo_unitario?.value || '',
                });

                hideModal(presupuestoModal);
                currentPresupuestoGroup = null;
                currentPresupuestoIndex = null;
                renderDynamicLists();
                save();
                render();
            };

            const nextEmptyCronogramaRow = () => Array.from(form.querySelectorAll('[data-cronograma-row]'))
                .find((row) => !cronogramaHasValue(row));

            const openCronogramaModal = (index = null) => {
                const row = index === null
                    ? nextEmptyCronogramaRow()
                    : form.querySelector(`[data-cronograma-row="${index}"]`);

                if (!row) {
                    return;
                }

                currentCronogramaIndex = Number(row.dataset.cronogramaRow);
                if (cronogramaModalTitle) {
                    cronogramaModalTitle.textContent = cronogramaHasValue(row) ? 'Editar actividad' : 'Agregar actividad';
                }

                Object.entries(cronogramaInputs).forEach(([fieldName, input]) => {
                    if (input) {
                        input.value = rowValue(row, fieldName);
                    }
                });

                showModal(cronogramaModal);
                cronogramaInputs.actividad?.focus();
            };

            const saveCronograma = () => {
                const row = currentCronogramaIndex !== null
                    ? form.querySelector(`[data-cronograma-row="${currentCronogramaIndex}"]`)
                    : null;

                if (!row) {
                    return;
                }

                setRowValues(row, Object.fromEntries(
                    Object.entries(cronogramaInputs).map(([fieldName, input]) => [fieldName, input?.value || '']),
                ));

                hideModal(cronogramaModal);
                currentCronogramaIndex = null;
                renderDynamicLists();
                save();
                render();
            };

            const restore = () => {
                const stored = window.localStorage.getItem(storageKey);
                let data = initialDraft || {};

                if (stored) {
                    try {
                        data = { ...(initialDraft || {}), ...JSON.parse(stored) };
                    } catch (error) {
                        data = initialDraft || {};
                    }
                }

                resetObjetivosEspecificos(Array.isArray(data['objetivos_especificos[]'])
                    ? data['objetivos_especificos[]']
                    : oldObjetivosEspecificos);

                form.querySelectorAll('input[name], select[name], textarea[name]').forEach((field) => {
                    if (field.type === 'file' || field.name === '_token' || !(field.name in data)) {
                        return;
                    }

                    const value = data[field.name];

                    if (field.type === 'checkbox') {
                        field.checked = Array.isArray(value) ? value.includes(field.value) : String(value) === field.value;
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

                    if (field.name.endsWith('[]') && Array.isArray(value)) {
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

            const updateSupervisorDocumentUploadState = () => {
                form.querySelectorAll('[data-doc-upload-card]').forEach((card) => {
                    const check = card.querySelector('[data-doc-upload-check]');
                    const radios = Array.from(card.querySelectorAll('[data-doc-upload-radio]'));
                    const file = card.querySelector('[data-doc-upload-file]');
                    const selectedRadio = radios.find((radio) => radio.checked);
                    const enabled = Boolean(check?.checked);
                    const uploadEnabled = enabled && selectedRadio?.value === 'Si';

                    radios.forEach((radio) => {
                        radio.disabled = !enabled;
                    });

                    if (file) {
                        file.disabled = !uploadEnabled;
                        file.required = uploadEnabled;

                        if (!uploadEnabled) {
                            file.value = '';
                        }
                    }
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

            const updateMetasContribuyeState = () => {
                const selectedOdsIds = new Set(
                    Array.from(form.querySelectorAll('[data-ods-checkbox]:checked')).map((field) => field.value),
                );
                const hasSelectedOds = selectedOdsIds.size > 0;
                const emptyState = form.querySelector('[data-meta-contribuye-empty]');
                let visibleCount = 0;

                form.querySelectorAll('[data-meta-contribuye-option]').forEach((option) => {
                    const checkbox = option.querySelector('input[type="checkbox"]');
                    const shouldShow = hasSelectedOds && selectedOdsIds.has(option.dataset.odsId);

                    option.classList.toggle('hidden', !shouldShow);

                    if (checkbox) {
                        checkbox.disabled = !shouldShow;

                        if (!shouldShow) {
                            checkbox.checked = false;
                        }
                    }

                    if (shouldShow) {
                        visibleCount += 1;
                    }
                });

                emptyState?.classList.toggle('hidden', visibleCount > 0);
            };

            restore();
            updateRegisteredEmployeesDetails();
            updateSupervisorDocumentUploadState();
            updateContraparteState();
            updateMetasContribuyeState();
            renderDynamicLists();
            render();

            form.querySelectorAll('[data-step-button]').forEach((button) => {
                button.addEventListener('click', () => goTo(button.dataset.stepButton));
            });

            previousButton?.addEventListener('click', () => goTo(step - 1));
            nextButton?.addEventListener('click', () => goTo(step + 1));
            addObjetivoEspecificoButton?.addEventListener('click', () => {
                addObjetivoEspecifico();
                save();
                render();
            });
            form.addEventListener('input', () => {
                updateRegisteredEmployeesDetails();
                updateSupervisorDocumentUploadState();
                updateContraparteState();
                updateMetasContribuyeState();
                renderDynamicLists();
                render();
                debouncedSave();
            });
            form.addEventListener('change', () => {
                updateRegisteredEmployeesDetails();
                updateSupervisorDocumentUploadState();
                updateContraparteState();
                updateMetasContribuyeState();
                renderDynamicLists();
                render();
                save();
            });
            approvedProgramSelect?.addEventListener('change', (event) => applyApprovedProgram(event.target.value));
            form.addEventListener('click', (event) => {
                const removeObjetivoEspecifico = event.target.closest('[data-remove-objetivo-especifico]');
                const removeDocente = event.target.closest('[data-remove-equipo-docente]');
                const removeConsultor = event.target.closest('[data-remove-consultor]');
                const editParticipacion = event.target.closest('[data-edit-participacion]');
                const editPractica = event.target.closest('[data-edit-practica]');
                const removePractica = event.target.closest('[data-remove-practica]');
                const editPresupuesto = event.target.closest('[data-edit-presupuesto]');
                const removePresupuesto = event.target.closest('[data-remove-presupuesto]');
                const editCronograma = event.target.closest('[data-edit-cronograma]');
                const removeCronograma = event.target.closest('[data-remove-cronograma]');

                if (removeObjetivoEspecifico && !removeObjetivoEspecifico.disabled) {
                    removeObjetivoEspecifico.closest('[data-objetivo-especifico-row]')?.remove();
                    renumberObjetivosEspecificos();
                    save();
                    render();
                    return;
                }

                if (removeDocente) {
                    clearRow(form.querySelector(`[data-equipo-docente-row="${removeDocente.dataset.removeEquipoDocente}"]`));
                    renderDynamicLists();
                    save();
                    render();
                    return;
                }

                if (removeConsultor) {
                    clearRow(form.querySelector(`[data-consultor-row="${removeConsultor.dataset.removeConsultor}"][data-index="${removeConsultor.dataset.index}"]`));
                    renderDynamicLists();
                    save();
                    render();
                    return;
                }

                if (editParticipacion) {
                    openParticipacionModal(editParticipacion.dataset.editParticipacion);
                    return;
                }

                if (editPractica) {
                    openPracticaModal(editPractica.dataset.editPractica);
                    return;
                }

                if (removePractica) {
                    clearRow(form.querySelector(`[data-practica-row="${removePractica.dataset.removePractica}"]`));
                    renderDynamicLists();
                    save();
                    render();
                    return;
                }

                if (editPresupuesto) {
                    openPresupuestoModal(editPresupuesto.dataset.editPresupuesto, editPresupuesto.dataset.index);
                    return;
                }

                if (removePresupuesto) {
                    clearPresupuestoRow(form.querySelector(`[data-presupuesto-row="${removePresupuesto.dataset.removePresupuesto}"][data-index="${removePresupuesto.dataset.index}"]`));
                    renderDynamicLists();
                    save();
                    render();
                    return;
                }

                if (editCronograma) {
                    openCronogramaModal(editCronograma.dataset.editCronograma);
                    return;
                }

                if (removeCronograma) {
                    clearRow(form.querySelector(`[data-cronograma-row="${removeCronograma.dataset.removeCronograma}"]`));
                    renderDynamicLists();
                    save();
                    render();
                }
            });
            document.querySelectorAll('[data-open-employee-modal]').forEach((button) => {
                button.addEventListener('click', () => {
                    selectedEmployeeId = null;
                    renderEmployeeResults();
                    showModal(employeeModal);
                    employeeSearch?.focus();
                });
            });
            document.querySelectorAll('[data-close-employee-modal]').forEach((button) => {
                button.addEventListener('click', () => hideModal(employeeModal));
            });
            employeeSearch?.addEventListener('input', renderEmployeeResults);
            employeeResults?.addEventListener('click', (event) => {
                const button = event.target.closest('[data-select-employee]');

                if (button) {
                    selectedEmployeeId = button.dataset.selectEmployee;
                    addEmployeeToEquipoDocente(selectedEmployeeId);
                }
            });
            document.querySelectorAll('[data-open-consultor-modal]').forEach((button) => {
                button.addEventListener('click', () => openConsultorModal(
                    button.dataset.openConsultorModal,
                    button.parentElement?.querySelector('h3')?.textContent || 'consultor',
                    button.dataset.extraLabel,
                    button.dataset.extraField,
                ));
            });
            document.querySelectorAll('[data-close-consultor-modal]').forEach((button) => {
                button.addEventListener('click', () => hideModal(consultorModal));
            });
            document.querySelector('[data-add-consultor]')?.addEventListener('click', addConsultor);
            document.querySelectorAll('[data-close-participacion-modal]').forEach((button) => {
                button.addEventListener('click', () => hideModal(participacionModal));
            });
            participacionInputs.hombres?.addEventListener('input', updateParticipacionTotal);
            participacionInputs.mujeres?.addEventListener('input', updateParticipacionTotal);
            document.querySelector('[data-save-participacion]')?.addEventListener('click', saveParticipacion);
            document.querySelector('[data-open-practica-modal]')?.addEventListener('click', () => openPracticaModal());
            document.querySelectorAll('[data-close-practica-modal]').forEach((button) => {
                button.addEventListener('click', () => hideModal(practicaModal));
            });
            practicaInputs.periodo_academico_id?.addEventListener('change', updatePracticaPeriodoTexto);
            document.querySelector('[data-save-practica]')?.addEventListener('click', savePractica);
            document.querySelectorAll('[data-close-presupuesto-modal]').forEach((button) => {
                button.addEventListener('click', () => hideModal(presupuestoModal));
            });
            document.querySelectorAll('[data-open-presupuesto-modal]').forEach((button) => {
                button.addEventListener('click', () => openPresupuestoModal(button.dataset.openPresupuestoModal));
            });
            document.querySelector('[data-genera-ingresos]')?.addEventListener('change', () => {
                updateIngresosState();
                save();
                render();
            });
            presupuestoInputs.cantidad?.addEventListener('input', updatePresupuestoTotal);
            presupuestoInputs.costo_unitario?.addEventListener('input', updatePresupuestoTotal);
            document.querySelector('[data-save-presupuesto]')?.addEventListener('click', savePresupuesto);
            document.querySelector('[data-open-cronograma-modal]')?.addEventListener('click', () => openCronogramaModal());
            document.querySelectorAll('[data-close-cronograma-modal]').forEach((button) => {
                button.addEventListener('click', () => hideModal(cronogramaModal));
            });
            document.querySelector('[data-save-cronograma]')?.addEventListener('click', saveCronograma);
            form.addEventListener('submit', save);
            window.addEventListener('beforeunload', save);
            window.addEventListener('pagehide', save);
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'hidden') {
                    save();
                }
            });
        })();
    </script>
@endsection
