@props([
    // PanelFormulariosService::detalle()
    'detalle' => null,
    // PanelFormulariosService::opcionesDetalle(): código => nombre
    'opciones' => [],
])

@if ($detalle)
    <div {{ $attributes->merge(['class' => 'space-y-5']) }}>
        {{--
            Un formulario a la vez: dibujar el recorrido de todos no cabe. Las
            etapas salen de su flujo configurado, así que un formulario nuevo
            se ve aquí sin tocar esta vista.
        --}}
        <div class="flex flex-wrap items-center gap-3">
            <label for="recorrido-formulario" class="text-[11px] font-bold uppercase tracking-[0.1em] text-slate-500 dark:text-slate-400">
                Formulario
            </label>
            <select id="recorrido-formulario" wire:change="verFormulario($event.target.value)"
                class="min-w-0 max-w-full rounded-xl border-slate-200 py-1.5 text-xs dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                @foreach ($opciones as $codigo => $nombre)
                    <option value="{{ $codigo }}" @selected($codigo === $detalle['codigo'])>{{ $nombre }}</option>
                @endforeach
            </select>
            <span class="text-xs text-slate-500 dark:text-slate-400">
                {{ $detalle['esperando'] === 1 ? '1 trámite espera' : number_format($detalle['esperando']).' trámites esperan' }} una decisión
            </span>
        </div>

        @if ($detalle['sin_flujo'])
            <x-dashboard.aviso tono="info"
                titulo="Este formulario no tiene un flujo activo configurado"
                mensaje="Se muestran las etapas en las que esperan sus trámites, sin el recorrido completo." />
        @endif

        @foreach ($detalle['procesos'] as $proceso)
            <section>
                <h3 class="mb-3 flex items-center gap-2 text-xs font-bold uppercase tracking-[0.14em] text-slate-600 dark:text-slate-300">
                    {{ $proceso['etiqueta'] }}
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold normal-case tracking-normal tabular-nums text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                        {{ number_format($proceso['esperando']) }} esperando
                    </span>
                </h3>

                @if (! empty($proceso['etapas']))
                    <x-dashboard.proceso-etapas :etapas="$proceso['etapas']" :total="max(1, $proceso['esperando'])" />
                @endif

                @if (! empty($proceso['otras']))
                    <p class="mt-3 flex flex-wrap items-center gap-1.5 text-[11px] text-slate-500 dark:text-slate-400">
                        <span title="Etapas retiradas del flujo o de un flujo anterior en las que todavía espera algún trámite">Fuera del flujo vigente:</span>
                        @foreach ($proceso['otras'] as $otra)
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                {{ $otra['etiqueta'] }} · {{ $otra['valor'] }}
                            </span>
                        @endforeach
                    </p>
                @endif
            </section>
        @endforeach

        @if (! empty($detalle['heredados']))
            {{-- Expedientes importados que aún siguen el recorrido anterior por cargos. --}}
            <section class="rounded-xl border border-amber-200 bg-amber-50/60 p-4 dark:border-amber-900 dark:bg-amber-950/40">
                <h3 class="text-xs font-bold text-amber-800 dark:text-amber-200">
                    Sin adaptar al flujo · {{ number_format(collect($detalle['heredados'])->sum('valor')) }}
                </h3>
                <p class="mt-1 text-[11px] text-amber-700 dark:text-amber-300">
                    Siguen el recorrido anterior por cargos. Aparecen en la bandeja de quien tenga el cargo y el rol;
                    al adaptarlos al flujo pasan a sus etapas.
                </p>
                <ul class="mt-3 flex flex-wrap gap-2">
                    @foreach ($detalle['heredados'] as $heredado)
                        <li class="rounded-lg bg-white px-2.5 py-1.5 text-xs dark:bg-slate-900"
                            title="El más antiguo lleva {{ $heredado['dias_maximo'] }} días">
                            <span class="font-semibold text-slate-800 dark:text-slate-100">{{ $heredado['etiqueta'] }}</span>
                            <span class="ml-1 tabular-nums text-slate-500 dark:text-slate-400">{{ $heredado['valor'] }} · hasta {{ $heredado['dias_maximo'] }} d</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @php
            $filas = collect($detalle['procesos'])
                ->flatMap(fn ($p) => collect($p['etapas'])->concat($p['otras'])
                    ->map(fn ($e) => $e + ['proceso' => $p['etiqueta']]))
                ->concat(collect($detalle['heredados'])->map(fn ($e) => $e + ['proceso' => 'Sin adaptar']))
                ->filter(fn ($e) => $e['valor'] > 0);
        @endphp

        @if ($filas->isNotEmpty())
            <details class="group border-t border-slate-100 pt-3 dark:border-slate-800">
                <summary class="inline-flex cursor-pointer list-none items-center gap-1.5 text-[11px] font-semibold text-slate-500 hover:text-primary-600 dark:text-slate-400 dark:hover:text-primary-300">
                    @svg('heroicon-o-table-cells', ['class' => 'h-3.5 w-3.5'])
                    Ver los datos en tabla
                </summary>
                <div class="mt-2 overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="text-[10px] uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <tr>
                                <th scope="col" class="py-1.5 pr-3 font-semibold">Proceso</th>
                                <th scope="col" class="py-1.5 pr-3 font-semibold">Etapa</th>
                                <th scope="col" class="py-1.5 pr-3 font-semibold">Rol</th>
                                <th scope="col" class="py-1.5 pr-3 text-right font-semibold">Esperando</th>
                                <th scope="col" class="py-1.5 pr-3 text-right font-semibold">Días de media</th>
                                <th scope="col" class="py-1.5 text-right font-semibold">Máximo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 dark:divide-slate-800 dark:text-slate-200">
                            @foreach ($filas as $fila)
                                <tr>
                                    <td class="py-1.5 pr-3">{{ $fila['proceso'] }}</td>
                                    <td class="py-1.5 pr-3">{{ $fila['etiqueta'] }}</td>
                                    <td class="py-1.5 pr-3 text-slate-500 dark:text-slate-400">{{ $fila['rol'] ?? '—' }}</td>
                                    <td class="py-1.5 pr-3 text-right tabular-nums">{{ $fila['valor'] }}</td>
                                    <td class="py-1.5 pr-3 text-right tabular-nums">{{ $fila['dias_promedio'] }}</td>
                                    <td class="py-1.5 text-right tabular-nums">{{ $fila['dias_maximo'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
        @endif
    </div>
@endif
