import ApexCharts from 'apexcharts';

/**
 * Gráficos del panel estadístico.
 *
 * Sustituye a los <script> inline que había en dashboard.blade.php,
 * dasboard-docente.blade.php y dashboard-director.blade.php. Aquellos:
 *
 *   - se inicializaban solo en DOMContentLoaded, así que al navegar con
 *     wire:navigate (activo en todo el sidebar) el contenedor quedaba vacío
 *     hasta recargar con F5;
 *   - nunca destruían la instancia, dejando gráficos huérfanos en memoria;
 *   - compartían la global window.projectsChart entre el panel admin y el del
 *     docente, así que el segundo pisaba al primero;
 *   - pintaban los ejes con clases de Tailwind escritas dentro del <script>,
 *     que el extractor de Tailwind no ve y por tanto no genera.
 *
 * Aquí cada gráfico se declara con <div data-nexo-chart data-nexo-chart-config>
 * (ver el componente x-dashboard.grafico-apex) y se indexa por el id del nodo.
 */

/** id del nodo -> { chart, detalle } */
const registro = new Map();

const esOscuro = () => document.documentElement.classList.contains('dark');

/**
 * Paleta categórica institucional (azul UNAH, dorado UNAH, teal).
 *
 * Los dos juegos están validados con el verificador de la guía de
 * visualización sobre las superficies reales del panel (#ffffff en claro,
 * slate-900 en oscuro): banda de luminosidad, suelo de croma, separación para
 * daltonismo y contraste. El juego oscuro son los mismos tres tonos re-escalados
 * para su fondo, no una paleta distinta.
 *
 * Si se cambia un tono hay que volver a pasar el verificador: la separación
 * entre dorado y teal es la más ajustada del conjunto.
 */
function paleta() {
    return esOscuro()
        ? {
            texto: '#94a3b8',
            grid: '#334155',
            series: ['#4a86d8', '#bd8a17', '#14a08a'],
        }
        : {
            texto: '#64748b',
            grid: '#e2e8f0',
            series: ['#1f5397', '#a77a00', '#0d9488'],
        };
}

/** Opciones que dependen del tema; se reaplican al alternar claro/oscuro. */
function opcionesTema() {
    const { texto, grid, series } = paleta();
    const estiloEtiqueta = { colors: texto, fontFamily: 'inherit', fontSize: '12px' };

    return {
        colors: series,
        theme: { mode: esOscuro() ? 'dark' : 'light' },
        tooltip: { theme: esOscuro() ? 'dark' : 'light' },
        // Rejilla de un solo píxel y continua: las líneas discontinuas compiten
        // visualmente con los datos.
        grid: { borderColor: grid, strokeDashArray: 0, padding: { left: 4, right: 4, top: -12 } },
        xaxis: { labels: { style: estiloEtiqueta } },
        yaxis: { labels: { style: estiloEtiqueta } },
        legend: { labels: { colors: texto } },
    };
}

function tooltipPersonalizado(id, sufijo) {
    return ({ series, seriesIndex, dataPointIndex, w }) => {
        const etiqueta = w.globals.labels[dataPointIndex];
        const detalle = registro.get(id)?.detalle ?? {};
        const items = detalle[etiqueta] ?? [];

        const lista = items.length
            ? `<ul class="mt-2 list-disc list-inside space-y-0.5">${items
                .map((texto) => `<li>${escapar(texto)}</li>`)
                .join('')}</ul>`
            : '';

        return `<div class="nexo-chart-tooltip">
            <strong class="nexo-chart-tooltip__titulo">${escapar(etiqueta)}</strong>
            <span class="nexo-chart-tooltip__valor">${series[seriesIndex][dataPointIndex]} ${escapar(sufijo)}</span>
            ${lista}
        </div>`;
    };
}

/** El detalle viene de la base de datos: hay que escaparlo antes de inyectarlo. */
function escapar(valor) {
    const div = document.createElement('div');
    div.textContent = String(valor ?? '');

    return div.innerHTML;
}

function opciones(config) {
    const {
        id,
        tipo = 'bar',
        series = [],
        categorias = [],
        alto = 320,
        sufijo = 'proyectos',
        apilado = false,
    } = config;

    const maximo = Math.max(0, ...series.flatMap((s) => s.data ?? []));

    return {
        ...opcionesTema(),
        series,
        chart: {
            id,
            type: tipo,
            height: alto,
            width: '100%',
            stacked: apilado,
            toolbar: { show: false },
            fontFamily: 'inherit',
            animations: { enabled: !window.matchMedia('(prefers-reduced-motion: reduce)').matches },
        },
        plotOptions: {
            bar: {
                horizontal: false,
                // Barra fina: el hueco del carril es aire, no espacio a llenar.
                columnWidth: '42%',
                maxWidth: 24,
                // Redondeada solo en el extremo del dato; cuadrada en la base.
                borderRadius: 4,
                borderRadiusApplication: 'end',
                borderRadiusWhenStacked: 'last',
            },
        },
        stroke: tipo === 'area' || tipo === 'line' ? { curve: 'smooth', width: 2 } : { width: 0 },
        fill: tipo === 'area' ? { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.02 } } : { opacity: 1 },
        dataLabels: { enabled: false },
        legend: { ...opcionesTema().legend, show: series.length > 1, position: 'top', horizontalAlign: 'right' },
        tooltip: { ...opcionesTema().tooltip, shared: false, intersect: false, custom: tooltipPersonalizado(id, sufijo) },
        xaxis: {
            ...opcionesTema().xaxis,
            categories: categorias,
            axisTicks: { show: false },
            axisBorder: { show: false },
        },
        yaxis: {
            ...opcionesTema().yaxis,
            // Sin esto una serie plana en 0 dibuja un eje 0..1 con decimales.
            max: Math.max(1, maximo + 1),
            tickAmount: Math.min(5, Math.max(1, maximo + 1)),
            labels: { ...opcionesTema().yaxis.labels, formatter: (valor) => String(Math.round(valor)) },
        },
    };
}

/** Monta todo contenedor [data-nexo-chart] que aún no tenga instancia. */
export function montarGraficos(raiz = document) {
    raiz.querySelectorAll('[data-nexo-chart]').forEach((el) => {
        if (!el.id || registro.has(el.id)) {
            return;
        }

        let config;

        try {
            config = JSON.parse(el.dataset.nexoChartConfig || '{}');
        } catch (error) {
            console.error(`[panel-charts] configuración inválida en #${el.id}`, error);

            return;
        }

        config.id = el.id;

        // El registro debe existir antes de render(): el tooltip lo consulta.
        registro.set(el.id, { chart: null, detalle: config.detalle || {} });

        const chart = new ApexCharts(el, opciones(config));
        registro.get(el.id).chart = chart;
        chart.render();
    });
}

/** Reemplaza series y categorías sin recrear la instancia (evita el parpadeo). */
export function actualizarGrafico(id, config = {}) {
    const entrada = registro.get(id);

    if (!entrada?.chart) {
        return;
    }

    entrada.detalle = config.detalle || {};

    const maximo = Math.max(0, ...(config.series ?? []).flatMap((s) => s.data ?? []));

    entrada.chart.updateOptions(
        {
            xaxis: { ...opcionesTema().xaxis, categories: config.categorias ?? [] },
            yaxis: {
                ...opcionesTema().yaxis,
                max: Math.max(1, maximo + 1),
                tickAmount: Math.min(5, Math.max(1, maximo + 1)),
                labels: { ...opcionesTema().yaxis.labels, formatter: (valor) => String(Math.round(valor)) },
            },
        },
        false,
        true
    );

    entrada.chart.updateSeries(config.series ?? [], true);
}

/**
 * Libera las instancias cuyo nodo ya no está en el documento. Necesario con
 * wire:navigate, que reemplaza el <body> entero sin avisar a ApexCharts.
 */
export function destruirGraficosHuerfanos() {
    registro.forEach((entrada, id) => {
        if (!document.getElementById(id)) {
            entrada.chart?.destroy();
            registro.delete(id);
        }
    });
}

/** Recolorea los gráficos vivos al alternar claro/oscuro, sin recargar. */
export function retemarGraficos() {
    registro.forEach((entrada) => entrada.chart?.updateOptions(opcionesTema(), false, false));
}
