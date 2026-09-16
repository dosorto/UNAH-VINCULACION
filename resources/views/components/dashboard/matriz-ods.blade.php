@props([
    // [['etiqueta' => '1. Fin de la pobreza', 'valor' => 13], ...] — los 17 ODS
    'items' => [],
    'vacio' => 'Todavía no hay proyectos con ODS asignados.',
])

@php
    // Un ranking de seis barras deja fuera dos tercios del marco y no permite
    // ver qué ODS están sin cubrir, que suele ser el dato interesante. La
    // matriz muestra los 17 y usa el mismo espacio.
    $porNumero = [];

    foreach ($items as $item) {
        // Los nombres vienen como "7. Energía asequible y no contaminante".
        if (preg_match('/^\s*(\d{1,2})\s*\.\s*(.+)$/u', (string) $item['etiqueta'], $m)) {
            $porNumero[(int) $m[1]] = [
                'nombre' => trim($m[2]),
                'valor' => (int) $item['valor'],
                'proyectos' => $item['proyectos'] ?? [],
            ];
        }
    }

    $maximo = max(1, max(array_column($porNumero, 'valor') ?: [0]));
    $hayDatos = array_sum(array_column($porNumero, 'valor')) > 0;

    // Rampa secuencial de un solo tono, validada como ordinal contra ambas
    // superficies: el paso más claro sigue despegando del fondo.
    $pasos = [
        'bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-600',
        'bg-[#8fb5e3] text-[#123a6e] dark:bg-[#2f5f92] dark:text-white',
        'bg-[#6b9fdd] text-[#123a6e] dark:bg-[#3a72ab] dark:text-white',
        'bg-[#4a86d8] text-white dark:bg-[#4a86d8] dark:text-white',
        'bg-[#2f6bb5] text-white dark:bg-[#6b9fdd] dark:text-[#0f172a]',
        'bg-[#1f5397] text-white dark:bg-[#8fb5e3] dark:text-[#0f172a]',
    ];
@endphp

@if (! $hayDatos)
    <x-dashboard.estado-vacio :titulo="$vacio" icono="heroicon-o-globe-alt" />
@else
    <div {{ $attributes }} x-data="{ activo: null }">
        <div class="grid grid-cols-6 gap-1.5 sm:grid-cols-9 lg:grid-cols-17">
            @for ($n = 1; $n <= 17; $n++)
                @php
                    $dato = $porNumero[$n] ?? ['nombre' => 'ODS '.$n, 'valor' => 0];
                    $valor = $dato['valor'];
                    // Paso 0 reservado para "sin proyectos": el vacío no es un
                    // valor bajo, es otra categoría.
                    $paso = $valor === 0 ? 0 : max(1, (int) ceil($valor / $maximo * 5));
                @endphp
                <button
                    type="button"
                    @click="activo = (activo === {{ $n }} ? null : {{ $n }})"
                    :class="activo === {{ $n }} ? 'ring-2 ring-amber-400 ring-offset-1 dark:ring-offset-slate-900' : ''"
                    class="flex aspect-square flex-col items-center justify-center rounded-lg transition hover:brightness-110 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-amber-400 {{ $pasos[$paso] }}"
                    title="ODS {{ $n }} · {{ $dato['nombre'] }} — {{ $valor }} {{ $valor === 1 ? 'proyecto' : 'proyectos' }}"
                >
                    <span class="text-[13px] font-black leading-none">{{ $n }}</span>
                    <span class="mt-0.5 text-[10px] font-bold leading-none tabular-nums opacity-80">{{ $valor }}</span>
                </button>
            @endfor
        </div>

        {{--
            Detalle en línea del objetivo elegido: se abre aquí y empuja lo de
            abajo. Lista los proyectos que lo declaran, con enlace a cada uno.
        --}}
        @for ($n = 1; $n <= 17; $n++)
            @php
                $dato = $porNumero[$n] ?? ['nombre' => 'ODS '.$n, 'valor' => 0, 'proyectos' => []];
                $proyectos = $dato['proyectos'] ?? [];
            @endphp
            <div x-show="activo === {{ $n }}" x-collapse x-cloak>
                <div class="mt-3 rounded-xl bg-slate-50 p-3 dark:bg-slate-800">
                    <div class="flex items-start gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-sm font-black {{ $pasos[$dato['valor'] === 0 ? 0 : max(1, (int) ceil($dato['valor'] / $maximo * 5))] }}">
                            {{ $n }}
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-xs font-bold text-slate-900 dark:text-white">{{ $dato['nombre'] }}</span>
                            <span class="mt-0.5 block text-[11px] text-slate-600 dark:text-slate-300">
                                @if ($dato['valor'] === 0)
                                    Ningún proyecto declara este objetivo todavía.
                                @else
                                    {{ $dato['valor'] }} {{ $dato['valor'] === 1 ? 'proyecto lo declara' : 'proyectos lo declaran' }}.
                                @endif
                            </span>
                        </span>
                    </div>

                    @if (! empty($proyectos))
                        <ul class="mt-2 divide-y divide-slate-200 border-t border-slate-200 pt-1 dark:divide-slate-700 dark:border-slate-700">
                            @foreach ($proyectos as $proyecto)
                                <li>
                                    <a href="{{ route('historialproyecto', $proyecto['id']) }}" wire:navigate
                                        class="group flex items-center gap-2 py-1.5 text-[11px] transition hover:text-primary-600 dark:hover:text-primary-300">
                                        @if ($proyecto['codigo'] ?? null)
                                            <span class="shrink-0 font-mono text-slate-400">{{ $proyecto['codigo'] }}</span>
                                        @endif
                                        <span class="min-w-0 flex-1 truncate text-slate-700 dark:text-slate-200" title="{{ $proyecto['nombre'] }}">
                                            {{ $proyecto['nombre'] }}
                                        </span>
                                        @svg('heroicon-o-arrow-up-right', ['class' => 'h-3 w-3 shrink-0 text-slate-300 transition group-hover:text-primary-500 dark:text-slate-600'])
                                    </a>
                                </li>
                            @endforeach
                        </ul>

                        @if ($dato['valor'] > count($proyectos))
                            <p class="mt-2 text-[11px] text-slate-400 dark:text-slate-500">
                                y {{ $dato['valor'] - count($proyectos) }} más.
                            </p>
                        @endif
                    @endif
                </div>
            </div>
        @endfor

        {{-- Leyenda de escala: sin ella la intensidad no es interpretable. --}}
        <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-1.5 text-[11px] text-slate-500 dark:text-slate-400">
                <span>Menos</span>
                @foreach (array_slice($pasos, 1) as $paso)
                    <span class="h-2.5 w-4 rounded-sm {{ $paso }}"></span>
                @endforeach
                <span>Más</span>
            </div>
            <p class="text-[11px] text-slate-400 dark:text-slate-500">
                Proyectos por Objetivo de Desarrollo Sostenible · máximo {{ $maximo }}
            </p>
        </div>

        {{-- Los tres con más presencia, con nombre: el número por sí solo no dice cuál es. --}}
        @php
            $destacados = collect($porNumero)->sortByDesc('valor')->take(3);
        @endphp
        <ul class="mt-3 flex flex-wrap gap-x-4 gap-y-1 border-t border-slate-100 pt-3 dark:border-slate-800">
            @foreach ($destacados as $numero => $dato)
                <li class="text-[11px] text-slate-600 dark:text-slate-300">
                    <span class="font-bold text-slate-900 dark:text-white">{{ $numero }}.</span>
                    {{ $dato['nombre'] }}
                    <span class="font-bold tabular-nums">({{ $dato['valor'] }})</span>
                </li>
            @endforeach
        </ul>
    </div>
@endif
