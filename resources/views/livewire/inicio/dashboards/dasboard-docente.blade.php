<div>
   
            <div><div class="grid grid-cols-1 md:grid-cols-2 sm:grid-cols-1 lg:grid-cols-3 gap-6 items-start"> 
                <section class="col-span-2 self-start">
                    <div class="container mx-auto">
                        <div class="mb-6">
                            <div class="w-full mb-6 lg:mb-0">
                                <div class="p-6 bg-yellow-500 dark:bg-yellow-500 rounded-xl">
                                    <div class="container px-4 mx-auto">
                                        <div class="relative overflow-hidden">
                                            <div class="relative max-w-sm mx-auto lg:mx-0 mb-2 lg:mb-0">
                                                <h3 class="text-3xl font-bold text-white">Bienvenido a su Dashboard
                                                </h3>
                                                <p class="font-medium text-blue-200">Aquí podrás darle seguimiento a
                                                    tus
                                                    proyectos. </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div>
    
                                    <!-- tarjetas de estadisticas -->
                                    <div
                                        class="w-full grid grid-cols-1 mt-6 sm:grid-cols-2 lg:grid-cols-3 gap-6 dark:bg-gray-900">
                                        <!-- Tarjeta -->
                                        <div
                                            class="relative flex items-center p-8 rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                                            <div
                                                class="flex items-center justify-center w-14 h-14 rounded-full bg-blue-100 dark:bg-blue-700">
                                                <svg class="w-8 h-8 text-blue-600 dark:text-white" aria-hidden="true"
                                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    fill="currentColor" viewBox="0 0 24 24">
                                                    <path
                                                        d="M12.4472 4.10557c-.2815-.14076-.6129-.14076-.8944 0L2.76981 8.49706l9.21949 4.39024L21 8.38195l-8.5528-4.27638Z" />
                                                    <path
                                                        d="M5 17.2222v-5.448l6.5701 3.1286c.278.1325.6016.1293.8771-.0084L19 11.618v5.6042c0 .2857-.1229.5583-.3364.7481l-.0025.0022-.0041.0036-.0103.009-.0119.0101-.0181.0152c-.024.02-.0562.0462-.0965.0776-.0807.0627-.1942.1465-.3405.2441-.2926.195-.7171.4455-1.2736.6928C15.7905 19.5208 14.1527 20 12 20c-2.15265 0-3.79045-.4792-4.90614-.9751-.5565-.2473-.98098-.4978-1.27356-.6928-.14631-.0976-.2598-.1814-.34049-.2441-.04036-.0314-.07254-.0576-.09656-.0776-.01201-.01-.02198-.0185-.02991-.0253l-.01038-.009-.00404-.0036-.00174-.0015-.0008-.0007s-.00004 0 .00978-.0112l-.00009-.0012-.01043.0117C5.12215 17.7799 5 17.5079 5 17.2222Zm-3-6.8765 2 .9523V17c0 .5523-.44772 1-1 1s-1-.4477-1-1v-6.6543Z" />
                                                </svg>
                                            </div>
                                            <div class="ml-5">
                                                <p class="text-base font-medium text-gray-600 dark:text-gray-400">Proyectos
                                                </p>
                                                <h4 class="text-2xl font-bold text-gray-900 dark:text-white">
                                                    {{ $proyectosUser->count() }}</h4>
                                            </div>
                                        </div>
    
                                        <!-- Tarjeta -->
                                        <div
                                            class="relative flex items-center p-8 rounded-xl border border-gray-200 bg-white  dark:border-gray-700 dark:bg-gray-800">
                                            <div
                                                class="flex items-center justify-center w-14 h-14 rounded-full bg-green-100 dark:bg-green-700">
                                                <svg class="w-8 h-8 text-green-600 dark:text-white" aria-hidden="true"
                                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M9 7V2.221a2 2 0 0 0-.5.365L4.586 6.5a2 2 0 0 0-.365.5H9Z" />
                                                    <path fill-rule="evenodd"
                                                        d="M11 7V2h7a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9h5a2 2 0 0 0 2-2Zm4.707 5.707a1 1 0 0 0-1.414-1.414L11 14.586l-1.293-1.293a1 1 0 0 0-1.414 1.414l2 2a1 1 0 0 0 1.414 0l4-4Z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                            <div class="ml-5">
                                                <p class="text-base font-medium text-gray-600 dark:text-gray-400">
                                                    Finalizados
                                                </p>
                                                <h4 class="text-2xl font-bold text-gray-900 dark:text-white">
                                                    {{ $finalizadosUser->count() }}</h4>
                                            </div>
                                        </div>
    
                                        <!-- Tarjeta -->
                                        <div
                                            class="relative flex items-center p-8 rounded-xl border border-gray-200 bg-white  dark:border-gray-700 dark:bg-gray-800">
                                            <div
                                                class="flex items-center justify-center w-14 h-14 rounded-full bg-red-100 dark:bg-red-700">
                                                <svg class="w-8 h-8 text-red-600 dark:text-white" aria-hidden="true"
                                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    fill="currentColor" viewBox="0 0 24 24">
                                                    <path
                                                        d="M14.502 7.046h-2.5v-.928a2.122 2.122 0 0 0-1.199-1.954 1.827 1.827 0 0 0-1.984.311L3.71 8.965a2.2 2.2 0 0 0 0 3.24L8.82 16.7a1.829 1.829 0 0 0 1.985.31 2.121 2.121 0 0 0 1.199-1.959v-.928h1a2.025 2.025 0 0 1 1.999 2.047V19a1 1 0 0 0 1.275.961 6.59 6.59 0 0 0 4.662-7.22 6.593 6.593 0 0 0-6.437-5.695Z" />
                                                </svg>
    
                                            </div>
                                            <div class="ml-5">
                                                <p class="text-base font-medium text-gray-600 dark:text-gray-400">Subsanar
                                                </p>
                                                <h4 class="text-2xl font-bold text-gray-900 dark:text-white">
                                                    {{ $subsanacionUser->count() }}</h4>
                                            </div>
                                        </div>
    
                                        <!-- Tarjeta -->
                                        <div
                                            class="relative flex items-center p-8 rounded-xl border border-gray-200 bg-white  dark:border-gray-700 dark:bg-gray-800">
                                            <div
                                                class="flex items-center justify-center w-14 h-14 rounded-full bg-yellow-100 dark:bg-yellow-700">
                                                <svg class="w-8 h-8 text-yellow-600 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                                    <path fill-rule="evenodd" d="M9 7V2.221a2 2 0 0 0-.5.365L4.586 6.5a2 2 0 0 0-.365.5H9Zm2 0V2h7a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-5h7.586l-.293.293a1 1 0 0 0 1.414 1.414l2-2a1 1 0 0 0 0-1.414l-2-2a1 1 0 0 0-1.414 1.414l.293.293H4V9h5a2 2 0 0 0 2-2Z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                            <div class="ml-5">
                                                <p class="text-base font-medium text-gray-600 dark:text-gray-400">
                                                    En Curso
                                                </p>
                                                <h4 class="text-2xl font-bold text-gray-900 dark:text-white">{{$ejecucionUser->count()}}</h4>
                                            </div>
                                        </div>
    
                                        <!-- Tarjeta -->
                                        <div
                                            class="relative flex items-center p-8 rounded-xl border border-gray-200 bg-white  dark:border-gray-700 dark:bg-gray-800">
                                            <div
                                                class="flex items-center justify-center w-14 h-14 rounded-full bg-purple-100 dark:bg-purple-700">
                                                <svg class="w-8 h-8 text-purple-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24"
                                                    height="24" fill="currentColor" viewBox="0 0 24 24">
                                                    <path fill-rule="evenodd"
                                                        d="M8 7V2.221a2 2 0 0 0-.5.365L3.586 6.5a2 2 0 0 0-.365.5H8Zm2 0V2h7a2 2 0 0 1 2 2v.126a5.087 5.087 0 0 0-4.74 1.368v.001l-6.642 6.642a3 3 0 0 0-.82 1.532l-.74 3.692a3 3 0 0 0 3.53 3.53l3.694-.738a3 3 0 0 0 1.532-.82L19 15.149V20a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9h5a2 2 0 0 0 2-2Z"
                                                        clip-rule="evenodd" />
                                                    <path fill-rule="evenodd"
                                                        d="M17.447 8.08a1.087 1.087 0 0 1 1.187.238l.002.001a1.088 1.088 0 0 1 0 1.539l-.377.377-1.54-1.542.373-.374.002-.001c.1-.102.22-.182.353-.237Zm-2.143 2.027-4.644 4.644-.385 1.924 1.925-.385 4.644-4.642-1.54-1.54Zm2.56-4.11a3.087 3.087 0 0 0-2.187.909l-6.645 6.645a1 1 0 0 0-.274.51l-.739 3.693a1 1 0 0 0 1.177 1.176l3.693-.738a1 1 0 0 0 .51-.274l6.65-6.646a3.088 3.088 0 0 0-2.185-5.275Z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                            <div class="ml-5">
                                                <p class="text-base font-medium text-gray-600 dark:text-gray-400">En revisión
                                                </p>
                                                <h4 class="text-2xl font-bold text-gray-900 dark:text-white">{{$enRevisionUser->count()}}</h4>
                                            </div>
                                        </div>
    
                                        <!-- Tarjeta -->
                                        <div
                                            class="relative flex items-center p-8 rounded-xl border border-gray-200 bg-white  dark:border-gray-700 dark:bg-gray-800">
                                            <div
                                                class="flex items-center justify-center w-14 h-14 rounded-full bg-teal-100 dark:bg-teal-700">
                                                <svg class="w-8 h-8 text-teal-600 dark:text-white" aria-hidden="true"
                                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    fill="currentColor" viewBox="0 0 24 24">
                                                    <path fill-rule="evenodd"
                                                        d="M8 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1h2a2 2 0 0 1 2 2v15a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h2Zm6 1h-4v2H9a1 1 0 0 0 0 2h6a1 1 0 1 0 0-2h-1V4Zm-3 8a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H9Zm2 5a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-2-1a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H9Z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                            <div class="ml-5">
                                                <p class="text-base font-medium text-gray-600 dark:text-gray-400">
                                                    Borradores
                                                </p>
                                                <h4 class="text-2xl font-bold text-gray-900 dark:text-white">
                                                    {{ $borradorUser->count() }}</h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- grafico proyectos en trimestres -->
                        <div class="w-full bg-white border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm dark:bg-gray-800 p-4 md:p-6">
                            <div class="flex justify-between border-gray-200 border-b dark:border-gray-700 pb-3 mb-2">
                                <dl>
                                    <dt class="text-base font-normal text-gray-500 dark:text-gray-400 pb-1">Mis proyectos</dt>
                                    <dd class="leading-none text-3xl font-bold text-gray-900 dark:text-white">{{ $totalProjectsYearUser }}</dd>
                                </dl>
                                <div>
                                    <span
                                        class="bg-green-100 text-green-800 text-xs font-medium inline-flex items-center px-2.5 py-1 rounded-md dark:bg-green-900 dark:text-green-300">
                                    Proyección anual
                                    </span>
                                </div>
                            </div>
    
                            <div id="bar-chart-user" wire:ignore></div>
                            <div class="grid grid-cols-1 items-center border-gray-200 border-t dark:border-gray-700 justify-between">
                                <div class="flex justify-between items-center pt-5">
                                    <button wire:click="toggleChartRange"
                                    class="inline-flex items-center px-4 py-2 font-medium text-sm rounded-md transition duration-200
                                        {{ $chartFullRange
    ? 'bg-blue-500 hover:bg-blue-600 text-white dark:bg-blue-500 dark:hover:bg-blue-600'
    : 'bg-gray-100 hover:bg-gray-200 text-gray-800 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-200' }}"
                                        >
                                        @if($chartFullRange)
                                            Ver últimos 4 años
                                        @else
                                            Ver rango completo
                                        @endif
                                    </button>
                                    <!--<a href="#"
                                        class="uppercase text-sm font-semibold inline-flex items-center rounded-lg text-blue-600 hover:text-blue-700 dark:hover:text-blue-500 hover:bg-gray-100 dark:hover:bg-gray-700 dark:focus:ring-gray-700 dark:border-gray-700 px-3 py-2">
                                        Reporte de Proyectos
                                        <svg class="w-2.5 h-2.5 ms-1.5 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                            fill="none" viewBox="0 0 6 10">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="m1 9 4-4-4-4" />
                                        </svg>
                                    </a>-->
                                </div>
                            </div>
                        </div>
                        <script>
                            document.addEventListener("DOMContentLoaded", function () {
                            let chartDataUser = @json($chartDataUser);
    
                            if (!chartDataUser || typeof chartDataUser !== "object") {
                                console.error("chartDataUser está vacío o en formato incorrecto.");
                                return;
                            }
    
                            const categories = Object.keys(chartDataUser);
                            const seriesData = categories.map(year => chartDataUser[year]?.count || 0);
    
                            const maxValue = Math.max(...seriesData, 0);
                            const yAxisMax = maxValue + 1;
    
                            const options = {
                                series: [{ name: "Proyectos", color: "#3B82F6", data: seriesData }],
                                chart: {
                                    type: "bar",
                                    height: 350,
                                    width: "100%",
                                    toolbar: { show: false },
                                    sparkline: { enabled: false }
                                },
                                plotOptions: {
                                    bar: {
                                        horizontal: false,
                                        columnWidth: "50%",
                                        borderRadius: 6,
                                        borderRadiusApplication: "end",
                                        dataLabels: { position: "top" }
                                    }
                                },
                                legend: { show: false },
                                dataLabels: { enabled: false },
                                tooltip: {
                                    shared: true,
                                    intersect: false,
                                    custom: function ({ series, seriesIndex, dataPointIndex, w }) {
                                        const year = w.globals.labels[dataPointIndex];
                                        const projects = chartDataUser[year]?.projects || [];
    
                                        const projectList = projects.length 
                                            ? projects.map(p => `<li>${p}</li>`).join("") 
                                            : "<li style='color: #94A3B8;'>No hay proyectos</li>";
    
                                        return `
                                            <div class="dark:bg-gray-800 bg-white p-4 rounded-lg shadow-md max-w-[220px] text-gray-800 dark:text-white">
                                                <!-- Año destacado -->
                                                <strong class="text-blue-500 dark:text-blue-400 text-lg font-bold block border-gray-200 border-b dark:border-gray-700 mb-2"> Año ${year}</strong>
    
                                                <!-- Número de proyectos -->
                                                <span class="text-sm font-semibold text-gray-600 dark:text-gray-300 block mb-3">
                                                    ${series[seriesIndex][dataPointIndex]} proyectos
                                                </span>
    
                                                <!-- Lista de proyectos -->
                                                <ul class="list-disc list-inside text-wrap dark:text-gray-300 text-gray-800 text-xs">
                                                    ${projectList}
                                                </ul>
                                            </div>
                                        `;
                                    }
                                },
                                xaxis: {
                                    categories: categories,
                                    axisTicks: { show: false },
                                    axisBorder: { show: false },
                                    labels: {
                                        style: {
                                            fontFamily: "Inter, sans-serif",
                                            cssClass: "text-xs font-normal fill-gray-500 dark:fill-gray-400"
                                        }
                                    }
                                },
                                yaxis: {
                                    max: yAxisMax,
                                    labels: {
                                        style: {
                                            fontFamily: "Inter, sans-serif",
                                            cssClass: "text-xs font-normal fill-gray-500 dark:fill-gray-400"
                                        },
                                        formatter: value => `${parseInt(value)} proyectos`
                                    }
                                },
                                grid: {
                                    strokeDashArray: 4,
                                    padding: { left: 2, right: 2, top: -20 }
                                },
                                fill: { opacity: 1 }
                            };
    
                            if (document.getElementById("bar-chart-user") && typeof ApexCharts !== "undefined") {
                                window.projectsChart = new ApexCharts(document.getElementById("bar-chart-user"), options);
                                window.projectsChart.render();
                            }
    
                            function renderChartUser(newData) {
                                const newCategories = Object.keys(newData);
                                const newSeriesData = newCategories.map(year => newData[year]?.count || 0);
                                const maxValueNew = Math.max(...newSeriesData, 0);
                                const yAxisMaxNew = maxValueNew + 1;
    
                                if (window.projectsChart) {
                                    window.projectsChart.updateOptions({
                                        xaxis: { categories: newCategories },
                                        yaxis: {
                                            max: yAxisMaxNew,
                                            labels: {
                                                style: {
                                                    fontFamily: "Inter, sans-serif",
                                                    cssClass: "text-xs font-normal fill-gray-500 dark:fill-gray-400"
                                                },
                                                formatter: value => `${parseInt(value)} proyectos`
                                            }
                                        }
                                    });
    
                                    window.projectsChart.updateSeries([{ data: newSeriesData }]);
                                }
                            }
    
                            window.addEventListener("updateChart-User", event => {
                                renderChartUser(event.detail.dataUser);
                            });
                        });
                        </script>
                    </div>
                </section>
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
                    <!-- tabla proyectos por empleados -->
                    <div>
                        <div
                            class="h-auto py-4 px-5 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                            <h4 class="text-lg text-gray-900 dark:text-white font-semibold mb-4">Cantidad de proyectos
                            </h4>
                            @forelse($empleadosWithCount as $empleado)
                                <a class="flex p-5 items-center justify-between rounded-xl transition duration-150 hover:bg-gray-100 dark:hover:bg-gray-700"
                                    href="#">
                                    <div class="flex items-center pr-2">
                                        <div
                                            class="flex w-10 h-10 mr-3 items-center justify-center bg-gray-200 dark:bg-gray-700 text-blue-600 dark:text-blue-400 rounded-full">
                                                <div class="rounded-full h-10 w-10 bg-blue-700 p-2 text-gray-100">{{ $empleado->getInitials() }}</div>
                                        </div>
                                        <div>
                                            <h5 class="text-sm text-gray-900 dark:text-white font-medium mb-1">
                                                {{ $empleado->nombre_completo }}</h5>
                                            <p class="text-xs text-gray-600 dark:text-gray-400 font-semibold">Tiene
                                                {{ $empleado->proyectos_count }} proyectos.</p>
                                        </div>
                                    </div>
                                </a>
                            @empty
                                <p class="text-xs text-gray-600 dark:text-gray-400 font-semibold">No hay empleados
                                    registrados
                                </p>
                            @endforelse
                        </div>
                    </div>
                </section>
            </div>
            <!-- tabla de ultimos usuarios -->
            <div>
                <div class="w-full pt-6 md:pt-6 sm:pt-6">
                    <div
                        class="h-full bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                        <h4 class="text-lg p-4 text-gray-900 dark:text-white font-semibold">Mis proyectos</h4>
                        <div class="relative overflow-x-auto sm:rounded-lg">
                            <table
                                class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                                <thead
                                    class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr class="hover:bg-gray-100 dark:hover:bg-gray-700 transition duration-150">
                                        <th scope="col" class="px-6 py-3">
                                            Nombre del proyecto
                                        </th>
                                        <th scope="col" class="px-6 py-3">
                                            Fecha Inicio
                                        </th>
                                        <th scope="col" class="px-6 py-3">
                                            Fecha Finalización
                                        </th>
                                        <th scope="col" class="px-6 py-3">
                                            Categoría
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($proyectosUserTable as $proyecto)
                                        <tr
                                            class="bg-white border-t hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-800 dark:border-gray-700 border-gray-200">
                                            <th scope="row"
                                                class="px-6 py-4 font-medium text-gray-900 max-w-sm dark:text-white">
                                                {{ $proyecto->nombre_proyecto }}
                                            </th>
                                            <td class="px-6 py-4">
                                                {{ \Carbon\Carbon::parse($proyecto->fecha_inicio)->isoFormat('D [de] MMMM YYYY') }}
                                            </td>
                                            <td class="px-6 py-4">
                                                {{ \Carbon\Carbon::parse($proyecto->fecha_finalizacion)->isoFormat('D [de] MMMM YYYY') }}
                                            </td>
                                            <td class="px-6 py-4">
                                                @forelse($proyecto->categoria as $cat)
                                                    <span>{{ $cat->nombre }}</span>@if(!$loop->last), @endif
                                                @empty
                                                    <span>Sin categoría</span>
                                                @endforelse
                                            </td>
                                        </tr>
                                    @empty
                                        <tr
                                            class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200">
                                            <th scope="row"
                                                class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                                No hay proyectos
                                            </th>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            <div class="flex items-center justify-end px-4 py-3 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
                                {{$proyectosUserTable->links()}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="container mx-auto py-6">
                <h4 class="text-lg py-2 text-gray-900 dark:text-white font-semibold">Panel de estados de proyectos</h4>
                <div class="flex flex-wrap -mx-3 -mb-8">
                    <div class="w-full md:w-1/2 lg:w-1/4 px-3 mb-6">
                        <div class="max-w-md mx-auto h-full px-4 pt-6 pb-24 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center">
                                    <h3 class="text-lg text-gray-900 dark:text-white font-semibold mr-2">Borrador</h3>
                                    <span class="inline-flex items-center justify-center w-6 h-7 rounded-full bg-gray-200 dark:bg-gray-700 text-xs font-medium text-gray-600 dark:text-gray-400">{{$borradorUser->count()}}</span>
                                </div>
                            </div>
                    
                            <!-- Línea de progreso -->
                            <div class="h-1 w-full mb-4 rounded-full bg-purple-600 dark:bg-purple-400"></div>
                    
                            <!-- Tarjeta de tarea -->
                            @forelse($enBorradorUser as $proyecto)
                            <a class="block p-4 mb-4 bg-gray-100 dark:bg-gray-700 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition duration-200" href="{{ route('historialproyecto', $proyecto->id) }}">
                                <h4 class="text-gray-900 dark:text-white font-semibold leading-6 mb-1">{{$proyecto->nombre_proyecto}}</h4>
                                <div class="flex items-center mb-4">
                                    <span class="h-2 w-2 mr-1 bg-purple-500 dark:bg-purple-400 rounded-full"></span>
                                    <span class="text-xs font-medium text-purple-500 dark:text-purple-400">Borrador</span>
                                </div>
                              <!--<p class="text-xs text-gray-600 dark:text-gray-300 leading-normal mb-10"></p>
                                <div class="pt-4 border-t border-gray-300 dark:border-gray-600">
                                    <div class="flex flex-wrap items-center justify-between -m-2">
                                        <div class="w-auto p-2">
                                            <div class="flex items-center p-2 bg-gray-200 dark:bg-gray-700 rounded-md">
                                                <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                                    <path fill-rule="evenodd" d="M5 5a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1 2 2 0 0 1 2 2v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7a2 2 0 0 1 2-2ZM3 19v-7a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6.01-6a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm-10 4a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Z" clip-rule="evenodd"/>
                                                  </svg>
                                                  
                                                <span class="ml-2 text-xs font-medium text-gray-700 dark:text-gray-300">21 de marzo</span>
                                            </div>
                                        </div>
                                        <div class="w-auto p-2">
                                            <div class="flex h-full items-center">
                                                <img class="w-7 h-7 rounded-full object-cover" src="tracker-assets/images/avatar-women-2-circle-border.png" alt="" />
                                                <img class="w-7 h-7 -ml-3 rounded-full object-cover" src="tracker-assets/images/avatar-women-3-circle-border.png" alt="" />
                                            </div>
                                        </div>
                                    </div>
                                </div>-->
                            </a>
                            @empty
                                <p class="text-xs text-gray-600 dark:text-gray-300 leading-normal mb-10">No hay proyectos en borrador</p>
                            @endforelse
                            <div class="flex flex-col bg-white rounded-t-lg dark:bg-gray-800 p-5 sm:flex-row flex-wrap space-y-4 sm:space-y-0 items-center justify-between">
                                @if ($enBorradorUser->hasMorePages())
                                    <div class="mt-4 text-center">
                                        <button 
                                            wire:click="loadMore"
                                            class="px-4 py-2 bg-yellow-500 text-white rounded-md"
                                            wire:loading.attr="disabled"
                                            wire:loading.class="bg-gray-300 cursor-not-allowed"
                                        >
                                            Ver más
                                            <div wire:loading>
                                                <svg 
                                                    aria-hidden="true" 
                                                    role="status"
                                                    class="inline w-4 h-4 me-3 text-gray-200 animate-spin dark:text-gray-600"
                                                    viewBox="0 0 100 101" 
                                                    fill="none" 
                                                    xmlns="http://www.w3.org/2000/svg"
                                                >
                                                    <path 
                                                        d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z"
                                                        fill="currentColor"
                                                    />
                                                    <path 
                                                        d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z"
                                                        fill="#ffff"
                                                    />
                                                </svg>
                                            </div>
                                        </button>
                                    </div>
                                @else
                                    <div class="text-center">
                                        <p class="text-gray-300 dark:text-gray-400">----------------------------</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="w-full md:w-1/2 lg:w-1/4 px-3 mb-6">
                        <div class="max-w-md mx-auto h-full px-4 pt-6 pb-24 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center">
                                    <h3 class="text-lg text-gray-900 dark:text-white font-semibold mr-2">En revisión</h3>
                                    <span class="inline-flex items-center justify-center w-6 h-7 rounded-full bg-gray-200 dark:bg-gray-700 text-xs font-medium text-gray-600 dark:text-gray-400">{{$enRevisionUser->count()}}</span>
                                </div>
                            </div>
                    
                            <!-- Línea de progreso -->
                            <div class="h-1 w-full mb-4 rounded-full bg-yellow-500 dark:bg-yellow-400"></div>
                    
                            <!-- Tarjeta de tarea -->
                           @forelse($enRevisionUser as $proyecto)
                            <a class="block p-4 mb-4 bg-gray-100 dark:bg-gray-700 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition duration-200" href="{{ route('historialproyecto', $proyecto->id) }}">
                                <h4 class="text-gray-900 dark:text-white font-semibold leading-6 mb-1">{{$proyecto->nombre_proyecto}}</h4>
                                <div class="flex items-center mb-4">
                                    <span class="h-2 w-2 mr-1 bg-yellow-500 dark:bg-yellow-400 rounded-full"></span>
                                    <span class="text-xs font-medium text-yellow-500 dark:text-ywllow-400">{{$proyecto->tipo_estado->nombre}}</span>
                                </div>
                                <!--<p class="text-xs text-gray-600 dark:text-gray-300 leading-normal mb-10">Comentario de retroalimentación para esta subsanacion</p>
                                <div class="pt-4 border-t border-gray-300 dark:border-gray-600">
                                    <div class="flex flex-wrap items-center justify-between -m-2">
                                        <div class="w-auto p-2">
                                            <div class="flex items-center p-2 bg-gray-200 dark:bg-gray-700 rounded-md">
                                                <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                                    <path fill-rule="evenodd" d="M5 5a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1 2 2 0 0 1 2 2v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7a2 2 0 0 1 2-2ZM3 19v-7a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6.01-6a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm-10 4a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Z" clip-rule="evenodd"/>
                                                  </svg>
                                                <span class="ml-2 text-xs font-medium text-gray-700 dark:text-gray-300">21 de marzo</span>
                                            </div>
                                        </div>
                                        <div class="w-auto p-2">
                                            <div class="flex h-full items-center">
                                                <img class="w-7 h-7 rounded-full object-cover" src="tracker-assets/images/avatar-women-3-circle-border.png" alt="" />
                                                <img class="w-7 h-7 -ml-3 rounded-full object-cover" src="tracker-assets/images/avatar-women-circle-border.png" alt="" />
                                            </div>
                                        </div>
                                    </div>
                                </div>-->
                            </a>
                            @empty
                                <p class="text-xs text-gray-600 dark:text-gray-300 leading-normal mb-10">Esperando proyectos</p>
                            @endforelse
                            <div class="flex flex-col bg-white rounded-t-lg dark:bg-gray-800 p-5 sm:flex-row flex-wrap space-y-4 sm:space-y-0 items-center justify-between">
                                @if ($enRevisionUser->hasMorePages())
                                    <div class="mt-4 text-center">
                                        <button 
                                            wire:click="loadMore"
                                            class="px-4 py-2 bg-yellow-500 text-white rounded-md"
                                            wire:loading.attr="disabled"
                                            wire:loading.class="bg-gray-300 cursor-not-allowed"
                                        >
                                            Ver más
                                            <div wire:loading>
                                                <svg 
                                                    aria-hidden="true" 
                                                    role="status"
                                                    class="inline w-4 h-4 me-3 text-gray-200 animate-spin dark:text-gray-600"
                                                    viewBox="0 0 100 101" 
                                                    fill="none" 
                                                    xmlns="http://www.w3.org/2000/svg"
                                                >
                                                    <path 
                                                        d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z"
                                                        fill="currentColor"
                                                    />
                                                    <path 
                                                        d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z"
                                                        fill="#ffff"
                                                    />
                                                </svg>
                                            </div>
                                        </button>
                                    </div>
                                @else
                                    <div class="text-center">
                                        <p class="text-gray-300 dark:text-gray-400">----------------------------</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    
        
                    <div class="w-full md:w-1/2 lg:w-1/4 px-3 mb-6">
                        <div class="max-w-md mx-auto h-full px-4 pt-6 pb-24 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center">
                                    <h3 class="text-lg text-gray-900 dark:text-white font-semibold mr-2">En curso</h3>
                                    <span class="inline-flex items-center justify-center w-6 h-7 rounded-full bg-gray-200 dark:bg-gray-700 text-xs font-medium text-gray-600 dark:text-gray-400">{{$ejecucionUser->count()}}</span>
                                </div>
                            </div>
                    
                            <!-- Línea de progreso -->
                            <div class="h-1 w-full mb-4 rounded-full bg-blue-500 dark:bg-blue-400"></div>
                    
                             <!-- Tarjeta de tarea -->
                             @forelse($enEjecucionUser as $proyecto)
                             <a class="block p-4 mb-4 bg-gray-100 dark:bg-gray-700 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition duration-200" href="{{ route('historialproyecto', $proyecto->id) }}">
                                 <h4 class="text-gray-900 dark:text-white font-semibold leading-6 mb-1">{{$proyecto->nombre_proyecto}}</h4>
                                 <div class="flex items-center mb-4">
                                     <span class="h-2 w-2 mr-1 bg-blue-500 dark:bg-blue-400 rounded-full"></span>
                                     <span class="text-xs font-medium text-blue-500 dark:text-blue-400">{{$proyecto->tipo_estado->nombre}}</span>
                                 </div>
                                 <!--<p class="text-xs text-gray-600 dark:text-gray-300 leading-normal mb-10">This is an example task that can be used within a Kanban system.</p>
                                 <div class="pt-4 border-t border-gray-300 dark:border-gray-600">
                                     <div class="flex flex-wrap items-center justify-between -m-2">
                                         <div class="w-auto p-2">
                                             <div class="flex items-center p-2 bg-gray-200 dark:bg-gray-700 rounded-md">
                                                 <svg width="14" height="14" viewbox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                     <path d="M11.0001 2.33337H3.00008C2.2637 2.33337..." stroke="currentColor"></path>
                                                 </svg>
                                                 <span class="ml-2 text-xs font-medium text-gray-700 dark:text-gray-300">Jul 03</span>
                                             </div>
                                         </div>
                                         <div class="w-auto p-2">
                                             <div class="flex h-full items-center">
                                                 <img class="w-7 h-7 rounded-full object-cover" src="tracker-assets/images/avatar-women-3-circle-border.png" alt="" />
                                                 <img class="w-7 h-7 -ml-3 rounded-full object-cover" src="tracker-assets/images/avatar-women-circle-border.png" alt="" />
                                             </div>
                                         </div>
                                     </div>
                                 </div>-->
                             </a>
                            @empty
                                <p class="text-xs text-gray-600 dark:text-gray-300 leading-normal mb-10">Esperando proyectos</p>
                            @endforelse
                            <div class="flex flex-col bg-white rounded-t-lg dark:bg-gray-800 p-5 sm:flex-row flex-wrap space-y-4 sm:space-y-0 items-center justify-between">
                                @if ($enEjecucionUser->hasMorePages())
                                    <div class="mt-4 text-center">
                                        <button 
                                            wire:click="loadMore"
                                            class="px-4 py-2 bg-yellow-500 text-white rounded-md"
                                            wire:loading.attr="disabled"
                                            wire:loading.class="bg-gray-300 cursor-not-allowed"
                                        >
                                            Ver más
                                            <div wire:loading>
                                                <svg 
                                                    aria-hidden="true" 
                                                    role="status"
                                                    class="inline w-4 h-4 me-3 text-gray-200 animate-spin dark:text-gray-600"
                                                    viewBox="0 0 100 101" 
                                                    fill="none" 
                                                    xmlns="http://www.w3.org/2000/svg"
                                                >
                                                    <path 
                                                        d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z"
                                                        fill="currentColor"
                                                    />
                                                    <path 
                                                        d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z"
                                                        fill="#ffff"
                                                    />
                                                </svg>
                                            </div>
                                        </button>
                                    </div>
                                @else
                                    <div class="text-center">
                                        <p class="text-gray-300 dark:text-gray-400">----------------------------</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    
        
                    <div class="w-full md:w-1/2 lg:w-1/4 px-3 mb-6">
                        <div class="max-w-md mx-auto h-full px-4 pt-6 pb-24 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center">
                                    <h3 class="text-lg text-gray-900 dark:text-white font-semibold mr-2">Finalizados</h3>
                                    <span class="inline-flex items-center justify-center w-6 h-7 rounded-full bg-gray-200 dark:bg-gray-700 text-xs font-medium text-gray-600 dark:text-gray-400"> {{ $finalizadosUser->count() }}</span>
                                </div>
                            </div>
                    
                            <!-- Línea de progreso -->
                            <div class="h-1 w-full mb-4 rounded-full bg-green-500 dark:bg-green-400"></div>
                    
                             <!-- Tarjeta de tarea -->
                             @forelse($enFinalizadosUser as $proyecto)
                             <a class="block p-4 mb-4 bg-gray-100 dark:bg-gray-700 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition duration-200" href="{{ route('historialproyecto', $proyecto->id) }}">
                                 <h4 class="text-gray-900 dark:text-white font-semibold leading-6 mb-1">{{$proyecto->nombre_proyecto}}</h4>
                                 <div class="flex items-center mb-4">
                                     <span class="h-2 w-2 mr-1 bg-green-500 dark:bg-green-400 rounded-full"></span>
                                     <span class="text-xs font-medium text-green-500 dark:text-green-400">{{$proyecto->tipo_estado->nombre}}</span>
                                 </div>
                                <!-- <p class="text-xs text-gray-600 dark:text-gray-300 leading-normal mb-10">This is an example task that can be used within a Kanban system.</p>
                                 <div class="pt-4 border-t border-gray-300 dark:border-gray-600">
                                     <div class="flex flex-wrap items-center justify-between -m-2">
                                         <div class="w-auto p-2">
                                             <div class="flex items-center p-2 bg-gray-200 dark:bg-gray-700 rounded-md">
                                                 <svg width="14" height="14" viewbox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                     <path d="M11.0001 2.33337H3.00008C2.2637 2.33337..." stroke="currentColor"></path>
                                                 </svg>
                                                 <span class="ml-2 text-xs font-medium text-gray-700 dark:text-gray-300">Jul 03</span>
                                             </div>
                                         </div>
                                         <div class="w-auto p-2">
                                             <div class="flex h-full items-center">
                                                 <img class="w-7 h-7 rounded-full object-cover" src="tracker-assets/images/avatar-women-3-circle-border.png" alt="" />
                                                 <img class="w-7 h-7 -ml-3 rounded-full object-cover" src="tracker-assets/images/avatar-women-circle-border.png" alt="" />
                                             </div>
                                         </div>
                                     </div>
                                 </div>-->
                             </a>
                            @empty
                                <p class="text-xs text-gray-600 dark:text-gray-300 leading-normal mb-10">Esperando proyectos</p>
                            @endforelse 
                            <div class="flex flex-col bg-white rounded-t-lg dark:bg-gray-800 p-5 sm:flex-row flex-wrap space-y-4 sm:space-y-0 items-center justify-between">
                                @if ($enFinalizadosUser->hasMorePages())
                                    <div class="mt-4 text-center">
                                        <button 
                                            wire:click="loadMore"
                                            class="px-4 py-2 bg-yellow-500 text-white rounded-md"
                                            wire:loading.attr="disabled"
                                            wire:loading.class="bg-gray-300 cursor-not-allowed"
                                        >
                                            Ver más
                                            <div wire:loading>
                                                <svg 
                                                    aria-hidden="true" 
                                                    role="status"
                                                    class="inline w-4 h-4 me-3 text-gray-200 animate-spin dark:text-gray-600"
                                                    viewBox="0 0 100 101" 
                                                    fill="none" 
                                                    xmlns="http://www.w3.org/2000/svg"
                                                >
                                                    <path 
                                                        d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z"
                                                        fill="currentColor"
                                                    />
                                                    <path 
                                                        d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z"
                                                        fill="#ffff"
                                                    />
                                                </svg>
                                            </div>
                                        </button>
                                    </div>
                                @else
                                    <div class="text-center">
                                        <p class="text-gray-300 dark:text-gray-400">----------------------------</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
 
</div>
