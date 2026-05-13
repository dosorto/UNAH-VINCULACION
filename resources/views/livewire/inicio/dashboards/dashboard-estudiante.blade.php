<div class="w-full mb-6 lg:mb-0">
    {{-- Bienvenida --}}
    <div class="p-6 bg-yellow-500 rounded-xl">
        <div class="container px-4 mx-auto">
            <h3 class="text-3xl font-bold text-white">Bienvenido a tu Dashboard</h3>
            <p class="font-medium text-yellow-100">Aquí podrás ver los proyectos en los que participas y descargar tus constancias.</p>
        </div>
    </div>

    {{-- Tabla de proyectos --}}
    <div class="mt-6">
        @if(!$estudiante)
            <p class="text-gray-500 dark:text-gray-400">No hay estudiante asociado a tu cuenta.</p>
        @else
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Nombre del Proyecto</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Tipo de Participación</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Fecha Inicio</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Fecha Fin</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($proyectos as $proyecto)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $proyecto->nombre_proyecto }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $proyecto->estudianteProyecto?->tipo_participacion_estudiante }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $proyecto->fecha_inicio?->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $proyecto->fecha_finalizacion?->format('d/m/Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No tienes proyectos registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $proyectos->links() }}</div>
        @endif
    </div>
</div>
