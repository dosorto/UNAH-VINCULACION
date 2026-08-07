<div class="w-full space-y-5 px-3 py-5 sm:px-5 lg:px-6">
    <section class="flex flex-col gap-4 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-black uppercase tracking-[0.25em] text-cyan-700 dark:text-cyan-400">Revisión documental DAFT</p>
            <h1 class="mt-2 text-2xl font-extrabold text-slate-900 dark:text-white">Expediente del programa</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Consulta la información y los documentos antes de emitir una decisión.</p>
        </div>
        <a href="{{ route('daft.bandeja-revision') }}" wire:navigate class="inline-flex items-center justify-center gap-2 rounded-full border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
            @svg('heroicon-o-arrow-left', ['class' => 'h-4 w-4'])
            Volver a bandeja
        </a>
    </section>

    @if (session('programas_status'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300">{{ session('programas_status') }}</div>
    @endif
    @if (session('programas_warning'))
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-200">{{ session('programas_warning') }}</div>
    @endif

    <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_21rem]">
        <main class="min-w-0 space-y-5 rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800 sm:p-6">
            <header class="flex flex-col gap-4 border-b border-slate-200 pb-5 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary text-white shadow-lg shadow-primary/20">@svg('heroicon-o-document-text', ['class' => 'h-6 w-6'])</span>
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Programa de certificación</p>
                        <h2 class="mt-1 text-xl font-extrabold text-slate-900 dark:text-white">{{ $programa->nombre }}</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $programa->codigo }} · {{ $programa->tipoPrograma?->nombre ?? $programa->tipo_programa }}</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <div class="rounded-xl bg-amber-50 px-4 py-3 text-center dark:bg-amber-950/30"><p class="text-[9px] font-black uppercase tracking-widest text-amber-700 dark:text-amber-300">Estado</p><p class="mt-1 text-sm font-extrabold text-slate-900 dark:text-white">{{ str_replace('_', ' ', $programa->estado_flujo) }}</p></div>
                    <div class="rounded-xl bg-slate-100 px-4 py-3 text-center dark:bg-slate-800"><p class="text-[9px] font-black uppercase tracking-widest text-slate-500">Versión</p><p class="mt-1 text-sm font-extrabold text-slate-900 dark:text-white">V{{ $programa->version_actual }}</p></div>
                </div>
            </header>

            <div class="flex flex-wrap items-center gap-2 py-2">
                <span class="mr-1 text-[10px] font-black uppercase tracking-[0.24em] text-slate-500">Ruta</span>
                @foreach ($currentStages as $stage)
                    @php
                        $isCurrent = $stage->id === $programa->etapaActual()?->id;
                        $completed = $stage->estado === 'APROBADO';
                    @endphp
                    <div class="flex items-center gap-2">
                        <span title="{{ $stage->etapa_nombre }} · {{ str_replace('_', ' ', $stage->estado) }}" class="flex h-9 w-9 items-center justify-center rounded-full text-xs font-black text-white ring-4 {{ $completed ? 'bg-emerald-600 ring-emerald-100 dark:ring-emerald-950/50' : ($isCurrent ? 'bg-amber-500 ring-amber-100 dark:ring-amber-950/50' : 'bg-slate-400 ring-slate-100 dark:bg-slate-700 dark:ring-slate-800') }}">{{ $stage->orden }}</span>
                        @if (! $loop->last)<span class="h-px w-5 bg-slate-300 dark:bg-slate-700"></span>@endif
                    </div>
                @endforeach
            </div>

            <section>
                <div class="mb-4 bg-primary px-4 py-3 text-sm font-black uppercase tracking-[0.18em] text-white">I. Información general del programa</div>
                <div class="grid gap-px overflow-hidden rounded-xl bg-slate-200 ring-1 ring-slate-200 dark:bg-slate-700 dark:ring-slate-700 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ([
                        ['Unidad académica', $programa->centroFacultad?->nombre ?? 'Sin unidad'],
                        ['Código institucional', $programa->codigo],
                        ['Tipo de programa', $programa->tipoPrograma?->nombre ?? $programa->tipo_programa],
                        ['Versión en revisión', 'V'.$programa->version_actual],
                        ['Versión vigente', $programa->versiones->firstWhere('vigente', true)?->numero_version ? 'V'.$programa->versiones->firstWhere('vigente', true)->numero_version : 'Sin versión aprobada'],
                        ['Creado por', $programa->creadoPor?->name ?? 'Usuario institucional'],
                        ['Duración requerida por el tipo', $programa->tipoPrograma?->descripcionDuracion() ?? 'No definida'],
                        ['Total de horas del programa', $programa->horas_maximas_programa.' horas'],
                    ] as [$label, $value])
                        <div class="bg-white p-4 dark:bg-slate-900"><p class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">{{ $label }}</p><p class="mt-2 text-sm font-bold text-slate-900 dark:text-white">{{ $value }}</p></div>
                    @endforeach
                    <div class="bg-white p-4 dark:bg-slate-900 sm:col-span-2 lg:col-span-3"><p class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Descripción del programa</p><p class="mt-2 min-h-16 whitespace-pre-line text-sm leading-6 text-slate-700 dark:text-slate-300">{{ $programa->descripcion ?: 'Sin descripción registrada.' }}</p></div>
                </div>
            </section>

            <section>
                <div class="mb-4 flex items-center justify-between bg-slate-100 px-4 py-3 dark:bg-slate-800"><h3 class="text-xs font-black uppercase tracking-[0.2em] text-primary dark:text-sky-300">II. Centros regionales habilitados</h3><span class="rounded-full bg-amber-200 px-3 py-1 text-[9px] font-black uppercase tracking-wider text-amber-800">{{ $programa->centrosPrograma->count() }} centros</span></div>
                <div class="grid gap-3 sm:grid-cols-2">
                    @forelse ($programa->centrosPrograma as $centro)
                        <div class="flex items-center gap-3 rounded-xl border border-slate-200 p-4 dark:border-slate-700"><span class="flex h-9 w-9 items-center justify-center rounded-full bg-sky-100 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300">@svg('heroicon-o-building-office-2', ['class' => 'h-4 w-4'])</span><div><p class="text-sm font-bold text-slate-900 dark:text-white">{{ $centro->centroFacultad?->nombre }}</p><p class="mt-1 text-xs text-slate-500">{{ $centro->activo ? 'Habilitado' : 'Inactivo' }}</p></div></div>
                    @empty
                        <p class="text-sm text-slate-500">No hay centros regionales registrados.</p>
                    @endforelse
                </div>
            </section>

            <section>
                <div class="mb-4 flex items-center justify-between bg-slate-100 px-4 py-3 dark:bg-slate-800"><h3 class="text-xs font-black uppercase tracking-[0.2em] text-primary dark:text-sky-300">III. Asignaturas y descripciones mínimas</h3><span class="rounded-full bg-amber-200 px-3 py-1 text-[9px] font-black uppercase tracking-wider text-amber-800">{{ $programa->asignaturasPrograma->count() }} asignaturas</span></div>
                <div class="space-y-4">
                    @forelse ($programa->asignaturasPrograma as $programaAsignatura)
                        @php($asignatura = $programaAsignatura->asignatura)
                        <article class="rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <div class="flex flex-wrap gap-2"><span class="rounded-full bg-sky-100 px-2.5 py-1 text-[9px] font-black uppercase text-sky-700">Orden {{ $programaAsignatura->orden }}</span><span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[9px] font-black uppercase text-emerald-700">{{ $programaAsignatura->es_obligatoria ? 'Obligatoria' : 'Optativa' }}</span></div>
                                    <h4 class="mt-3 text-base font-extrabold text-slate-900 dark:text-white">{{ $asignatura?->nombre ?? 'Asignatura no disponible' }}</h4>
                                    <p class="mt-1 text-sm text-slate-500">{{ $asignatura?->codigo ?: 'S/C' }} · {{ $asignatura?->creditos_academicos ?? 0 }} créditos · {{ $asignatura?->horas_academicas ?? 0 }} horas</p>
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-xs"><div class="rounded-xl bg-slate-100 p-3 dark:bg-slate-800"><p class="text-[8px] font-black uppercase tracking-wider text-slate-500">Prerrequisitos</p><p class="mt-1 font-bold text-slate-800 dark:text-slate-200">{{ $asignatura?->prerrequisitos->pluck('codigo')->filter()->join(', ') ?: 'Sin prerrequisitos' }}</p></div><div class="rounded-xl bg-slate-100 p-3 dark:bg-slate-800"><p class="text-[8px] font-black uppercase tracking-wider text-slate-500">Documento</p><p class="mt-1 font-bold text-slate-800 dark:text-slate-200">{{ $asignatura?->ruta_documento_descripcion_minima ? 'Cargado' : 'No disponible' }}</p></div></div>
                            </div>
                            @if ($asignatura?->ruta_documento_descripcion_minima)
                                <div class="mt-4 flex flex-wrap gap-2 border-t border-slate-200 pt-4 dark:border-slate-700">
                                    <button type="button" wire:click="selectDocument({{ $programaAsignatura->id }})" class="inline-flex items-center gap-2 rounded-full bg-primary px-4 py-2 text-xs font-bold text-white">@svg('heroicon-o-eye', ['class' => 'h-4 w-4']) Abrir</button>
                                    <button type="button" wire:click="downloadDocument({{ $programaAsignatura->id }})" class="inline-flex items-center gap-2 rounded-full border border-slate-300 px-4 py-2 text-xs font-bold text-slate-700 dark:border-slate-700 dark:text-slate-200">@svg('heroicon-o-arrow-down-tray', ['class' => 'h-4 w-4']) Descargar</button>
                                </div>
                            @endif
                        </article>
                    @empty
                        <p class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500 dark:border-slate-700">No hay asignaturas registradas.</p>
                    @endforelse
                </div>

                @if ($selectedAssignment)
                    <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-stone-50 dark:border-slate-700 dark:bg-slate-950">
                        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3 dark:border-slate-700"><div><p class="text-xs font-bold text-slate-900 dark:text-white">Vista del documento</p><p class="mt-1 text-[11px] text-slate-500">{{ $selectedAssignment->asignatura?->codigo }} · {{ $selectedAssignment->asignatura?->nombre }}</p></div>@if ($documentUrl)<a href="{{ $documentUrl }}" target="_blank" rel="noopener" class="text-xs font-bold text-primary dark:text-sky-300">Abrir en otra pestaña</a>@endif</div>
                        @if ($documentUrl && $documentIsPdf)
                            <iframe src="{{ $documentUrl }}" title="Documento de {{ $selectedAssignment->asignatura?->nombre }}" class="h-[36rem] w-full bg-white"></iframe>
                        @elseif ($documentUrl)
                            <div class="flex min-h-64 items-center justify-center p-8 text-center"><div>@svg('heroicon-o-document-text', ['class' => 'mx-auto h-10 w-10 text-slate-400'])<p class="mt-3 text-sm text-slate-500">La vista previa está disponible para PDF. Usa “Abrir en otra pestaña” o “Descargar” para revisar este archivo.</p></div></div>
                        @else
                            <div class="flex min-h-64 items-center justify-center p-8 text-sm text-slate-500">El archivo registrado no se encuentra disponible.</div>
                        @endif
                    </div>
                @endif
            </section>
        </main>

        <aside class="space-y-5 xl:sticky xl:top-20">
            <section class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <p class="text-[10px] font-black uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Panel de decisión</p>
                <h2 class="mt-2 text-lg font-extrabold text-slate-900 dark:text-white">{{ $revision->etapa_nombre }}</h2>
                <p class="mt-2 text-sm leading-5 text-slate-500 dark:text-slate-400">Revisa el expediente académico completo antes de aprobar o solicitar correcciones.</p>

                @php($estadoEtapa = $revision->estado === 'PENDIENTE_ASIGNACION' ? 'Pendiente de asignación' : ucfirst(strtolower(str_replace('_', ' ', $revision->estado))))
                @php($responsableEtapa = $revision->asignadoUsuario?->name ?? $revision->responsableUsuario?->name)
                <div class="mt-5 rounded-2xl bg-slate-50 p-4 dark:bg-slate-800/60">
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Estado de la etapa</p>
                    <p class="mt-2 text-sm font-extrabold text-slate-900 dark:text-white">{{ $estadoEtapa }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $responsableEtapa ? 'Responsable: '.$responsableEtapa : 'Sin responsable asignado' }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $revision->rol_requerido ?: 'Sin rol específico' }}</p>
                </div>

                @if ($canAssign)
                    <div class="mt-5 space-y-3">
                        <label class="block space-y-2">
                            <span class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Responsable de la etapa</span>
                            <select wire:model="responsableSeleccionadoId" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                <option value="">Seleccione responsable</option>
                                @foreach ($eligibleReviewers as $reviewer)
                                    <option value="{{ $reviewer->id }}">{{ $reviewer->name }}{{ filled($reviewer->email) ? ' — '.$reviewer->email : '' }}</option>
                                @endforeach
                            </select>
                            @error('responsableSeleccionadoId')<p class="text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror
                        </label>
                        <button wire:click="assignResponsible" class="w-full rounded-full bg-cyan-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-cyan-700 disabled:opacity-60" @disabled($eligibleReviewers->isEmpty())>Asignar responsable</button>
                        @if ($eligibleReviewers->isEmpty())
                            <p class="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-700 dark:bg-amber-950/30 dark:text-amber-300">No hay usuarios elegibles para esta etapa.</p>
                        @endif
                    </div>
                @elseif ($canAct)
                    <label class="mt-5 block space-y-2"><span class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Observaciones</span><textarea wire:model="observaciones" rows="6" placeholder="Escribe observaciones o las correcciones requeridas" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"></textarea>@error('observaciones')<p class="text-xs font-medium text-rose-600">{{ $message }}</p>@enderror</label>
                    <div class="mt-5 space-y-3">
                        <button wire:click="approveRevision" wire:confirm="¿Confirmas que revisaste el expediente y deseas aprobar esta etapa?" class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-emerald-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-emerald-700">@svg('heroicon-o-check-badge', ['class' => 'h-5 w-5']) Aceptar y firmar etapa</button>
                        <button wire:click="rejectRevision" wire:confirm="¿Enviar el programa a subsanación con estas observaciones?" class="inline-flex w-full items-center justify-center gap-2 rounded-full border border-rose-300 px-4 py-3 text-sm font-bold text-rose-700 transition hover:bg-rose-50 dark:border-rose-800 dark:text-rose-300 dark:hover:bg-rose-950/30">@svg('heroicon-o-arrow-uturn-left', ['class' => 'h-5 w-5']) Enviar a subsanación</button>
                    </div>
                @else
                    <p class="mt-5 rounded-xl bg-slate-100 px-4 py-3 text-sm text-slate-600 dark:bg-slate-800 dark:text-slate-300">Esta etapa es de consulta o ya fue resuelta.</p>
                @endif
            </section>

            <section class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <p class="text-[10px] font-black uppercase tracking-[0.22em] text-slate-500">Historial</p><h2 class="mt-2 text-lg font-extrabold text-slate-900 dark:text-white">Actividad del programa</h2>
                <div class="mt-5 space-y-5">
                    @foreach ($activityFeed as $activity)
                        <article class="relative border-l-2 {{ $activity['tone'] === 'rose' ? 'border-rose-300' : ($activity['tone'] === 'emerald' ? 'border-emerald-300' : 'border-sky-300') }} pl-4"><span class="absolute -left-[7px] top-0 h-3 w-3 rounded-full {{ $activity['tone'] === 'rose' ? 'bg-rose-500' : ($activity['tone'] === 'emerald' ? 'bg-emerald-500' : 'bg-sky-500') }} ring-4 ring-white dark:ring-slate-900"></span><p class="text-sm font-bold text-slate-900 dark:text-white">{{ $activity['title'] }}</p><p class="mt-1 text-xs leading-5 text-slate-500">{{ $activity['description'] }}</p><time class="mt-1 block text-[10px] font-semibold text-slate-400">{{ optional($activity['at'])->format('d/m/Y · h:i A') }}</time></article>
                    @endforeach
                </div>
            </section>
        </aside>
    </div>
</div>
