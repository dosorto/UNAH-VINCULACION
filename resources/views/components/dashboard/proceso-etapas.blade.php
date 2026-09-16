@props([
    // [['etiqueta'=>..,'valor'=>n,'tono'=>'neutro|info|riesgo|exito|acento','href'=>null], ...]
    'etapas' => [],
    'total' => 0,
])

@php
    $totalReal = $total > 0 ? $total : max(1, array_sum(array_map(fn ($e) => (int) ($e['valor'] ?? 0), $etapas)));
    $maximo = max(1, max(array_map(fn ($e) => (int) ($e['valor'] ?? 0), $etapas) ?: [0]));
@endphp

{{--
    El expediente recorre estas etapas en orden, así que la forma tiene que
    llevar dirección: nodos encadenados, no tarjetas sueltas ni una barra por
    fila. El disco crece con la carga de la etapa; la línea que las une dice que
    pertenecen al mismo camino.
--}}
<ol {{ $attributes->merge(['class' => 'flex flex-col gap-4 sm:flex-row sm:items-start sm:gap-0']) }}>
    @foreach ($etapas as $i => $etapa)
        @php
            $valor = (int) ($etapa['valor'] ?? 0);
            $cuota = round($valor / $totalReal * 100);
            // El disco escala con la raíz del valor: el área es lo que se
            // percibe, y con el diámetro los extremos se disparan.
            $escala = $maximo > 0 ? sqrt($valor / $maximo) : 0;
            $lado = (int) round(44 + $escala * 28);

            [$relleno, $borde, $texto] = match ($etapa['tono'] ?? 'neutro') {
                'info' => ['bg-[#1f5397] dark:bg-[#4a86d8]', 'ring-[#1f5397]/20 dark:ring-[#4a86d8]/30', 'text-white'],
                'exito' => ['bg-emerald-600 dark:bg-emerald-500', 'ring-emerald-600/20 dark:ring-emerald-400/30', 'text-white'],
                'riesgo' => ['bg-red-600 dark:bg-red-500', 'ring-red-600/20 dark:ring-red-400/30', 'text-white'],
                'acento' => ['bg-[#a77a00] dark:bg-[#bd8a17]', 'ring-[#a77a00]/20 dark:ring-[#bd8a17]/30', 'text-white'],
                default => ['bg-slate-300 dark:bg-slate-600', 'ring-slate-300/30 dark:ring-slate-600/30', 'text-slate-700 dark:text-white'],
            };
            $interactivo = ($etapa['href'] ?? null) !== null;
        @endphp

        <li class="flex min-w-0 flex-1 items-center gap-3 sm:flex-col sm:gap-0">
            <span class="flex items-center sm:w-full sm:justify-center">
                {{-- Tramo de conexión con la etapa anterior. --}}
                @if ($i > 0)
                    <span class="hidden h-px flex-1 bg-slate-200 dark:bg-slate-700 sm:block" aria-hidden="true"></span>
                @else
                    <span class="hidden flex-1 sm:block" aria-hidden="true"></span>
                @endif

                <{{ $interactivo ? 'a' : 'span' }}
                    @if ($interactivo) href="{{ $etapa['href'] }}" wire:navigate @endif
                    class="flex shrink-0 flex-col items-center justify-center rounded-full ring-4 {{ $relleno }} {{ $borde }} {{ $interactivo ? 'transition hover:scale-105' : '' }}"
                    style="width: {{ $lado }}px; height: {{ $lado }}px"
                    title="{{ $etapa['etiqueta'] }}: {{ number_format($valor) }} ({{ $cuota }}%)"
                >
                    <span class="text-base font-black leading-none {{ $texto }}">{{ number_format($valor) }}</span>
                    <span class="text-[9px] font-bold leading-none {{ $texto }} opacity-70">{{ $cuota }}%</span>
                </{{ $interactivo ? 'a' : 'span' }}>

                @if ($i < count($etapas) - 1)
                    <span class="hidden h-px flex-1 bg-slate-200 dark:bg-slate-700 sm:block" aria-hidden="true"></span>
                @else
                    <span class="hidden flex-1 sm:block" aria-hidden="true"></span>
                @endif
            </span>

            <span class="min-w-0 sm:mt-2 sm:w-full sm:px-1 sm:text-center">
                <span class="block truncate text-[11px] font-bold uppercase tracking-[0.08em] text-slate-600 dark:text-slate-300">
                    {{ $etapa['etiqueta'] }}
                </span>
            </span>
        </li>
    @endforeach
</ol>
