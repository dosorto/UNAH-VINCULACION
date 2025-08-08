<div>
    <div class="mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Completar Información del Proyecto
                </h2>
                <p class="text-gray-600 dark:text-gray-400 mt-2">
                    {{ $record->nombre_proyecto }} ({{ $record->codigo_proyecto }})
                </p>
            </div>
            <div class="text-right">
                @php
                    $estado = $record->tipo_estado?->nombre ?? 'Desconocido';
                    $colorClass = match($estado) {
                        'PendienteInformacion' => 'bg-yellow-100 text-yellow-800',
                        'Finalizado' => 'bg-green-100 text-green-800',
                        default => 'bg-gray-100 text-gray-800'
                    };
                @endphp
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $colorClass }}">
                    {{ $estado === 'PendienteInformacion' ? 'Pendiente de Información' : $estado }}
                </span>
            </div>
        </div>
    </div>

    <!-- Información importante -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                    Información sobre este proyecto
                </h3>
                <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                    <p>Este proyecto fue creado automáticamente al verificar su código de investigación. Complete la información faltante y guárdelo.</p>
                    <p class="mt-1"><strong>Nota:</strong> Al completar la información, el proyecto cambiará a estado "Finalizado" pero permanecerá en la sección "Proyectos Antes del Sistema" para su gestión continua.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulario con Wizard -->
    <div class="bg-white dark:bg-gray-900 shadow rounded-lg p-6">
        <form wire:submit="save">
            {{ $this->form }}
        </form>
        
        <!-- Botón de volver separado -->
        <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
            <a href="{{ route('proyectosAntesDelSistema') }}" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Volver a Proyectos Antes del Sistema
            </a>
        </div>
    </div>
    </form>
</div>
