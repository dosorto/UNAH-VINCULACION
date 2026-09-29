<div class="space-y-6" x-data="{ documentoActivo: 'ficha' }">
    {{-- Campos explícitos del detalle: fecha_registro, fecha_inicio, fecha_finalizacion,
         tipo_pasantia, modalidad_ejecucion, nombre_contacto_directo,
         jornada_laboral_docente, firma_estudiante, archivo_convenio_marco. --}}
    @php
        $registro = $this->registro;
        $estadoTexto = $registro->estado === 'en_revision' && $registro->etapaActual ? $registro->etapaActual->nombre : ucfirst(str_replace('_', ' ', $registro->estado ?: 'sin estado'));
        $puedeEditar = ($registro->perteneceAlUsuario(auth()->id()) && in_array($registro->estado, ['borrador', 'subsanacion'], true)) || ($registro->estado === 'en_revision' && $registro->usuarioPuedeRevisar(auth()->user()));
    @endphp

    <header class="grid gap-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900 lg:grid-cols-[minmax(12rem,1fr)_minmax(0,2fr)_auto] lg:items-center">
        <div class="min-w-0">
            <a href="{{ route($historialRouteName) }}" wire:navigate class="text-sm font-medium text-blue-600 hover:text-blue-700">Volver a mis proyectos</a>
            <h1 class="my-3 break-words text-center text-xl font-bold text-gray-900 dark:text-white">{{ $registro->codigo_registro ?: 'Registro de Pasantías #'.$registro->id }}</h1>
            <p class="text-sm italic text-gray-500 dark:text-gray-400">Estado: {{ $estadoTexto }}</p>
        </div>
        <section class="min-w-0 rounded-lg bg-gray-50 px-5 py-4 dark:bg-gray-800" aria-label="Progreso del flujo">
            <h2 class="text-xs font-bold uppercase italic tracking-wide text-slate-600 dark:text-gray-300">Progreso del flujo</h2>
            @if($registro->estado === 'borrador' && !$registro->fecha_envio)
                <p class="mt-5 text-sm text-slate-400">Sin enviar</p>
            @else
                <ol class="mt-4 flex items-start gap-3 overflow-x-auto" aria-label="Flujo de revisión">
                    @foreach($etapasVisuales as $etapa)
                        <li class="flex min-w-24 flex-1 items-start gap-2">
                            <div class="flex flex-1 flex-col items-center gap-2 text-center">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold {{ $etapa['estado'] === 'Aprobado' ? 'bg-green-100 text-green-700' : ($etapa['estado'] === 'Rechazado' ? 'bg-red-100 text-red-700' : 'bg-gray-200 text-gray-600') }}">{{ $etapa['estado'] === 'Aprobado' ? '✓' : $loop->iteration }}</span>
                                <span class="text-xs font-medium">{{ $etapa['nombre'] }}</span>
                                <span class="text-xs text-gray-500">{{ $etapa['estado'] }}</span>
                            </div>
                            @unless($loop->last)<span aria-hidden="true" class="pt-2 text-gray-400">→</span>@endunless
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('pasantias.pdf', $registro->id) }}" class="inline-flex items-center rounded-md bg-sky-600 px-4 py-3 text-sm font-medium text-white hover:bg-sky-700">Descargar PDF</a>
            @if($puedeEditar)
                <a href="{{ route('pasantias.edit', $registro->id) }}" wire:navigate class="inline-flex items-center rounded-md bg-blue-600 px-4 py-3 text-sm font-medium text-white hover:bg-blue-700">Continuar editando</a>
            @endif
        </div>
    </header>

    <nav class="flex gap-1 overflow-x-auto rounded-lg border border-gray-200 bg-white p-2 dark:border-gray-700 dark:bg-gray-900" aria-label="Documentos de la pasantía">
        <button type="button" x-on:click="documentoActivo = 'ficha'" :aria-pressed="documentoActivo === 'ficha'" :class="documentoActivo === 'ficha' ? 'bg-blue-600 text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100'" class="rounded-md px-5 py-3 text-sm font-semibold">Ficha</button>
        @foreach($anexos as $anexo)
            <button type="button" x-on:click="documentoActivo = @js($anexo['tipo'])" :aria-pressed="documentoActivo === @js($anexo['tipo'])" :class="documentoActivo === @js($anexo['tipo']) ? 'bg-blue-600 text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100'" class="whitespace-nowrap rounded-md px-5 py-3 text-sm font-semibold">Adjunto {{ $loop->iteration }} · {{ $anexo['titulo'] }}</button>
        @endforeach
    </nav>
    @if($camposFaltantesEnvio !== [])<div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 shadow-sm"><p class="font-semibold">Complete la información obligatoria antes de enviar a revisión.</p><ul class="mt-2 list-disc space-y-1 pl-5">@foreach($camposFaltantesEnvio as $campo)<li>{{ $campo }}</li>@endforeach</ul></div>@endif
    @error('flujo')<div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 shadow-sm">{{ $message }}</div>@enderror
    @error('pdf')<div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 shadow-sm">{{ $message }}</div>@enderror
    @if($registro->motivo_rechazo && in_array($registro->estado, ['rechazado', 'subsanacion'], true))<div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 shadow-sm"><p class="font-semibold">Observaciones de subsanación</p><p class="mt-2 whitespace-pre-line">{{ $registro->motivo_rechazo }}</p></div>@endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <section class="min-w-0 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <template x-if="documentoActivo === 'ficha'">
                <div>
                    <h2 class="border-b border-gray-200 px-5 py-3 text-sm font-semibold dark:border-gray-700">Ficha FORM-DVUS-013</h2>
                    <iframe wire:key="pdf-ficha-{{ $registro->id }}-{{ $registro->updated_at?->timestamp }}" src="{{ route('pasantias.pdf', [$registro->id, 'inline' => 1, 'v' => $registro->updated_at?->timestamp]) }}" title="Ficha FORM-DVUS-013 en PDF" class="w-full border-0" style="height:80vh;min-height:600px"></iframe>
                </div>
            </template>
            @foreach($anexos as $anexo)
                <template x-if="documentoActivo === @js($anexo['tipo'])">
                    <div>
                        <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-5 py-3 dark:border-gray-700">
                            <h2 class="text-sm font-semibold">{{ $anexo['titulo'] }}</h2>
                            @if($anexo['exists'])<a href="{{ route('pasantias.anexo', [$registro->id, $anexo['tipo'], 'download' => 1]) }}" class="text-sm text-blue-600">Descargar original</a>@endif
                        </div>
                        @if($anexo['exists'])
                            <iframe src="{{ $anexo['url'] }}" title="{{ $anexo['titulo'] }}" class="w-full border-0" style="height:80vh;min-height:600px"></iframe>
                        @else
                            <p class="p-6 text-sm text-gray-500">Este adjunto no tiene un archivo disponible.</p>
                        @endif
                    </div>
                </template>
            @endforeach
        </section>
        <aside class="space-y-6">
            <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900"><h2 class="mb-4 text-lg font-bold text-gray-900 dark:text-white">Historial de movimientos</h2>@if($movimientos->isNotEmpty())<ol class="relative border-s border-yellow-600">@foreach($movimientos as $movimiento)<li class="mb-6 ms-4"><div class="absolute -start-1.5 mt-1.5 h-3 w-3 rounded-full border border-white bg-yellow-600"></div><time class="text-xs text-yellow-700">{{ optional($movimiento->created_at)->format('d/m/Y H:i') }}</time><h3 class="mt-1 text-sm font-semibold">Estado: {{ $movimiento->tipoestado?->nombre ?? 'Cambio de estado' }}</h3><p class="text-xs text-gray-500">@if($movimiento->empleado) · Usuario: {{ $movimiento->empleado->nombre_completo }} @endif</p>@if($movimiento->comentario)<p class="mt-1 whitespace-pre-line break-words text-sm text-gray-600 dark:text-gray-300">Observación: {{ $movimiento->comentario }}</p>@endif</li>@endforeach</ol>@else<p class="text-sm text-gray-500">No hay movimientos registrados.</p>@endif</section>
        </aside>
    </div>

    @if($subsanarModal)<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"><div class="w-full max-w-lg rounded-xl bg-white shadow-xl"><div class="border-b p-4"><h3 class="text-lg font-semibold">Enviar a subsanación</h3></div><div class="space-y-4 p-4"><label class="block text-sm font-medium">Correcciones requeridas <span class="text-red-500">*</span><textarea wire:model="subsanarComentario" rows="5" class="mt-1 w-full rounded-md border px-3 py-2"></textarea></label>@error('subsanarComentario')<p class="text-sm text-red-600">{{ $message }}</p>@enderror<div class="flex justify-end gap-3"><button type="button" wire:click="cerrarModalSubsanacion" class="rounded-lg border px-4 py-2">Cancelar</button><button type="button" wire:click="enviarASubsanar" wire:loading.attr="disabled" class="rounded-lg bg-amber-600 px-4 py-2 text-white">Subsanar</button></div></div></div></div>@endif
</div>
