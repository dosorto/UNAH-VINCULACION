@extends('layouts.panel.base')

@php
    $versionDocumento = max(
        (int) ($accion->updated_at?->timestamp ?? 0),
        (int) ($accion->documentos->max('updated_at')?->timestamp ?? 0),
    );
@endphp

@section('main')
    <div class="mx-auto w-full max-w-none space-y-4">
        <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div>
                    <a href="{{ route('enf.acciones.show', $accion) }}" class="text-sm font-semibold text-sky-700 hover:text-sky-900 dark:text-sky-300">Volver al detalle</a>
                    <h1 class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ $accion->nombre_accion }}</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Vista del FORM-DVUS-018 en NEXO.</p>
                </div>

                <a href="{{ route('enf.acciones.pdf', $accion) }}" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-sky-700">
                    @svg('heroicon-o-arrow-down-tray', ['class' => 'h-4 w-4'])
                    Descargar ficha PDF
                </a>
            </div>

            <div class="mt-5 border-t border-gray-200 pt-4 dark:border-gray-700">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-gray-700 dark:text-gray-200">Documentos adjuntos del paso 10</h2>
                    <span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700 dark:bg-sky-950/50 dark:text-sky-300">{{ $accion->documentos->count() }}</span>
                </div>

                <div class="mt-3 flex flex-wrap gap-3">
                    @forelse ($accion->documentos as $documento)
                        <article class="min-w-64 flex-1 rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $documento->nombre }}</p>
                            @if ($documento->ruta && $documento->ruta !== 'pendiente')
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <a href="{{ route('enf.documentos.ver', $documento) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-lg border border-sky-300 px-3 py-2 text-xs font-semibold text-sky-700 hover:bg-sky-50 dark:border-sky-700 dark:text-sky-300">
                                        @svg('heroicon-o-eye', ['class' => 'h-4 w-4'])
                                        Ver anexo
                                    </a>
                                    <a href="{{ route('enf.documentos.descargar', $documento) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-sky-600 px-3 py-2 text-xs font-semibold text-white hover:bg-sky-700">
                                        @svg('heroicon-o-arrow-down-tray', ['class' => 'h-4 w-4'])
                                        Descargar archivo
                                    </a>
                                </div>
                            @else
                                <p class="mt-2 text-xs text-gray-500">Archivo pendiente.</p>
                            @endif
                        </article>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">No hay archivos asociados a esta ficha.</p>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <iframe
                src="{{ route('enf.acciones.pdf.contenido', ['accion' => $accion, 'v' => $versionDocumento]) }}"
                title="FORM-DVUS-018"
                class="block min-h-[85vh] w-full border-0 bg-white"
            ></iframe>
        </section>
    </div>
@endsection
