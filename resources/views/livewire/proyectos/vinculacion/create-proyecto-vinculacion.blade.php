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
            10 => 'Firmas',
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
                @if($step < 10)
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

            @php
            $multiSelectFields = [
                ['wire' => 'categoria',                'label' => 'Categorías',                   'opts' => $categorias,           'req' => true],
                ['wire' => 'ejes_prioritarios_unah',   'label' => 'Ejes Prioritarios UNAH',       'opts' => $ejesPrioritarios,      'req' => true],
                ['wire' => 'facultades_centros',        'label' => 'Facultades / Centros',         'opts' => $facultadesCentros,     'req' => true, 'live' => true],
            ];
            @endphp
            
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

            @if($departamentosAcademicos->count())
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Departamentos Académicos</label>
                <div x-data="{open:false,selected:$wire.entangle('departamentos_academicos').live,options:@js($departamentosAcademicos),toggle(id){id=String(id);let c=(this.selected||[]).map(String);const i=c.indexOf(id);i===-1?c.push(id):c.splice(i,1);this.selected=c;},isSelected(id){return(this.selected||[]).map(String).includes(String(id));},getName(id){return this.options[id]??this.options[String(id)]??id;}}" @click.outside="open=false" class="relative">
                    <div @click="open=!open" class="min-h-[42px] w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 cursor-pointer flex flex-wrap gap-1 items-center">
                        <template x-for="id in (selected||[])" :key="id"><span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300"><span x-text="getName(id)"></span><button type="button" @click.stop="toggle(id)" class="font-bold leading-none">×</button></span></template>
                        <span x-show="!selected||selected.length===0" class="text-gray-400 text-sm">Seleccione...</span>
                        <span class="ml-auto text-gray-400 text-xs" x-text="open?'▴':'▾'"></span>
                    </div>
                    <div x-show="open" x-cloak class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg max-h-48 overflow-y-auto">
                        <template x-for="[id,name] in Object.entries(options)" :key="id"><div @click="toggle(id)" class="px-3 py-2 text-sm cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center justify-between" :class="isSelected(id)?'bg-blue-50 text-blue-700 font-medium':'text-gray-700 dark:text-gray-300'"><span x-text="name"></span><span x-show="isSelected(id)" class="text-blue-600 text-xs">✓</span></div></template>
                    </div>
                </div>
            </div>
            @endif
            @if($carrerasOpts->count())
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Carreras</label>
                <div x-data="{
                        open: false,
                        selected: $wire.entangle('carreras').live,
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
                        <template x-for="[id,name] in Object.entries(options)" :key="id">
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
                        <template x-for="id in (selected||[])" :key="id"><span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300"><span x-text="getName(id)"></span><button type="button" @click.stop="toggle(id)" class="font-bold">×</button></span></template>
                        <span x-show="!selected||selected.length===0" class="text-gray-400 text-sm">Seleccione los ODS...</span>
                        <span class="ml-auto text-gray-400 text-xs" x-text="open?'▴':'▾'"></span>
                    </div>
                    <div x-show="open" x-cloak class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg max-h-56 overflow-y-auto">
                        <template x-for="[id,name] in Object.entries(options)" :key="id"><div @click="toggle(id)" class="px-3 py-2 text-sm cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center justify-between" :class="isSelected(id)?'bg-green-50 text-green-700 font-medium':'text-gray-700 dark:text-gray-300'"><span x-text="name"></span><span x-show="isSelected(id)" class="text-xs">✓</span></div></template>
                    </div>
                </div>
                @error('ods') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Metas (carga automática al seleccionar ODS) --}}
            @if($metasList->count())
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Metas que Contribuye</label>
                <div wire:key="metas-contribuye-{{ md5(json_encode($metasDisponibles)) }}" wire:ignore x-data="{
                        open: false,
                        selected: @js($metasContribuye),
                        options: @js($metasDisponibles),
                        toggle(id) {
                            id = String(id);
                            let curr = (this.selected || []).map(String);
                            const i = curr.indexOf(id);
                            if (i === -1) curr.push(id); else curr.splice(i, 1);
                            this.selected = curr;
                            @this.set('metasContribuye', curr, false);
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
                    <div x-show="open" x-cloak class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-green-200 dark:border-green-700 rounded-md shadow-lg max-h-56 overflow-y-auto">
                        <template x-for="[id,name] in Object.entries(options)" :key="id"><div @click="toggle(id)" class="px-3 py-2 text-xs cursor-pointer hover:bg-green-50 flex items-start gap-2" :class="isSelected(id)?'bg-green-50 text-green-700 font-medium':'text-gray-700 dark:text-gray-300'"><span class="mt-0.5 shrink-0" x-show="isSelected(id)">✓</span><span x-text="name"></span></div></template>
                    </div>
                </div>
                <p class="text-xs text-green-600 mt-1">{{ $metasList->count() }} metas disponibles para los ODS seleccionados.</p>
            </div>
            @endif

            {{-- Fechas --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha Inicio <span class="text-red-500">*</span></label>
                    <input type="date" wire:model.live.debounce.1000ms="fecha_inicio" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500" />
                    @error('fecha_inicio') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha Finalización <span class="text-red-500">*</span></label>
                    <input type="date" wire:model.live.debounce.1000ms="fecha_finalizacion" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500" />
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
                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-purple-600 text-white hover:bg-purple-700">
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
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300">
                                        {{ $est['tipo_participacion_estudiante'] ?: '-' }}
                                    </span>
                                    @if(($est['tipo_participacion_estudiante'] ?? '') === 'Practica Asignatura')
                                    <p class="text-xs text-gray-500 mt-0.5">Asignatura requerida</p>
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
                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-orange-600 text-white hover:bg-orange-700">
                        + Agregar internacional
                    </button>
                </div>
                @if(count($integrante_internacional_proyecto))
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="min-w-full text-sm divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Nombre</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">País</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Institución</th>
                                <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($integrante_internacional_proyecto as $i => $int)
                            <tr>
                                <td class="px-4 py-2 text-gray-900 dark:text-white">{{ $int['nombre'] ?: 'Integrante #'.($int['integrante_internacional_id'] ?? '-') }}</td>
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
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Asignatura <span class="text-red-500">*</span></label>
                                <select wire:model="nuevoEstudiante.asignatura_id" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500">
                                    <option value="">Seleccione...</option>
                                    @foreach($asignaturas as $id => $nombre) <option value="{{ $id }}">{{ $nombre }}</option> @endforeach
                                </select>
                                @error('nuevoEstudiante.asignatura_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Periodo Académico <span class="text-red-500">*</span></label>
                                <select wire:model="nuevoEstudiante.periodo_academico_id" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500">
                                    <option value="">Seleccione...</option>
                                    @foreach($periodosAcademicos as $id => $nombre) <option value="{{ $id }}">{{ $nombre }}</option> @endforeach
                                </select>
                                @error('nuevoEstudiante.periodo_academico_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
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
                        <button wire:click="saveEstudiante" type="button" class="px-3 py-1.5 text-xs font-medium rounded-md bg-purple-600 text-white hover:bg-purple-700">Guardar</button>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Modal: Crear integrante internacional --}}
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
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Nombre completo <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="nuevoIntegranteInternacional.nombre_completo" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500" />
                                @error('nuevoIntegranteInternacional.nombre_completo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Pasaporte / Documento <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="nuevoIntegranteInternacional.documento_identidad" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500" />
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Nombre completo</label>
                                <input type="text" wire:model.live.debounce.1000ms="nuevoIntegranteInternacional.nombre_completo" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500" />
                                @error('nuevoIntegranteInternacional.nombre_completo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Pasaporte / Documento</label>
                                <input type="text" wire:model.live.debounce.1000ms="nuevoIntegranteInternacional.documento_identidad" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500" />
                                @error('nuevoIntegranteInternacional.documento_identidad') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
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
                                <input type="email" wire:model="nuevoIntegranteInternacional.email" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500" />
                                @error('nuevoIntegranteInternacional.email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">País <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="nuevoIntegranteInternacional.pais" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500" />
                                @error('nuevoIntegranteInternacional.pais') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Institución <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="nuevoIntegranteInternacional.institucion" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500" />
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Correo electrónico</label>
                                <input type="email" wire:model.live.debounce.1000ms="nuevoIntegranteInternacional.email" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500" />
                                @error('nuevoIntegranteInternacional.email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">País</label>
                                <input type="text" wire:model.live.debounce.1000ms="nuevoIntegranteInternacional.pais" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500" />
                                @error('nuevoIntegranteInternacional.pais') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Institución</label>
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

            @if(count(array_filter(array_column($entidad_contraparte, 'nombre'))))
            <div class="space-y-3">
                @foreach($entidad_contraparte as $ci => $contraparte)
                @if(!empty($contraparte['nombre']))
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $contraparte['nombre'] }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">
                                {{ $contraparte['tipo_entidad'] ? ucfirst(str_replace('_', ' ', $contraparte['tipo_entidad'])) : 'Tipo no definido' }}
                                @if($contraparte['nombre_contacto']) · {{ $contraparte['nombre_contacto'] }} @endif
                            </p>
                            @if(count($contraparte['instrumento_formalizacion'] ?? []))
                            <div class="mt-2 flex flex-wrap gap-1">
                                @foreach($contraparte['instrumento_formalizacion'] as $inst)
                                @if($inst['tipo_documento'])
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                    {{ str_replace('_', ' ', $inst['tipo_documento']) }}
                                    @if($inst['documento_url']) <span class="ml-1 text-green-600">✓</span> @endif
                                </span>
                                @endif
                                @endforeach
                            </div>
                            @endif
                        </div>
                        <div class="flex items-center gap-2 ml-3 shrink-0">
                            <button wire:click="openContraparteModal({{ $ci }})" type="button"
                                class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400">
                                Editar
                            </button>
                            <button wire:click="removeContraparte({{ $ci }})" type="button" wire:confirm="¿Eliminar esta entidad contraparte?"
                                class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded bg-red-50 text-red-700 hover:bg-red-100 dark:bg-red-900/30 dark:text-red-400">
                                Eliminar
                            </button>
                        </div>
                    </div>
                </div>
                @endif
                @endforeach
            </div>
            @else
            <p class="text-sm text-gray-500 text-center py-6 border border-dashed border-gray-300 dark:border-gray-600 rounded-lg">Sin entidades contraparte agregadas.</p>
            @endif
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
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Nombre <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="nuevaContraparte.nombre" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500" />
                                @error('nuevaContraparte.nombre') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Tipo de Entidad</label>
                                <select wire:model="nuevaContraparte.tipo_entidad" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500">
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
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Nombre Contacto</label>
                                <input type="text" wire:model="nuevaContraparte.nombre_contacto" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Cargo</label>
                                <input type="text" wire:model="nuevaContraparte.cargo_contacto" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Teléfono</label>
                                <input type="text" wire:model="nuevaContraparte.telefono" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Correo</label>
                                <input type="email" wire:model="nuevaContraparte.correo" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500" />
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Descripción de Acuerdos</label>
                                <textarea wire:model="nuevaContraparte.descripcion_acuerdos" rows="2" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500"></textarea>
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
                                <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-2 items-start">
                                    <select wire:model="nuevaContraparte.instrumento_formalizacion.{{ $ii }}.tipo_documento" class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-sm focus:border-blue-500">
                                        <option value="">Tipo de documento...</option>
                                        <option value="carta_formal_solicitud">Carta formal de solicitud</option>
                                        <option value="carta_intenciones">Carta de intenciones</option>
                                        <option value="convenio_marco">Convenio marco</option>
                                    </select>
                                    <button wire:click="removeInstrumentoFromModal({{ $ii }})" type="button" class="text-xs text-red-600 hover:text-red-800 whitespace-nowrap">Eliminar</button>
                                </div>
                                @if(!empty($inst['documento_url']))
                                    <a href="{{ Storage::url($inst['documento_url']) }}" target="_blank" class="mt-1 inline-block text-xs text-blue-600 hover:text-blue-800">Ver documento actual</a>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-gray-200 dark:border-gray-700 px-5 py-3">
                        <button wire:click="closeContraparteModal" type="button" class="px-3 py-1.5 text-xs font-medium rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 hover:bg-gray-200">Cancelar</button>
                        <button wire:click="saveContraparte" type="button" class="px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">Guardar</button>
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

            @if(count(array_filter(array_column($actividades, 'descripcion'))))
            <div class="space-y-3">
                @foreach($actividades as $i => $actividad)
                @if(!empty($actividad['descripcion']))
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">Act. {{ $i + 1 }}</span>
                                <p class="text-sm text-gray-900 dark:text-white font-medium truncate">{{ Str::limit($actividad['descripcion'], 80) }}</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500">
                                @if($actividad['fecha_inicio']) <span>Inicio: {{ $actividad['fecha_inicio'] }}</span> @endif
                                @if($actividad['fecha_finalizacion']) <span>Fin: {{ $actividad['fecha_finalizacion'] }}</span> @endif
                                @if($actividad['horas']) <span>{{ $actividad['horas'] }} hrs</span> @endif
                                @if(count($actividad['empleados'] ?? [])) <span>{{ count($actividad['empleados']) }} responsable(s)</span> @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <button wire:click="openActividadModal({{ $i }})" type="button"
                                class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400">
                                Editar
                            </button>
                            <button wire:click="removeActividad({{ $i }})" type="button" wire:confirm="¿Eliminar esta actividad?"
                                class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded bg-red-50 text-red-700 hover:bg-red-100 dark:bg-red-900/30 dark:text-red-400">
                                Eliminar
                            </button>
                        </div>
                    </div>
                </div>
                @endif
                @endforeach
            </div>
            @else
            <p class="text-sm text-gray-500 text-center py-6 border border-dashed border-gray-300 dark:border-gray-600 rounded-lg">Sin actividades agregadas.</p>
            @endif
        </div>

        {{-- Modal: Crear/Editar Actividad --}}
        @if($showActividadModal)
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
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Fecha Inicio</label>
                                <input type="date" wire:model="nuevaActividad.fecha_inicio" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Fecha Fin</label>
                                <input type="date" wire:model="nuevaActividad.fecha_finalizacion" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Horas</label>
                                <input type="number" wire:model="nuevaActividad.horas" min="0" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500" />
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
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Resumen</label>
                <textarea wire:model.live.debounce.1000ms="resumen" rows="4" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descripción de Participantes</label>
                <textarea wire:model.live.debounce.1000ms="descripcion_participantes" rows="3" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Definición del Problema</label>
                <textarea wire:model.live.debounce.1000ms="definicion_problema" rows="3" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
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
                                <td class="py-1 px-3"><input type="number" wire:model.live.debounce.1000ms="indigenas_hombres" wire:change="calcTotales" min="0" class="w-24 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-sm text-center" /></td>
                                <td class="py-1 px-3"><input type="number" wire:model.live.debounce.1000ms="indigenas_mujeres" wire:change="calcTotales" min="0" class="w-24 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-sm text-center" /></td>
                            </tr>
                            <tr>
                                <td class="py-1 pr-6 text-gray-700 dark:text-gray-300">Afroamericanos</td>
                                <td class="py-1 px-3"><input type="number" wire:model.live.debounce.1000ms="afroamericanos_hombres" wire:change="calcTotales" min="0" class="w-24 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-sm text-center" /></td>
                                <td class="py-1 px-3"><input type="number" wire:model.live.debounce.1000ms="afroamericanos_mujeres" wire:change="calcTotales" min="0" class="w-24 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-sm text-center" /></td>
                            </tr>
                            <tr>
                                <td class="py-1 pr-6 text-gray-700 dark:text-gray-300">Mestizos</td>
                                <td class="py-1 px-3"><input type="number" wire:model.live.debounce.1000ms="mestizos_hombres" wire:change="calcTotales" min="0" class="w-24 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-sm text-center" /></td>
                                <td class="py-1 px-3"><input type="number" wire:model.live.debounce.1000ms="mestizos_mujeres" wire:change="calcTotales" min="0" class="w-24 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-sm text-center" /></td>
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
                    <select wire:model.live="municipio_geo" multiple size="4" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500">
                        @foreach($municipiosGeo as $id => $nombre) <option value="{{ $id }}">{{ $nombre }}</option> @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Ctrl+click para seleccionar múltiples</p>
                </div>
                @endif
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Aldea</label>
                    <input type="text" wire:model.live.debounce.1000ms="aldea" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Caserío</label>
                    <input type="text" wire:model.live.debounce.1000ms="caserio" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500" />
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Alineamiento a la Reforma</label>
                <textarea wire:model.live.debounce.1000ms="alineamiento_reforma" rows="2" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Impacto Deseado</label>
                <textarea wire:model.live.debounce.1000ms="impacto_deseado" rows="2" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Metodología</label>
                <textarea wire:model.live.debounce.1000ms="metodologia" rows="3" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bibliografía</label>
                <textarea wire:model.live.debounce.1000ms="bibliografia" rows="2" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
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
                            <tr class="bg-white dark:bg-gray-900">
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-300 font-medium text-xs">{{ $g['label'] }}</td>
                                <td class="px-4 py-2 text-center">
                                    <input type="number" wire:model="{{ $g['h'] }}" wire:change="calcTotales" min="0"
                                        class="w-24 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-sm text-center focus:border-blue-500" />
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <input type="number" wire:model="{{ $g['m'] }}" wire:change="calcTotales" min="0"
                                        class="w-24 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-sm text-center focus:border-blue-500" />
                                </td>
                                <td class="px-4 py-2 text-center font-semibold text-gray-700 dark:text-gray-300">
                                    {{ ($this->$g['h'] ?? 0) + ($this->$g['m'] ?? 0) }}
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
                        <select wire:model.live="departamento_geo" multiple size="5"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500">
                            @foreach($departamentosGeo as $id => $nombre) <option value="{{ $id }}">{{ $nombre }}</option> @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Ctrl+clic para múltiples.</p>
                    </div>
                    @if($municipiosGeo->count())
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Municipio(s)</label>
                        <select wire:model="municipio_geo" multiple size="5"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500">
                            @foreach($municipiosGeo as $id => $nombre) <option value="{{ $id }}">{{ $nombre }}</option> @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Ctrl+clic para múltiples.</p>
                    </div>
                    @endif
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Aldea</label>
                        <input type="text" wire:model="aldea" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Caserío</label>
                        <input type="text" wire:model="caserio" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500" />
                    </div>
                </div>
            </div>
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
                        <div wire:click="selectObjetivo({{ $oi }})" class="cursor-pointer rounded-lg border-2 p-3 transition-colors
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
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 space-y-3">
                    <div class="flex items-center justify-between mb-1">
                        <h5 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                            Objetivo Específico {{ $selectedObjetivoIndex + 1 }}
                        </h5>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Descripción <span class="text-red-500">*</span></label>
                        <textarea wire:model="objetivosEspecificos.{{ $selectedObjetivoIndex }}.descripcion" rows="3"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500"></textarea>
                        @error("objetivosEspecificos.{$selectedObjetivoIndex}.descripcion") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Resultados del objetivo activo --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs font-semibold text-gray-600 dark:text-gray-400">Resultados Esperados</p>
                            <button wire:click="addResultado({{ $selectedObjetivoIndex }})" type="button"
                                class="text-xs text-blue-600 hover:text-blue-800">+ Agregar resultado</button>
                        </div>
                        <div class="space-y-2 max-h-72 overflow-y-auto pr-1">
                            @foreach($objActivo['resultados'] ?? [] as $ri => $resultado)
                            <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-600">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-semibold text-gray-500">R{{ $ri + 1 }}</span>
                                    <button wire:click="removeResultado({{ $selectedObjetivoIndex }}, {{ $ri }})" type="button"
                                        class="text-xs text-red-500 hover:text-red-700">✕</button>
                                </div>
                                <div class="grid grid-cols-1 gap-2">
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-0.5">Resultado</label>
                                        <input type="text" wire:model="objetivosEspecificos.{{ $selectedObjetivoIndex }}.resultados.{{ $ri }}.nombre_resultado"
                                            class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-xs focus:border-blue-500" />
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-0.5">Indicador</label>
                                        <input type="text" wire:model="objetivosEspecificos.{{ $selectedObjetivoIndex }}.resultados.{{ $ri }}.nombre_indicador"
                                            class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-xs focus:border-blue-500" />
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-0.5">Medio de Verificación</label>
                                        <input type="text" wire:model="objetivosEspecificos.{{ $selectedObjetivoIndex }}.resultados.{{ $ri }}.nombre_medio_verificacion"
                                            class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-xs focus:border-blue-500" />
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-0.5">Plazo</label>
                                        <select wire:model="objetivosEspecificos.{{ $selectedObjetivoIndex }}.resultados.{{ $ri }}.plazo"
                                            class="w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-2 py-1 text-xs focus:border-blue-500">
                                            <option value="corto_plazo">Corto plazo</option>
                                            <option value="mediano_plazo">Mediano plazo</option>
                                            <option value="largo_plazo">Largo plazo</option>
                                        </select>
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
                    <button wire:click="deleteAnexo({{ $anexo->id }})" wire:confirm="¿Eliminar este anexo?" type="button" class="text-xs text-red-600 hover:text-red-800 ml-3 shrink-0">Eliminar</button>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-sm text-gray-500 text-center py-4">Sin anexos guardados.</p>
            @endif
        </div>
        @endif

        {{-- ══════════════════ PASO 10: Firmas ══════════════════ --}}
        @if($currentStep === 10)
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Paso 10: Firmas y Envío</h3>
        <div class="space-y-5">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Seleccione los firmantes del proyecto.
                @if(!empty($facultades_centros))
                <span class="text-blue-600 dark:text-blue-400 font-medium">Mostrando empleados de las facultades/centros seleccionados en el Paso 1.</span>
                @endif
            </p>

            {{-- Buscador --}}
            <div>
                <input type="text" wire:model.live.debounce.300ms="firmaSearch"
                    placeholder="Buscar firmante por nombre o número de empleado..."
                    class="w-full sm:w-96 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500" />
                @if($firmantesOpts->isEmpty())
                <p class="text-xs text-amber-600 mt-1">Sin empleados en las facultades seleccionadas. Verifique el Paso 1.</p>
                @else
                <p class="text-xs text-gray-500 mt-1">{{ $firmantesOpts->count() }} empleado(s) disponibles.</p>
                @endif
            </div>

            @php
            $firmaFields = [
                ['field' => 'jefe_empleado_id',    'label' => 'Jefe de Departamento', 'icon' => '🏛️'],
                ['field' => 'decano_empleado_id',  'label' => 'Decano / Director de Centro', 'icon' => '🎓'],
                ['field' => 'enlace_empleado_id',  'label' => 'Enlace de Vinculación', 'icon' => '🔗'],
            ];
            @endphp

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                @foreach($firmaFields as $ff)
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        {{ $ff['icon'] }} {{ $ff['label'] }}
                    </label>
                    <select wire:model="{{ $ff['field'] }}"
                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500">
                        <option value="">Seleccione...</option>
                        @foreach($firmantesOpts as $firmante)
                            <option value="{{ $firmante->id }}">
                                {{ $firmante->nombre_completo }}
                                @if($firmante->numero_empleado) ({{ $firmante->numero_empleado }}) @endif
                            </option>
                        @endforeach
                    </select>
                    @if($this->{$ff['field']})
                    @php $sel = $firmantesOpts->firstWhere('id', $this->{$ff['field']}); @endphp
                    @if($sel)
                    <p class="text-xs text-green-600 dark:text-green-400 mt-1">✓ {{ $sel->nombre_completo }}</p>
                    @endif
                    @endif
                </div>
                @endforeach
            </div>

            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <p class="text-sm text-blue-800 dark:text-blue-200">
                    Al hacer clic en <strong>"Enviar para Firmar"</strong> el proyecto iniciará el proceso de firmas. Al hacer clic en <strong>"Guardar como Borrador"</strong> se guardará sin enviar.
                </p>
            </div>
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
                @if($currentStep === 10)
                <button wire:click="borrador" type="button"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50">
                    Guardar como Borrador
                </button>
                <button wire:click="create" type="button"
                    class="inline-flex items-center px-4 py-2 rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    Enviar para Firmar →
                </button>
                @else
                <button wire:click="nextStep" type="button"
                    class="inline-flex items-center px-4 py-2 rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    Siguiente →
                </button>
                @endif
            </div>
        </div>

    </div>
</div>
