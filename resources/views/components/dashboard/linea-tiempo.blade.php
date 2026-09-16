@props([
    // Objetos con: tipo_elemento, nombre_elemento, estado, es_actual, comentario, fecha, href
    'items' => [],
    'vacio' => 'No hay actividad reciente.',
])

@if (empty($items) || (is_countable($items) && count($items) === 0))
    <x-dashboard.estado-vacio
        :titulo="$vacio"
        mensaje="Los cambios de estado aparecerán aquí."
        icono="heroicon-o-clock"
    />
@else
    {{--
        Extracción del bloque de línea de tiempo que estaba repetido casi
        idéntico en los tres paneles. Conserva el "Ver más" con Alpine para los
        comentarios largos de subsanación.
    --}}
    <ol {{ $attributes->merge(['class' => 'relative space-y-4 border-l border-slate-200 pl-5 dark:border-slate-700']) }}>
        @foreach ($items as $item)
            <li class="relative">
                <span class="absolute -left-[1.5625rem] top-1.5 flex h-2.5 w-2.5 items-center justify-center">
                    {{-- Anillo del color de la superficie para que el punto se lea sobre la línea. --}}
                    <span class="h-2.5 w-2.5 rounded-full ring-2 ring-white dark:ring-slate-900 {{ $item->es_actual ? 'bg-primary-600 dark:bg-primary-400' : 'bg-slate-300 dark:bg-slate-600' }}"></span>
                </span>

                <div class="flex flex-wrap items-center gap-1.5">
                    <x-dashboard.chip-estado :nombre="$item->estado" tamano="xs" />
                    <span class="text-[11px] text-slate-400">{{ $item->fecha }}</span>
                </div>

                @if ($item->href ?? null)
                    <a href="{{ $item->href }}" wire:navigate
                        class="mt-1 block truncate text-sm font-semibold text-slate-900 hover:text-primary-600 dark:text-white dark:hover:text-primary-300"
                        title="{{ $item->nombre_elemento }}">
                        {{ $item->nombre_elemento }}
                    </a>
                @else
                    <p class="mt-1 truncate text-sm font-semibold text-slate-900 dark:text-white" title="{{ $item->nombre_elemento }}">
                        {{ $item->nombre_elemento }}
                    </p>
                @endif

                @if ($item->comentario ?? null)
                    <div x-data="{ abierto: false }"
                        class="mt-2 rounded-lg border-l-2 border-slate-200 bg-slate-50 p-2.5 text-xs italic text-slate-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300">
                        <p class="break-words" :class="abierto ? '' : 'line-clamp-3'">"{{ $item->comentario }}"</p>
                        @if (mb_strlen($item->comentario) > 160)
                            <button type="button" @click="abierto = !abierto"
                                class="mt-1 text-[11px] font-semibold not-italic text-primary-600 hover:underline dark:text-primary-300">
                                <span x-show="!abierto">Ver más</span>
                                <span x-show="abierto" x-cloak>Ver menos</span>
                            </button>
                        @endif
                    </div>
                @endif
            </li>
        @endforeach
    </ol>
@endif
