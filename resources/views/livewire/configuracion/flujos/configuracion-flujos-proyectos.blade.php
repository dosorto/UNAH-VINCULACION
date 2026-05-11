<div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <section class="flex flex-col gap-3 rounded-2xl bg-white px-5 py-4 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-cyan-700 dark:text-cyan-400">Configuracion</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">Flujos de aprobacion de proyectos</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Administra el orden de las etapas de aprobacion para proyectos institucionales.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button wire:click="newWorkflow" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-700 dark:text-slate-200">Nuevo flujo</button>
            <button wire:click="save" class="rounded-full bg-primary px-4 py-2 text-sm font-semibold text-white">Guardar flujo</button>
        </div>
    </section>

    @if (session('status'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    <section class="grid gap-6 xl:grid-cols-[280px,minmax(0,1fr)]">
        <aside class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
            <div class="mb-4">
                <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Flujos disponibles</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Selecciona un flujo para editarlo o crea uno nuevo.</p>
            </div>

            <div class="space-y-2">
                @forelse ($flows as $flow)
                    <button
                        wire:click="selectWorkflow({{ $flow->id }})"
                        class="w-full rounded-2xl border px-4 py-3 text-left transition {{ $selectedWorkflowId === $flow->id ? 'border-primary bg-primary/5' : 'border-slate-200 bg-white hover:border-primary/40 hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:hover:bg-slate-800/60' }}">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <div class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $flow->nombre }}</div>
                                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">Flujo de proyecto</div>
                            </div>
                            <span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em] {{ $selectedWorkflowId === $flow->id ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }}">
                                {{ $selectedWorkflowId === $flow->id ? 'Activo' : 'Disponible' }}
                            </span>
                        </div>
                    </button>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 px-4 py-6 text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                        No hay flujos registrados.
                    </div>
                @endforelse
            </div>
        </aside>

        <div class="space-y-6">
            <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                            {{ $workflowId ? $workflow['nombre'] : 'Nuevo flujo de aprobacion' }}
                        </h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            {{ $workflowId ? 'Edita el flujo actual o reordena sus etapas.' : 'Completa los datos para registrar un nuevo flujo.' }}
                        </p>
                    </div>
                    <div class="rounded-full px-4 py-2 text-sm font-semibold {{ $workflowId ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300' }}">
                        {{ $workflowId ? 'Flujo configurado' : 'Pendiente de configurar' }}
                    </div>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <label class="block space-y-2">
                        <span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Codigo *</span>
                        <input wire:model="workflow.codigo" type="text" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" />
                        @error('workflow.codigo')<p class="text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror
                    </label>
                    <label class="block space-y-2">
                        <span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Nombre *</span>
                        <input wire:model="workflow.nombre" type="text" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" />
                        @error('workflow.nombre')<p class="text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror
                    </label>
                    <label class="block space-y-2">
                        <span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Proceso</span>
                        <input value="PROYECTO" type="text" disabled class="w-full rounded-xl border-slate-300 bg-slate-50 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" />
                    </label>
                    <label class="inline-flex items-center gap-3 text-sm font-medium text-slate-700 dark:text-slate-300">
                        <input wire:model="workflow.activo" type="checkbox" class="rounded border-slate-300 text-primary dark:border-slate-700 dark:bg-slate-800" />
                        Flujo activo
                    </label>
                    <label class="block space-y-2 md:col-span-2">
                        <span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Descripcion</span>
                        <textarea wire:model="workflow.descripcion" rows="4" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"></textarea>
                        @error('workflow.descripcion')<p class="text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror
                    </label>
                </div>
            </section>

            <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Etapas del flujo</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Ordena el recorrido. La primera tarjeta sera la primera etapa en ejecutarse.</p>
                    </div>
                    <button wire:click="addStage" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-700 dark:text-slate-200">Agregar etapa</button>
                </div>

                <div class="mt-5 space-y-4">
                    @if (count($stages) > 0)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-800/50">
                            <div class="flex flex-wrap items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">Inicio</span>
                                <span class="material-symbols-outlined text-base text-slate-400">east</span>
                                @foreach ($stages as $stage)
                                    <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-700 ring-1 ring-slate-200 dark:bg-slate-900 dark:text-slate-200 dark:ring-slate-700">{{ $stage['nombre'] ?: 'Etapa '.($loop->iteration) }}</span>
                                    @if (! $loop->last)
                                        <span class="material-symbols-outlined text-base text-slate-400">east</span>
                                    @endif
                                @endforeach
                                <span class="material-symbols-outlined text-base text-slate-400">east</span>
                                <span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-rose-700 dark:bg-rose-950/40 dark:text-rose-300">Final</span>
                            </div>
                        </div>
                    @endif

                    @foreach ($stages as $index => $stage)
                        <div class="relative overflow-hidden rounded-2xl border border-slate-200 p-4 dark:border-slate-800">
                            <div class="absolute inset-y-0 left-0 w-1.5 {{ $loop->first ? 'bg-emerald-500' : ($loop->last ? 'bg-rose-500' : 'bg-cyan-500') }}"></div>
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3 pl-2">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-full {{ $loop->first ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : ($loop->last ? 'bg-rose-100 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300' : 'bg-cyan-100 text-cyan-700 dark:bg-cyan-950/40 dark:text-cyan-300') }}">
                                        <span class="text-sm font-bold">{{ $index + 1 }}</span>
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">Etapa {{ $index + 1 }}</div>
                                        <div class="text-xs uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                                            {{ $loop->first ? 'Primera en ejecutarse' : ($loop->last ? 'Cierre del flujo' : 'Etapa intermedia') }}
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button wire:click="moveStageUp({{ $index }})" @disabled($loop->first) class="rounded-full border border-slate-300 p-2 text-slate-600 transition hover:border-primary hover:text-primary disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:text-slate-300">
                                        <span class="material-symbols-outlined text-[18px]">arrow_upward</span>
                                    </button>
                                    <button wire:click="moveStageDown({{ $index }})" @disabled($loop->last) class="rounded-full border border-slate-300 p-2 text-slate-600 transition hover:border-primary hover:text-primary disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:text-slate-300">
                                        <span class="material-symbols-outlined text-[18px]">arrow_downward</span>
                                    </button>
                                    <button wire:click="removeStage({{ $index }})" class="text-sm font-medium text-rose-600 dark:text-rose-300">Quitar</button>
                                </div>
                            </div>

                            <div class="mt-4 grid gap-4 pl-2 md:grid-cols-2 xl:grid-cols-3">
                                <label class="block space-y-2">
                                    <span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Codigo</span>
                                    <input wire:model="stages.{{ $index }}.codigo" type="text" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" />
                                    @error("stages.$index.codigo")<p class="text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror
                                </label>
                                <label class="block space-y-2 xl:col-span-2">
                                    <span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Nombre</span>
                                    <input wire:model="stages.{{ $index }}.nombre" type="text" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" />
                                    @error("stages.$index.nombre")<p class="text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror
                                </label>
                                <label class="block space-y-2">
                                    <span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Cargo de firma</span>
                                    <select wire:model="stages.{{ $index }}.cargo_firma_id" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                        <option value="">Seleccione</option>
                                        @foreach ($cargos as $cargo)
                                            <option value="{{ $cargo->id }}">{{ $cargo->cargo_nombre }}</option>
                                        @endforeach
                                    </select>
                                    @error("stages.$index.cargo_firma_id")<p class="text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror
                                </label>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-4">
                                <label class="inline-flex items-center gap-3 text-sm font-medium text-slate-700 dark:text-slate-300">
                                    <input wire:model.live="stages.{{ $index }}.requiere_asignacion" type="checkbox" class="rounded border-slate-300 text-primary dark:border-slate-700 dark:bg-slate-800" />
                                    Requiere asignacion del responsable
                                </label>
                                <label class="inline-flex items-center gap-3 text-sm font-medium text-slate-700 dark:text-slate-300">
                                    <input wire:model.live="stages.{{ $index }}.emisor_define_destinatario" @disabled(!($stage['requiere_asignacion'] ?? false)) type="checkbox" class="rounded border-slate-300 text-primary disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:bg-slate-800" />
                                    El emisor define el destinatario al enviar
                                </label>
                                <label class="inline-flex items-center gap-3 text-sm font-medium text-slate-700 dark:text-slate-300">
                                    <input wire:model="stages.{{ $index }}.activo" type="checkbox" class="rounded border-slate-300 text-primary dark:border-slate-700 dark:bg-slate-800" />
                                    Etapa activa
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </section>
</div>
