@props([
    'titulo',
    'subtitulo' => null,
    'icono' => null,
    // Cifra o texto que resume el contenido plegado, para no tener que abrirlo.
    'resumen' => null,
    'abierto' => false,
    'sinPadding' => false,
])

{{--
    Panel que se pliega y despliega en el sitio, empujando lo que tiene debajo.

    El panel llega cerrado salvo que se indique lo contrario: la cabecera lleva
    una cifra de resumen, así que se sabe si hay algo dentro sin abrirlo. Lo que
    se consulta a diario va abierto; lo que se mira de vez en cuando, cerrado.
--}}
<section
    x-data="{ abierto: {{ $abierto ? 'true' : 'false' }} }"
    {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900']) }}
>
    <h2>
        <button
            type="button"
            @click="abierto = ! abierto"
            :aria-expanded="abierto ? 'true' : 'false'"
            class="flex w-full items-center gap-3 px-5 py-4 text-left transition hover:bg-slate-50 focus-visible:outline focus-visible:-outline-offset-2 focus-visible:outline-2 focus-visible:outline-primary-500 dark:hover:bg-slate-800/60"
        >
            @if ($icono)
                @svg($icono, ['class' => 'h-4 w-4 shrink-0 text-slate-400'])
            @endif

            <span class="min-w-0 flex-1">
                <span class="block text-sm font-bold text-slate-900 dark:text-white">{{ $titulo }}</span>
                @if ($subtitulo)
                    <span class="mt-0.5 block truncate text-xs text-slate-500 dark:text-slate-400">{{ $subtitulo }}</span>
                @endif
            </span>

            @if ($resumen)
                <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold tabular-nums text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                    {{ $resumen }}
                </span>
            @endif

            @svg('heroicon-o-chevron-down', [
                'class' => 'h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200',
                ':class' => "abierto ? 'rotate-180' : ''",
            ])
        </button>
    </h2>

    {{-- x-collapse anima la altura y deja que el contenido empuje lo de abajo. --}}
    <div x-show="abierto" x-collapse x-cloak>
        <div class="{{ $sinPadding ? '' : 'px-5 pb-5' }} border-t border-slate-100 dark:border-slate-800">
            <div class="{{ $sinPadding ? '' : 'pt-4' }}">
                {{ $slot }}
            </div>
        </div>
    </div>
</section>
