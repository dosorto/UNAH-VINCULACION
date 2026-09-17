@props([
    // Debe ser único en la página: panel-charts.js indexa las instancias por él.
    'id',
    'tipo' => 'bar',
    // [['name' => 'Proyectos', 'data' => [1, 2, 3]], ...]
    'series' => [],
    'categorias' => [],
    // ['2025' => ['Proyecto A', 'Proyecto B'], ...] — se lista en el tooltip.
    'detalle' => [],
    'alto' => 320,
    'sufijo' => 'proyectos',
    'apilado' => false,
])

{{--
    Punto de montaje de un gráfico. No lleva <script>: lo instancia
    resources/js/panel-charts.js al arrancar y en cada livewire:navigated.

    wire:ignore es obligatorio — sin él Livewire borraría el SVG que ApexCharts
    inyecta aquí en cada re-render del componente.

    `alto` es la altura del área de trazado; el contenedor crece por encima de
    ella para alojar las etiquetas del eje. Fijarla al alto total dejaba el eje
    fuera y le salía un scroll interno a la tarjeta.
--}}
<div
    id="{{ $id }}"
    wire:ignore
    data-nexo-chart="{{ $tipo }}"
    data-nexo-chart-config="{{ json_encode([
        'tipo' => $tipo,
        'series' => $series,
        'categorias' => $categorias,
        'detalle' => $detalle,
        'alto' => $alto,
        'sufijo' => $sufijo,
        'apilado' => $apilado,
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) }}"
    style="min-height: {{ $alto + 40 }}px"
    {{ $attributes }}
></div>

@if (! empty($categorias) && ! empty($series))
    @php
        $filasTabla = [];

        foreach ($categorias as $i => $categoria) {
            foreach ($series as $serie) {
                $filasTabla[] = [
                    'etiqueta' => count($series) > 1
                        ? $categoria.' · '.($serie['name'] ?? '')
                        : $categoria,
                    'valor' => $serie['data'][$i] ?? 0,
                ];
            }
        }
    @endphp

    <x-dashboard.tabla-datos :items="$filasTabla" columna-etiqueta="Periodo" :columna-valor="ucfirst($sufijo)" />
@endif
