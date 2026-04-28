<div>
    <div class="mb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <div>
            <p class="text-zinc-950 dark:text-white font-bold mb-1">Tickets</p>
            @if(auth()->user()->can('admin-tickets-administrar-tickets'))
                <p class="text-zinc-500 dark:text-gray-400 text-sm">Gestiona y responde los tickets de los usuarios.</p>
            @else
                <p class="text-zinc-500 dark:text-gray-400 text-sm">Envía tickets para soporte técnico, consultas o sugerencias.</p>
            @endif
        </div>
        <div class="flex gap-2">
            <a href="{{ route('historialTicket') }}"
                class="inline-flex items-center px-4 py-2 bg-green-700 hover:bg-green-800 text-white text-sm font-medium rounded-lg">
                Historial
            </a>
            @can('tickets-ver-modulo')
                <button wire:click="openCreate"
                    class="inline-flex items-center px-4 py-2 bg-blue-700 hover:bg-blue-800 text-white text-sm font-medium rounded-lg">
                    + Nuevo Ticket
                </button>
            @endcan
        </div>
    </div>

    {{-- Filtros --}}
    <div class="mb-3 flex flex-col sm:flex-row gap-3">
        <select wire:model.live="filtroTipo"
            class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
            <option value="">Todos los tipos</option>
            <option value="Soporte Tecnico">Soporte Técnico</option>
            <option value="Sugerencia">Sugerencia</option>
            <option value="Consulta General">Consulta General</option>
            <option value="Otro">Otro</option>
        </select>
        <select wire:model.live="filtroEstado"
            class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
            <option value="">Todos los estados</option>
            <option value="abierto">Abierto</option>
            <option value="en proceso">En Proceso</option>
        </select>
    </div>

    {{-- Tabla --}}
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
                            @php $color = match($record->estado) { 'abierto' => 'blue', 'en proceso' => 'yellow', default => 'gray' } @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-{{ $color }}-100 text-{{ $color }}-800 dark:bg-{{ $color }}-900/30 dark:text-{{ $color }}-300">
                                {{ $record->estado }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $record->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-right">
                            @if($record->mensajes->isNotEmpty())
                                <button wire:click="openView({{ $record->id }})"
                                    class="inline-flex items-center px-2.5 py-1 text-xs font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400 rounded-md">
                                    @if($this->debeMostrarAlerta($record)) ! @endif Ver
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">No hay tickets activos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $records->links() }}</div>

    {{-- Modal Crear Ticket --}}
    @if($createModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:click.self="$set('createModal', false)">
            <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full max-w-lg p-6 mx-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Nuevo Ticket</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo *</label>
                        <select wire:model="create_tipo_ticket"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                            <option value="">Seleccione un tipo</option>
                            <option value="Soporte Tecnico">Soporte Técnico</option>
                            <option value="Sugerencia">Sugerencia</option>
                            <option value="Consulta General">Consulta General</option>
                            <option value="Otro">Otro</option>
                        </select>
                        @error('create_tipo_ticket') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Asunto *</label>
                        <textarea wire:model="create_asunto" rows="2"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm"></textarea>
                        @error('create_asunto') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mensaje *</label>
                        <textarea wire:model="create_mensaje" rows="4"
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm"></textarea>
                        @error('create_mensaje') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button wire:click="$set('createModal', false)" class="px-4 py-2 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg">Cancelar</button>
                    <button wire:click="crearTicket" class="px-4 py-2 text-sm text-white bg-blue-700 hover:bg-blue-800 rounded-lg">Enviar</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal Ver Mensajes --}}
    @if($viewModal && $ticket)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:click.self="$set('viewModal', false)">
            <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl w-full max-w-2xl p-6 mx-4 max-h-[90vh] flex flex-col">
                <div class="flex justify-between items-start mb-4">
                    <div class="text-sm text-gray-700 dark:text-gray-200">
                        <div class="font-semibold">{{ $ticket->tipo_ticket }}</div>
                        <div class="text-gray-500">{{ $ticket->asunto }}</div>
                    </div>
                    @if(auth()->user()->can('admin-tickets-administrar-tickets') && $ticket->estado !== 'cerrado')
                        <button wire:click="finalizarTicket"
                            class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg">
                            Finalizar Ticket
                        </button>
                    @endif
                </div>

                <div class="flex-1 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-xl p-4 space-y-4 max-h-72" wire:poll.5s.keep-alive>
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

                @if($ticket->estado !== 'cerrado')
                    <div class="mt-4">
                        <textarea wire:model="nuevoMensaje" rows="2" placeholder="Escribe tu mensaje..."
                            class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm"></textarea>
                        <div class="flex justify-end mt-2">
                            <button wire:click="enviarMensaje" class="px-4 py-2 text-sm text-white bg-blue-700 hover:bg-blue-800 rounded-lg">Enviar</button>
                        </div>
                    </div>
                @endif

                <div class="flex justify-end mt-4">
                    <button wire:click="$set('viewModal', false)" class="px-4 py-2 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg">Cerrar</button>
                </div>
            </div>
        </div>
    @endif
</div>
