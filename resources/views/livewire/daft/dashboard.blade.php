<div class="min-h-full bg-[#f6f2ea] px-4 py-6 dark:bg-slate-950 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl space-y-6">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.34em] text-amber-600 dark:text-amber-400">Programas DAFT</p>
                    <h1 class="mt-2 text-3xl font-black tracking-tight text-[#062a52] dark:text-white">Panel de programas</h1>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Seguimiento de creación, revisión, subsanación y aprobación de programas.</p>
                </div>
                <div class="flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('daft.programas.create') }}" wire:navigate class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#062a52] px-5 py-3 text-sm font-bold text-white transition hover:bg-[#0b3c70]">
                        @svg('heroicon-o-plus', ['class' => 'h-5 w-5'])
                        Nuevo programa
                    </a>
                    <a href="{{ route('daft.bandeja-revision') }}" wire:navigate class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-[#062a52] transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800">
                        @svg('heroicon-o-clipboard-document-check', ['class' => 'h-5 w-5'])
                        Bandeja de revisión
                    </a>
                </div>
            </div>
        </section>

        @php
            $cards = [
                ['label' => 'Programas', 'value' => $metricas['total'], 'icon' => 'heroicon-o-academic-cap', 'accent' => 'text-[#062a52] dark:text-sky-300'],
                ['label' => 'En elaboración', 'value' => $metricas['elaboracion'], 'icon' => 'heroicon-o-pencil-square', 'accent' => 'text-amber-600 dark:text-amber-400'],
                ['label' => 'En revisión', 'value' => $metricas['revision'], 'icon' => 'heroicon-o-clipboard-document-list', 'accent' => 'text-blue-600 dark:text-blue-400'],
                ['label' => 'Subsanación', 'value' => $metricas['subsanacion'], 'icon' => 'heroicon-o-exclamation-triangle', 'accent' => 'text-orange-600 dark:text-orange-400'],
            ];
        @endphp

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            @foreach ($cards as $card)
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    @svg($card['icon'], ['class' => 'h-7 w-7 '.$card['accent']])
                    <p class="mt-5 text-3xl font-black text-[#062a52] dark:text-white">{{ $card['value'] }}</p>
                    <p class="mt-1 text-[11px] font-bold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">{{ $card['label'] }}</p>
                </article>
            @endforeach

            <article class="rounded-2xl bg-[#062a52] p-5 shadow-sm ring-1 ring-[#062a52]">
                @svg('heroicon-o-check-badge', ['class' => 'h-7 w-7 text-amber-400'])
                <p class="mt-5 text-3xl font-black text-white">{{ $metricas['aprobados'] }}</p>
                <p class="mt-1 text-[11px] font-bold uppercase tracking-[0.24em] text-slate-300">Aprobados</p>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.7fr_1fr]">
            <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-black text-[#062a52] dark:text-white">Actividad mensual</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Programas creados y aprobados durante los últimos meses.</p>
                    </div>
                    <div class="flex items-center gap-4 text-xs font-semibold text-slate-600 dark:text-slate-300">
                        <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-[#062a52] dark:bg-sky-400"></span>Creados</span>
                        <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>Aprobados</span>
                    </div>
                </div>

                @php($maxActividad = max(1, $actividad->max(fn ($mes) => max($mes['creados'], $mes['aprobados']))))
                <div class="mt-8 grid h-56 grid-cols-6 items-end gap-3 sm:gap-5">
                    @foreach ($actividad as $mes)
                        <div class="flex h-full min-w-0 flex-col justify-end">
                            <div class="flex flex-1 items-end justify-center gap-1 rounded-t-xl bg-[#f3efe7] px-1 dark:bg-slate-800 sm:gap-2 sm:px-2">
                                <div title="{{ $mes['creados'] }} creados" class="w-2/5 rounded-t-md bg-[#062a52] transition-all dark:bg-sky-500" style="height: {{ $mes['creados'] ? max(8, ($mes['creados'] / $maxActividad) * 100) : 3 }}%"></div>
                                <div title="{{ $mes['aprobados'] }} aprobados" class="w-2/5 rounded-t-md bg-amber-500 transition-all" style="height: {{ $mes['aprobados'] ? max(8, ($mes['aprobados'] / $maxActividad) * 100) : 3 }}%"></div>
                            </div>
                            <p class="mt-3 text-center text-[10px] font-bold text-slate-500 dark:text-slate-400">{{ $mes['label'] }}</p>
                        </div>
                    @endforeach
                </div>
            </article>

            <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-black text-[#062a52] dark:text-white">Revisiones pendientes</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Asignadas a tu rol activo.</p>
                    </div>
                    <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-full bg-amber-300 px-2 text-sm font-black text-[#062a52]">{{ $revisionesPendientes->count() }}</span>
                </div>

                <div class="mt-5 max-h-64 space-y-3 overflow-y-auto pr-1">
                    @forelse ($revisionesPendientes as $revision)
                        @php($estadoRevision = $revision->estado === 'PENDIENTE_ASIGNACION' ? 'Pendiente de asignación' : ucfirst(strtolower(str_replace('_', ' ', $revision->estado))))
                        @php($responsableRevision = $revision->asignadoUsuario?->name ?? $revision->responsableUsuario?->name)
                        <a href="{{ route('daft.bandeja-revision') }}" wire:navigate class="block rounded-2xl border border-slate-200 bg-[#faf8f4] p-4 transition hover:border-sky-300 hover:bg-sky-50 dark:border-slate-700 dark:bg-slate-800 dark:hover:border-sky-700 dark:hover:bg-slate-800/80">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-[#062a52] dark:text-white">{{ $revision->programa?->nombre ?? 'Programa sin nombre' }}</p>
                                    <p class="mt-1 truncate text-xs text-slate-500 dark:text-slate-400">{{ $revision->etapa_nombre }} · {{ $estadoRevision }}</p>
                                    <p class="mt-1 truncate text-xs text-slate-500 dark:text-slate-400">{{ $responsableRevision ? 'Responsable: '.$responsableRevision : 'Sin responsable asignado' }}</p>
                                </div>
                                @svg('heroicon-o-chevron-right', ['class' => 'mt-1 h-4 w-4 shrink-0 text-slate-400'])
                            </div>
                        </a>
                    @empty
                        <div class="rounded-2xl bg-[#faf8f4] px-4 py-8 text-center dark:bg-slate-800">
                            @svg('heroicon-o-check-circle', ['class' => 'mx-auto h-9 w-9 text-emerald-500'])
                            <p class="mt-3 text-sm font-semibold text-slate-600 dark:text-slate-300">No tienes revisiones pendientes.</p>
                        </div>
                    @endforelse
                </div>
            </article>
        </section>

        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-6 py-5 dark:border-slate-800">
                <div>
                    <h2 class="text-lg font-black text-[#062a52] dark:text-white">Programas recientes</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Últimos programas creados o modificados.</p>
                </div>
                <a href="{{ route('daft.programas') }}" wire:navigate class="rounded-full border border-slate-300 px-4 py-2 text-xs font-bold text-[#062a52] hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Ver todos</a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                    <thead class="bg-[#f3efe7] text-left dark:bg-slate-800/80">
                        <tr class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">
                            <th class="px-6 py-4">Código</th>
                            <th class="px-6 py-4">Programa</th>
                            <th class="px-6 py-4">Tipo</th>
                            <th class="px-6 py-4">Estado</th>
                            <th class="px-6 py-4 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($programasRecientes as $programa)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/60">
                                <td class="px-6 py-4 font-bold text-[#062a52] dark:text-sky-300">{{ $programa->codigo ?: 'S/C' }}</td>
                                <td class="px-6 py-4">
                                    <p class="font-bold text-slate-900 dark:text-white">{{ $programa->nombre }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ $programa->centroFacultad?->nombre ?? 'Sin centro' }}</p>
                                </td>
                                <td class="px-6 py-4 text-slate-600 dark:text-slate-300">{{ $programa->tipoPrograma?->nombre ?? $programa->tipo_programa ?? 'Sin tipo' }}</td>
                                <td class="px-6 py-4"><span class="rounded-full bg-blue-100 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-blue-800 dark:bg-blue-950 dark:text-blue-300">{{ str_replace('_', ' ', $programa->estado_flujo) }}</span></td>
                                <td class="px-6 py-4 text-right"><a href="{{ route('daft.programas.edit', $programa) }}" wire:navigate class="font-bold text-[#062a52] hover:underline dark:text-sky-300">Abrir</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-10 text-center text-slate-500 dark:text-slate-400">Todavía no hay programas registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
