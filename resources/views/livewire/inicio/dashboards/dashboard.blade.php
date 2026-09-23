<div class="space-y-5">
    <x-dashboard.cabecera
        titulo="Panel estadístico institucional"
        subtitulo="Seguimiento de la vinculación universidad–sociedad."
        :rol="$ambito->rolActivo"
        :ambito="$ambito->etiqueta">
        <x-slot:acciones>
            @can('proyectos.historial')
                <a href="{{ route('listarProyectosVinculacion') }}" wire:navigate
                    class="inline-flex items-center gap-1.5 rounded-xl bg-amber-400 px-4 py-2 text-xs font-bold text-primary-900 transition hover:bg-amber-300">
                    @svg('heroicon-o-rectangle-stack', ['class' => 'h-4 w-4'])
                    Todos los proyectos
                </a>
            @endcan
            @can('proyectos.solicitados')
                <a href="{{ route('listarProyectosSolicitado') }}" wire:navigate
                    class="inline-flex items-center gap-1.5 rounded-xl bg-white/10 px-4 py-2 text-xs font-bold text-white transition hover:bg-white/20">
                    Bandeja de revisión
                </a>
            @endcan
        </x-slot:acciones>

        <x-slot:metricas>
            <div class="grid grid-cols-2 gap-x-6 gap-y-4 sm:grid-cols-3 lg:grid-cols-5">
                {{-- Vigentes son los registrados y en ejecución; el total incluía borradores y finalizados. --}}
                <x-dashboard.cifra-cabecera :valor="$resumen['en_curso']" etiqueta="Proyectos vigentes" icono="heroicon-o-academic-cap" :destacada="true" />
                <x-dashboard.cifra-cabecera :valor="$poblacion['poblacion']" etiqueta="Personas alcanzadas" icono="heroicon-o-users" :destacada="true" />
                <x-dashboard.cifra-cabecera :valor="$estudiantes['total']" etiqueta="Estudiantes vinculados" icono="heroicon-o-user-group" />
                <x-dashboard.cifra-cabecera :valor="$esfuerzo['horas']" etiqueta="Horas de vinculación" icono="heroicon-o-clock" />
                <x-dashboard.cifra-cabecera :valor="'L '.number_format($esfuerzo['aporte'])" etiqueta="Aporte institucional" icono="heroicon-o-banknotes" />
            </div>
        </x-slot:metricas>
    </x-dashboard.cabecera>

    <div
        x-data="{ barra: (localStorage.getItem('nexoPanelBarra') ?? 'abierta') === 'abierta' }"
        x-init="$watch('barra', v => localStorage.setItem('nexoPanelBarra', v ? 'abierta' : 'cerrada'))"
        class="flex flex-col items-start gap-5 md:flex-row"
    >
        <main class="w-full min-w-0 flex-1 space-y-5">
            <x-dashboard.panel titulo="Estado de los trámites"
                subtitulo="Todos los formularios, resumidos en los mismos cinco estados"
                icono="heroicon-o-squares-2x2">
                <x-dashboard.estado-tramites :resumen="$tramites" :matriz="$matriz"
                    :seleccionado="$detalleFormulario['codigo'] ?? null" />
            </x-dashboard.panel>

            @if ($detalleFormulario)
                <x-dashboard.panel titulo="Recorrido de {{ $detalleFormulario['codigo'] }}"
                    subtitulo="{{ $detalleFormulario['nombre'] }} · en qué etapa espera cada trámite"
                    icono="heroicon-o-arrow-long-right">
                    <x-dashboard.recorrido-formulario :detalle="$detalleFormulario" :opciones="$opcionesDetalle" />
                </x-dashboard.panel>
            @endif

            <x-dashboard.panel titulo="Dónde se atascan los trámites"
                subtitulo="Etapas con más trámites esperando, de todos los formularios"
                icono="heroicon-o-funnel" :sinPadding="true">
                <x-dashboard.atascos :items="$atascos" />
            </x-dashboard.panel>

            <div class="grid gap-5" :class="barra ? '2xl:grid-cols-2' : 'xl:grid-cols-2'">
                <x-dashboard.panel titulo="Evolución de registros"
                    subtitulo="Proyectos inscritos mes a mes" icono="heroicon-o-chart-bar">
                    <x-slot:acciones>
                        <button type="button" wire:click="alternarRangoGrafico"
                            class="rounded-lg border border-slate-200 px-3 py-1.5 text-[11px] font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                            {{ $mesesGrafico === 12 ? 'Ver 3 años' : 'Ver último año' }}
                        </button>
                    </x-slot:acciones>

                    <x-dashboard.grafico-apex :id="$idGrafico" tipo="area"
                        :series="$serieGrafico['series']" :categorias="$serieGrafico['categorias']"
                        :detalle="$serieGrafico['detalle']" :alto="190" />
                </x-dashboard.panel>

                @if (! empty($tiempos))
                    <x-dashboard.panel titulo="Salud del flujo"
                        subtitulo="Proyectos: días promedio por etapa" icono="heroicon-o-heart">
                        <x-dashboard.salud-flujo :etapas="$tiempos" :esperando="$esperando" />
                    </x-dashboard.panel>
                @endif
            </div>

            @if (! empty($detenidos))
                @php
                    $detenidosLista = collect($detenidos)->map(fn (array $d) => (object) [
                        'tipo' => $d['tipo'] ?? 'Proyecto',
                        'codigo' => $d['codigo'],
                        'nombre' => $d['nombre'],
                        'etapa' => $d['etapa'],
                        'dias_espera' => $d['dias'],
                        'href' => route('historialproyecto', $d['proyecto_id']),
                    ]);
                @endphp
                <x-dashboard.panel titulo="Trámites detenidos"
                    subtitulo="Más de dos semanas sin resolverse"
                    icono="heroicon-o-exclamation-triangle" :sinPadding="true">
                    <x-dashboard.lista-pendientes :items="$detenidosLista"
                        vacio="Ningún trámite lleva más de dos semanas detenido" />
                </x-dashboard.panel>
            @endif

            @if ($coberturaOds['proyectos'] > 0)
                @php $odsLider = collect($ods)->sortByDesc('valor')->first(); @endphp
                <x-dashboard.panel-expandible titulo="Objetivos de Desarrollo Sostenible"
                    subtitulo="{{ $coberturaOds['categorias'] }} de los 17 objetivos · {{ number_format($coberturaOds['proyectos']) }} proyectos"
                    icono="heroicon-o-globe-alt"
                    :resumen="$odsLider ? \Illuminate\Support\Str::limit($odsLider['etiqueta'], 22, '…').' · '.$odsLider['valor'] : null">
                    <x-dashboard.matriz-ods :items="$ods" />
                </x-dashboard.panel-expandible>
            @endif

            @if ($coberturaCentros['proyectos'] > 0)
                @php $centroLider = collect($centros)->sortByDesc('valor')->first(); @endphp
                <x-dashboard.panel-expandible titulo="Facultades y centros"
                    subtitulo="{{ $coberturaCentros['categorias'] }} unidades · {{ number_format($coberturaCentros['proyectos']) }} proyectos"
                    icono="heroicon-o-building-library"
                    :resumen="$centroLider ? \Illuminate\Support\Str::limit($centroLider['etiqueta'], 22, '…').' · '.$centroLider['valor'] : null">
                    <x-dashboard.mosaico :items="$centros" :filas="2" :alto="190" />
                </x-dashboard.panel-expandible>
            @endif

            @if ($coberturaDeptos['proyectos'] > 0)
                @php $deptoLider = collect($deptos)->sortByDesc('valor')->first(); @endphp
                <x-dashboard.panel-expandible titulo="Departamentos académicos"
                    subtitulo="{{ $coberturaDeptos['categorias'] }} departamentos · {{ number_format($coberturaDeptos['proyectos']) }} proyectos"
                    icono="heroicon-o-beaker"
                    :resumen="$deptoLider ? \Illuminate\Support\Str::limit($deptoLider['etiqueta'], 22, '…').' · '.$deptoLider['valor'] : null">
                    <x-dashboard.mosaico :items="$deptos" :filas="2" :alto="190" />
                </x-dashboard.panel-expandible>
            @endif

            <x-dashboard.panel-expandible titulo="Quiénes participan"
                subtitulo="Composición de la población alcanzada" icono="heroicon-o-user-group"
                :resumen="number_format($poblacion['poblacion']).' personas'">
                @php
                    $participacion = collect([
                        ['etiqueta' => 'Mujeres', 'valor' => $poblacion['mujeres']],
                        ['etiqueta' => 'Hombres', 'valor' => $poblacion['hombres']],
                        ['etiqueta' => 'Otros', 'valor' => $poblacion['otros']],
                    ])->filter(fn ($p) => $p['valor'] > 0)->values()->all();
                @endphp

                @if (! empty($participacion))
                    <x-dashboard.mosaico :items="$participacion" unidad="personas" :filas="1" :alto="120" />
                @endif

                {{-- Cada bloque solo se dibuja si tiene datos: anunciar un vacío ocupa sitio sin decir nada. --}}
                @if (! empty($modalidad))
                    <div class="mt-5 border-t border-slate-100 pt-4 dark:border-slate-800">
                        <p class="mb-2 text-[11px] font-bold uppercase tracking-[0.1em] text-slate-500 dark:text-slate-400">Modalidad de trabajo</p>
                        <x-dashboard.mosaico :items="$modalidad" :filas="1" :alto="110" />
                    </div>
                @endif

                @if (! empty($categorias))
                    <div class="mt-5 border-t border-slate-100 pt-4 dark:border-slate-800">
                        <p class="mb-2 text-[11px] font-bold uppercase tracking-[0.1em] text-slate-500 dark:text-slate-400">Categoría del proyecto</p>
                        <x-dashboard.mosaico :items="$categorias" :filas="1" :alto="110" />
                    </div>
                @endif
            </x-dashboard.panel-expandible>

            @if (! empty($docentes))
                <x-dashboard.panel-expandible titulo="Quiénes vinculan"
                    subtitulo="Personal con más proyectos registrados" icono="heroicon-o-identification"
                    :resumen="count($docentes).' con proyectos'">
                    <div class="mb-3">
                        <label class="sr-only" for="buscar-docente">Buscar por nombre</label>
                        <input id="buscar-docente" type="search" wire:model.live.debounce.400ms="buscarDocente"
                            placeholder="Buscar por nombre…"
                            class="w-full rounded-xl border-slate-200 text-xs placeholder:text-slate-400 focus:border-primary-400 focus:ring-primary-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    </div>
                    <x-dashboard.mosaico :items="$docentes" :filas="2" :alto="170"
                        vacio="Nadie coincide con esa búsqueda." />
                </x-dashboard.panel-expandible>
            @endif
        </main>

        <aside
            class="w-full shrink-0 md:sticky md:top-[4.5rem] md:h-[calc(100vh-6rem)]"
            :class="barra ? 'md:w-[18rem] lg:w-[20rem]' : 'md:w-11'"
        >
            <div x-show="! barra" x-cloak
                class="hidden h-full flex-col items-center gap-3 rounded-2xl border border-slate-200 bg-white py-3 dark:border-slate-800 dark:bg-slate-900 md:flex">
                <button type="button" @click="barra = true" title="Mostrar actividad"
                    class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200">
                    @svg('heroicon-o-chevron-double-left', ['class' => 'h-4 w-4'])
                </button>
                <span class="rounded-full bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold tabular-nums text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                    {{ $actividad->count() }}
                </span>
                <span class="mt-1 text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400 [writing-mode:vertical-rl]">
                    Actividad
                </span>
            </div>

            <div x-show="barra"
                class="flex h-full min-h-[24rem] flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
                <div class="flex shrink-0 items-center gap-2 border-b border-slate-100 px-4 py-3 dark:border-slate-800">
                    @svg('heroicon-o-bell-alert', ['class' => 'h-4 w-4 shrink-0 text-slate-400'])
                    <h2 class="flex-1 truncate text-sm font-bold text-slate-900 dark:text-white">Actividad reciente</h2>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold tabular-nums text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                        {{ $actividad->count() }}
                    </span>
                    <button type="button" @click="barra = false" title="Ocultar la barra"
                        class="hidden rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200 md:block">
                        @svg('heroicon-o-chevron-double-right', ['class' => 'h-4 w-4'])
                    </button>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto px-4 py-3">
                    <x-dashboard.linea-tiempo :items="$actividad" />
                </div>

                @if ($totalPendientes > 0)
                    {{-- La cifra sale de la bandeja personal, así que lleva a ella y no a la revisión solicitada. --}}
                    <div class="shrink-0 border-t border-slate-200 px-4 py-3 dark:border-slate-800">
                        @can('docente.proyectos')
                            <a href="{{ route('SolicitudProyectosDocente') }}" wire:navigate
                                class="flex items-center gap-2 text-xs font-semibold text-primary-600 hover:underline dark:text-primary-300">
                                @svg('heroicon-o-inbox-arrow-down', ['class' => 'h-4 w-4'])
                                {{ $totalPendientes }} esperan tu revisión
                            </a>
                        @else
                            <p class="flex items-center gap-2 text-xs font-semibold text-slate-600 dark:text-slate-300">
                                @svg('heroicon-o-inbox-arrow-down', ['class' => 'h-4 w-4'])
                                {{ $totalPendientes }} esperan tu revisión
                            </p>
                        @endcan
                    </div>
                @endif
            </div>
        </aside>
    </div>
</div>
