<div class="mx-auto max-w-6xl space-y-6 p-6">
    <div class="rounded-xl border bg-white p-6 shadow-sm">
        <p class="text-xs font-semibold uppercase text-blue-700">FORM-DVUS-013</p>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><h1 class="text-2xl font-bold">{{ $registro->codigo_registro }}</h1><p class="text-sm text-gray-500">Estado: {{ ucfirst($registro->estado) }}</p></div>
            @if(in_array($registro->estado, ['borrador', 'subsanacion'], true) && $registro->created_by === auth()->id())
                <a class="rounded-lg bg-blue-700 px-4 py-2 text-sm text-white" href="{{ route('pasantias.edit', $registro->id) }}">Editar</a>
            @endif
            @if($registro->puedeEliminarBorrador(auth()->id()))
                <button type="button" wire:loading.attr="disabled" wire:target="eliminarBorrador"
                        x-on:click.prevent="confirmDialog('¿Desea eliminar este borrador? Esta acción no elimina físicamente el registro.').then((ok) => ok && $wire.eliminarBorrador())"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm text-white">
                    <span wire:loading.remove wire:target="eliminarBorrador">Eliminar</span>
                    <span wire:loading wire:target="eliminarBorrador">Eliminando...</span>
                </button>
            @endif
            @if($registro->estado === 'rechazado' && $registro->created_by === auth()->id())
                <button wire:click="iniciarSubsanacion" class="rounded-lg bg-amber-600 px-4 py-2 text-sm text-white">Iniciar subsanación</button>
            @elseif($registro->estado === 'subsanacion' && $registro->created_by === auth()->id())
                <button wire:click="volverABorrador" class="rounded-lg border border-blue-700 px-4 py-2 text-sm text-blue-700">Volver a borrador</button>
            @endif
        </div>
        @error('flujo')<p class="mt-3 rounded bg-red-50 p-3 text-sm text-red-700">{{ $message }}</p>@enderror
        @error('pdf')<p class="mt-3 rounded bg-red-50 p-3 text-sm text-red-700">{{ $message }}</p>@enderror
        @if($registro->created_by === auth()->id() || auth()->user()?->can('proyectos.historial'))
            <div class="mt-3 flex flex-wrap gap-2"><button wire:click="generarDocumento('formulario')" class="rounded-lg border px-3 py-2 text-sm">Generar FORM-DVUS-013</button><button wire:click="generarDocumento('solicitud_practica')" class="rounded-lg border px-3 py-2 text-sm">Generar Solicitud</button><button wire:click="generarDocumento('autorizacion_pps')" class="rounded-lg border px-3 py-2 text-sm">Generar Autorización</button><a href="{{ route('pasantias.pdf', [$registro->id, 'tipo'=>'solicitud_practica']) }}" class="rounded-lg bg-blue-700 px-3 py-2 text-sm text-white">Descargar Solicitud</a><a href="{{ route('pasantias.pdf', [$registro->id, 'tipo'=>'autorizacion_pps']) }}" class="rounded-lg bg-blue-700 px-3 py-2 text-sm text-white">Descargar Autorización</a></div>
        @endif
        @if(in_array($registro->estado, ['borrador', 'subsanacion'], true) && $registro->created_by === auth()->id())
            <button wire:click="enviarRevision" class="mt-3 rounded-lg bg-blue-700 px-4 py-2 text-sm text-white">Enviar a revisión</button>
        @endif
        @if($registro->estado === 'enviado' && auth()->user()?->can('proyectos.historial'))
            <div class="mt-4 flex flex-wrap gap-2"><button wire:click="aprobar" class="rounded-lg bg-green-700 px-4 py-2 text-sm text-white">Aprobar</button><button wire:click="rechazar" class="rounded-lg bg-red-700 px-4 py-2 text-sm text-white">Solicitar subsanación</button><input wire:model="comentarioRechazo" placeholder="Comentario obligatorio para rechazo" class="rounded-lg border px-3 py-2 text-sm"></div>
        @endif
    </div>
    @foreach($secciones as $nombre => $campos)
        <section class="rounded-xl border bg-white p-5 shadow-sm">
            <h2 class="mb-4 border-b pb-2 text-lg font-semibold text-blue-900">{{ $nombre }}</h2>
            <dl class="grid grid-cols-1 gap-x-6 gap-y-3 md:grid-cols-2">
                @foreach($campos as $campo)
                    @php($valor = $registro->{$campo})
                    <div class="border-b pb-2"><dt class="text-xs font-semibold uppercase text-gray-500">{{ str_replace('_', ' ', $campo) }}</dt><dd class="mt-1 whitespace-pre-wrap text-sm {{ is_null($valor) ? 'italic text-gray-400' : 'text-gray-900' }}">{{ is_null($valor) ? 'Sin información (NULL)' : (is_array($valor) ? json_encode($valor, JSON_UNESCAPED_UNICODE) : $valor) }}</dd></div>
                @endforeach
            </dl>
        </section>
    @endforeach
    @if($registro->motivo_rechazo)
        <section class="rounded-xl border border-amber-200 bg-amber-50 p-5"><h2 class="font-semibold">Observaciones de revisión</h2><p class="mt-2 whitespace-pre-wrap">{{ $registro->motivo_rechazo }}</p></section>
    @endif
    @if($registro->historialEstados->isNotEmpty())
        <section class="rounded-xl border bg-white p-5"><h2 class="mb-3 text-lg font-semibold">Historial</h2>@foreach($registro->historialEstados->sortByDesc('created_at') as $movimiento)<p class="border-b py-2 text-sm"><b>{{ $movimiento->tipoestado?->nombre }}</b> — {{ $movimiento->comentario ?: 'Sin observaciones' }} <span class="text-gray-400">{{ optional($movimiento->created_at)->format('d/m/Y H:i') }}</span></p>@endforeach</section>
    @endif
</div>
