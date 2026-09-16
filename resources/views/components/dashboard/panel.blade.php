@props([
    'titulo' => null,
    'subtitulo' => null,
    'icono' => null,
    'sinPadding' => false,
])

{{--
    Tarjeta contenedora del panel. Sustituye a la veintena de
    <div class="py-6 px-5 bg-white dark:bg-gray-800 rounded-xl border ..."> que
    estaban copiados por los tres dashboards con medidas ligeramente distintas.
--}}
<section {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900']) }}>
    @if ($titulo || isset($acciones))
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 px-5 py-4 dark:border-slate-800">
            <div class="min-w-0">
                <h2 class="flex items-center gap-2 text-sm font-bold text-slate-900 dark:text-white">
                    @if ($icono)
                        @svg($icono, ['class' => 'h-4 w-4 shrink-0 text-slate-400'])
                    @endif
                    {{ $titulo }}
                </h2>
                @if ($subtitulo)
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $subtitulo }}</p>
                @endif
            </div>

            @if (isset($acciones))
                <div class="flex shrink-0 items-center gap-2">{{ $acciones }}</div>
            @endif
        </div>
    @endif

    <div class="{{ $sinPadding ? '' : 'p-5' }}">
        {{ $slot }}
    </div>

    @if (isset($pie))
        <div class="border-t border-slate-100 px-5 py-3 dark:border-slate-800">
            {{ $pie }}
        </div>
    @endif
</section>
