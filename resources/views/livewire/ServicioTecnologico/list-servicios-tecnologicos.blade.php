<div>
    <div class="mb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <div>
            <p class="text-zinc-950 dark:text-white font-bold mb-1">Listado de servicios tecnológicos</p>
            <p class="text-zinc-500 dark:text-gray-400 font-medium text-sm">A continuación se muestra el listado de servicios tecnológicos registrados.</p>
        </div>
        <a href="{{ route('createServicioTecnologico') }}" wire:navigate
            class="inline-flex items-center px-4 py-2 bg-blue-700 hover:bg-blue-800 text-white text-sm font-medium rounded-lg">
            + Nuevo
        </a>
    </div>

    <div class="mb-3">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre de la acción..."
            class="w-full sm:w-96 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500" />
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Acción</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Modalidad</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Fechas</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Centros</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-300">Empleados</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-300">Actividades</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($records as $record)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-900 dark:text-white">{{ $record->nombre_accion }}</p>
                            @if ($record->ubicacion)
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $record->ubicacion }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                            {{ $record->modalidad?->nombre ?? 'Sin modalidad' }}
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                            <span>{{ optional($record->fecha_inicio)->format('d/m/Y') }}</span>
                            <span class="text-gray-400">-</span>
                            <span>{{ optional($record->fecha_finalizacion)->format('d/m/Y') }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                            {{ $record->centrosFacultades->pluck('nombre')->join(', ') ?: 'Sin centros' }}
                        </td>
                        <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">
                            {{ $record->empleados->count() }}
                        </td>
                        <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">
                            {{ $record->actividades_count }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                            No se encontraron servicios tecnológicos.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $records->links() }}
    </div>
</div>
