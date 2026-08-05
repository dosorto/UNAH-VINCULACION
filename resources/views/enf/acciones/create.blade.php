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
    $form018CatalogLabel = function (string $nombre): string {
        $key = \Illuminate\Support\Str::of(\Illuminate\Support\Str::ascii($nombre))->lower()->toString();

        return [
            'proyecto de educacion continua' => 'Proyecto de educación continua',
            'diplomado' => 'Diplomado (80 a 250 horas máximo)',
            'congreso' => 'Congreso (2 a 5 días consecutivos, mínimo 6 horas por día)',
            'seminario' => 'Seminario (5 a 29 horas máximo)',
            'egresados unah' => 'Egresados(as) UNAH',
            'funcionarios publicos' => 'Funcionarios públicos',
            'lideres comunitarios' => 'Líderes comunitarios',
            'profesionales universitarios otros ies' => 'Profesionales universitarios otros CES',
            'academicos' => 'Académicos',
            '14-18' => 'Entre 14 – 18 años',
            '19-25' => 'Entre 19 – 25 años',
            '26-40' => 'Entre 26 – 40 años',
            '41-55' => 'Entre 41 – 55 años',
            '56-70' => 'Entre 56 – 70 años',
            'mayores de 70' => 'Mayores de 70 años',
            'grupos etnicos' => 'Grupos étnicos',
            'poblacion vulnerable' => 'Población vulnerable',
            'personas con discapacidad' => 'Personas con discapacidades',
            'campus virtual unah' => 'Campus virtual UNAH',
            'iniciativa de la unidad academica' => 'Iniciativa de la unidad académica',
            'solicitud de secretaria de estado' => 'Solicitud de Secretaría de Estado',
            'secretaria de estado' => 'Secretaría de Estado',
            'sector academico' => 'Sector académico',
            'carta formal de solicitud' => 'Carta formal de solicitud a la unidad académica',
        ][$key] ?? $nombre;
    };
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
    $programasAprobadosData = $programasAprobados->values();
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
        10 => 'Documentos',
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
                <p class="text-sm text-slate-600 dark:text-slate-300">Registro de proyectos de educación continua, diplomados, congresos y seminarios.</p>
            </div>
            <a href="{{ route('selectorTipoAccion') }}" class="text-sm font-semibold text-blue-700 hover:text-blue-900">Volver al selector</a>
        </div>

        @if ($errors->any())
            <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                Hay campos pendientes o con formato inválido. Revisa la ficha antes de guardar.
            </div>
        @endif

        <script>
            (() => {
                const enfStorageKey = @js($storageKey);

                if (@js($clearDraftOnLoad)) {
                    window.localStorage.removeItem(enfStorageKey);
                    window.localStorage.removeItem(`${enfStorageKey}:step`);

                    const currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.delete('nuevo');
                    window.history.replaceState({}, '', currentUrl);
                }

                window.__enfInitialDrafts = Object.assign(window.__enfInitialDrafts || {}, {
                    [enfStorageKey]: @js($initialDraft ?? []),
                });
            })();
        </script>

        <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" class="space-y-6" data-enf-wizard-form data-total-steps="{{ count($stepLabels) }}" data-storage-key="{{ $storageKey }}" data-clear-draft-on-load="{{ $clearDraftOnLoad ? '1' : '0' }}" data-lock-step-navigation="{{ $editingAccion ? '0' : '1' }}" data-record-id="{{ $editingAccion?->id }}" data-autosave-url="{{ route('enf.acciones.autoguardar-borrador') }}" data-autosave-update-url-template="{{ route('enf.acciones.autoguardar-borrador.update', ['accion' => '__ID__']) }}" data-destinatarios-url-template="{{ route('enf.acciones.destinatarios-inscripcion', ['accion' => '__ID__']) }}" data-send-review-url-template="{{ route('enf.acciones.enviar-revision', ['accion' => '__ID__']) }}">
            @csrf
            @if ($editingAccion)
                @method('PUT')
            @endif
            <input type="hidden" name="borrador_autoguardado_id" value="{{ $editingAccion?->id }}">
            <input type="hidden" name="tipo_accion_id" value="{{ old('tipo_accion_id', $tipoAccionVinculacionEnfId ?: $tiposAccion->first()?->id) }}">
            <input type="hidden" name="codigo_formulario" value="FORM-DVUS-018">
            <input type="hidden" name="estado_flujo" value="BORRADOR">

            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-gray-900">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Registro por pasos</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400" data-autosave-status>Los cambios se autoguardan mientras escribe.</p>
                        <p class="mt-1 hidden text-xs font-semibold text-red-600 dark:text-red-400" data-step-validation-message></p>
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
                    <label class="{{ $label }}">Programa aprobado de DAFT</label>
                    <select data-approved-program-select class="{{ $input }}">
                        <option value="">Crear acción desde cero</option>
                        @forelse ($programasAprobados as $programaAprobado)
                            <option value="{{ $programaAprobado['id'] }}">
                                {{ $programaAprobado['label'] }} · {{ $programaAprobado['source'] }}
                            </option>
                        @empty
                            <option value="" disabled>No hay programas aprobados disponibles</option>
                        @endforelse
                    </select>
                    <p class="mt-2 text-xs text-blue-800 dark:text-blue-200">
                        Al seleccionar un programa aprobado se cargarán automáticamente los datos disponibles desde DAFT. Esos datos quedarán en modo de solo lectura; los datos propios de la nueva edición permanecerán editables.
                    </p>
                    <div data-approved-program-summary class="mt-4 hidden rounded-md border border-blue-200 bg-white/80 p-4 dark:border-blue-800 dark:bg-slate-900/60"></div>
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="order-1">
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
                            @foreach ($tiposAccionForm018 as $item)
                                <option value="{{ $item->id }}" @selected(old('catalogos.tipo_accion_enf.0', $selectedTipoAccionEnfId) == $item->id)>{{ $form018CatalogLabel($item->nombre) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $label }}">No. resolución programa original</label>
                        <input name="resolucion_original" value="{{ old('resolucion_original') }}" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">No. resolución última actualización</label>
                        <input name="resolucion_actualizacion" value="{{ old('resolucion_actualizacion') }}" class="{{ $input }}">
                    </div>
                    <div class="order-1">
                        <label class="{{ $label }}">Número de edición</label>
                        <input type="number" min="1" name="numero_edicion" value="{{ old('numero_edicion', 1) }}" class="{{ $input }}">
                    </div>
                    <div class="order-1">
                        <label class="{{ $label }}">Fecha de inicio</label>
                        <input type="date" name="fecha_inicio" value="{{ old('fecha_inicio') }}" class="{{ $input }}">
                    </div>
                    <div class="order-1">
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
                        @enf-approved-program-selected.window="
                            lockedCentros = Boolean($event.detail.locked_centros);
                            lockedDepartamentos = Boolean($event.detail.locked_departamentos);
                            lockedCarreras = Boolean($event.detail.locked_carreras);
                            openCentros = false;
                            openDepartamentos = false;
                            openCarreras = false;
                            selectedCentros = normalized($event.detail.centro_facultad_ids || []);
                            selectedDepartamentos = normalized($event.detail.departamento_academico_ids || []);
                            selectedCarreras = normalized($event.detail.carrera_ids || []);
                            filterSelections();
                            notifyChange();
                        "
                        x-data="{
                            openCentros: false,
                            openDepartamentos: false,
                            openCarreras: false,
                            lockedCentros: false,
                            lockedDepartamentos: false,
                            lockedCarreras: false,
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
                                <div @click="if (!lockedCentros) { openCentros = true; $nextTick(() => $refs.searchCentros?.focus()) }"
                                    class="min-h-[42px] w-full cursor-text rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm transition focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500 dark:border-gray-600 dark:bg-gray-800">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <template x-for="id in selectedCentros" :key="`centro-${id}`">
                                            <span class="inline-flex max-w-full items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                                <span class="truncate" x-text="label(centrosOptions, id)"></span>
                                                <button x-show="!lockedCentros" type="button" @click.stop="remove('selectedCentros', id)" class="font-bold leading-none hover:text-blue-950 dark:hover:text-blue-100">×</button>
                                            </span>
                                        </template>
                                        <input x-ref="searchCentros" x-model="searchCentros" @focus="if (!lockedCentros) openCentros = true" @keydown.escape="openCentros = false" :disabled="lockedCentros"
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
                                <div x-show="openCentros && !lockedCentros" x-cloak class="absolute z-50 mt-1 max-h-56 w-full overflow-y-auto rounded-md border border-blue-200 bg-white shadow-lg dark:border-blue-700 dark:bg-gray-800">
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
                                <div @click="if (selectedCentros.length && !lockedDepartamentos) { openDepartamentos = true; $nextTick(() => $refs.searchDepartamentos?.focus()) }"
                                    class="min-h-[42px] w-full rounded-md border px-3 py-2 text-sm shadow-sm transition"
                                    :class="selectedCentros.length ? 'cursor-text border-gray-300 bg-white focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500 dark:border-gray-600 dark:bg-gray-800' : 'cursor-not-allowed border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/60'">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <template x-for="id in selectedDepartamentos" :key="`departamento-${id}`">
                                            <span class="inline-flex max-w-full items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                                <span class="truncate" x-text="label(departamentosOptions, id)"></span>
                                                <button x-show="!lockedDepartamentos" type="button" @click.stop="remove('selectedDepartamentos', id)" class="font-bold leading-none hover:text-blue-950 dark:hover:text-blue-100">×</button>
                                            </span>
                                        </template>
                                        <input x-ref="searchDepartamentos" x-model="searchDepartamentos" @focus="if (selectedCentros.length) openDepartamentos = true" @keydown.escape="openDepartamentos = false"
                                            :disabled="!selectedCentros.length || lockedDepartamentos"
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
                                <div x-show="openDepartamentos && selectedCentros.length && !lockedDepartamentos" x-cloak class="absolute z-50 mt-1 max-h-56 w-full overflow-y-auto rounded-md border border-blue-200 bg-white shadow-lg dark:border-blue-700 dark:bg-gray-800">
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
                                <div @click="if (selectedCentros.length && !lockedCarreras) { openCarreras = true; $nextTick(() => $refs.searchCarreras?.focus()) }"
                                    class="min-h-[42px] w-full rounded-md border px-3 py-2 text-sm shadow-sm transition"
                                    :class="selectedCentros.length ? 'cursor-text border-gray-300 bg-white focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500 dark:border-gray-600 dark:bg-gray-800' : 'cursor-not-allowed border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/60'">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <template x-for="id in selectedCarreras" :key="`carrera-${id}`">
                                            <span class="inline-flex max-w-full items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                                <span class="truncate" x-text="label(carrerasOptions, id)"></span>
                                                <button x-show="!lockedCarreras" type="button" @click.stop="remove('selectedCarreras', id)" class="font-bold leading-none hover:text-blue-950 dark:hover:text-blue-100">×</button>
                                            </span>
                                        </template>
                                        <input x-ref="searchCarreras" x-model="searchCarreras" @focus="if (selectedCentros.length) openCarreras = true" @keydown.escape="openCarreras = false"
                                            :disabled="!selectedCentros.length || lockedCarreras"
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
                                <div x-show="openCarreras && selectedCentros.length && !lockedCarreras" x-cloak class="absolute z-50 mt-1 max-h-56 w-full overflow-y-auto rounded-md border border-blue-200 bg-white shadow-lg dark:border-blue-700 dark:bg-gray-800">
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
                        <input type="number" min="0" name="total_horas" value="{{ old('total_horas', 0) }}" class="{{ $input }} bg-slate-50 dark:bg-slate-800/70" readonly>
                    </div>
                </div>
            </div>

            <div class="{{ $card }} hidden" data-step-panel="2">
                <h2 class="{{ $sectionTitle }}">2. Lugar, modalidad virtual y antecedentes</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="{{ $label }}">Modalidad de ejecución</label>
                        <select name="modalidad_ejecucion" data-modalidad-ejecucion class="{{ $input }}">
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
                    <div class="md:col-span-3">
                        <label class="{{ $label }}">Descripción de las plataformas virtuales y de teledocencia</label>
                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                            <div data-teledocencia-fields class="rounded-md border border-slate-200 p-3 transition-opacity dark:border-slate-700">
                                <h3 class="mb-3 text-sm font-semibold text-slate-800 dark:text-slate-100">Teledocencia</h3>
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    @foreach ($catalog('plataforma')->filter(fn ($item) => in_array($item->nombre, ['Teams', 'Zoom', 'Meet', 'Webex', 'Otro'], true)) as $item)
                                        <label class="flex items-center gap-2 rounded-md border border-slate-200 px-3 py-2 text-sm dark:border-slate-700">
                                            <input type="checkbox" name="catalogos[plataforma_teledocencia][]" value="{{ $item->id }}" @checked(in_array((string) $item->id, array_map('strval', (array) old('catalogos.plataforma_teledocencia', [])), true)) class="rounded border-gray-300 text-blue-600">
                                            <span>{{ $form018CatalogLabel($item->nombre) }}</span>
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
                                            <span>{{ $form018CatalogLabel($item->nombre) }}</span>
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
                                    <span>{{ $form018CatalogLabel($item->nombre) }}</span>
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
                                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="catalogos[perfil_participante][]" value="{{ $item->id }}" class="rounded border-gray-300 text-blue-600"> {{ $form018CatalogLabel($item->nombre) }}</label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label class="{{ $label }}">Rango de edad</label>
                        <div class="space-y-2">
                            @foreach ($catalog('rango_edad') as $item)
                                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="catalogos[rango_edad][]" value="{{ $item->id }}" class="rounded border-gray-300 text-blue-600"> {{ $form018CatalogLabel($item->nombre) }}</label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label class="{{ $label }}">Condición social</label>
                        <div class="space-y-2">
                            @foreach ($catalog('condicion_social') as $item)
                                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="catalogos[condicion_social][]" value="{{ $item->id }}" class="rounded border-gray-300 text-blue-600"> {{ $form018CatalogLabel($item->nombre) }}</label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div><label class="{{ $label }}">Total cupos programados</label><input type="number" min="0" name="beneficiarios[total]" value="0" class="{{ $input }}"></div>
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
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Participación de la comunidad universitaria</h3>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Registre la distribución de hombres y mujeres por tipo de participación.</p>
                        </div>
                        <button type="button" data-open-participacion-list-modal
                            class="rounded-md bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">
                            Gestionar participación
                        </button>
                    </div>
                    <div class="mt-4 overflow-x-auto rounded-md border border-slate-200 dark:border-slate-700">
                        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500 dark:bg-slate-800">
                                <tr>
                                    <th class="px-3 py-2">Tipo de participación</th>
                                    <th class="px-3 py-2 text-right">Hombres</th>
                                    <th class="px-3 py-2 text-right">Mujeres</th>
                                    <th class="px-3 py-2 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody data-participacion-summary class="divide-y divide-slate-100 bg-white dark:divide-slate-800 dark:bg-slate-900">
                                <tr><td colspan="4" class="px-3 py-4 text-center text-slate-500">Sin participación registrada.</td></tr>
                            </tbody>
                            <tfoot data-participacion-summary-totals class="hidden bg-slate-50 font-semibold text-slate-800 dark:bg-slate-800 dark:text-slate-100">
                                <tr>
                                    <td class="px-3 py-2">Total general</td>
                                    <td class="px-3 py-2 text-right" data-participacion-total-hombres>0</td>
                                    <td class="px-3 py-2 text-right" data-participacion-total-mujeres>0</td>
                                    <td class="px-3 py-2 text-right" data-participacion-total-general>0</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="hidden" data-participacion-fields>
                        @foreach ([
                            'Estudiantes de grado / posgrado',
                            'Práctica de asignatura',
                            'Servicio Social o PPS',
                            'Voluntariado',
                            'Personal docente',
                            'Profesores x hora',
                            'Profesores horarios',
                            'Profesores permanentes',
                            'Personal administrativo',
                            'Administrativo',
                            'Servicios',
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

                <div data-participacion-list-modal role="dialog" aria-modal="true" aria-labelledby="participacion-list-modal-title"
                    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
                    <div class="flex max-h-[90vh] w-full max-w-6xl flex-col rounded-lg bg-white p-5 shadow-xl dark:bg-slate-900">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <div>
                                <h2 id="participacion-list-modal-title" class="text-base font-semibold text-slate-900 dark:text-slate-100">Participación de la comunidad universitaria</h2>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Seleccione “Editar” para ingresar la cantidad de hombres y mujeres.</p>
                            </div>
                            <button type="button" data-close-participacion-list-modal class="rounded-md px-3 py-1 text-sm font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">Cerrar</button>
                        </div>
                        <div class="overflow-auto rounded-md border border-slate-200 dark:border-slate-700">
                            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                                <thead class="sticky top-0 bg-slate-50 text-left text-xs uppercase text-slate-500 dark:bg-slate-800">
                                    <tr>
                                        <th class="px-3 py-2">Tipo</th>
                                        <th class="px-3 py-2">Hombres</th>
                                        <th class="px-3 py-2">Mujeres</th>
                                        <th class="px-3 py-2">Total</th>
                                        <th class="px-3 py-2"></th>
                                    </tr>
                                </thead>
                                <tbody data-participacion-list class="divide-y divide-slate-100 dark:divide-slate-800"></tbody>
                            </table>
                        </div>
                        <div class="mt-5 flex justify-end">
                            <button type="button" data-close-participacion-list-modal class="rounded-md bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">Listo</button>
                        </div>
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
                    <div><label class="{{ $label }}">RTN / identificación internacional</label><input name="contraparte[rtn]" maxlength="50" class="{{ $input }}" data-contraparte-field></div>
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
                                <option value="{{ $item->id }}">{{ $form018CatalogLabel($item->nombre) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $label }}">Instrumento de alianza</label>
                        <select name="contraparte[instrumento_alianza_id]" class="{{ $input }}" data-contraparte-field>
                            <option value="">Seleccione...</option>
                            @foreach ($catalog('instrumento_alianza') as $item)
                                <option value="{{ $item->id }}">{{ $form018CatalogLabel($item->nombre) }}</option>
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
                    <div><label class="{{ $label }}">Resumen de logística</label><textarea name="logistica" rows="3" class="{{ $input }}"></textarea></div>
                </div>
            </div>

            <div class="{{ $card }} hidden" data-step-panel="7">
                <h2 class="{{ $sectionTitle }}">7. Resultados esperados y ODS</h2>
                <div class="space-y-4">
                    <section class="rounded-md border border-slate-200 p-4 dark:border-slate-700">
                        @php
                            $gruposResultados = [
                                ['tipo' => 'Corto plazo', 'cantidad' => 6, 'clave' => 'corto', 'titulo' => 'Resultados de corto plazo'],
                                ['tipo' => 'Mediano plazo', 'cantidad' => 5, 'clave' => 'mediano', 'titulo' => 'Resultados de mediano plazo'],
                                ['tipo' => 'Largo plazo / impacto', 'cantidad' => 5, 'clave' => 'largo', 'titulo' => 'Resultados de largo plazo / impacto'],
                            ];
                        @endphp

                        <div class="mb-4">
                            <h3 class="text-base font-semibold text-slate-800 dark:text-slate-100">Resultados esperados</h3>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Agrega cada resultado en la tabla del plazo correspondiente.</p>
                        </div>

                        <p data-resultados-feedback class="mb-3 hidden rounded-md bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:bg-amber-950/40 dark:text-amber-200"></p>

                        <div class="space-y-5">
                            @foreach ($gruposResultados as $grupoResultado)
                                <div>
                                    <div class="mb-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                        <h4 class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $grupoResultado['titulo'] }}</h4>
                                        <button type="button" data-open-resultado-modal="{{ $grupoResultado['tipo'] }}" class="self-start rounded-md bg-blue-700 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 sm:self-auto">Agregar resultado</button>
                                    </div>
                                    <div class="overflow-hidden rounded-md border border-slate-200 dark:border-slate-700">
                                        <table class="w-full table-fixed divide-y divide-slate-200 text-sm dark:divide-slate-700">
                                            <colgroup>
                                                @if ($grupoResultado['tipo'] === 'Corto plazo')
                                                    <col class="w-[8%]">
                                                    <col class="w-[40%]">
                                                    <col class="w-[34%]">
                                                @else
                                                    <col class="w-[44%]">
                                                    <col class="w-[38%]">
                                                @endif
                                                <col class="w-[18%]">
                                            </colgroup>
                                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                                <tr>
                                                    @if ($grupoResultado['tipo'] === 'Corto plazo')
                                                        <th class="px-3 py-2">OE</th>
                                                    @endif
                                                    <th class="px-3 py-2">Descripción del resultado</th>
                                                    <th class="px-3 py-2">Medio de verificación</th>
                                                    <th class="px-3 py-2 text-right">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody data-resultados-list="{{ $grupoResultado['clave'] }}" data-show-objetivo="{{ $grupoResultado['tipo'] === 'Corto plazo' ? '1' : '0' }}" class="divide-y divide-slate-100 dark:divide-slate-800"></tbody>
                                        </table>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div data-resultados-fields class="hidden">
                            @php $resultadoIndex = 0; @endphp
                            @foreach ($gruposResultados as $grupoResultado)
                                @for ($filaResultado = 0; $filaResultado < $grupoResultado['cantidad']; $filaResultado++, $resultadoIndex++)
                                    <div data-resultado-row="{{ $resultadoIndex }}" data-tipo="{{ $grupoResultado['tipo'] }}" data-grupo="{{ $grupoResultado['clave'] }}">
                                        <input type="hidden" name="resultados[{{ $resultadoIndex }}][tipo]" value="{{ $grupoResultado['tipo'] }}">
                                        @if ($grupoResultado['tipo'] === 'Corto plazo')
                                            <input type="number" min="1" name="resultados[{{ $resultadoIndex }}][objetivo_orden]">
                                        @endif
                                        <textarea name="resultados[{{ $resultadoIndex }}][descripcion]"></textarea>
                                        <textarea name="resultados[{{ $resultadoIndex }}][indicador]"></textarea>
                                    </div>
                                @endfor
                            @endforeach
                        </div>
                    </section>
                    <div
                        x-data="{
                            openOds: false,
                            openMetas: false,
                            searchOds: '',
                            searchMetas: '',
                            odsOptions: @js($odsOptions),
                            metasOptions: @js($metasContribuyeOptions),
                            selectedOds: @js(array_map('strval', (array) old('ods_ids', []))),
                            selectedMetas: @js(array_map('strval', (array) old('meta_contribuye_ids', []))),
                            init() {
                                const key = this.$root.closest('form')?.dataset.storageKey;
                                if (key) {
                                    try {
                                        const initial = window.__enfInitialDrafts?.[key] || {};
                                        const stored = JSON.parse(window.localStorage.getItem(key) || '{}');
                                        const data = { ...initial, ...stored };
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
                    @foreach ([
                        'presupuesto_ingresos' => ['Ingresos', ['Cuotas de inscripción', 'Mensualidades / módulos', 'Gestión de becas (donaciones)', 'Otros']],
                        'presupuesto_egresos' => ['Egresos', ['Pago de personal docente', 'Gastos de materiales y suministros', 'Gastos de movilización (transporte, pasajes)', 'Gastos de manutención y hospedaje', 'Costos administrativos / Financieros', 'Otros gastos']],
                        'aporte_unah' => ['Aportación de la UNAH', ['Horas de participación del personal docente del equipo ejecutor de la acción', 'Horas de participación estudiantes', 'Costos indirectos depreciación de equipo (3% de la suma de los incisos a) y b) anteriores)', 'Costos indirectos servicios públicos ((3% de la suma de los incisos a) y b) anteriores))']],
                    ] as $name => [$title, $rubros])
                        <div class="rounded-md border border-slate-200 p-4 dark:border-slate-700" data-presupuesto-card="{{ $name }}">
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $title }}</h3>
                                <button type="button" data-open-presupuesto-modal="{{ $name }}"
                                    class="rounded-md bg-blue-700 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-50">
                                    Agregar {{ match ($name) { 'presupuesto_ingresos' => 'ingreso', 'presupuesto_egresos' => 'egreso', default => 'aporte' } }}
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
                <h2 class="{{ $sectionTitle }}">10. Documentos adjuntos</h2>
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
                            'label' => 'Otros (detallar)',
                            'slug' => 'otros_documentos_respaldo',
                        ],
                    ] as $documentoSupervisor)
                    <section class="rounded-md border border-slate-200 p-4 shadow-sm dark:border-slate-700" data-doc-upload-card>
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $documentoSupervisor['label'] }}</h3>

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
                        {{ $editingAccion ? 'Actualizar borrador ENF' : 'Guardar borrador ENF' }}
                    </button>
                </div>
            </div>
        </form>

        @include('enf.acciones.partials.send-review-modal')

        <div data-resultado-modal class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-2xl rounded-lg bg-white p-5 shadow-xl dark:bg-slate-900">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 data-resultado-modal-title class="text-base font-semibold text-slate-900 dark:text-slate-100">Agregar resultado</h2>
                    <button type="button" data-close-resultado-modal class="rounded-md px-3 py-1 text-sm font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">Cerrar</button>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="{{ $label }}">Plazo</label>
                        <input data-resultado-tipo class="{{ $input }} bg-slate-50 dark:bg-slate-800/70" readonly>
                    </div>
                    <div data-resultado-objetivo-wrap>
                        <label class="{{ $label }}">Objetivo específico (OE)</label>
                        <input type="number" min="1" data-resultado-objetivo class="{{ $input }}" placeholder="No.">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $label }}">Descripción del resultado</label>
                        <textarea data-resultado-descripcion rows="3" class="{{ $input }}"></textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $label }}">Medio de verificación (indicador)</label>
                        <textarea data-resultado-indicador rows="3" class="{{ $input }}"></textarea>
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-3">
                    <button type="button" data-close-resultado-modal class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Cancelar</button>
                    <button type="button" data-save-resultado class="rounded-md bg-blue-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">Guardar</button>
                </div>
            </div>
        </div>

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
                        <label class="{{ $label }}">Hombres</label>
                        <input type="number" min="0" data-participacion-hombres class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Mujeres</label>
                        <input type="number" min="0" data-participacion-mujeres class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Total</label>
                        <input type="number" min="0" data-participacion-cantidad class="{{ $input }}" readonly>
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
                    <div class="md:col-span-6">
                        <label class="{{ $label }}">Período académico</label>
                        <select data-practica-periodo-id class="{{ $input }}">
                            <option value="">Seleccione un período académico...</option>
                            @foreach ($periodosAcademicos as $periodo)
                                <option value="{{ $periodo->id }}">{{ $periodoAcademicoLabel($periodo) }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" data-practica-periodo-texto>
                    </div>
                    <div>
                        <label class="{{ $label }}">Hombres</label>
                        <input type="number" min="0" data-practica-hombres class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Mujeres</label>
                        <input type="number" min="0" data-practica-mujeres class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Matrícula</label>
                        <input type="number" min="0" data-practica-matricula class="{{ $input }} bg-slate-50 dark:bg-slate-800/70" readonly>
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
            const shouldLockStepNavigation = form.dataset.lockStepNavigation === '1';
            const approvedPrograms = @js($programasAprobadosData);
            const empleados = @js($empleadosModalData);
            const initialDraft = @js($initialDraft ?? []);
            const autosaveUrl = form.dataset.autosaveUrl;
            const autosaveUpdateUrlTemplate = form.dataset.autosaveUpdateUrlTemplate || '';
            const draftIdField = form.querySelector('[name="borrador_autoguardado_id"]');
            const oldObjetivosEspecificos = @js(array_values((array) old('objetivos_especificos', [])));
            const approvedProgramSelect = form.querySelector('[data-approved-program-select]');
            const approvedProgramSummary = form.querySelector('[data-approved-program-summary]');
            const panels = Array.from(form.querySelectorAll('[data-step-panel]'));
            const previousButton = form.querySelector('[data-previous-step]');
            const nextButton = form.querySelector('[data-next-step]');
            const submitButton = form.querySelector('[data-submit-step]');
            const status = form.querySelector('[data-autosave-status]');
            const stepValidationMessage = form.querySelector('[data-step-validation-message]');
            const objetivosEspecificosList = form.querySelector('[data-objetivos-especificos-list]');
            const objetivoEspecificoTemplate = form.querySelector('[data-objetivo-especifico-template]');
            const addObjetivoEspecificoButton = form.querySelector('[data-add-objetivo-especifico]');
            const horasTeoricasField = form.querySelector('[name="horas_teoricas"]');
            const horasPracticasField = form.querySelector('[name="horas_practicas"]');
            const totalHorasField = form.querySelector('[name="total_horas"]');
            const modalidadEjecucionField = form.querySelector('[data-modalidad-ejecucion]');
            const teledocenciaFields = form.querySelector('[data-teledocencia-fields]');
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
            const participacionListModal = form.querySelector('[data-participacion-list-modal]');
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
            const resultadoModal = document.querySelector('[data-resultado-modal]');
            const resultadoModalTitle = document.querySelector('[data-resultado-modal-title]');
            const resultadoObjetivoWrap = document.querySelector('[data-resultado-objetivo-wrap]');
            const resultadoFeedback = form.querySelector('[data-resultados-feedback]');
            const resultadoInputs = {
                tipo: document.querySelector('[data-resultado-tipo]'),
                objetivo_orden: document.querySelector('[data-resultado-objetivo]'),
                descripcion: document.querySelector('[data-resultado-descripcion]'),
                indicador: document.querySelector('[data-resultado-indicador]'),
            };
            let selectedEmployeeId = null;
            let currentConsultorGroup = null;
            let currentConsultorExtraField = null;
            let currentParticipacionIndex = null;
            let currentPracticaIndex = null;
            let currentPresupuestoGroup = null;
            let currentPresupuestoIndex = null;
            let currentCronogramaIndex = null;
            let currentResultadoIndex = null;
            let draftRecordId = form.dataset.recordId || draftIdField?.value || '';
            let localAutosaveTimer = null;
            let serverAutosaveTimer = null;
            let serverAutosavePromise = Promise.resolve();
            let serverAutosaveDirty = false;
            let serverAutosaveInFlight = false;
            let submittingAfterAutosave = false;
            let shouldPersistDraft = Boolean(draftRecordId);
            let sendReviewConfirmed = false;
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
                if (!sendReviewSteps || !sendReviewBody) {
                    return;
                }

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
                        </div>
                    `;
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
                        </div>
                    `;
                }

                sendReviewPrev?.classList.toggle('hidden', sendReviewStep === 0);
                sendReviewNext?.classList.toggle('hidden', sendReviewStep >= sendReviewEtapas.length);
                sendReviewConfirm?.classList.toggle('hidden', sendReviewStep < sendReviewEtapas.length);
            };

            const appendDestinatariosToForm = () => {
                form.querySelectorAll('[data-enf-destinatario-hidden]').forEach((field) => field.remove());
                sendReviewEtapas.forEach((etapa) => {
                    const selected = selectedCandidate(etapa);
                    if (!selected) {
                        return;
                    }
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
                sendReviewConfirmed = true;
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
                    .catch(() => {
                        submitButton?.removeAttribute('disabled');
                        status.textContent = 'No se pudo cargar el flujo de revisión.';
                    });
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
            if (clearDraftOnLoad) {
                window.localStorage.removeItem(storageKey);
                window.localStorage.removeItem(`${storageKey}:step`);
            }

            let step = Number(window.localStorage.getItem(`${storageKey}:step`) || 1);

            const clampStep = (value) => Math.min(Math.max(Number(value) || 1, 1), totalSteps);

            const collectDraftData = () => {
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

                return data;
            };

            const updateDraftRecord = (payload) => {
                if (!payload?.id) {
                    return;
                }

                draftRecordId = String(payload.id);
                form.dataset.recordId = draftRecordId;

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
                status.textContent = 'Guardando borrador...';

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
                        status.textContent = `Borrador guardado ${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
                    })
                    .catch(() => {
                        serverAutosaveDirty = true;
                        status.textContent = 'No se pudo guardar el borrador. Se reintentará.';
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
                const data = collectDraftData();

                window.localStorage.setItem(storageKey, JSON.stringify(data));
                status.textContent = `Autoguardado ${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;

                if (persist) {
                    scheduleServerAutosave();
                }
            };

            const debouncedSave = () => {
                window.clearTimeout(localAutosaveTimer);
                localAutosaveTimer = window.setTimeout(save, 600);
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

            const numericHoursValue = (field) => {
                const value = Number(field?.value || 0);

                return Number.isFinite(value) && value > 0 ? value : 0;
            };

            const syncTotalHoras = () => {
                if (!totalHorasField) {
                    return;
                }

                totalHorasField.value = String(numericHoursValue(horasTeoricasField) + numericHoursValue(horasPracticasField));
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

            const ignoredStepFieldContainers = [
                '[data-equipo-docente-fields]',
                '[data-consultor-fields]',
                '[data-participacion-fields]',
                '[data-practicas-fields]',
                '[data-presupuesto-fields]',
                '[data-cronograma-fields]',
            ].join(',');
            const invalidFieldClasses = ['border-red-500', 'ring-1', 'ring-red-500'];
            const invalidChoiceClasses = ['text-red-600', 'dark:text-red-400'];

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

            const isStepField = (field) => {
                return !field.disabled
                    && field.type !== 'hidden'
                    && field.name !== '_token'
                    && !field.closest(ignoredStepFieldContainers);
            };

            const markInvalidTarget = (target, classes) => {
                if (!target) {
                    return;
                }

                target.classList.add(...classes);
                target.setAttribute('data-wizard-invalid', '1');
            };

            const requiredMarkerSelector = '[data-wizard-required-marker]';

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

            const syncRequiredMarkers = () => {
                form.querySelectorAll(requiredMarkerSelector).forEach((marker) => marker.remove());

                if (!shouldLockStepNavigation) {
                    return;
                }

                panels.forEach((panel) => {
                    const groupedChoices = new Map();
                    const fields = Array.from(panel.querySelectorAll('input[name], select[name], textarea[name]'))
                        .filter((field) => isStepField(field) && !field.classList.contains('hidden'));

                    fields.forEach((field) => {
                        if (field.type === 'checkbox' || field.type === 'radio') {
                            const group = groupedChoices.get(field.name) || [];
                            group.push(field);
                            groupedChoices.set(field.name, group);
                            return;
                        }

                        appendRequiredMarker(fieldRequiredTarget(field, panel));
                    });

                    groupedChoices.forEach((choices) => {
                        if (choices.length > 1) {
                            const groupTarget = fieldRequiredTarget(choices[0], panel);

                            if (groupTarget) {
                                appendRequiredMarker(groupTarget);
                                return;
                            }

                            choices.forEach((field) => appendRequiredMarker(field.closest('label')));
                        }
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

                if (stepNumber === 9 && !stepIsComplete(stepNumber)) {
                    firstInvalidField = panel.querySelector('[data-open-cronograma-modal]');
                } else {
                    const groupedChoices = new Map();
                    const fields = Array.from(panel.querySelectorAll('input[name], select[name], textarea[name]'))
                        .filter(isStepField);

                    fields.forEach((field) => {
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
                }

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

            const setFieldValue = (name, value) => {
                const field = form.querySelector(fieldSelector(name));

                if (!field || Array.isArray(value)) {
                    return;
                }

                field.value = value ?? '';
                field.dispatchEvent(new Event('input', { bubbles: true }));
                field.dispatchEvent(new Event('change', { bubbles: true }));
            };

            const approvedProgramFieldNames = [...new Set(approvedPrograms.flatMap((program) =>
                Object.keys(program.fields || {}).filter((name) => !name.endsWith('_ids[]'))
            ))];

            const setApprovedProgramFieldLocked = (name, locked) => {
                const field = form.querySelector(fieldSelector(name));

                if (!field) {
                    return;
                }

                if (field.matches('input, textarea')) {
                    if (!field.dataset.approvedProgramOriginalReadonly) {
                        field.dataset.approvedProgramOriginalReadonly = field.readOnly ? '1' : '0';
                    }
                    field.readOnly = locked || field.dataset.approvedProgramOriginalReadonly === '1';
                } else if (field.matches('select')) {
                    field.classList.toggle('pointer-events-none', locked);
                    field.setAttribute('aria-disabled', locked ? 'true' : 'false');
                    field.tabIndex = locked ? -1 : 0;
                }

                field.classList.toggle('cursor-not-allowed', locked);
                field.classList.toggle('bg-slate-100', locked);
                field.classList.toggle('text-slate-600', locked);
                field.classList.toggle('dark:bg-slate-800/70', locked);
            };

            const renderApprovedProgramSummary = (program) => {
                if (!approvedProgramSummary || !program) {
                    approvedProgramSummary?.classList.add('hidden');
                    if (approvedProgramSummary) approvedProgramSummary.innerHTML = '';
                    return;
                }

                const details = (program.details || []).map((detail) => `
                    <div class="rounded-md bg-blue-50/70 px-3 py-2 dark:bg-blue-950/30">
                        <dt class="text-[11px] font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-300">${escapeHtml(detail.label)}</dt>
                        <dd class="mt-1 whitespace-pre-wrap text-sm text-slate-800 dark:text-slate-100">${escapeHtml(detail.value)}</dd>
                    </div>
                `).join('');

                approvedProgramSummary.innerHTML = `
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Información disponible del programa</h3>
                        <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-900/50 dark:text-blue-200">${escapeHtml(program.source || 'Programa aprobado')}</span>
                    </div>
                    <dl class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">${details}</dl>
                `;
                approvedProgramSummary.classList.remove('hidden');
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

                approvedProgramFieldNames.forEach((name) => {
                    setApprovedProgramFieldLocked(name, false);
                    setFieldValue(name, '');
                });

                if (!program) {
                    renderApprovedProgramSummary(null);
                    form.dispatchEvent(new CustomEvent('enf-approved-program-selected', {
                        bubbles: true,
                        detail: {
                            locked_centros: false,
                            locked_departamentos: false,
                            locked_carreras: false,
                            centro_facultad_ids: [],
                            departamento_academico_ids: [],
                            carrera_ids: [],
                        },
                    }));
                    syncTotalHoras();
                    save();
                    return;
                }

                Object.entries(program.fields || {}).forEach(([name, value]) => setFieldValue(name, value));
                Object.entries(program.fields || {})
                    .filter(([name, value]) => !name.endsWith('_ids[]') && value !== null && value !== undefined && value !== '')
                    .forEach(([name]) => setApprovedProgramFieldLocked(name, true));
                renderApprovedProgramSummary(program);
                form.dispatchEvent(new CustomEvent('enf-approved-program-selected', {
                    bubbles: true,
                    detail: {
                        locked_centros: Boolean(program.fields?.['centro_facultad_ids[]']?.length || program.fields?.centro_facultad_id),
                        locked_departamentos: Boolean(program.fields?.['departamento_academico_ids[]']?.length || program.fields?.departamento_academico_id),
                        locked_carreras: Boolean(program.fields?.['carrera_ids[]']?.length || program.fields?.carrera_id),
                        centro_facultad_ids: program.fields?.['centro_facultad_ids[]'] || (program.fields?.centro_facultad_id ? [program.fields.centro_facultad_id] : []),
                        departamento_academico_ids: program.fields?.['departamento_academico_ids[]'] || (program.fields?.departamento_academico_id ? [program.fields.departamento_academico_id] : []),
                        carrera_ids: program.fields?.['carrera_ids[]'] || (program.fields?.carrera_id ? [program.fields.carrera_id] : []),
                    },
                }));
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
                const summary = form.querySelector('[data-participacion-summary]');
                const summaryTotals = form.querySelector('[data-participacion-summary-totals]');
                const rows = Array.from(form.querySelectorAll('[data-participacion-row]'))
                    .map((row, index) => ({ row, index }));

                if (target) {
                    target.innerHTML = rows.map(({ row, index }) => `
                        <tr>
                            <td class="px-3 py-2 font-medium text-slate-700 dark:text-slate-200">${escapeHtml(rowValue(row, 'tipo_participacion'))}</td>
                            <td class="px-3 py-2">${escapeHtml(rowValue(row, 'hombres') || '0')}</td>
                            <td class="px-3 py-2">${escapeHtml(rowValue(row, 'mujeres') || '0')}</td>
                            <td class="px-3 py-2">${escapeHtml(rowValue(row, 'cantidad') || '0')}</td>
                            <td class="px-3 py-2 text-right">
                                <button type="button" data-edit-participacion="${index}" class="text-sm font-semibold text-blue-700 hover:text-blue-900">Editar</button>
                            </td>
                        </tr>
                    `).join('');
                }

                if (!summary) {
                    return;
                }

                const registeredRows = rows.filter(({ row }) =>
                    Number(rowValue(row, 'hombres') || 0) > 0
                    || Number(rowValue(row, 'mujeres') || 0) > 0
                    || Number(rowValue(row, 'cantidad') || 0) > 0
                );

                if (registeredRows.length === 0) {
                    summary.innerHTML = '<tr><td colspan="4" class="px-3 py-4 text-center text-slate-500">Sin participación registrada.</td></tr>';
                    summaryTotals?.classList.add('hidden');
                    return;
                }

                summary.innerHTML = registeredRows.map(({ row }) => `
                    <tr>
                        <td class="px-3 py-2 font-medium text-slate-700 dark:text-slate-200">${escapeHtml(rowValue(row, 'tipo_participacion'))}</td>
                        <td class="px-3 py-2 text-right">${escapeHtml(rowValue(row, 'hombres') || '0')}</td>
                        <td class="px-3 py-2 text-right">${escapeHtml(rowValue(row, 'mujeres') || '0')}</td>
                        <td class="px-3 py-2 text-right font-semibold">${escapeHtml(rowValue(row, 'cantidad') || '0')}</td>
                    </tr>
                `).join('');

                const totals = registeredRows.reduce((result, { row }) => ({
                    hombres: result.hombres + Number(rowValue(row, 'hombres') || 0),
                    mujeres: result.mujeres + Number(rowValue(row, 'mujeres') || 0),
                    general: result.general + Number(rowValue(row, 'cantidad') || 0),
                }), { hombres: 0, mujeres: 0, general: 0 });

                const totalHombres = form.querySelector('[data-participacion-total-hombres]');
                const totalMujeres = form.querySelector('[data-participacion-total-mujeres]');
                const totalGeneral = form.querySelector('[data-participacion-total-general]');

                if (totalHombres) totalHombres.textContent = String(totals.hombres);
                if (totalMujeres) totalMujeres.textContent = String(totals.mujeres);
                if (totalGeneral) totalGeneral.textContent = String(totals.general);
                summaryTotals?.classList.remove('hidden');
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

            const resultadoHasValue = (row) => [
                'objetivo_orden',
                'descripcion',
                'indicador',
            ].some((fieldName) => rowValue(row, fieldName));

            const renderResultados = () => {
                form.querySelectorAll('[data-resultados-list]').forEach((target) => {
                    const showObjetivo = target.dataset.showObjetivo === '1';
                    const rows = Array.from(form.querySelectorAll(`[data-resultado-row][data-grupo="${target.dataset.resultadosList}"]`))
                        .map((row) => ({ row, index: row.dataset.resultadoRow }))
                        .filter(({ row }) => resultadoHasValue(row));

                    if (rows.length === 0) {
                        target.innerHTML = `<tr><td colspan="${showObjetivo ? 4 : 3}" class="px-3 py-4 text-center text-slate-500">Sin resultados agregados en este plazo.</td></tr>`;
                        return;
                    }

                    target.innerHTML = rows.map(({ row, index }) => `
                        <tr class="align-top">
                            ${showObjetivo ? `<td class="px-3 py-3">${escapeHtml(rowValue(row, 'objetivo_orden') || '—')}</td>` : ''}
                            <td class="whitespace-pre-wrap break-words px-3 py-3 [overflow-wrap:anywhere]">${escapeHtml(rowValue(row, 'descripcion') || 'Sin descripción')}</td>
                            <td class="whitespace-pre-wrap break-words px-3 py-3 [overflow-wrap:anywhere]">${escapeHtml(rowValue(row, 'indicador') || 'Sin dato')}</td>
                            <td class="px-3 py-3 text-right">
                                <div class="flex flex-col items-end gap-1">
                                    <button type="button" data-edit-resultado="${index}" class="text-sm font-semibold text-blue-700 hover:text-blue-900">Editar</button>
                                    <button type="button" data-remove-resultado="${index}" class="text-sm font-semibold text-red-600 hover:text-red-800">Quitar</button>
                                </div>
                            </td>
                        </tr>
                    `).join('');
                });
            };

            const renderDynamicLists = () => {
                renderEquipoDocente();
                renderConsultores('consultores_nacionales');
                renderConsultores('consultores_internacionales');
                renderParticipacion();
                renderPracticas();
                updateIngresosState();
                renderPresupuesto('presupuesto_egresos');
                renderPresupuesto('aporte_unah');
                renderCronograma();
                renderResultados();
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
                hideModal(participacionListModal);
                showModal(participacionModal);
                participacionInputs.hombres?.focus();
            };

            const cancelParticipacion = () => {
                hideModal(participacionModal);
                currentParticipacionIndex = null;
                renderParticipacion();
                showModal(participacionListModal);
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
                showModal(participacionListModal);
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

            const updatePracticaMatriculaTotal = () => {
                if (practicaInputs.matricula_total) {
                    practicaInputs.matricula_total.value = String(
                        numericValue(practicaInputs.hombres?.value) + numericValue(practicaInputs.mujeres?.value),
                    );
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

                updatePracticaMatriculaTotal();
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
                updatePracticaMatriculaTotal();

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

            const nextEmptyResultadoRow = (tipo) => Array.from(form.querySelectorAll('[data-resultado-row]'))
                .find((row) => row.dataset.tipo === tipo && !resultadoHasValue(row));

            const hideResultadoFeedback = () => {
                if (!resultadoFeedback) {
                    return;
                }

                resultadoFeedback.textContent = '';
                resultadoFeedback.classList.add('hidden');
            };

            const showResultadoFeedback = (message) => {
                if (!resultadoFeedback) {
                    return;
                }

                resultadoFeedback.textContent = message;
                resultadoFeedback.classList.remove('hidden');
            };

            const closeResultadoModal = () => {
                hideModal(resultadoModal);
                currentResultadoIndex = null;
            };

            const openResultadoModal = (tipo, index = null) => {
                const row = index === null
                    ? nextEmptyResultadoRow(tipo)
                    : form.querySelector(`[data-resultado-row="${index}"]`);

                hideResultadoFeedback();

                if (!row) {
                    showResultadoFeedback(`Se alcanzó el máximo de resultados para ${tipo.toLowerCase()}.`);
                    return;
                }

                currentResultadoIndex = row.dataset.resultadoRow;
                const editing = resultadoHasValue(row);
                const rowTipo = row.dataset.tipo || tipo;

                if (resultadoModalTitle) {
                    resultadoModalTitle.textContent = `${editing ? 'Editar' : 'Agregar'} resultado`;
                }

                if (resultadoInputs.tipo) resultadoInputs.tipo.value = rowTipo;
                if (resultadoInputs.objetivo_orden) resultadoInputs.objetivo_orden.value = rowValue(row, 'objetivo_orden');
                if (resultadoInputs.descripcion) resultadoInputs.descripcion.value = rowValue(row, 'descripcion');
                if (resultadoInputs.indicador) resultadoInputs.indicador.value = rowValue(row, 'indicador');

                const isCortoPlazo = rowTipo === 'Corto plazo';
                resultadoObjetivoWrap?.classList.toggle('hidden', !isCortoPlazo);
                if (resultadoInputs.objetivo_orden) {
                    resultadoInputs.objetivo_orden.required = isCortoPlazo;
                }

                showModal(resultadoModal);
                (isCortoPlazo ? resultadoInputs.objetivo_orden : resultadoInputs.descripcion)?.focus();
            };

            const saveResultado = () => {
                const row = currentResultadoIndex !== null
                    ? form.querySelector(`[data-resultado-row="${currentResultadoIndex}"]`)
                    : null;

                if (!row || !resultadoInputs.descripcion?.value.trim()) {
                    resultadoInputs.descripcion?.focus();
                    return;
                }

                if (row.dataset.tipo === 'Corto plazo' && !resultadoInputs.objetivo_orden?.value) {
                    resultadoInputs.objetivo_orden?.focus();
                    return;
                }

                setRowValues(row, {
                    objetivo_orden: resultadoInputs.objetivo_orden?.value || '',
                    descripcion: resultadoInputs.descripcion.value,
                    indicador: resultadoInputs.indicador?.value || '',
                });

                closeResultadoModal();
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
                        if (field.tagName === 'SELECT') {
                            field.value = value[0] || '';
                        }

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

                const reachableStep = highestReachableStep();

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
                    const isReachable = index <= reachableStep;
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

            const updateSupervisorDocumentUploadState = () => {
                form.querySelectorAll('[data-doc-upload-card]').forEach((card) => {
                    const radios = Array.from(card.querySelectorAll('[data-doc-upload-radio]'));
                    const file = card.querySelector('[data-doc-upload-file]');
                    const selectedRadio = radios.find((radio) => radio.checked);
                    const uploadEnabled = selectedRadio?.value === 'Si';

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

            const updateTeledocenciaState = () => {
                const isPresencial = modalidadEjecucionField?.value === 'Presencial';

                teledocenciaFields?.classList.toggle('opacity-50', isPresencial);
                teledocenciaFields?.classList.toggle('cursor-not-allowed', isPresencial);
                teledocenciaFields?.setAttribute('aria-disabled', isPresencial ? 'true' : 'false');

                teledocenciaFields?.querySelectorAll('input, select, textarea').forEach((field) => {
                    field.disabled = isPresencial;

                    if (isPresencial && (field.type === 'checkbox' || field.type === 'radio')) {
                        field.checked = false;
                    }
                });
            };

            restore();
            syncTotalHoras();
            updateRegisteredEmployeesDetails();
            updateSupervisorDocumentUploadState();
            updateContraparteState();
            updateMetasContribuyeState();
            updateTeledocenciaState();
            renderDynamicLists();
            syncRequiredMarkers();
            step = shouldLockStepNavigation ? firstIncompleteStepBefore(step) || step : step;
            render();

            form.querySelectorAll('[data-step-button]').forEach((button) => {
                button.addEventListener('click', () => goTo(button.dataset.stepButton));
            });

            previousButton?.addEventListener('click', () => goTo(step - 1));
            nextButton?.addEventListener('click', () => goTo(step + 1));
            addObjetivoEspecificoButton?.addEventListener('click', () => {
                addObjetivoEspecifico();
                save();
                syncRequiredMarkers();
                render();
            });
            form.addEventListener('input', () => {
                shouldPersistDraft = true;
                syncTotalHoras();
                updateRegisteredEmployeesDetails();
                updateSupervisorDocumentUploadState();
                updateContraparteState();
                updateMetasContribuyeState();
                updateTeledocenciaState();
                renderDynamicLists();
                syncRequiredMarkers();
                render();
                refreshValidationFeedback();
                debouncedSave();
            });
            form.addEventListener('change', () => {
                shouldPersistDraft = true;
                syncTotalHoras();
                updateRegisteredEmployeesDetails();
                updateSupervisorDocumentUploadState();
                updateContraparteState();
                updateMetasContribuyeState();
                updateTeledocenciaState();
                renderDynamicLists();
                syncRequiredMarkers();
                render();
                refreshValidationFeedback();
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
                const editResultado = event.target.closest('[data-edit-resultado]');
                const removeResultado = event.target.closest('[data-remove-resultado]');

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
                    return;
                }

                if (editResultado) {
                    const row = form.querySelector(`[data-resultado-row="${editResultado.dataset.editResultado}"]`);
                    openResultadoModal(row?.dataset.tipo || '', editResultado.dataset.editResultado);
                    return;
                }

                if (removeResultado) {
                    const row = form.querySelector(`[data-resultado-row="${removeResultado.dataset.removeResultado}"]`);
                    setRowValues(row, {
                        objetivo_orden: '',
                        descripcion: '',
                        indicador: '',
                    });
                    hideResultadoFeedback();
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
            form.querySelector('[data-open-participacion-list-modal]')?.addEventListener('click', () => {
                renderParticipacion();
                showModal(participacionListModal);
            });
            form.querySelectorAll('[data-close-participacion-list-modal]').forEach((button) => {
                button.addEventListener('click', () => hideModal(participacionListModal));
            });
            document.querySelectorAll('[data-close-participacion-modal]').forEach((button) => {
                button.addEventListener('click', cancelParticipacion);
            });
            participacionInputs.hombres?.addEventListener('input', updateParticipacionTotal);
            participacionInputs.mujeres?.addEventListener('input', updateParticipacionTotal);
            document.querySelector('[data-save-participacion]')?.addEventListener('click', saveParticipacion);
            document.querySelector('[data-open-practica-modal]')?.addEventListener('click', () => openPracticaModal());
            document.querySelectorAll('[data-close-practica-modal]').forEach((button) => {
                button.addEventListener('click', () => hideModal(practicaModal));
            });
            practicaInputs.periodo_academico_id?.addEventListener('change', updatePracticaPeriodoTexto);
            practicaInputs.hombres?.addEventListener('input', updatePracticaMatriculaTotal);
            practicaInputs.mujeres?.addEventListener('input', updatePracticaMatriculaTotal);
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
            document.querySelectorAll('[data-open-resultado-modal]').forEach((button) => {
                button.addEventListener('click', () => openResultadoModal(button.dataset.openResultadoModal));
            });
            document.querySelectorAll('[data-close-resultado-modal]').forEach((button) => {
                button.addEventListener('click', closeResultadoModal);
            });
            document.querySelector('[data-save-resultado]')?.addEventListener('click', saveResultado);
            form.addEventListener('submit', (event) => {
                if (submittingAfterAutosave) {
                    return;
                }

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
