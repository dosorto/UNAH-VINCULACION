@props([
    'titulo',
    'mensaje' => null,
    'icono' => 'heroicon-o-inbox',
    // info | exito | alerta
    'tono' => 'info',
    'accionTexto' => null,
    'accionHref' => null,
])

@php
    $tonoIcono = match ($tono) {
        'exito' => 'text-emerald-500',
        'alerta' => 'text-amber-500',
        default => 'text-slate-300 dark:text-slate-600',
    };
@endphp

{{-- Sustituye los seis bloques de SVG en línea que estaban copiados por los paneles. --}}
<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-4 py-8 text-center']) }}>
    @svg($icono, ['class' => 'h-9 w-9 '.$tonoIcono])

    <p class="mt-3 text-sm font-semibold text-slate-600 dark:text-slate-300">{{ $titulo }}</p>

    @if ($mensaje)
        <p class="mt-1 max-w-xs text-xs text-slate-400 dark:text-slate-500">{{ $mensaje }}</p>
    @endif

    @if ($accionTexto && $accionHref)
        <a href="{{ $accionHref }}" wire:navigate
            class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-primary-900 px-4 py-2 text-xs font-bold text-white transition hover:bg-primary-800">
            {{ $accionTexto }}
            @svg('heroicon-o-arrow-right', ['class' => 'h-3.5 w-3.5'])
        </a>
    @endif
</div>
