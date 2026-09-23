@props([
    // PanelFormulariosService::resumen()
    'resumen' => ['total' => 0, 'conteos' => []],
    // PanelFormulariosService::matriz()
    'matriz' => [],
    // Código del formulario abierto en el detalle, para resaltar su fila.
    'seleccionado' => null,
])

@use('App\Support\Dashboard\EstadoGeneral')

@php
    $conteos = $resumen['conteos'] + EstadoGeneral::vacio();
    // «Otros» solo ocupa columna si hay algo que contar en ella.
    $columnas = collect(EstadoGeneral::CLAVES)
        ->reject(fn (string $clave) => $clave === EstadoGeneral::OTROS && $conteos[$clave] === 0)
        ->values();

    $tarjetas = [
        EstadoGeneral::BORRADOR => ['heroicon-o-pencil-square', 'neutro'],
        EstadoGeneral::EN_REVISION => ['heroicon-o-clipboard-document-list', 'info'],
        EstadoGeneral::SUBSANACION => ['heroicon-o-arrow-uturn-left', 'alerta'],
        EstadoGeneral::APROBADO => ['heroicon-o-check-circle', 'exito'],
        EstadoGeneral::FINALIZADO => ['heroicon-o-check-badge', 'acento'],
        EstadoGeneral::OTROS => ['heroicon-o-archive-box', 'neutro'],
    ];

    // Las clases van literales: Tailwind analiza las vistas, no cadenas armadas.
    $tinta = fn (string $clave, int $valor) => match (true) {
        $valor === 0 => 'text-slate-300 dark:text-slate-600',
        $clave === EstadoGeneral::EN_REVISION => 'font-bold text-sky-700 dark:text-sky-300',
        $clave === EstadoGeneral::SUBSANACION => 'font-bold text-amber-700 dark:text-amber-300',
        default => 'text-slate-700 dark:text-slate-200',
    };
@endphp

@if (empty($matriz))
    <x-dashboard.estado-vacio titulo="Todavía no hay trámites registrados." icono="heroicon-o-inbox" />
@else
    <div {{ $attributes->merge(['class' => 'space-y-5']) }}>
        {{--
            Todos los formularios reducidos a los mismos cinco estados: con más
            de treinta formularios, cada uno con su flujo, es la única lectura
            que se puede comparar de un vistazo.
        --}}
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 {{ $columnas->count() > 5 ? 'lg:grid-cols-6' : 'lg:grid-cols-5' }}">
            @foreach ($columnas as $clave)
                <x-dashboard.kpi :etiqueta="EstadoGeneral::ETIQUETAS[$clave]" :valor="$conteos[$clave]"
                    :icono="$tarjetas[$clave][0]" :tono="$tarjetas[$clave][1]" />
            @endforeach
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-100 dark:border-slate-800">
            <table class="w-full min-w-[720px] text-left text-xs">
                <caption class="sr-only">Trámites por formulario y estado general</caption>
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                    <tr>
                        <th scope="col" class="px-4 py-2.5 font-semibold">Formulario</th>
                        @foreach ($columnas as $clave)
                            <th scope="col" class="px-3 py-2.5 text-right font-semibold" title="{{ EstadoGeneral::AYUDAS[$clave] }}">{{ EstadoGeneral::ETIQUETAS[$clave] }}</th>
                        @endforeach
                        <th scope="col" class="px-3 py-2.5 text-right font-semibold">Total</th>
                        <th scope="col" class="px-4 py-2.5 text-right font-semibold" title="Trámites que esperan la decisión de alguien ahora, incluidos sus informes">Esperando</th>
                    </tr>
                </thead>

                @foreach ($matriz as $grupo)
                    @if (count($grupo['formularios']) === 1)
                        {{-- Un tipo de acción con un solo formulario no necesita fila de grupo. --}}
                        @php $formulario = $grupo['formularios'][0]; @endphp
                        <tbody class="border-t border-slate-100 dark:border-slate-800">
                            <tr wire:key="matriz-{{ $formulario['codigo'] }}"
                                class="{{ $seleccionado === $formulario['codigo'] ? 'bg-primary-50 dark:bg-primary-900' : 'bg-white dark:bg-slate-900' }}">
                                <th scope="row" class="px-4 py-2.5 text-left">
                                    <button type="button" wire:click="verFormulario('{{ $formulario['codigo'] }}')"
                                        class="text-left hover:text-primary-600 dark:hover:text-primary-300"
                                        title="Ver dónde espera cada trámite de este formulario">
                                        <span class="block text-xs font-bold text-slate-900 hover:underline dark:text-white">{{ $grupo['etiqueta'] }}</span>
                                        <span class="font-mono text-[11px] font-normal text-slate-400">{{ $formulario['codigo'] }}</span>
                                    </button>
                                    @if ($formulario['ambito_parcial'])
                                        <span class="ml-1 inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700 dark:bg-amber-950 dark:text-amber-300"
                                            title="Este formulario no guarda la unidad académica como dato enlazado, así que la cifra es de toda la UNAH.">
                                            cifra institucional
                                        </span>
                                    @endif
                                </th>
                                @foreach ($columnas as $clave)
                                    <td class="px-3 py-2.5 text-right tabular-nums {{ $tinta($clave, $formulario['conteos'][$clave]) }}">
                                        {{ $formulario['conteos'][$clave] > 0 ? number_format($formulario['conteos'][$clave]) : '—' }}
                                    </td>
                                @endforeach
                                <td class="px-3 py-2.5 text-right font-bold tabular-nums text-slate-900 dark:text-white">{{ number_format($formulario['total']) }}</td>
                                <td class="px-4 py-2.5 text-right tabular-nums {{ $formulario['esperando'] > 0 ? 'font-bold text-sky-700 dark:text-sky-300' : 'text-slate-300 dark:text-slate-600' }}">
                                    {{ $formulario['esperando'] > 0 ? number_format($formulario['esperando']) : '—' }}
                                </td>
                            </tr>
                        </tbody>
                        @continue
                    @endif

                    {{-- Agrupados por tipo de acción; se abren solos si son pocos. --}}
                    <tbody x-data="{ abierto: {{ count($matriz) <= 3 ? 'true' : 'false' }} }"
                        class="border-t border-slate-100 dark:border-slate-800">
                        <tr class="bg-white dark:bg-slate-900">
                            <th scope="rowgroup" class="px-4 py-2.5 text-left">
                                <button type="button" @click="abierto = ! abierto" :aria-expanded="abierto"
                                    class="flex items-center gap-1.5 text-left text-xs font-bold text-slate-900 hover:text-primary-600 dark:text-white dark:hover:text-primary-300">
                                    <span class="transition" :class="abierto ? 'rotate-90' : ''">
                                        @svg('heroicon-o-chevron-right', ['class' => 'h-3.5 w-3.5 text-slate-400'])
                                    </span>
                                    {{ $grupo['etiqueta'] }}
                                    <span class="font-normal text-slate-400">({{ count($grupo['formularios']) }})</span>
                                </button>
                            </th>
                            @foreach ($columnas as $clave)
                                <td class="px-3 py-2.5 text-right tabular-nums {{ $tinta($clave, $grupo['conteos'][$clave]) }}">
                                    {{ $grupo['conteos'][$clave] > 0 ? number_format($grupo['conteos'][$clave]) : '—' }}
                                </td>
                            @endforeach
                            <td class="px-3 py-2.5 text-right font-bold tabular-nums text-slate-900 dark:text-white">{{ number_format($grupo['total']) }}</td>
                            <td class="px-4 py-2.5 text-right tabular-nums {{ $grupo['esperando'] > 0 ? 'font-bold text-sky-700 dark:text-sky-300' : 'text-slate-300 dark:text-slate-600' }}">
                                {{ $grupo['esperando'] > 0 ? number_format($grupo['esperando']) : '—' }}
                            </td>
                        </tr>

                        @foreach ($grupo['formularios'] as $formulario)
                            <tr x-show="abierto" x-cloak wire:key="matriz-{{ $formulario['codigo'] }}"
                                class="{{ $seleccionado === $formulario['codigo'] ? 'bg-primary-50 dark:bg-primary-900' : 'bg-slate-50/60 dark:bg-slate-800/30' }}">
                                <th scope="row" class="py-2 pl-10 pr-4 text-left font-normal">
                                    <button type="button" wire:click="verFormulario('{{ $formulario['codigo'] }}')"
                                        class="text-left text-slate-700 hover:text-primary-600 hover:underline dark:text-slate-200 dark:hover:text-primary-300"
                                        title="Ver dónde espera cada trámite de este formulario">
                                        <span class="font-mono text-[11px] text-slate-400">{{ $formulario['codigo'] }}</span>
                                        · {{ $formulario['nombre'] }}
                                    </button>
                                    @if ($formulario['ambito_parcial'])
                                        {{-- No sabe acotarse a la unidad: decirlo evita leer la cifra como del centro. --}}
                                        <span class="ml-1 inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700 dark:bg-amber-950 dark:text-amber-300"
                                            title="Este formulario no guarda la unidad académica como dato enlazado, así que la cifra es de toda la UNAH.">
                                            cifra institucional
                                        </span>
                                    @endif
                                </th>
                                @foreach ($columnas as $clave)
                                    <td class="px-3 py-2 text-right tabular-nums {{ $tinta($clave, $formulario['conteos'][$clave]) }}">
                                        {{ $formulario['conteos'][$clave] > 0 ? number_format($formulario['conteos'][$clave]) : '—' }}
                                    </td>
                                @endforeach
                                <td class="px-3 py-2 text-right tabular-nums text-slate-700 dark:text-slate-200">{{ number_format($formulario['total']) }}</td>
                                <td class="px-4 py-2 text-right tabular-nums {{ $formulario['esperando'] > 0 ? 'font-bold text-sky-700 dark:text-sky-300' : 'text-slate-300 dark:text-slate-600' }}">
                                    {{ $formulario['esperando'] > 0 ? number_format($formulario['esperando']) : '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                @endforeach
            </table>
        </div>

        <p class="text-[11px] text-slate-500 dark:text-slate-400">
            Cada formulario traduce sus estados a estos cinco. «Aprobado» incluye los proyectos
            registrados{{ $columnas->contains(EstadoGeneral::OTROS) ? ' y «Otros», los cancelados o anteriores al sistema' : '' }}.
            «Esperando» cuenta los trámites que esperan una decisión ahora, incluidos sus informes;
            pulsa un formulario para ver en qué etapa.
        </p>
    </div>
@endif
