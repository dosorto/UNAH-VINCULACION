
<div class="flex flex-col md:flex-row lg:flex-row gap-4">
    @php
    use App\Models\Estado\EstadoProyecto;
    use App\Models\Proyecto\Proyecto;
    use Carbon\Carbon;

    // Obtener la fecha de creación del proyecto (el primer log 'created' o la fecha de creacion del proyecto)
    $creationLog = $logs->where('description', 'created')->where('subject_type', Proyecto::class)->first();
    $projectStartDate = $creationLog ? Carbon::parse($creationLog->created_at) : Carbon::parse($proyecto->created_at);
    
    // Calcular días totales del proyecto (desde creación hasta hoy o hasta aprobacion)
    $today = Carbon::now();
    $projectEndDate = $proyecto->fecha_aprobacion ? Carbon::parse($proyecto->fecha_aprobacion) : $today;
    $totalDaysProject = $projectStartDate->diffInDays($projectEndDate);
@endphp
    <div class="w-full md:w-3/5 lg:w-2/3">
        <h1 class="text-2xl font-bold dark:text-white text-gray-900 mb-4">
            Ficha de proyecto: {{ $proyecto->estado->tipoestado->nombre }}
        </h1>
        <x-fichas.ficha-proyecto-historial :proyecto="$proyecto" />
    </div>
    <div class="w-full md:w-2/5 lg:w-1/3">
        <h1 class="text-2xl font-bold dark:text-white text-gray-900 mb-4">
            Historial de cambios
        </h1>
        @if(count($logs) > 0)
            <ol class="relative border-s border-yellow-600">
                @foreach($logs as $index => $log)
            @php
                $logDate = Carbon::parse($log->created_at);
                $daysFromStart = intval($projectStartDate->diffInDays($logDate)); // Convertir a entero
                
                // Determinar el estado del proyecto en el momento del log
                $estadoEnLog = null;
                if ($log->subject_type == EstadoProyecto::class && isset($log->properties['attributes']['tipo_estado_id'])) {
                    $estadoEnLog = \App\Models\Estado\TipoEstado::find($log->properties['attributes']['tipo_estado_id']);
                }
                
                // Si este log es un cambio a "En curso" o "Aprobado", mostrar 100%
                if ($proyecto->fecha_aprobacion && $estadoEnLog && ($estadoEnLog->nombre == 'En curso' || $estadoEnLog->nombre == 'Aprobado')) {
                    $percentComplete = 100;
                } 
                // Para los demás casos
                else {
                    // Evita división por cero y asegura valores positivos
                    $percentComplete = $totalDaysProject > 0 ? min(100, max(0, round(($daysFromStart / $totalDaysProject) * 100))) : 0;
                }
            @endphp
                    <li class="{{ $index < count($logs) - 1 ? 'mb-10' : '' }} ms-4">
                        <div
                            class="absolute w-3 h-3 bg-yellow-600 rounded-full mt-1.5 -start-1.5 border border-white">
                        </div>
                        <div class="flex items-center">
                            <time class="text-sm font-normal leading-none text-yellow-600">
                                {{ $log->created_at->day }} de {{ $log->created_at->translatedFormat('F') }} del {{ $log->created_at->year }}
                            </time>
                            <span class="ml-2 px-2 py-0.5 text-xs font-medium dark:bg-gray-600 bg-gray-100 text-gray-600 dark:text-gray-100 rounded-full">
                                Día {{ $daysFromStart }} ({{ $percentComplete }}% Aprobado)
                            </span>
                        </div>
                        
                            @if($log->subject_type == EstadoProyecto::class)
                            <h3 class=" flex text-lg font-semibold dark:text-white text-gray-900">
                                Cambio de estado
                                <svg class="w-4 h-4 text-green-800 ml-0.5 mt-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                    <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm13.707-1.293a1 1 0 0 0-1.414-1.414L11 12.586l-1.793-1.793a1 1 0 0 0-1.414 1.414l2.5 2.5a1 1 0 0 0 1.414 0l4-4Z" clip-rule="evenodd"/>
                                  </svg>
                            </h3>
                            @else
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ ucfirst($log->description) }} {{ $log->subject_type == Proyecto::class ? 'proyecto' : '' }}
                            </h3>
                            @endif
                        </h3>
                        <p class="mb-4 text-base font-normal text-gray-500 dark:text-gray-400">
                            {{ $log->causer ? $log->causer->nombre_completo : '' }} 
                            @if($log->subject_type == EstadoProyecto::class)
                                Ha cambiado el estado del proyecto a 
                                @if(isset($log->properties['attributes']['tipo_estado_id']))
                                    "{{ \App\Models\Estado\TipoEstado::find($log->properties['attributes']['tipo_estado_id'])->nombre ?? 'Desconocido' }}"
                                @else
                                    un nuevo estado
                                @endif
                            @else
                                {{ $log->description == 'created' ? 'creado' :
                    ($log->description == 'updated' ? 'actualizado' :
                        ($log->description == 'deleted' ? 'eliminado' : $log->description)) }} 
                            @endif
                        </p>

                        @if($log->description == 'updated' && isset($log->properties['old']) && count($log->properties['old']) > 0)
                            <div class="mb-4">
                                <h4 class="text-sm font-semibold text-gray-700 mb-2">Cambios realizados:</h4>
                                <ul class="list-disc list-inside text-sm text-gray-600 ml-4">
                                    @foreach($log->properties['old'] as $key => $value)
                                        @if($key !== 'updated_at' && $key !== 'id')
                                            <li>
                                                <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span> 
                                                {{ $value ?? 'N/A' }} → {{ $log->properties['attributes'][$key] ?? 'N/A' }}
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if($log->description == 'created')
                            <a href="{{ route('proyectos.show', $log->subject_id) }}"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:outline-none focus:ring-gray-100 focus:text-blue-700">
                                Ver detalles
                                <svg class="w-3 h-3 ms-2 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 14 10">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M1 5h12m0 0L9 1m4 4L9 9" />
                                </svg>
                            </a>
                        @endif
                    </li>
                @endforeach
            </ol>
        @else
            <div class="flex items-center justify-center h-full">
                <p class="text-gray-500">No hay cambios registrados para este proyecto.</p>
            </div>
        @endif
    </div>
</div>