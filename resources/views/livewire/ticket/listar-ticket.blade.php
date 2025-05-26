<div>
    <div class="mb-4 mt-4 flex justify-between items-center">
        <div>
            <p class="text-zinc-950 dark:text-white font-bold mb-1">
                Ticket
            </p>

            @php
                $user = auth()->user();
            @endphp

            @if ($user->can('admin-tickets-administrar-tickets'))
                <p class="text-zinc-500 dark:text-gray-400 font-medium text-sm mt-0">
                    Aquí puedes gestionar y responder los tickets enviados por los usuarios.
                </p>
            @elseif ($user->hasAnyRole(['docente', 'Director/Enlace', 'estudiante']))
                <p class="text-zinc-500 dark:text-gray-400 font-medium text-sm mt-0">
                    Puedes enviar tickets para soporte técnico, consultas o sugerencias.
                </p>
            @endif
        </div>
    </div>

    {{ $this->table }}
</div>
