<div>
    {{-- Header --}}
    <div class="mb-6 flex items-start justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Ficha de Actualización</h2>
            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $record->nombre_proyecto }} ({{ $record->codigo_proyecto }})</p>
        </div>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">En Curso</span>
    </div>

    {{-- Step tabs --}}
    <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
        <nav class="flex gap-6">
            <button wire:click="$set('currentStep', 1)" type="button"
                class="pb-3 text-sm font-medium border-b-2 {{ $currentStep === 1 ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                1. Equipo Ejecutor
            </button>
            <button wire:click="$set('currentStep', 2)" type="button"
                class="pb-3 text-sm font-medium border-b-2 {{ $currentStep === 2 ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                2. Extensión de Tiempo
            </button>
        </nav>
    </div>

    <div class="bg-white dark:bg-gray-900 shadow rounded-lg p-6">

        {{-- STEP 1: Equipo --}}
        @if($currentStep === 1)
        <div class="space-y-6">

            {{-- Coordinador --}}
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Coordinador</p>
                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $coordinador?->nombre_completo ?? 'N/A' }}</p>
            </div>

            {{-- Equipo actual --}}
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Empleados Integrantes</h4>
                    <button wire:click="openNuevoEmpleado" type="button" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                        + Solicitar Nuevo
                    </button>
                </div>
                @forelse($empleadosProyecto as $ep)
                @php
                    $enBaja = $bajasPendientes->where('tipo_integrante', 'empleado')->where('integrante_id', $ep->empleado_id)->isNotEmpty();
                @endphp
                <div class="flex items-center justify-between py-2 px-3 border border-gray-200 dark:border-gray-700 rounded-lg mb-2 {{ $enBaja ? 'opacity-50' : '' }}">
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $ep->empleado?->nombre_completo }}</p>
                        <p class="text-xs text-gray-500">{{ $ep->rol }}</p>
                    </div>
                    @if(!$enBaja)
                    <button wire:click="openBaja({{ $ep->empleado_id }}, '{{ addslashes($ep->empleado?->nombre_completo) }}', 'empleado')" type="button"
                        class="text-xs text-red-600 hover:text-red-800 ml-3 shrink-0">Dar de Baja</button>
                    @else
                    <span class="text-xs text-yellow-600 ml-3">Baja pendiente</span>
                    @endif
                </div>
                @empty
                <p class="text-sm text-gray-500 text-center py-3">Sin empleados integrantes.</p>
                @endforelse
            </div>

            {{-- Internacionales --}}
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Integrantes Internacionales</h4>
                    <button wire:click="openNuevoInternacional" type="button" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                        + Solicitar Nuevo
                    </button>
                </div>
                @forelse($internacionalesProyecto as $ip)
                @php
                    $enBaja = $bajasPendientes->where('tipo_integrante', 'integrante_internacional')->where('integrante_id', $ip->integrante_internacional_id)->isNotEmpty();
                @endphp
                <div class="flex items-center justify-between py-2 px-3 border border-gray-200 dark:border-gray-700 rounded-lg mb-2 {{ $enBaja ? 'opacity-50' : '' }}">
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $ip->integranteInternacional?->nombre_completo }}</p>
                        <p class="text-xs text-gray-500">{{ $ip->integranteInternacional?->pais }} — {{ $ip->integranteInternacional?->institucion }}</p>
                    </div>
                    @if(!$enBaja)
                    <button wire:click="openBaja({{ $ip->integrante_internacional_id }}, '{{ addslashes($ip->integranteInternacional?->nombre_completo) }}', 'integrante_internacional')" type="button"
                        class="text-xs text-red-600 hover:text-red-800 ml-3 shrink-0">Dar de Baja</button>
                    @else
                    <span class="text-xs text-yellow-600 ml-3">Baja pendiente</span>
                    @endif
                </div>
                @empty
                <p class="text-sm text-gray-500 text-center py-3">Sin integrantes internacionales.</p>
                @endforelse
            </div>

            {{-- Bajas pendientes --}}
            @if($bajasPendientes->count())
            <div>
                <h4 class="text-sm font-medium text-yellow-700 dark:text-yellow-400 mb-3">Bajas Pendientes de Aprobación</h4>
                @foreach($bajasPendientes as $baja)
                <div class="flex items-center justify-between py-2 px-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg mb-2">
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $baja->nombre_integrante }}</p>
                        <p class="text-xs text-gray-500">{{ $baja->motivo_baja }}</p>
                    </div>
                    <button wire:click="reincorporar({{ $baja->id }})" wire:confirm="¿Reincorporar a este integrante y cancelar la baja?" type="button"
                        class="text-xs text-green-600 hover:text-green-800 ml-3 shrink-0">Reincorporar</button>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Nuevos pendientes --}}
            @if($nuevosPendientes->count())
            <div>
                <h4 class="text-sm font-medium text-blue-700 dark:text-blue-400 mb-3">Incorporaciones Pendientes de Aprobación</h4>
                @foreach($nuevosPendientes as $nuevo)
                <div class="flex items-center justify-between py-2 px-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg mb-2">
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $nuevo->nombre_integrante }}</p>
                        <p class="text-xs text-gray-500">{{ $nuevo->motivo_incorporacion }}</p>
                    </div>
                    <button wire:click="cancelarSolicitud({{ $nuevo->id }})" wire:confirm="¿Cancelar esta solicitud de incorporación?" type="button"
                        class="text-xs text-red-600 hover:text-red-800 ml-3 shrink-0">Cancelar</button>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Estudiantes --}}
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Participación de Estudiantes</h4>
                    <button wire:click="addEstudiante" type="button" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">+ Agregar</button>
                </div>
                @forelse($estudiante_proyecto as $i => $est)
                <div class="mb-3 p-3 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Tipo</label>
                            <p class="text-sm text-gray-900 dark:text-white">{{ $est['tipo_participacion_estudiante'] ?: '—' }}</p>
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
                        @if(empty($est['id']))
                        <button wire:click="removeEstudiante({{ $i }})" type="button" class="text-xs text-red-600 hover:text-red-800">Eliminar</button>
                        @endif
                    </div>
                </div>
                @empty
                <p class="text-sm text-gray-500 text-center py-3">Sin participación de estudiantes.</p>
                @endforelse
            </div>
        </div>
        @endif

        {{-- STEP 2: Extensión de tiempo --}}
        @if($currentStep === 2)
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha de Finalización Actual</label>
                <p class="text-sm text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800 rounded-md px-3 py-2 border border-gray-200 dark:border-gray-700">
                    {{ $record->fecha_finalizacion?->format('d/m/Y') ?? 'No definida' }}
                </p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nueva Fecha de Finalización (Ampliación)</label>
                <input type="date" wire:model.live="fecha_ampliacion" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500" />
            </div>
            @if($fecha_ampliacion)
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Motivo de la Ampliación <span class="text-red-500">*</span></label>
                <textarea wire:model="motivo_ampliacion" rows="3" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"></textarea>
                @error('motivo_ampliacion') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            @endif
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Motivo de Nuevas Responsabilidades</label>
                <textarea wire:model="motivo_responsabilidades_nuevos" rows="3" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"
                    placeholder="Justificación para nuevos integrantes (si aplica)..."></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Motivo de los Cambios</label>
                <textarea wire:model="motivo_razones_cambio" rows="3" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:border-blue-500"
                    placeholder="Razones generales de los cambios solicitados..."></textarea>
            </div>
        </div>
        @endif

        {{-- Navigation --}}
        <div class="mt-8 flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
            <div>
                @if($currentStep > 1)
                <button wire:click="prevStep" type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Anterior
                </button>
                @else
                <a href="{{ route('FichasActualizacionDocente') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Volver
                </a>
                @endif
            </div>
            <div class="flex items-center gap-3">
                @if($currentStep < 2)
                <button wire:click="nextStep" type="button" class="inline-flex items-center px-4 py-2 rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    Siguiente
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
                @else
                <button wire:click="save" type="button" class="inline-flex items-center px-4 py-2 rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    Enviar Ficha para Firmar
                </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Modal: Dar de Baja --}}
    @if($bajaModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-md mx-4">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Dar de Baja</h3>
                <p class="text-sm text-gray-500 mt-1">{{ $bajaNombre }}</p>
            </div>
            <div class="p-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Motivo de la Baja <span class="text-red-500">*</span></label>
                <textarea wire:model="bajaMotivo" rows="3" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500" placeholder="Indique el motivo..."></textarea>
                @error('bajaMotivo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                <button wire:click="$set('bajaModal', false)" type="button" class="px-4 py-2 text-sm font-medium rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">Cancelar</button>
                <button wire:click="confirmarBaja" type="button" class="px-4 py-2 text-sm font-medium rounded-md bg-red-600 text-white hover:bg-red-700">Confirmar Baja</button>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal: Nuevo Empleado --}}
    @if($nuevoEmpleadoModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-md mx-4">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Solicitar Nuevo Empleado</h3>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Empleado <span class="text-red-500">*</span></label>
                    <select wire:model="nuevoEmpleadoId" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500">
                        <option value="">Seleccione...</option>
                        @foreach($empleadosDisponibles as $id => $nombre) <option value="{{ $id }}">{{ $nombre }}</option> @endforeach
                    </select>
                    @error('nuevoEmpleadoId') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Motivo de Incorporación <span class="text-red-500">*</span></label>
                    <textarea wire:model="nuevoEmpleadoMotivo" rows="3" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500"></textarea>
                    @error('nuevoEmpleadoMotivo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                <button wire:click="$set('nuevoEmpleadoModal', false)" type="button" class="px-4 py-2 text-sm font-medium rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">Cancelar</button>
                <button wire:click="agregarNuevoEmpleado" type="button" class="px-4 py-2 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">Solicitar</button>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal: Nuevo Internacional --}}
    @if($nuevoInternacionalModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-md mx-4">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Solicitar Integrante Internacional</h3>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Integrante Internacional <span class="text-red-500">*</span></label>
                    <select wire:model="nuevoInternacionalId" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500">
                        <option value="">Seleccione...</option>
                        @foreach($internacionalesDisponibles as $id => $nombre) <option value="{{ $id }}">{{ $nombre }}</option> @endforeach
                    </select>
                    @error('nuevoInternacionalId') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Motivo de Incorporación <span class="text-red-500">*</span></label>
                    <textarea wire:model="nuevoInternacionalMotivo" rows="3" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:border-blue-500"></textarea>
                    @error('nuevoInternacionalMotivo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                <button wire:click="$set('nuevoInternacionalModal', false)" type="button" class="px-4 py-2 text-sm font-medium rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">Cancelar</button>
                <button wire:click="agregarNuevoInternacional" type="button" class="px-4 py-2 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">Solicitar</button>
            </div>
        </div>
    </div>
    @endif
</div>
