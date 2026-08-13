<div>
    <div class="grid grid-cols-1 md:grid-cols-2 sm:grid-cols-1 lg:grid-cols-3 gap-6 items-start">

        <!-- ══ COLUMNA PRINCIPAL (2/3) ══════════════════════════════════════ -->
        <section class="col-span-2 self-start">
            <div class="container mx-auto">
                <div class="mb-6">
                    <div class="w-full mb-6 lg:mb-0">

                        <!-- Banner -->
                        <div class="p-6 bg-yellow-500 dark:bg-yellow-700 rounded-xl">
                            <div class="container px-4 mx-auto">
                                <div class="relative overflow-hidden">
                                    <div class="relative max-w-sm mx-auto lg:mx-0 mb-2 lg:mb-0">
                                        <h3 class="text-2xl font-bold text-white">Bienvenido a su Panel estadístico</h3>
                                        <p class="font-medium text-yellow-100">Aquí podrás dar seguimiento a tus proyectos.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ── TARJETAS: MIS PROYECTOS ── -->
                        <div class="mt-6">
                            <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-3">Mis proyectos</h2>
                            <div class="w-full grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 dark:bg-gray-900">

                                <!-- Total -->
                                <div class="relative flex items-center p-6 rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                                    <div class="flex items-center justify-center w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-700">
                                        <svg class="w-7 h-7 text-blue-600 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12.4472 4.10557c-.2815-.14076-.6129-.14076-.8944 0L2.76981 8.49706l9.21949 4.39024L21 8.38195l-8.5528-4.27638Z"/>
                                            <path d="M5 17.2222v-5.448l6.5701 3.1286c.278.1325.6016.1293.8771-.0084L19 11.618v5.6042c0 .2857-.1229.5583-.3364.7481l-.0025.0022-.0041.0036-.0103.009-.0119.0101-.0181.0152c-.024.02-.0562.0462-.0965.0776-.0807.0627-.1942.1465-.3405.2441-.2926.195-.7171.4455-1.2736.6928C15.7905 19.5208 14.1527 20 12 20c-2.15265 0-3.79045-.4792-4.90614-.9751-.5565-.2473-.98098-.4978-1.27356-.6928-.14631-.0976-.2598-.1814-.34049-.2441-.04036-.0314-.07254-.0576-.09656-.0776-.01201-.01-.02198-.0185-.02991-.0253l-.01038-.009-.00404-.0036-.00174-.0015-.0008-.0007s-.00004 0 .00978-.0112l-.00009-.0012-.01043.0117C5.12215 17.7799 5 17.5079 5 17.2222Zm-3-6.8765 2 .9523V17c0 .5523-.44772 1-1 1s-1-.4477-1-1v-6.6543Z"/>
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Proyectos</p>
                                        <h4 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $totalMisProyectos }}</h4>
                                    </div>
                                </div>

                                <!-- Finalizados -->
                                <div class="relative flex items-center p-6 rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                                    <div class="flex items-center justify-center w-12 h-12 rounded-full bg-green-100 dark:bg-green-700">
                                        <svg class="w-7 h-7 text-green-600 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M9 7V2.221a2 2 0 0 0-.5.365L4.586 6.5a2 2 0 0 0-.365.5H9Z"/>
                                            <path fill-rule="evenodd" d="M11 7V2h7a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9h5a2 2 0 0 0 2-2Zm4.707 5.707a1 1 0 0 0-1.414-1.414L11 14.586l-1.293-1.293a1 1 0 0 0-1.414 1.414l2 2a1 1 0 0 0 1.414 0l4-4Z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Finalizados</p>
                                        <h4 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $finalizadosCount }}</h4>
                                    </div>
                                </div>

                                <!-- Subsanar -->
                                <div class="relative flex items-center p-6 rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                                    <div class="flex items-center justify-center w-12 h-12 rounded-full bg-red-100 dark:bg-red-700">
                                        <svg class="w-7 h-7 text-red-600 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M14.502 7.046h-2.5v-.928a2.122 2.122 0 0 0-1.199-1.954 1.827 1.827 0 0 0-1.984.311L3.71 8.965a2.2 2.2 0 0 0 0 3.24L8.82 16.7a1.829 1.829 0 0 0 1.985.31 2.121 2.121 0 0 0 1.199-1.959v-.928h1a2.025 2.025 0 0 1 1.999 2.047V19a1 1 0 0 0 1.275.961 6.59 6.59 0 0 0 4.662-7.22 6.593 6.593 0 0 0-6.437-5.695Z"/>
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Subsanar</p>
                                        <h4 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $subsanarCount }}</h4>
                                    </div>
                                </div>

                                <!-- En Curso -->
                                <div class="relative flex items-center p-6 rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                                    <div class="flex items-center justify-center w-12 h-12 rounded-full bg-yellow-100 dark:bg-yellow-700">
                                        <svg class="w-7 h-7 text-yellow-600 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                            <path fill-rule="evenodd" d="M9 7V2.221a2 2 0 0 0-.5.365L4.586 6.5a2 2 0 0 0-.365.5H9Zm2 0V2h7a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-5h7.586l-.293.293a1 1 0 0 0 1.414 1.414l2-2a1 1 0 0 0 0-1.414l-2-2a1 1 0 0 0-1.414 1.414l.293.293H4V9h5a2 2 0 0 0 2-2Z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">En Curso</p>
                                        <h4 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $enCursoCount }}</h4>
                                    </div>
                                </div>

                                <!-- En Revisión -->
                                <div class="relative flex items-center p-6 rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                                    <div class="flex items-center justify-center w-12 h-12 rounded-full bg-purple-100 dark:bg-purple-700">
                                        <svg class="w-7 h-7 text-purple-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                            <path fill-rule="evenodd" d="M8 7V2.221a2 2 0 0 0-.5.365L3.586 6.5a2 2 0 0 0-.365.5H8Zm2 0V2h7a2 2 0 0 1 2 2v.126a5.087 5.087 0 0 0-4.74 1.368v.001l-6.642 6.642a3 3 0 0 0-.82 1.532l-.74 3.692a3 3 0 0 0 3.53 3.53l3.694-.738a3 3 0 0 0 1.532-.82L19 15.149V20a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9h5a2 2 0 0 0 2-2Z" clip-rule="evenodd"/>
                                            <path fill-rule="evenodd" d="M17.447 8.08a1.087 1.087 0 0 1 1.187.238l.002.001a1.088 1.088 0 0 1 0 1.539l-.377.377-1.54-1.542.373-.374.002-.001c.1-.102.22-.182.353-.237Zm-2.143 2.027-4.644 4.644-.385 1.924 1.925-.385 4.644-4.642-1.54-1.54Zm2.56-4.11a3.087 3.087 0 0 0-2.187.909l-6.645 6.645a1 1 0 0 0-.274.51l-.739 3.693a1 1 0 0 0 1.177 1.176l3.693-.738a1 1 0 0 0 .51-.274l6.65-6.646a3.088 3.088 0 0 0-2.185-5.275Z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">En Revisión</p>
                                        <h4 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $enRevisionCount }}</h4>
                                    </div>
                                </div>

                                <!-- Borradores -->
                                <div class="relative flex items-center p-6 rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                                    <div class="flex items-center justify-center w-12 h-12 rounded-full bg-teal-100 dark:bg-teal-700">
                                        <svg class="w-7 h-7 text-teal-600 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                            <path fill-rule="evenodd" d="M8 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1h2a2 2 0 0 1 2 2v15a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h2Zm6 1h-4v2H9a1 1 0 0 0 0 2h6a1 1 0 1 0 0-2h-1V4Zm-3 8a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H9Zm2 5a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H9Z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Borradores</p>
                                        <h4 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $borradorCount }}</h4>
                                    </div>
                                </div>

                                <!-- Pendientes por revisar -->
                                <div class="relative flex items-center p-6 rounded-xl border border-orange-200 bg-orange-50 dark:border-orange-700 dark:bg-orange-900/20">
                                    <div class="flex items-center justify-center w-12 h-12 rounded-full bg-orange-100 dark:bg-orange-700">
                                        <svg class="w-7 h-7 text-orange-600 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                            <path fill-rule="evenodd" d="M9 2.221V7H4.221a2 2 0 0 1 .365-.5L8.5 2.586A2 2 0 0 1 9 2.22ZM11 2v5a2 2 0 0 1-2 2H4v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2h-7Zm.394 9.553a1 1 0 0 0-1.817.692l.99 3.464-1.811 1.12a1 1 0 1 0 1.054 1.7l2.16-1.337a1 1 0 0 0 .434-1.044l-.52-1.816 1.02-.292a1 1 0 0 0-.51-1.487Z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-orange-700 dark:text-orange-300">
                                            Pendientes
                                            @if($estadoPendienteNombre)
                                                <span class="text-xs font-normal">({{ $estadoPendienteNombre }})</span>
                                            @endif
                                        </p>
                                        <h4 class="text-2xl font-bold text-orange-800 dark:text-orange-200">{{ $totalPendientes }}</h4>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <!-- /Tarjetas -->

                        <!-- ── GRÁFICO: MIS PROYECTOS ── -->
                        <div class="mt-6 w-full bg-white border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm dark:bg-gray-800 p-4 md:p-6">
                            <div class="flex justify-between border-gray-200 border-b dark:border-gray-700 pb-3 mb-2">
                                <dl>
                                    <dt class="text-base font-normal text-gray-500 dark:text-gray-400 pb-1">Mis proyectos</dt>
                                    <dd class="leading-none text-3xl font-bold text-gray-900 dark:text-white">{{ $totalProjectsYearUser }}</dd>
                                </dl>
                                <div>
                                    <span class="bg-green-100 text-green-800 text-xs font-medium inline-flex items-center px-2.5 py-1 rounded-md dark:bg-green-900 dark:text-green-300">
                                        Proyección anual
                                    </span>
                                </div>
                            </div>

                            <div id="bar-chart-director" wire:ignore></div>

                            <div class="grid grid-cols-1 items-center border-gray-200 border-t dark:border-gray-700 justify-between">
                                <div class="flex justify-between items-center pt-5">
                                    <button wire:click="toggleChartRange"
                                        class="inline-flex items-center px-4 py-2 font-medium text-sm rounded-md transition duration-200
                                            {{ $chartFullRange
                                                ? 'bg-blue-500 hover:bg-blue-600 text-white dark:bg-blue-500 dark:hover:bg-blue-600'
                                                : 'bg-gray-100 hover:bg-gray-200 text-gray-800 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-200' }}">
                                        @if($chartFullRange)
                                            Ver últimos 4 años
                                        @else
                                            Ver rango completo
                                        @endif
                                    </button>
                                </div>
                            </div>
                        </div>

                        <script>
                            document.addEventListener("DOMContentLoaded", function () {
                                let chartDataDirector = @json($chartDataUser);

                                if (!chartDataDirector || typeof chartDataDirector !== "object") return;

                                const categories  = Object.keys(chartDataDirector);
                                const seriesData  = categories.map(y => chartDataDirector[y]?.count || 0);
                                const maxValue    = Math.max(...seriesData, 0);

                                const options = {
                                    series: [{ name: "Proyectos", color: "#3B82F6", data: seriesData }],
                                    chart: {
                                        type: "bar", height: 350, width: "100%",
                                        toolbar: { show: false }, sparkline: { enabled: false }
                                    },
                                    plotOptions: {
                                        bar: { horizontal: false, columnWidth: "50%", borderRadius: 6,
                                               borderRadiusApplication: "end", dataLabels: { position: "top" } }
                                    },
                                    legend: { show: false },
                                    dataLabels: { enabled: false },
                                    tooltip: {
                                        shared: true, intersect: false,
                                        custom: function ({ series, seriesIndex, dataPointIndex, w }) {
                                            const year     = w.globals.labels[dataPointIndex];
                                            const projects = chartDataDirector[year]?.projects || [];
                                            const list     = projects.length
                                                ? projects.map(p => `<li>${p}</li>`).join("")
                                                : "<li style='color:#94A3B8;'>No hay proyectos</li>";
                                            return `<div class="dark:bg-gray-800 bg-white p-4 rounded-lg shadow-md max-w-[220px] text-gray-800 dark:text-white">
                                                <strong class="text-blue-500 dark:text-blue-400 text-lg font-bold block border-b border-gray-200 dark:border-gray-700 mb-2">Año ${year}</strong>
                                                <span class="text-sm font-semibold text-gray-600 dark:text-gray-300 block mb-3">${series[seriesIndex][dataPointIndex]} proyectos</span>
                                                <ul class="list-disc list-inside text-wrap dark:text-gray-300 text-gray-800 text-xs">${list}</ul>
                                            </div>`;
                                        }
                                    },
                                    xaxis: {
                                        categories: categories,
                                        axisTicks: { show: false }, axisBorder: { show: false },
                                        labels: { style: { fontFamily: "Inter, sans-serif", cssClass: "text-xs font-normal fill-gray-500 dark:fill-gray-400" } }
                                    },
                                    yaxis: {
                                        max: maxValue + 1,
                                        labels: {
                                            style: { fontFamily: "Inter, sans-serif", cssClass: "text-xs font-normal fill-gray-500 dark:fill-gray-400" },
                                            formatter: v => `${parseInt(v)} proyectos`
                                        }
                                    },
                                    grid: { strokeDashArray: 4, padding: { left: 2, right: 2, top: -20 } },
                                    fill: { opacity: 1 }
                                };

                                if (document.getElementById("bar-chart-director") && typeof ApexCharts !== "undefined") {
                                    window.directorChart = new ApexCharts(document.getElementById("bar-chart-director"), options);
                                    window.directorChart.render();
                                }

                                window.addEventListener("updateChart-Director", event => {
                                    const newData       = event.detail.dataUser;
                                    const newCategories = Object.keys(newData);
                                    const newSeries     = newCategories.map(y => newData[y]?.count || 0);
                                    const newMax        = Math.max(...newSeries, 0);

                                    if (window.directorChart) {
                                        window.directorChart.updateOptions({
                                            xaxis: { categories: newCategories },
                                            yaxis: { max: newMax + 1, labels: { formatter: v => `${parseInt(v)} proyectos` } }
                                        });
                                        window.directorChart.updateSeries([{ data: newSeries }]);
                                    }
                                });
                            });
                        </script>
                        <!-- /Gráfico -->

                        <!-- ── TABLA: MIS PROYECTOS ── -->
                        @if($totalMisProyectos > 0)
                        <div class="mt-6 rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Mis proyectos</h2>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                        <tr>
                                            <th scope="col" class="px-6 py-3">Código</th>
                                            <th scope="col" class="px-6 py-3">Nombre del Proyecto</th>
                                            <th scope="col" class="px-6 py-3">Estado actual</th>
                                            <th scope="col" class="px-6 py-3">Progreso</th>
                                            <th scope="col" class="px-6 py-3">Fecha inicio</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($misProyectosTable as $proyecto)
                                            @php
                                                // Deduplicate: one firma per etapa (latest revision_ciclo first)
                                                $etapasFirmas = $proyecto->firmasDeEtapa
                                                    ->unique('flujo_aprobacion_etapa_id')
                                                    ->sortBy('orden_revision')
                                                    ->values();
                                                $hayProgreso = $etapasFirmas->isNotEmpty();
                                                // First Pendiente = current stage
                                                $etapaActualId = optional(
                                                    $etapasFirmas->firstWhere('estado_revision', 'Pendiente')
                                                        ?? $etapasFirmas->firstWhere('estado', 'PENDIENTE')
                                                        ?? $etapasFirmas->firstWhere('estado', 'ASIGNADO')
                                                        ?? $etapasFirmas->firstWhere('estado', 'EN_PROCESO')
                                                )->flujo_aprobacion_etapa_id;
                                            @endphp
                                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                                <td class="px-6 py-3 font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                                    {{ $proyecto->codigo ?? '—' }}
                                                </td>
                                                <td class="px-6 py-3">{{ $proyecto->nombre }}</td>
                                                <td class="px-6 py-3">
                                                    @if($proyecto->estado)
                                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                            {{ $proyecto->estado }}
                                                        </span>
                                                    @else
                                                        <span class="text-gray-400">—</span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-3">
                                                    @if($hayProgreso)
                                                    <div class="flex items-center gap-1">
                                                        @foreach($etapasFirmas as $firma)
                                                            @php
                                                                $esCurrent = $firma->flujo_aprobacion_etapa_id === $etapaActualId;
                                                                $estadoFirma = $firma->estado_revision ?? $firma->estado ?? null;
                                                                $nombreEtapa = $firma->etapa_nombre ?? 'Etapa';
                                                                $colorClase = match($estadoFirma) {
                                                                    'Aprobado' => 'bg-emerald-500 text-white',
                                                                    'APROBADO' => 'bg-emerald-500 text-white',
                                                                    'Rechazado' => 'bg-red-500 text-white',
                                                                    'SUBSANACION' => 'bg-red-500 text-white',
                                                                    default => $esCurrent
                                                                        ? 'bg-amber-400 text-white ring-2 ring-amber-300 ring-offset-1'
                                                                        : 'bg-slate-200 text-slate-400 dark:bg-slate-700',
                                                                };
                                                                $icono = match($estadoFirma) {
                                                                    'Aprobado' => '✓',
                                                                    'Rechazado' => '✕',
                                                                    default => $esCurrent ? '●' : '○',
                                                                };
                                                            @endphp
                                                            <div class="flex flex-col items-center gap-0.5" title="{{ $nombreEtapa }}">
                                                                <span class="flex h-6 w-6 items-center justify-center rounded-full text-[10px] font-bold {{ $colorClase }}">
                                                                    {{ $icono }}
                                                                </span>
                                                                <span class="hidden text-[9px] text-slate-400 dark:text-slate-500 max-w-[40px] text-center leading-tight truncate sm:block">
                                                                    {{ \Illuminate\Support\Str::limit($nombreEtapa, 8) }}
                                                                </span>
                                                            </div>
                                                            @if(! $loop->last)
                                                                <div class="h-px w-3 bg-slate-300 dark:bg-slate-600 mb-3 shrink-0"></div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                    @else
                                                        <span class="text-xs text-slate-400">Sin enviar</span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-3">
                                                    {{ $proyecto->fecha_inicio ? \Carbon\Carbon::parse($proyecto->fecha_inicio)->format('d/m/Y') : '—' }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="px-6 py-4 text-center text-gray-400 dark:text-gray-500">No hay proyectos registrados.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            @if(false)
                                <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700">
                                    <button wire:click="loadMore"
                                        class="w-full text-sm text-blue-600 dark:text-blue-400 hover:underline">
                                        Ver más proyectos
                                    </button>
                                </div>
                            @endif
                        </div>
                        @endif
                        <!-- /Tabla mis proyectos -->

                        <!-- ── TABLA: PENDIENTES DE REVISIÓN ── -->
                        <div class="mt-6 rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-2">
                                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Pendientes de revisión</h2>
                                @if($estadoPendienteNombre)
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300">
                                        {{ $estadoPendienteNombre }}
                                    </span>
                                @endif
                            </div>

                            @if($pendientesTable->isEmpty())
                                <div class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                                    <svg class="mx-auto mb-3 w-10 h-10 text-gray-300 dark:text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <p class="text-sm">No hay elementos pendientes de revisión para su rol activo.</p>
                                </div>
                            @else
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                            <tr>
                                                <th scope="col" class="px-6 py-3">Tipo</th>
                                                <th scope="col" class="px-6 py-3">Código</th>
                                                <th scope="col" class="px-6 py-3">Nombre</th>
                                                <th scope="col" class="px-6 py-3">Etapa actual</th>
                                                <th scope="col" class="px-6 py-3">Fecha inicio</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($pendientesTable as $pendiente)
                                                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                                    <td class="px-6 py-3">
                                                        <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                                            {{ $pendiente->tipo }}
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-3 font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                                        {{ $pendiente->codigo ?? '—' }}
                                                    </td>
                                                    <td class="px-6 py-3">{{ $pendiente->nombre }}</td>
                                                    <td class="px-6 py-3">
                                                        @if($pendiente->etapa)
                                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                                                                {{ $pendiente->etapa }}
                                                            </span>
                                                        @else
                                                            <span class="text-gray-400">—</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-6 py-3">
                                                        {{ $pendiente->fecha_inicio ? \Carbon\Carbon::parse($pendiente->fecha_inicio)->format('d/m/Y') : '—' }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @if($hayMasPendientes)
                                    <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700">
                                        <button wire:click="loadMorePendientes"
                                            class="w-full text-sm text-orange-600 dark:text-orange-400 hover:underline">
                                            Ver más pendientes
                                        </button>
                                    </div>
                                @endif
                            @endif
                        </div>
                        <!-- /Tabla pendientes -->

                        <!-- ── PANEL DE ESTADOS (kanban) ── -->
                        <div class="mt-6">
                            <h4 class="text-lg py-2 text-gray-900 dark:text-white font-semibold">Panel de estados de proyectos</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                                <!-- Borrador -->
                                <div class="px-4 pt-6 pb-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl">
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex items-center">
                                            <h3 class="text-lg text-gray-900 dark:text-white font-semibold mr-2">Borrador</h3>
                                            <span class="inline-flex items-center justify-center w-6 h-7 rounded-full bg-gray-200 dark:bg-gray-700 text-xs font-medium text-gray-600 dark:text-gray-400">{{ $panelBorrador->total() }}</span>
                                        </div>
                                    </div>
                                    <div class="h-1 w-full mb-4 rounded-full bg-purple-600 dark:bg-purple-400"></div>
                                    @forelse($panelBorrador as $proyecto)
                                        <a class="block p-4 mb-3 bg-gray-100 dark:bg-gray-700 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition duration-200"
                                           href="{{ route('historialproyecto', $proyecto->id) }}">
                                            <h4 class="text-gray-900 dark:text-white font-semibold leading-6 mb-1 text-sm">{{ $proyecto->nombre_proyecto }}</h4>
                                            <div class="flex items-center">
                                                <span class="h-2 w-2 mr-1 bg-purple-500 dark:bg-purple-400 rounded-full"></span>
                                                <span class="text-xs font-medium text-purple-500 dark:text-purple-400">Borrador</span>
                                            </div>
                                        </a>
                                    @empty
                                        <p class="text-xs text-gray-600 dark:text-gray-300 leading-normal">No hay proyectos en borrador</p>
                                    @endforelse
                                    @if($panelBorrador->hasMorePages())
                                        <div class="mt-3 text-center">
                                            <button wire:click="loadMorePanel"
                                                class="px-4 py-1.5 text-xs bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-md transition">
                                                Ver más
                                            </button>
                                        </div>
                                    @endif
                                </div>

                                <!-- En revisión -->
                                <div class="px-4 pt-6 pb-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl">
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex items-center">
                                            <h3 class="text-lg text-gray-900 dark:text-white font-semibold mr-2">En revisión</h3>
                                            <span class="inline-flex items-center justify-center w-6 h-7 rounded-full bg-gray-200 dark:bg-gray-700 text-xs font-medium text-gray-600 dark:text-gray-400">{{ $panelEnRevision->total() }}</span>
                                        </div>
                                    </div>
                                    <div class="h-1 w-full mb-4 rounded-full bg-yellow-500 dark:bg-yellow-400"></div>
                                    @forelse($panelEnRevision as $proyecto)
                                        <a class="block p-4 mb-3 bg-gray-100 dark:bg-gray-700 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition duration-200"
                                           href="{{ route('historialproyecto', $proyecto->id) }}">
                                            <h4 class="text-gray-900 dark:text-white font-semibold leading-6 mb-1 text-sm">{{ $proyecto->nombre_proyecto }}</h4>
                                            <div class="flex items-center">
                                                <span class="h-2 w-2 mr-1 bg-yellow-500 dark:bg-yellow-400 rounded-full"></span>
                                                <span class="text-xs font-medium text-yellow-600 dark:text-yellow-400">{{ $proyecto->tipo_estado->nombre ?? 'En revisión' }}</span>
                                            </div>
                                        </a>
                                    @empty
                                        <p class="text-xs text-gray-600 dark:text-gray-300 leading-normal">No hay proyectos en revisión</p>
                                    @endforelse
                                    @if($panelEnRevision->hasMorePages())
                                        <div class="mt-3 text-center">
                                            <button wire:click="loadMorePanel"
                                                class="px-4 py-1.5 text-xs bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-md transition">
                                                Ver más
                                            </button>
                                        </div>
                                    @endif
                                </div>

                                <!-- En curso -->
                                <div class="px-4 pt-6 pb-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl">
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex items-center">
                                            <h3 class="text-lg text-gray-900 dark:text-white font-semibold mr-2">En curso</h3>
                                            <span class="inline-flex items-center justify-center w-6 h-7 rounded-full bg-gray-200 dark:bg-gray-700 text-xs font-medium text-gray-600 dark:text-gray-400">{{ $panelEnCurso->total() }}</span>
                                        </div>
                                    </div>
                                    <div class="h-1 w-full mb-4 rounded-full bg-blue-500 dark:bg-blue-400"></div>
                                    @forelse($panelEnCurso as $proyecto)
                                        <a class="block p-4 mb-3 bg-gray-100 dark:bg-gray-700 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition duration-200"
                                           href="{{ route('historialproyecto', $proyecto->id) }}">
                                            <h4 class="text-gray-900 dark:text-white font-semibold leading-6 mb-1 text-sm">{{ $proyecto->nombre_proyecto }}</h4>
                                            <div class="flex items-center">
                                                <span class="h-2 w-2 mr-1 bg-blue-500 dark:bg-blue-400 rounded-full"></span>
                                                <span class="text-xs font-medium text-blue-500 dark:text-blue-400">En curso</span>
                                            </div>
                                        </a>
                                    @empty
                                        <p class="text-xs text-gray-600 dark:text-gray-300 leading-normal">No hay proyectos en curso</p>
                                    @endforelse
                                    @if($panelEnCurso->hasMorePages())
                                        <div class="mt-3 text-center">
                                            <button wire:click="loadMorePanel"
                                                class="px-4 py-1.5 text-xs bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-md transition">
                                                Ver más
                                            </button>
                                        </div>
                                    @endif
                                </div>

                                <!-- Finalizados -->
                                <div class="px-4 pt-6 pb-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl">
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex items-center">
                                            <h3 class="text-lg text-gray-900 dark:text-white font-semibold mr-2">Finalizados</h3>
                                            <span class="inline-flex items-center justify-center w-6 h-7 rounded-full bg-gray-200 dark:bg-gray-700 text-xs font-medium text-gray-600 dark:text-gray-400">{{ $panelFinalizados->total() }}</span>
                                        </div>
                                    </div>
                                    <div class="h-1 w-full mb-4 rounded-full bg-green-500 dark:bg-green-400"></div>
                                    @forelse($panelFinalizados as $proyecto)
                                        <a class="block p-4 mb-3 bg-gray-100 dark:bg-gray-700 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition duration-200"
                                           href="{{ route('historialproyecto', $proyecto->id) }}">
                                            <h4 class="text-gray-900 dark:text-white font-semibold leading-6 mb-1 text-sm">{{ $proyecto->nombre_proyecto }}</h4>
                                            <div class="flex items-center">
                                                <span class="h-2 w-2 mr-1 bg-green-500 dark:bg-green-400 rounded-full"></span>
                                                <span class="text-xs font-medium text-green-500 dark:text-green-400">Finalizado</span>
                                            </div>
                                        </a>
                                    @empty
                                        <p class="text-xs text-gray-600 dark:text-gray-300 leading-normal">No hay proyectos finalizados</p>
                                    @endforelse
                                    @if($panelFinalizados->hasMorePages())
                                        <div class="mt-3 text-center">
                                            <button wire:click="loadMorePanel"
                                                class="px-4 py-1.5 text-xs bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-md transition">
                                                Ver más
                                            </button>
                                        </div>
                                    @endif
                                </div>

                            </div>
                        </div>
                        <!-- /Panel de estados -->

                    </div>
                </div>
            </div>
        </section>

        <!-- ══ PANEL LATERAL (1/3) ══════════════════════════════════════════ -->
      <section
                    class="grid grid-cols-1 md:grid-cols-2 md:col-span-2 sm:md:col-span-2 lg:md:col-span-1 sm:grid-cols-1 lg:grid-cols-1 gap-6 self-start">
                    <div>
                        <div
                            class="py-6 px-5 sm:px-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                            <h4 class="text-lg text-gray-900 dark:text-white font-semibold mb-6">Actividades recientes </h4>
                            @if($activitiesUser->count() > 0)
                                <div class="timeline relative pl-6 mt-4">
                                    @foreach($activitiesUser as $estado)
                                        <div class="timeline-item mb-6 relative pb-4">
                                            <!-- Línea vertical continua -->
                                            <div class="absolute top-0 left-0 h-full w-0.5 bg-gray-200 dark:bg-gray-600 -ml-3"></div>
                                            
                                            <!-- Badge/Círculo del timeline -->
                                            <div class="timeline-badge absolute -left-6 w-6 h-6 rounded-full flex items-center justify-center 
                                                {{ $estado->es_actual 
                                                    ? 'bg-blue-500 dark:bg-blue-600' 
                                                    : 'bg-gray-400 dark:bg-gray-500' }}">
                                                <div class="w-3 h-3 bg-white dark:bg-gray-800 rounded-full"></div>
                                            </div>
                                            
                                            <!-- Contenido del elemento del timeline -->
                                            <div class="timeline-content bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow">
                                                <!-- Encabezado -->
                                                <h6 class="timeline-header text-sm font-semibold text-gray-800 dark:text-gray-200 flex items-center mb-2">
                                                    @if($estado->tipo_elemento === 'Proyecto')
                                                        <svg class="w- h-4 text-blue-600 dark:text-blue-600" aria-hidden="true"
                                                            xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                            fill="currentColor" viewBox="0 0 24 24">
                                                            <path
                                                                d="M12.4472 4.10557c-.2815-.14076-.6129-.14076-.8944 0L2.76981 8.49706l9.21949 4.39024L21 8.38195l-8.5528-4.27638Z" />
                                                            <path
                                                                d="M5 17.2222v-5.448l6.5701 3.1286c.278.1325.6016.1293.8771-.0084L19 11.618v5.6042c0 .2857-.1229.5583-.3364.7481l-.0025.0022-.0041.0036-.0103.009-.0119.0101-.0181.0152c-.024.02-.0562.0462-.0965.0776-.0807.0627-.1942.1465-.3405.2441-.2926.195-.7171.4455-1.2736.6928C15.7905 19.5208 14.1527 20 12 20c-2.15265 0-3.79045-.4792-4.90614-.9751-.5565-.2473-.98098-.4978-1.27356-.6928-.14631-.0976-.2598-.1814-.34049-.2441-.04036-.0314-.07254-.0576-.09656-.0776-.01201-.01-.02198-.0185-.02991-.0253l-.01038-.009-.00404-.0036-.00174-.0015-.0008-.0007s-.00004 0 .00978-.0112l-.00009-.0012-.01043.0117C5.12215 17.7799 5 17.5079 5 17.2222Zm-3-6.8765 2 .9523V17c0 .5523-.44772 1-1 1s-1-.4477-1-1v-6.6543Z" />
                                                        </svg>
                                                    @else
                                                        <svg class="w-4 h-4 mr-1.5 text-yellow-600 dark:text-yellow-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h16a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1Z"/>
                                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h8m-8 4h4"/>
                                                        </svg>
                                                    @endif
                                                    <span>{{ $estado->tipo_elemento }}: <span class="text-blue-600 dark:text-blue-400">{{ $estado->nombre_elemento }}</span></span>
                                                </h6>
                                                
                                                <!-- Cuerpo -->
                                                <div class="timeline-body mb-3">
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                                                        {{ $estado->es_actual 
                                                            ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' 
                                                            : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' }}">
                                                        {{ $estado->tipoestado->nombre }}
                                                    </span>
                                                    
                                                    @if($estado->comentario)
                                                        <div class="mt-3 text-sm text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-700 p-3 rounded-md border-l-2 border-gray-300 dark:border-gray-500 italic">
                                                            "{{ $estado->comentario }}"
                                                        </div>
                                                    @endif
                                                </div>
                                                
                                                <!-- Pie -->
                                                <div class="timeline-footer text-xs text-gray-500 dark:text-gray-400 flex items-center">
                                                    <svg class="w-3.5 h-3.5 mr-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                                    </svg>
                                                    {{ $estado->fecha_cambio }}
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="flex flex-col items-center justify-center py-6 text-gray-500 dark:text-gray-400">
                                    <svg class="w-12 h-12 mb-3 opacity-50" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 8h10M9 12h10M9 16h10M5 8h0M5 12h0M5 16h0"/>
                                    </svg>
                                    <p class="font-medium">No hay actividad reciente para mostrar.</p>
                                    <p class="text-sm mt-1">Las actualizaciones de estado aparecerán aquí.</p>
                                </div>
                            @endif
                        </div>
                    </div>

            <!-- Cantidad de proyectos -->
            <div class="py-4 px-5 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <h4 class="text-lg text-gray-900 dark:text-white font-semibold mb-4">Cantidad de proyectos</h4>
                @forelse($empleadosWithCount as $empleado)
                    <div class="flex p-4 items-center justify-between rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition duration-150">
                        <div class="flex items-center pr-2">
                            <div class="rounded-full h-10 w-10 bg-blue-700 p-2 text-gray-100 flex items-center justify-center text-sm font-semibold mr-3">
                                {{ $empleado->getInitials() }}
                            </div>
                            <div>
                                <h5 class="text-sm text-gray-900 dark:text-white font-medium mb-1">{{ $empleado->nombre_completo }}</h5>
                                <p class="text-xs text-gray-600 dark:text-gray-400 font-semibold">
                                    Tiene {{ $empleado->proyectos_count }} proyecto{{ $empleado->proyectos_count !== 1 ? 's' : '' }}.
                                </p>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-gray-600 dark:text-gray-400 font-semibold">Sin proyectos registrados.</p>
                @endforelse
            </div>

        </aside>
        <!-- /Panel lateral -->

    </div>
</div>
