<div>
    <div class="mb-4">
        <p class="text-zinc-950 dark:text-white font-bold mb-1">Registro de Actividades</p>
        <p class="text-zinc-500 dark:text-gray-400 font-medium text-sm">Aquí se muestran los registros de actividades de la aplicación.</p>
    </div>

    <div class="mb-3">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar por descripción o usuario..."
            class="w-full sm:w-80 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500" />
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Descripción</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Tipo</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Usuario</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Fecha</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($records as $record)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        <td class="px-4 py-3 text-gray-900 dark:text-white">{{ Str::limit($record->description, 80) }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ class_basename($record->subject_type) }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $record->causer?->name ?? 'Dato precargado' }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $record->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No se encontraron registros.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $records->links() }}</div>
</div>
