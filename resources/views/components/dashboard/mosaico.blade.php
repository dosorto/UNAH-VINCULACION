@props([
    // [['etiqueta'=>..,'valor'=>n,'detalle'=>?string], ...] ordenado desc
    'items' => [],
    'unidad' => 'proyectos',
    'filas' => 2,
    'vacio' => 'Todavía no hay datos.',
    'alto' => 200,
])

@php
    $conValor = array_values(array_filter($items, fn ($i) => (int) ($i['valor'] ?? 0) > 0));
    $total = array_sum(array_map(fn ($i) => (int) $i['valor'], $conValor));

    // Reparto en franjas: se acumulan celdas hasta cubrir la cuota de la franja,
    // de modo que el área de cada rectángulo —no su largo— sea proporcional al
    // valor. Es lo que distingue un mosaico de un ranking: no hay un carril
    // común contra el que se compita, sino un total que se reparte.
    $franjas = [];
    $cuota = $total > 0 ? $total / max(1, $filas) : 0;
    $actual = [];
    $sumaActual = 0;

    foreach ($conValor as $indice => $item) {
        $actual[] = $item;
        $sumaActual += (int) $item['valor'];
        $quedan = count($conValor) - $indice - 1;
        $franjasRestantes = $filas - count($franjas) - 1;

        if ($sumaActual >= $cuota && $franjasRestantes > 0 && $quedan >= $franjasRestantes) {
            $franjas[] = ['items' => $actual, 'suma' => $sumaActual];
            $actual = [];
            $sumaActual = 0;
        }
    }

    if ($actual !== []) {
        $franjas[] = ['items' => $actual, 'suma' => $sumaActual];
    }

    // Seis pasos de la rampa institucional; el tono acompaña a la magnitud.
    $tonos = [
        'bg-[#123a6e] text-white',
        'bg-[#1f5397] text-white',
        'bg-[#2f6bb5] text-white',
        'bg-[#4a86d8] text-white',
        'bg-[#6b9fdd] text-[#0b2242]',
        'bg-[#8fb5e3] text-[#0b2242]',
    ];
    $maximo = max(1, max(array_map(fn ($i) => (int) $i['valor'], $conValor) ?: [0]));
@endphp

@if ($conValor === [])
    <x-dashboard.estado-vacio :titulo="$vacio" icono="heroicon-o-squares-2x2" />
@else
    <div {{ $attributes }} x-data="{ activo: null }">
        <div class="flex flex-col gap-1" style="height: {{ $alto }}px">
            @foreach ($franjas as $iFranja => $franja)
                @php $altoFranja = $total > 0 ? $franja['suma'] / $total * 100 : 0; @endphp
                <div class="flex min-h-0 gap-1" style="height: {{ max(18, $altoFranja) }}%">
                    @foreach ($franja['items'] as $item)
                        @php
                            $valor = (int) $item['valor'];
                            $ancho = $franja['suma'] > 0 ? $valor / $franja['suma'] * 100 : 0;
                            $paso = (int) min(5, floor((1 - $valor / $maximo) * 6));
                            $clave = $iFranja.'-'.$loop->index;
                            $cuotaTotal = $total > 0 ? round($valor / $total * 100) : 0;
                        @endphp
                        <button
                            type="button"
                            @click="activo = (activo === '{{ $clave }}' ? null : '{{ $clave }}')"
                            :class="activo === '{{ $clave }}' ? 'ring-2 ring-amber-400' : ''"
                            class="flex min-w-0 flex-col justify-between overflow-hidden rounded-lg p-2 text-left transition hover:brightness-110 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-amber-400 {{ $tonos[$paso] }}"
                            style="width: {{ max(6, $ancho) }}%"
                            title="{{ $item['etiqueta'] }} — {{ number_format($valor) }} {{ $unidad }} ({{ $cuotaTotal }}%)"
                        >
                            {{-- La etiqueta solo se escribe dentro si cabe; si no, vive en el detalle. --}}
                            <span class="line-clamp-2 text-[11px] font-bold leading-tight">{{ $item['etiqueta'] }}</span>
                            <span class="text-sm font-black leading-none">{{ number_format($valor) }}</span>
                        </button>
                    @endforeach
                </div>
            @endforeach
        </div>

        {{--
            Detalle en línea: se abre bajo el mosaico y empuja lo que sigue.
            Lista los proyectos que forman el bloque y enlaza a cada uno —saber
            que son cinco sin poder ver cuáles obliga a salir del panel a
            buscarlos a mano.
        --}}
        @foreach ($franjas as $iFranja => $franja)
            @foreach ($franja['items'] as $iItem => $item)
                @php
                    $clave = $iFranja.'-'.$iItem;
                    $proyectos = $item['proyectos'] ?? [];
                    $valor = (int) $item['valor'];
                @endphp
                <div x-show="activo === '{{ $clave }}'" x-collapse x-cloak>
                    <div class="mt-3 rounded-xl bg-slate-50 p-3 dark:bg-slate-800">
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <p class="text-xs font-bold text-slate-900 dark:text-white">{{ $item['etiqueta'] }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                {{ number_format($valor) }} {{ $unidad }}
                                · {{ $total > 0 ? round($valor / $total * 100, 1) : 0 }}% del total
                            </p>
                        </div>

                        @if (! empty($proyectos))
                            <ul class="mt-2 divide-y divide-slate-200 dark:divide-slate-700">
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

                            @if ($valor > count($proyectos))
                                <p class="mt-2 text-[11px] text-slate-400 dark:text-slate-500">
                                    y {{ number_format($valor - count($proyectos)) }} más.
                                </p>
                            @endif
                        @elseif ($item['detalle'] ?? null)
                            <p class="mt-1 text-[11px] text-slate-600 dark:text-slate-300">{{ $item['detalle'] }}</p>
                        @endif
                    </div>
                </div>
            @endforeach
        @endforeach

        <p class="mt-3 text-[11px] text-slate-400 dark:text-slate-500">
            El área de cada bloque es su parte del total ({{ number_format($total) }} {{ $unidad }}). Pulsa uno para ver el detalle.
        </p>

        <x-dashboard.tabla-datos :items="$conValor" :columna-valor="ucfirst($unidad)" />
    </div>
@endif
