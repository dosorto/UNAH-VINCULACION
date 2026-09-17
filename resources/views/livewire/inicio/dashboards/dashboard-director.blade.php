<div class="space-y-5">
    <x-dashboard.cabecera
        titulo="Panel estadístico"
        subtitulo="Tu bandeja de revisión y el estado de {{ $ambito->etiqueta }}."
        :rol="$ambito->rolActivo"
        :ambito="$ambito->etiqueta">
        <x-slot:acciones>
            @can('docente.proyectos')
                <a href="{{ route('SolicitudProyectosDocente') }}" wire:navigate
                    class="inline-flex items-center gap-1.5 rounded-xl bg-amber-400 px-4 py-2 text-xs font-bold text-primary-900 transition hover:bg-amber-300">
                    @svg('heroicon-o-inbox-arrow-down', ['class' => 'h-4 w-4'])
                    Bandeja de revisión
                </a>
            @endcan
            @can('proyectos.historial')
                <a href="{{ route('listarProyectosVinculacion') }}" wire:navigate
                    class="inline-flex items-center gap-1.5 rounded-xl bg-white/10 px-4 py-2 text-xs font-bold text-white transition hover:bg-white/20">
                    Todos los proyectos
                </a>
            @endcan
        </x-slot:acciones>

        @if ($institucional && $resumen)
            <x-slot:metricas>
                <div class="grid grid-cols-2 gap-x-6 gap-y-4 sm:grid-cols-3 lg:grid-cols-5">
                    <x-dashboard.cifra-cabecera :valor="$resumen['total']" etiqueta="Proyectos de {{ \Illuminate\Support\Str::limit($ambito->etiqueta, 18, '…') }}" icono="heroicon-o-academic-cap" :destacada="true" />
                    <x-dashboard.cifra-cabecera :valor="$totalPendientes" etiqueta="Esperan tu revisión" icono="heroicon-o-inbox-arrow-down" :destacada="true" />
                    <x-dashboard.cifra-cabecera :valor="$resumen['en_revision']" etiqueta="En revisión" icono="heroicon-o-clipboard-document-list" />
                    <x-dashboard.cifra-cabecera :valor="$resumen['en_curso']" etiqueta="En curso" icono="heroicon-o-play-circle" />
                    <x-dashboard.cifra-cabecera :valor="$resumen['subsanacion']" etiqueta="En subsanación" icono="heroicon-o-exclamation-triangle" />
                </div>
            </x-slot:metricas>
        @endif
    </x-dashboard.cabecera>

    @if ($ambito->centroIndeterminado)
        <x-dashboard.aviso tono="alerta"
            titulo="Tu perfil no tiene Facultad o Centro asignado"
            mensaje="Por eso el panel muestra los proyectos donde tienes firma, y no los de tu unidad."
            accionTexto="Completar mi perfil"
            :accionHref="route('mi_perfil')" />
    @endif

    <div
        x-data="{ barra: (localStorage.getItem('nexoPanelBarra') ?? 'abierta') === 'abierta' }"
        x-init="$watch('barra', v => localStorage.setItem('nexoPanelBarra', v ? 'abierta' : 'cerrada'))"
        class="flex flex-col items-start gap-5 md:flex-row"
    >
        <main class="w-full min-w-0 flex-1 space-y-5">
            {{--
                Mi bandeja primero: es la función del rol. Va ordenada de lo más
                antiguo a lo más reciente, porque a quien revisa le importa lo
                que lleva más tiempo esperando.
            --}}
            <x-dashboard.panel titulo="Esperan tu revisión"
                subtitulo="Lo más antiguo primero" icono="heroicon-o-inbox-arrow-down" :sinPadding="true">
                <x-slot:acciones>
                    @if ($esperaMasLarga !== null && $esperaMasLarga > 0)
                        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-bold text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                            El más antiguo: {{ $esperaMasLarga }} días
                        </span>
                    @endif
                    @foreach ($pendientesPorTipo as $tipo => $cuantos)
                        <span class="rounded-full bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                            {{ $tipo }} {{ $cuantos }}
                        </span>
                    @endforeach
                </x-slot:acciones>

                <x-dashboard.lista-pendientes :items="$pendientes"
                    vacio="No tienes nada pendiente de revisar"
                    :verTodosHref="auth()->user()?->can('docente.proyectos') ? route('SolicitudProyectosDocente') : null"
                    verTodosTexto="Ver la bandeja completa" />

                <x-slot:pie>
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        {{--
                            Las asignaciones de revisión se reparten a todos los
                            usuarios de un rol, sin mirar el centro. Decirlo evita
                            que este número parezca contradecir al de la unidad.
                        --}}
                        <p class="text-[11px] text-slate-400 dark:text-slate-500">
                            Las asignaciones pueden incluir trámites de otras unidades.
                        </p>
                        @if ($hayMasPendientes)
                            <button type="button" wire:click="verMasPendientes"
                                class="text-xs font-semibold text-primary-600 hover:underline dark:text-primary-300">
                                Ver más
                            </button>
                        @endif
                    </div>
                </x-slot:pie>
            </x-dashboard.panel>

            {{-- ── Lo que antes no existía: el estado de su unidad ─────────── --}}
            @if ($institucional)
                @if (! empty($carriles))
                    <x-dashboard.panel titulo="Recorrido de los trámites"
                        subtitulo="Cada formulario con el itinerario que le corresponde"
                        icono="heroicon-o-arrow-long-right">
                        <x-dashboard.carriles-tramite :carriles="$carriles" />
                    </x-dashboard.panel>
                @endif

                <div class="grid gap-5" :class="barra ? '2xl:grid-cols-2' : 'xl:grid-cols-2'">
                    @if ($serieGrafico)
                        <x-dashboard.panel titulo="Registro de proyectos"
                            subtitulo="{{ $ambito->etiqueta }}, mes a mes" icono="heroicon-o-chart-bar">
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
                    @endif

                    @if (! empty($tiempos))
                        <x-dashboard.panel titulo="Salud del flujo"
                            subtitulo="Días promedio por etapa" icono="heroicon-o-heart">
                            <x-dashboard.salud-flujo :etapas="$tiempos" :esperando="$esperando" />
                        </x-dashboard.panel>
                    @endif
                </div>

                @if (! empty($detenidos))
                    @php
                        $detenidosLista = collect($detenidos)->map(fn (array $d) => (object) [
                            'tipo' => 'Proyecto',
                            'codigo' => $d['codigo'],
                            'nombre' => $d['nombre'],
                            'etapa' => $d['etapa'],
                            'dias_espera' => $d['dias'],
                            'href' => route('historialproyecto', $d['proyecto_id']),
                        ]);
                    @endphp
                    <x-dashboard.panel
                        titulo="Trámites detenidos en {{ $ambito->etiqueta }}"
                        subtitulo="Más de dos semanas sin resolverse, los revise quien los revise"
                        icono="heroicon-o-exclamation-triangle" :sinPadding="true">
                        <x-dashboard.lista-pendientes :items="$detenidosLista"
                            vacio="Ningún trámite lleva más de dos semanas detenido" />
                    </x-dashboard.panel>
                @endif

                @if ($coberturaCentros['proyectos'] > 0)
                    @php $centroLider = collect($centros)->sortByDesc('valor')->first(); @endphp
                    <x-dashboard.panel-expandible titulo="Facultades y centros"
                        subtitulo="{{ $coberturaCentros['categorias'] }} unidades · {{ number_format($coberturaCentros['proyectos']) }} proyectos"
                        icono="heroicon-o-building-library"
                        :resumen="$centroLider ? \Illuminate\Support\Str::limit($centroLider['etiqueta'], 22, '…').' · '.$centroLider['valor'] : null">
                        <x-dashboard.mosaico :items="$centros" :filas="2" :alto="180" />
                    </x-dashboard.panel-expandible>
                @endif

                @if ($coberturaDeptos['proyectos'] > 0)
                    @php $deptoLider = collect($deptos)->sortByDesc('valor')->first(); @endphp
                    <x-dashboard.panel-expandible titulo="Departamentos académicos"
                        subtitulo="{{ $coberturaDeptos['categorias'] }} departamentos · {{ number_format($coberturaDeptos['proyectos']) }} proyectos"
                        icono="heroicon-o-beaker"
                        :resumen="$deptoLider ? \Illuminate\Support\Str::limit($deptoLider['etiqueta'], 22, '…').' · '.$deptoLider['valor'] : null">
                        <x-dashboard.mosaico :items="$deptos" :filas="2" :alto="180" />
                    </x-dashboard.panel-expandible>
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

                @if ($proyectosAmbito->isNotEmpty())
                    <x-dashboard.panel-expandible titulo="Últimos proyectos de {{ $ambito->etiqueta }}"
                        subtitulo="Los más recientes" icono="heroicon-o-clock"
                        :resumen="$proyectosAmbito->count()">
                        <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($proyectosAmbito as $proyecto)
                                <li>
                                    <a href="{{ route('historialproyecto', $proyecto->id) }}" wire:navigate
                                        class="flex items-center gap-3 py-2.5 transition hover:text-primary-600 dark:hover:text-primary-300">
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-sm font-medium text-slate-900 dark:text-white">
                                                {{ $proyecto->nombre_proyecto }}
                                            </span>
                                            @if ($proyecto->codigo_proyecto)
                                                <span class="mt-0.5 block font-mono text-[11px] text-slate-400">{{ $proyecto->codigo_proyecto }}</span>
                                            @endif
                                        </span>
                                        <x-dashboard.chip-estado :nombre="$proyecto->estadoActual?->tipoestado?->nombre" tamano="xs" />
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </x-dashboard.panel-expandible>
                @endif
            @endif

            {{-- ── Mis proyectos: solo si el usuario tiene alguno ──────────── --}}
            @if ($tienePropios)
                <x-dashboard.panel titulo="Mis proyectos"
                    subtitulo="Aquellos en los que participas" icono="heroicon-o-user"
                    :sinPadding="true">
                    @if ($resumenPropios)
                        <x-slot:acciones>
                            <span class="rounded-full bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                {{ $resumenPropios['total'] }} trámites
                            </span>
                        </x-slot:acciones>
                    @endif

                    {{--
                        Los anchos del <colgroup> y las etiquetas de
                        texto-truncado y stepper-progreso están comprobados por
                        DashboardMisProyectosLayoutTest: el progreso del flujo es
                        la función que no puede perderse en el rediseño.
                    --}}
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[960px] table-fixed text-left text-sm text-slate-500 dark:text-slate-400">
                            <colgroup>
                                <col class="w-[10%]">
                                <col class="w-[19%]">
                                <col class="w-[15%]">
                                <col class="w-[41%]">
                                <col class="w-[15%]">
                            </colgroup>
                            <thead class="border-b border-slate-100 bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500 dark:border-slate-800 dark:bg-slate-800/60 dark:text-slate-400">
                                <tr>
                                    <th scope="col" class="px-5 py-3 font-semibold">Código</th>
                                    <th scope="col" class="px-5 py-3 font-semibold">Nombre</th>
                                    <th scope="col" class="px-5 py-3 font-semibold">Estado actual</th>
                                    <th scope="col" class="px-5 py-3 font-semibold">Progreso</th>
                                    <th scope="col" class="px-5 py-3 font-semibold">Fecha inicio</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach ($misProyectos as $proyecto)
                                    @php
                                        // Una firma por etapa, quedándose con el ciclo de revisión
                                        // más reciente; la primera pendiente marca dónde está.
                                        $etapasFirmas = $proyecto->firmasDeEtapa
                                            ->unique('flujo_aprobacion_etapa_id')
                                            ->sortBy('orden_revision')
                                            ->values();
                                        $etapaActualId = $etapasFirmas
                                            ->firstWhere('estado_revision', 'Pendiente')
                                            ?->flujo_aprobacion_etapa_id;
                                        $stepperDirector = $etapasFirmas->map(fn ($firma) => [
                                            'nombre' => $firma->etapa_nombre,
                                            'estado' => match ($firma->estado_revision) {
                                                'Aprobado' => 'aprobado',
                                                'Rechazado' => 'rechazado',
                                                default => $firma->flujo_aprobacion_etapa_id === $etapaActualId ? 'actual' : 'pendiente',
                                            },
                                        ])->all();
                                    @endphp
                                    <tr class="transition hover:bg-slate-50 dark:hover:bg-slate-800/60">
                                        <td class="whitespace-nowrap px-5 py-3 font-mono text-xs text-slate-500 dark:text-slate-400">
                                            {{ $proyecto->codigo_proyecto ?? '—' }}
                                        </td>
                                        <td class="min-w-0 px-5 py-3 font-medium text-slate-900 dark:text-white">
                                            <a href="{{ route('historialproyecto', $proyecto->id) }}" wire:navigate
                                                class="hover:text-primary-600 dark:hover:text-primary-300">
                                                <x-dashboard.texto-truncado :texto="$proyecto->nombre_proyecto" />
                                            </a>
                                        </td>
                                        <td class="px-5 py-3">
                                            <x-dashboard.chip-estado :nombre="$proyecto->estadoActual?->tipoestado?->nombre" tamano="xs" />
                                        </td>
                                        <td class="px-5 py-3 align-top">
                                            <x-dashboard.stepper-progreso :stepper="$stepperDirector" />
                                        </td>
                                        <td class="px-5 py-3">
                                            {{ $proyecto->fecha_inicio ? \Carbon\Carbon::parse($proyecto->fecha_inicio)->isoFormat('D MMM YYYY') : '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($misProyectos->count() >= $proyectosVisibles)
                        <x-slot:pie>
                            <button type="button" wire:click="verMasProyectos"
                                class="text-xs font-semibold text-primary-600 hover:underline dark:text-primary-300">
                                Ver más proyectos
                            </button>
                        </x-slot:pie>
                    @endif
                </x-dashboard.panel>
            @endif

            {{-- Sin ámbito institucional ni proyectos propios no queda nada que mostrar. --}}
            @if (! $institucional && ! $tienePropios && $totalPendientes === 0)
                <x-dashboard.panel>
                    <x-dashboard.estado-vacio
                        titulo="Todavía no hay nada que mostrar"
                        mensaje="Cuando participes en un proyecto o te asignen una revisión, aparecerá aquí."
                        icono="heroicon-o-inbox"
                        :accionTexto="auth()->user()?->can('docente.crear-proyecto') ? 'Registrar un proyecto' : null"
                        :accionHref="auth()->user()?->can('docente.crear-proyecto') ? route('selectorTipoAccion') : null" />
                </x-dashboard.panel>
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
                    <x-dashboard.linea-tiempo :items="$actividad"
                        vacio="Sin movimientos recientes en {{ $ambito->etiqueta }}." />
                </div>
            </div>
        </aside>
    </div>
</div>
