@props([
    // [['etiqueta'=>..,'valor'=>días,'firmas'=>n,'porcentaje'=>0-100], ...] en orden del flujo
    'etapas' => [],
    // PanelEstadisticoService::detenidosPorEtapa(): etapa => proyectos/días esperando ahora
    'esperando' => [],
    'vacio' => 'Todavía no hay revisiones resueltas para medir tiempos.',
])

@php
    $maximo = max(1, max(array_map(fn ($e) => (float) $e['valor'], $etapas) ?: [0]));
    $totalEsperando = array_sum(array_column($esperando, 'proyectos'));
    // Con menos revisiones resueltas la media no dice nada: dos firmas del
    // mismo día pintaban «0 días» en verde como si la etapa fuera ágil.
    $minimoMuestras = 3;
    $hayPocosDatos = collect($etapas)->contains(fn ($e) => (int) ($e['firmas'] ?? $minimoMuestras) < $minimoMuestras);
@endphp

@if (empty($etapas))
    <x-dashboard.estado-vacio :titulo="$vacio" icono="heroicon-o-clock" />
@else
    <div {{ $attributes }}>
        {{--
            Dos lecturas en el mismo sitio: la columna es lo que esa etapa TARDÓ
            de media en revisiones ya resueltas, y el punto de abajo cuántos
            proyectos están esperándola ahora mismo. Separadas obligaban a
            cruzarlas a ojo.
        --}}
        <div class="flex items-end gap-1.5" style="height: 140px">
            @foreach ($etapas as $etapa)
                @php
                    $dias = (float) $etapa['valor'];
                    $cola = $esperando[mb_strtolower(trim((string) $etapa['etiqueta']))] ?? null;
                    $enCola = $cola['proyectos'] ?? 0;
                    $altura = max(4, $dias / $maximo * 100);

                    $firmas = (int) ($etapa['firmas'] ?? $minimoMuestras);
                    $pocosDatos = $firmas < $minimoMuestras;

                    [$relleno, $tinta] = match (true) {
                        $pocosDatos => ['bg-slate-300 dark:bg-slate-600', 'text-slate-500 dark:text-slate-400'],
                        $dias >= 30 => ['bg-red-500 dark:bg-red-400', 'text-red-700 dark:text-red-300'],
                        $dias >= 14 => ['bg-amber-500 dark:bg-amber-400', 'text-amber-700 dark:text-amber-300'],
                        default => ['bg-emerald-500 dark:bg-emerald-400', 'text-emerald-700 dark:text-emerald-300'],
                    };

                    $titulo = $etapa['etiqueta'].' — tarda '.number_format($dias, 1).' días de media';
                    $titulo .= $pocosDatos
                        ? ' (solo '.$firmas.' '.($firmas === 1 ? 'revisión resuelta' : 'revisiones resueltas').': aún no es representativo)'
                        : '';
                    $titulo .= $enCola > 0
                        ? '. Ahora hay '.$enCola.' '.($enCola === 1 ? 'proyecto esperando' : 'proyectos esperando')
                            .', el más antiguo desde hace '.($cola['dias_maximo'] ?? 0).' días.'
                        : '. Ahora mismo no hay ninguno esperando.';
                @endphp

                <div class="flex h-full min-w-0 flex-1 flex-col justify-end" title="{{ $titulo }}">
                    <span class="mb-1 text-center text-[10px] font-bold tabular-nums {{ $tinta }}">{{ number_format($dias, 0) }}d</span>
                    <span class="w-full rounded-t-[4px] {{ $relleno }}" style="height: {{ $altura }}%"></span>
                </div>
            @endforeach
        </div>

        {{-- Eje: nombre de la etapa y cuántos esperan en ella. --}}
        <div class="mt-2 flex gap-1.5 border-t border-slate-200 pt-2 dark:border-slate-700">
            @foreach ($etapas as $etapa)
                @php
                    $cola = $esperando[mb_strtolower(trim((string) $etapa['etiqueta']))] ?? null;
                    $enCola = $cola['proyectos'] ?? 0;
                @endphp
                <div class="min-w-0 flex-1 text-center">
                    <span class="block text-[10px] leading-tight text-slate-500 dark:text-slate-400" title="{{ $etapa['etiqueta'] }}">
                        {{ \Illuminate\Support\Str::limit($etapa['etiqueta'], 13, '…') }}
                    </span>
                    @if ($enCola > 0)
                        <span class="mt-1 inline-flex items-center gap-0.5 rounded-full bg-red-50 px-1.5 py-0.5 text-[10px] font-bold tabular-nums text-red-700 dark:bg-red-950 dark:text-red-300">
                            {{ $enCola }} <span class="font-normal">en cola</span>
                        </span>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- La leyenda dice qué significa cada cosa, sin jerga. --}}
        <div class="mt-3 space-y-1.5 border-t border-slate-100 pt-3 dark:border-slate-800">
            <p class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-500 dark:text-slate-400">
                <span class="font-semibold text-slate-600 dark:text-slate-300">Altura:</span>
                días que tardó esa etapa de media
                <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>hasta 14</span>
                <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-amber-500"></span>14 a 30</span>
                <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-red-500"></span>más de 30</span>
                @if ($hayPocosDatos)
                    <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-slate-300 dark:bg-slate-600"></span>menos de {{ $minimoMuestras }} revisiones: poco representativo</span>
                @endif
            </p>
            <p class="text-[11px] text-slate-500 dark:text-slate-400">
                <span class="font-semibold text-slate-600 dark:text-slate-300">En cola:</span>
                proyectos parados hoy en esa etapa, esperando a que alguien los revise.
                @if ($totalEsperando > 0)
                    Ahora hay <span class="font-bold text-red-700 dark:text-red-300">{{ $totalEsperando }}</span> en total.
                @endif
            </p>
        </div>

        <x-dashboard.tabla-datos :items="$etapas" columna-etiqueta="Etapa" columna-valor="Días de media" :decimales="1" />
    </div>
@endif
