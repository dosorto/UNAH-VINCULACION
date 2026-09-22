<div class="space-y-5">
    <x-dashboard.cabecera
        titulo="Mi panel de vinculación"
        subtitulo="Tus trámites y lo que necesita tu atención."
        :rol="$ambito->rolActivo"
        :ambito="$ambito->etiqueta">
        <x-slot:acciones>
            @can('docente.crear-proyecto')
                <a href="{{ route('selectorTipoAccion') }}" wire:navigate
                    class="inline-flex items-center gap-1.5 rounded-xl bg-amber-400 px-4 py-2 text-xs font-bold text-primary-900 transition hover:bg-amber-300">
                    @svg('heroicon-o-plus', ['class' => 'h-4 w-4'])
                    Nuevo registro
                </a>
            @endcan
            @can('docente.proyectos')
                <a href="{{ route('proyectosDocente') }}" wire:navigate
                    class="inline-flex items-center gap-1.5 rounded-xl bg-white/10 px-4 py-2 text-xs font-bold text-white transition hover:bg-white/20">
                    Mi historial
                </a>
            @endcan
        </x-slot:acciones>

        <x-slot:metricas>
            <div class="grid grid-cols-2 gap-x-6 gap-y-4 sm:grid-cols-3 lg:grid-cols-6">
                <x-dashboard.cifra-cabecera :valor="$resumen['total']" etiqueta="Mis trámites" icono="heroicon-o-folder" :destacada="true" />
                <x-dashboard.cifra-cabecera :valor="$resumen['en_revision']" etiqueta="En revisión" icono="heroicon-o-clipboard-document-list" />
                <x-dashboard.cifra-cabecera :valor="$resumen['en_curso']" etiqueta="Registrados" icono="heroicon-o-play-circle" />
                <x-dashboard.cifra-cabecera :valor="$resumen['finalizado']" etiqueta="Finalizados" icono="heroicon-o-check-badge" />
                <x-dashboard.cifra-cabecera :valor="$poblacion['poblacion']" etiqueta="Personas alcanzadas" icono="heroicon-o-users" />
                <x-dashboard.cifra-cabecera :valor="$esfuerzo['horas']" etiqueta="Horas registradas" icono="heroicon-o-clock" />
            </div>
        </x-slot:metricas>
    </x-dashboard.cabecera>

    <div
        x-data="{ barra: (localStorage.getItem('nexoPanelBarra') ?? 'abierta') === 'abierta' }"
        x-init="$watch('barra', v => localStorage.setItem('nexoPanelBarra', v ? 'abierta' : 'cerrada'))"
        class="flex flex-col items-start gap-5 md:flex-row"
    >
        <main class="w-full min-w-0 flex-1 space-y-5">
            {{--
                Lo primero es lo accionable: subsanaciones propias y firmas
                pendientes. Antes había que deducirlo mirando las tarjetas.
            --}}
            @if ($porSubsanar->isNotEmpty() || $totalPendientes > 0)
                <x-dashboard.panel titulo="Requiere tu atención"
                    subtitulo="Trámites devueltos y revisiones que esperan tu firma"
                    icono="heroicon-o-bell-alert" :sinPadding="true">
                    @if ($porSubsanar->isNotEmpty())
                        <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($porSubsanar as $formulario)
                                <li>
                                    <a @if ($formulario['href']) href="{{ $formulario['href'] }}" wire:navigate @endif
                                        class="flex items-start gap-3 px-5 py-3 transition hover:bg-slate-50 dark:hover:bg-slate-800/60">
                                        <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600 dark:bg-red-950 dark:text-red-300">
                                            @svg('heroicon-o-arrow-uturn-left', ['class' => 'h-4 w-4'])
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-sm font-semibold text-slate-900 dark:text-white">
                                                {{ $formulario['nombre'] }}
                                            </span>
                                            <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">
                                                Te lo devolvieron para corregir · {{ $formulario['categoria'] ?: 'Sin categoría' }}
                                            </span>
                                        </span>
                                        <x-dashboard.chip-estado :nombre="$formulario['estado']" tamano="xs" />
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($pendientesFirma->isNotEmpty())
                        <div class="border-t border-slate-100 dark:border-slate-800">
                            <p class="px-5 pt-3 text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">
                                Esperan tu revisión
                            </p>
                            <x-dashboard.lista-pendientes :items="$pendientesFirma"
                                :verTodosHref="auth()->user()?->can('docente.proyectos') ? route('SolicitudProyectosDocente') : null"
                                verTodosTexto="Ver toda la bandeja" />
                        </div>
                    @endif
                </x-dashboard.panel>
            @endif

            {{--
                Mis proyectos con su stepper.

                La estructura de esta tabla está fijada por
                DashboardMisProyectosLayoutTest: los anchos del <colgroup> y las
                etiquetas de texto-truncado y stepper-progreso se comprueban
                literalmente, porque es la función que no puede perderse en el
                rediseño. Todo lo demás (cabecera, envoltorio, estado vacío) sí
                cambia.
            --}}
            <x-dashboard.panel titulo="Mis proyectos"
                subtitulo="Avance de cada trámite por su flujo de aprobación"
                icono="heroicon-o-list-bullet" :sinPadding="true">
                <div class="relative overflow-x-auto">
                    <table class="w-full min-w-[1100px] table-fixed text-left text-sm text-slate-500 dark:text-slate-400">
                        <colgroup>
                            <col class="w-[18%]">
                            <col class="w-[38%]">
                            <col class="w-[15%]">
                            <col class="w-[15%]">
                            <col class="w-[14%]">
                        </colgroup>
                        <thead class="border-b border-slate-100 bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500 dark:border-slate-800 dark:bg-slate-800/60 dark:text-slate-400">
                            <tr>
                                <th scope="col" class="px-5 py-3 font-semibold">Nombre</th>
                                <th scope="col" class="px-5 py-3 font-semibold">Progreso</th>
                                <th scope="col" class="px-5 py-3 font-semibold">Fecha inicio</th>
                                <th scope="col" class="px-5 py-3 font-semibold">Fecha finalización</th>
                                <th scope="col" class="px-5 py-3 font-semibold">Categoría</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse ($misFormularios as $formulario)
                                <tr class="transition hover:bg-slate-50 dark:hover:bg-slate-800/60">
                                    <th scope="row" class="min-w-0 px-5 py-4 text-left font-medium text-slate-900 dark:text-white">
                                        @if ($formulario['href'])
                                            <a href="{{ $formulario['href'] }}" wire:navigate class="hover:text-primary-600 dark:hover:text-primary-300">
                                                <x-dashboard.texto-truncado :texto="$formulario['nombre']" />
                                            </a>
                                        @else
                                            <x-dashboard.texto-truncado :texto="$formulario['nombre']" />
                                        @endif
                                    </th>
                                    <td class="px-5 py-4 align-top">
                                        @if (! empty($formulario['stepper']))
                                            @php
                                                $faseClase = match ($formulario['fase'] ?? 'Aprobación') {
                                                    'Informe Intermedio' => 'bg-sky-50 text-sky-700 dark:bg-sky-950 dark:text-sky-300',
                                                    'Informe Final' => 'bg-purple-50 text-purple-700 dark:bg-purple-950 dark:text-purple-300',
                                                    default => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
                                                };
                                            @endphp
                                            <span class="mb-1.5 inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide {{ $faseClase }}">
                                                {{ $formulario['fase'] ?? 'Aprobación' }}
                                            </span>
                                        @endif
                                        <x-dashboard.stepper-progreso :stepper="$formulario['stepper']" />
                                    </td>
                                    <td class="px-5 py-4">
                                        {{ $formulario['fecha_inicio'] ? \Carbon\Carbon::parse($formulario['fecha_inicio'])->isoFormat('D MMM YYYY') : '—' }}
                                    </td>
                                    <td class="px-5 py-4">
                                        {{ $formulario['fecha_fin'] ? \Carbon\Carbon::parse($formulario['fecha_fin'])->isoFormat('D MMM YYYY') : '—' }}
                                    </td>
                                    <td class="px-5 py-4">{{ $formulario['categoria'] ?: 'Sin categoría' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-10">
                                        <x-dashboard.estado-vacio
                                            titulo="Todavía no tienes trámites"
                                            mensaje="Cuando registres un proyecto aparecerá aquí con su avance."
                                            icono="heroicon-o-folder-open"
                                            :accionTexto="auth()->user()?->can('docente.crear-proyecto') ? 'Registrar el primero' : null"
                                            :accionHref="auth()->user()?->can('docente.crear-proyecto') ? route('selectorTipoAccion') : null"
                                        />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($hayMasFormularios)
                    <x-slot:pie>
                        <button type="button" wire:click="verMasFormularios"
                            class="text-xs font-semibold text-primary-600 hover:underline dark:text-primary-300">
                            Ver más trámites
                        </button>
                    </x-slot:pie>
                @endif
            </x-dashboard.panel>

            <x-dashboard.panel titulo="Mi actividad por mes"
                subtitulo="Trámites registrados a lo largo del tiempo" icono="heroicon-o-chart-bar">
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

            <x-dashboard.panel-expandible titulo="Mi aporte"
                subtitulo="Lo que suman tus proyectos" icono="heroicon-o-scale"
                :resumen="number_format($estudiantes['total']).' estudiantes'">
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <x-dashboard.kpi etiqueta="Personas alcanzadas" :valor="$poblacion['poblacion']" icono="heroicon-o-users" tono="acento" />
                    <x-dashboard.kpi etiqueta="Estudiantes" :valor="$estudiantes['total']" icono="heroicon-o-academic-cap" tono="info" />
                    <x-dashboard.kpi etiqueta="Horas" :valor="$esfuerzo['horas']" icono="heroicon-o-clock" tono="alerta" />
                    <x-dashboard.kpi etiqueta="Actividades" :valor="$esfuerzo['actividades']" icono="heroicon-o-list-bullet" tono="neutro" />
                </div>

                @if (! empty($categorias))
                    <div class="mt-5 border-t border-slate-100 pt-4 dark:border-slate-800">
                        <p class="mb-2 text-[11px] font-bold uppercase tracking-[0.1em] text-slate-500 dark:text-slate-400">Categorías de mis proyectos</p>
                        <x-dashboard.mosaico :items="$categorias" :filas="1" :alto="110" />
                    </div>
                @endif
            </x-dashboard.panel-expandible>

            @if ($coberturaOds['proyectos'] > 0)
                @php $odsLider = collect($ods)->sortByDesc('valor')->first(); @endphp
                <x-dashboard.panel-expandible titulo="Objetivos de Desarrollo Sostenible"
                    subtitulo="{{ $coberturaOds['categorias'] }} objetivos · {{ number_format($coberturaOds['proyectos']) }} proyectos"
                    icono="heroicon-o-globe-alt"
                    :resumen="$odsLider ? \Illuminate\Support\Str::limit($odsLider['etiqueta'], 22, '…').' · '.$odsLider['valor'] : null">
                    <x-dashboard.matriz-ods :items="$ods" />
                </x-dashboard.panel-expandible>
            @endif
        </main>

        {{--
            Barra lateral fija con la actividad: es lo que se consulta a diario,
            así que acompaña al scroll en vez de quedar al final de la página. Se
            pliega a una pestaña cuando estorba. El desplazamiento arranca bajo
            la barra superior del panel, que mide 3.5rem.
        --}}
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
                        vacio="Aún no hay movimientos en tus trámites." />
                </div>
            </div>
        </aside>
    </div>
</div>
