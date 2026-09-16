@props([
    'titulo',
    'subtitulo' => null,
    // Alcance de los datos: "Toda la UNAH", "CU Valle de Sula"...
    'ambito' => null,
    'rol' => null,
])

{{--
    Cabecera del panel. Azul institucional #001b3d con filo dorado, en lugar del
    bg-yellow-500 genérico que traía antes.

    Los dos distintivos (rol activo y ámbito) no son decorativos: son lo que
    explica por qué los números de la bandeja y los del centro no coinciden.
--}}
<header class="rounded-3xl bg-primary-900 px-6 py-6 shadow-soft border-b-4 border-amber-400 dark:bg-primary-950 sm:px-8">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div class="min-w-0">
            @if ($rol || $ambito)
                <div class="mb-2 flex flex-wrap items-center gap-2">
                    @if ($rol)
                        <span class="inline-flex items-center rounded-full bg-amber-400/15 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.14em] text-amber-200">
                            {{ $rol }}
                        </span>
                    @endif
                    @if ($ambito)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-[11px] font-semibold text-slate-100">
                            @svg('heroicon-o-map-pin', ['class' => 'h-3.5 w-3.5 shrink-0 text-amber-300'])
                            <span class="truncate">{{ $ambito }}</span>
                        </span>
                    @endif
                </div>
            @endif

            <h1 class="text-2xl font-black tracking-tight text-white sm:text-3xl">{{ $titulo }}</h1>

            @if ($subtitulo)
                <p class="mt-1.5 max-w-2xl text-sm text-slate-300">{{ $subtitulo }}</p>
            @endif
        </div>

        @if (isset($acciones))
            <div class="flex flex-wrap items-center gap-2">
                {{ $acciones }}
            </div>
        @endif
    </div>

    @if (isset($metricas))
        <div class="mt-6 border-t border-white/10 pt-5">
            {{ $metricas }}
        </div>
    @endif
</header>
