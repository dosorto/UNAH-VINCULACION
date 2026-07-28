<div>
    {{-- Step progress bar --}}
    @php
        $stepLabels = [
            1 => 'Info General',
            2 => 'Equipo',
            3 => 'Contraparte',
            4 => 'Actividades',
            5 => 'Descripción',
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

            @php
                $academicMultiSelects = [
                    [
                        'field' => 'ejes_prioritarios_unah',
                        'label' => 'Ejes Prioritarios UNAH',
                        'options' => $ejesPrioritarios,
                        'placeholder' => 'Buscar o seleccionar ejes prioritarios...',
                        'disabled' => false,
                        'emptyMessage' => 'No hay ejes prioritarios disponibles.',
                    ],
                    [
                        'field' => 'facultades_centros',
                        'label' => 'Facultad o Centros',
                        'options' => $facultadesCentros,
                        'placeholder' => 'Buscar o seleccionar facultades/centros...',
                        'disabled' => false,
                        'emptyMessage' => 'No hay facultades o centros disponibles.',
                    ],
                    [
                        'field' => 'departamentos_academicos',
                        'label' => 'Departamentos Académicos',
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
                        'disabled' => empty($departamentos_academicos) || !$carrerasOpts->count(),
                        'emptyMessage' => empty($departamentos_academicos)
                            ? 'Seleccione primero Departamentos Académicos.'
                            : 'No hay carreras para el Departamento Académico seleccionado.',
                    ],
                ];
            @endphp

            @foreach($academicMultiSelects as $field)
            <div wire:key="academico-{{ $field['field'] }}-{{ md5(json_encode($field['options'])) }}">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $field['label'] }} <span class="text-red-500">*</span></label>
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
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Programa al que Pertenece <span class="text-red-500">*</span></label>
                <input type="text" wire:model.live.debounce.1000ms="programa_pertenece" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500" />
                @error('programa_pertenece') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Líneas de Investigación Académica <span class="text-red-500">*</span></label>
                <textarea wire:model.live.debounce.1000ms="lineas_investigacion_academica" rows="3" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
                @error('lineas_investigacion_academica') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- ODS --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ODS <span class="text-red-500">*</span></label>
                <div wire:ignore x-data="{open:false,selected:($wire.get('ods')||[]).map(String),options:@js($odsList),toggle(id){id=String(id);let c=(this.selected||[]).map(String);const i=c.indexOf(id);i===-1?c.push(id):c.splice(i,1);this.selected=c;$wire.set('ods',c,true);},isSelected(id){return(this.selected||[]).map(String).includes(String(id));},getName(id){return this.options[id]??this.options[String(id)]??id;}}" @click.outside="open=false" class="relative">
                    <div @click="open=!open" class="min-h-[42px] w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 cursor-pointer flex flex-wrap gap-1 items-center">
                        <template x-for="id in (selected||[])" :key="id"><span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300"><span x-text="getName(id)"></span><button type="button" @click.stop="toggle(id)" class="font-bold">×</button></span></template>
                        <span x-show="!selected||selected.length===0" class="text-gray-400 text-sm">Seleccione los ODS...</span>
                        <span class="ml-auto text-gray-400 text-xs" x-text="open?'▴':'▾'"></span>
                    </div>
                    <div x-show="open" x-cloak class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg max-h-56 overflow-y-auto">
                        <template x-for="[id,name] in Object.entries(options)" :key="id"><div @click="toggle(id)" class="px-3 py-2 text-sm cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center justify-between" :class="isSelected(id)?'bg-blue-50 text-blue-700 font-medium':'text-gray-700 dark:text-gray-300'"><span x-text="name"></span><span x-show="isSelected(id)" class="text-xs">✓</span></div></template>
                    </div>
                </div>
                @error('ods') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Metas (carga automática al seleccionar ODS) --}}
            @if($metasList->count())
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Metas que Contribuye</label>
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
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha Inicio <span class="text-red-500">*</span></label>
                    <input type="date" wire:model.blur="fecha_inicio" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500" />
                    @error('fecha_inicio') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha Finalización <span class="text-red-500">*</span></label>
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
                    <p class="text-xs text-blue-600 dark:text-blue-400">Coordinador del proyecto</p>
                </div>
            </div>

            {{-- Empleados integrantes --}}
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Empleados Integrantes</h4>
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
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Participación de Estudiantes <span class="text-red-500">*</span></h4>
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
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Integrantes Internacionales</h4>
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
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">País</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Institución</th>
                                <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($integrante_internacional_proyecto as $i => $int)
                            <tr>
                                <td class="px-4 py-2 text-gray-900 dark:text-white">{{ $int['nombre'] ?: 'Integrante #'.($int['integrante_internacional_id'] ?? '-') }}</td>
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $int['rtn'] ?? '-' }}</td>
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $int['pais'] ?? '-' }}</td>
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $int['institucion'] ?? '-' }}</td>
                                <td class="px-4 py-2 text-right"><button wire:click="removeInternacional({{ $i }})" type="button" class="text-xs text-red-600 hover:text-red-800">Eliminar</button></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
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
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Cantidad Mujeres</label>
                                <input type="number" wire:model="nuevoEstudiante.cantidad_estudiantes_mujeres" min="0"
                                    class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500" />
                            </div>
                        </div>
                        <p class="text-xs text-gray-500">
                            Total: <strong>{{ (int)($nuevoEstudiante['cantidad_estudiantes_hombres'] ?? 0) + (int)($nuevoEstudiante['cantidad_estudiantes_mujeres'] ?? 0) }}</strong> estudiantes
                        </p>
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
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Crear / Seleccionar Integrante Internacional</h4>
                        <button wire:click="closeInternacionalModal" type="button" class="text-gray-500 hover:text-gray-800 text-lg leading-none">✕</button>
                    </div>
                    <div class="p-5 space-y-4">
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
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">RTN <span class="text-xs text-gray-400">(opcional, 14 dígitos)</span></label>
                                <input type="text" wire:model.live.debounce.1000ms="nuevoIntegranteInternacional.rtn" maxlength="14" placeholder="00000000000000" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500" />
                                @error('nuevoIntegranteInternacional.rtn') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Sexo</label>
                                <select wire:model.live="nuevoIntegranteInternacional.sexo" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500">
                                    <option value="">No especificado</option>
                                    <option value="masculino">Masculino</option>
                                    <option value="femenino">Femenino</option>
                                    <option value="otro">Otro</option>
                                </select>
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
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-gray-200 dark:border-gray-700 px-5 py-3">
                        <button wire:click="closeInternacionalModal" type="button" class="px-3 py-1.5 text-xs font-medium rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 hover:bg-gray-200">Cancelar</button>
                        <button wire:click="saveNuevoIntegranteInternacional" type="button" class="px-3 py-1.5 text-xs font-medium rounded-md bg-orange-600 text-white hover:bg-orange-700">Guardar integrante</button>
                    </div>
                </div>
            </div>
        </div>
        @endif
        @endif

        {{-- ══════════════════ PASO 3: Entidades Contraparte ══════════════════ --}}
        @if($currentStep === 3)
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Paso 3: Entidades Contraparte</h3>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-500">Entidades socias del proyecto.</p>
                <button wire:click="openContraparteModal" type="button"
                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                    + Agregar entidad
                </button>
            </div>

            @php
                $contrapartesConNombre = collect($entidad_contraparte)->filter(fn($contraparte) => !empty($contraparte['nombre'] ?? null));
                $instrumentoLabels = [
                    'carta_formal_solicitud' => 'Carta formal de solicitud',
                    'carta_intenciones' => 'Carta de intenciones',
                    'convenio_marco' => 'Convenio marco',
                ];
            @endphp

            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Nombre</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">RTN</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Tipo de entidad</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Contacto</th>
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
                                    {{ !empty($contraparte['tipo_entidad']) ? ucfirst(str_replace('_', ' ', $contraparte['tipo_entidad'])) : 'No especificado' }}
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
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">RTN <span class="text-xs text-gray-400">(opcional, 14 dígitos)</span></label>
                                <input type="text" wire:model="nuevaContraparte.rtn" maxlength="14" placeholder="00000000000000" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500" />
                                @error('nuevaContraparte.rtn') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Nombre <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="nuevaContraparte.nombre" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500" />
                                @error('nuevaContraparte.nombre') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Tipo de Entidad <span class="text-red-500">*</span></label>
                                <select wire:model="nuevaContraparte.tipo_entidad" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500">
                                    <option value="">Seleccione...</option>
                                    <option value="internacional">Internacional</option>
                                    <option value="gobierno_nacional">Gobierno Nacional</option>
                                    <option value="gobierno_municipal">Gobierno Municipal</option>
                                    <option value="ong">ONG</option>
                                    <option value="sociedad_civil">Sociedad Civil</option>
                                    <option value="sector_privado">Sector Privado</option>
                                </select>
                                @error('nuevaContraparte.tipo_entidad') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Nombre Contacto</label>
                                <input type="text" wire:model="nuevaContraparte.nombre_contacto" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500" />
                                @error('nuevaContraparte.nombre_contacto') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Cargo</label>
                                <input type="text" wire:model="nuevaContraparte.cargo_contacto" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500" />
                                @error('nuevaContraparte.cargo_contacto') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Teléfono</label>
                                <input type="text" wire:model="nuevaContraparte.telefono" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500" />
                                @error('nuevaContraparte.telefono') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Correo</label>
                                <input type="email" wire:model="nuevaContraparte.correo" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500" />
                                @error('nuevaContraparte.correo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Descripción de Acuerdos</label>
                                <textarea wire:model="nuevaContraparte.descripcion_acuerdos" rows="2" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500"></textarea>
                                @error('nuevaContraparte.descripcion_acuerdos') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        {{-- Instrumentos de formalización --}}
                        <div class="mt-2">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-xs font-semibold text-gray-600 dark:text-gray-400">Instrumentos de Formalización</p>
                                <button wire:click="addInstrumentoToModal" type="button" class="text-xs text-blue-600 hover:text-blue-800">+ Agregar</button>
                            </div>
                            @foreach($nuevaContraparte['instrumento_formalizacion'] ?? [] as $ii => $inst)
                            <div class="mb-2 p-3 bg-gray-50 dark:bg-gray-800 rounded-md border border-gray-200 dark:border-gray-600">
                                <div class="grid grid-cols-1 sm:grid-cols-[1fr_1fr_auto] gap-2 items-start">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Tipo de instrumento <span class="text-red-500">*</span></label>
                                        <select wire:model="nuevaContraparte.instrumento_formalizacion.{{ $ii }}.tipo_documento" class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-sm focus:border-blue-500">
                                            <option value="">Tipo de documento...</option>
                                            <option value="carta_formal_solicitud">Carta formal de solicitud</option>
                                            <option value="carta_intenciones">Carta de intenciones</option>
                                            <option value="convenio_marco">Convenio marco</option>
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
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Paso 4: Actividades / Cronograma</h3>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-500">Actividades del proyecto con responsables y fechas.</p>
                <button wire:click="openActividadModal" type="button"
                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
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
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Horas</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Responsables</th>
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
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Descripción <span class="text-red-500">*</span></label>
                            <textarea wire:model="nuevaActividad.descripcion" rows="3" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500"></textarea>
                            @error('nuevaActividad.descripcion') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Fecha Inicio <span class="text-red-500">*</span></label>
                                <input type="date" wire:model.live="nuevaActividad.fecha_inicio"
                                    class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500 @error('nuevaActividad.fecha_inicio') border-red-500 @enderror" />
                                @error('nuevaActividad.fecha_inicio') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Fecha Fin <span class="text-red-500">*</span></label>
                                <input type="date" wire:model.live="nuevaActividad.fecha_finalizacion"
                                    @if($actividadFechaFinMin) min="{{ $actividadFechaFinMin }}" @endif
                                    class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500 @error('nuevaActividad.fecha_finalizacion') border-red-500 @enderror" />
                                @error('nuevaActividad.fecha_finalizacion') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Horas</label>
                                <input type="number" wire:model.blur.number="nuevaActividad.horas" min="0" step="1" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500" />
                                @error('nuevaActividad.horas') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Responsables</label>
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
                                <div class="min-h-[42px] w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-2 py-2 cursor-text flex flex-wrap gap-1.5 items-center" @click="open=true">
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
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Paso 5: Descripción del Proyecto</h3>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Resumen <span class="text-red-500">*</span></label>
                <textarea wire:model.live.debounce.1000ms="resumen" rows="8" class="w-full resize-y rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
                @error('resumen') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descripción de Participantes <span class="text-red-500">*</span></label>
                <textarea wire:model.live.debounce.1000ms="descripcion_participantes" rows="6" class="w-full resize-y rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
                @error('descripcion_participantes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Definición del Problema <span class="text-red-500">*</span></label>
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
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Alineamiento a la Reforma <span class="text-red-500">*</span></label>
                <textarea wire:model.live.debounce.1000ms="alineamiento_reforma" rows="5" class="w-full resize-y rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
                @error('alineamiento_reforma') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Impacto Deseado <span class="text-red-500">*</span></label>
                <textarea wire:model.live.debounce.1000ms="impacto_deseado" rows="5" class="w-full resize-y rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
                @error('impacto_deseado') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
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
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Beneficiarios por Grupo Étnico</h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                        <thead class="bg-gray-100 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300">Grupo Étnico</th>
                                <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600 dark:text-gray-300">Hombres</th>
                                <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600 dark:text-gray-300">Mujeres</th>
                                <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600 dark:text-gray-300">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @php
                            $grupos = [
                                ['label' => 'Indígenas', 'h' => 'indigenas_hombres', 'm' => 'indigenas_mujeres'],
                                ['label' => 'Afroamericanos', 'h' => 'afroamericanos_hombres', 'm' => 'afroamericanos_mujeres'],
                                ['label' => 'Mestizos', 'h' => 'mestizos_hombres', 'm' => 'mestizos_mujeres'],
                            ];
                            @endphp
                            @foreach($grupos as $g)
                            @php
                                $hKey = $g['h'] ?? null;
                                $mKey = $g['m'] ?? null;
                                $hValue = match ($hKey) {
                                    'indigenas_hombres' => (int) ($indigenas_hombres ?? 0),
                                    'afroamericanos_hombres' => (int) ($afroamericanos_hombres ?? 0),
                                    'mestizos_hombres' => (int) ($mestizos_hombres ?? 0),
                                    default => 0,
                                };
                                $mValue = match ($mKey) {
                                    'indigenas_mujeres' => (int) ($indigenas_mujeres ?? 0),
                                    'afroamericanos_mujeres' => (int) ($afroamericanos_mujeres ?? 0),
                                    'mestizos_mujeres' => (int) ($mestizos_mujeres ?? 0),
                                    default => 0,
                                };
                                $total = $hValue + $mValue;
                            @endphp
                            <tr class="bg-white dark:bg-gray-900">
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-300 font-medium text-xs">{{ $g['label'] }}</td>
                                <td class="px-4 py-2 text-center">
                                    <input type="number" wire:model.blur.number="{{ $g['h'] }}" wire:blur="calcTotales" min="0" step="1" inputmode="numeric"
                                        class="w-24 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-sm text-center focus:border-blue-500" />
                                    @error($g['h']) <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <input type="number" wire:model.blur.number="{{ $g['m'] }}" wire:blur="calcTotales" min="0" step="1" inputmode="numeric"
                                        class="w-24 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-sm text-center focus:border-blue-500" />
                                    @error($g['m']) <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </td>
                                <td class="px-4 py-2 text-center font-semibold text-gray-700 dark:text-gray-300">
                                    {{ $total }}
                                </td>
                            </tr>
                            @endforeach
                            <tr class="bg-gray-100 dark:bg-gray-700 font-bold border-t-2 border-gray-300 dark:border-gray-500">
                                <td class="px-4 py-2 text-gray-900 dark:text-white text-sm">Total General</td>
                                <td class="px-4 py-2 text-center text-gray-900 dark:text-white">{{ $hombres }}</td>
                                <td class="px-4 py-2 text-center text-gray-900 dark:text-white">{{ $mujeres }}</td>
                                <td class="px-4 py-2 text-center text-blue-700 dark:text-blue-400 text-base">{{ $poblacion_participante }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Zona geográfica --}}
            <div>
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Zona de Impacto</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Departamento(s)</label>
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
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Municipio(s)</label>
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
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Aldea</label>
                        <input type="text" wire:model.live.debounce.1000ms="aldea" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Caserío</label>
                        <input type="text" wire:model.live.debounce.1000ms="caserio" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500" />
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
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Paso 7: Marco Lógico</h3>
        <div class="space-y-4">
            {{-- Objetivo General (full width) --}}
            <div class="bg-blue-50 dark:bg-blue-900/10 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <label class="block text-sm font-semibold text-blue-800 dark:text-blue-300 mb-2">Objetivo General <span class="text-red-500">*</span></label>
                <textarea wire:model.live.debounce.1000ms="objetivo_general" rows="3" placeholder="Describe el propósito central del proyecto..."
                    class="w-full rounded-md border border-blue-300 dark:border-blue-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
                @error('objetivo_general') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Two-column layout: Objetivos Específicos (left) + Resultados (right) --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                {{-- Left: Objetivos Específicos list --}}
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Objetivos Específicos <span class="text-red-500">*</span></h4>
                        <button wire:click="addObjetivo" type="button"
                            class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                            + Agregar
                        </button>
                    </div>
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

                {{-- Right: Selected Objetivo Detail + Resultados --}}
                @php $objActivo = $objetivosEspecificos[$selectedObjetivoIndex] ?? null; @endphp
                @if($objActivo !== null)
                @php $objetivoActivoKey = $objActivo['wire_key'] ?? $objActivo['id'] ?? 'nuevo-'.$selectedObjetivoIndex; @endphp
                <div wire:key="objetivo-detalle-{{ $objetivoActivoKey }}" class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 space-y-3">
                    <div class="flex items-center justify-between mb-1">
                        <h5 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                            Objetivo Específico {{ $selectedObjetivoIndex + 1 }}
                        </h5>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Descripción <span class="text-red-500">*</span></label>
                        <textarea wire:key="objetivo-descripcion-{{ $objetivoActivoKey }}" wire:model.live.debounce.1000ms="objetivosEspecificos.{{ $selectedObjetivoIndex }}.descripcion" rows="3"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500"></textarea>
                        @error("objetivosEspecificos.{$selectedObjetivoIndex}.descripcion") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Resultados del objetivo activo --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs font-semibold text-gray-600 dark:text-gray-400">Resultados Esperados <span class="text-red-500">*</span></p>
                            <button wire:click="addResultado({{ $selectedObjetivoIndex }})" type="button"
                                class="text-xs text-blue-600 hover:text-blue-800">+ Agregar resultado</button>
                        </div>
                        @error("objetivosEspecificos.{$selectedObjetivoIndex}.resultados") <p class="text-red-500 text-xs mb-2">{{ $message }}</p> @enderror
                        <div class="space-y-2 max-h-72 overflow-y-auto pr-1">
                            @foreach($objActivo['resultados'] ?? [] as $ri => $resultado)
                            <div wire:key="resultado-{{ $objetivoActivoKey }}-{{ $resultado['wire_key'] ?? $resultado['id'] ?? 'nuevo-'.$ri }}" class="p-3 bg-gray-50 dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-600">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-semibold text-gray-500">R{{ $ri + 1 }}</span>
                                    <button wire:click="removeResultado({{ $selectedObjetivoIndex }}, {{ $ri }})" type="button"
                                        class="text-xs text-red-500 hover:text-red-700">✕</button>
                                </div>
                                <div class="grid grid-cols-1 gap-2">
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-0.5">Resultado <span class="text-red-500">*</span></label>
                                        <input type="text" wire:model.live.debounce.1000ms="objetivosEspecificos.{{ $selectedObjetivoIndex }}.resultados.{{ $ri }}.nombre_resultado"
                                            class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-xs focus:border-blue-500" />
                                        @error("objetivosEspecificos.{$selectedObjetivoIndex}.resultados.{$ri}.nombre_resultado") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-0.5">Indicador <span class="text-red-500">*</span></label>
                                        <input type="text" wire:model.live.debounce.1000ms="objetivosEspecificos.{{ $selectedObjetivoIndex }}.resultados.{{ $ri }}.nombre_indicador"
                                            class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-xs focus:border-blue-500" />
                                        @error("objetivosEspecificos.{$selectedObjetivoIndex}.resultados.{$ri}.nombre_indicador") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-0.5">Medio de Verificación <span class="text-red-500">*</span></label>
                                        <input type="text" wire:model.live.debounce.1000ms="objetivosEspecificos.{{ $selectedObjetivoIndex }}.resultados.{{ $ri }}.nombre_medio_verificacion"
                                            class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-xs focus:border-blue-500" />
                                        @error("objetivosEspecificos.{$selectedObjetivoIndex}.resultados.{$ri}.nombre_medio_verificacion") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-0.5">Plazo <span class="text-red-500">*</span></label>
                                        <select wire:model.live="objetivosEspecificos.{{ $selectedObjetivoIndex }}.resultados.{{ $ri }}.plazo"
                                            class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-xs focus:border-blue-500">
                                            <option value="">Seleccione...</option>
                                            <option value="corto_plazo">Corto plazo</option>
                                            <option value="mediano_plazo">Mediano plazo</option>
                                            <option value="largo_plazo">Largo plazo</option>
                                        </select>
                                        @error("objetivosEspecificos.{$selectedObjetivoIndex}.resultados.{$ri}.plazo") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>
                            @endforeach
                            @if(empty($objActivo['resultados']))
                            <p class="text-xs text-gray-500 text-center py-2">Sin resultados. Haz clic en "+ Agregar resultado".</p>
                            @endif
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- ══════════════════ PASO 8: Presupuesto ══════════════════ --}}
        @if($currentStep === 8)
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Paso 8: Presupuesto</h3>
        <div class="space-y-6">
            <div>
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Aporte Institucional UNAH</h4>
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
                                <td class="py-2 px-3"><input type="number" wire:model="aporte_institucional.{{ $i }}.cantidad" wire:change="updateAporteTotal({{ $i }})" min="0" @disabled(!($aporte['editable']??true)) class="w-24 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 disabled:bg-gray-100 disabled:text-gray-500 dark:disabled:bg-gray-800 px-2 py-1 text-sm text-center mx-auto block focus:border-blue-500" /></td>
                                <td class="py-2 px-3"><input type="number" wire:model="aporte_institucional.{{ $i }}.costo_unitario" wire:change="updateAporteTotal({{ $i }})" min="0" step="0.01" @disabled(!($aporte['editable']??true)) class="w-28 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 disabled:bg-gray-100 disabled:text-gray-500 dark:disabled:bg-gray-800 px-2 py-1 text-sm text-center mx-auto block focus:border-blue-500" /></td>
                                <td class="py-2 px-3 text-center font-medium text-gray-900 dark:text-white">L. {{ number_format($aporte['costo_total'] ?? 0, 2) }}</td>
                            </tr>
                            @endforeach
                            <tr class="border-t-2 border-gray-400 dark:border-gray-500 bg-gray-50 dark:bg-gray-800">
                                <td colspan="4" class="py-2 px-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Total Aporte UNAH</td>
                                <td class="py-2 px-3 text-center font-bold text-gray-900 dark:text-white">L. {{ number_format(collect($aporte_institucional)->sum('costo_total'), 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div>
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Otros Aportes (Lempiras)</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @php
                    $aportes = [
                        ['field' => 'aporte_contraparte',        'label' => 'Aporte Contraparte'],
                        ['field' => 'aporte_internacionales',    'label' => 'Aporte Internacionales'],
                        ['field' => 'aporte_otras_universidades','label' => 'Otras Universidades'],
                        ['field' => 'aporte_comunidad',          'label' => 'Aporte Comunidad'],
                        ['field' => 'otros_aportes',             'label' => 'Otros Aportes'],
                    ];
                    @endphp
                    @foreach($aportes as $a)
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ $a['label'] }}</label>
                        <input type="number" wire:model="{{ $a['field'] }}" min="0" step="0.01" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500" />
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- ══════════════════ PASO 9: Anexos ══════════════════ --}}
        @if($currentStep === 9)
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Paso 9: Anexos</h3>
        <div class="space-y-4">
            <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6">
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Agregar Anexo</label>
                        <input type="file" wire:model="newAnexo" class="w-full text-sm text-gray-600 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                        @error('newAnexo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <button wire:click="uploadAnexo" type="button" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                        Subir Anexo
                    </button>
                </div>
            </div>
            @if($record && $record->anexos->count())
            <div>
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Anexos Guardados ({{ $record->anexos->count() }})</h4>
                @foreach($record->anexos as $anexo)
                <div class="flex items-center justify-between py-2 px-3 border border-gray-200 dark:border-gray-700 rounded-lg mb-2">
                    <a href="{{ Storage::url($anexo->documento_url) }}" target="_blank" class="text-sm text-blue-600 hover:text-blue-800 truncate max-w-xs">{{ basename($anexo->documento_url) }}</a>
                    <button x-on:click.prevent="confirmDialog('¿Eliminar este anexo?', { type: 'danger' }).then((ok) => ok && $wire.deleteAnexo({{ $anexo->id }}))" type="button" class="text-xs text-red-600 hover:text-red-800 ml-3 shrink-0">Eliminar</button>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-sm text-gray-500 text-center py-4">Sin anexos guardados.</p>
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
