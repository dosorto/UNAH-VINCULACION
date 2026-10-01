<div>
    {{-- Step progress bar --}}
    @php
        $stepLabels = [
            1 => 'Info General',
            2 => 'Equipo Ejecutor',
            3 => 'Contraparte',
            4 => 'Cronograma',
            5 => 'Formulación',
            6 => 'Beneficiarios',
            7 => 'Marco Lógico',
            8 => 'Presupuesto',
            9 => 'Anexos',
        ];
    @endphp
<div
    x-data
    x-on:borrador-creado.window="
        const baseUrl = @js(url('crearProyectoVinculacion'));
        if ($event.detail?.id && !window.location.pathname.endsWith('/' + $event.detail.id)) {
            window.history.replaceState({}, '', baseUrl + '/' + $event.detail.id);
        }
    "
    x-on:validation-failed.window="
        $nextTick(() => {
            const el = document.getElementById('validation-error-summary');
            if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    "
>
    @if($esVoluntariado)
    <div class="mb-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/40 px-4 py-3">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">FORM-DVUS-015</p>
        <h2 class="text-lg font-bold text-gray-900 dark:text-white">Registro de Proyecto de Voluntariado Académico</h2>
    </div>
    @endif

    @if($enSubsanacion)
    <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-4 text-amber-950 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-100">
        <p class="font-semibold">Este proyecto está en subsanación. Realice las correcciones solicitadas y presione Reenviar a revisión.</p>
        @if($detalleSubsanacion)
        <dl class="mt-3 grid grid-cols-1 gap-2 text-sm sm:grid-cols-2">
            <div class="sm:col-span-2">
                <dt class="font-semibold">Motivo de rechazo</dt>
                <dd>{{ $detalleSubsanacion['motivo'] }}</dd>
            </div>
            <div>
                <dt class="font-semibold">Rechazado por</dt>
                <dd>{{ $detalleSubsanacion['rechazado_por'] }}</dd>
            </div>
            <div>
                <dt class="font-semibold">Fecha</dt>
                <dd>{{ \Illuminate\Support\Carbon::parse($detalleSubsanacion['fecha'])->format('d/m/Y H:i') }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="font-semibold">Etapa</dt>
                <dd>{{ $detalleSubsanacion['etapa'] }}</dd>
            </div>
            @if ($detalleSubsanacion['documento'] ?? null)
                <div class="sm:col-span-2">
                    <dt class="font-semibold">Documento adjunto</dt>
                    <dd>
                        <a href="{{ route('proyectos.documentos-subsanacion.descargar', $detalleSubsanacion['documento']) }}"
                           class="text-amber-900 underline hover:text-amber-700 dark:text-amber-200 dark:hover:text-amber-100"
                           target="_blank">
                            {{ $detalleSubsanacion['documento']->nombre_original }}
                        </a>
                    </dd>
                </div>
            @endif
        </dl>
        @endif
    </div>
    @endif

    @if(!empty($seccionesNoGuardadas))
    <div role="alert" class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-900 dark:border-red-700 dark:bg-red-900/20 dark:text-red-100">
        <p class="font-semibold">Hay cambios que no se han guardado. Corrija lo indicado; el resto del formulario sí está guardado.</p>
        <p class="mt-1">{{ $this->resumenSeccionesNoGuardadas() }}</p>
    </div>
    @endif

    {{-- Step progress --}}
    <div class="mb-6 bg-white dark:bg-gray-900 shadow rounded-lg p-4">
        <div class="flex items-center overflow-x-auto gap-0.5">
            @foreach($stepLabels as $step => $label)
                @php $complete = $this->isStepComplete($step); @endphp
                <button wire:click="goToStep({{ $step }})" type="button"
                    class="flex flex-col items-center flex-1 min-w-[44px] p-1 group">
                    <span class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold mb-1 transition-colors
                        {{ $currentStep === $step
                            ? 'bg-blue-600 text-white ring-2 ring-blue-300'
                            : ($complete
                                ? 'bg-green-500 text-white'
                                : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400') }}">
                        {{ $complete ? '✓' : $step }}
                    </span>
                    <span class="text-[10px] text-center hidden sm:block leading-tight
                        {{ $currentStep === $step ? 'text-blue-600 font-semibold' : ($complete ? 'text-green-600 dark:text-green-400' : 'text-gray-500') }}">
                        {{ $label }}
                    </span>
                </button>
                @if($step < 9)
                    <div class="h-0.5 w-3 shrink-0 {{ $complete ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-700' }}"></div>
                @endif
            @endforeach
        </div>
    </div>

    <div class="bg-white dark:bg-gray-900 shadow rounded-lg p-6">

        @if($errors->any())
        <div id="validation-error-summary" class="mb-6 rounded-lg border border-red-300 bg-red-50 dark:bg-red-900/20 dark:border-red-700 px-4 py-3">
            <p class="text-sm font-semibold text-red-800 dark:text-red-300 mb-1">Revise los siguientes campos antes de continuar:</p>
            <ul class="list-disc list-inside space-y-0.5 text-xs text-red-700 dark:text-red-300">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        {{-- ══════════════════ PASO 1: Información General ══════════════════ --}}
        @if($currentStep === 1)
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Paso 1: Información General</h3>
        <div class="space-y-4">
            {{-- Nombre --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre del Proyecto <span class="text-red-500">*</span></label>
                <input type="text" wire:model.live.debounce.1000ms="nombre_proyecto" maxlength="255" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500" />
                @error('nombre_proyecto') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            {{-- Modalidad --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Modalidad <span class="text-red-500">*</span></label>
                <select wire:model.live="modalidad_id" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <option value="">Seleccione...</option>
                    @foreach($modalidades as $id => $nombre) <option value="{{ $id }}">{{ $nombre }}</option> @endforeach
                </select>
                @error('modalidad_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Temática principal (FORM-DVUS-015 · sólo Voluntariado) --}}
            @if($esVoluntariado)
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Temática principal del proyecto <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                    @foreach($tematicaPrincipalOpciones as $valor => $etiqueta)
                    <label class="flex items-center gap-2 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer hover:border-blue-400">
                        <input type="radio" wire:model.live="tematica_principal" value="{{ $valor }}" class="text-blue-600 focus:ring-blue-500" />
                        <span>{{ $etiqueta }}</span>
                    </label>
                    @endforeach
                </div>
                @error('tematica_principal') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror

                @if($tematica_principal === 'otros')
                <div class="mt-2">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Especifique la temática</label>
                    <input type="text" wire:model.live.debounce.1000ms="tematica_principal_otro" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500" />
                    @error('tematica_principal_otro') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                @endif
            </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Categorías <span class="text-red-500">*</span></label>
                <div x-data="{
                        open: false,
                        selected: $wire.entangle('categoria').live,
                        options: @js($categorias),
                        toggle(id) {
                            id = String(id);
                            let curr = (this.selected || []).map(String);
                            const i = curr.indexOf(id);
                            if (i === -1) curr.push(id); else curr.splice(i, 1);
                            this.selected = curr;
                        },
                        isSelected(id) { return (this.selected || []).map(String).includes(String(id)); },
                        getName(id) { return this.options[id] ?? this.options[String(id)] ?? id; }
                    }" @click.outside="open = false" class="relative">
                    <div @click="open = !open" class="min-h-[42px] w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 cursor-pointer flex flex-wrap gap-1 items-center">
                        <template x-for="id in (selected || [])" :key="id">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300">
                                <span x-text="getName(id)"></span>
                                <button type="button" @click.stop="toggle(id)" class="ml-0.5 font-bold leading-none hover:text-orange-900">×</button>
                            </span>
                        </template>
                        <span x-show="!selected || selected.length === 0" class="text-gray-400 text-sm">Seleccione una opción</span>
                        <span class="ml-auto text-gray-400 text-xs" x-text="open ? '▴' : '▾'"></span>
                    </div>
                    <div x-show="open" x-cloak class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg max-h-48 overflow-y-auto">
                        <template x-for="[id, name] in Object.entries(options)" :key="id">
                            <div @click="toggle(id)" class="px-3 py-2 text-sm cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center justify-between"
                                :class="isSelected(id) ? 'bg-orange-50 dark:bg-orange-900/20 text-orange-700 dark:text-orange-300 font-medium' : 'text-gray-700 dark:text-gray-300'">
                                <span x-text="name"></span>
                                <span x-show="isSelected(id)" class="text-orange-600 dark:text-orange-400 text-xs">✓</span>
                            </div>
                        </template>
                    </div>
                </div>
                @error('categoria') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $esVoluntariado ? 'Alineamiento con ejes prioritarios de la UNAH' : 'Alineamiento Institucional' }} <span class="text-red-500">*</span></label>
                <div x-data="{
                        selected: $wire.entangle('ejes_prioritarios_unah').live,
                        get valorActual() { return (this.selected && this.selected[0]) ? String(this.selected[0]) : ''; },
                        elegir(id) { this.selected = id ? [id] : []; },
                    }">
                    <select
                        :value="valorActual"
                        @change="elegir($event.target.value)"
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                    >
                        <option value="">Seleccione...</option>
                        @foreach($ejesPrioritarios as $id => $nombre)
                            <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>
                @error('ejes_prioritarios_unah') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            @php
                $academicMultiSelects = [
                    [
                        'field' => 'facultades_centros',
                        'label' => 'Facultad, Centro Universitario Regional o Instituto Tecnológico',
                        'options' => $facultadesCentros,
                        'placeholder' => 'Buscar o seleccionar facultades/centros...',
                        'disabled' => false,
                        'emptyMessage' => 'No hay facultades o centros disponibles.',
                    ],
                    [
                        'field' => 'departamentos_academicos',
                        'label' => 'Escuela, Departamento Académico, Técnicos Universitarios, Instituto de Investigación, Observatorio o Consultorio',
                        'options' => $departamentosAcademicos,
                        'placeholder' => 'Buscar o seleccionar departamentos...',
                        'disabled' => empty($facultades_centros) || !$departamentosAcademicos->count(),
                        'emptyMessage' => empty($facultades_centros)
                            ? 'Seleccione primero Facultad o Centros.'
                            : 'No hay departamentos para la Facultad o Centro seleccionado.',
                    ],
                    [
                        'field' => 'carreras',
                        'label' => $esVoluntariado ? 'Carrera' : 'Carreras',
                        'options' => $carrerasOpts,
                        'placeholder' => 'Buscar o seleccionar carreras...',
                        'disabled' => $carrera_no_aplica || empty($departamentos_academicos) || !$carrerasOpts->count(),
                        'emptyMessage' => $carrera_no_aplica
                            ? 'No aplica: este proyecto no requiere seleccionar carreras.'
                            : (empty($departamentos_academicos)
                                ? 'Seleccione primero Departamentos Académicos.'
                                : 'No hay carreras para el Departamento Académico seleccionado.'),
                    ],
                ];
            @endphp

            @foreach($academicMultiSelects as $field)
            <div wire:key="academico-{{ $field['field'] }}-{{ md5(json_encode($field['options'])) }}-{{ $field['disabled'] ? '1' : '0' }}">
                <div class="mb-1 flex items-center justify-between gap-3">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $field['label'] }} <span class="text-red-500">*</span></label>
                    @if($field['field'] === 'carreras')
                    <label class="inline-flex cursor-pointer items-center gap-2">
                        <span class="relative inline-flex h-5 w-9 flex-shrink-0">
                            <input wire:model.live="carrera_no_aplica" type="checkbox" class="peer sr-only" />
                            <span class="h-5 w-9 rounded-full bg-slate-200 transition-colors peer-checked:bg-blue-600 dark:bg-slate-700 dark:peer-checked:bg-blue-600"></span>
                            <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-4"></span>
                        </span>
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-300">No aplica</span>
                    </label>
                    @endif
                </div>
                <div
                    x-data="{
                        open: false,
                        search: '',
                        selected: $wire.entangle('{{ $field['field'] }}').live,
                        options: @js($field['options']),
                        disabled: @js($field['disabled']),
                        placeholder: @js($field['placeholder']),
                        emptyMessage: @js($field['emptyMessage']),
                        values() {
                            return Object.entries(this.options || {});
                        },
                        selectedValues() {
                            return (this.selected || []).map(String);
                        },
                        filteredOptions() {
                            const term = this.search.trim().toLowerCase();
                            return this.values().filter(([id, name]) => {
                                return !term || String(name).toLowerCase().includes(term);
                            });
                        },
                        toggle(id) {
                            if (this.disabled) return;
                            id = String(id);
                            const current = this.selectedValues();
                            const index = current.indexOf(id);
                            if (index === -1) current.push(id); else current.splice(index, 1);
                            this.selected = current;
                            this.search = '';
                            this.$nextTick(() => this.$refs.search?.focus());
                        },
                        remove(id) {
                            id = String(id);
                            this.selected = this.selectedValues().filter(value => value !== id);
                        },
                        isSelected(id) {
                            return this.selectedValues().includes(String(id));
                        },
                        getName(id) {
                            return this.options[id] ?? this.options[String(id)] ?? id;
                        }
                    }"
                    @click.outside="open = false"
                    class="relative"
                >
                    <div
                        @click="if (!disabled) { open = true; $nextTick(() => $refs.search?.focus()) }"
                        class="min-h-[42px] w-full rounded-md border px-3 py-2 flex flex-wrap gap-1.5 items-center transition"
                        :class="disabled
                            ? 'border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800/60 cursor-not-allowed'
                            : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 cursor-text focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500'"
                    >
                        <template x-for="id in selectedValues()" :key="id">
                            <span class="inline-flex max-w-full items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                <span class="truncate" x-text="getName(id)"></span>
                                <button type="button" @click.stop="remove(id)" class="font-bold leading-none hover:text-blue-950 dark:hover:text-blue-100">×</button>
                            </span>
                        </template>
                        <input
                            x-ref="search"
                            x-model="search"
                            @focus="if (!disabled) open = true"
                            @keydown.escape="open = false"
                            :disabled="disabled"
                            :placeholder="selectedValues().length ? '' : placeholder"
                            class="min-w-[180px] flex-1 border-0 bg-transparent p-0 text-sm text-gray-900 placeholder:text-gray-400 focus:ring-0 disabled:cursor-not-allowed disabled:text-gray-500 dark:text-white"
                            type="text"
                        />
                        <span class="ml-auto text-gray-400 text-xs" x-text="open && !disabled ? '▴' : '▾'"></span>
                    </div>
                    <div
                        x-show="open && !disabled"
                        x-cloak
                        class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg max-h-56 overflow-y-auto"
                    >
                        <template x-if="filteredOptions().length === 0">
                            <div class="px-3 py-2 text-sm text-gray-500">Sin resultados.</div>
                        </template>
                        <template x-for="[id, name] in filteredOptions()" :key="id">
                            <div
                                @click="toggle(id)"
                                class="px-3 py-2 text-sm cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center justify-between gap-3"
                                :class="isSelected(id) ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 font-medium' : 'text-gray-700 dark:text-gray-300'"
                            >
                                <span x-text="name"></span>
                                <span x-show="isSelected(id)" class="text-blue-600 dark:text-blue-300 text-xs">✓</span>
                            </div>
                        </template>
                    </div>
                    <p x-show="disabled" class="text-xs text-gray-500 dark:text-gray-400 mt-1" x-text="emptyMessage"></p>
                </div>
                @error($field['field']) <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            @endforeach

            {{-- Programa / Líneas --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $esVoluntariado ? 'Programa al que pertenece' : 'Programa/Estrategia al que Pertenece' }} <span class="text-red-500">*</span></label>
                <input type="text" wire:model.live.debounce.1000ms="programa_pertenece" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500" />
                @error('programa_pertenece') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Líneas de Investigación de la Unidad Académica <span class="text-red-500">*</span></label>
                <textarea wire:model.live.debounce.1000ms="lineas_investigacion_academica" rows="3" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
                @error('lineas_investigacion_academica') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- ODS --}}
            <div>
                <div class="mb-1 flex items-center justify-between gap-3">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">ODS <span class="text-red-500">*</span></label>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Máximo 3</span>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                    Indique el o los ODS a los que pretende contribuir el proyecto y las metas correspondientes. Para esta descripción deberá basarse en el
                    <a href="https://www.un.org/sustainabledevelopment/es/objetivos-de-desarrollo-sostenible/" target="_blank" rel="noopener" class="underline hover:text-blue-600">documento de ODS de la ONU (Objetivos y metas de desarrollo sostenible)</a>.
                </p>
                <div wire:ignore x-data="{
                        open: false,
                        maxOds: 3,
                        selected: ($wire.get('ods') || []).map(String),
                        options: @js($odsList),
                        toggle(id) {
                            id = String(id);
                            const current = (this.selected || []).map(String);
                            const index = current.indexOf(id);

                            if (index === -1) {
                                if (current.length >= this.maxOds) return;
                                current.push(id);
                            } else {
                                current.splice(index, 1);
                            }

                            this.selected = current;
                            $wire.set('ods', current, true);
                        },
                        isSelected(id) {
                            return (this.selected || []).map(String).includes(String(id));
                        },
                        isDisabled(id) {
                            return !this.isSelected(id) && (this.selected || []).length >= this.maxOds;
                        },
                        getName(id) {
                            return this.options[id] ?? this.options[String(id)] ?? id;
                        }
                    }" @click.outside="open=false" class="relative">
                    <div @click="open=!open" class="min-h-[42px] w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 cursor-pointer flex flex-wrap gap-1 items-center">
                        <template x-for="(id, idx) in (selected||[])" :key="id"><span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300"><span x-show="idx === 0" class="rounded-full bg-blue-700 px-1.5 py-0.5 text-[9px] font-bold uppercase text-white">Principal</span><span x-text="getName(id)"></span><button type="button" @click.stop="toggle(id)" class="font-bold">×</button></span></template>
                        <span x-show="!selected||selected.length===0" class="text-gray-400 text-sm">Seleccione los ODS...</span>
                        <span class="ml-auto text-gray-400 text-xs"><span x-text="(selected || []).length + '/3'"></span> <span x-text="open?'▴':'▾'"></span></span>
                    </div>
                    <div x-show="open" x-cloak class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg max-h-56 overflow-y-auto">
                        <template x-for="[id,name] in Object.entries(options)" :key="id"><div @click="toggle(id)" class="px-3 py-2 text-sm flex items-center justify-between" :class="isSelected(id)?'bg-blue-50 text-blue-700 font-medium cursor-pointer':(isDisabled(id)?'text-gray-400 dark:text-gray-600 cursor-not-allowed':'text-gray-700 dark:text-gray-300 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700')" :aria-disabled="isDisabled(id)"><span x-text="name"></span><span x-show="isSelected(id)" class="text-xs">✓</span></div></template>
                    </div>
                </div>
                @error('ods') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Metas (carga automática al seleccionar ODS) --}}
            @if($metasList->count())
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $esVoluntariado ? 'Meta a la que se contribuye' : 'Meta(s) a la que se Contribuye' }} @if($esVoluntariado)<span class="text-red-500">*</span>@endif</label>
                @if($esVoluntariado)
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Seleccione al menos una meta por cada ODS.</p>
                @endif
                <div wire:key="metas-contribuye-{{ md5(json_encode($metasDisponibles)) }}" x-data="{
                        open: false,
                        search: '',
                        selected: @js($metasContribuye),
                        options: @js($metasDisponibles),
                        values() {
                            return Object.entries(this.options || {});
                        },
                        selectedValues() {
                            return (this.selected || []).map(String);
                        },
                        filteredOptions() {
                            const term = this.search.trim().toLowerCase();
                            return this.values().filter(([id, name]) => {
                                return !term || String(name).toLowerCase().includes(term);
                            });
                        },
                        toggle(id) {
                            id = String(id);
                            const current = this.selectedValues();
                            const index = current.indexOf(id);
                            if (index === -1) current.push(id); else current.splice(index, 1);
                            this.selected = current;
                            this.search = '';
                            this.syncSelection();
                            this.$nextTick(() => this.$refs.search?.focus());
                        },
                        remove(id) {
                            id = String(id);
                            this.selected = this.selectedValues().filter(value => value !== id);
                            this.syncSelection();
                        },
                        syncSelection() {
                            const values = this.selectedValues();
                            this.$wire.set('metasContribuye', values, false);
                            this.$wire.call('guardarMetasContribuyeSeleccionadas', values);
                        },
                        isSelected(id) {
                            return this.selectedValues().includes(String(id));
                        },
                        getName(id) {
                            return this.options[id] ?? this.options[String(id)] ?? id;
                        }
                    }" @click.outside="open = false" class="relative">
                    <div
                        @click="open = true; $nextTick(() => $refs.search?.focus())"
                        class="min-h-[42px] w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 cursor-text flex flex-wrap gap-1.5 items-center focus-within:border-green-500 focus-within:ring-1 focus-within:ring-green-500"
                    >
                        <template x-for="id in selectedValues()" :key="id">
                            <span class="inline-flex max-w-full items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                <span class="truncate" x-text="getName(id)"></span>
                                <button type="button" @click.stop="remove(id)" class="font-bold leading-none hover:text-blue-950 dark:hover:text-blue-100">×</button>
                            </span>
                        </template>
                        <input
                            x-ref="search"
                            x-model="search"
                            @focus="open = true"
                            @keydown.escape="open = false"
                            :placeholder="selectedValues().length ? '' : 'Buscar o seleccionar metas...'"
                            class="min-w-[180px] flex-1 border-0 bg-transparent p-0 text-sm text-gray-900 placeholder:text-gray-400 focus:ring-0 dark:text-white"
                            type="text"
                        />
                        <span class="ml-auto text-gray-400 text-xs" x-text="open ? '▴' : '▾'"></span>
                    </div>
                    <div x-show="open" x-cloak class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-blue-200 dark:border-blue-700 rounded-md shadow-lg max-h-56 overflow-y-auto">
                        <template x-if="filteredOptions().length === 0">
                            <div class="px-3 py-2 text-sm text-gray-500">Sin resultados.</div>
                        </template>
                        <template x-for="[id,name] in filteredOptions()" :key="id"><div @click="toggle(id)" class="px-3 py-2 text-xs cursor-pointer hover:bg-blue-50 flex items-start gap-2" :class="isSelected(id)?'bg-blue-50 text-blue-700 font-medium':'text-gray-700 dark:text-gray-300'"><span class="mt-0.5 shrink-0" x-show="isSelected(id)">✓</span><span x-text="name"></span></div></template>
                    </div>
                </div>
                @foreach($errors->get('metasContribuye') as $mensaje) <p class="text-red-500 text-xs mt-1">{{ $mensaje }}</p> @endforeach
            </div>
            @endif

            {{-- Fechas --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha de Inicio <span class="text-red-500">*</span></label>
                    <input type="date" wire:model.blur="fecha_inicio" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500" />
                    @error('fecha_inicio') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha de Finalización <span class="text-red-500">*</span></label>
                    <input type="date" wire:model.blur="fecha_finalizacion" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500" />
                    @error('fecha_finalizacion') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>
        @endif

        {{-- ══════════════════ PASO 2: Equipo Ejecutor ══════════════════ --}}
        @if($currentStep === 2)
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Paso 2: Equipo Ejecutor</h3>
        <div class="space-y-6">

            {{-- Coordinador (no editable) --}}
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center text-sm font-bold shrink-0">C</div>
                <div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $coordNombre }}</p>
                    <p class="text-xs text-blue-600 dark:text-blue-400">{{ $esVoluntariado ? 'Coordinador/a del Programa' : 'Coordinador/a del Proyecto' }}</p>
                    @error('coordinador') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Empleados integrantes --}}
            <div>
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Integrantes del Equipo Docente Permanente Tiempo Completo</h4>
                        <p class="text-xs text-gray-500 mt-0.5">Agregar más líneas de ser necesario. Los voluntarios no se listan en el ítem {{ $esVoluntariado ? 12 : 10 }}: suman en «Voluntariado personal de la UNAH» según la categoría y el sexo de su perfil.</p>
                    </div>
                    <button wire:click="openEmpleadoModal" type="button"
                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                        + Agregar empleado
                    </button>
                </div>
                @if(count($empleado_proyecto))
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="min-w-full text-sm divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Nombre</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Categoría</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Rol</th>
                                <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($empleado_proyecto as $i => $emp)
                            @php
                                $perfilEmp = $perfilesEquipo[(int) ($emp['empleado_id'] ?? 0)] ?? null;
                                // Una categoría registrada sin columna en el voluntariado (p. ej. Titular) no puede ser voluntaria;
                                // en el 015 el ítem 12 solo admite docentes permanentes como integrantes.
                                $voluntarioBloqueado = filled($perfilEmp['categoria'] ?? null) && blank($perfilEmp['columna'] ?? null);
                                $integranteBloqueado = $esVoluntariado && !($perfilEmp['permanente'] ?? false) && filled($perfilEmp['columna'] ?? null);
                                $esVoluntarioEmp = ($emp['rol'] ?? null) === 'Voluntario';
                            @endphp
                            <tr wire:key="equipo-{{ $emp['empleado_id'] ?? $i }}">
                                <td class="px-4 py-2 text-gray-900 dark:text-white">{{ $emp['nombre'] ?: 'Empleado #'.($emp['empleado_id'] ?? '-') }}</td>
                                <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">{{ $perfilEmp['categoria'] ?? 'Sin categoría' }}</td>
                                <td class="px-4 py-2">
                                    <select wire:model.live="empleado_proyecto.{{ $i }}.rol" class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-2 py-1 text-xs focus:border-blue-500">
                                        <option value="Integrante" @disabled($integranteBloqueado)>Integrante</option>
                                        <option value="Voluntario" @disabled($voluntarioBloqueado)>Voluntario</option>
                                    </select>
                                    @if($esVoluntarioEmp && filled($perfilEmp['columna'] ?? null) && filled($perfilEmp['sexo'] ?? null))
                                        <p class="text-[11px] text-gray-500 mt-1">Suma en «{{ \App\Models\Proyecto\Proyecto::VOLUNTARIADO_PERSONAL_UNAH[$perfilEmp['columna']] }}» ({{ $perfilEmp['sexo'] }}).</p>
                                    @elseif($esVoluntarioEmp)
                                        <p class="text-[11px] text-amber-600 mt-1">Complete la categoría y el sexo en su perfil para contarlo.</p>
                                    @elseif($voluntarioBloqueado)
                                        <p class="text-[11px] text-gray-400 mt-1">Docente permanente: no puede ser voluntario.</p>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-right"><button wire:click="removeEmpleado({{ $i }})" type="button" class="text-xs text-red-600 hover:text-red-800">Eliminar</button></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-sm text-gray-500 text-center py-4 border border-dashed border-gray-300 dark:border-gray-600 rounded-lg">Sin empleados integrantes agregados.</p>
                @endif
                @foreach($errors->get('empleado_proyecto.*') as $mensajes)
                    @foreach($mensajes as $mensaje) <p class="text-red-500 text-xs mt-1">{{ $mensaje }}</p> @endforeach
                @endforeach
            </div>

            {{-- Voluntariado personal de la UNAH: ítem 13 del FORM-DVUS-001, 15 del FORM-DVUS-015. Se calcula con el equipo de arriba. --}}
            @include('livewire.proyectos.vinculacion.partials.voluntariado-bloque', ['bloque' => [
                'titulo' => 'Voluntariado personal de la UNAH',
                'subtitulo' => 'Desglose del tipo de participación de personal de la UNAH (cantidad)',
                'columnas' => \App\Models\Proyecto\Proyecto::VOLUNTARIADO_PERSONAL_UNAH,
                'automatico' => true,
            ]])

            {{-- Integrantes Internacionales --}}
            <div>
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $esVoluntariado ? 'Integrantes del equipo de cooperación internacional' : 'Docentes Internacionales Participantes en el Proyecto' }}</h4>
                        <p class="text-xs text-gray-500 mt-0.5">Agregar más líneas de ser necesario.</p>
                    </div>
                    <button wire:click="openInternacionalModal" type="button"
                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                        + Agregar internacional
                    </button>
                </div>
                @if(count($integrante_internacional_proyecto))
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="min-w-full text-sm divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                @if($esVoluntariado)
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Nombre Completo</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Pasaporte</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Correo electrónico</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">País</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Universidad</th>
                                @else
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Nombre</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">RTN</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Sexo</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">País</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Institución</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Nivel Académico</th>
                                @endif
                                <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($integrante_internacional_proyecto as $i => $int)
                            <tr wire:key="integrante-internacional-{{ $int['integrante_internacional_id'] ?? $i }}-{{ $i }}">
                                @if($esVoluntariado)
                                @php
                                    $internacionalIncompleto = empty($int['nivel_academico_id']) || !in_array($int['sexo'] ?? '', ['masculino', 'femenino'], true);
                                @endphp
                                <td class="px-4 py-2 text-gray-900 dark:text-white">
                                    {{ $int['nombre'] ?: 'Integrante #'.($int['integrante_internacional_id'] ?? '-') }}
                                    @if($internacionalIncompleto)
                                        <p class="text-xs font-medium text-red-600">Falta sexo o nivel académico: use «Editar».</p>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ ($int['documento_identidad'] ?? '') ?: '-' }}</td>
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ ($int['email'] ?? '') ?: '-' }}</td>
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $int['pais'] ?? '-' }}</td>
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $int['institucion'] ?? '-' }}</td>
                                @else
                                <td class="px-4 py-2 text-gray-900 dark:text-white">{{ $int['nombre'] ?: 'Integrante #'.($int['integrante_internacional_id'] ?? '-') }}</td>
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $int['rtn'] ?? '-' }}</td>
                                <td class="px-4 py-2 {{ in_array($int['sexo'] ?? '', ['masculino', 'femenino'], true) ? 'text-gray-700 dark:text-gray-300' : 'text-red-600 font-medium' }}">
                                    {{ ($int['sexo'] ?? '') === 'masculino' ? 'Masculino' : (($int['sexo'] ?? '') === 'femenino' ? 'Femenino' : 'Sin registrar') }}
                                </td>
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $int['pais'] ?? '-' }}</td>
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $int['institucion'] ?? '-' }}</td>
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ !empty($int['nivel_academico_nombre']) ? $int['nivel_academico_nombre'] : 'Sin registrar' }}</td>
                                @endif
                                <td class="px-4 py-2 text-right space-x-2">
                                    <button wire:click="openInternacionalModal({{ $i }})" type="button" class="text-xs text-blue-600 hover:text-blue-800">Editar</button>
                                    <button wire:click="removeInternacional({{ $i }})" type="button" class="text-xs text-red-600 hover:text-red-800">Eliminar</button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @error('integrante_internacional_proyecto')
                    <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                @enderror
                @else
                <p class="text-sm text-gray-500 text-center py-4 border border-dashed border-gray-300 dark:border-gray-600 rounded-lg">Sin integrantes internacionales.</p>
                @endif
            </div>

            {{-- Sección III del formato: estudiantes (ítem 12) separados del voluntariado de personal e internacional (13 y 14) --}}
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $esVoluntariado ? 'Participación de la comunidad universitaria' : 'Participación de estudiantes y voluntarios' }}</p>
            </div>

            {{-- Participación de Estudiantes --}}
            <div>
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Participación de Estudiantes UNAH <span class="text-red-500">*</span></h4>
                        <p class="text-xs text-gray-500 mt-0.5">Solo estudiantes UNAH. Debe agregar al menos un grupo para continuar.</p>
                    </div>
                    <button wire:click="openEstudianteModal" type="button"
                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                        + Agregar grupo
                    </button>
                </div>
                @error('estudiante_proyecto') <p class="text-red-500 text-xs mb-2">{{ $message }}</p> @enderror
                @if(count($estudiante_proyecto))
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="min-w-full text-sm divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Tipo Participación</th>
                                <th class="px-4 py-2 text-center text-xs font-semibold text-gray-500">Hombres</th>
                                <th class="px-4 py-2 text-center text-xs font-semibold text-gray-500">Mujeres</th>
                                <th class="px-4 py-2 text-center text-xs font-semibold text-gray-500">Total</th>
                                <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($estudiante_proyecto as $i => $est)
                            <tr>
                                <td class="px-4 py-2 text-gray-900 dark:text-white">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                        {{ $tiposParticipacionEstudiante[$est['tipo_participacion_estudiante'] ?? ''] ?? ($est['tipo_participacion_estudiante'] ?: '-') }}
                                    </span>
                                    @if(($est['tipo_participacion_estudiante'] ?? '') === 'Practica Asignatura')
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        {{ $asignaturasOpciones[$est['asignatura_id'] ?? ''] ?? 'Asignatura pendiente' }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ $periodosAcademicos[$est['periodo_academico_id'] ?? ''] ?? ($est['periodo_academico_id'] ?? 'Periodo pendiente') }}
                                    </p>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-center text-gray-700 dark:text-gray-300">{{ $est['cantidad_estudiantes_hombres'] ?? 0 }}</td>
                                <td class="px-4 py-2 text-center text-gray-700 dark:text-gray-300">{{ $est['cantidad_estudiantes_mujeres'] ?? 0 }}</td>
                                <td class="px-4 py-2 text-center font-semibold text-gray-900 dark:text-white">{{ $est['total_estudiantes'] ?? 0 }}</td>
                                <td class="px-4 py-2 text-right space-x-2">
                                    <button wire:click="openEstudianteModal({{ $i }})" type="button" class="text-xs text-blue-600 hover:text-blue-800">Editar</button>
                                    <button wire:click="removeEstudiante({{ $i }})" type="button" class="text-xs text-red-600 hover:text-red-800">Eliminar</button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{-- Desglose del tipo de participación de estudiantes, como en la ficha --}}
                @php
                    $gruposEstudiantes = collect($estudiante_proyecto);
                    $desgloseEstudiantes = collect([
                        'Practica Asignatura' => 'Práctica de asignatura / posgrado',
                        'Servicio Social o PPS' => 'Servicio social o PPS',
                        'Voluntariado' => 'Voluntariado',
                    ])->map(function ($etiqueta, $tipo) use ($gruposEstudiantes) {
                        $grupos = $gruposEstudiantes->where('tipo_participacion_estudiante', $tipo);

                        return ['etiqueta' => $etiqueta, 'hombres' => $grupos->sum('cantidad_estudiantes_hombres'), 'mujeres' => $grupos->sum('cantidad_estudiantes_mujeres')];
                    })->push([
                        'etiqueta' => 'Total estudiantes',
                        'hombres' => $gruposEstudiantes->sum('cantidad_estudiantes_hombres'),
                        'mujeres' => $gruposEstudiantes->sum('cantidad_estudiantes_mujeres'),
                    ]);
                @endphp
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mt-3">
                    @foreach($desgloseEstudiantes as $desglose)
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3 {{ $loop->last ? 'bg-gray-50 dark:bg-gray-800' : '' }}">
                        <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">{{ $desglose['etiqueta'] }}</p>
                        <div class="grid grid-cols-2 gap-2 text-center">
                            <div>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400">Hombres</p>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $desglose['hombres'] }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400">Mujeres</p>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $desglose['mujeres'] }}</p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-sm text-red-500 text-center py-4 border border-dashed border-red-300 rounded-lg">Sin grupos de estudiantes agregados. Este paso es obligatorio.</p>
                @endif
            </div>

            {{-- Voluntariado internacional: ítem 14 del FORM-DVUS-001 (opcional), 16 del FORM-DVUS-015 (obligatorio) --}}
            @include('livewire.proyectos.vinculacion.partials.voluntariado-bloque', ['bloque' => [
                'titulo' => 'Voluntariado internacional',
                'subtitulo' => 'Desglose del voluntariado internacional (cantidad)',
                'columnas' => \App\Models\Proyecto\Proyecto::VOLUNTARIADO_INTERNACIONAL,
                'automatico' => false,
            ]])
        </div>

        {{-- Modal: Buscar y seleccionar empleado --}}
        @if($showEmpleadoModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog">
            <div class="fixed inset-0 bg-black/50"></div>
            <div class="relative flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-2xl rounded-lg bg-white dark:bg-gray-900 shadow-xl border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-5 py-3">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Seleccionar Empleado Integrante</h4>
                        <button wire:click="closeEmpleadoModal" type="button" class="text-gray-500 hover:text-gray-800 dark:hover:text-gray-200 text-lg leading-none">✕</button>
                    </div>
                    <div class="p-4">
                        <input type="text" wire:model.live.debounce.300ms="empleadoModalSearch"
                            placeholder="Buscar por nombre o número de empleado..."
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500 mb-3" />
                        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700 max-h-72 overflow-y-auto">
                            <table class="min-w-full text-sm divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-800 sticky top-0">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">N° Empleado</th>
                                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Nombre</th>
                                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Tipo</th>
                                        <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500"></th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                                    @forelse($empleadosModal as $emp)
                                    <tr class="hover:bg-blue-50 dark:hover:bg-blue-900/10">
                                        <td class="px-4 py-2 text-gray-500 font-mono text-xs">{{ $emp->numero_empleado ?? '-' }}</td>
                                        <td class="px-4 py-2 text-gray-900 dark:text-white">{{ $emp->nombre_completo }}</td>
                                        <td class="px-4 py-2 text-xs text-gray-500">{{ $emp->tipo_empleado ?? '-' }}</td>
                                        <td class="px-4 py-2 text-right">
                                            <button wire:click="selectEmpleadoFromModal({{ $emp->id }}, '{{ addslashes($emp->nombre_completo) }}')" type="button"
                                                class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded bg-blue-600 text-white hover:bg-blue-700">
                                                Seleccionar
                                            </button>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">{{ empty($empleadoModalSearch) ? 'Escriba para buscar empleados...' : 'Sin resultados.' }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="flex justify-end border-t border-gray-200 dark:border-gray-700 px-5 py-3">
                        <button wire:click="closeEmpleadoModal" type="button" class="px-3 py-1.5 text-xs font-medium rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Modal: Agregar/Editar grupo de estudiantes --}}
        @if($showEstudianteModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog">
            <div class="fixed inset-0 bg-black/50"></div>
            <div class="relative flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg rounded-lg bg-white dark:bg-gray-900 shadow-xl border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-5 py-3">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $editEstudianteIndex !== null ? 'Editar' : 'Agregar' }} Grupo de Estudiantes
                        </h4>
                        <button wire:click="closeEstudianteModal" type="button" class="text-gray-500 hover:text-gray-800 text-lg leading-none">✕</button>
                    </div>
                    <div class="p-5 space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Tipo de Participación <span class="text-red-500">*</span></label>
                            <select wire:model.live="nuevoEstudiante.tipo_participacion_estudiante"
                                class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500">
                                <option value="">Seleccione...</option>
                                @foreach($tiposParticipacionEstudiante as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('nuevoEstudiante.tipo_participacion_estudiante') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        @if(($nuevoEstudiante['tipo_participacion_estudiante'] ?? '') === 'Practica Asignatura')
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <div class="mb-1 flex items-center justify-between gap-3">
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Asignatura <span class="text-red-500">*</span></label>
                                    @if(!empty($carreras))
                                        <button wire:click="{{ $showCrearAsignaturaInline ? 'closeCrearAsignaturaInline' : 'openCrearAsignaturaInline' }}" type="button" class="text-[11px] font-medium text-blue-600 hover:text-blue-800">
                                            {{ $showCrearAsignaturaInline ? 'Ocultar formulario' : '+ Nueva asignatura' }}
                                        </button>
                                    @endif
                                </div>
                                <select wire:model.live="nuevoEstudiante.asignatura_id"
                                    @disabled(empty($carreras) || empty($asignaturasOpciones))
                                    class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500 disabled:bg-gray-100 disabled:text-gray-500 dark:disabled:bg-gray-800/60">
                                    @if(empty($carreras))
                                        <option value="">Seleccione primero una carrera en Información General</option>
                                    @elseif(empty($asignaturasOpciones))
                                        <option value="">No hay asignaturas para la carrera seleccionada</option>
                                    @else
                                        <option value="">Seleccione...</option>
                                        @foreach($asignaturasOpciones as $id => $nombre)
                                            <option value="{{ $id }}">{{ $nombre }}</option>
                                        @endforeach
                                    @endif
                                </select>
                                @error('nuevoEstudiante.asignatura_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                @if(empty($asignaturasOpciones) && !empty($carreras))
                                    <p class="text-xs text-amber-600 mt-1">Cree una asignatura asociada a una de las carreras seleccionadas para poder usarla aquí.</p>
                                @endif
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ $esVoluntariado ? 'Período académico' : 'Periodo Académico' }} <span class="text-red-500">*</span></label>
                                <select wire:model.live="nuevoEstudiante.periodo_academico_id" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500">
                                    <option value="">Seleccione...</option>
                                    @forelse($periodosAcademicos as $id => $nombre)
                                        <option value="{{ $id }}">{{ $nombre }}</option>
                                    @empty
                                        <option value="" disabled>No hay periodos registrados</option>
                                    @endforelse
                                </select>
                                @error('nuevoEstudiante.periodo_academico_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        @if($showCrearAsignaturaInline)
                        <div class="rounded-lg border border-blue-200 bg-blue-50/70 dark:border-blue-800 dark:bg-blue-900/10 p-3 space-y-3">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Carrera <span class="text-red-500">*</span></label>
                                    <select wire:model="nuevaAsignaturaCarreraId" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500">
                                        <option value="">Seleccione...</option>
                                        @foreach($carrerasSeleccionadas as $id => $nombre)
                                            <option value="{{ $id }}">{{ $nombre }}</option>
                                        @endforeach
                                    </select>
                                    @error('nuevaAsignaturaCarreraId') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Codigo</label>
                                    <input type="text" wire:model="nuevaAsignaturaCodigo" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500" />
                                    @error('nuevaAsignaturaCodigo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Nombre <span class="text-red-500">*</span></label>
                                    <input type="text" wire:model="nuevaAsignaturaNombre" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500" />
                                    @error('nuevaAsignaturaNombre') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div class="flex justify-end">
                                <button wire:click="crearAsignaturaInline" type="button" class="px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">Crear asignatura</button>
                            </div>
                        </div>
                        @endif
                        @endif
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Cantidad Hombres</label>
                                <input type="number" wire:model="nuevoEstudiante.cantidad_estudiantes_hombres" min="0"
                                    class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500" />
                                @error('nuevoEstudiante.cantidad_estudiantes_hombres') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Cantidad Mujeres</label>
                                <input type="number" wire:model="nuevoEstudiante.cantidad_estudiantes_mujeres" min="0"
                                    class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500" />
                                @error('nuevoEstudiante.cantidad_estudiantes_mujeres') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <p class="text-xs text-gray-500">
                            Total: <strong>{{ (int)($nuevoEstudiante['cantidad_estudiantes_hombres'] ?? 0) + (int)($nuevoEstudiante['cantidad_estudiantes_mujeres'] ?? 0) }}</strong> estudiantes
                        </p>
                        @error('nuevoEstudiante.total_estudiantes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex justify-end gap-2 border-t border-gray-200 dark:border-gray-700 px-5 py-3">
                        <button wire:click="closeEstudianteModal" type="button" class="px-3 py-1.5 text-xs font-medium rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 hover:bg-gray-200">Cancelar</button>
                        <button wire:click="saveEstudiante" type="button" class="px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">Guardar</button>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Modal: Crear / Seleccionar integrante internacional --}}
        @if($showInternacionalModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog">
            <div class="fixed inset-0 bg-black/50"></div>
            <div class="relative flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-2xl rounded-lg bg-white dark:bg-gray-900 shadow-xl border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-5 py-3">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                            @if($esVoluntariado)
                                {{ $editIntegranteInternacionalIndex !== null ? 'Editar integrante del equipo de cooperación internacional' : 'Crear / Seleccionar integrante del equipo de cooperación internacional' }}
                            @else
                                {{ $editIntegranteInternacionalIndex !== null ? 'Editar Docente Internacional' : 'Crear / Seleccionar Docente Internacional' }}
                            @endif
                        </h4>
                        <button wire:click="closeInternacionalModal" type="button" class="text-gray-500 hover:text-gray-800 text-lg leading-none">✕</button>
                    </div>
                    <div class="p-5 space-y-4">
                        @if($editIntegranteInternacionalIndex === null)
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                            <h5 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3">Seleccionar integrante existente</h5>
                            <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-3 items-start">
                                <div>
                                    <label class="sr-only" for="integrante-internacional-existente">Seleccione un integrante internacional</label>
                                    <select id="integrante-internacional-existente" wire:model="integranteInternacionalSeleccionadoId"
                                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500">
                                        <option value="">Seleccione un integrante internacional</option>
                                        @foreach($internacionales as $id => $nombre)
                                            <option value="{{ $id }}">{{ $nombre }}</option>
                                        @endforeach
                                    </select>
                                    @error('integranteInternacionalSeleccionadoId') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <button wire:click="agregarIntegranteInternacionalExistente" type="button"
                                    class="inline-flex items-center justify-center px-3 py-2 text-xs font-medium rounded-md bg-orange-600 text-white hover:bg-orange-700">
                                    Agregar seleccionado
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div>
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">O crear nuevo integrante</span>
                            <div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div>
                        </div>
                        @endif

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Nombre completo <span class="text-red-500">*</span></label>
                                <input type="text" wire:model.live.debounce.1000ms="nuevoIntegranteInternacional.nombre_completo" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500" />
                                @error('nuevoIntegranteInternacional.nombre_completo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ $esVoluntariado ? 'Pasaporte' : 'Pasaporte / Documento' }} <span class="text-red-500">*</span></label>
                                <input type="text" wire:model.live.debounce.1000ms="nuevoIntegranteInternacional.documento_identidad" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500" />
                                @error('nuevoIntegranteInternacional.documento_identidad') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">RTN / identificador fiscal <span class="text-xs text-gray-400">(opcional; RTN Honduras: 14 dígitos)</span></label>
                                <input type="text" wire:model.live.debounce.1000ms="nuevoIntegranteInternacional.rtn" maxlength="50" placeholder="Identificador fiscal" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500" />
                                @error('nuevoIntegranteInternacional.rtn') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Sexo <span class="text-red-500">*</span></label>
                                <select wire:model.live="nuevoIntegranteInternacional.sexo" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500">
                                    <option value="">Seleccione el sexo</option>
                                    <option value="masculino">Masculino</option>
                                    <option value="femenino">Femenino</option>
                                </select>
                                @error('nuevoIntegranteInternacional.sexo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Correo electrónico <span class="text-red-500">*</span></label>
                                <input type="email" wire:model.live.debounce.1000ms="nuevoIntegranteInternacional.email" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500" />
                                @error('nuevoIntegranteInternacional.email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">País <span class="text-red-500">*</span></label>
                                <select wire:model.live="nuevoIntegranteInternacional.pais" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500">
                                    <option value="">Seleccione un país</option>
                                    @foreach($paises as $id => $nombre)
                                        <option value="{{ $nombre }}">{{ $nombre }}</option>
                                    @endforeach
                                </select>
                                @error('nuevoIntegranteInternacional.pais') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ $esVoluntariado ? 'Universidad' : 'Institución' }} <span class="text-red-500">*</span></label>
                                <input type="text" wire:model.live.debounce.1000ms="nuevoIntegranteInternacional.institucion" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500" />
                                @error('nuevoIntegranteInternacional.institucion') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Nivel Académico <span class="text-red-500">*</span></label>
                                <select wire:model.live="nuevoIntegranteInternacional.nivel_academico_id" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500">
                                    <option value="">Seleccione un nivel académico</option>
                                    @foreach($nivelesAcademicos as $id => $nombre)
                                        <option value="{{ $id }}">{{ $nombre }}</option>
                                    @endforeach
                                </select>
                                @error('nuevoIntegranteInternacional.nivel_academico_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-gray-200 dark:border-gray-700 px-5 py-3">
                        <button wire:click="closeInternacionalModal" type="button" class="px-3 py-1.5 text-xs font-medium rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 hover:bg-gray-200">Cancelar</button>
                        <button wire:click="saveNuevoIntegranteInternacional" type="button" class="px-3 py-1.5 text-xs font-medium rounded-md bg-orange-600 text-white hover:bg-orange-700">
                            {{ $editIntegranteInternacionalIndex !== null ? 'Actualizar docente' : 'Guardar integrante' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endif
        @endif

        {{-- ══════════════════ PASO 3: Entidades Contraparte ══════════════════ --}}
        @if($currentStep === 3)
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Paso 3: {{ $esVoluntariado ? 'Información de la Entidad Contraparte' : 'Información de la Entidad Contraparte del Proyecto' }}</h3>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-500">Si existe más de una contraparte, añada una entidad por cada una de ellas.</p>
                <button wire:click="openContraparteModal" type="button"
                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                    + Agregar entidad
                </button>
            </div>

            @php
                $contrapartesConNombre = collect($entidad_contraparte)->filter(fn($contraparte) => !empty($contraparte['nombre'] ?? null));
                $instrumentoLabels = [
                    'carta_formal_solicitud' => 'Carta formal de solicitud a la unidad académica',
                    'carta_intenciones' => 'Carta de intenciones con la UNAH',
                    'convenio_marco' => 'Convenio marco con la UNAH',
                ];
                $tipoContraparteLabels = \App\Models\Proyecto\EntidadContraparte::TIPOS;
                $contraparteDocErrors = collect($errors->keys())->filter(fn($k) => preg_match('/^entidad_contraparte\.\d+$/', $k));
            @endphp

            @if($contraparteDocErrors->isNotEmpty())
            <div class="rounded-md border border-red-300 bg-red-50 dark:bg-red-900/20 dark:border-red-700 px-3 py-2">
                @foreach($contraparteDocErrors as $errorKey)
                    @foreach($errors->get($errorKey) as $mensaje)
                    <p class="text-red-600 dark:text-red-400 text-xs">{{ $mensaje }}</p>
                    @endforeach
                @endforeach
            </div>
            @endif

            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Nombre</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">RTN</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Tipo de contraparte</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Contacto directo</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Cargo</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Teléfono</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Correo</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Instrumentos</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-700 dark:bg-gray-900">
                        @forelse($contrapartesConNombre as $ci => $contraparte)
                            <tr class="align-top hover:bg-gray-50 dark:hover:bg-gray-800/70">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                    {{ $contraparte['nombre'] ?? 'Sin nombre' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                    {{ !empty($contraparte['rtn'] ?? null) ? $contraparte['rtn'] : '-' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                    {{ !empty($contraparte['tipo_entidad']) ? ($tipoContraparteLabels[$contraparte['tipo_entidad']] ?? ucfirst(str_replace('_', ' ', $contraparte['tipo_entidad']))) : 'No especificado' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                    {{ !empty($contraparte['nombre_contacto'] ?? null) ? $contraparte['nombre_contacto'] : 'No especificado' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                    {{ !empty($contraparte['cargo_contacto'] ?? null) ? $contraparte['cargo_contacto'] : 'No especificado' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                    {{ !empty($contraparte['telefono'] ?? null) ? $contraparte['telefono'] : 'No especificado' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                    {{ !empty($contraparte['correo'] ?? null) ? $contraparte['correo'] : 'No especificado' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                    @if(count($contraparte['instrumento_formalizacion'] ?? []))
                                        <ul class="space-y-1">
                                            @foreach($contraparte['instrumento_formalizacion'] as $inst)
                                                @if(!empty($inst['tipo_documento']))
                                                    @php
                                                        $documentoUrl = $this->instrumentoDocumentoUrl($inst['id'] ?? null, $inst['documento_url'] ?? null);
                                                    @endphp
                                                    <li>
                                                        <span class="block text-xs font-medium text-gray-700 dark:text-gray-200">
                                                            {{ $instrumentoLabels[$inst['tipo_documento']] ?? ucfirst(str_replace('_', ' ', $inst['tipo_documento'])) }}
                                                        </span>
                                                        @if($documentoUrl)
                                                            <a href="{{ $documentoUrl }}" target="_blank" rel="noopener" class="text-xs font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                                                Ver documento
                                                            </a>
                                                        @endif
                                                    </li>
                                                @endif
                                            @endforeach
                                        </ul>
                                    @else
                                        <span class="text-xs font-medium text-red-600 dark:text-red-400">Falta instrumento (obligatorio)</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button wire:click="openContraparteModal({{ $ci }})" type="button"
                                            class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400">
                                            Editar
                                        </button>
                                        <button type="button" x-on:click.prevent="confirmDialog('¿Eliminar esta entidad contraparte?', { type: 'danger' }).then((ok) => ok && $wire.removeContraparte({{ $ci }}))" type="button"
                                            class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded bg-red-50 text-red-700 hover:bg-red-100 dark:bg-red-900/30 dark:text-red-400">
                                            Eliminar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                    No hay entidades contraparte agregadas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Modal: Crear/Editar Contraparte --}}
        @if($showContraparteModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog">
            <div class="fixed inset-0 bg-black/50"></div>
            <div class="relative flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-2xl rounded-lg bg-white dark:bg-gray-900 shadow-xl border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-5 py-3">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $editContraparteIndex !== null ? 'Editar' : 'Nueva' }} Entidad Contraparte
                        </h4>
                        <button wire:click="closeContraparteModal" type="button" class="text-gray-500 hover:text-gray-800 text-lg leading-none">✕</button>
                    </div>
                    <div class="p-5 space-y-3 max-h-[70vh] overflow-y-auto">
                        {{-- Seleccionar contraparte existente o crear una nueva (solo al agregar) --}}
                        @if($editContraparteIndex === null)
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                            <h5 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3">Seleccionar contraparte existente</h5>
                            <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-3 items-start">
                                <div wire:key="buscador-contraparte" x-data="{
                                    open: false,
                                    search: '',
                                    selected: @js(filled($contraparteSeleccionadoId) ? (string) $contraparteSeleccionadoId : ''),
                                    options: @js($contrapartesExistentes),
                                    tipos: @js($tipoContraparteLabels),
                                    normalizar(texto) { return String(texto ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim(); },
                                    filtradas() {
                                        const term = this.normalizar(this.search);
                                        return this.options.filter(option => !term || this.normalizar(option.nombre).includes(term));
                                    },
                                    etiqueta(option) { return option ? option.nombre + ' (' + (this.tipos[option.tipo] ?? option.tipo) + ')' : ''; },
                                    seleccionada() { return this.options.find(option => option.id === this.selected); },
                                    elegir(option) {
                                        this.selected = option.id;
                                        this.search = '';
                                        this.open = false;
                                        this.$wire.set('contraparteSeleccionadoId', option.id, false);
                                    }
                                }" @click.outside="open = false">
                                    <label class="sr-only" for="contraparte-existente">Buscar contraparte por nombre</label>
                                    <div class="relative">
                                        <input id="contraparte-existente" type="text" autocomplete="off" x-model="search"
                                            @focus="open = true" @click="open = true" @input="open = true" @keydown.escape="open = false"
                                            @keydown.enter.prevent="filtradas().length && elegir(filtradas()[0])"
                                            :placeholder="seleccionada() ? etiqueta(seleccionada()) : 'Buscar contraparte por nombre...'"
                                            :class="seleccionada() ? 'placeholder:text-gray-900 dark:placeholder:text-white' : 'placeholder:text-gray-400'"
                                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 pl-3 pr-8 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500" />
                                        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-gray-400" x-text="open ? '▴' : '▾'"></span>
                                    </div>
                                    <div x-show="open" x-cloak class="mt-1 max-h-56 overflow-y-auto rounded-md border border-blue-200 bg-white shadow-sm dark:border-blue-700 dark:bg-gray-800">
                                        <template x-if="filtradas().length === 0">
                                            <div class="px-3 py-2 text-sm text-gray-500">Sin resultados. Si no existe, use «Crear contraparte».</div>
                                        </template>
                                        <template x-for="option in filtradas()" :key="option.id">
                                            <div @click="elegir(option)" class="px-3 py-2 text-sm cursor-pointer hover:bg-blue-50 dark:hover:bg-gray-700"
                                                :class="option.id === selected ? 'bg-blue-50 text-blue-700 font-medium dark:bg-blue-900/30 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300'">
                                                <span x-text="etiqueta(option)"></span>
                                            </div>
                                        </template>
                                    </div>
                                    @error('contraparteSeleccionadoId') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <button wire:click="agregarContraparteExistente" type="button"
                                    class="inline-flex items-center justify-center px-3 py-2 text-xs font-medium rounded-md bg-orange-600 text-white hover:bg-orange-700">
                                    Usar seleccionada
                                </button>
                            </div>
                            <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <p class="text-xs text-gray-500 dark:text-gray-400">Busque la contraparte por nombre y presione «Usar seleccionada». Si no existe, presione «Crear contraparte».</p>
                                <button wire:click="crearContraparteNueva" type="button"
                                    class="inline-flex shrink-0 items-center justify-center px-3 py-2 text-xs font-medium rounded-md border border-blue-600 text-blue-700 hover:bg-blue-50 dark:border-blue-400 dark:text-blue-300 dark:hover:bg-blue-900/30">
                                    + Crear contraparte
                                </button>
                            </div>
                        </div>
                        @endif

                        @if($modoContraparte)
                        @php
                            $soloLectura = $modoContraparte === 'existente';
                            $asterisco = $soloLectura ? '' : '<span class="text-red-500">*</span>';
                            $claseCampo = 'w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-1.5 text-sm '
                                . ($soloLectura ? 'bg-gray-100 text-gray-600 cursor-not-allowed dark:bg-gray-800/60 dark:text-gray-400' : 'bg-white dark:bg-gray-800 focus:border-blue-500');
                        @endphp
                        @if($soloLectura)
                        <div class="rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-600 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-300">
                            Los datos de la contraparte vienen del catálogo y no se pueden modificar. Complete los compromisos asumidos y el instrumento que da lugar a la alianza.
                        </div>
                        @endif

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @php
                                // El RTN se escribe al crear, o si la contraparte del catálogo no lo tiene.
                                $rtnEditable = !$soloLectura || $contraparteSinRtn;
                            @endphp
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">RTN / identificador fiscal @if($rtnEditable && !$esVoluntariado)<span class="text-red-500">*</span>@endif @if($rtnEditable && $esVoluntariado)<span class="text-xs text-gray-400">(opcional)</span>@endif</label>
                                <input type="text" wire:model="nuevaContraparte.rtn" maxlength="50" @readonly(!$rtnEditable) placeholder="{{ $rtnEditable ? 'Identificador fiscal' : 'Sin registrar' }}"
                                    class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-1.5 text-sm {{ $rtnEditable ? 'bg-white dark:bg-gray-800 focus:border-blue-500' : 'bg-gray-100 text-gray-600 cursor-not-allowed dark:bg-gray-800/60 dark:text-gray-400' }}" />
                                @if($soloLectura && $contraparteSinRtn)
                                    <p class="text-xs text-amber-700 dark:text-amber-400 mt-1">Esta contraparte no tiene RTN registrado. Escríbalo y se guardará en el catálogo.</p>
                                @endif
                                @error('nuevaContraparte.rtn') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Nombre {!! $asterisco !!}</label>
                                <input type="text" wire:model="nuevaContraparte.nombre" @readonly($soloLectura) class="{{ $claseCampo }}" />
                                @error('nuevaContraparte.nombre') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Tipo de Contraparte {!! $asterisco !!}</label>
                                <select wire:model="nuevaContraparte.tipo_entidad" @disabled($soloLectura) class="{{ $claseCampo }}">
                                    <option value="">{{ $soloLectura ? 'Sin registrar' : 'Seleccione...' }}</option>
                                    @foreach($tipoContraparteLabels as $valorTipo => $etiquetaTipo)
                                        <option value="{{ $valorTipo }}">{{ $etiquetaTipo }}</option>
                                    @endforeach
                                </select>
                                @error('nuevaContraparte.tipo_entidad') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Nombre del Contacto Directo {!! $asterisco !!}</label>
                                <input type="text" wire:model="nuevaContraparte.nombre_contacto" @readonly($soloLectura) placeholder="{{ $soloLectura ? 'Sin registrar' : '' }}" class="{{ $claseCampo }}" />
                                @error('nuevaContraparte.nombre_contacto') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ $esVoluntariado ? 'Cargo del contacto' : 'Cargo del Contacto del Proyecto' }} {!! $asterisco !!}</label>
                                <input type="text" wire:model="nuevaContraparte.cargo_contacto" @readonly($soloLectura) placeholder="{{ $soloLectura ? 'Sin registrar' : '' }}" class="{{ $claseCampo }}" />
                                @error('nuevaContraparte.cargo_contacto') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Teléfono {!! $asterisco !!}</label>
                                <input type="text" wire:model="nuevaContraparte.telefono" @readonly($soloLectura) placeholder="{{ $soloLectura ? 'Sin registrar' : '' }}" class="{{ $claseCampo }}" />
                                @error('nuevaContraparte.telefono') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Correo Electrónico {!! $asterisco !!}</label>
                                <input type="email" wire:model="nuevaContraparte.correo" @readonly($soloLectura) placeholder="{{ $soloLectura ? 'Sin registrar' : '' }}" class="{{ $claseCampo }}" />
                                @error('nuevaContraparte.correo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Breve Descripción de los Compromisos Asumidos por la Contraparte <span class="text-red-500">*</span></label>
                                <textarea wire:model="nuevaContraparte.descripcion_acuerdos" rows="2" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500"></textarea>
                                @error('nuevaContraparte.descripcion_acuerdos') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        {{-- Instrumentos de formalización --}}
                        <div class="mt-2">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-xs font-semibold text-gray-600 dark:text-gray-400">Tipo de Instrumento que da Lugar a la Alianza <span class="text-red-500">*</span></p>
                                <button wire:click="addInstrumentoToModal" type="button" class="text-xs text-blue-600 hover:text-blue-800">+ Agregar</button>
                            </div>
                            @error('nuevaContraparte.instrumento_formalizacion') <p class="text-red-500 text-xs mb-2">{{ $message }}</p> @enderror
                            @foreach($nuevaContraparte['instrumento_formalizacion'] ?? [] as $ii => $inst)
                            <div class="mb-2 p-3 bg-gray-50 dark:bg-gray-800 rounded-md border border-gray-200 dark:border-gray-600">
                                <div class="grid grid-cols-1 sm:grid-cols-[1fr_1fr_auto] gap-2 items-start">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Tipo de instrumento <span class="text-red-500">*</span></label>
                                        <select wire:model="nuevaContraparte.instrumento_formalizacion.{{ $ii }}.tipo_documento" class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-sm focus:border-blue-500">
                                            <option value="">Tipo de documento...</option>
                                            <option value="carta_formal_solicitud">Carta formal de solicitud a la unidad académica</option>
                                            <option value="carta_intenciones">Carta de intenciones con la UNAH</option>
                                            <option value="convenio_marco">Convenio marco con la UNAH</option>
                                        </select>
                                        @error("nuevaContraparte.instrumento_formalizacion.$ii.tipo_documento") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Documento <span class="text-red-500">*</span></label>
                                        <input id="instrumento-documento-{{ $ii }}" type="file" wire:model="nuevaContraparte.instrumento_formalizacion.{{ $ii }}.documento_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="hidden" />
                                        <label for="instrumento-documento-{{ $ii }}" class="inline-flex cursor-pointer items-center rounded-md bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/50">
                                            Seleccionar archivo
                                        </label>
                                        <div wire:loading wire:target="nuevaContraparte.instrumento_formalizacion.{{ $ii }}.documento_file" class="mt-1 text-xs text-blue-600">
                                            Cargando documento...
                                        </div>
                                        @error("nuevaContraparte.instrumento_formalizacion.$ii.documento_file") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <button wire:click="removeInstrumentoFromModal({{ $ii }})" type="button" class="mt-6 text-xs text-red-600 hover:text-red-800 whitespace-nowrap">Eliminar</button>
                                </div>
                                @php
                                    $documentoActualUrl = $this->instrumentoDocumentoUrl($inst['id'] ?? null, $inst['documento_url'] ?? null);
                                    $documentoSeleccionado = $inst['documento_file'] ?? null;
                                    $tieneDocumentoGuardado = !empty($inst['documento_url'] ?? null);
                                @endphp
                                @if(is_object($documentoSeleccionado))
                                    <p class="mt-1 text-xs text-green-700 dark:text-green-400">
                                        Documento seleccionado: {{ method_exists($documentoSeleccionado, 'getClientOriginalName') ? $documentoSeleccionado->getClientOriginalName() : 'archivo listo para guardar' }}
                                    </p>
                                @elseif($tieneDocumentoGuardado)
                                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                                        <span class="rounded bg-green-100 px-2 py-1 font-medium text-green-700 dark:bg-green-900/40 dark:text-green-300">
                                            Documento cargado: {{ $this->instrumentoDocumentoNombre($inst['documento_url'] ?? null, $inst['nombre_archivo'] ?? null) }}
                                        </span>
                                        @if($documentoActualUrl)
                                            <a href="{{ $documentoActualUrl }}" target="_blank" rel="noopener" class="font-medium text-blue-600 hover:text-blue-800">
                                                Ver documento actual
                                            </a>
                                        @endif
                                    </div>
                                @else
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">No hay documento cargado</p>
                                @endif
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    <div class="flex justify-end gap-2 border-t border-gray-200 dark:border-gray-700 px-5 py-3">
                        <button wire:click="closeContraparteModal" type="button" class="px-3 py-1.5 text-xs font-medium rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 hover:bg-gray-200">Cancelar</button>
                        @if($modoContraparte)
                        <button wire:click="saveContraparte" wire:loading.attr="disabled" wire:target="nuevaContraparte.instrumento_formalizacion" type="button" class="px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60">Guardar</button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif
        @endif

        {{-- ══════════════════ PASO 4: Actividades ══════════════════ --}}
        @if($currentStep === 4)
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Paso 4: Cronograma de las Actividades del Proyecto</h3>
        <div class="space-y-4">
            <div class="flex items-center justify-between gap-4">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Descripción de todas las actividades enmarcadas en el proyecto, las cuales pueden ser, entre otras, la negociación inicial, la organización de los equipos de trabajo, la planificación, el desarrollo de actividades de capacitación y fortalecimiento, presentación de informe intermedio o parciales, presentación del informe final, proceso de evaluación, proceso de sistematización, publicación de artículo, otras acciones de divulgación.
                </p>
                <button wire:click="openActividadModal" type="button"
                    class="inline-flex shrink-0 items-center px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                    + Agregar actividad
                </button>
            </div>

            @php
                $actividadesConDescripcion = collect($actividades)->filter(fn($actividad) => !empty($actividad['descripcion'] ?? null));
            @endphp

            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">No.</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Actividad</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Fecha inicio</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Fecha fin</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Horas requeridas</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Responsable</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-700 dark:bg-gray-900">
                        @forelse($actividadesConDescripcion as $i => $actividad)
                            <tr class="align-top hover:bg-gray-50 dark:hover:bg-gray-800/70">
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                    {{ $loop->iteration }}
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                    {{ $actividad['descripcion'] ?? 'Sin descripción' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                    {{ !empty($actividad['fecha_inicio'] ?? null) ? $actividad['fecha_inicio'] : 'No definida' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                    {{ !empty($actividad['fecha_finalizacion'] ?? null) ? $actividad['fecha_finalizacion'] : 'No definida' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                    {{ $actividad['horas'] ?? 0 }} hrs
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                    @if(count($actividad['empleados'] ?? []))
                                        <ul class="space-y-1">
                                            @foreach($actividad['empleados'] as $empleadoId)
                                                <li class="text-xs text-gray-700 dark:text-gray-200">
                                                    {{ $responsablesOptions[$empleadoId] ?? $responsablesOptions[(int) $empleadoId] ?? "#{$empleadoId}" }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <span class="text-xs text-gray-500 dark:text-gray-400">Sin responsables</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button wire:click="openActividadModal({{ $i }})" type="button"
                                            class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400">
                                            Editar
                                        </button>
                                        <button type="button" x-on:click.prevent="confirmDialog('¿Eliminar esta actividad?', { type: 'danger' }).then((ok) => ok && $wire.removeActividad({{ $i }}))" type="button"
                                            class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded bg-red-50 text-red-700 hover:bg-red-100 dark:bg-red-900/30 dark:text-red-400">
                                            Eliminar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                    No hay actividades agregadas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Modal: Crear/Editar Actividad --}}
        @if($showActividadModal)
        @php
            $actividadFechaFinMin = data_get($nuevaActividad, 'fecha_inicio');
        @endphp
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog">
            <div class="fixed inset-0 bg-black/50"></div>
            <div class="relative flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-xl rounded-lg bg-white dark:bg-gray-900 shadow-xl border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-5 py-3">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $editActividadIndex !== null ? 'Editar' : 'Nueva' }} Actividad
                        </h4>
                        <button wire:click="closeActividadModal" type="button" class="text-gray-500 hover:text-gray-800 text-lg leading-none">✕</button>
                    </div>
                    <div class="p-5 space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Actividad <span class="text-red-500">*</span></label>
                            <textarea wire:model="nuevaActividad.descripcion" rows="3" required class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500"></textarea>
                            @error('nuevaActividad.descripcion') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Producto <span class="text-red-500">*</span></label>
                            <textarea wire:model="nuevaActividad.resultados" rows="2" required class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500"></textarea>
                            @error('nuevaActividad.resultados') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Fecha Inicio <span class="text-red-500">*</span></label>
                                <input type="date" wire:model.live="nuevaActividad.fecha_inicio" required
                                    class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500 @error('nuevaActividad.fecha_inicio') border-red-500 @enderror" />
                                @error('nuevaActividad.fecha_inicio') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Fecha Fin <span class="text-red-500">*</span></label>
                                <input type="date" wire:model.live="nuevaActividad.fecha_finalizacion" required
                                    @if($actividadFechaFinMin) min="{{ $actividadFechaFinMin }}" @endif
                                    class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500 @error('nuevaActividad.fecha_finalizacion') border-red-500 @enderror" />
                                @error('nuevaActividad.fecha_finalizacion') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Horas Requeridas <span class="text-red-500">*</span></label>
                                <input type="number" wire:model.blur.number="nuevaActividad.horas" min="1" step="1" required class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500" />
                                @error('nuevaActividad.horas') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ $esVoluntariado ? 'Responsable' : 'Responsables' }} <span class="text-red-500">*</span></label>
                            <div
                                x-data="{
                                    open: false,
                                    query: '',
                                    selected: $wire.entangle('nuevaActividad.empleados').live,
                                options: @js($responsablesOptions),
                                normalize(){this.selected=(this.selected||[]).map(String).filter(Boolean);},
                                toggle(id){this.normalize();id=String(id);const i=this.selected.indexOf(id);i===-1?this.selected=[...this.selected,id]:this.selected=this.selected.filter(x=>x!==id);},
                                remove(id){this.normalize();this.selected=this.selected.filter(x=>x!==String(id));},
                                isSelected(id){this.normalize();return this.selected.includes(String(id));},
                                getName(id){return this.options[id]??this.options[String(id)]??`#${id}`;},
                                filteredOptions(){const t=this.query.toLowerCase();return Object.entries(this.options).filter(([i,n])=>String(n).toLowerCase().includes(t));}
                            }" x-init="normalize()" @click.outside="open=false" class="relative">
                                <div class="min-h-[42px] w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-2 py-2 cursor-text flex flex-wrap gap-1.5 items-center @error('nuevaActividad.empleados') border-red-500 @enderror" @click="open=true">
                                    <template x-for="id in selected" :key="id">
                                        <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 dark:bg-blue-900/30 px-2 py-0.5 text-xs font-medium text-blue-700 dark:text-blue-200">
                                            <span x-text="getName(id)"></span>
                                            <button type="button" @click.stop="remove(id)" class="font-bold text-blue-500 hover:text-blue-800">×</button>
                                        </span>
                                    </template>
                                    <input type="text" x-model="query" @focus="open=true" class="min-w-[160px] flex-1 border-0 bg-transparent px-1 py-0.5 text-sm text-gray-900 dark:text-white placeholder:text-gray-400 focus:ring-0" placeholder="Buscar responsables..." />
                                </div>
                                <div x-show="open" x-cloak class="absolute z-40 mt-1 max-h-44 w-full overflow-y-auto rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg">
                                    <template x-for="[id,name] in filteredOptions()" :key="id">
                                        <button type="button" @click="toggle(id)" class="flex w-full items-center justify-between px-3 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700">
                                            <span class="text-gray-700 dark:text-gray-200" x-text="name"></span>
                                            <span x-show="isSelected(id)" class="text-xs font-semibold text-blue-600">✓</span>
                                        </button>
                                    </template>
                                    <div x-show="filteredOptions().length===0" class="px-3 py-2 text-sm text-gray-500">Sin resultados</div>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Solo integrantes del equipo ejecutor.</p>
                            @error('nuevaActividad.empleados') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-gray-200 dark:border-gray-700 px-5 py-3">
                        <button wire:click="closeActividadModal" type="button" class="px-3 py-1.5 text-xs font-medium rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 hover:bg-gray-200">Cancelar</button>
                        <button wire:click="saveActividad" type="button" class="px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">Guardar</button>
                    </div>
                </div>
            </div>
        </div>
        @endif
        @endif

        {{-- ══════════════════ PASO 5: Descripción ══════════════════ --}}
        @if($currentStep === 5)
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Paso 5: Formulación del Proyecto</h3>
        <div class="space-y-4">
            <div>
                @if($esVoluntariado)
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Antecedentes <span class="text-red-500">*</span></label>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Explicar brevemente en qué consiste el programa, los antecedentes que dieron su origen y la importancia que tiene para los objetivos estratégicos de la UNAH. Este programa es de carácter.</p>
                @else
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descripción de los Antecedentes del Proyecto <span class="text-red-500">*</span></label>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Explicar brevemente los antecedentes que dieron su origen y la importancia que tiene para los objetivos estratégicos de la UNAH.</p>
                @endif
                <textarea wire:model.live.debounce.1000ms="resumen" rows="8" class="w-full resize-y rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
                @error('resumen') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                @if($esVoluntariado)
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Descripción de las participantes</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                    Descripción breve de las unidades académicas participantes y su alineamiento con la estrategia de vinculación de la unidad. También se realizará una breve descripción de las contrapartes participantes, a qué se dedican y cómo se alinea el programa a los planes estratégicos.
                </p>
                @else
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Descripción de los Participantes del Proyecto</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                    Breve descripción de los alcances de la participación de los actores del proyecto. En el caso de la participación de la UNAH, se describirá de manera sucinta, cómo se articula el proyecto de vinculación con las funciones de la docencia (participación de asignaturas) y/o la investigación (si participa un grupo de investigación, o se generan insumos de una investigación en marcha).
                </p>
                @endif
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descripción de la participación de la UNAH en el proyecto a través de las funciones de docencia e investigación <span class="text-red-500">*</span></label>
                <textarea wire:model.live.debounce.1000ms="participacion_unah" rows="4" class="w-full resize-y rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
                @error('participacion_unah') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descripción de la participación de la entidad contraparte <span class="text-red-500">*</span></label>
                <textarea wire:model.live.debounce.1000ms="participacion_contraparte" rows="4" class="w-full resize-y rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
                @error('participacion_contraparte') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descripción de la participación de la comunidad beneficiada <span class="text-red-500">*</span></label>
                <textarea wire:model.live.debounce.1000ms="participacion_comunidad" rows="4" class="w-full resize-y rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
                @error('participacion_comunidad') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Definición del Problema <span class="text-red-500">*</span></label>
                @if($esVoluntariado)
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Breve descripción del problema que se desea resolver, indicando línea base que se tendrá en consideración para la definición de los resultados del programa.</p>
                @else
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Breve descripción del problema que se desea resolver, indicando línea base que se tendrá en consideración para la definición de los resultados del proyecto. La línea base debe representarse con datos y debe de describirse las causas del problema identificado.</p>
                @endif
                <textarea wire:model.live.debounce.1000ms="definicion_problema" rows="6" class="w-full resize-y rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
                @error('definicion_problema') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Descripción de la experiencia académica (FORM-DVUS-015 · sólo Voluntariado) --}}
            @if($esVoluntariado)
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/40 p-4 space-y-4">
                <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Descripción de la experiencia académica que se desarrollará</h4>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descripción de los conocimientos teóricos que se aplicarán <span class="text-red-500">*</span></label>
                    <textarea wire:model.live.debounce.1000ms="experiencia_conocimientos_teoricos" rows="6" class="w-full resize-y rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
                    @error('experiencia_conocimientos_teoricos') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descripción de las habilidades técnicas que se aplicarán <span class="text-red-500">*</span></label>
                    <textarea wire:model.live.debounce.1000ms="experiencia_habilidades_tecnicas" rows="6" class="w-full resize-y rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
                    @error('experiencia_habilidades_tecnicas') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descripción de las competencias blandas que adquirirán los(as) estudiantes con esta experiencia <span class="text-red-500">*</span></label>
                    <textarea wire:model.live.debounce.1000ms="experiencia_competencias_blandas" rows="6" class="w-full resize-y rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
                    @error('experiencia_competencias_blandas') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Alineamiento con lo Esencial de la Reforma de la UNAH <span class="text-red-500">*</span></label>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Detalle brevemente cómo se alinean los ejes de lo esencial de la reforma en la ejecución de este {{ $esVoluntariado ? 'programa' : 'proyecto' }}. En resumen, describa qué competencias relacionadas con los ejes de lo esencial de la reforma adquirirán los{{ $esVoluntariado ? '' : '(as)' }} estudiantes con la participación en este {{ $esVoluntariado ? 'programa' : 'proyecto' }}.</p>
                <textarea wire:model.live.debounce.1000ms="alineamiento_reforma" rows="5" class="w-full resize-y rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
                @error('alineamiento_reforma') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Metodología <span class="text-red-500">*</span></label>
                <textarea wire:model.live.debounce.1000ms="metodologia" rows="6" class="w-full resize-y rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
                @error('metodologia') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bibliografía <span class="text-red-500">*</span></label>
                <textarea wire:model.live.debounce.1000ms="bibliografia" rows="5" class="w-full resize-y rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
                @error('bibliografia') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
        @endif

        {{-- ══════════════════ PASO 6: Beneficiarios ══════════════════ --}}
        @if($currentStep === 6)
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Paso 6: Beneficiarios y Zona de Impacto</h3>
        <div class="space-y-6">
            {{-- Tabla beneficiarios por etnia --}}
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ $esVoluntariado ? 'Indicar tipo de etnia' : 'Tipo de Población a la que está Dirigido el Proyecto' }}</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Marque los grupos que se atenderán. Puede seleccionar más de una opción.</p>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                        <thead class="bg-gray-100 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Grupo Étnico</th>
                                <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600 dark:text-gray-300">Hombres</th>
                                <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600 dark:text-gray-300">Mujeres</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @php
                            $grupos = [
                                ['label' => $esVoluntariado ? 'Pueblo originario' : 'Indígenas', 'h' => 'indigenas_hombres_marcado', 'm' => 'indigenas_mujeres_marcado'],
                                ['label' => $esVoluntariado ? 'Afrodescendiente' : 'Afrodescendientes', 'h' => 'afroamericanos_hombres_marcado', 'm' => 'afroamericanos_mujeres_marcado'],
                                ['label' => $esVoluntariado ? 'Mestizo' : 'Mestizos', 'h' => 'mestizos_hombres_marcado', 'm' => 'mestizos_mujeres_marcado'],
                            ];
                            @endphp
                            @foreach($grupos as $g)
                            <tr class="bg-white dark:bg-gray-900">
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-300 font-medium text-xs">{{ $g['label'] }}</td>
                                <td class="px-4 py-2 text-center">
                                    <input type="checkbox" wire:model="{{ $g['h'] }}" class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500" />
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <input type="checkbox" wire:model="{{ $g['m'] }}" class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500" />
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Cantidad aproximada de beneficiarios --}}
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                @if($esVoluntariado)
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Beneficiarios directos <span class="text-red-500">*</span></h4>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Cantidad de hombres y mujeres beneficiados directamente.</p>
                @else
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Cantidad Aproximada de Beneficiarios</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Cantidad total estimada.</p>
                @endif
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Hombres</label>
                        <input type="number" wire:model.blur.number="hombres" wire:blur="calcTotales" min="0" step="1" inputmode="numeric"
                            class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1.5 text-sm focus:border-blue-500" />
                        @error('hombres') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Mujeres</label>
                        <input type="number" wire:model.blur.number="mujeres" wire:blur="calcTotales" min="0" step="1" inputmode="numeric"
                            class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1.5 text-sm focus:border-blue-500" />
                        @error('mujeres') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <p class="text-sm mt-3 text-gray-700 dark:text-gray-300">Total General: <strong class="text-blue-700 dark:text-blue-400">{{ $poblacion_participante }}</strong></p>
            </div>

            {{-- Zona geográfica --}}
            <div>
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Sitio de Ejecución del Proyecto</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Departamento <span class="text-red-500">*</span></label>
                        <div wire:key="departamentos-impacto" x-data="{
                            open: false,
                            search: '',
                            _debounce: null,
                            selected: @js(collect($departamento_geo)->map(fn($id) => (string) $id)->values()->toArray()),
                            options: Object.entries(@js($departamentosGeo)).map(([id, label]) => ({ id: String(id), label: String(label) })),
                            values() { return this.options || []; },
                            selectedValues() {
                                const validIds = this.values().map(option => option.id);
                                return [...new Set((this.selected || []).map(String).filter(id => id !== '' && validIds.includes(id)))];
                            },
                            selectedLabels() {
                                return this.selectedValues()
                                    .map(id => this.values().find(option => option.id === id))
                                    .filter(Boolean);
                            },
                            filteredOptions() {
                                const term = this.search.trim().toLowerCase();
                                return this.values().filter(option => !term || option.label.toLowerCase().includes(term));
                            },
                            toggle(id) {
                                id = String(id);
                                const current = this.selectedValues();
                                const index = current.indexOf(id);
                                index === -1 ? current.push(id) : current.splice(index, 1);
                                this.selected = current;
                                this.search = '';
                                this.syncSelection();
                                this.$nextTick(() => this.$refs.search?.focus());
                            },
                            remove(id) {
                                this.selected = this.selectedValues().filter(value => value !== String(id));
                                this.syncSelection();
                            },
                            syncSelection() {
                                const values = this.selectedValues();
                                this.$wire.set('departamento_geo', values, false);
                                clearTimeout(this._debounce);
                                this._debounce = setTimeout(() => {
                                    this.$wire.call('actualizarDepartamentosImpacto', values);
                                }, 450);
                            },
                            isSelected(id) { return this.selectedValues().includes(String(id)); },
                            getName(id) { return this.values().find(option => option.id === String(id))?.label ?? ''; }
                        }" @click.outside="open = false" class="relative">
                            <div @click="open = true; $nextTick(() => $refs.search?.focus())"
                                class="min-h-[42px] max-h-24 w-full overflow-y-auto rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-2.5 py-2 cursor-text focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500">
                                <div class="flex min-w-0 flex-wrap items-center gap-1.5 pr-4">
                                    <template x-for="item in selectedLabels()" :key="item.id">
                                        <span class="inline-flex max-w-[180px] items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                            <span class="truncate" x-text="item.label"></span>
                                            <button type="button" @click.stop="remove(item.id)" class="shrink-0 font-bold leading-none hover:text-blue-950 dark:hover:text-blue-100">×</button>
                                        </span>
                                    </template>
                                    <input x-ref="search" x-model="search" @focus="open = true" @keydown.escape="open = false"
                                        :placeholder="selectedValues().length ? '' : 'Buscar departamentos...'"
                                        class="min-w-[140px] flex-1 border-0 bg-transparent p-0 text-sm text-gray-900 placeholder:text-gray-400 focus:ring-0 dark:text-white"
                                        type="text" />
                                    <span class="ml-auto shrink-0 text-gray-400 text-xs" x-text="open ? '▴' : '▾'"></span>
                                </div>
                            </div>
                            <div x-show="open" x-cloak class="absolute left-0 right-0 z-50 mt-1 max-h-56 w-full overflow-y-auto rounded-md border border-blue-200 bg-white shadow-lg dark:border-blue-700 dark:bg-gray-800">
                                <template x-if="filteredOptions().length === 0">
                                    <div class="px-3 py-2 text-sm text-gray-500">Sin resultados.</div>
                                </template>
                                <template x-for="option in filteredOptions()" :key="option.id">
                                    <div @click="toggle(option.id)" class="px-3 py-2 text-sm cursor-pointer hover:bg-blue-50 dark:hover:bg-gray-700 flex items-center justify-between"
                                        :class="isSelected(option.id) ? 'bg-blue-50 text-blue-700 font-medium dark:bg-blue-900/30 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300'">
                                        <span x-text="option.label"></span>
                                        <span x-show="isSelected(option.id)" class="text-xs">✓</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                        @error('departamento_geo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Municipio <span class="text-red-500">*</span></label>
                        <div wire:key="municipios-impacto-{{ md5(json_encode($departamento_geo)) }}-{{ md5(json_encode($municipiosGeo->keys()->values()->toArray())) }}" x-data="{
                            open: false,
                            search: '',
                            _debounce: null,
                            selected: @js(collect($municipio_geo)->map(fn($id) => (string) $id)->values()->toArray()),
                            options: Object.entries(@js($municipiosGeo)).map(([id, label]) => ({ id: String(id), label: String(label) })),
                            values() { return this.options || []; },
                            selectedValues() {
                                const validIds = this.values().map(option => option.id);
                                return [...new Set((this.selected || []).map(String).filter(id => id !== '' && validIds.includes(id)))];
                            },
                            selectedLabels() {
                                return this.selectedValues()
                                    .map(id => this.values().find(option => option.id === id))
                                    .filter(Boolean);
                            },
                            normalizeSelection() {
                                this.selected = this.selectedValues();
                                this.$wire.set('municipio_geo', this.selected, false);
                            },
                            filteredOptions() {
                                const term = this.search.trim().toLowerCase();
                                return this.values().filter(option => !term || option.label.toLowerCase().includes(term));
                            },
                            toggle(id) {
                                if (!this.values().length) return;
                                id = String(id);
                                const current = this.selectedValues();
                                const index = current.indexOf(id);
                                index === -1 ? current.push(id) : current.splice(index, 1);
                                this.selected = current;
                                this.search = '';
                                this.syncSelection();
                                this.$nextTick(() => this.$refs.search?.focus());
                            },
                            remove(id) {
                                this.selected = this.selectedValues().filter(value => value !== String(id));
                                this.syncSelection();
                            },
                            syncSelection() {
                                const values = this.selectedValues();
                                this.selected = values;
                                this.$wire.set('municipio_geo', values, false);
                                clearTimeout(this._debounce);
                                this._debounce = setTimeout(() => {
                                    this.$wire.call('actualizarMunicipiosImpacto', values);
                                }, 450);
                            },
                            isSelected(id) { return this.selectedValues().includes(String(id)); },
                            getName(id) { return this.values().find(option => option.id === String(id))?.label ?? ''; }
                        }" x-init="normalizeSelection()" @click.outside="open = false" class="relative">
                            <div @click="if (values().length) { open = true; $nextTick(() => $refs.search?.focus()) }"
                                class="min-h-[42px] max-h-24 w-full overflow-y-auto rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-2.5 py-2 focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500"
                                :class="values().length ? 'cursor-text' : 'cursor-not-allowed opacity-70'">
                                <div class="flex min-w-0 flex-wrap items-center gap-1.5 pr-4">
                                    <template x-for="item in selectedLabels()" :key="item.id">
                                        <span class="inline-flex max-w-[180px] items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                            <span class="truncate" x-text="item.label"></span>
                                            <button type="button" @click.stop="remove(item.id)" class="shrink-0 font-bold leading-none hover:text-blue-950 dark:hover:text-blue-100">×</button>
                                        </span>
                                    </template>
                                    <input x-ref="search" x-model="search" @focus="if (values().length) open = true" @keydown.escape="open = false"
                                        :disabled="!values().length"
                                        :placeholder="values().length ? (selectedValues().length ? '' : 'Buscar municipios...') : 'Seleccione departamentos primero'"
                                        class="min-w-[140px] flex-1 border-0 bg-transparent p-0 text-sm text-gray-900 placeholder:text-gray-400 focus:ring-0 disabled:cursor-not-allowed dark:text-white"
                                        type="text" />
                                    <span class="ml-auto shrink-0 text-gray-400 text-xs" x-text="open ? '▴' : '▾'"></span>
                                </div>
                            </div>
                            <div x-show="open" x-cloak class="absolute left-0 right-0 z-50 mt-1 max-h-56 w-full overflow-y-auto rounded-md border border-blue-200 bg-white shadow-lg dark:border-blue-700 dark:bg-gray-800">
                                <template x-if="filteredOptions().length === 0">
                                    <div class="px-3 py-2 text-sm text-gray-500">Sin resultados.</div>
                                </template>
                                <template x-for="option in filteredOptions()" :key="option.id">
                                    <div @click="toggle(option.id)" class="px-3 py-2 text-sm cursor-pointer hover:bg-blue-50 dark:hover:bg-gray-700 flex items-center justify-between"
                                        :class="isSelected(option.id) ? 'bg-blue-50 text-blue-700 font-medium dark:bg-blue-900/30 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300'">
                                        <span x-text="option.label"></span>
                                        <span x-show="isSelected(option.id)" class="text-xs">✓</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                        @error('municipio_geo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">País <span class="text-red-500">*</span></label>
                        <div wire:key="paises-impacto" x-data="{
                            open: false,
                            search: '',
                            selected: @js(collect($pais)->map(fn($p) => (string) $p)->values()->toArray()),
                            options: @js($paises->values()->toArray()),
                            values() { return this.options || []; },
                            selectedValues() { return (this.selected || []).map(String); },
                            filteredOptions() {
                                const term = this.search.trim().toLowerCase();
                                return this.values().filter(name => !term || String(name).toLowerCase().includes(term));
                            },
                            toggle(name) {
                                const current = this.selectedValues();
                                const index = current.indexOf(name);
                                index === -1 ? current.push(name) : current.splice(index, 1);
                                this.selected = current;
                                this.search = '';
                                this.$wire.set('pais', current, true);
                                this.$nextTick(() => this.$refs.search?.focus());
                            },
                            remove(name) {
                                this.selected = this.selectedValues().filter(value => value !== name);
                                this.$wire.set('pais', this.selected, true);
                            },
                            isSelected(name) { return this.selectedValues().includes(String(name)); }
                        }" @click.outside="open = false" class="relative">
                            <div @click="open = true; $nextTick(() => $refs.search?.focus())"
                                class="min-h-[42px] max-h-24 w-full overflow-y-auto rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-2.5 py-2 cursor-text focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500">
                                <div class="flex min-w-0 flex-wrap items-center gap-1.5 pr-4">
                                    <template x-for="name in selectedValues()" :key="name">
                                        <span class="inline-flex max-w-[180px] items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                            <span class="truncate" x-text="name"></span>
                                            <button type="button" @click.stop="remove(name)" class="shrink-0 font-bold leading-none hover:text-blue-950 dark:hover:text-blue-100">×</button>
                                        </span>
                                    </template>
                                    <input x-ref="search" x-model="search" @focus="open = true" @keydown.escape="open = false"
                                        :placeholder="selectedValues().length ? '' : 'Buscar países...'"
                                        class="min-w-[140px] flex-1 border-0 bg-transparent p-0 text-sm text-gray-900 placeholder:text-gray-400 focus:ring-0 dark:text-white"
                                        type="text" />
                                    <span class="ml-auto shrink-0 text-gray-400 text-xs" x-text="open ? '▴' : '▾'"></span>
                                </div>
                            </div>
                            <div x-show="open" x-cloak class="absolute left-0 right-0 z-50 mt-1 max-h-56 w-full overflow-y-auto rounded-md border border-blue-200 bg-white shadow-lg dark:border-blue-700 dark:bg-gray-800">
                                <template x-if="filteredOptions().length === 0">
                                    <div class="px-3 py-2 text-sm text-gray-500">Sin resultados.</div>
                                </template>
                                <template x-for="name in filteredOptions()" :key="name">
                                    <div @click="toggle(name)" class="px-3 py-2 text-sm cursor-pointer hover:bg-blue-50 dark:hover:bg-gray-700 flex items-center justify-between"
                                        :class="isSelected(name) ? 'bg-blue-50 text-blue-700 font-medium dark:bg-blue-900/30 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300'">
                                        <span x-text="name"></span>
                                        <span x-show="isSelected(name)" class="text-xs">✓</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                        @error('pais') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        @if($esVoluntariado)
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Aldea (incluye ciudad) <span class="text-red-500">*</span></label>
                        @else
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Aldea <span class="text-red-500">*</span></label>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Aplica también para ciudad.</p>
                        @endif
                        <input type="text" wire:model.live.debounce.1000ms="aldea" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500" />
                        @error('aldea') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Caserío <span class="text-red-500">*</span></label>
                        <input type="text" wire:model.live.debounce.1000ms="caserio" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500" />
                        @error('caserio') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Región <span class="text-red-500">*</span></label>
                        <input type="text" wire:model.live.debounce.1000ms="region" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500" />
                        @error('region') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Metodología de seguimiento (FORM-DVUS-015 · sólo Voluntariado) --}}
            @if($esVoluntariado)
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/40 p-4">
                <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-3">Metodología de seguimiento <span class="text-red-500">*</span></h4>
                <div class="flex flex-wrap gap-3">
                    @foreach($metodologiaSeguimientoOpciones as $valor => $etiqueta)
                    <label class="flex items-center gap-2 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer hover:border-blue-400">
                        <input type="checkbox" wire:model.live="metodologia_seguimiento" value="{{ $valor }}" class="text-blue-600 focus:ring-blue-500 rounded" />
                        <span>{{ $etiqueta }}</span>
                    </label>
                    @endforeach
                </div>
                @error('metodologia_seguimiento') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                @error('metodologia_seguimiento.*') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            @endif
        </div>
        @endif

        {{-- ══════════════════ PASO 7: Marco Lógico ══════════════════ --}}
        @if($currentStep === 7)
        @php
            $unidad = $esVoluntariado ? 'programa' : 'proyecto';
            $textoLleno = fn ($valor) => trim((string) ($valor ?? '')) !== '';
            $resultadoCompleto = fn (array $resultado) => $textoLleno($resultado['nombre_resultado'] ?? null)
                && $textoLleno($resultado['nombre_indicador'] ?? null)
                && $textoLleno($resultado['nombre_medio_verificacion'] ?? null);

            // Lo que le falta a cada objetivo específico (null = completo), con la misma regla que marca el paso como completo.
            $pendienteObjetivo = collect($objetivosEspecificos)->map(fn (array $objetivo) => match (true) {
                !$textoLleno($objetivo['descripcion'] ?? null) => 'Falta la descripción',
                empty($objetivo['resultados']) => 'Falta un resultado',
                !collect($objetivo['resultados'])->every($resultadoCompleto) => 'Hay un resultado incompleto',
                default => null,
            });
            $objetivosPorCompletar = $pendienteObjetivo->filter()->count();

            // Mediano y largo plazo se agrupan conservando el índice real de resultadosProyecto.
            $resultadosPorPlazo = collect($resultadosProyecto)->groupBy(
                fn (array $resultado) => ($resultado['plazo'] ?? '') === 'largo_plazo' ? 'largo_plazo' : 'mediano_plazo',
                true
            );
            $listasPlazo = [
                'mediano_plazo' => [
                    'titulo' => $esVoluntariado ? 'b) Indicadores de mediano plazo' : 'b) Resultados de mediano plazo',
                    'ayuda' => "Son los efectos que se esperan alcanzar del {$unidad}, es decir, la transformación esperada en la población beneficiada.",
                    'agregar' => '+ Agregar resultado de mediano plazo',
                    'resumen' => 'Mediano plazo',
                    'ejemplos' => ['resultado' => 'Ej.: Productores aplican prácticas de conservación de suelos', 'indicador' => 'Ej.: % de productores que aplican las prácticas', 'medio' => 'Ej.: Informe de visitas de seguimiento'],
                ],
                'largo_plazo' => [
                    'titulo' => $esVoluntariado ? 'c) Impacto que se desea generar' : 'c) Impacto que se desea generar en el proyecto',
                    'ayuda' => "Debe expresar los indicadores de impacto del {$unidad} (largo plazo).",
                    'agregar' => '+ Agregar resultado de largo plazo',
                    'resumen' => 'Largo plazo',
                    'ejemplos' => ['resultado' => 'Ej.: Mejora del rendimiento de los cultivos de la comunidad', 'indicador' => 'Ej.: Variación del rendimiento por manzana', 'medio' => 'Ej.: Registros de producción de la cooperativa'],
                ],
            ];
            $ejemplosCortoPlazo = ['resultado' => 'Ej.: 30 productores capacitados en manejo de suelos', 'indicador' => 'Ej.: N.º de productores que aprueban la evaluación', 'medio' => 'Ej.: Listas de asistencia y evaluaciones'];

            $claseChip = fn (string $estado) => match ($estado) {
                'listo' => 'border-green-200 bg-green-50 text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300',
                'pendiente' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-300',
                default => 'border-gray-200 bg-gray-50 text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300',
            };
            $mediano = count($resultadosPorPlazo->get('mediano_plazo', []));
            $largo = count($resultadosPorPlazo->get('largo_plazo', []));
            // En el 015 mediano y largo plazo son obligatorios; en el 001 son opcionales.
            $estadoPlazos = $esVoluntariado
                ? ($mediano > 0 && $largo > 0 ? 'listo' : 'pendiente')
                : ($mediano + $largo > 0 ? 'listo' : 'neutro');
        @endphp

        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Paso 7: Marco Lógico</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">Complete en orden: el objetivo general, cada objetivo específico con sus resultados de corto plazo y, al final, los resultados de mediano y largo plazo del {{ $unidad }}.</p>
        <details wire:ignore class="mt-1 mb-4 text-xs text-gray-600 dark:text-gray-400">
            <summary class="cursor-pointer select-none font-medium text-blue-700 hover:text-blue-900 dark:text-blue-300">¿Qué es cada tipo de resultado?</summary>
            <p class="mt-2 rounded-md bg-gray-50 p-3 dark:bg-gray-800/60">
                El indicador de resultado es una medida específica y observable que permite evaluar el grado de cumplimiento de los resultados que se han planteado. Sirven para evaluar en qué medida y calidad se lograron los objetivos del {{ $unidad }}. Hay tres tipos de resultados: 1) corto plazo, que son los productos que se obtendrán con el {{ $unidad }}, 2) los de mediano plazo, que son los efectos que alcanzará el {{ $unidad }}, y 3) los de largo plazo, resultados de impacto.
            </p>
        </details>

        {{-- Resumen de avance --}}
        <nav aria-label="Avance del marco lógico" class="mb-6 flex flex-wrap items-center gap-2 text-xs font-medium">
            <a href="#marco-objetivo-general" class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 {{ $claseChip($textoLleno($objetivo_general) ? 'listo' : 'pendiente') }}">
                <span>1</span> Objetivo general · {{ $textoLleno($objetivo_general) ? '✓' : 'pendiente' }}
            </a>
            <span class="text-gray-400" aria-hidden="true">→</span>
            <a href="#marco-objetivos" class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 {{ $claseChip($objetivosPorCompletar === 0 ? 'listo' : 'pendiente') }}">
                <span>2</span> {{ count($objetivosEspecificos) }} {{ count($objetivosEspecificos) === 1 ? 'objetivo específico' : 'objetivos específicos' }} · {{ $objetivosPorCompletar === 0 ? '✓' : $objetivosPorCompletar . ' por completar' }}
            </a>
            <span class="text-gray-400" aria-hidden="true">→</span>
            <a href="#marco-mediano-largo" class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 {{ $claseChip($estadoPlazos) }}">
                <span>3</span> Mediano plazo: {{ $mediano }} · Largo plazo: {{ $largo }}
            </a>
        </nav>

        <div class="space-y-8">
            {{-- 1. Objetivo general --}}
            <section id="marco-objetivo-general" class="scroll-mt-24">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">1. Objetivo general <span class="text-red-500">*</span></h4>
                <p class="mt-0.5 mb-2 text-xs text-gray-500 dark:text-gray-400">El objetivo debe estar basado en la población participante del {{ $unidad }}.</p>
                <textarea wire:model.live.debounce.1000ms="objetivo_general" rows="3" placeholder="Describa el propósito central del {{ $unidad }}..."
                    class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500"></textarea>
                @error('objetivo_general') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </section>

            {{-- 2. Objetivos específicos, cada uno con sus resultados de corto plazo --}}
            <section id="marco-objetivos" class="scroll-mt-24">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">2. Objetivos específicos y sus resultados de corto plazo <span class="text-red-500">*</span></h4>
                <p class="mt-0.5 mb-3 text-xs text-gray-500 dark:text-gray-400">Los objetivos deben estar relacionados con los resultados que se esperan obtener. Cada objetivo necesita al menos un resultado de corto plazo (producto) con su indicador y su medio de verificación.</p>
                @error('objetivosEspecificos') <p class="text-red-500 text-xs mb-2">{{ $message }}</p> @enderror

                <div class="space-y-3">
                    @foreach($objetivosEspecificos as $oi => $objetivo)
                        @php
                            $objetivoKey = $objetivo['wire_key'] ?? $objetivo['id'] ?? 'nuevo-'.$oi;
                            $pendiente = $pendienteObjetivo[$oi];
                        @endphp
                        <div wire:key="objetivo-{{ $objetivoKey }}"
                            x-data="{ abierto: true }"
                            x-on:validation-failed.window="abierto = true"
                            x-on:marco-logico-enfocar.window="if ($event.detail.objetivo === {{ $oi }}) { abierto = true; $nextTick(() => { $el.scrollIntoView({ behavior: 'smooth', block: 'center' }); $refs.descripcion?.focus(); }); }"
                            class="rounded-lg border {{ $pendiente ? 'border-gray-200 dark:border-gray-700' : 'border-green-200 dark:border-green-900' }}">
                            <div class="flex items-center gap-3 px-4 py-2.5">
                                <button type="button" x-on:click="abierto = !abierto" class="flex min-w-0 flex-1 items-center gap-2 text-left" :aria-expanded="abierto">
                                    <span class="text-xs text-gray-400" x-text="abierto ? '▾' : '▸'"></span>
                                    <span class="shrink-0 whitespace-nowrap rounded bg-blue-600 px-1.5 py-0.5 text-xs font-semibold text-white">OE {{ $oi + 1 }}</span>
                                    @if($pendiente)
                                        <span class="shrink-0 whitespace-nowrap rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">⚠ {{ $pendiente }}</span>
                                    @else
                                        <span class="shrink-0 whitespace-nowrap rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-300">✓ Completo</span>
                                    @endif
                                    <span x-show="!abierto" class="truncate text-xs text-gray-500 dark:text-gray-400">
                                        {{ \Illuminate\Support\Str::limit(trim((string) ($objetivo['descripcion'] ?? '')), 90) ?: 'Sin descripción' }}
                                        · {{ count($objetivo['resultados'] ?? []) }} {{ count($objetivo['resultados'] ?? []) === 1 ? 'resultado' : 'resultados' }}
                                    </span>
                                </button>
                                @if(count($objetivosEspecificos) > 1)
                                    <button type="button"
                                        x-on:click.prevent="confirmDialog('¿Eliminar el objetivo específico OE {{ $oi + 1 }} y sus resultados?', { type: 'danger' }).then((ok) => ok && $wire.removeObjetivo({{ $oi }}))"
                                        class="shrink-0 text-xs font-medium text-red-600 hover:text-red-800">
                                        Eliminar
                                    </button>
                                @endif
                            </div>

                            <div x-show="abierto" class="space-y-4 border-t border-gray-100 px-4 pb-4 pt-3 dark:border-gray-800">
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Descripción del objetivo <span class="text-red-500">*</span></label>
                                    <textarea x-ref="descripcion" wire:key="objetivo-descripcion-{{ $objetivoKey }}" wire:model.live.debounce.1000ms="objetivosEspecificos.{{ $oi }}.descripcion" rows="3"
                                        placeholder="Ej.: Fortalecer las capacidades de los productores de la comunidad en manejo sostenible de suelos"
                                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder:text-gray-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"></textarea>
                                    @error("objetivosEspecificos.{$oi}.descripcion") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <p class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ $esVoluntariado ? 'a) Resultados de corto plazo' : 'a) Resultados de corto plazo del proyecto' }} <span class="font-normal text-gray-500">(productos que se lograrán)</span> <span class="text-red-500">*</span></p>
                                    @error("objetivosEspecificos.{$oi}.resultados") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                    @if(!empty($objetivo['resultados']))
                                        <div class="mt-2 hidden gap-3 text-xs font-medium text-gray-500 dark:text-gray-400 md:grid md:grid-cols-[1.5rem_1fr_1fr_1fr_2rem]">
                                            <span>#</span><span>Resultado <span class="text-red-500">*</span></span><span>Indicador <span class="text-red-500">*</span></span><span>Medio de verificación <span class="text-red-500">*</span></span><span></span>
                                        </div>
                                        <div class="divide-y divide-gray-100 dark:divide-gray-800">
                                            @foreach($objetivo['resultados'] as $ri => $resultado)
                                                @include('livewire.proyectos.vinculacion.partials.marco-logico-fila-resultado', [
                                                    'ruta' => "objetivosEspecificos.{$oi}.resultados.{$ri}",
                                                    'numero' => $ri + 1,
                                                    'wireKey' => 'resultado-'.$objetivoKey.'-'.($resultado['wire_key'] ?? $resultado['id'] ?? 'nuevo-'.$ri),
                                                    'quitar' => "removeResultado({$oi}, {$ri})",
                                                    'ejemplos' => $ejemplosCortoPlazo,
                                                ])
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="mt-2 text-xs text-gray-500">Sin resultados todavía.</p>
                                    @endif
                                    <button wire:click="addResultado({{ $oi }})" type="button" class="mt-2 text-xs font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400">+ Agregar resultado</button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <button wire:click="addObjetivo" type="button"
                    class="mt-3 inline-flex w-full items-center justify-center rounded-lg border border-dashed border-blue-300 px-3 py-2 text-sm font-medium text-blue-700 hover:bg-blue-50 dark:border-blue-700 dark:text-blue-300 dark:hover:bg-blue-900/20">
                    + Agregar objetivo específico
                </button>
            </section>

            {{-- 3. Resultados de mediano y largo plazo (efectos e impacto del proyecto, no ligados a un objetivo específico) --}}
            <section id="marco-mediano-largo" class="scroll-mt-24">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">3. Resultados de mediano y largo plazo @if($esVoluntariado)<span class="text-red-500">*</span>@endif</h4>
                <p class="mt-0.5 mb-3 text-xs text-gray-500 dark:text-gray-400">Son resultados del {{ $unidad }} en conjunto, no de un objetivo específico. {{ $esVoluntariado ? 'Registre al menos uno de cada plazo.' : 'Registre los que apliquen.' }}</p>
                @foreach($errors->get('resultadosProyecto') as $mensaje) <p class="text-red-500 text-xs mb-2">{{ $mensaje }}</p> @endforeach

                <div class="space-y-5">
                    @foreach($listasPlazo as $plazo => $lista)
                        @php $filas = $resultadosPorPlazo->get($plazo, collect()); @endphp
                        <div wire:key="marco-lista-{{ $plazo }}" class="rounded-lg border border-gray-200 px-4 py-3 dark:border-gray-700">
                            <p class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ $lista['titulo'] }} @if($esVoluntariado)<span class="text-red-500">*</span>@endif</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $lista['ayuda'] }}</p>
                            @if($filas->isNotEmpty())
                                <div class="mt-2 hidden gap-3 text-xs font-medium text-gray-500 dark:text-gray-400 md:grid md:grid-cols-[1.5rem_1fr_1fr_1fr_2rem]">
                                    <span>#</span><span>Resultado <span class="text-red-500">*</span></span><span>Indicador <span class="text-red-500">*</span></span><span>Medio de verificación <span class="text-red-500">*</span></span><span></span>
                                </div>
                                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach($filas as $ri => $resultado)
                                        @include('livewire.proyectos.vinculacion.partials.marco-logico-fila-resultado', [
                                            'ruta' => "resultadosProyecto.{$ri}",
                                            'numero' => $loop->iteration,
                                            'wireKey' => 'resultado-proyecto-'.($resultado['wire_key'] ?? $resultado['id'] ?? 'nuevo-'.$ri),
                                            'quitar' => "removeResultadoProyecto({$ri})",
                                            'ejemplos' => $lista['ejemplos'],
                                        ])
                                    @endforeach
                                </div>
                            @else
                                <p class="mt-2 text-xs text-gray-500">Sin resultados de {{ mb_strtolower($lista['resumen']) }}.</p>
                            @endif
                            <button wire:click="addResultadoProyecto('{{ $plazo }}')" type="button" class="mt-2 text-xs font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400">{{ $lista['agregar'] }}</button>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
        @endif

        {{-- ══════════════════ PASO 8: Presupuesto ══════════════════ --}}
        @if($currentStep === 8)
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Paso 8: Detalle del Presupuesto</h3>
        <div class="space-y-6">
            <div>
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Aporte Institucional (Manifestado en Lempiras)</h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr class="text-xs text-gray-500 text-left">
                                <th class="py-2 px-3">Concepto</th>
                                <th class="py-2 px-3">Unidad</th>
                                <th class="py-2 px-3 text-center">Cantidad</th>
                                <th class="py-2 px-3 text-center">Costo Unitario</th>
                                <th class="py-2 px-3 text-center">Costo Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($aporte_institucional as $i => $aporte)
                            <tr class="border-t border-gray-200 dark:border-gray-700 {{ !($aporte['editable'] ?? true) ? 'bg-gray-50 dark:bg-gray-800/50' : '' }}">
                                <td class="py-2 px-3 text-gray-700 dark:text-gray-300 text-xs">{{ $aporte['concepto_label'] ?? $aporte['concepto'] }}</td>
                                <td class="py-2 px-3 text-gray-500 text-xs">{{ $aporte['unidad_label'] ?? $aporte['unidad'] }}</td>
                                <td class="py-2 px-3"><input type="number" wire:model="aporte_institucional.{{ $i }}.cantidad" wire:change="updateAporteTotal({{ $i }})" min="0" max="99999999.99" @readonly(($aporte['concepto'] ?? '') === 'horas_trabajo_docentes') @disabled(!($aporte['editable']??true)) title="{{ ($aporte['concepto'] ?? '') === 'horas_trabajo_docentes' ? 'Calculado con las horas requeridas y responsables de todas las actividades' : '' }}" class="w-24 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 read-only:bg-gray-100 read-only:text-gray-600 disabled:bg-gray-100 disabled:text-gray-500 dark:disabled:bg-gray-800 px-2 py-1 text-sm text-center mx-auto block focus:border-blue-500" /></td>
                                <td class="py-2 px-3"><input type="number" wire:model="aporte_institucional.{{ $i }}.costo_unitario" wire:change="updateAporteTotal({{ $i }})" min="0" max="99999999.99" step="0.01" @disabled(!($aporte['editable']??true)) class="w-28 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 disabled:bg-gray-100 disabled:text-gray-500 dark:disabled:bg-gray-800 px-2 py-1 text-sm text-center mx-auto block focus:border-blue-500" /></td>
                                <td class="py-2 px-3 text-center font-medium text-gray-900 dark:text-white">L. {{ number_format($aporte['costo_total'] ?? 0, 2) }}</td>
                            </tr>
                            @endforeach
                            <tr class="border-t-2 border-gray-400 dark:border-gray-500 bg-gray-50 dark:bg-gray-800">
                                <td colspan="4" class="py-2 px-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Total Aporte Institucional</td>
                                <td class="py-2 px-3 text-center font-bold text-gray-900 dark:text-white">L. {{ number_format(collect($aporte_institucional)->sum('costo_total'), 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">La cantidad de horas de trabajo docentes se calcula sumando, para cada actividad, las horas requeridas multiplicadas por su número de responsables.</p>
                </div>
            </div>
            <div>
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Otras Aportaciones (Manifestado en Lempiras)</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">El aporte de la institución contraparte y de la comunidad deberá ser certificado al finalizar el proyecto mediante documento de declaración firmada por el representante legal de la entidad contraparte y/o comunidad. De no poder contarse con este documento, no se deberá detallar este dato.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @php
                    $aportes = [
                        ['field' => 'aporte_contraparte',        'label' => 'Aporte de la Contraparte'],
                        ['field' => 'aporte_internacionales',    'label' => 'Aporte Fondos Internacionales'],
                        ['field' => 'aporte_otras_universidades','label' => 'Aporte de Otras Universidades'],
                        ['field' => 'aporte_comunidad',          'label' => 'Aporte de los Beneficiarios (Comunidad)'],
                        ['field' => 'otros_aportes',             'label' => 'Otros Aportes'],
                    ];
                    @endphp
                    @foreach($aportes as $a)
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ $a['label'] }}</label>
                        <input type="number" wire:model.live.debounce.500ms="{{ $a['field'] }}" min="0" step="0.01" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500" />
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="rounded-lg border-2 border-blue-300 dark:border-blue-700 bg-blue-50 dark:bg-blue-900/20 p-4 flex items-center justify-between">
                <span class="text-sm font-semibold text-blue-900 dark:text-blue-200">Total Proyecto (Aporte Institucional + Otras Aportaciones)</span>
                <span class="text-lg font-bold text-blue-900 dark:text-blue-100">L. {{ number_format($this->totalGeneralPresupuesto(), 2) }}</span>
            </div>
        </div>
        @endif

        {{-- ══════════════════ PASO 9: Anexos ══════════════════ --}}
        @if($currentStep === 9)
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Paso 9: Anexos</h3>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-5">
            Agregue cada documento indicando el tipo de anexo al que corresponde. Puede adjuntar archivos PDF, documentos de Office o imágenes de hasta 10 MB.
        </p>
        <div class="space-y-4">
            <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-200">
                <span class="font-semibold">Documentos obligatorios:</span>
                adjunte el documento 1 y/o el documento 2 , y el documento 3.
            </div>
            @error('anexos')
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">
                    {{ $message }}
                </div>
            @enderror
            <div class="flex items-center justify-between gap-4">
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Anexos guardados ({{ $record?->anexos->count() ?? 0 }})</h4>
                <button wire:click="openAnexoModal" type="button"
                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                    + Agregar anexo
                </button>
            </div>

            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="w-16 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">No.</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Nombre</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Tipo</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-700 dark:bg-gray-900">
                        @forelse($record?->anexos ?? collect() as $anexo)
                            <tr wire:key="anexo-guardado-{{ $anexo->id }}">
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $loop->iteration }}</td>
                                <td class="px-4 py-3 text-gray-900 dark:text-white">
                                    <span class="block max-w-md truncate" title="{{ $anexo->nombre_archivo ?: basename($anexo->documento_url) }}">
                                        {{ $anexo->nombre_archivo ?: basename($anexo->documento_url) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                    <span>{{ $anexo->tipoAnexo?->nombre ?? 'Sin clasificar (registro anterior)' }}</span>
                                    @if(!empty($anexo->detalle))
                                        <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $anexo->detalle }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <a href="{{ Storage::url($anexo->documento_url) }}" target="_blank" rel="noopener" class="text-xs text-blue-600 hover:text-blue-800">Ver</a>
                                    <button type="button" x-on:click.prevent="confirmDialog('¿Eliminar este anexo?', { type: 'danger' }).then((ok) => ok && $wire.deleteAnexo({{ $anexo->id }}))" type="button" class="ml-3 text-xs text-red-600 hover:text-red-800">Eliminar</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No hay anexos agregados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($showAnexoModal)
            @php
                $tipoAnexoSeleccionado = $tiposAnexo->firstWhere('id', (int) $nuevoAnexoTipoId);
            @endphp
            <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
                <div class="fixed inset-0 bg-black/50"></div>
                <div class="relative flex min-h-full items-center justify-center p-4">
                    <div class="relative w-full max-w-xl rounded-lg bg-white dark:bg-gray-900 shadow-xl border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-5 py-3">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Agregar anexo</h4>
                            <button wire:click="closeAnexoModal" type="button" class="text-gray-500 hover:text-gray-800 dark:hover:text-gray-200 text-lg leading-none">✕</button>
                        </div>

                        <div class="p-5 space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Tipo de documento <span class="text-red-500">*</span></label>
                                <select wire:model.live="nuevoAnexoTipoId" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500">
                                    <option value="">Seleccione el tipo de documento</option>
                                    @foreach($tiposAnexo as $tipoAnexo)
                                        <option value="{{ $tipoAnexo->id }}">{{ $tipoAnexo->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('nuevoAnexoTipoId') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            @if($tipoAnexoSeleccionado?->requiere_detalle)
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Detalle del documento <span class="text-red-500">*</span></label>
                                    <input type="text" wire:model="nuevoAnexoDetalle" maxlength="255" placeholder="Especifique el tipo de documento"
                                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500" />
                                    @error('nuevoAnexoDetalle') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                            @endif

                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Archivo <span class="text-red-500">*</span></label>
                                <div class="relative flex min-h-40 items-center justify-center rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/50 p-6 text-center hover:border-blue-400">
                                    <input type="file" multiple wire:model="newAnexos" wire:key="anexo-input-{{ $anexoUploadKey }}"
                                        accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                                        class="absolute inset-0 h-full w-full cursor-pointer opacity-0" />
                                    <div class="pointer-events-none">
                                        <p class="text-sm font-medium text-gray-700 dark:text-gray-200">Seleccione o suelte sus documentos aquí</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">PDF, Office o imagen · máximo 10 MB por archivo</p>
                                        <p wire:loading wire:target="newAnexos" class="mt-2 text-xs font-medium text-blue-600">Cargando archivos...</p>
                                    </div>
                                </div>
                                @error('newAnexos') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                @error('newAnexos.*') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            @if(!empty($newAnexos))
                                <div class="space-y-1.5">
                                    @foreach($newAnexos as $i => $archivo)
                                        <div wire:key="pendiente-anexo-{{ $i }}" class="flex items-center justify-between rounded bg-blue-50 dark:bg-blue-900/20 px-3 py-2 text-sm">
                                            <span class="max-w-sm truncate text-gray-700 dark:text-gray-300">{{ is_object($archivo) ? $archivo->getClientOriginalName() : '' }}</span>
                                            <button wire:click="removeNewAnexo({{ $i }})" type="button" class="ml-3 shrink-0 text-xs text-red-600 hover:text-red-800">Quitar</button>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="flex justify-end gap-2 border-t border-gray-200 dark:border-gray-700 px-5 py-3">
                            <button wire:click="closeAnexoModal" type="button" class="px-3 py-1.5 text-xs font-medium rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 hover:bg-gray-200">Cancelar</button>
                            <button wire:click="uploadAnexos" type="button" wire:loading.attr="disabled" wire:target="uploadAnexos,newAnexos" @disabled(empty($newAnexos) || empty($nuevoAnexoTipoId))
                                class="px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                <span wire:loading.remove wire:target="uploadAnexos">Agregar</span>
                                <span wire:loading wire:target="uploadAnexos">Subiendo...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Uso de espacios, servicios y medios institucionales (FORM-DVUS-015 · sólo Voluntariado) --}}
            @if($esVoluntariado)
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/40 p-4">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Información sobre el uso de espacios, servicios y medios institucionales <span class="text-red-500">*</span></h4>
                    <button wire:click="addEspacioInstitucional" type="button" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                        + Agregar
                    </button>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">En esta sección detallarán los espacios o servicios de la UNAH, que utilizará para el desarrollo de la actividad, tales como: uso de laboratorios, aulas, auditorios, medios de comunicación, etc.</p>
                @error('espacios_institucionales') <p class="text-red-500 text-xs mb-2">{{ $message }}</p> @enderror

                <div class="space-y-3">
                    @foreach($espacios_institucionales as $i => $espacio)
                    <div wire:key="espacio-{{ $i }}" class="grid grid-cols-1 md:grid-cols-12 gap-2 items-end border-b border-gray-100 dark:border-gray-700 pb-3">
                        <div class="md:col-span-4">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Descripción del servicio o infraestructura <span class="text-red-500">*</span></label>
                            <input type="text" wire:model.live.debounce.1000ms="espacios_institucionales.{{ $i }}.descripcion" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500" />
                            @error('espacios_institucionales.'.$i.'.descripcion') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Ubicación <span class="text-red-500">*</span></label>
                            <input type="text" wire:model.live.debounce.1000ms="espacios_institucionales.{{ $i }}.ubicacion" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500" />
                            @error('espacios_institucionales.'.$i.'.ubicacion') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Unidad gestora <span class="text-red-500">*</span></label>
                            <input type="text" wire:model.live.debounce.1000ms="espacios_institucionales.{{ $i }}.unidad_gestora" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500" />
                            @error('espacios_institucionales.'.$i.'.unidad_gestora') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="md:col-span-1">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Tiempo de uso (horas) <span class="text-red-500">*</span></label>
                            <input type="number" min="0" step="0.5" wire:model.live.debounce.1000ms="espacios_institucionales.{{ $i }}.tiempo_uso_horas" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-2 py-1.5 text-sm focus:border-blue-500" />
                            @error('espacios_institucionales.'.$i.'.tiempo_uso_horas') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="md:col-span-1 flex justify-end">
                            <button wire:click="removeEspacioInstitucional({{ $i }})" type="button" class="text-xs text-red-600 hover:text-red-800">Quitar</button>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endif

        {{-- ══════════════════ Navegación ══════════════════ --}}
        <div class="mt-8 flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
            <div>
                @if($currentStep > 1)
                <button wire:click="prevStep" type="button"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                    ← Anterior
                </button>
                @endif
            </div>
            <div class="flex items-center gap-3">
                <span class="text-xs min-w-[78px] text-right">
                    @if($estadoAutoGuardado === 'guardando')
                        <span class="text-gray-500 dark:text-gray-400">Guardando...</span>
                    @elseif($estadoAutoGuardado === 'guardado')
                        <span class="text-green-600 dark:text-green-400">Guardado</span>
                    @elseif($estadoAutoGuardado === 'error')
                        <span class="text-red-600 dark:text-red-400">Error al guardar</span>
                    @endif
                </span>
                @if($currentStep < 9)
                <button wire:click="nextStep" type="button"
                    class="inline-flex items-center px-4 py-2 rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    Siguiente →
                </button>
                @elseif($currentStep === 9)
                <button wire:click="borrador" type="button"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50">
                    {{ $enSubsanacion ? 'Guardar cambios' : 'Guardar como Borrador' }}
                </button>
                <button
                    wire:click="abrirModalEnviar"
                    type="button"
                    class="inline-flex items-center px-4 py-2 rounded-md text-sm font-semibold text-white bg-amber-500 hover:bg-amber-600 shadow-sm">
                    {{ $enSubsanacion ? 'Reenviar a revisión' : 'Enviar para Firmar' }}
                </button>
                @endif
            </div>
        </div>

    </div>
</div>

{{-- ══════════════════ Modal: Enviar para Firmar ══════════════════ --}}
@if($showEnviarModal)
<div
    wire:key="modal-enviar"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
>
    <div class="relative w-full max-w-2xl max-h-[92vh] overflow-y-auto rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900" wire:click.stop>

        {{-- Header --}}
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Enviar proyecto para revisión</h2>
                @if(count($modalEtapasConDestinatario) > 0)
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Seleccione el destinatario para cada etapa del flujo.</p>
                @else
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">El sistema asignará automáticamente los responsables según el flujo configurado.</p>
                @endif
            </div>
            <button wire:click="$set('showEnviarModal', false)" class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full border border-slate-200 text-slate-500 hover:bg-slate-100 dark:border-slate-700">
                &times;
            </button>
        </div>

        {{-- Indicador de pasos --}}
        @php
            $totalPasos = count($modalEtapasConDestinatario) + 1;
            $pasoActual = $modalStep;
        @endphp
        @if($totalPasos > 1)
        <div class="mt-5 flex flex-wrap items-center gap-2">
            @foreach($modalEtapasConDestinatario as $i => $etapa)
                <div class="flex items-center gap-1.5 {{ $pasoActual < $i ? 'opacity-40' : '' }}">
                    <span class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold {{ $pasoActual === $i ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900' : ($pasoActual > $i ? 'bg-emerald-500 text-white' : 'border border-slate-300 text-slate-400 dark:border-slate-600') }}">
                        {{ $pasoActual > $i ? '✓' : ($i + 1) }}
                    </span>
                    <span class="hidden text-xs sm:block {{ $pasoActual === $i ? 'font-semibold text-slate-900 dark:text-white' : 'text-slate-400' }}">{{ $etapa['nombre'] }}</span>
                </div>
                <span class="text-slate-300 dark:text-slate-600 text-xs">&rarr;</span>
            @endforeach
            <div class="flex items-center gap-1.5 {{ $pasoActual < count($modalEtapasConDestinatario) ? 'opacity-40' : '' }}">
                <span class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold {{ $pasoActual === count($modalEtapasConDestinatario) ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900' : 'border border-slate-300 text-slate-400 dark:border-slate-600' }}">
                    {{ count($modalEtapasConDestinatario) + 1 }}
                </span>
                <span class="hidden text-xs sm:block {{ $pasoActual === count($modalEtapasConDestinatario) ? 'font-semibold text-slate-900 dark:text-white' : 'text-slate-400' }}">Confirmación</span>
            </div>
        </div>
        @endif

        {{-- Pasos de selección de destinatario --}}
        @foreach($modalEtapasConDestinatario as $i => $etapa)
        @if($pasoActual === $i)
        <div wire:key="modal-paso-{{ $i }}" class="mt-5 rounded-xl border border-slate-200 p-5 dark:border-slate-700">
            <div class="mb-4">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ $etapa['nombre'] }}</h3>
                @if(!empty($etapa['codigo']))
                <span class="inline-block mt-1 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $etapa['codigo'] }}</span>
                @endif
                @if(!empty($etapa['rol_nombre']))
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Rol requerido: <span class="font-medium">{{ $etapa['rol_nombre'] }}</span></p>
                @endif
            </div>

            @if(!empty($etapa['candidatos']))
            <div>
                <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">Seleccione el destinatario</label>
                <select
                    wire:model="modalDestinatarios.{{ $etapa['id'] }}"
                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    <option value="">Seleccione un destinatario...</option>
                    @foreach($etapa['candidatos'] as $candidato)
                    <option value="{{ $candidato['user_id'] }}">{{ $candidato['nombre'] }}</option>
                    @endforeach
                </select>
            </div>
            @else
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200">
                No hay usuarios disponibles con el rol requerido para esta etapa.
            </div>
            @endif
        </div>
        @endif
        @endforeach

        {{-- Paso de confirmación --}}
        @if($pasoActual === count($modalEtapasConDestinatario))
        <div wire:key="modal-confirmacion" class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-5 dark:border-emerald-900/50 dark:bg-emerald-950/30">
            <h3 class="font-semibold text-emerald-800 dark:text-emerald-200">Listo para enviar</h3>
            <p class="mt-1 text-sm text-emerald-700 dark:text-emerald-300">
                El proyecto será enviado al flujo de aprobación configurado.
            </p>
            @if(count($modalEtapasConDestinatario) > 0)
            <div class="mt-4 space-y-2">
                @foreach($modalEtapasConDestinatario as $etapa)
                @php
                    $userId = $modalDestinatarios[$etapa['id']] ?? null;
                    $seleccionado = collect($etapa['candidatos'])->firstWhere('user_id', $userId);
                @endphp
                <div class="flex items-center gap-2 text-xs text-emerald-800 dark:text-emerald-200">
                    <span class="font-medium">{{ $etapa['nombre'] }}:</span>
                    <span>{{ $seleccionado ? $seleccionado['nombre'] : '—' }}</span>
                </div>
                @endforeach
            </div>
            @endif
        </div>
        @endif

        {{-- Footer --}}
        <div class="mt-6 flex items-center justify-between">
            <button wire:click="$set('showEnviarModal', false)" class="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                Cancelar
            </button>
            <div class="flex items-center gap-2">
                @if($pasoActual > 0)
                <button wire:click="modalAnterior" class="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                    &larr; Anterior
                </button>
                @endif
                @if($pasoActual < count($modalEtapasConDestinatario))
                <button wire:click="modalSiguiente" class="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 dark:bg-slate-100 dark:text-slate-900">
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
</div>
