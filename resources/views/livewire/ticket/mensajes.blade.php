<div class="space-y-6">

    {{-- Encabezado con info y botón al lado --}}
    @php
        $usuarioActual = auth()->user();
        $esAdmin = $usuarioActual->hasRole('admin');

        $nombreOtroUsuario = $esAdmin
            ? ($ticket->user->name ?? 'Usuario')
            : optional($ticket->mensajes->where('user_id', '!=', $usuarioActual->id)->last())->user->name ?? 'Administrador';
    @endphp

    <div class="flex justify-between items-start flex-wrap gap-2">
        <div class="text-sm text-gray-700 dark:text-gray-200 space-y-1">
            <div>Ticket: {{ $ticket->tipo_ticket }}</div>
            <div>Asunto: {{ $ticket->asunto }}</div>
        </div>

        {{-- Botón Finalizar (al lado del encabezado si es admin y no está cerrado) --}}
        @if($esAdmin && $ticket->estado !== 'cerrado')
            <x-filament::button
                color="danger"
                wire:click="finalizarTicket"
                class="self-start"
            >
                Finalizar Ticket
            </x-filament::button>
        @endif
    </div>

    {{-- Lista de mensajes con actualización automática --}}
    <div
        class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow p-4 max-h-96 overflow-y-auto space-y-4"
        wire:poll.5s.keep-alive
        wire:key="mensajes-ticket-{{ $ticket->id }}"
    >
        @forelse ($ticket->mensajes as $mensaje)
            @php
                $esPropio = $mensaje->user_id === auth()->id();
            @endphp

            <div class="flex {{ $esPropio ? 'justify-end' : 'justify-start' }}">
                <div class="max-w-[100%]">
                    <div class="text-sm text-gray-700 dark:text-gray-300 flex justify-between mb-1">
                        <strong>{{ $mensaje->user->name ?? 'Usuario' }}</strong>
                        <span class="text-xs text-gray-500">{{ $mensaje->created_at->format('d M Y H:i') }}</span>
                    </div>

                    <div class="{{ $esPropio 
                        ? 'bg-blue-100 dark:bg-blue-800 text-black dark:text-blue-100' 
                        : 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-100' 
                    }} text-sm p-3 rounded-lg shadow-sm">
                        {{ $mensaje->mensaje }}
                    </div>
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-500 dark:text-gray-400">No hay mensajes aún.</p>
        @endforelse
    </div>

    {{-- Formulario para nuevo mensaje (solo si no está cerrado) --}}
    @if($ticket->estado !== 'cerrado')
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow p-4">
            <label for="nuevoMensaje" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                Nuevo mensaje
            </label>

            <textarea
                wire:model.defer="nuevoMensaje"
                id="nuevoMensaje"
                rows="2"
                class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm"
                placeholder="Escribe tu mensaje aquí..."
            ></textarea>

            <div class="mt-4 flex justify-between">
                <x-filament::button
                    wire:click="enviarMensaje"
                    color="primary"
                >
                    Enviar
                </x-filament::button>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
    window.addEventListener('cerrar-modal-ticket', () => {
        const closeBtn = document.querySelector('[data-dismiss="modal"]');
        if (closeBtn) {
            closeBtn.click();
        }
    });
</script>
@endpush
