<div class="space-y-6">
    @push('styles')
        <style>
            @media print {
                .no-print {
                    display: none !important;
                }
            }
        </style>
    @endpush

    @php
        use Carbon\Carbon;

        $estadoNombre = $proyecto->estado?->tipoestado?->nombre;
        $firmaRevisionPendiente = $this->firmaPendienteRevision();
    @endphp

    <div class="no-print rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <a href="{{ route('proyectosDocente', ['tipo' => 'proyectos']) }}" class="text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">
                    Volver al historial
                </a>
                <h1 class="mt-1 text-xl font-bold text-gray-900 dark:text-white">
                    {{ $proyecto->nombre_proyecto }}
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Estado: {{ $estadoNombre ?? 'Sin estado' }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('proyecto.perfil.pdf.download', ['proyecto' => $proyecto->id]) }}"
                   class="inline-flex items-center rounded-lg bg-sky-600 px-3 py-2 text-sm font-medium text-white hover:bg-sky-700">
                    Descargar PDF
                </a>

                @if ($firmaRevisionPendiente)
                    <button wire:click="openSubsanar"
                            class="inline-flex items-center rounded-lg bg-amber-600 px-3 py-2 text-sm font-medium text-white hover:bg-amber-700">
                        Subsanar
                    </button>
                    <button x-on:click.prevent="confirmDialog('¿Aprobar esta etapa y avanzar el proyecto?').then((ok) => ok && $wire.aprobarFirmaPendiente())"
                            class="inline-flex items-center rounded-lg bg-green-600 px-3 py-2 text-sm font-medium text-white hover:bg-green-700">
                        Aprobar
                    </button>
                @endif

                @if ($esCoordinador)
                    @if (in_array($estadoNombre, ['Borrador', 'Autoguardado', 'Subsanacion']))
                        <a href="{{ route('crearProyectoVinculacion', $proyecto) }}"
                           class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700">
                            {{ $estadoNombre === 'Subsanacion' ? 'Subsanar Proyecto' : 'Continuar Editando' }}
                        </a>
                    @endif

                    @if ($estadoNombre === 'En curso')
                        <a href="{{ route('ficha-actualizacion', ['proyecto' => $proyecto->id]) }}"
                           class="inline-flex items-center rounded-lg bg-violet-600 px-3 py-2 text-sm font-medium text-white hover:bg-violet-700">
                            Actualizar Equipo o Fechas
                        </a>
                    @endif
                @endif
            </div>
        </div>
    </div>

    @if($informeIntermedio['visible'])
        <section class="no-print rounded-xl border border-sky-200 bg-white p-5 shadow-sm dark:border-sky-900 dark:bg-gray-900">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-bold uppercase tracking-wide text-sky-700 dark:text-sky-400">Seguimiento del proyecto</p>
                    <h2 class="mt-1 text-lg font-bold text-gray-900 dark:text-white">Informe Intermedio</h2>
                    <dl class="mt-3 grid gap-x-8 gap-y-2 text-sm sm:grid-cols-2 lg:grid-cols-4">
                        <div><dt class="text-gray-500">Estado</dt><dd class="font-semibold text-gray-900 dark:text-gray-100">{{ $informeIntermedio['etiqueta'] }}</dd></div>
                        @if($informeIntermedio['informe'])
                            <div><dt class="text-gray-500">Archivo</dt><dd class="truncate text-gray-900 dark:text-gray-100">{{ $informeIntermedio['informe']->nombre_original }}</dd></div>
                            <div><dt class="text-gray-500">Tamaño</dt><dd class="text-gray-900 dark:text-gray-100">{{ number_format($informeIntermedio['informe']->tamano_bytes / 1048576, 2) }} MB</dd></div>
                            <div><dt class="text-gray-500">Fecha de carga</dt><dd class="text-gray-900 dark:text-gray-100">{{ $informeIntermedio['informe']->fecha_carga?->format('d/m/Y H:i') }}</dd></div>
                        @endif
                        @if($informeIntermedio['etapa_actual'])
                            <div><dt class="text-gray-500">Etapa actual</dt><dd class="text-gray-900 dark:text-gray-100">{{ $informeIntermedio['etapa_actual'] }}</dd></div>
                        @endif
                    </dl>

                    @if($informeIntermedio['informe']?->observaciones)
                        <p class="mt-3 rounded-lg bg-amber-50 p-3 text-sm text-amber-900 dark:bg-amber-950/40 dark:text-amber-100">
                            <strong>Observaciones de subsanación:</strong> {{ $informeIntermedio['informe']->observaciones }}
                        </p>
                    @endif

                    @if($informeIntermedio['legacy'])
                        <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">
                            Este informe fue enviado antes de habilitarse el registro documental con metadatos. Su historial de revisión se conserva sin reiniciar el flujo.
                        </p>
                    @endif

                    @if($opcionesDestinatariosIntermedio->isNotEmpty() && $informeIntermedio['puede_enviar'])
                        <div class="mt-4 grid gap-3 md:grid-cols-2">
                            @foreach($opcionesDestinatariosIntermedio as $etapaId => $opcion)
                                <label class="text-sm text-gray-700 dark:text-gray-200">
                                    Destinatario para {{ $opcion['etapa']->nombre }}
                                    <select wire:model="destinatariosIntermedio.{{ $etapaId }}" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                                        <option value="">Seleccione un destinatario</option>
                                        @foreach($opcion['usuarios'] as $usuario)
                                            <option value="{{ $usuario->id }}">{{ $usuario->empleado?->nombre_completo ?? $usuario->name }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="flex flex-wrap justify-end gap-2">
                    @if($informeIntermedio['informe'])
                        <a href="{{ route('informes-intermedios.ver', $informeIntermedio['informe']) }}" target="_blank" class="rounded-lg border border-sky-300 px-3 py-2 text-sm font-medium text-sky-700 dark:border-sky-700 dark:text-sky-300">Ver PDF</a>
                        <a href="{{ route('informes-intermedios.descargar', $informeIntermedio['informe']) }}" class="rounded-lg border border-sky-300 px-3 py-2 text-sm font-medium text-sky-700 dark:border-sky-700 dark:text-sky-300">Descargar</a>
                    @endif
                    @if($informeIntermedio['puede_editar'])
                        <button wire:click="openSubirIntermedio" class="rounded-lg bg-sky-600 px-3 py-2 text-sm font-medium text-white hover:bg-sky-700">
                            {{ $informeIntermedio['informe'] ? 'Reemplazar PDF' : 'Cargar PDF' }}
                        </button>
                    @endif
                    @if($informeIntermedio['informe']?->estado === \App\Models\InformeIntermedio\InformeIntermedioProyecto::ESTADO_BORRADOR)
                        <button wire:click="eliminarInformeIntermedio" wire:confirm="¿Eliminar el PDF en borrador?" class="rounded-lg border border-rose-300 px-3 py-2 text-sm font-medium text-rose-700 dark:border-rose-800 dark:text-rose-300">Eliminar</button>
                    @endif
                    @if($informeIntermedio['puede_enviar'])
                        <button wire:click="enviarInformeIntermedio" wire:confirm="¿Enviar el Informe Intermedio a revisión?" class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700">Enviar a revisión</button>
                    @endif
                </div>
            </div>

            @if($informeIntermedio['historial']->isNotEmpty())
                <details class="mt-4">
                    <summary class="cursor-pointer text-sm font-semibold text-gray-700 dark:text-gray-200">Historial de revisión</summary>
                    <ol class="mt-2 space-y-2 border-s border-sky-300 ps-4 text-sm">
                        @foreach($informeIntermedio['historial'] as $movimiento)
                            <li>
                                <span class="font-medium">{{ $movimiento->tipoestado?->nombre ?? 'Movimiento' }}</span>
                                · {{ $movimiento->created_at?->format('d/m/Y H:i') }}
                                @if($movimiento->comentario) — {{ $movimiento->comentario }} @endif
                            </li>
                        @endforeach
                    </ol>
                </details>
            @endif
        </section>
    @endif

    @if($proyecto->puedeMostrarCierreProyecto(auth()->user()))
        <section class="no-print rounded-xl border border-emerald-200 bg-white p-5 shadow-sm dark:border-emerald-900 dark:bg-gray-900">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-400">Cierre del proyecto</p>
                    <h2 class="mt-1 text-lg font-bold text-gray-900 dark:text-white">Informe Final INF-001</h2>
                    <dl class="mt-3 grid gap-x-8 gap-y-2 text-sm sm:grid-cols-2 lg:grid-cols-3">
                        <div><dt class="text-gray-500">Estado</dt><dd class="font-semibold text-gray-900 dark:text-gray-100">{{ $cierreInformeFinal['etiqueta'] }}</dd></div>
                        @if(!empty($cierreInformeFinal['fecha_creacion']))
                            <div><dt class="text-gray-500">Fecha de creación</dt><dd class="text-gray-900 dark:text-gray-100">{{ $cierreInformeFinal['fecha_creacion']->format('d/m/Y H:i') }}</dd></div>
                        @endif
                        @if(!empty($cierreInformeFinal['fecha_envio']))
                            <div><dt class="text-gray-500">Fecha de envío</dt><dd class="text-gray-900 dark:text-gray-100">{{ $cierreInformeFinal['fecha_envio']->format('d/m/Y H:i') }}</dd></div>
                        @endif
                        @if(!empty($cierreInformeFinal['etapa_actual']))
                            <div><dt class="text-gray-500">Etapa actual</dt><dd class="text-gray-900 dark:text-gray-100">{{ $cierreInformeFinal['etapa_actual'] }}</dd></div>
                        @endif
                        @if(!empty($cierreInformeFinal['revisor_actual']))
                            <div><dt class="text-gray-500">Revisor actual</dt><dd class="text-gray-900 dark:text-gray-100">{{ $cierreInformeFinal['revisor_actual'] }}</dd></div>
                        @endif
                    </dl>
                    @if(!empty($cierreInformeFinal['motivo_rechazo']))
                        <p class="mt-3 rounded-lg bg-rose-50 p-3 text-sm text-rose-800 dark:bg-rose-950/40 dark:text-rose-200">
                            <strong>Motivo de rechazo:</strong> {{ $cierreInformeFinal['motivo_rechazo'] }}
                        </p>
                    @endif
                    @if($opcionesDestinatariosCierre->isNotEmpty() && $cierreInformeFinal['accion'] === 'enviar')
                        <div class="mt-4 grid gap-3 md:grid-cols-2">
                            @foreach($opcionesDestinatariosCierre as $etapaId => $opcion)
                                <label class="text-sm text-gray-700 dark:text-gray-200">
                                    Destinatario para {{ $opcion['etapa']->nombre }}
                                    <select wire:model="destinatariosCierre.{{ $etapaId }}" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                                        <option value="">Seleccione un destinatario</option>
                                        @foreach($opcion['usuarios'] as $usuario)
                                            <option value="{{ $usuario->id }}">{{ $usuario->empleado?->nombre_completo ?? $usuario->name }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="flex flex-wrap justify-end gap-2">
                    @if($cierreInformeFinal['accion'] === 'crear')
                        <button wire:click="crearInformeFinal" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Crear informe final</button>
                    @elseif($cierreInformeFinal['accion'] === 'continuar')
                        <a href="{{ route('proyectos.informe-final', $proyecto) }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">{{ $cierreInformeFinal['texto_accion'] }}</a>
                    @elseif($cierreInformeFinal['accion'] === 'subsanar')
                        <a href="{{ route('proyectos.informe-final', $proyecto) }}" class="rounded-lg border border-emerald-300 px-4 py-2 text-sm font-medium text-emerald-700 dark:border-emerald-700 dark:text-emerald-300">Editar subsanación</a>
                        @if($cierreInformeFinal['puede_enviar'])
                            <button wire:click="enviarInformeFinal" wire:confirm="¿Reenviar el INF-001 a la etapa que solicitó la subsanación?" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Reenviar informe final</button>
                        @endif
                    @elseif($cierreInformeFinal['accion'] === 'enviar' && $cierreInformeFinal['puede_enviar'])
                        <button wire:click="enviarInformeFinal" wire:confirm="¿Enviar el INF-001 al flujo de cierre? La edición quedará bloqueada." class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">{{ $cierreInformeFinal['texto_accion'] }}</button>
                    @elseif($cierreInformeFinal['accion'] === 'ver')
                        <a href="{{ route('informes-finales.inf-001.preview', $cierreInformeFinal['informe']) }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 dark:border-gray-600 dark:text-gray-200">Informe final en revisión</a>
                    @elseif($cierreInformeFinal['accion'] === 'aprobado')
                        <a href="{{ route('informes-finales.inf-001.preview', $cierreInformeFinal['informe']) }}" class="rounded-lg border border-emerald-300 px-4 py-2 text-sm font-medium text-emerald-700 dark:border-emerald-700 dark:text-emerald-300">Ver informe final aprobado</a>
                        <a href="{{ route('informes-finales.inf-001.pdf', $cierreInformeFinal['informe']) }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Descargar PDF final</a>
                    @endif
                </div>
            </div>
        </section>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <section class="min-w-0">
            @include('components.fichas.ficha-proyecto-vinculacion', [
                'proyecto' => $proyecto,
                'hideEmbeddedDocuments' => true,
            ])
        </section>

        <aside class="no-print rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h2 class="mb-4 text-lg font-bold text-gray-900 dark:text-white">
                Historial de movimientos
            </h2>

            <div class="max-h-[calc(100vh-12rem)] overflow-y-auto pr-2">
                @if ($estados->count() > 0)
                    <ol class="relative border-s border-yellow-600">
                        @foreach ($estados as $index => $estado)
                            @php
                                $esMovimientoCierre = ($estado->estadoable instanceof \App\Models\Proyecto\DocumentoProyecto
                                    && $estado->estadoable->tipo_documento === 'Informe Final')
                                    || str_starts_with((string) $estado->comentario, '[Cierre INF-001]');
                            @endphp
                            <li class="{{ $index < $estados->count() - 1 ? 'mb-8' : '' }} ms-4">
                                <div class="absolute -start-1.5 mt-1.5 h-3 w-3 rounded-full border border-white bg-yellow-600"></div>
                                <time class="text-sm font-normal leading-none text-yellow-600">
                                    {{ Carbon::parse($estado->created_at)->format('d') }} de
                                    {{ Carbon::parse($estado->created_at)->translatedFormat('F') }} del
                                    {{ Carbon::parse($estado->created_at)->format('Y') }}
                                </time>
                                <h3 class="mt-2 text-base font-semibold text-gray-900 dark:text-gray-200">
                                    Estado: {{ $estado->tipoestado?->nombre ?? 'Cambio de estado' }}
                                </h3>
                                <span class="mt-1 inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $esMovimientoCierre ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800' }}">
                                    {{ $esMovimientoCierre ? 'Flujo de cierre INF-001' : 'Flujo normal del proyecto' }}
                                </span>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    @if ($estado->empleado)
                                        Realizado por: {{ $estado->empleado->nombre_completo }}
                                    @endif
                                    {{ $estado->comentario ? ' - ' . $estado->comentario : '' }}
                                </p>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        No hay movimientos registrados para este proyecto.
                    </p>
                @endif
            </div>
        </aside>
    </div>

    @if ($subsanarModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-lg rounded-xl bg-white shadow-xl dark:bg-gray-800">
                <div class="flex items-center justify-between border-b border-gray-200 p-4 dark:border-gray-700">
                    <h3 class="text-lg font-semibold dark:text-white">Enviar a subsanacion</h3>
                    <button wire:click="$set('subsanarModal', false)" class="text-2xl leading-none text-gray-400 hover:text-gray-600">&times;</button>
                </div>
                <div class="space-y-4 p-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Correcciones requeridas <span class="text-red-500">*</span>
                        </label>
                        <textarea wire:model="subsanarComentario" rows="5"
                                  class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white"></textarea>
                        @error('subsanarComentario') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button wire:click="$set('subsanarModal', false)"
                                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Cancelar
                        </button>
                        <button wire:click="subsanar"
                                class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700">
                            Subsanar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($informeIntermedioModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-lg rounded-xl bg-white shadow-xl dark:bg-gray-800">
                <div class="flex items-center justify-between border-b border-gray-200 p-4 dark:border-gray-700">
                    <h3 class="text-lg font-semibold dark:text-white">Subir Informe Intermedio</h3>
                    <button wire:click="$set('informeIntermedioModal', false)" class="text-2xl leading-none text-gray-400 hover:text-gray-600">&times;</button>
                </div>
                <div class="space-y-4 p-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Archivo PDF</label>
                        <input type="file" wire:model="informeIntermedioFile" accept=".pdf"
                               class="w-full text-sm text-gray-700 file:mr-3 file:rounded file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100 dark:text-gray-300">
                        @error('informeIntermedioFile') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        <div wire:loading wire:target="informeIntermedioFile" class="mt-1 text-sm text-gray-500">Cargando archivo...</div>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button wire:click="$set('informeIntermedioModal', false)"
                                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Cancelar
                        </button>
                        <button wire:click="guardarInformeIntermedio" wire:loading.attr="disabled" wire:target="guardarInformeIntermedio"
                                class="rounded-lg bg-yellow-500 px-4 py-2 text-sm font-medium text-white hover:bg-yellow-600 disabled:opacity-50">
                            <span wire:loading.remove wire:target="guardarInformeIntermedio">Guardar borrador</span>
                            <span wire:loading wire:target="guardarInformeIntermedio">Guardando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>
