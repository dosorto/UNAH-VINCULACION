<div
    x-data="{ activityOpen: true, tab: 'asignaturas' }"
    class="w-full space-y-5 px-2 py-4 sm:px-3 lg:px-3 xl:px-4">
    @php
        $isEditableState = ! $programa || $programa->estaEditable();
    @endphp

    <section class="rounded-2xl bg-white px-5 py-4 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-cyan-700 dark:text-cyan-400">Programas</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ $programa ? 'Edición del programa' : 'Nuevo programa' }}</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    {{ $programa ? 'Consulta y configura el programa antes de enviarlo a revisión.' : 'Registra los datos base del nuevo programa.' }}
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('daft.programas') }}" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-700 dark:text-slate-200">Volver</a>
                @if (! $programa)
                    <button wire:click="savePrograma" class="rounded-full bg-primary px-4 py-2 text-sm font-medium text-white">Crear programa</button>
                @elseif ($isEditableState)
                    <button
                        wire:click="openSendReviewModal"
                        @disabled(! $draftSetup['ready_for_review'])
                        title="{{ $draftSetup['ready_for_review'] ? 'Enviar programa a revisión' : 'Debes completar asignaturas, centros y horas válidas antes de enviar a revisión.' }}"
                        class="rounded-full px-4 py-2 text-sm font-semibold text-white shadow-sm transition {{ $draftSetup['ready_for_review'] ? 'bg-amber-500 shadow-amber-500/20 hover:bg-amber-600' : 'cursor-not-allowed bg-slate-300 shadow-none dark:bg-slate-700' }}">
                        Enviar a revisión
                    </button>
                    <button wire:click="deleteDraft" wire:confirm="¿Deseas borrar este programa? Esta acción no se puede deshacer." class="rounded-full border border-rose-300 px-4 py-2 text-sm font-medium text-rose-700 dark:border-rose-800 dark:text-rose-300">Borrar</button>
                @endif
            </div>
        </div>

        @if ($programa)
            <div class="mt-5 border-t border-slate-200 pt-5 dark:border-slate-800">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Flujo de revisión</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Seguimiento cronológico del programa.</p>
                    </div>
                    <div class="flex flex-col items-start gap-3 xl:items-end">
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ $timelineProgress }}</span>
                        <div class="relative z-10 flex flex-wrap items-center gap-2">
                            <span class="text-[10px] font-black uppercase tracking-[0.28em] text-slate-500 dark:text-slate-400">Ruta</span>
                            @foreach ($timelineEntries as $index => $entry)
                                <div
                                    x-data="{ tooltipOpen: false }"
                                    @mouseenter="tooltipOpen = true"
                                    @mouseleave="tooltipOpen = false"
                                    @focusin="tooltipOpen = true"
                                    @focusout="tooltipOpen = false"
                                    class="relative flex items-center gap-2">
                                    <button type="button"
                                        aria-label="Etapa {{ $index + 1 }}: {{ $entry['title'] }}. {{ $entry['status'] }}"
                                        class="flex h-9 w-9 items-center justify-center rounded-full text-xs font-black shadow-sm transition
                                            {{ $entry['tone'] === 'emerald' ? 'bg-emerald-600 text-white ring-4 ring-emerald-100 dark:bg-emerald-500 dark:ring-emerald-950/40' : '' }}
                                            {{ $entry['tone'] === 'sky' ? 'bg-sky-600 text-white ring-4 ring-sky-100 dark:bg-sky-500 dark:ring-sky-950/40' : '' }}
                                            {{ $entry['tone'] === 'rose' ? 'bg-rose-600 text-white ring-4 ring-rose-100 dark:bg-rose-500 dark:ring-rose-950/40' : '' }}
                                            {{ $entry['tone'] === 'slate' ? 'bg-slate-400 text-white ring-4 ring-slate-100 dark:bg-slate-700 dark:ring-slate-900/50' : '' }}">
                                        {{ $index + 1 }}
                                    </button>
                                    <div
                                        x-cloak
                                        x-show="tooltipOpen"
                                        x-transition.opacity.duration.150ms
                                        class="absolute bottom-full right-0 z-50 mb-3 w-72 rounded-2xl border border-slate-200 bg-white p-4 text-left shadow-2xl dark:border-slate-700 dark:bg-slate-900">
                                        <p class="text-[10px] font-black uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Etapa {{ $index + 1 }} · {{ $entry['status'] }}</p>
                                        <p class="mt-2 text-sm font-extrabold text-slate-900 dark:text-white">{{ $entry['title'] }}</p>
                                        <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">{{ $entry['description'] }}</p>
                                        @if ($entry['assignee'])
                                            <p class="mt-3 text-xs font-semibold text-slate-600 dark:text-slate-300">Asignado a: {{ $entry['assignee'] }}</p>
                                        @endif
                                        @if ($entry['cycle'])
                                            <p class="mt-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">Ciclo de revisión {{ $entry['cycle'] }}</p>
                                        @endif
                                    </div>
                                    @if (! $loop->last)
                                        <span class="h-px w-4 bg-slate-300 dark:bg-slate-700"></span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </section>

    @if (session('programas_status'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300">{{ session('programas_status') }}</div>
    @endif

    @if ($currentTipoPrograma)
        <section class="rounded-2xl border border-primary/15 bg-gradient-to-r from-primary to-primary-container px-5 py-4 text-white shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-[11px] font-black uppercase tracking-[0.28em] text-white/70">Plantilla obligatoria</p>
                    <h2 class="mt-1 text-lg font-extrabold">{{ $currentTipoPrograma->nombre }}</h2>
                    <p class="mt-1 text-sm text-white/80">Para construir este programa debes usar el formato establecido para el tipo seleccionado.</p>
                </div>
                @if ($programa && $currentTipoPrograma->plantilla_docx_path)
                    <button wire:click="downloadTemplate" wire:loading.attr="disabled" wire:target="downloadTemplate" class="inline-flex items-center justify-center gap-2 rounded-full bg-white px-5 py-3 text-sm font-extrabold text-primary shadow-sm transition hover:bg-slate-100 disabled:opacity-60">
                        @svg('heroicon-o-arrow-down-tray', ['class' => 'h-5 w-5'])
                        <span wire:loading.remove wire:target="downloadTemplate">Descargar formato {{ $currentTipoPrograma->nombre }}</span>
                        <span wire:loading wire:target="downloadTemplate">Preparando descarga...</span>
                    </button>
                @else
                    <span class="inline-flex items-center justify-center rounded-full border border-white/30 px-5 py-3 text-sm font-semibold text-white/70">Formato no disponible</span>
                @endif
            </div>
        </section>
    @endif

    @if (! $programa)
        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
            <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Datos básicos</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <label class="space-y-2">
                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Centro / facultad principal <span class="text-red-500">*</span></span>
                    <select wire:model="programaForm.centro_facultad_id" required class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        <option value="">Seleccione</option>
                        @foreach ($centrosFacultad as $centro)
                            <option value="{{ $centro->id }}">{{ $centro->nombre }}</option>
                        @endforeach
                    </select>
                    @error('programaForm.centro_facultad_id')<p class="text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror
                </label>
                <label class="space-y-2">
                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Código <span class="text-red-500">*</span></span>
                    <input wire:model="programaForm.codigo" type="text" required class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" />
                    @error('programaForm.codigo')<p class="text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror
                </label>
                <label class="space-y-2">
                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Tipo de programa <span class="text-red-500">*</span></span>
                    <select wire:model.live="programaForm.tipo_programa_id" required class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        <option value="">Seleccione</option>
                        @foreach ($tiposPrograma as $tipo)
                            <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                        @endforeach
                    </select>
                    @error('programaForm.tipo_programa_id')<p class="text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror
                </label>
                <label class="space-y-2 md:col-span-2">
                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Nombre <span class="text-red-500">*</span></span>
                    <input wire:model="programaForm.nombre" type="text" required class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" />
                    @error('programaForm.nombre')<p class="text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror
                </label>
                <label class="space-y-2 md:col-span-2">
                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Descripción</span>
                    <textarea wire:model="programaForm.descripcion" rows="4" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"></textarea>
                    @error('programaForm.descripcion')<p class="text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror
                </label>
            </div>
        </section>
    @else
        <div class="relative overflow-hidden rounded-[30px] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 lg:flex">
            <section class="min-w-0 flex-1 bg-transparent p-5 lg:p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex-1">
                        <div class="grid gap-3 md:grid-cols-6">
                            <div class="rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/70"><div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Versión actual</div><div class="mt-2 text-sm font-semibold text-slate-900 dark:text-slate-100">V{{ $programa->version_actual }}</div></div>
                            <div class="rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/70"><div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Versión vigente</div><div class="mt-2 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $programa->versiones->firstWhere('vigente', true) ? 'V'.$programa->versiones->firstWhere('vigente', true)->numero_version : 'Sin versión aprobada' }}</div></div>
                            <div class="rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/70"><div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Estado</div><div class="mt-2"><span class="rounded-full bg-amber-100 px-3 py-1.5 text-xs font-bold uppercase tracking-[0.22em] text-amber-800 dark:bg-amber-950/40 dark:text-amber-300">{{ str_replace('_', ' ', $programa->estado_flujo) }}</span></div></div>
                            <div class="rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/70"><div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Código</div><div class="mt-2 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $programa->codigo ?: 'Sin código' }}</div></div>
                            <div class="rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/70"><div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Nombre</div><div class="mt-2 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $programa->nombre }}</div></div>
                            <div class="rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/70"><div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Tipo</div><div class="mt-2 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $programa->tipoPrograma?->nombre ?? $programa->tipo_programa ?? 'Sin tipo' }}</div></div>
                            <div class="rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/70"><div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Rango del tipo</div><div class="mt-2 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $hoursStatus['range_label'] }}</div></div>
                            <div class="rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/70"><div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Horas del programa</div><div class="mt-2 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ (int) $programa->horas_maximas_programa }} horas</div></div>
                            <div class="rounded-xl border px-4 py-3 {{ $hoursStatus['in_range'] ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-900/40 dark:bg-emerald-950/20' : 'border-rose-200 bg-rose-50 dark:border-rose-900/40 dark:bg-rose-950/20' }}"><div class="text-[11px] font-semibold uppercase tracking-[0.18em] {{ $hoursStatus['in_range'] ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300' }}">Horas acumuladas</div><div class="mt-2 text-sm font-semibold {{ $hoursStatus['in_range'] ? 'text-emerald-900 dark:text-emerald-100' : 'text-rose-900 dark:text-rose-100' }}">{{ $hoursStatus['total'] }} horas</div><div class="mt-1 text-xs {{ $hoursStatus['in_range'] ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300' }}">{{ $hoursStatus['message'] }}</div></div>
                            <div class="rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/70"><div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Unidad académica</div><div class="mt-2 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $programa->centroFacultad?->nombre ?? 'Sin unidad' }}</div></div>
                            <div class="rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/70"><div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Estado operativo</div><div class="mt-2 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ (int) $programa->estado === 1 ? 'Activo' : 'En construcción' }}</div></div>
                        </div>
                        <div class="mt-3 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600 dark:bg-slate-800/70 dark:text-slate-300">{{ $programa->descripcion ?: 'Sin descripción registrada.' }}</div>
                    </div>
                    @if ($isEditableState)
                        <div class="flex shrink-0 items-start">
                            <button wire:click="openEditProgramModal" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-700 dark:text-slate-200">Editar programa</button>
                        </div>
                    @endif
                </div>

                <div class="mt-5 border-t border-slate-200 pt-5 dark:border-slate-800">
                    @if ($isEditableState)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/40">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                <div>
                                    <p class="text-[11px] font-black uppercase tracking-[0.28em] text-slate-500 dark:text-slate-400">Construcción guiada</p>
                                    <h2 class="mt-1 text-base font-semibold text-slate-900 dark:text-slate-100">Completa los pasos obligatorios antes de enviar el programa a revisión</h2>
                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Paso 1: define las asignaturas. Paso 2: selecciona los centros regionales donde podrá aperturarse.</p>
                                </div>
                                <div class="shrink-0 rounded-full px-4 py-2 text-sm font-semibold {{ $draftSetup['ready_for_review'] ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300' }}">{{ $draftSetup['ready_for_review'] ? 'Programa listo para revisión' : 'Faltan pasos obligatorios' }}</div>
                            </div>

                            <div class="mt-4 grid gap-3 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,1.15fr)_minmax(240px,0.7fr)]">
                                <button
                                    type="button"
                                    @click="tab = 'asignaturas'"
                                    :class="tab === 'asignaturas'
                                        ? 'border-primary bg-slate-100 shadow-sm dark:border-sky-500 dark:bg-slate-800'
                                        : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-slate-600 dark:hover:bg-slate-800/70'"
                                    class="flex min-h-20 items-center gap-4 rounded-2xl border px-4 py-3 text-left transition">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-sm font-bold text-amber-700 dark:bg-amber-950/50 dark:text-amber-300">1</span>
                                    <span class="min-w-0">
                                        <span class="flex flex-wrap items-center gap-2">
                                            <span class="text-sm font-bold text-slate-900 dark:text-slate-100">Definir asignaturas</span>
                                            <span class="rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-bold uppercase text-slate-600 dark:bg-slate-700 dark:text-slate-300">
                                                {{ count($asignaturas) }} {{ count($asignaturas) === 1 ? 'registrada' : 'registradas' }}
                                            </span>
                                        </span>
                                        <span class="mt-1 block text-sm text-slate-500 dark:text-slate-400">Agrega la estructura académica del programa.</span>
                                    </span>
                                </button>

                                <button
                                    type="button"
                                    @click="tab = 'centros'"
                                    :class="tab === 'centros'
                                        ? 'border-primary bg-slate-100 shadow-sm dark:border-sky-500 dark:bg-slate-800'
                                        : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-slate-600 dark:hover:bg-slate-800/70'"
                                    class="flex min-h-20 items-center gap-4 rounded-2xl border px-4 py-3 text-left transition">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-sm font-bold text-amber-700 dark:bg-amber-950/50 dark:text-amber-300">2</span>
                                    <span class="min-w-0">
                                        <span class="flex flex-wrap items-center gap-2">
                                            <span class="text-sm font-bold text-slate-900 dark:text-slate-100">Definir centros regionales</span>
                                            <span class="rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-bold uppercase text-slate-600 dark:bg-slate-700 dark:text-slate-300">
                                                {{ count($centros) }} {{ count($centros) === 1 ? 'seleccionado' : 'seleccionados' }}
                                            </span>
                                        </span>
                                        <span class="mt-1 block text-sm text-slate-500 dark:text-slate-400">Indica en qué centros puede aperturarse.</span>
                                    </span>
                                </button>

                                <button
                                    type="button"
                                    @click="tab = 'auditoria'"
                                    :class="tab === 'auditoria'
                                        ? 'border-primary bg-slate-100 shadow-sm dark:border-sky-500 dark:bg-slate-800'
                                        : 'border-slate-300 bg-transparent hover:border-slate-400 hover:bg-white dark:border-slate-700 dark:hover:border-slate-600 dark:hover:bg-slate-900'"
                                    class="flex min-h-20 items-center gap-4 rounded-2xl border border-dashed px-4 py-3 text-left transition">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300">
                                        @svg('heroicon-o-clock', ['class' => 'h-5 w-5'])
                                    </span>
                                    <span>
                                        <span class="block text-sm font-bold text-slate-900 dark:text-slate-100">Auditoría</span>
                                        <span class="mt-1 block text-sm text-slate-500 dark:text-slate-400">Consulta el historial y los movimientos del programa.</span>
                                    </span>
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-4 dark:border-slate-800">
                            <div class="flex flex-wrap gap-2">
                                <button @click="tab = 'asignaturas'" :class="tab === 'asignaturas' ? 'bg-primary text-white' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200'" class="rounded-full px-4 py-2 text-sm font-medium transition">Asignaturas <span class="ml-2 rounded-full bg-white/20 px-2 py-0.5 text-xs">{{ count($asignaturas) }}</span></button>
                                <button @click="tab = 'centros'" :class="tab === 'centros' ? 'bg-primary text-white' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200'" class="rounded-full px-4 py-2 text-sm font-medium transition">Centros regionales <span class="ml-2 rounded-full bg-white/20 px-2 py-0.5 text-xs">{{ count($centros) }}</span></button>
                                <button @click="tab = 'auditoria'" :class="tab === 'auditoria' ? 'bg-primary text-white' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200'" class="rounded-full px-4 py-2 text-sm font-medium transition">Auditoría</button>
                            </div>
                        </div>
                    @endif
                </div>

                <div x-show="tab === 'asignaturas'" x-transition class="pt-5">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                        <div><h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Asignaturas del programa</h2><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Agrega asignaturas existentes o crea nuevas para este programa.</p></div>
                    </div>

                    @if ($isEditableState)
                        <div class="mt-4 grid gap-3 lg:grid-cols-[minmax(0,1fr),auto,auto]">
                            <select wire:model="selectedAsignaturaId" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                <option value="">Seleccione o busque una asignatura</option>
                                @foreach ($asignaturasDisponibles as $asignatura)
                                    <option value="{{ $asignatura->id }}">{{ $asignatura->codigo ?: 'S/C' }} · {{ $asignatura->nombre }}</option>
                                @endforeach
                            </select>
                            <button wire:click="addAsignatura" class="rounded-full bg-primary px-4 py-2 text-sm font-medium text-white">Agregar</button>
                            <button wire:click="openCreateAsignaturaModal" class="rounded-full border border-slate-300 px-4 py-2 text-center text-sm font-medium text-slate-700 dark:border-slate-700 dark:text-slate-200">Crear asignatura</button>
                        </div>
                        @error('selectedAsignaturaId')<p class="mt-2 text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror

                        <div class="mt-4 rounded-2xl border px-4 py-3 {{ $hoursStatus['in_range'] ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-900/40 dark:bg-emerald-950/20' : 'border-rose-200 bg-rose-50 dark:border-rose-900/40 dark:bg-rose-950/20' }}">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div><p class="text-[11px] font-black uppercase tracking-[0.22em] {{ $hoursStatus['in_range'] ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300' }}">Control de horas</p><p class="mt-1 text-sm font-semibold {{ $hoursStatus['in_range'] ? 'text-emerald-900 dark:text-emerald-100' : 'text-rose-900 dark:text-rose-100' }}">Total acumulado: {{ $hoursStatus['total'] }} horas</p><p class="mt-1 text-xs {{ $hoursStatus['in_range'] ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300' }}">Rango permitido para el tipo: {{ $hoursStatus['range_label'] }}</p></div>
                                <div class="text-right"><p class="text-[11px] font-black uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Horas registradas</p><p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ (int) $programa->horas_maximas_programa }} horas</p></div>
                            </div>
                        </div>
                    @endif

                    <div class="mt-4 overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                                <thead class="bg-slate-50 dark:bg-slate-800/70">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Orden</th>
                                        <th class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Código</th>
                                        <th class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Asignatura</th>
                                        <th class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Horas</th>
                                        <th class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Requisitos</th>
                                        <th class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Tipo</th>
                                        <th class="px-3 py-2 text-right text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Acción</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-900">
                                    @forelse ($asignaturas as $index => $asignatura)
                                        <tr>
                                            <td class="px-3 py-3"><input type="number" min="1" wire:model.live.debounce.500ms="asignaturas.{{ $index }}.orden" @disabled(!$isEditableState) class="w-20 rounded-lg border-slate-300 text-sm disabled:bg-slate-100 disabled:text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:disabled:bg-slate-900"></td>
                                            <td class="px-3 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $asignatura['codigo'] ?: 'S/C' }}</td>
                                            <td class="px-3 py-3">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ $asignatura['nombre'] }}</span>
                                                    @if ($isEditableState)
                                                        <button type="button" wire:click="openEditAsignaturaModal({{ $asignatura['asignatura_id'] }})" aria-label="Editar {{ $asignatura['nombre'] }}" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-slate-300 text-slate-500 transition hover:border-primary hover:text-primary dark:border-slate-700 dark:text-slate-300">
                                                            @svg('heroicon-o-pencil', ['class' => 'h-4 w-4'])
                                                        </button>
                                                    @endif
                                                </div>
                                                <span class="mt-1 inline-flex rounded-full bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-500 dark:bg-slate-800 dark:text-slate-300">Sin aprobación previa</span>
                                            </td>
                                            <td class="px-3 py-3 text-sm text-slate-600 dark:text-slate-300">{{ (int) ($asignatura['horas_academicas'] ?? 0) }} h</td>
                                            <td class="px-3 py-3">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-sm text-slate-600 dark:text-slate-300">
                                                        @if (empty($asignatura['prerrequisitos']))
                                                            Sin requisitos
                                                        @else
                                                            {{ collect($asignatura['prerrequisitos'])->map(fn ($item) => $item['codigo'] ?: $item['nombre'])->join(', ') }}
                                                        @endif
                                                    </span>
                                                    @if ($isEditableState)
                                                        <button type="button" wire:click="openPrerequisitosModal({{ $asignatura['asignatura_id'] }})" aria-label="Editar requisitos de {{ $asignatura['nombre'] }}" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-slate-300 text-slate-500 transition hover:border-primary hover:text-primary dark:border-slate-700 dark:text-slate-300">
                                                            @svg('heroicon-o-pencil', ['class' => 'h-4 w-4'])
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-3 py-3">
                                                <label class="inline-flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                                                    <input type="checkbox" wire:model.live="asignaturas.{{ $index }}.es_obligatoria" @disabled(!$isEditableState) class="rounded border-slate-300 text-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800">
                                                    Obligatoria
                                                </label>
                                            </td>
                                            <td class="px-3 py-3 text-right">@if ($isEditableState)<button wire:click="removeAsignatura({{ $index }})" class="text-xs font-semibold text-rose-600">Quitar</button>@endif</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="px-3 py-8 text-center text-sm text-slate-500 dark:text-slate-400">No hay asignaturas agregadas.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div x-show="tab === 'centros'" x-transition class="pt-5">
                    <div><h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Centros regionales</h2><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Selecciona los centros donde podrá aperturarse el programa.</p></div>
                    @if ($isEditableState)
                        <div class="mt-4 grid gap-3 lg:grid-cols-[minmax(0,1fr),auto]">
                            <select wire:model="selectedCentroId" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                <option value="">Seleccione un centro regional</option>
                                @foreach ($centrosFacultad as $centro)
                                    <option value="{{ $centro->id }}">{{ $centro->nombre }}</option>
                                @endforeach
                            </select>
                            <button wire:click="addCentro" class="rounded-full bg-primary px-4 py-2 text-sm font-medium text-white">Agregar centro</button>
                        </div>
                        @error('selectedCentroId')<p class="mt-2 text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror
                    @endif
                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                        @forelse ($centros as $index => $centro)
                            <div class="flex items-center justify-between rounded-2xl border border-slate-200 p-4 dark:border-slate-800">
                                <div><p class="font-semibold text-slate-900 dark:text-slate-100">{{ $centro['nombre'] }}</p><p class="text-xs text-slate-500 dark:text-slate-400">{{ $centro['activo'] ? 'Activo' : 'Inactivo' }}</p></div>
                                @if ($isEditableState)<button wire:click="removeCentro({{ $index }})" class="text-xs font-semibold text-rose-600">Quitar</button>@endif
                            </div>
                        @empty
                            <div class="rounded-2xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">No hay centros seleccionados.</div>
                        @endforelse
                    </div>
                </div>

                <div x-show="tab === 'auditoria'" x-transition class="pt-5">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Auditoría</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($activityFeed as $activity)
                            <div class="rounded-2xl border border-slate-200 p-4 dark:border-slate-800">
                                <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $activity['title'] }}</p>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $activity['description'] }}</p>
                                <p class="mt-2 text-xs text-slate-400">{{ optional($activity['at'])->format('d/m/Y H:i') }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500 dark:text-slate-400">Sin movimientos registrados.</p>
                        @endforelse
                    </div>
                </div>
            </section>

            <aside
                :class="activityOpen ? 'lg:w-80' : 'lg:w-16'"
                class="relative w-full shrink-0 border-t border-slate-200 bg-slate-50/60 transition-[width] duration-300 ease-in-out dark:border-slate-800 dark:bg-slate-950/30 lg:border-l lg:border-t-0">
                <div x-show="activityOpen" x-transition.opacity.duration.200ms>
                    <div class="flex items-start justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.24em] text-cyan-700 dark:text-cyan-400">Actividad</p>
                            <h2 class="mt-1 text-base font-extrabold text-slate-900 dark:text-white">Programa</h2>
                        </div>
                        <button @click="activityOpen = false" type="button" aria-label="Ocultar actividad" class="flex h-8 w-8 items-center justify-center rounded-full border border-slate-300 text-slate-500 hover:bg-white dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                            @svg('heroicon-o-x-mark', ['class' => 'h-4 w-4'])
                        </button>
                    </div>
                    <div class="border-b border-slate-200 p-5 dark:border-slate-800">
                        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                            <p class="text-sm font-extrabold text-slate-900 dark:text-white">{{ $programa->codigo ?: 'Sin código' }}</p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $programa->nombre }}</p>
                        </div>
                    </div>
                    <div class="max-h-[620px] space-y-3 overflow-y-auto p-5">
                        @forelse ($activityFeed as $activity)
                            <article class="relative rounded-2xl border border-slate-200 bg-white p-4 pl-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                                <span class="absolute -left-1.5 top-5 h-3 w-3 rounded-full bg-sky-500 ring-4 ring-sky-100 dark:ring-sky-950"></span>
                                <div class="flex items-start justify-between gap-3">
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ $activity['title'] }}</p>
                                    <time class="shrink-0 text-[9px] font-bold uppercase tracking-wider text-slate-400">{{ optional($activity['at'])->format('d/m H:i') }}</time>
                                </div>
                                <p class="mt-2 text-xs leading-5 text-slate-500 dark:text-slate-400">{{ $activity['description'] }}</p>
                            </article>
                        @empty
                            <p class="rounded-xl bg-white p-4 text-center text-sm text-slate-500 dark:bg-slate-900 dark:text-slate-400">Sin movimientos registrados.</p>
                        @endforelse
                    </div>
                </div>

                <button
                    x-cloak
                    x-show="!activityOpen"
                    x-transition.opacity.duration.200ms
                    @click="activityOpen = true"
                    type="button"
                    aria-label="Mostrar actividad"
                    class="flex w-full items-center justify-center gap-3 px-4 py-3 text-slate-500 transition hover:bg-white hover:text-primary dark:text-slate-400 dark:hover:bg-slate-900 dark:hover:text-sky-300 lg:absolute lg:inset-0 lg:flex-col lg:justify-start lg:gap-8 lg:px-0 lg:py-8">
                    @svg('heroicon-o-clock', ['class' => 'h-6 w-6 shrink-0'])
                    <span class="text-xs font-black uppercase tracking-[0.3em] lg:[writing-mode:vertical-rl] lg:rotate-180">Actividad</span>
                </button>
            </aside>
        </div>
    @endif

    @if ($editProgramModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 px-4 py-6" wire:click.self="closeEditProgramModal">
            <section class="w-full max-w-3xl rounded-3xl bg-white p-6 shadow-2xl dark:bg-slate-900">
                <div class="flex items-start justify-between border-b border-slate-200 pb-4 dark:border-slate-800">
                    <div><h2 class="text-xl font-bold text-slate-900 dark:text-slate-100">Editar programa</h2><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Actualiza los datos generales del programa.</p></div>
                    <button wire:click="closeEditProgramModal" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:text-slate-200">Cerrar</button>
                </div>
                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <label class="space-y-2"><span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Centro / facultad principal <span class="text-red-500">*</span></span><select wire:model="programaForm.centro_facultad_id" required class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"><option value="">Seleccione</option>@foreach ($centrosFacultad as $centro)<option value="{{ $centro->id }}">{{ $centro->nombre }}</option>@endforeach</select>@error('programaForm.centro_facultad_id')<p class="text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror</label>
                    <label class="space-y-2"><span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Código <span class="text-red-500">*</span></span><input wire:model="programaForm.codigo" type="text" required class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" />@error('programaForm.codigo')<p class="text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror</label>
                    <label class="space-y-2"><span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Tipo de programa <span class="text-red-500">*</span></span><select wire:model.live="programaForm.tipo_programa_id" required class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"><option value="">Seleccione</option>@foreach ($tiposPrograma as $tipo)<option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>@endforeach</select>@error('programaForm.tipo_programa_id')<p class="text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror</label>
                    <label class="space-y-2 md:col-span-2"><span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Nombre <span class="text-red-500">*</span></span><input wire:model="programaForm.nombre" type="text" required class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" />@error('programaForm.nombre')<p class="text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror</label>
                    <label class="space-y-2 md:col-span-2"><span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Descripción</span><textarea wire:model="programaForm.descripcion" rows="4" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"></textarea>@error('programaForm.descripcion')<p class="text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror</label>
                </div>
                <div class="mt-6 flex justify-end gap-3 border-t border-slate-200 pt-4 dark:border-slate-800">
                    <button wire:click="closeEditProgramModal" class="rounded-2xl border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:text-slate-200">Cancelar</button>
                    <button wire:click="savePrograma" class="rounded-2xl bg-primary px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-container">Guardar cambios</button>
                </div>
            </section>
        </div>
    @endif

    @if ($showSendReviewModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/55 px-4 py-6 backdrop-blur-[1px]" wire:click.self="closeSendReviewModal">
            <section role="dialog" aria-modal="true" aria-labelledby="send-review-title" class="max-h-[calc(100vh-3rem)] w-full max-w-2xl overflow-y-auto rounded-3xl bg-white p-6 shadow-2xl dark:bg-slate-900 sm:p-8">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.24em] text-cyan-700 dark:text-cyan-400">Flujo DAFT</p>
                        <h2 id="send-review-title" class="mt-2 text-2xl font-bold text-slate-900 dark:text-slate-100">Seleccionar destinatarios</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">El flujo indica que el emisor debe elegir quién recibirá el programa en las siguientes etapas.</p>
                    </div>
                    <button type="button" wire:click="closeSendReviewModal" aria-label="Cerrar" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-slate-300 text-slate-500 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                        @svg('heroicon-o-x-mark', ['class' => 'h-5 w-5'])
                    </button>
                </div>

                <form wire:submit.prevent="sendToReview">
                    <div class="mt-7 space-y-4">
                        @foreach ($reviewRecipientStages as $stage)
                            <div wire:key="review-recipient-stage-{{ $stage['id'] }}" class="rounded-2xl border border-slate-200 p-5 dark:border-slate-700">
                                <div class="flex items-start gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-sky-100 text-sm font-black text-sky-700 dark:bg-sky-950/50 dark:text-sky-300">{{ $stage['order'] }}</span>
                                    <div>
                                        <h3 class="font-bold text-slate-900 dark:text-white">{{ $stage['name'] }}</h3>
                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Rol requerido: {{ $stage['role'] }}</p>
                                    </div>
                                </div>

                                <label class="mt-4 block space-y-2">
                                    <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">Usuario destinatario <span class="text-rose-500">*</span></span>
                                    <select wire:model="reviewRecipients.{{ $stage['id'] }}" required class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                        <option value="">Seleccione un usuario</option>
                                        @foreach ($stage['users'] as $user)
                                            <option value="{{ $user['id'] }}">{{ $user['name'] }}{{ filled($user['email']) ? ' — '.$user['email'] : '' }}{{ ! $user['active_role'] ? ' — debe activar este rol' : '' }}</option>
                                        @endforeach
                                    </select>
                                    @error('reviewRecipients.'.$stage['id'])<p class="text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror
                                </label>

                                @if (empty($stage['users']))
                                    <p class="mt-3 rounded-xl bg-amber-50 px-3 py-2 text-xs font-medium text-amber-700 dark:bg-amber-950/30 dark:text-amber-300">No hay usuarios vinculados al rol {{ $stage['role'] }}.</p>
                                @endif
                            </div>
                        @endforeach
                        @error('reviewRecipients')<p class="rounded-xl bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 dark:bg-rose-950/30 dark:text-rose-300">{{ $message }}</p>@enderror
                    </div>

                    <div class="mt-7 flex flex-wrap justify-end gap-3 border-t border-slate-200 pt-5 dark:border-slate-800">
                        <button type="button" wire:click="closeSendReviewModal" class="rounded-full border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Cancelar</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="sendToReview" class="rounded-full bg-amber-500 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-amber-600 disabled:opacity-60">
                            <span wire:loading.remove wire:target="sendToReview">Confirmar y enviar</span>
                            <span wire:loading wire:target="sendToReview">Enviando...</span>
                        </button>
                    </div>
                </form>
            </section>
        </div>
    @endif

    @if ($showCreateAsignaturaModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/55 px-4 py-6 backdrop-blur-[1px]" wire:click.self="closeCreateAsignaturaModal">
            <section role="dialog" aria-modal="true" aria-labelledby="create-subject-title" class="max-h-[calc(100vh-3rem)] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl dark:bg-slate-900 sm:p-7">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 id="create-subject-title" class="text-xl font-bold text-slate-900 dark:text-slate-100">Crear asignatura</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Registra la asignatura y sube el documento de descripciones mínimas.</p>
                    </div>
                    <button type="button" wire:click="closeCreateAsignaturaModal" aria-label="Cerrar" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-slate-300 text-slate-500 transition hover:bg-slate-50 hover:text-slate-900 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white">
                        @svg('heroicon-o-x-mark', ['class' => 'h-5 w-5'])
                    </button>
                </div>
                <form wire:submit.prevent="createAsignaturaAndAttach">
                    <div class="mt-6 grid gap-x-4 gap-y-5 sm:grid-cols-2">
                        <label class="space-y-2">
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">Código <span class="text-rose-500">*</span></span>
                            <input wire:model="newAsignatura.codigo" type="text" required class="w-full rounded-lg border-slate-300 bg-white text-sm text-slate-900 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                            @error('newAsignatura.codigo')<p class="text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">Créditos académicos <span class="text-rose-500">*</span></span>
                            <input wire:model="newAsignatura.creditos_academicos" type="number" min="0" step="0.01" required class="w-full rounded-lg border-slate-300 bg-white text-sm text-slate-900 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                            @error('newAsignatura.creditos_academicos')<p class="text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">Horas académicas <span class="text-rose-500">*</span></span>
                            <input wire:model="newAsignatura.horas_academicas" type="number" min="1" required class="w-full rounded-lg border-slate-300 bg-white text-sm text-slate-900 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                            @error('newAsignatura.horas_academicas')<p class="text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </label>
                        <label class="space-y-2 sm:col-span-2">
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">Nombre <span class="text-rose-500">*</span></span>
                            <input wire:model="newAsignatura.nombre" type="text" required class="w-full rounded-lg border-slate-300 bg-white text-sm text-slate-900 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                            @error('newAsignatura.nombre')<p class="text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </label>
                    </div>

                    <div class="mt-5 flex flex-col gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/60 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Formato del tipo</p>
                            <p class="mt-1 text-sm font-bold text-slate-900 dark:text-white">{{ $currentTipoPrograma?->nombre ?? 'Sin tipo seleccionado' }}</p>
                            <p class="mt-1 max-w-sm text-xs leading-5 text-slate-500 dark:text-slate-400">Usa esta plantilla como base para preparar el documento de descripciones mínimas.</p>
                        </div>
                        @if ($currentTipoPrograma?->plantilla_docx_path)
                            <button type="button" wire:click="downloadTemplate" wire:loading.attr="disabled" wire:target="downloadTemplate" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-full bg-primary px-5 py-3 text-sm font-bold text-white transition hover:bg-primary-container disabled:opacity-60">
                                @svg('heroicon-o-arrow-down-tray', ['class' => 'h-5 w-5'])
                                Descargar formato
                            </button>
                        @else
                            <span class="shrink-0 rounded-full bg-slate-200 px-4 py-2 text-xs font-semibold text-slate-500 dark:bg-slate-700 dark:text-slate-300">Sin formato</span>
                        @endif
                    </div>

                    <div class="mt-5">
                        <p class="mb-2 text-sm font-semibold text-slate-700 dark:text-slate-200">Documento de descripciones mínimas <span class="text-rose-500">*</span></p>
                        <label class="flex min-h-36 cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 px-5 py-6 text-center transition hover:border-primary hover:bg-primary/5 dark:border-slate-700 dark:bg-slate-800/40 dark:hover:border-sky-500">
                            <input wire:model="newAsignaturaDocumento" type="file" required accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" class="sr-only">
                            @svg('heroicon-o-document-arrow-up', ['class' => 'h-8 w-8 text-slate-400'])
                            @if ($newAsignaturaDocumento)
                                <p class="mt-3 break-all text-sm font-bold text-primary dark:text-sky-300">{{ $newAsignaturaDocumento->getClientOriginalName() }}</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Haz clic para reemplazar el archivo</p>
                            @else
                                <p class="mt-3 text-sm font-bold text-slate-700 dark:text-slate-200">Arrastra y suelta el archivo aquí</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">o haz clic para seleccionar un PDF, DOC o DOCX</p>
                            @endif
                            <div wire:loading wire:target="newAsignaturaDocumento" class="mt-2 text-xs font-semibold text-primary dark:text-sky-300">Cargando documento...</div>
                        </label>
                        @error('newAsignaturaDocumento')<p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="mt-6 flex justify-end gap-3 border-t border-slate-200 pt-5 dark:border-slate-800">
                        <button type="button" wire:click="closeCreateAsignaturaModal" class="rounded-full border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Cancelar</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="newAsignaturaDocumento,createAsignaturaAndAttach" class="rounded-full bg-primary px-5 py-2.5 text-sm font-bold text-white transition hover:bg-primary-container disabled:cursor-not-allowed disabled:opacity-60">
                            <span wire:loading.remove wire:target="createAsignaturaAndAttach">Crear asignatura</span>
                            <span wire:loading wire:target="createAsignaturaAndAttach">Creando...</span>
                        </button>
                    </div>
                </form>
            </section>
        </div>
    @endif

    @if ($showEditAsignaturaModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/55 px-4 py-6 backdrop-blur-[1px]" wire:click.self="closeEditAsignaturaModal">
            <section role="dialog" aria-modal="true" aria-labelledby="edit-subject-title" class="max-h-[calc(100vh-3rem)] w-full max-w-3xl overflow-y-auto rounded-3xl bg-white p-6 shadow-2xl dark:bg-slate-900 sm:p-8">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 id="edit-subject-title" class="text-2xl font-bold text-slate-900 dark:text-slate-100">Editar asignatura</h2>
                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Actualiza los atributos de la asignatura y reemplaza el documento si es necesario.</p>
                    </div>
                    <button type="button" wire:click="closeEditAsignaturaModal" aria-label="Cerrar" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-slate-300 text-slate-500 transition hover:bg-slate-50 hover:text-slate-900 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white">
                        @svg('heroicon-o-x-mark', ['class' => 'h-6 w-6'])
                    </button>
                </div>

                <form wire:submit.prevent="updateAsignatura">
                    <div class="mt-7 grid gap-x-5 gap-y-6 sm:grid-cols-2">
                        <label class="space-y-2">
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">Código <span class="text-rose-500">*</span></span>
                            <input wire:model="editingAsignatura.codigo" type="text" required class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                            @error('editingAsignatura.codigo')<p class="text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">Créditos académicos <span class="text-rose-500">*</span></span>
                            <input wire:model="editingAsignatura.creditos_academicos" type="number" min="0" step="0.01" required class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                            @error('editingAsignatura.creditos_academicos')<p class="text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">Horas académicas <span class="text-rose-500">*</span></span>
                            <input wire:model="editingAsignatura.horas_academicas" type="number" min="1" required class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                            @error('editingAsignatura.horas_academicas')<p class="text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </label>
                        <label class="space-y-2 sm:col-span-2">
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">Nombre <span class="text-rose-500">*</span></span>
                            <input wire:model="editingAsignatura.nombre" type="text" required class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                            @error('editingAsignatura.nombre')<p class="text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </label>
                    </div>

                    <div class="mt-6">
                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Documento de descripciones mínimas</p>
                        @if (filled($editingAsignatura['ruta_documento_descripcion_minima'] ?? null))
                            <p class="mt-2 break-all text-sm text-slate-500 dark:text-slate-400">Documento actual: {{ basename($editingAsignatura['ruta_documento_descripcion_minima']) }}</p>
                        @else
                            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">La asignatura no tiene un documento registrado.</p>
                        @endif
                        <label class="mt-3 flex min-h-24 cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 px-5 py-5 text-center transition hover:border-primary hover:bg-primary/5 dark:border-slate-700 dark:bg-slate-800/40 dark:hover:border-sky-500">
                            <input wire:model="editingAsignaturaDocumento" type="file" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" class="sr-only">
                            @svg('heroicon-o-document-arrow-up', ['class' => 'h-7 w-7 text-slate-400'])
                            @if ($editingAsignaturaDocumento)
                                <p class="mt-2 break-all text-sm font-bold text-primary dark:text-sky-300">{{ $editingAsignaturaDocumento->getClientOriginalName() }}</p>
                            @else
                                <p class="mt-2 text-sm font-semibold text-slate-700 dark:text-slate-200">Selecciona un archivo para reemplazar el actual</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Opcional · PDF, DOC o DOCX</p>
                            @endif
                        </label>
                        @error('editingAsignaturaDocumento')<p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="mt-7 flex justify-end gap-3 border-t border-slate-200 pt-5 dark:border-slate-800">
                        <button type="button" wire:click="closeEditAsignaturaModal" class="rounded-full border border-slate-300 px-6 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Cancelar</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="editingAsignaturaDocumento,updateAsignatura" class="rounded-full bg-primary px-6 py-3 text-sm font-bold text-white transition hover:bg-primary-container disabled:opacity-60">Guardar cambios</button>
                    </div>
                </form>
            </section>
        </div>
    @endif

    @if ($showPrerequisitosModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/55 px-4 py-6 backdrop-blur-[1px]" wire:click.self="closePrerequisitosModal">
            <section role="dialog" aria-modal="true" aria-labelledby="subject-requirements-title" class="max-h-[calc(100vh-3rem)] w-full max-w-3xl overflow-y-auto rounded-3xl bg-white p-6 shadow-2xl dark:bg-slate-900 sm:p-8">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 id="subject-requirements-title" class="text-2xl font-bold text-slate-900 dark:text-slate-100">Requisitos de la asignatura</h2>
                        <p class="mt-2 text-lg text-slate-500 dark:text-slate-400">{{ $prerequisiteSubject['codigo'] ?? 'S/C' }} · {{ $prerequisiteSubject['nombre'] ?? '' }}</p>
                    </div>
                    <button type="button" wire:click="closePrerequisitosModal" aria-label="Cerrar" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-slate-300 text-slate-500 transition hover:bg-slate-50 hover:text-slate-900 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white">
                        @svg('heroicon-o-x-mark', ['class' => 'h-6 w-6'])
                    </button>
                </div>

                <form wire:submit.prevent="savePrerequisitos">
                    <div class="mt-7 rounded-2xl border border-dashed border-slate-300 p-5 dark:border-slate-700">
                        @if ($prerequisiteCandidates->isEmpty())
                            <p class="py-7 text-center text-base text-slate-500 dark:text-slate-400">Agrega primero otras asignaturas al programa para poder marcarlas como requisito.</p>
                        @else
                            <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">Selecciona las asignaturas que deben aprobarse previamente.</p>
                            <div class="space-y-3">
                                @foreach ($prerequisiteCandidates as $candidate)
                                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 transition hover:border-primary/50 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">
                                        <input type="checkbox" wire:model="selectedPrerequisiteIds" value="{{ $candidate['asignatura_id'] }}" class="rounded border-slate-300 text-primary focus:ring-primary dark:border-slate-600 dark:bg-slate-800">
                                        <span>
                                            <span class="block text-sm font-bold text-slate-900 dark:text-white">{{ $candidate['codigo'] ?: 'S/C' }} · {{ $candidate['nombre'] }}</span>
                                            <span class="mt-1 block text-xs text-slate-500 dark:text-slate-400">{{ (int) ($candidate['horas_academicas'] ?? 0) }} horas académicas</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                        @error('selectedPrerequisiteIds')<p class="mt-3 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        @error('selectedPrerequisiteIds.*')<p class="mt-3 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="mt-7 flex justify-end gap-3">
                        <button type="button" wire:click="closePrerequisitosModal" class="rounded-full border border-slate-300 px-6 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Cancelar</button>
                        <button type="submit" class="rounded-full bg-primary px-6 py-3 text-sm font-bold text-white transition hover:bg-primary-container">Guardar requisitos</button>
                    </div>
                </form>
            </section>
        </div>
    @endif
</div>
