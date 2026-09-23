@props([
    // PanelFormulariosService::atascos()
    'items' => [],
    'vacio' => 'Ningún trámite está esperando en este momento.',
])

@if (empty($items))
    <x-dashboard.estado-vacio :titulo="$vacio" icono="heroicon-o-check-circle" tono="exito" />
@else
    {{--
        Etapas, no expedientes: dice dónde se acumula el trabajo en todos los
        formularios a la vez. Cada fila nombra su formulario porque dos flujos
        pueden tener etapas con el mismo nombre.
    --}}
    <ul {{ $attributes->merge(['class' => 'divide-y divide-slate-100 dark:divide-slate-800']) }}>
        @foreach ($items as $item)
            @php
                $dias = (int) $item['dias_maximo'];
                // Mismo semáforo que la lista de pendientes; el color nunca va solo.
                [$tonoDias, $iconoDias] = match (true) {
                    $dias >= 15 => ['bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300', 'heroicon-o-exclamation-triangle'],
                    $dias >= 7 => ['bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300', 'heroicon-o-clock'],
                    default => ['bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300', 'heroicon-o-clock'],
                };
                $proceso = \App\Support\Dashboard\Formularios\FormularioPanel::PROCESOS[$item['proceso']] ?? $item['proceso'];
            @endphp

            <li>
                <button type="button" wire:click="verFormulario('{{ $item['codigo'] }}')"
                    class="flex w-full items-start gap-3 px-5 py-3 text-left transition hover:bg-slate-50 dark:hover:bg-slate-800/60"
                    title="Ver el recorrido de {{ $item['codigo'] }}">
                    <span class="flex h-8 min-w-[2rem] shrink-0 items-center justify-center rounded-lg bg-sky-50 px-1.5 text-sm font-black tabular-nums text-sky-700 dark:bg-sky-950 dark:text-sky-300">
                        {{ $item['tramites'] }}
                    </span>

                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-semibold text-slate-900 dark:text-white">{{ $item['etapa'] }}</span>
                        <span class="mt-0.5 flex flex-wrap items-center gap-1.5 text-[11px] text-slate-500 dark:text-slate-400">
                            <span class="font-mono">{{ $item['codigo'] }}</span>
                            <span>· {{ $proceso }}</span>
                            @if ($item['rol'])
                                <span>· {{ $item['rol'] }}</span>
                            @endif
                            @if ($item['heredado'])
                                <span class="rounded-full bg-amber-50 px-1.5 py-0.5 font-semibold text-amber-700 dark:bg-amber-950 dark:text-amber-300">sin adaptar</span>
                            @endif
                        </span>
                    </span>

                    <span class="inline-flex shrink-0 items-center gap-1 rounded-full px-2 py-1 text-[11px] font-bold {{ $tonoDias }}"
                        title="De media {{ $item['dias_promedio'] }} días; el más antiguo, {{ $dias }}">
                        @svg($iconoDias, ['class' => 'h-3.5 w-3.5'])
                        hasta {{ $dias === 1 ? '1 día' : $dias.' días' }}
                    </span>
                </button>
            </li>
        @endforeach
    </ul>
@endif
