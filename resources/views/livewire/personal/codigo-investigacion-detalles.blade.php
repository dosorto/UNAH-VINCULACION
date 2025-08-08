<div class="p-6 space-y-6">
    <!-- Información del Docente -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                Información del Empleado
            </h3>
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Nombre Completo
                    </label>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white">
                        {{ $record->empleado->nombre_completo }}
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Número de Empleado
                    </label>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white">
                        {{ $record->empleado->numero_empleado }}
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Centro/Facultad
                    </label>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white">
                        {{ optional($record->empleado->centro_facultad)->nombre ?? 'No asignado' }}
                    </p>
                </div>
            </div>
        </div>

        <div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                Estado de Verificación
            </h3>
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Estado Actual
                    </label>
                    <p class="mt-1">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                            {{ $record->estado_verificacion === 'pendiente' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ $record->estado_verificacion === 'verificado' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $record->estado_verificacion === 'rechazado' ? 'bg-red-100 text-red-800' : '' }}">
                            {{ $record->getEstadoLabel() }}
                        </span>
                    </p>
                </div>
                @if($record->verificadoPor)
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Verificado por
                    </label>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white">
                        {{ $record->verificadoPor->nombre_completo }}
                    </p>
                </div>
                @endif
                @if($record->fecha_verificacion)
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Fecha de Verificación
                    </label>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white">
                        {{ $record->fecha_verificacion->format('d/m/Y H:i') }}
                    </p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Información del Proyecto -->
    <div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
            Información del Proyecto de Investigación
        </h3>
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Código del Proyecto
                    </label>
                    <p class="mt-1 text-sm font-mono text-gray-900 dark:text-white bg-white dark:bg-gray-800 px-3 py-2 rounded border">
                        {{ $record->codigo_proyecto }}
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Año
                    </label>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white">
                        {{ $record->año }}
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Fecha de Registro
                    </label>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white">
                        {{ $record->created_at->format('d/m/Y H:i') }}
                    </p>
                </div>
            </div>
            
            @if($record->nombre_proyecto)
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Nombre del Proyecto
                </label>
                <p class="mt-1 text-sm text-gray-900 dark:text-white">
                    {{ $record->nombre_proyecto }}
                </p>
            </div>
            @endif

            @if($record->rol_docente)
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Participación del Empleado
                </label>
                <p class="mt-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                        {{ $record->rol_docente === 'coordinador' ? 'bg-green-100 text-green-800' : '' }}
                        {{ $record->rol_docente === 'integrante' ? 'bg-orange-100 text-orange-800' : '' }}">
                        {{ match($record->rol_docente) {
                            'coordinador' => 'Coordinador',
                            'integrante' => 'Integrante',
                            default => $record->rol_docente
                        } }}
                    </span>
                </p>
            </div>
            @endif

            @if($record->descripcion)
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Descripción
                </label>
                <p class="mt-1 text-sm text-gray-900 dark:text-white">
                    {{ $record->descripcion }}
                </p>
            </div>
            @endif
        </div>
    </div>

    <!-- Observaciones del Administrador -->
    @if($record->observaciones_admin)
    <div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
            Observaciones del Administrador
        </h3>
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <p class="text-sm text-blue-800 dark:text-blue-200">
                {{ $record->observaciones_admin }}
            </p>
        </div>
    </div>
    @endif
</div>
