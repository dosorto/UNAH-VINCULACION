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
    <div class="mb-4 rounded-lg border border-yellow-300 dark:border-yellow-700 bg-yellow-50 dark:bg-yellow-900/20 px-4 py-3">
        <p class="text-xs font-semibold uppercase tracking-wide text-yellow-700 dark:text-yellow-300">FORM-DVUS-015</p>
        <h2 class="text-lg font-bold text-yellow-900 dark:text-yellow-200">Registro de Proyecto de Voluntariado Académico</h2>
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
                <input type="text" wire:model.live.debounce.1000ms="nombre_proyecto" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500" />
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
                    <label class="flex items-center gap-2 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer hover:border-yellow-400">
                        <input type="radio" wire:model.live="tematica_principal" value="{{ $valor }}" class="text-yellow-600 focus:ring-yellow-500" />
                        <span>{{ $etiqueta }}</span>
                    </label>
                    @endforeach
                </div>
                @error('tematica_principal') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror

                @if($tematica_principal === 'otros')
                <div class="mt-2">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Especifique la temática</label>
                    <input type="text" wire:model.live.debounce.1000ms="tematica_principal_otro" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-yellow-500 focus:ring-1 focus:ring-yellow-500" />
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
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Alineamiento Institucional <span class="text-red-500">*</span></label>
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
                        'label' => 'Carreras',
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
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Programa/Estrategia al que Pertenece <span class="text-red-500">*</span></label>
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
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Meta(s) a la que se Contribuye</label>
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
                    <p class="text-xs text-blue-600 dark:text-blue-400">Coordinador/a del Proyecto</p>
                </div>
            </div>

            {{-- Empleados integrantes --}}
            <div>
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Integrantes del Equipo Docente Permanente Tiempo Completo</h4>
                        <p class="text-xs text-gray-500 mt-0.5">Agregar más líneas de ser necesario.</p>
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
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Rol</th>
                                <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($empleado_proyecto as $i => $emp)
                            <tr>
                                <td class="px-4 py-2 text-gray-900 dark:text-white">{{ $emp['nombre'] ?: 'Empleado #'.($emp['empleado_id'] ?? '-') }}</td>
                                <td class="px-4 py-2"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">{{ $emp['rol'] ?? 'Integrante' }}</span></td>
                                <td class="px-4 py-2 text-right"><button wire:click="removeEmpleado({{ $i }})" type="button" class="text-xs text-red-600 hover:text-red-800">Eliminar</button></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-sm text-gray-500 text-center py-4 border border-dashed border-gray-300 dark:border-gray-600 rounded-lg">Sin empleados integrantes agregados.</p>
                @endif
            </div>

            {{-- Participación de Estudiantes --}}
            <div>
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Participación de Estudiantes UNAH <span class="text-red-500">*</span></h4>
                        <p class="text-xs text-gray-500 mt-0.5">Debe agregar al menos un grupo para continuar.</p>
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
                                        {{ $est['tipo_participacion_estudiante'] ?: '-' }}
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
                            <tr class="bg-gray-50 dark:bg-gray-800 font-semibold">
                                <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">Total</td>
                                <td class="px-4 py-2 text-center text-xs">{{ collect($estudiante_proyecto)->sum('cantidad_estudiantes_hombres') }}</td>
                                <td class="px-4 py-2 text-center text-xs">{{ collect($estudiante_proyecto)->sum('cantidad_estudiantes_mujeres') }}</td>
                                <td class="px-4 py-2 text-center text-xs">{{ collect($estudiante_proyecto)->sum('total_estudiantes') }}</td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-sm text-red-500 text-center py-4 border border-dashed border-red-300 rounded-lg">Sin grupos de estudiantes agregados. Este paso es obligatorio.</p>
                @endif
            </div>

            {{-- Integrantes Internacionales --}}
            <div>
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Docentes Internacionales Participantes en el Proyecto</h4>
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
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Nombre</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">RTN</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Sexo</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">País</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Institución</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Nivel Académico</th>
                                <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($integrante_internacional_proyecto as $i => $int)
                            <tr wire:key="integrante-internacional-{{ $int['integrante_internacional_id'] ?? $i }}-{{ $i }}">
                                <td class="px-4 py-2 text-gray-900 dark:text-white">{{ $int['nombre'] ?: 'Integrante #'.($int['integrante_internacional_id'] ?? '-') }}</td>
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $int['rtn'] ?? '-' }}</td>
                                <td class="px-4 py-2 {{ in_array($int['sexo'] ?? '', ['masculino', 'femenino'], true) ? 'text-gray-700 dark:text-gray-300' : 'text-red-600 font-medium' }}">
                                    {{ ($int['sexo'] ?? '') === 'masculino' ? 'Masculino' : (($int['sexo'] ?? '') === 'femenino' ? 'Femenino' : 'Sin registrar') }}
                                </td>
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $int['pais'] ?? '-' }}</td>
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $int['institucion'] ?? '-' }}</td>
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ !empty($int['nivel_academico_nombre']) ? $int['nivel_academico_nombre'] : 'Sin registrar' }}</td>
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
                                    <p class="text-xs text-amber-600 mt-1">Cree una asignatura asociada a una de las carreras seleccionadas para poder usarla aqui.</p>
                                @endif
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Periodo Académico <span class="text-red-500">*</span></label>
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
                            {{ $editIntegranteInternacionalIndex !== null ? 'Editar Docente Internacional' : 'Crear / Seleccionar Docente Internacional' }}
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
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Pasaporte / Documento <span class="text-red-500">*</span></label>
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
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Institución <span class="text-red-500">*</span></label>
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
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Paso 3: Información de la Entidad Contraparte del Proyecto</h3>
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
                $tipoContraparteLabels = [
                    'gobierno_nacional' => 'Gobierno Nacional',
                    'gobierno_municipal' => 'Gobierno Municipal',
                    'ong' => 'ONG',
                    'sociedad_civil' => 'Sociedad Civil Organizada',
                    'sector_privado' => 'Sector Privado',
                    'internacional' => 'Internacional',
                ];
                $contraparteDocErrors = collect($errors->keys())->filter(fn($k) => preg_match('/^entidad_contraparte\.\d+$/', $k));
            @endphp

            @if($contraparteDocErrors->isNotEmpty())
            <div class="rounded-md border border-red-300 bg-red-50 dark:bg-red-900/20 dark:border-red-700 px-3 py-2">
                @foreach($contraparteDocErrors as $errorKey)
                    <p class="text-red-600 dark:text-red-400 text-xs">{{ $errors->first($errorKey) }}</p>
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
                                        <span class="text-xs text-gray-500 dark:text-gray-400">Sin instrumentos</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button wire:click="openContraparteModal({{ $ci }})" type="button"
                                            class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400">
                                            Editar
                                        </button>
                                        <button x-on:click.prevent="confirmDialog('¿Eliminar esta entidad contraparte?', { type: 'danger' }).then((ok) => ok && $wire.removeContraparte({{ $ci }}))" type="button"
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
                        {{-- Seleccionar contraparte existente --}}
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                            <h5 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3">Seleccionar contraparte existente</h5>
                            <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-3 items-start">
                                <div>
                                    <label class="sr-only" for="contraparte-existente">Seleccione una contraparte</label>
                                    <select id="contraparte-existente" wire:model="contraparteSeleccionadoId"
                                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500">
                                        <option value="">Seleccione una contraparte existente</option>
                                        @foreach($contrapartesExistentes as $id => $nombre)
                                            <option value="{{ $id }}">{{ $nombre }}</option>
                                        @endforeach
                                    </select>
                                    @error('contraparteSeleccionadoId') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <button wire:click="agregarContraparteExistente" type="button"
                                    class="inline-flex items-center justify-center px-3 py-2 text-xs font-medium rounded-md bg-orange-600 text-white hover:bg-orange-700">
                                    Agregar seleccionada
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div>
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">O crear/editar manualmente</span>
                            <div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">RTN / identificador fiscal <span class="text-xs text-gray-400">(opcional; RTN Honduras: 14 dígitos)</span></label>
                                <input type="text" wire:model="nuevaContraparte.rtn" maxlength="50" placeholder="Identificador fiscal" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500" />
                                @error('nuevaContraparte.rtn') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Nombre <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="nuevaContraparte.nombre" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500" />
                                @error('nuevaContraparte.nombre') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Tipo de Contraparte <span class="text-red-500">*</span></label>
                                <select wire:model="nuevaContraparte.tipo_entidad" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500">
                                    <option value="">Seleccione...</option>
                                    <option value="gobierno_nacional">Gobierno Nacional</option>
                                    <option value="gobierno_municipal">Gobierno Municipal</option>
                                    <option value="ong">ONG</option>
                                    <option value="sociedad_civil">Sociedad Civil Organizada</option>
                                    <option value="sector_privado">Sector Privado</option>
                                    <option value="internacional">Internacional</option>
                                </select>
                                @error('nuevaContraparte.tipo_entidad') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Nombre del Contacto Directo</label>
                                <input type="text" wire:model="nuevaContraparte.nombre_contacto" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500" />
                                @error('nuevaContraparte.nombre_contacto') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Cargo del Contacto del Proyecto</label>
                                <input type="text" wire:model="nuevaContraparte.cargo_contacto" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500" />
                                @error('nuevaContraparte.cargo_contacto') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Teléfono</label>
                                <input type="text" wire:model="nuevaContraparte.telefono" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500" />
                                @error('nuevaContraparte.telefono') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Correo Electrónico</label>
                                <input type="email" wire:model="nuevaContraparte.correo" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500" />
                                @error('nuevaContraparte.correo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Breve Descripción de los Compromisos Asumidos por la Contraparte</label>
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
                    </div>
                    <div class="flex justify-end gap-2 border-t border-gray-200 dark:border-gray-700 px-5 py-3">
                        <button wire:click="closeContraparteModal" type="button" class="px-3 py-1.5 text-xs font-medium rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 hover:bg-gray-200">Cancelar</button>
                        <button wire:click="saveContraparte" wire:loading.attr="disabled" wire:target="nuevaContraparte.instrumento_formalizacion" type="button" class="px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60">Guardar</button>
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
                                        <button x-on:click.prevent="confirmDialog('¿Eliminar esta actividad?', { type: 'danger' }).then((ok) => ok && $wire.removeActividad({{ $i }}))" type="button"
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
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Responsables <span class="text-red-500">*</span></label>
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
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descripción de los Antecedentes del Proyecto <span class="text-red-500">*</span></label>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Explicar brevemente los antecedentes que dieron su origen y la importancia que tiene para los objetivos estratégicos de la UNAH.</p>
                <textarea wire:model.live.debounce.1000ms="resumen" rows="8" class="w-full resize-y rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
                @error('resumen') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Descripción de los Participantes del Proyecto</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                    Breve descripción de los alcances de la participación de los actores del proyecto. En el caso de la participación de la UNAH, se describirá de manera sucinta, cómo se articula el proyecto de vinculación con las funciones de la docencia (participación de asignaturas) y/o la investigación (si participa un grupo de investigación, o se generan insumos de una investigación en marcha).
                </p>
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
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Breve descripción del problema que se desea resolver, indicando línea base que se tendrá en consideración para la definición de los resultados del proyecto. La línea base debe representarse con datos y debe de describirse las causas del problema identificado.</p>
                <textarea wire:model.live.debounce.1000ms="definicion_problema" rows="6" class="w-full resize-y rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
                @error('definicion_problema') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Descripción de la experiencia académica (FORM-DVUS-015 · sólo Voluntariado) --}}
            @if($esVoluntariado)
            <div class="rounded-lg border border-yellow-200 dark:border-yellow-800 bg-yellow-50/50 dark:bg-yellow-900/10 p-4 space-y-4">
                <h4 class="text-sm font-semibold text-yellow-800 dark:text-yellow-300">Descripción de la experiencia académica que se desarrollará</h4>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Conocimientos teóricos que se aplicarán <span class="text-red-500">*</span></label>
                    <textarea wire:model.live.debounce.1000ms="experiencia_conocimientos_teoricos" rows="6" class="w-full resize-y rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-yellow-500"></textarea>
                    @error('experiencia_conocimientos_teoricos') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Habilidades técnicas que se aplicarán <span class="text-red-500">*</span></label>
                    <textarea wire:model.live.debounce.1000ms="experiencia_habilidades_tecnicas" rows="6" class="w-full resize-y rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-yellow-500"></textarea>
                    @error('experiencia_habilidades_tecnicas') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Competencias blandas que adquirirán los(as) estudiantes <span class="text-red-500">*</span></label>
                    <textarea wire:model.live.debounce.1000ms="experiencia_competencias_blandas" rows="6" class="w-full resize-y rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-yellow-500"></textarea>
                    @error('experiencia_competencias_blandas') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Alineamiento con lo Esencial de la Reforma de la UNAH <span class="text-red-500">*</span></label>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Detalle brevemente cómo se alinean los ejes de lo esencial de la reforma en la ejecución de este proyecto. En resumen, describa qué competencias relacionadas con los ejes de lo esencial de la reforma adquirirán los(as) estudiantes con la participación en este proyecto.</p>
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
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Tipo de Población a la que está Dirigido el Proyecto</h4>
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
                                ['label' => 'Indígenas', 'h' => 'indigenas_hombres_marcado', 'm' => 'indigenas_mujeres_marcado'],
                                ['label' => 'Afrodescendientes', 'h' => 'afroamericanos_hombres_marcado', 'm' => 'afroamericanos_mujeres_marcado'],
                                ['label' => 'Mestizos', 'h' => 'mestizos_hombres_marcado', 'm' => 'mestizos_mujeres_marcado'],
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
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Cantidad Aproximada de Beneficiarios</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Cantidad total estimada.</p>
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
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Aldea <span class="text-red-500">*</span></label>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Aplica también para ciudad.</p>
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
            <div class="rounded-lg border border-yellow-200 dark:border-yellow-800 bg-yellow-50/50 dark:bg-yellow-900/10 p-4">
                <h4 class="text-sm font-semibold text-yellow-800 dark:text-yellow-300 mb-3">Metodología de seguimiento</h4>
                <div class="flex flex-wrap gap-3">
                    @foreach($metodologiaSeguimientoOpciones as $valor => $etiqueta)
                    <label class="flex items-center gap-2 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer hover:border-yellow-400">
                        <input type="checkbox" wire:model.live="metodologia_seguimiento" value="{{ $valor }}" class="text-yellow-600 focus:ring-yellow-500 rounded" />
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
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Paso 7: Marco Lógico</h3>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
            El indicador de resultado es una medida específica y observable que permite evaluar el grado de cumplimiento de los resultados que se han planteado. Sirven para evaluar en qué medida y calidad se lograron los objetivos del proyecto. Hay tres tipos de resultados: 1) corto plazo, que son los productos que se obtendrán con el proyecto, 2) los de mediano plazo, que son los efectos que alcanzará el proyecto, y 3) los de largo plazo, resultados de impacto.
        </p>
        <div class="space-y-4">
            {{-- Objetivo General (full width) --}}
            <div class="bg-blue-50 dark:bg-blue-900/10 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <label class="block text-sm font-semibold text-blue-800 dark:text-blue-300 mb-1">Objetivo General <span class="text-red-500">*</span></label>
                <p class="text-xs text-blue-700/80 dark:text-blue-300/80 mb-2">El objetivo debe estar basado en la población participante del proyecto.</p>
                <textarea wire:model.live.debounce.1000ms="objetivo_general" rows="3" placeholder="Describe el propósito central del proyecto..."
                    class="w-full rounded-md border border-blue-300 dark:border-blue-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
                @error('objetivo_general') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Three-column layout: Objetivos Específicos | Descripción | Resultados Esperados --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                {{-- Columna 1: Objetivos Específicos list --}}
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Objetivos Específicos <span class="text-red-500">*</span></h4>
                        <button wire:click="addObjetivo" type="button"
                            class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                            + Agregar
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Deben estar relacionados con los resultados que esperan obtener en el proyecto.</p>
                    <div class="space-y-2 max-h-[520px] overflow-y-auto pr-1">
                        @foreach($objetivosEspecificos as $oi => $objetivo)
                        <div wire:key="objetivo-{{ $objetivo['wire_key'] ?? $objetivo['id'] ?? 'nuevo-'.$oi }}" wire:click="selectObjetivo({{ $oi }})" class="cursor-pointer rounded-lg border-2 p-3 transition-colors
                            {{ $selectedObjetivoIndex === $oi
                                ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                                : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 hover:border-blue-300' }}">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex-1 min-w-0">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold
                                        {{ $selectedObjetivoIndex === $oi ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">
                                        OE {{ $oi + 1 }}
                                    </span>
                                    <p class="mt-1 text-xs text-gray-600 dark:text-gray-400 line-clamp-2">
                                        {{ $objetivo['descripcion'] ?: 'Sin descripción...' }}
                                    </p>
                                    <p class="text-xs text-gray-400 mt-1">{{ count($objetivo['resultados'] ?? []) }} resultado(s)</p>
                                </div>
                                @if(count($objetivosEspecificos) > 1)
                                <button wire:click.stop="removeObjetivo({{ $oi }})" type="button" class="text-xs text-red-500 hover:text-red-700 shrink-0">✕</button>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                @php $objActivo = $objetivosEspecificos[$selectedObjetivoIndex] ?? null; @endphp
                @if($objActivo !== null)
                @php $objetivoActivoKey = $objActivo['wire_key'] ?? $objActivo['id'] ?? 'nuevo-'.$selectedObjetivoIndex; @endphp

                {{-- Columna 2: Descripción del objetivo activo --}}
                <div wire:key="objetivo-detalle-{{ $objetivoActivoKey }}" class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <h5 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Objetivo Específico {{ $selectedObjetivoIndex + 1 }}
                    </h5>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Descripción <span class="text-red-500">*</span></label>
                    <textarea wire:key="objetivo-descripcion-{{ $objetivoActivoKey }}" wire:model.live.debounce.1000ms="objetivosEspecificos.{{ $selectedObjetivoIndex }}.descripcion" rows="10"
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500"></textarea>
                    @error("objetivosEspecificos.{$selectedObjetivoIndex}.descripcion") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Columna 3: Resultados Esperados del objetivo activo --}}
                <div wire:key="objetivo-resultados-{{ $objetivoActivoKey }}" class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Resultados de Corto Plazo del Proyecto <span class="text-red-500">*</span></p>
                        <button wire:click="addResultado({{ $selectedObjetivoIndex }})" type="button"
                            class="text-xs text-blue-600 hover:text-blue-800">+ Agregar</button>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Debe plantearse un resultado para cada objetivo específico. Son los productos que se lograrán a corto plazo.</p>
                    @error("objetivosEspecificos.{$selectedObjetivoIndex}.resultados") <p class="text-red-500 text-xs mb-2">{{ $message }}</p> @enderror
                    <div class="space-y-2 max-h-[420px] overflow-y-auto pr-1">
                        @foreach($objActivo['resultados'] ?? [] as $ri => $resultado)
                        <div wire:key="resultado-{{ $objetivoActivoKey }}-{{ $resultado['wire_key'] ?? $resultado['id'] ?? 'nuevo-'.$ri }}" class="p-2.5 bg-gray-50 dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-600">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-xs font-semibold text-gray-500">R{{ $ri + 1 }}</span>
                                <button wire:click="removeResultado({{ $selectedObjetivoIndex }}, {{ $ri }})" type="button"
                                    class="text-xs text-red-500 hover:text-red-700">✕</button>
                            </div>
                            <div class="grid grid-cols-2 gap-1.5">
                                <div class="col-span-2">
                                    <label class="block text-xs text-gray-500 mb-0.5">Resultado <span class="text-red-500">*</span></label>
                                    <input type="text" wire:model.live.debounce.1000ms="objetivosEspecificos.{{ $selectedObjetivoIndex }}.resultados.{{ $ri }}.nombre_resultado"
                                        class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-xs focus:border-blue-500" />
                                    @error("objetivosEspecificos.{$selectedObjetivoIndex}.resultados.{$ri}.nombre_resultado") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-xs text-gray-500 mb-0.5">Indicador <span class="text-red-500">*</span></label>
                                    <input type="text" wire:model.live.debounce.1000ms="objetivosEspecificos.{{ $selectedObjetivoIndex }}.resultados.{{ $ri }}.nombre_indicador"
                                        class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-xs focus:border-blue-500" />
                                    @error("objetivosEspecificos.{$selectedObjetivoIndex}.resultados.{$ri}.nombre_indicador") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-xs text-gray-500 mb-0.5">Medio de Verificación <span class="text-red-500">*</span></label>
                                    <input type="text" wire:model.live.debounce.1000ms="objetivosEspecificos.{{ $selectedObjetivoIndex }}.resultados.{{ $ri }}.nombre_medio_verificacion"
                                        class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-xs focus:border-blue-500" />
                                    @error("objetivosEspecificos.{$selectedObjetivoIndex}.resultados.{$ri}.nombre_medio_verificacion") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div class="col-span-2">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300">Corto plazo</span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                        @if(empty($objActivo['resultados']))
                        <p class="text-xs text-gray-500 text-center py-2">Sin resultados. Haz clic en "+ Agregar".</p>
                        @endif
                    </div>
                </div>
                @endif
            </div>

            {{-- Resultados de Mediano y Largo Plazo (efectos e impacto del proyecto, no ligados a un objetivo específico) --}}
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <div class="flex items-center justify-between mb-1">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Resultados de Mediano y Largo Plazo</h4>
                    <button wire:click="addResultadoProyecto" type="button"
                        class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                        + Agregar
                    </button>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                    Mediano plazo: son los efectos que se esperan alcanzar del proyecto, es decir, la transformación esperada en la población beneficiada.
                    Largo plazo (impacto): debe expresar los indicadores de impacto que se desea generar en el proyecto.
                </p>
                @error('resultadosProyecto') <p class="text-red-500 text-xs mb-2">{{ $message }}</p> @enderror
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach($resultadosProyecto as $ri => $resultadoProyecto)
                    <div wire:key="resultado-proyecto-{{ $resultadoProyecto['wire_key'] ?? $resultadoProyecto['id'] ?? 'nuevo-'.$ri }}" class="p-2.5 bg-gray-50 dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-600">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-semibold text-gray-500">Resultado {{ $ri + 1 }}</span>
                            <button wire:click="removeResultadoProyecto({{ $ri }})" type="button"
                                class="text-xs text-red-500 hover:text-red-700">✕</button>
                        </div>
                        <div class="grid grid-cols-2 gap-1.5">
                            <div class="col-span-2">
                                <label class="block text-xs text-gray-500 mb-0.5">Resultado <span class="text-red-500">*</span></label>
                                <input type="text" wire:model.live.debounce.1000ms="resultadosProyecto.{{ $ri }}.nombre_resultado"
                                    class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-xs focus:border-blue-500" />
                                @error("resultadosProyecto.{$ri}.nombre_resultado") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs text-gray-500 mb-0.5">Indicador <span class="text-red-500">*</span></label>
                                <input type="text" wire:model.live.debounce.1000ms="resultadosProyecto.{{ $ri }}.nombre_indicador"
                                    class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-xs focus:border-blue-500" />
                                @error("resultadosProyecto.{$ri}.nombre_indicador") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs text-gray-500 mb-0.5">Medio de Verificación <span class="text-red-500">*</span></label>
                                <input type="text" wire:model.live.debounce.1000ms="resultadosProyecto.{{ $ri }}.nombre_medio_verificacion"
                                    class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-xs focus:border-blue-500" />
                                @error("resultadosProyecto.{$ri}.nombre_medio_verificacion") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs text-gray-500 mb-0.5">Plazo <span class="text-red-500">*</span></label>
                                <select wire:model.live="resultadosProyecto.{{ $ri }}.plazo"
                                    class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-xs focus:border-blue-500">
                                    <option value="mediano_plazo">Mediano plazo</option>
                                    <option value="largo_plazo">Largo plazo</option>
                                </select>
                                @error("resultadosProyecto.{$ri}.plazo") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                    @endforeach
                    @if(empty($resultadosProyecto))
                    <p class="text-xs text-gray-500 text-center py-2 md:col-span-2">Sin resultados de mediano/largo plazo. Haz clic en "+ Agregar".</p>
                    @endif
                </div>
            </div>
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
                                <td class="py-2 px-3"><input type="number" wire:model="aporte_institucional.{{ $i }}.cantidad" wire:change="updateAporteTotal({{ $i }})" min="0" @readonly(($aporte['concepto'] ?? '') === 'horas_trabajo_docentes') @disabled(!($aporte['editable']??true)) title="{{ ($aporte['concepto'] ?? '') === 'horas_trabajo_docentes' ? 'Calculado con las horas requeridas y responsables de todas las actividades' : '' }}" class="w-24 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 read-only:bg-gray-100 read-only:text-gray-600 disabled:bg-gray-100 disabled:text-gray-500 dark:disabled:bg-gray-800 px-2 py-1 text-sm text-center mx-auto block focus:border-blue-500" /></td>
                                <td class="py-2 px-3"><input type="number" wire:model="aporte_institucional.{{ $i }}.costo_unitario" wire:change="updateAporteTotal({{ $i }})" min="0" step="0.01" @disabled(!($aporte['editable']??true)) class="w-28 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 disabled:bg-gray-100 disabled:text-gray-500 dark:disabled:bg-gray-800 px-2 py-1 text-sm text-center mx-auto block focus:border-blue-500" /></td>
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
                adjunte el documento 1 o el documento 2 (cualquiera de los dos), y el documento 3.
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
                                    <button x-on:click.prevent="confirmDialog('¿Eliminar este anexo?', { type: 'danger' }).then((ok) => ok && $wire.deleteAnexo({{ $anexo->id }}))" type="button" class="ml-3 text-xs text-red-600 hover:text-red-800">Eliminar</button>
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
            <div class="rounded-lg border border-yellow-200 dark:border-yellow-800 bg-yellow-50/50 dark:bg-yellow-900/10 p-4">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-semibold text-yellow-800 dark:text-yellow-300">Uso de espacios, servicios y medios institucionales</h4>
                    <button wire:click="addEspacioInstitucional" type="button" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-yellow-600 text-white hover:bg-yellow-700">
                        + Agregar
                    </button>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Detalle los espacios o servicios de la UNAH que se utilizarán (laboratorios, aulas, auditorios, medios de comunicación, etc).</p>

                <div class="space-y-3">
                    @foreach($espacios_institucionales as $i => $espacio)
                    <div wire:key="espacio-{{ $i }}" class="grid grid-cols-1 md:grid-cols-12 gap-2 items-end border-b border-yellow-100 dark:border-yellow-900/40 pb-3">
                        <div class="md:col-span-4">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Descripción del servicio o infraestructura</label>
                            <input type="text" wire:model.live.debounce.1000ms="espacios_institucionales.{{ $i }}.descripcion" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-yellow-500" />
                            @error('espacios_institucionales.'.$i.'.descripcion') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Ubicación</label>
                            <input type="text" wire:model.live.debounce.1000ms="espacios_institucionales.{{ $i }}.ubicacion" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-yellow-500" />
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Unidad gestora</label>
                            <input type="text" wire:model.live.debounce.1000ms="espacios_institucionales.{{ $i }}.unidad_gestora" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-yellow-500" />
                        </div>
                        <div class="md:col-span-1">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Horas</label>
                            <input type="number" min="0" step="0.5" wire:model.live.debounce.1000ms="espacios_institucionales.{{ $i }}.tiempo_uso_horas" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-2 py-1.5 text-sm focus:border-yellow-500" />
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
