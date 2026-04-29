<div>
    <div class="mb-4">
        <p class="text-zinc-950 dark:text-white font-bold mb-1">Proyectos Aprobados</p>
        <p class="text-zinc-500 dark:text-gray-400 font-medium text-sm">Firmas de proyectos aprobadas por usted.</p>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Nombre del Proyecto</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Cargo de Firma</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Estado Firma</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Tipo Firma</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($records as $record)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $record->proyecto?->nombre_proyecto }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                {{ $record->cargo_firma?->tipoCargoFirma?->nombre }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $record->estado_revision }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $record->cargo_firma?->descripcion }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No hay proyectos aprobados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $records->links() }}</div>
</div>
