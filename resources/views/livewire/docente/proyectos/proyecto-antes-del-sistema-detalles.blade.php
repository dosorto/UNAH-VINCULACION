<div class="p-6">
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
            {{ $record->nombre_proyecto }}
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Código: {{ $record->codigo_proyecto }}
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Información básica -->
        <div class="space-y-4">
            <div>
                <h4 class="font-medium text-gray-900 dark:text-white">Información General</h4>
                <dl class="mt-2 space-y-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Resumen:</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">{{ $record->resumen ?: 'No definido' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Modalidad:</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">{{ $record->modalidad->nombre ?? 'No definida' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Estado:</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">{{ $record->tipo_estado->nombre ?? 'No definido' }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Fechas -->
        <div class="space-y-4">
            <div>
                <h4 class="font-medium text-gray-900 dark:text-white">Fechas del Proyecto</h4>
                <dl class="mt-2 space-y-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Fecha de Inicio:</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">{{ $record->fecha_inicio ? $record->fecha_inicio->format('d/m/Y') : 'No definida' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Fecha de Finalización:</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">{{ $record->fecha_finalizacion ? $record->fecha_finalizacion->format('d/m/Y') : 'No definida' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Fecha de Registro:</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">{{ $record->created_at->format('d/m/Y H:i') }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

    <!-- Participantes -->
    <div class="mt-6">
        <h4 class="font-medium text-gray-900 dark:text-white mb-3">Participantes del Proyecto</h4>
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            @if($record->docentes_proyecto->count() > 0)
                <div class="space-y-2">
                    @foreach($record->docentes_proyecto as $docenteProyecto)
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-900 dark:text-white">
                                {{ $docenteProyecto->empleado->nombre_completo }}
                            </span>
                            <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                                {{ $docenteProyecto->rol }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">No hay participantes registrados</p>
            @endif
        </div>
    </div>

    @if($record->tipo_estado?->nombre === 'PendienteInformacion')
    <!-- Información adicional -->
    <div class="mt-6">
        <h4 class="font-medium text-gray-900 dark:text-white mb-3">Información del Proyecto</h4>
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                        Proyecto pendiente de información
                    </h3>
                    <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                        <p>Este proyecto fue creado automáticamente al verificar un código de investigación. Puede:</p>
                        <ul class="list-disc list-inside mt-2 space-y-1">
                            <li>Completar y actualizar la información del proyecto</li>
                            <li>Agregar o modificar participantes</li>
                            <li>Definir cronograma y presupuesto detallado</li>
                            <li>El proyecto permanecerá en esta sección para su gestión</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
