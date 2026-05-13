<div>
    <div class="mb-4">
        <p class="text-zinc-950 dark:text-white font-bold mb-1">Historial de Tickets</p>
        <p class="text-zinc-500 dark:text-gray-400 text-sm">Tickets finalizados.</p>
    </div>

    <div class="mb-3">
        <select wire:model.live="filtroTipo"
            class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
            <option value="">Todos los tipos</option>
            <option value="Soporte Tecnico">Soporte Técnico</option>
            <option value="Sugerencia">Sugerencia</option>
            <option value="Consulta General">Consulta General</option>
            <option value="Otro">Otro</option>
        </select>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Tipo</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Asunto</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Estado</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Fecha</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($records as $record)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $record->tipo_ticket }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ Str::limit($record->asunto, 50) }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                {{ $record->estado }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $record->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-right">
                            @if($record->mensajes->isNotEmpty())
                                <button wire:click="openView({{ $record->id }})"
                                    class="inline-flex items-center px-2.5 py-1 text-xs font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400 rounded-md">
                                    Ver
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">No hay tickets en el historial.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $records->links() }}</div>

    {{-- Modal Ver Mensajes --}}
    @if($viewModal && $ticket)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:click.self="$set('viewModal', false)">
            <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full max-w-2xl p-6 mx-4 max-h-[90vh] flex flex-col">
                <div class="mb-4 text-sm text-gray-700 dark:text-gray-200">
                    <div class="font-semibold">{{ $ticket->tipo_ticket }}</div>
                    <div class="text-gray-500">{{ $ticket->asunto }}</div>
                </div>
                <div class="flex-1 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-xl p-4 space-y-4 max-h-72">
                    @foreach($ticket->mensajes as $mensaje)
                        @php $esPropio = $mensaje->user_id === auth()->id(); @endphp
                        <div class="flex {{ $esPropio ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[80%]">
                                <div class="text-xs text-gray-500 mb-1 {{ $esPropio ? 'text-right' : '' }}">
                                    {{ $mensaje->user->name ?? 'Usuario' }} · {{ $mensaje->created_at->format('d M Y H:i') }}
                                </div>
                                <div class="text-sm p-3 rounded-lg {{ $esPropio ? 'bg-blue-100 dark:bg-blue-800 text-black dark:text-blue-100' : 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-100' }}">
                                    {{ $mensaje->mensaje }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="flex justify-end mt-4">
                    <button wire:click="$set('viewModal', false)" class="px-4 py-2 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg">Cerrar</button>
                </div>
            </div>
        </div>
    @endif
</div>
