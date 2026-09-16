@props([
    'etiqueta',
    'valor',
    'icono' => 'heroicon-o-chart-bar',
    // neutro | info | exito | alerta | riesgo | acento
    'tono' => 'neutro',
    'href' => null,
    // Texto pequeño bajo la etiqueta: aclara de dónde sale la cifra.
    'ayuda' => null,
    'sufijo' => null,
])

@php
    // Las clases se escriben literales aquí porque Tailwind analiza los .blade,
    // no cadenas construidas en PHP.
    $tonoIcono = match ($tono) {
        'info' => 'bg-sky-50 text-sky-700 dark:bg-sky-950 dark:text-sky-300',
        'exito' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
        'alerta' => 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
        'riesgo' => 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300',
        'acento' => 'bg-primary-50 text-primary-700 dark:bg-primary-900 dark:text-primary-200',
        default => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
    };

    $etiquetaComoTexto = is_numeric($valor) ? number_format((float) $valor) : $valor;

    $clasesTarjeta = 'group flex items-start gap-3 rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900';
    $clasesEnlace = $href ? ' transition hover:border-primary-300 hover:shadow-sm dark:hover:border-primary-700' : '';
@endphp

<{{ $href ? 'a' : 'div' }}
    @if ($href) href="{{ $href }}" wire:navigate @endif
    {{ $attributes->merge(['class' => $clasesTarjeta.$clasesEnlace]) }}
>
    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $tonoIcono }}">
        @svg($icono, ['class' => 'h-5 w-5'])
    </span>

    <span class="min-w-0 flex-1">
        {{-- Figuras proporcionales: tabular-nums solo tiene sentido en columnas. --}}
        <span class="block text-2xl font-black leading-none text-slate-900 dark:text-white">
            {{ $etiquetaComoTexto }}@if ($sufijo)<span class="ml-0.5 text-sm font-bold text-slate-400">{{ $sufijo }}</span>@endif
        </span>

        <span class="mt-1.5 block truncate text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">
            {{ $etiqueta }}
        </span>

        @if ($ayuda)
            <span class="mt-0.5 block truncate text-[11px] font-normal text-slate-400 dark:text-slate-500">{{ $ayuda }}</span>
        @endif
    </span>

    @if ($href)
        @svg('heroicon-o-chevron-right', ['class' => 'mt-1 h-4 w-4 shrink-0 text-slate-300 transition group-hover:text-primary-500 dark:text-slate-600'])
    @endif
</{{ $href ? 'a' : 'div' }}>
