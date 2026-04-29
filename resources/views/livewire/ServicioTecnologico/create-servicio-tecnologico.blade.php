<div>
    {{-- Step progress --}}
    <div class="mb-6 bg-white dark:bg-gray-900 shadow rounded-lg p-4">
        @php $stepLabels = ['Info General','Equipo','Actividades','Servicio','Beneficiarios','Presupuesto']; @endphp
        <div class="flex items-center overflow-x-auto gap-1">
            @foreach($stepLabels as $idx => $label)
                @php $step = $idx + 1; @endphp
                <div class="flex flex-col items-center flex-1 min-w-[50px] p-1">
                    <span class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold mb-1
                        {{ $currentStep === $step ? 'bg-blue-600 text-white' : ($currentStep > $step ? 'bg-green-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400') }}">
                        {{ $currentStep > $step ? '✓' : $step }}
                    </span>
                    <span class="text-xs text-center hidden sm:block {{ $currentStep === $step ? 'text-blue-600 font-medium' : 'text-gray-500' }}">{{ $label }}</span>
                </div>
                @if($idx < count($stepLabels) - 1)
                    <div class="h-0.5 flex-1 max-w-[30px] {{ $currentStep > $step ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-700' }}"></div>
                @endif
            @endforeach
        </div>
    </div>

    <div class="bg-white dark:bg-gray-900 shadow rounded-lg p-6">

        {{-- STEP 1: Información General --}}
        @if($currentStep === 1)
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Paso 1: Información General</h3>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre de la Acción <span class="text-red-500">*</span></label>
                <input type="text" wire:model="nombre_accion" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500" />
                @error('nombre_accion') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Modalidad</label>
                <select wire:model="modalidad_id" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500">
                    <option value="">Seleccione...</option>
                    @foreach($modalidades as $id => $nombre) <option value="{{ $id }}">{{ $nombre }}</option> @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Facultades / Centros</label>
                <select wire:model.live="facultades_centros" multiple size="4" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500">
                    @foreach($facultadesCentros as $id => $nombre) <option value="{{ $id }}">{{ $nombre }}</option> @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">Ctrl+click para seleccionar múltiples</p>
            </div>
            @if($departamentosAcademicos->count())
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Departamentos Académicos</label>
                <select wire:model.live="departamentos_academicos" multiple size="4" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500">
                    @foreach($departamentosAcademicos as $id => $nombre) <option value="{{ $id }}">{{ $nombre }}</option> @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">Ctrl+click para seleccionar múltiples</p>
            </div>
            @endif
            @if($carrerasOpts->count())
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Carreras</label>
                <select wire:model="carreras" multiple size="4" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500">
                    @foreach($carrerasOpts as $id => $nombre) <option value="{{ $id }}">{{ $nombre }}</option> @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">Ctrl+click para seleccionar múltiples</p>
            </div>
            @endif
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descripción del Servicio</label>
                <textarea wire:model="descripcion_servicio" rows="3" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descripción del Problema</label>
                <textarea wire:model="descripcion_problema" rows="3" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descripción del Participante</label>
                <textarea wire:model="descripcion_participante" rows="2" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha Inicio</label>
                    <input type="date" wire:model="fecha_inicio" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha Finalización</label>
                    <input type="date" wire:model="fecha_finalizacion" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500" />
                </div>
            </div>
        </div>
        @endif

        {{-- STEP 2: Equipo Ejecutor --}}
        @if($currentStep === 2)
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Paso 2: Equipo Ejecutor</h3>
        <div class="space-y-6">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Empleados</h4>
                    <button wire:click="addEmpleado" type="button" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">+ Agregar</button>
                </div>
                @forelse($empleados as $i => $emp)
                <div class="mb-3 p-3 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Empleado</label>
                            <select wire:model="empleados.{{ $i }}.empleado_id" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500">
                                <option value="">Seleccione...</option>
                                @foreach($empleadosOpts as $id => $nombre) <option value="{{ $id }}">{{ $nombre }}</option> @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Rol</label>
                            <input type="text" wire:model="empleados.{{ $i }}.rol" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500" />
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
        </div>
        @endif

        {{-- STEP 3: Actividades --}}
        @if($currentStep === 3)
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Paso 3: Actividades / Cronograma</h3>
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
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Responsable</label>
                            <input type="text" wire:model="actividades.{{ $i }}.responsable" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm focus:border-blue-500" />
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
            <button wire:click="addActividad" type="button" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200">
                + Agregar Actividad
            </button>
        </div>
        @endif

        {{-- STEP 4: Información del Servicio --}}
        @if($currentStep === 4)
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Paso 4: Información del Servicio</h3>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Objetivo General</label>
                <textarea wire:model="objetivo_general" rows="3" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Objetivo Específico</label>
                <textarea wire:model="objetivo_especifico" rows="3" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Resultados Esperados</label>
                <textarea wire:model="resultados_esperados" rows="3" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Indicadores de Resultados</label>
                <textarea wire:model="indicadores_resultados" rows="2" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descripción del Servicio de Infraestructura</label>
                <textarea wire:model="descripcion_servicio_infraestructura" rows="3" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ubicación</label>
                    <input type="text" wire:model="ubicacion" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Unidad Gestora</label>
                    <input type="text" wire:model="unidad_gestora" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500" />
                </div>
            </div>
        </div>
        @endif

        {{-- STEP 5: Beneficiarios --}}
        @if($currentStep === 5)
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Paso 5: Beneficiarios</h3>
        <div class="space-y-4">
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Beneficiarios por Etnia</h4>
                <div class="overflow-x-auto">
                    <table class="text-sm">
                        <thead><tr class="text-xs text-gray-500"><th class="text-left py-1 pr-6">Grupo</th><th class="text-center py-1 px-3">Hombres</th><th class="text-center py-1 px-3">Mujeres</th></tr></thead>
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
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Departamento</label>
                    <select wire:model.live="departamento_geo" multiple size="4" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500">
                        @foreach($departamentosGeo as $id => $nombre) <option value="{{ $id }}">{{ $nombre }}</option> @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Ctrl+click para seleccionar múltiples</p>
                </div>
                @if($municipiosGeo->count())
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Municipio</label>
                    <select wire:model="municipio_geo" multiple size="4" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500">
                        @foreach($municipiosGeo as $id => $nombre) <option value="{{ $id }}">{{ $nombre }}</option> @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Ctrl+click para seleccionar múltiples</p>
                </div>
                @endif
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Aldea</label>
                    <input type="text" wire:model="aldea" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500" />
                </div>
            </div>
        </div>
        @endif

        {{-- STEP 6: Presupuesto --}}
        @if($currentStep === 6)
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Paso 6: Presupuesto</h3>
        <div class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Aporte UNAH</label>
                    <input type="number" wire:model="aporte_unah" min="0" step="0.01" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Aporte Contraparte</label>
                    <input type="number" wire:model="aporte_contraparte" min="0" step="0.01" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Otros Aportes</label>
                    <input type="number" wire:model="otros_aportes" min="0" step="0.01" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500" />
                </div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-sm">
                <p class="font-medium text-gray-700 dark:text-gray-300">
                    Total: <span class="font-bold text-gray-900 dark:text-white">{{ number_format($aporte_unah + $aporte_contraparte + $otros_aportes, 2) }}</span>
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
            <div>
                @if($currentStep < 6)
                <button wire:click="nextStep" type="button" class="inline-flex items-center px-4 py-2 rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    Siguiente
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
                @else
                <button wire:click="create" type="button" class="inline-flex items-center px-4 py-2 rounded-md text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                    Crear Servicio Tecnológico
                </button>
                @endif
            </div>
        </div>
    </div>
</div>
