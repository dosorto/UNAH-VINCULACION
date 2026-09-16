@props([
    // info | alerta
    'tono' => 'info',
    'titulo' => null,
    'mensaje' => null,
    'accionTexto' => null,
    'accionHref' => null,
])

@php
    [$caja, $iconoColor, $icono] = match ($tono) {
        'alerta' => [
            'border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950/40',
            'text-amber-600 dark:text-amber-400',
            'heroicon-o-exclamation-triangle',
        ],
        default => [
            'border-sky-200 bg-sky-50 dark:border-sky-900 dark:bg-sky-950/40',
            'text-sky-600 dark:text-sky-400',
            'heroicon-o-information-circle',
        ],
    };
@endphp

<div {{ $attributes->merge(['class' => "flex items-start gap-3 rounded-2xl border px-4 py-3 {$caja}"]) }}>
    @svg($icono, ['class' => 'mt-0.5 h-4 w-4 shrink-0 '.$iconoColor])

    <div class="min-w-0 flex-1 text-xs">
        @if ($titulo)
            <p class="font-bold text-slate-800 dark:text-slate-100">{{ $titulo }}</p>
        @endif
        @if ($mensaje)
            <p class="mt-0.5 text-slate-600 dark:text-slate-300">{{ $mensaje }}</p>
        @endif
        {{ $slot }}

        @if ($accionTexto && $accionHref)
            <a href="{{ $accionHref }}" wire:navigate
                class="mt-1.5 inline-block font-semibold text-primary-600 hover:underline dark:text-primary-300">
                {{ $accionTexto }} →
            </a>
        @endif
    </div>
</div>
