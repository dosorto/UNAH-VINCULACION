@include('components.fichas.partials.institutional-pdf-chrome-styles')

<style>
    .pasantia-fdv { background: #fff; color: #111; font-family: Arial, Helvetica, "DejaVu Sans", sans-serif; font-size: 9px; line-height: 1.2; }
    .pasantia-fdv * { box-sizing: border-box; }
    .pasantia-fdv-shell { background: #fff; display: flex; justify-content: center; margin-top: 20px; overflow-x: auto; padding: 0; width: 100%; }
    .pasantia-fdv .sheet { background: #fff; border: 1px solid #ccc; margin: 0 auto; max-width: 100%; padding: 20px; position: relative; width: 100%; }
    .pasantia-fdv .form-header { border-bottom: 1px solid #d1d5db; margin-bottom: 12px; padding-bottom: 12px; }
    .pasantia-fdv .institutional-pdf-header-table { border-collapse: collapse; table-layout: fixed; width: 100%; }
    .pasantia-fdv .institutional-pdf-header-table td { border: 0; padding: 0; vertical-align: middle; }
    .pasantia-fdv .institutional-pdf-brand { width: 70%; }
    .pasantia-fdv .institutional-pdf-brand img { display: block; height: auto; width: 270pt; }
    .pasantia-fdv .institutional-pdf-contact { border-left: .6pt solid #002060; color: #002060; font-size: 8px; font-weight: 700; line-height: 1.2; padding: 4px 8px !important; text-align: right; width: 30%; }
    .pasantia-fdv .institutional-pdf-title { color: #002060; font-size: 11px; font-weight: 700; line-height: 1.1; padding-top: 7px !important; text-align: center; width: 78%; }
    .pasantia-fdv .institutional-pdf-code { background: #002060; color: #fff; font-size: 11px; font-weight: 700; padding: 8px !important; text-align: right; width: 22%; }
    .pasantia-fdv .content { position: relative; z-index: 1; }
    .pasantia-fdv .section { margin-top: 10px; }
    .pasantia-fdv .section-bar { background: #001b44; color: #fff; font-size: 10px; font-weight: 700; padding: 4px 9px; text-transform: uppercase; }
    .pasantia-fdv table.grid { border-collapse: collapse; table-layout: fixed; width: 100%; }
    .pasantia-fdv table.grid td { border: .5pt solid #374151; line-height: 1.35; overflow-wrap: anywhere; padding: 5px; vertical-align: middle; word-break: break-word; }
    .pasantia-fdv .num { background: #001b44; color: #fff; font-size: 9px; font-weight: 700; text-align: center; width: 44px; }
    .pasantia-fdv .num::after { content: "."; }
    .pasantia-fdv .lbl { background: #001b44; color: #fff; font-size: 9px; font-weight: 700; overflow-wrap: anywhere; width: 36%; }
    .pasantia-fdv .data { background: #fff; color: #111827; font-size: 9px; overflow-wrap: anywhere; white-space: pre-wrap; }
    .pasantia-fdv .empty { color: #9ca3af; font-style: italic; }
</style>

<div class="space-y-6">
    {{-- Campos explícitos del detalle: fecha_registro, fecha_inicio, fecha_finalizacion,
         tipo_pasantia, modalidad_ejecucion, nombre_contacto_directo,
         jornada_laboral_docente, firma_estudiante, archivo_convenio_marco. --}}
    @php
        $registro = $this->registro;
        $estadoTexto = $registro->estado === 'en_revision' && $registro->etapaActual ? $registro->etapaActual->nombre : ucfirst(str_replace('_', ' ', $registro->estado ?: 'sin estado'));
        $estadoBadge = match ($registro->estado) {
            'borrador' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-200',
            'enviado', 'en_revision' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
            'aprobado' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200',
            'rechazado' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
            'subsanacion' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
        };
        $puedeEnviarRevision = in_array($registro->estado, ['borrador', 'subsanacion'], true) && $registro->perteneceAlUsuario(auth()->id());
        $puedeEliminar = $registro->puedeEliminarBorrador(auth()->id());
        $puedeEditar = ($registro->perteneceAlUsuario(auth()->id()) && in_array($registro->estado, ['borrador', 'subsanacion'], true)) || ($registro->estado === 'en_revision' && $registro->usuarioPuedeRevisar(auth()->user()));
        $puedeRevisarEtapa = $registro->estado === 'en_revision' && $registro->usuarioPuedeRevisar(auth()->user());
        $puedeAprobar = $puedeRevisarEtapa && $registro->puedeAprobarse(auth()->id(), auth()->user());
        $puedeRechazar = $puedeRevisarEtapa && $registro->puedeRechazarse(auth()->id(), auth()->user());
        $puedeSubsanar = $registro->puedeSubsanarse(auth()->id());
        $puedeDescargarPdf = $registro->puedeDescargarPdf(auth()->id(), auth()->user());
        $puedeCrearRegistro = (bool) auth()->user()?->can('docente.crear-proyecto');
        $responsableActual = $registro->etapaActual?->usuarioResponsable;
        $mostrar = static function ($valor, string $campo): string {
            if ($valor === null || $valor === '') return 'Sin información';
            if (in_array($campo, ['fecha_registro', 'fecha_inicio', 'fecha_finalizacion'], true)) return $valor instanceof \DateTimeInterface ? $valor->format('d/m/Y') : \Illuminate\Support\Carbon::parse($valor)->format('d/m/Y');
            if (is_bool($valor)) return $valor ? 'Sí' : 'No';
            if (is_array($valor)) return collect($valor)->map(fn ($item) => is_array($item) ? implode(' — ', array_filter(array_map('strval', $item))) : (string) $item)->implode('; ');
            return (string) $valor;
        };
    @endphp

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <a href="{{ route($historialRouteName) }}" wire:navigate class="text-sm font-medium text-blue-600 hover:text-blue-700">Volver al historial</a>
                <p class="mt-2 text-xs font-semibold uppercase tracking-wide text-blue-700">FORM-DVUS-013</p>
                <h1 class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ $registro->codigo_registro ?: 'Registro de Pasantías #'.$registro->id }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $registro->nombre_estudiante ?: 'Estudiante no registrado' }} @if($registro->numero_cuenta) · {{ $registro->numero_cuenta }} @endif</p>
                <div class="mt-3 flex flex-wrap items-center gap-3"><span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $estadoBadge }}">{{ $estadoTexto }}</span>@if($registro->etapaActual)<span class="text-xs text-gray-500">Etapa: {{ $registro->etapaActual->nombre }}</span>@if($responsableActual)<span class="text-xs text-gray-500">Responsable: {{ $responsableActual->name }}</span>@endif @endif</div>
            </div>
            <div class="flex flex-wrap gap-2">
                @if($puedeEditar)<a href="{{ route('pasantias.edit', $registro->id) }}" wire:navigate class="inline-flex items-center rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-700 shadow-sm transition hover:bg-amber-100">Editar</a>@endif
                @if($puedeEliminar)<button type="button" x-on:click.prevent="confirmDialog('¿Desea eliminar este borrador? Esta acción no elimina físicamente el registro.').then((ok) => ok && $wire.eliminarBorrador())" wire:loading.attr="disabled" wire:target="eliminarBorrador" class="inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-700 shadow-sm transition hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-70"><span wire:loading.remove wire:target="eliminarBorrador">Eliminar</span><span wire:loading wire:target="eliminarBorrador">Eliminando...</span></button>@endif
                @if($puedeEnviarRevision)<button type="button" x-on:click.prevent="confirmDialog('Al enviar este registro a revisión ya no podrá editarse. ¿Desea continuar?').then((ok) => ok && $wire.enviarRevision())" wire:loading.attr="disabled" wire:target="enviarRevision" class="inline-flex items-center rounded-lg bg-green-600 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-70"><span wire:loading.remove wire:target="enviarRevision">Enviar a revisión</span><span wire:loading wire:target="enviarRevision">Enviando...</span></button>@endif
                @if($puedeRechazar)<button type="button" wire:click="abrirModalSubsanacion" class="inline-flex items-center rounded-lg bg-amber-600 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-amber-700">Enviar a subsanación</button>@endif
                @if($puedeAprobar)<button type="button" x-on:click.prevent="confirmDialog('¿Desea aprobar esta etapa del registro de Pasantías?').then((ok) => ok && $wire.aprobar())" wire:loading.attr="disabled" wire:target="aprobar" class="inline-flex items-center rounded-lg bg-green-600 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-70"><span wire:loading.remove wire:target="aprobar">Aprobar</span><span wire:loading wire:target="aprobar">Aprobando...</span></button>@endif
                @if($puedeSubsanar)<button type="button" x-on:click.prevent="confirmDialog('¿Desea iniciar la subsanación? El registro volverá a borrador para editarlo.').then((ok) => ok && $wire.iniciarSubsanacion())" wire:loading.attr="disabled" wire:target="iniciarSubsanacion" class="inline-flex items-center rounded-lg bg-amber-600 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-amber-700 disabled:cursor-not-allowed disabled:opacity-70"><span wire:loading.remove wire:target="iniciarSubsanacion">Iniciar subsanación</span><span wire:loading wire:target="iniciarSubsanacion">Abriendo...</span></button>@endif
                @if($puedeDescargarPdf)<a href="{{ route('pasantias.pdf', [$registro->id, 'tipo' => 'formulario']) }}" class="inline-flex items-center rounded-lg bg-sky-600 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-sky-700">Descargar PDF</a>@endif
                @if($puedeCrearRegistro)<a href="{{ route('crearPasantia') }}" wire:navigate class="inline-flex items-center rounded-lg bg-blue-700 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-800">Nuevo registro</a>@endif
            </div>
        </div>
    </div>

    @if($camposFaltantesEnvio !== [])<div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 shadow-sm"><p class="font-semibold">Complete la información obligatoria antes de enviar a revisión.</p><ul class="mt-2 list-disc space-y-1 pl-5">@foreach($camposFaltantesEnvio as $campo)<li>{{ $campo }}</li>@endforeach</ul></div>@endif
    @error('flujo')<div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 shadow-sm">{{ $message }}</div>@enderror
    @error('pdf')<div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 shadow-sm">{{ $message }}</div>@enderror
    @if($registro->motivo_rechazo && in_array($registro->estado, ['rechazado', 'subsanacion'], true))<div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 shadow-sm"><p class="font-semibold">Observaciones de subsanación</p><p class="mt-2 whitespace-pre-line">{{ $registro->motivo_rechazo }}</p></div>@endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <section class="min-w-0 rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <div>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">Ficha FORM-DVUS-013</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Datos registrados para revisión.</p>
                </div>
            </div>
            <div class="pasantia-fdv-shell">
                <div class="pasantia-fdv">
                    <div class="sheet">
                        @include('components.fichas.partials.institutional-pdf-header', ['isPdf' => false, 'institutionalVariant' => 'form', 'institutionalTitle' => 'FORMULARIO DE REGISTRO DE PASANTÍAS', 'institutionalSubtitle' => '', 'institutionalCode' => 'FORM-DVUS-013', 'institutionalPhone' => '2216-7070 Ext. 110576'])
                        <div class="content">
                            @php($fila = 0)
                            @foreach($secciones as $seccion => $campos)
                                <div class="section">
                                    <div class="section-bar">{{ $seccion }}</div>
                                    <table class="grid">
                                        <colgroup><col style="width:44px"><col style="width:36%"><col></colgroup>
                                        <tbody>
                                            @foreach($campos as $item)
                                                @php($fila++)
                                                @php($valor = $item['valor'])
                                                <tr>
                                                    <td class="num" data-campo="{{ $item['campo'] }}">{{ $fila }}</td>
                                                    <td class="lbl">{{ $item['etiqueta'] }}</td>
                                                    <td class="data {{ is_null($valor) ? 'empty' : '' }}">{{ $mostrar($valor, $item['campo']) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <aside class="space-y-6">
            <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900"><h2 class="mb-4 text-lg font-bold text-gray-900 dark:text-white">Historial de movimientos</h2>@if($movimientos->isNotEmpty())<ol class="relative border-s border-yellow-600">@foreach($movimientos as $movimiento)<li class="mb-6 ms-4"><div class="absolute -start-1.5 mt-1.5 h-3 w-3 rounded-full border border-white bg-yellow-600"></div><time class="text-xs text-yellow-700">{{ optional($movimiento->created_at)->format('d/m/Y H:i') }}</time><h3 class="mt-1 text-sm font-semibold">Estado: {{ $movimiento->tipoestado?->nombre ?? 'Cambio de estado' }}</h3><p class="text-xs text-gray-500">Etapa: {{ $registro->etapaActual?->nombre ?? 'No registrada' }} @if($movimiento->empleado) · Usuario: {{ $movimiento->empleado->nombre_completo }} @endif</p>@if($movimiento->comentario)<p class="mt-1 whitespace-pre-line break-words text-sm text-gray-600 dark:text-gray-300">Observación: {{ $movimiento->comentario }}</p>@endif</li>@endforeach</ol>@else<p class="text-sm text-gray-500">No hay movimientos registrados.</p>@endif</section>
            @if($registro->documentosGenerados->isNotEmpty())
            <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <h2 class="mb-3 text-lg font-bold text-gray-900 dark:text-white">Documentos generados</h2>
                <div class="space-y-2">
                    @foreach($registro->documentosGenerados->sortByDesc('generado_en') as $documento)
                        <a href="{{ route('pasantias.pdf', [$registro->id, 'tipo' => $documento->tipo]) }}" class="flex items-center justify-between gap-3 rounded-md border border-gray-200 px-3 py-2 text-sm text-blue-700 transition hover:bg-blue-50 dark:border-gray-700 dark:text-blue-300">
                            <span class="min-w-0 break-words">{{ $documento->tipo === 'formulario' ? 'FORM-DVUS-013' : ($documento->tipo === 'solicitud_practica' ? 'Solicitud' : 'Autorización') }} · v{{ $documento->version }}</span>
                            <span class="shrink-0">Descargar</span>
                        </a>
                    @endforeach
                </div>
            </section>
            @endif
            <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900"><h2 class="mb-4 text-lg font-bold">Anexos</h2>@forelse($anexos as $anexo)<div class="mb-3 rounded-lg border p-3 last:mb-0"><p class="text-sm font-semibold">{{ $anexo['titulo'] }}</p><p class="mt-1 text-xs text-gray-500">{{ $anexo['archivo'] ?: 'Marcado como adjunto, sin archivo registrado' }}</p>@if($anexo['exists'])<a href="{{ $anexo['url'] }}" target="_blank" rel="noopener" class="mt-2 inline-flex rounded-md border px-3 py-1.5 text-xs font-semibold text-blue-700">Ver / descargar anexo</a>@else<p class="mt-2 text-xs text-amber-600">No hay archivo disponible.</p>@endif</div>@empty<p class="text-sm text-gray-500">No hay anexos registrados.</p>@endforelse</section>
        </aside>
    </div>

    @if($subsanarModal)<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"><div class="w-full max-w-lg rounded-xl bg-white shadow-xl"><div class="border-b p-4"><h3 class="text-lg font-semibold">Enviar a subsanación</h3></div><div class="space-y-4 p-4"><label class="block text-sm font-medium">Correcciones requeridas <span class="text-red-500">*</span><textarea wire:model="subsanarComentario" rows="5" class="mt-1 w-full rounded-md border px-3 py-2"></textarea></label>@error('subsanarComentario')<p class="text-sm text-red-600">{{ $message }}</p>@enderror<div class="flex justify-end gap-3"><button type="button" wire:click="cerrarModalSubsanacion" class="rounded-lg border px-4 py-2">Cancelar</button><button type="button" wire:click="enviarASubsanar" wire:loading.attr="disabled" class="rounded-lg bg-amber-600 px-4 py-2 text-white">Subsanar</button></div></div></div></div>@endif
</div>
