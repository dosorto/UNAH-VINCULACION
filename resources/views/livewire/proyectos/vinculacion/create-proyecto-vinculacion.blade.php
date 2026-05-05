<div>
    {{-- Step progress --}}
    <div class="mb-6 bg-white dark:bg-gray-900 shadow rounded-lg p-4">
        @php $stepLabels = ['Info General','Equipo','Contraparte','Actividades','Descripción','Marco Lógico','Presupuesto','Anexos','Firmas']; @endphp
        <div class="flex items-center overflow-x-auto gap-1">
            @foreach($stepLabels as $idx => $label)
                @php $step = $idx + 1; @endphp
                <button wire:click="goToStep({{ $step }})" type="button" class="flex flex-col items-center flex-1 min-w-[50px] p-1">
                    <span class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold mb-1
                        {{ $currentStep === $step ? 'bg-blue-600 text-white' : ($currentStep > $step ? 'bg-green-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400') }}">
                        {{ $currentStep > $step ? '✓' : $step }}
                    </span>
                    <span class="text-xs text-center hidden sm:block {{ $currentStep === $step ? 'text-blue-600 font-medium' : 'text-gray-500' }}">{{ $label }}</span>
                </button>
                @if($idx < count($stepLabels) - 1)
                    <div class="h-0.5 flex-1 max-w-[30px] {{ $currentStep > $step ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-700' }}"></div>
                @endif
            @endforeach
        </div>
    </div>

    <div class="bg-white dark:bg-gray-900 shadow rounded-lg p-6">

        {{-- STEP 1 --}}
        @if($currentStep === 1)
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Paso 1: Información General</h3>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre del Proyecto <span class="text-red-500">*</span></label>
                <input type="text" wire:model="nombre_proyecto" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500" />
                @error('nombre_proyecto') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Modalidad <span class="text-red-500">*</span></label>
                <select wire:model="modalidad_id" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <option value="">Seleccione...</option>
                    @foreach($modalidades as $id => $nombre) <option value="{{ $id }}">{{ $nombre }}</option> @endforeach
                </select>
                @error('modalidad_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Categorías <span class="text-red-500">*</span></label>
                <div x-data="{
                        open: false,
                        selected: $wire.entangle('categoria'),
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
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ejes Prioritarios UNAH <span class="text-red-500">*</span></label>
                <div x-data="{
                        open: false,
                        selected: $wire.entangle('ejes_prioritarios_unah'),
                        options: @js($ejesPrioritarios),
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
                @error('ejes_prioritarios_unah') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Facultades / Centros <span class="text-red-500">*</span></label>
                <div x-data="{
                        open: false,
                        selected: $wire.entangle('facultades_centros').live,
                        options: @js($facultadesCentros),
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
                @error('facultades_centros') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            @if($departamentosAcademicos->count())
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Departamentos Académicos</label>
                <div x-data="{
                        open: false,
                        selected: $wire.entangle('departamentos_academicos').live,
                        options: @js($departamentosAcademicos),
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
            </div>
            @endif
            @if($carrerasOpts->count())
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Carreras</label>
                <div x-data="{
                        open: false,
                        selected: $wire.entangle('carreras'),
                        options: @js($carrerasOpts),
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
            </div>
            @endif
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Programa al que Pertenece <span class="text-red-500">*</span></label>
                <input type="text" wire:model="programa_pertenece" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500" />
                @error('programa_pertenece') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Líneas de Investigación Académica <span class="text-red-500">*</span></label>
                <textarea wire:model="lineas_investigacion_academica" rows="3" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
                @error('lineas_investigacion_academica') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ODS <span class="text-red-500">*</span></label>
                <div x-data="{
                        open: false,
                        selected: $wire.entangle('ods').live,
                        options: @js($odsList),
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
                @error('ods') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            @if($metasList->count())
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Metas que Contribuye</label>
                <div x-data="{
                        open: false,
                        selected: $wire.entangle('metasContribuye'),
                        options: @js($metasList),
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
            </div>
            @endif
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha Inicio <span class="text-red-500">*</span></label>
                    <input type="date" wire:model="fecha_inicio" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500" />
                    @error('fecha_inicio') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha Finalización <span class="text-red-500">*</span></label>
                    <input type="date" wire:model="fecha_finalizacion" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500" />
                    @error('fecha_finalizacion') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>
        @endif

        {{-- STEP 2 --}}
        @if($currentStep === 2)
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Paso 2: Equipo Ejecutor</h3>
        <div class="space-y-6">
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Coordinador del Proyecto</p>
                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $coordNombre }}</p>
                <p class="text-xs text-gray-500">Coordinador (no editable)</p>
            </div>
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Empleados Integrantes</h4>
                    <button wire:click="addEmpleado" type="button" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">+ Agregar</button>
                </div>
                @forelse($empleado_proyecto as $i => $emp)
                <div class="mb-3 p-3 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Empleado</label>
                            <select wire:model="empleado_proyecto.{{ $i }}.empleado_id" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500">
                                <option value="">Seleccione...</option>
                                @foreach($empleados as $id => $nombre) <option value="{{ $id }}">{{ $nombre }}</option> @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Rol</label>
                            <input type="text" wire:model="empleado_proyecto.{{ $i }}.rol" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500" />
                        </div>
                    </div>
                    <div class="mt-2 text-right">
                        <button wire:click="removeEmpleado({{ $i }})" type="button" class="text-xs text-red-600 hover:text-red-800">Eliminar</button>
                    </div>
                </div>
                @empty
                <p class="text-sm text-gray-500 text-center py-3">Sin empleados agregados.</p>
                @endforelse
            </div>
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Participación de Estudiantes</h4>
                    <button wire:click="addEstudiante" type="button" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">+ Agregar</button>
                </div>
                @foreach($estudiante_proyecto as $i => $est)
                <div class="mb-3 p-3 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Tipo de Participación</label>
                            <select wire:model="estudiante_proyecto.{{ $i }}.tipo_participacion_estudiante" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500">
                                <option value="">Seleccione...</option>
                                <option value="Práctica Profesional">Práctica Profesional</option>
                                <option value="Servicio Social">Servicio Social</option>
                                <option value="Voluntariado">Voluntariado</option>
                                <option value="Asignatura">Asignatura</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Hombres</label>
                            <input type="number" wire:model="estudiante_proyecto.{{ $i }}.cantidad_estudiantes_hombres" wire:change="updateEstudianteTotal({{ $i }})" min="0" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Mujeres</label>
                            <input type="number" wire:model="estudiante_proyecto.{{ $i }}.cantidad_estudiantes_mujeres" wire:change="updateEstudianteTotal({{ $i }})" min="0" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500" />
                        </div>
                    </div>
                    <div class="flex items-center justify-between mt-2">
                        <span class="text-xs text-gray-500">Total: {{ $est['total_estudiantes'] ?? 0 }}</span>
                        <button wire:click="removeEstudiante({{ $i }})" type="button" class="text-xs text-red-600 hover:text-red-800">Eliminar</button>
                    </div>
                </div>
                @endforeach
            </div>
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Integrantes Internacionales</h4>
                    <button wire:click="addInternacional" type="button" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">+ Agregar</button>
                </div>
                @foreach($integrante_internacional_proyecto as $i => $int)
                <div class="mb-3 p-3 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <select wire:model="integrante_internacional_proyecto.{{ $i }}.integrante_internacional_id" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500">
                        <option value="">Seleccione...</option>
                        @foreach($internacionales as $id => $nombre) <option value="{{ $id }}">{{ $nombre }}</option> @endforeach
                    </select>
                    <div class="mt-2 text-right">
                        <button wire:click="removeInternacional({{ $i }})" type="button" class="text-xs text-red-600 hover:text-red-800">Eliminar</button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- STEP 3 --}}
        @if($currentStep === 3)
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Paso 3: Entidades Contraparte</h3>
        <div class="space-y-4">
            @foreach($entidad_contraparte as $ci => $contraparte)
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Entidad {{ $ci + 1 }}</h4>
                    @if(count($entidad_contraparte) > 1)
                    <button wire:click="removeContraparte({{ $ci }})" type="button" class="text-xs text-red-600 hover:text-red-800">Eliminar</button>
                    @endif
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Nombre</label>
                        <input type="text" wire:model="entidad_contraparte.{{ $ci }}.nombre" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Tipo de Entidad</label>
                        <select wire:model="entidad_contraparte.{{ $ci }}.tipo_entidad" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500">
                            <option value="">Seleccione...</option>
                            <option value="internacional">Internacional</option>
                            <option value="gobierno_nacional">Gobierno Nacional</option>
                            <option value="gobierno_municipal">Gobierno Municipal</option>
                            <option value="ong">ONG</option>
                            <option value="sociedad_civil">Sociedad Civil</option>
                            <option value="sector_privado">Sector Privado</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Nombre de Contacto</label>
                        <input type="text" wire:model="entidad_contraparte.{{ $ci }}.nombre_contacto" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Cargo de Contacto</label>
                        <input type="text" wire:model="entidad_contraparte.{{ $ci }}.cargo_contacto" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Teléfono</label>
                        <input type="text" wire:model="entidad_contraparte.{{ $ci }}.telefono" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Correo</label>
                        <input type="email" wire:model="entidad_contraparte.{{ $ci }}.correo" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Descripción de Acuerdos</label>
                        <textarea wire:model="entidad_contraparte.{{ $ci }}.descripcion_acuerdos" rows="2" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500"></textarea>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Instrumentos de Formalización</p>
                        <button wire:click="addInstrumento({{ $ci }})" type="button" class="text-xs text-blue-600 hover:text-blue-800">+ Agregar</button>
                    </div>
                    @foreach($contraparte['instrumento_formalizacion'] ?? [] as $ii => $inst)
                    <div class="mb-3 rounded-md border border-gray-200 dark:border-gray-700 p-3">
                        <div class="grid grid-cols-1 lg:grid-cols-[1fr_1fr_auto] gap-2 items-start">
                            <div>
                                <select wire:model="entidad_contraparte.{{ $ci }}.instrumento_formalizacion.{{ $ii }}.tipo_documento" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-2 py-1 text-sm focus:border-blue-500">
                                    <option value="">Tipo de documento...</option>
                                    <option value="carta_formal_solicitud">Carta formal de solicitud a la unidad académica</option>
                                    <option value="carta_intenciones">Carta de intenciones con la UNAH</option>
                                    <option value="convenio_marco">Convenio marco con la UNAH</option>
                                </select>
                                @error("entidad_contraparte.$ci.instrumento_formalizacion.$ii.tipo_documento") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <input type="file" wire:model="entidad_contraparte.{{ $ci }}.instrumento_formalizacion.{{ $ii }}.documento_file" class="w-full text-xs text-gray-600 dark:text-gray-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                                @if(!empty($inst['documento_url']))
                                    <a href="{{ Storage::url($inst['documento_url']) }}" target="_blank" class="mt-1 inline-block text-xs text-blue-600 hover:text-blue-800">Documento actual</a>
                                @endif
                                @error("entidad_contraparte.$ci.instrumento_formalizacion.$ii.documento_file") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <button wire:click="removeInstrumento({{ $ci }}, {{ $ii }})" type="button" class="text-xs text-red-600 hover:text-red-800 whitespace-nowrap lg:mt-1">Eliminar</button>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
            <button wire:click="addContraparte" type="button" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600">
                + Agregar Entidad Contraparte
            </button>
        </div>
        @endif

        {{-- STEP 4 --}}
        @if($currentStep === 4)
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Paso 4: Actividades / Cronograma</h3>
        <div class="space-y-4">
            @foreach($actividades as $i => $actividad)
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Actividad {{ $i + 1 }}</h4>
                    @if(count($actividades) > 1)
                    <button wire:click="removeActividad({{ $i }})" type="button" class="text-xs text-red-600 hover:text-red-800">Eliminar</button>
                    @endif
                </div>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Descripción</label>
                        <textarea wire:model="actividades.{{ $i }}.descripcion" rows="2" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500"></textarea>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Fecha Inicio</label>
                            <input type="date" wire:model="actividades.{{ $i }}.fecha_inicio" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Fecha Finalización</label>
                            <input type="date" wire:model="actividades.{{ $i }}.fecha_finalizacion" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Horas</label>
                            <input type="number" wire:model="actividades.{{ $i }}.horas" min="0" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Responsables</label>
                        <select wire:model="actividades.{{ $i }}.empleados" multiple size="3" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500">
                            @foreach($empleados as $id => $nombre) <option value="{{ $id }}">{{ $nombre }}</option> @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Ctrl+click para seleccionar múltiples</p>
                    </div>
                </div>
            </div>
            @endforeach
            <button wire:click="addActividad" type="button" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600">
                + Agregar Actividad
            </button>
        </div>
        @endif

        {{-- STEP 5 --}}
        @if($currentStep === 5)
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Paso 5: Descripción y Beneficiarios</h3>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Resumen</label>
                <textarea wire:model="resumen" rows="4" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descripción de Participantes</label>
                <textarea wire:model="descripcion_participantes" rows="3" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Definición del Problema</label>
                <textarea wire:model="definicion_problema" rows="3" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Beneficiarios por Etnia</h4>
                <div class="overflow-x-auto">
                    <table class="text-sm">
                        <thead>
                            <tr class="text-xs text-gray-500">
                                <th class="text-left py-1 pr-6">Grupo</th>
                                <th class="text-center py-1 px-3">Hombres</th>
                                <th class="text-center py-1 px-3">Mujeres</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="py-1 pr-6 text-gray-700 dark:text-gray-300">Indígenas</td>
                                <td class="py-1 px-3"><input type="number" wire:model="indigenas_hombres" wire:change="calcTotales" min="0" class="w-24 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-sm text-center" /></td>
                                <td class="py-1 px-3"><input type="number" wire:model="indigenas_mujeres" wire:change="calcTotales" min="0" class="w-24 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-sm text-center" /></td>
                            </tr>
                            <tr>
                                <td class="py-1 pr-6 text-gray-700 dark:text-gray-300">Afroamericanos</td>
                                <td class="py-1 px-3"><input type="number" wire:model="afroamericanos_hombres" wire:change="calcTotales" min="0" class="w-24 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-sm text-center" /></td>
                                <td class="py-1 px-3"><input type="number" wire:model="afroamericanos_mujeres" wire:change="calcTotales" min="0" class="w-24 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-sm text-center" /></td>
                            </tr>
                            <tr>
                                <td class="py-1 pr-6 text-gray-700 dark:text-gray-300">Mestizos</td>
                                <td class="py-1 px-3"><input type="number" wire:model="mestizos_hombres" wire:change="calcTotales" min="0" class="w-24 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-sm text-center" /></td>
                                <td class="py-1 px-3"><input type="number" wire:model="mestizos_mujeres" wire:change="calcTotales" min="0" class="w-24 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-sm text-center" /></td>
                            </tr>
                            <tr class="border-t border-gray-300 dark:border-gray-600 font-semibold">
                                <td class="py-1 pr-6 text-gray-900 dark:text-white">Total</td>
                                <td class="py-1 px-3 text-center text-gray-900 dark:text-white">{{ $hombres }}</td>
                                <td class="py-1 px-3 text-center text-gray-900 dark:text-white">{{ $mujeres }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <p class="text-sm mt-2 text-gray-700 dark:text-gray-300">Población Participante Total: <strong>{{ $poblacion_participante }}</strong></p>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Departamento(s)</label>
                    <select wire:model.live="departamento_geo" multiple size="4" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500">
                        @foreach($departamentosGeo as $id => $nombre) <option value="{{ $id }}">{{ $nombre }}</option> @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Ctrl+click para seleccionar múltiples</p>
                </div>
                @if($municipiosGeo->count())
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Municipio(s)</label>
                    <select wire:model="municipio_geo" multiple size="4" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500">
                        @foreach($municipiosGeo as $id => $nombre) <option value="{{ $id }}">{{ $nombre }}</option> @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Ctrl+click para seleccionar múltiples</p>
                </div>
                @endif
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Aldea</label>
                    <input type="text" wire:model="aldea" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Caserío</label>
                    <input type="text" wire:model="caserio" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500" />
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Alineamiento a la Reforma</label>
                <textarea wire:model="alineamiento_reforma" rows="2" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Impacto Deseado</label>
                <textarea wire:model="impacto_deseado" rows="2" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Metodología</label>
                <textarea wire:model="metodologia" rows="3" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bibliografía</label>
                <textarea wire:model="bibliografia" rows="2" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
            </div>
        </div>
        @endif

        {{-- STEP 6 --}}
        @if($currentStep === 6)
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Paso 6: Marco Lógico</h3>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Objetivo General <span class="text-red-500">*</span></label>
                <textarea wire:model="objetivo_general" rows="3" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
                @error('objetivo_general') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Objetivos Específicos <span class="text-red-500">*</span></h4>
                    <button wire:click="addObjetivo" type="button" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">+ Agregar Objetivo</button>
                </div>
                @foreach($objetivosEspecificos as $oi => $objetivo)
                <div class="mb-4 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h5 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Objetivo Específico {{ $oi + 1 }}</h5>
                        @if(count($objetivosEspecificos) > 1)
                        <button wire:click="removeObjetivo({{ $oi }})" type="button" class="text-xs text-red-600 hover:text-red-800">Eliminar</button>
                        @endif
                    </div>
                    <div class="mb-3">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Descripción <span class="text-red-500">*</span></label>
                        <textarea wire:model="objetivosEspecificos.{{ $oi }}.descripcion" rows="2" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500"></textarea>
                        @error("objetivosEspecificos.{$oi}.descripcion") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="ml-4">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs font-medium text-gray-500">Resultados Esperados</p>
                            <button wire:click="addResultado({{ $oi }})" type="button" class="text-xs text-blue-600 hover:text-blue-800">+ Agregar Resultado</button>
                        </div>
                        @foreach($objetivo['resultados'] ?? [] as $ri => $resultado)
                        <div class="mb-2 p-3 bg-gray-50 dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-600">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Resultado</label>
                                    <input type="text" wire:model="objetivosEspecificos.{{ $oi }}.resultados.{{ $ri }}.nombre_resultado" class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-xs focus:border-blue-500" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Indicador</label>
                                    <input type="text" wire:model="objetivosEspecificos.{{ $oi }}.resultados.{{ $ri }}.nombre_indicador" class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-xs focus:border-blue-500" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Medio de Verificación</label>
                                    <input type="text" wire:model="objetivosEspecificos.{{ $oi }}.resultados.{{ $ri }}.nombre_medio_verificacion" class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-xs focus:border-blue-500" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Plazo</label>
                                    <select wire:model="objetivosEspecificos.{{ $oi }}.resultados.{{ $ri }}.plazo" class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-xs focus:border-blue-500">
                                        <option value="corto_plazo">Corto plazo</option>
                                        <option value="mediano_plazo">Mediano plazo</option>
                                        <option value="largo_plazo">Largo plazo</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mt-1 text-right">
                                <button wire:click="removeResultado({{ $oi }}, {{ $ri }})" type="button" class="text-xs text-red-600 hover:text-red-800">Eliminar</button>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- STEP 7 --}}
        @if($currentStep === 7)
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Paso 7: Presupuesto</h3>
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
                            <tr class="border-t border-gray-200 dark:border-gray-700">
                                <td class="py-2 px-3 text-gray-700 dark:text-gray-300 text-xs">{{ $aporte['concepto_label'] ?? $aporte['concepto'] }}</td>
                                <td class="py-2 px-3 text-gray-500 text-xs">{{ $aporte['unidad_label'] ?? $aporte['unidad'] }}</td>
                                <td class="py-2 px-3"><input type="number" wire:model="aporte_institucional.{{ $i }}.cantidad" wire:change="updateAporteTotal({{ $i }})" min="0" @disabled(!($aporte['editable'] ?? true)) class="w-24 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 disabled:bg-gray-100 disabled:text-gray-500 dark:disabled:bg-gray-800 px-2 py-1 text-sm text-center mx-auto block focus:border-blue-500" /></td>
                                <td class="py-2 px-3"><input type="number" wire:model="aporte_institucional.{{ $i }}.costo_unitario" wire:change="updateAporteTotal({{ $i }})" min="0" step="0.01" @disabled(!($aporte['editable'] ?? true)) class="w-28 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 disabled:bg-gray-100 disabled:text-gray-500 dark:disabled:bg-gray-800 px-2 py-1 text-sm text-center mx-auto block focus:border-blue-500" /></td>
                                <td class="py-2 px-3 text-center font-medium text-gray-900 dark:text-white">{{ number_format($aporte['costo_total'] ?? 0, 2) }}</td>
                            </tr>
                            @endforeach
                            <tr class="border-t-2 border-gray-400 dark:border-gray-500 bg-gray-50 dark:bg-gray-800">
                                <td colspan="4" class="py-2 px-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Total Aporte UNAH</td>
                                <td class="py-2 px-3 text-center font-bold text-gray-900 dark:text-white">{{ number_format(collect($aporte_institucional)->sum('costo_total'), 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div>
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Otros Aportes</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Aporte Contraparte</label>
                        <input type="number" wire:model="aporte_contraparte" min="0" step="0.01" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Aporte Internacionales</label>
                        <input type="number" wire:model="aporte_internacionales" min="0" step="0.01" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Aporte Otras Universidades</label>
                        <input type="number" wire:model="aporte_otras_universidades" min="0" step="0.01" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Aporte Comunidad</label>
                        <input type="number" wire:model="aporte_comunidad" min="0" step="0.01" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Otros Aportes</label>
                        <input type="number" wire:model="otros_aportes" min="0" step="0.01" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500" />
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- STEP 8 --}}
        @if($currentStep === 8)
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Paso 8: Anexos</h3>
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
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Anexos Guardados</h4>
                @foreach($record->anexos as $anexo)
                <div class="flex items-center justify-between py-2 px-3 border border-gray-200 dark:border-gray-700 rounded-lg mb-2">
                    <a href="{{ Storage::url($anexo->documento_url) }}" target="_blank" class="text-sm text-blue-600 hover:text-blue-800 truncate max-w-xs">{{ basename($anexo->documento_url) }}</a>
                    <button wire:click="deleteAnexo({{ $anexo->id }})" wire:confirm="¿Eliminar este anexo?" type="button" class="text-xs text-red-600 hover:text-red-800 ml-3 shrink-0">Eliminar</button>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-sm text-gray-500 text-center py-4">Sin anexos guardados.</p>
            @endif
        </div>
        @endif

        {{-- STEP 9 --}}
        @if($currentStep === 9)
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Paso 9: Firmas y Envío</h3>
        <div class="space-y-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">Seleccione a las personas que firmarán el proyecto.</p>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Jefe de Departamento</label>
                <select wire:model="jefe_empleado_id" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500">
                    <option value="">Seleccione...</option>
                    @foreach($empleadosMismoCentro as $id => $nombre) <option value="{{ $id }}">{{ $nombre }}</option> @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Decano / Director de Centro</label>
                <select wire:model="decano_empleado_id" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500">
                    <option value="">Seleccione...</option>
                    @foreach($empleadosMismoCentro as $id => $nombre) <option value="{{ $id }}">{{ $nombre }}</option> @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Enlace de Vinculación</label>
                <select wire:model="enlace_empleado_id" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500">
                    <option value="">Seleccione...</option>
                    @foreach($empleados as $id => $nombre) <option value="{{ $id }}">{{ $nombre }}</option> @endforeach
                </select>
            </div>
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <p class="text-sm text-blue-800 dark:text-blue-200">
                    Al hacer clic en <strong>"Enviar para Firmar"</strong> el proyecto iniciará el proceso de firmas. Al hacer clic en <strong>"Guardar como Borrador"</strong> se guardará sin enviar.
                </p>
            </div>
        </div>
        @endif

        {{-- Navigation --}}
        <div class="mt-8 flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
            <div>
                @if($currentStep > 1)
                <button wire:click="prevStep" type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Anterior
                </button>
                @endif
            </div>
            <div class="flex items-center gap-3">
                @if($currentStep === 9)
                <button wire:click="borrador" type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50">
                    Guardar como Borrador
                </button>
                <button wire:click="create" type="button" class="inline-flex items-center px-4 py-2 rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    Enviar para Firmar
                </button>
                @else
                <button wire:click="nextStep" type="button" class="inline-flex items-center px-4 py-2 rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    Siguiente
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
                @endif
            </div>
        </div>
    </div>
</div>
