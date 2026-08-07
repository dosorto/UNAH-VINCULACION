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
                <a href="{{ route('sgcu.programas') }}" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-700 dark:text-slate-200">Volver</a>
                @if (! $programa)
                    <button wire:click="savePrograma" class="rounded-full bg-primary px-4 py-2 text-sm font-medium text-white">Crear programa</button>
                @elseif ($isEditableState)
                    <button
                        wire:click="sendToReview"
                        @disabled(! $draftSetup['ready_for_review'])
                        title="{{ $draftSetup['ready_for_review'] ? 'Enviar programa a revisión' : 'Debes completar asignaturas, centros y horas válidas antes de enviar a revisión.' }}"
                        class="rounded-full px-4 py-2 text-sm font-semibold text-white shadow-sm transition {{ $draftSetup['ready_for_review'] ? 'bg-amber-500 shadow-amber-500/20 hover:bg-amber-600' : 'cursor-not-allowed bg-slate-300 shadow-none dark:bg-slate-700' }}">
                        Enviar a revisión
                    </button>
                    <button x-on:click.prevent="confirmDialog('¿Deseas borrar este programa? Esta acción no se puede deshacer.', { type: 'danger' }).then((ok) => ok && $wire.deleteDraft())" class="rounded-full border border-rose-300 px-4 py-2 text-sm font-medium text-rose-700 dark:border-rose-800 dark:text-rose-300">Borrar</button>
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
                                <div class="flex items-center gap-2">
                                    <button type="button" title="Etapa {{ $index + 1 }} · {{ $entry['title'] }} · {{ $entry['status'] }}"
                                        class="flex h-9 w-9 items-center justify-center rounded-full text-xs font-black shadow-sm transition
                                            {{ $entry['tone'] === 'emerald' ? 'bg-emerald-600 text-white ring-4 ring-emerald-100 dark:bg-emerald-500 dark:ring-emerald-950/40' : '' }}
                                            {{ $entry['tone'] === 'sky' ? 'bg-sky-600 text-white ring-4 ring-sky-100 dark:bg-sky-500 dark:ring-sky-950/40' : '' }}
                                            {{ $entry['tone'] === 'slate' ? 'bg-slate-400 text-white ring-4 ring-slate-100 dark:bg-slate-700 dark:ring-slate-900/50' : '' }}">
                                        {{ $index + 1 }}
                                    </button>
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
                    <p class="text-[11px] font-black uppercase tracking-[0.28em] text-white/70">Tipo seleccionado</p>
                    <h2 class="mt-1 text-lg font-extrabold">{{ $currentTipoPrograma->nombre }}</h2>
                    <p class="mt-1 text-sm text-white/80">Duración requerida: {{ $currentTipoPrograma->descripcionDuracion() }}.</p>
                </div>
            </div>
        </section>
    @endif

    @if (! $programa)
        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
            <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Datos básicos</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <label class="space-y-2">
                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Centro / facultad principal *</span>
                    <select wire:model="programaForm.centro_facultad_id" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        <option value="">Seleccione</option>
                        @foreach ($centrosFacultad as $centro)
                            <option value="{{ $centro->id }}">{{ $centro->nombre }}</option>
                        @endforeach
                    </select>
                    @error('programaForm.centro_facultad_id')<p class="text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror
                </label>
                <label class="space-y-2">
                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Código *</span>
                    <input wire:model="programaForm.codigo" type="text" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" />
                    @error('programaForm.codigo')<p class="text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror
                </label>
                <label class="space-y-2">
                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Tipo de programa *</span>
                    <select wire:model.live="programaForm.tipo_programa_id" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        <option value="">Seleccione</option>
                        @foreach ($tiposPrograma as $tipo)
                            <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                        @endforeach
                    </select>
                    @error('programaForm.tipo_programa_id')<p class="text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror
                </label>
                <label class="space-y-2 md:col-span-2">
                    <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Nombre *</span>
                    <input wire:model="programaForm.nombre" type="text" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" />
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
                        <div class="mb-5 rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/40">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                <div>
                                    <p class="text-[11px] font-black uppercase tracking-[0.28em] text-slate-500 dark:text-slate-400">Construcción guiada</p>
                                    <h2 class="mt-1 text-base font-semibold text-slate-900 dark:text-slate-100">Completa los pasos obligatorios antes de enviar el programa a revisión</h2>
                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Paso 1: define las asignaturas. Paso 2: selecciona los centros regionales donde podrá aperturarse.</p>
                                </div>
                                <div class="rounded-full px-4 py-2 text-sm font-semibold {{ $draftSetup['ready_for_review'] ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300' }}">{{ $draftSetup['ready_for_review'] ? 'Programa listo para revisión' : 'Faltan pasos obligatorios' }}</div>
                            </div>
                        </div>
                    @endif

                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-4 dark:border-slate-800">
                        <div class="flex flex-wrap gap-2">
                            <button @click="tab = 'asignaturas'" :class="tab === 'asignaturas' ? 'bg-primary text-white' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200'" class="rounded-full px-4 py-2 text-sm font-medium transition">Paso 1 · Asignaturas <span class="ml-2 rounded-full bg-white/20 px-2 py-0.5 text-xs">{{ count($asignaturas) }}</span></button>
                            <button @click="tab = 'centros'" :class="tab === 'centros' ? 'bg-primary text-white' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200'" class="rounded-full px-4 py-2 text-sm font-medium transition">Paso 2 · Centros regionales <span class="ml-2 rounded-full bg-white/20 px-2 py-0.5 text-xs">{{ count($centros) }}</span></button>
                            <button @click="tab = 'auditoria'" :class="tab === 'auditoria' ? 'bg-primary text-white' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200'" class="rounded-full px-4 py-2 text-sm font-medium transition">Auditoría</button>
                        </div>
                    </div>
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
                                        <th class="px-3 py-2 text-right text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Acción</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-900">
                                    @forelse ($asignaturas as $index => $asignatura)
                                        <tr>
                                            <td class="px-3 py-3"><input type="number" min="1" wire:model.live="asignaturas.{{ $index }}.orden" class="w-20 rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"></td>
                                            <td class="px-3 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $asignatura['codigo'] ?: 'S/C' }}</td>
                                            <td class="px-3 py-3 text-sm font-medium text-slate-900 dark:text-slate-100">{{ $asignatura['nombre'] }}</td>
                                            <td class="px-3 py-3 text-sm text-slate-600 dark:text-slate-300">{{ (int) ($asignatura['horas_academicas'] ?? 0) }} h</td>
                                            <td class="px-3 py-3 text-right">@if ($isEditableState)<button wire:click="removeAsignatura({{ $index }})" class="text-xs font-semibold text-rose-600">Quitar</button>@endif</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="px-3 py-8 text-center text-sm text-slate-500 dark:text-slate-400">No hay asignaturas agregadas.</td></tr>
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
                    <label class="space-y-2"><span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Centro / facultad principal *</span><select wire:model="programaForm.centro_facultad_id" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"><option value="">Seleccione</option>@foreach ($centrosFacultad as $centro)<option value="{{ $centro->id }}">{{ $centro->nombre }}</option>@endforeach</select>@error('programaForm.centro_facultad_id')<p class="text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror</label>
                    <label class="space-y-2"><span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Código *</span><input wire:model="programaForm.codigo" type="text" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" />@error('programaForm.codigo')<p class="text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror</label>
                    <label class="space-y-2"><span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Tipo de programa *</span><select wire:model.live="programaForm.tipo_programa_id" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"><option value="">Seleccione</option>@foreach ($tiposPrograma as $tipo)<option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>@endforeach</select>@error('programaForm.tipo_programa_id')<p class="text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror</label>
                    <label class="space-y-2 md:col-span-2"><span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Nombre *</span><input wire:model="programaForm.nombre" type="text" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" />@error('programaForm.nombre')<p class="text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror</label>
                    <label class="space-y-2 md:col-span-2"><span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Descripción</span><textarea wire:model="programaForm.descripcion" rows="4" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"></textarea>@error('programaForm.descripcion')<p class="text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror</label>
                </div>
                <div class="mt-6 flex justify-end gap-3 border-t border-slate-200 pt-4 dark:border-slate-800">
                    <button wire:click="closeEditProgramModal" class="rounded-2xl border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:text-slate-200">Cancelar</button>
                    <button wire:click="savePrograma" class="rounded-2xl bg-primary px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-container">Guardar cambios</button>
                </div>
            </section>
        </div>
    @endif

    @if ($showCreateAsignaturaModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 px-4 py-6" wire:click.self="closeCreateAsignaturaModal">
            <section class="w-full max-w-2xl rounded-3xl bg-white p-6 shadow-2xl dark:bg-slate-900">
                <div class="flex items-start justify-between border-b border-slate-200 pb-4 dark:border-slate-800">
                    <div><h2 class="text-xl font-bold text-slate-900 dark:text-slate-100">Crear asignatura</h2><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Completa la información académica para agregarla al programa.</p></div>
                    <button wire:click="closeCreateAsignaturaModal" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:text-slate-200">Cerrar</button>
                </div>
                <form wire:submit.prevent="createAsignaturaAndAttach">
                    <div class="mt-6 grid gap-4 md:grid-cols-2">
                        <label class="space-y-2"><span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Código</span><input wire:model="newAsignatura.codigo" type="text" required class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">@error('newAsignatura.codigo')<p class="text-xs font-medium text-rose-600">{{ $message }}</p>@enderror</label>
                        <label class="space-y-2 md:col-span-2"><span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Nombre</span><input wire:model="newAsignatura.nombre" type="text" required class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">@error('newAsignatura.nombre')<p class="text-xs font-medium text-rose-600">{{ $message }}</p>@enderror</label>
                        <label class="space-y-2"><span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Créditos</span><input wire:model="newAsignatura.creditos_academicos" type="number" min="0" step="0.01" required class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">@error('newAsignatura.creditos_academicos')<p class="text-xs font-medium text-rose-600">{{ $message }}</p>@enderror</label>
                        <label class="space-y-2"><span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Horas</span><input wire:model="newAsignatura.horas_academicas" type="number" min="1" required class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">@error('newAsignatura.horas_academicas')<p class="text-xs font-medium text-rose-600">{{ $message }}</p>@enderror</label>
                        <label class="space-y-2 md:col-span-2">
                            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Documento de descripción mínima</span>
                            <input wire:model="newAsignaturaDocumento" type="file" required accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                            <div wire:loading wire:target="newAsignaturaDocumento" class="text-xs font-medium text-slate-500 dark:text-slate-400">Cargando documento...</div>
                            @error('newAsignaturaDocumento')<p class="text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        </label>
                    </div>
                    <div class="mt-6 flex justify-end gap-3 border-t border-slate-200 pt-4 dark:border-slate-800">
                        <button type="button" wire:click="closeCreateAsignaturaModal" class="rounded-2xl border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:text-slate-200">Cancelar</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="newAsignaturaDocumento,createAsignaturaAndAttach" class="rounded-2xl bg-primary px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-container disabled:cursor-not-allowed disabled:opacity-60">
                            <span wire:loading.remove wire:target="createAsignaturaAndAttach">Crear y agregar</span>
                            <span wire:loading wire:target="createAsignaturaAndAttach">Creando...</span>
                        </button>
                    </div>
                </form>
            </section>
        </div>
    @endif
</div>
