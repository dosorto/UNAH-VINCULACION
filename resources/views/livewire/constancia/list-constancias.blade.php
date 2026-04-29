<div>
    <div class="mb-4">
        <p class="text-zinc-950 dark:text-white font-bold mb-1">Constancias</p>
        <p class="text-zinc-500 dark:text-gray-400 font-medium text-sm">Listado de constancias generadas.</p>
    </div>

    <div class="mb-3">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar por hash..."
            class="w-full sm:w-80 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500" />
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Tipo</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Origen</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Destinatario</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Hash</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Estado</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Fecha</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($records as $record)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $record->tipo?->nombre }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ class_basename($record->origen_type) }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ class_basename($record->destinatario_type) }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300 font-mono text-xs">{{ $record->hash }}</td>
                        <td class="px-4 py-3">
                            @if($record->status)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">Activa</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">Inactiva</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $record->created_at->format('d/m/Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No se encontraron constancias.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $records->links() }}</div>
</div>
