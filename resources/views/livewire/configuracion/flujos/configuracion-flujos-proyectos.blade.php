<div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <section class="flex flex-col gap-3 rounded-2xl bg-white px-5 py-4 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-cyan-700 dark:text-cyan-400">Configuracion</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">Flujos</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Configura los flujos por tipo de accion.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if ($activeFlowTab === 'proyectos' && $isPpsActionSelected)
                <button type="button" wire:click="cargarEtapasSugeridas" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-primary hover:text-primary dark:border-slate-700 dark:text-slate-200">
                    Cargar sugeridas
                </button>
            @endif
            <button wire:click="save" class="rounded-full bg-primary px-4 py-2 text-sm font-semibold text-white">Guardar flujo</button>
        </div>
    </section>

    @if (session('status'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->has('stages'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-300">
            {{ $errors->first('stages') }}
        </div>
    @endif

    <section class="flex flex-wrap items-center gap-2 rounded-2xl bg-white px-4 py-3 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
        <button type="button" wire:click="showProgramFlows" class="rounded-full border px-4 py-2 text-sm font-semibold transition {{ $activeFlowTab === 'programas' ? 'border-emerald-500 bg-emerald-50 text-emerald-700 dark:border-emerald-400 dark:bg-emerald-950/40 dark:text-emerald-300' : 'border-slate-200 text-slate-700 hover:border-emerald-300 hover:bg-emerald-50 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-slate-800/60' }}">
            Flujos de programa SGCU
        </button>
        <button type="button" wire:click="showProjectFlows" class="rounded-full border px-4 py-2 text-sm font-semibold transition {{ $activeFlowTab === 'proyectos' ? 'border-emerald-500 bg-emerald-50 text-emerald-700 dark:border-emerald-400 dark:bg-emerald-950/40 dark:text-emerald-300' : 'border-slate-200 text-slate-700 hover:border-emerald-300 hover:bg-emerald-50 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-slate-800/60' }}">
            Flujos de proyectos
        </button>
    </section>

    @if ($activeFlowTab === 'proyectos')
    <section class="grid gap-6 xl:grid-cols-[420px,minmax(0,1fr)]">
        <aside class="space-y-6">
            <div class="grid gap-4 lg:grid-cols-2">
                <section class="min-h-[360px] rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                    <div class="mb-4">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Acciones</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Selecciona la accion principal.</p>
                    </div>
                    <div class="space-y-2">
                        @forelse ($actions as $action)
                            <button
                                wire:click="selectAction({{ $action->id }})"
                                class="w-full rounded-2xl border px-3 py-2 text-left text-sm font-medium transition {{ $selectedActionId === $action->id ? 'border-emerald-500 bg-emerald-50 text-emerald-700 dark:border-emerald-400 dark:bg-emerald-950/40 dark:text-emerald-300' : 'border-slate-200 text-slate-700 hover:border-primary/40 hover:bg-slate-50 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-slate-800/60' }}">
                                <span class="whitespace-normal break-words leading-snug">{{ $action->nombre }}</span>
                            </button>
                        @empty
                            <div class="rounded-2xl border border-dashed border-slate-300 px-3 py-4 text-xs text-slate-500 dark:border-slate-700 dark:text-slate-400">
                                No hay acciones registradas.
                            </div>
                        @endforelse
                    </div>
                </section>

                @if ($selectedActionId)
                    <section class="min-h-[360px] rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                        <div class="mb-4">
                            <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Subacciones</h2>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Se despliegan al seleccionar una accion.</p>
                        </div>
                        <div class="space-y-2">
                            @forelse ($subactions as $subaction)
                                <button
                                    type="button"
                                    wire:click="selectSubaction({{ $subaction->id }})"
                                    class="w-full rounded-2xl border px-3 py-2 text-left text-sm font-medium transition {{ $selectedSubactionId === $subaction->id ? 'border-emerald-500 bg-emerald-50 text-emerald-700 dark:border-emerald-400 dark:bg-emerald-950/40 dark:text-emerald-300' : 'border-slate-200 text-slate-700 hover:border-primary/40 hover:bg-slate-50 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-slate-800/60' }}">
                                    <span class="whitespace-normal break-words leading-snug">{{ $subaction->nombre }}</span>
                                </button>
                            @empty
                                <div class="rounded-2xl border border-dashed border-slate-300 px-3 py-4 text-xs text-slate-500 dark:border-slate-700 dark:text-slate-400">
                                    No hay formularios habilitados para esta accion.
                                </div>
                            @endforelse
                        </div>
                    </section>
                @else
                    <section class="min-h-[360px] rounded-2xl border border-dashed border-slate-200 p-4 text-sm text-slate-500 dark:border-slate-800 dark:text-slate-400">
                        Selecciona una accion para ver subacciones.
                    </section>
                @endif
            </div>

            @if (! $selectedSubactionId)
                <section class="rounded-2xl border border-dashed border-slate-200 p-4 text-sm text-slate-500 dark:border-slate-800 dark:text-slate-400">
                    Selecciona una subaccion para configurar el flujo.
                </section>
            @else
                <section class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Flujo principal</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $isPpsActionSelected ? 'Esta pantalla configura el flujo PPS / Servicio Social.' : 'Esta pantalla configura el flujo exclusivo para el formulario seleccionado.' }}</p>
                    </div>

                    <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-3 py-3 dark:border-emerald-900/60 dark:bg-emerald-950/30">
                        <span class="block truncate text-sm font-semibold text-emerald-800 dark:text-emerald-200">{{ $workflow['nombre'] }}</span>
                        <span class="mt-1 block truncate text-xs text-emerald-700 dark:text-emerald-300">{{ $workflow['codigo'] }}</span>
                    </div>
                </section>
            @endif
        </aside>

        <div class="space-y-6">
            @if ($selectedSubactionId)
                <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                            {{ $workflowId ? $workflow['nombre'] : 'Flujo del formulario seleccionado' }}
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
                    <label class="block space-y-2" >
                        <span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400" >Codigo </span>
                        <input wire:model="workflow.codigo" type="text" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" />
                        @error('workflow.codigo')<p class="text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror
                    </label>
                    <label class="block space-y-2">
                        <span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400" required uniqid>Nombre </span>
                        <input wire:model.live.debounce.300ms="workflow.nombre" type="text" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" />
                        @error('workflow.nombre')<p class="text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror
                    </label>
                    <label class="block space-y-2">
                        <span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Proceso</span>
                        <input value="{{ $workflow['proceso'] ?? 'PROYECTO' }}" type="text" disabled class="w-full rounded-xl border-slate-300 bg-slate-50 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" />
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
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Ordena el recorrido. La primera tarjeta sera la primera etapa en ejecutarse y la ultima sera el cierre del flujo.</p>
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
                                        <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $stage['nombre'] ?: 'Etapa '.($index + 1) }}</div>
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
                                    <input wire:model.live.debounce.300ms="stages.{{ $index }}.codigo" type="text" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" />
                                    @error("stages.$index.codigo")<p class="text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror
                                </label>
                                <label class="block space-y-2 xl:col-span-2">
                                    <span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Nombre</span>
                                    <input wire:model.live.debounce.300ms="stages.{{ $index }}.nombre" type="text" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" />
                                    @error("stages.$index.nombre")<p class="text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror
                                </label>
                                <label class="block space-y-2">
                                    <span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Tipo</span>
                                    <select wire:model="stages.{{ $index }}.tipo_etapa" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                        <option value="FORMULACION">Formulacion</option>
                                        <option value="REVISION">Revision</option>
                                        <option value="APROBACION">Aprobacion</option>
                                    </select>
                                    @error("stages.$index.tipo_etapa")<p class="text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror
                                </label>
                                <label class="block space-y-2">
                                    <span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Alcance academico</span>
                                    <select wire:model.live="stages.{{ $index }}.alcance_academico" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                        @foreach ($alcancesAcademicos as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error("stages.$index.alcance_academico")<p class="text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror
                                </label>
                                <label class="block space-y-2">
                                    <span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Multiplicidad</span>
                                    <select wire:model.live="stages.{{ $index }}.multiplicidad_revision" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                        @foreach ($multiplicidadesRevision as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error("stages.$index.multiplicidad_revision")<p class="text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror
                                </label>
                                <label class="block space-y-2">
                                    <span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Rol con acceso</span>
                                    <select wire:model.live="stages.{{ $index }}.rol_revisor_id" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                        <option value="">Seleccione</option>
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                    @error("stages.$index.rol_revisor_id")<p class="text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror
                                </label>
                                <label class="block space-y-2">
                                    <span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Usuario responsable de asignacion</span>
                                    @if ($isPpsActionSelected)
                                        @php($usuariosEtapa = $usuariosPorRol[(string) ($stage['rol_revisor_id'] ?? '')] ?? [])
                                        <select wire:model.live="stages.{{ $index }}.usuario_responsable_id" @disabled(!($stage['rol_revisor_id'] ?? null) || !($stage['requiere_asignacion'] ?? false)) class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:disabled:bg-slate-800/60 dark:disabled:text-slate-500">
                                            <option value="">{{ ($stage['rol_revisor_id'] ?? null) ? 'Sin responsable fijo' : 'Seleccione un rol primero' }}</option>
                                            @foreach ($usuariosEtapa as $usuario)
                                                <option value="{{ $usuario['id'] }}">{{ $usuario['name'] }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        @php($usuariosEtapa = $usuariosPorRol[(string) ($stage['rol_revisor_id'] ?? '')] ?? [])
                                        <select wire:model="stages.{{ $index }}.usuario_responsable_id" @disabled(!($stage['rol_revisor_id'] ?? null) || !($stage['requiere_asignacion'] ?? false) || ($stage['emisor_define_destinatario'] ?? false)) class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:disabled:bg-slate-800/60 dark:disabled:text-slate-500">
                                            <option value="">{{ ($stage['rol_revisor_id'] ?? null) ? 'Sin responsable fijo' : 'Seleccione un rol primero' }}</option>
                                            @foreach ($usuariosEtapa as $usuario)
                                                <option value="{{ $usuario['id'] }}">{{ $usuario['name'] }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                    @error("stages.$index.usuario_responsable_id")<p class="text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror
                                </label>
                            </div>

                            @if ($isPpsActionSelected)
                                <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-800/50">
                                    <label class="block space-y-2">
                                        <span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Estado resultante</span>
                                        <select wire:model="stages.{{ $index }}.estado_resultante" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                            <option value="">Seleccione</option>
                                            @foreach ($estadoOpciones as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error("stages.$index.estado_resultante")<p class="text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror
                                    </label>
                                </div>
                            @else
                                <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-800/50">
                                    <div class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Procesos donde aplica esta etapa</div>
                                    <div class="mt-3 flex flex-wrap gap-4">
                                        <label class="inline-flex items-center gap-3 text-sm font-medium text-slate-700 dark:text-slate-300">
                                            <input wire:model.live="stages.{{ $index }}.aplica_inscripcion" type="checkbox" class="rounded border-slate-300 text-primary dark:border-slate-700 dark:bg-slate-800" />
                                            Inscripcion
                                        </label>
                                        <label class="inline-flex items-center gap-3 text-sm font-medium text-slate-700 dark:text-slate-300">
                                            <input wire:model.live="stages.{{ $index }}.aplica_informe_intermedio" type="checkbox" class="rounded border-slate-300 text-primary dark:border-slate-700 dark:bg-slate-800" />
                                            Informe intermedio
                                        </label>
                                        <label class="inline-flex items-center gap-3 text-sm font-medium text-slate-700 dark:text-slate-300">
                                            <input wire:model.live="stages.{{ $index }}.aplica_cierre_proyecto" type="checkbox" class="rounded border-slate-300 text-primary dark:border-slate-700 dark:bg-slate-800" />
                                            Cierre de proyecto
                                        </label>
                                    </div>
                                    @error("stages.$index.aplica_inscripcion")<p class="mt-2 text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror
                                    @error("stages.$index.aplica_informe_intermedio")<p class="mt-2 text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror
                                    @error("stages.$index.aplica_cierre_proyecto")<p class="mt-2 text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror
                                </div>
                            @endif

                            <div class="mt-4 flex flex-wrap gap-x-6 gap-y-3">
                                <label class="inline-flex cursor-pointer items-center gap-3">
                                    <span class="relative inline-flex h-6 w-11 flex-shrink-0">
                                        <input wire:model.live="stages.{{ $index }}.requiere_asignacion" type="checkbox" class="peer sr-only" />
                                        <span class="h-6 w-11 rounded-full bg-slate-200 transition-colors peer-checked:bg-primary dark:bg-slate-700 dark:peer-checked:bg-primary"></span>
                                        <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-5"></span>
                                    </span>
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Requiere asignacion del responsable</span>
                                </label>
                                @if ($isPpsActionSelected)
                                    <label class="inline-flex items-center gap-3 text-sm font-medium text-slate-700 dark:text-slate-300">
                                        <input wire:model.live="stages.{{ $index }}.permite_edicion" type="checkbox" class="rounded border-slate-300 text-primary dark:border-slate-700 dark:bg-slate-800" />
                                        Permite edicion
                                    </label>
                                    <label class="inline-flex items-center gap-3 text-sm font-medium text-slate-700 dark:text-slate-300">
                                        <input wire:model.live="stages.{{ $index }}.permite_rechazo" type="checkbox" class="rounded border-slate-300 text-primary dark:border-slate-700 dark:bg-slate-800" />
                                        Permite rechazo
                                    </label>
                                    <label class="inline-flex items-center gap-3 text-sm font-medium text-slate-700 dark:text-slate-300">
                                        <input wire:model.live="stages.{{ $index }}.es_estado_final_aprobado" type="checkbox" class="rounded border-slate-300 text-primary dark:border-slate-700 dark:bg-slate-800" />
                                        Final aprobado
                                    </label>
                                @else
                                    <label class="inline-flex cursor-pointer items-center gap-3">
                                        <span class="relative inline-flex h-6 w-11 flex-shrink-0">
                                            <input wire:model.live="stages.{{ $index }}.emisor_define_destinatario" type="checkbox" class="peer sr-only" />
                                            <span class="h-6 w-11 rounded-full bg-slate-200 transition-colors peer-checked:bg-primary dark:bg-slate-700 dark:peer-checked:bg-primary"></span>
                                            <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-5"></span>
                                        </span>
                                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">El emisor define el destinatario al enviar</span>
                                    </label>
                                @endif
                                <label class="inline-flex items-center gap-3 text-sm font-medium text-slate-700 dark:text-slate-300">
                                    <input wire:model="stages.{{ $index }}.activo" type="checkbox" class="rounded border-slate-300 text-primary dark:border-slate-700 dark:bg-slate-800" />
                                    Etapa activa
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
                </section>
            @endif
        </div>
    </section>
    @else
        <section class="grid gap-6 xl:grid-cols-[280px,minmax(0,1fr)]">
            <aside class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <div class="mb-4">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Tipos de programa</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Cada tipo puede tener su propio flujo.</p>
                </div>

                <div class="space-y-2">
                    @forelse ($tiposPrograma as $tipo)
                        <button
                            type="button"
                            wire:click="selectProgramTipoPrograma({{ $tipo->id }})"
                            class="w-full rounded-2xl border px-4 py-3 text-left transition {{ $programSelectedTipoProgramaId === $tipo->id ? 'border-emerald-500 bg-emerald-50 dark:border-emerald-400 dark:bg-emerald-950/40' : 'border-slate-200 bg-white hover:border-emerald-300 hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-emerald-700 dark:hover:bg-slate-800/60' }}">
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $tipo->nombre }}</div>
                                    <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        {{ $tipo->flujoAprobacion ? 'Flujo configurado' : 'Sin flujo configurado' }}
                                    </div>
                                </div>
                                <span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em] {{ $tipo->flujoAprobacion ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300' }}">
                                    {{ $tipo->flujoAprobacion ? 'Listo' : 'Pendiente' }}
                                </span>
                            </div>
                        </button>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 px-4 py-6 text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                            No hay tipos de programa registrados.
                        </div>
                    @endforelse
                </div>
            </aside>

            <div class="space-y-6">
                <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                                {{ $selectedTipoPrograma?->nombre ? 'Flujo de ' . $selectedTipoPrograma->nombre : 'Selecciona un tipo de programa' }}
                            </h2>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                @if ($selectedTipoPrograma)
                                    {{ $programWorkflowId ? 'Edita el flujo actual o reordena sus etapas.' : 'Este tipo todavia no tiene flujo. Puedes crear uno ahora.' }}
                                @else
                                    Elige un tipo de programa para comenzar.
                                @endif
                            </p>
                        </div>
                        @if ($selectedTipoPrograma)
                            <div class="rounded-full px-4 py-2 text-sm font-semibold {{ $programWorkflowId ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300' }}">
                                {{ $programWorkflowId ? 'Flujo configurado' : 'Pendiente de configurar' }}
                            </div>
                        @endif
                    </div>

                    

                    @if ($selectedTipoPrograma)
                        <div class="mt-5 grid gap-4 md:grid-cols-2">
                            <label class="block space-y-2">
                                <span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Codigo *</span>
                                <input wire:model="programWorkflow.codigo" type="text" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" />
                                @error('programWorkflow.codigo')<p class="text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror
                            </label>
                            <label class="block space-y-2">
                                <span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Nombre *</span>
                                <input wire:model.live.debounce.300ms="programWorkflow.nombre" type="text" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" />
                                @error('programWorkflow.nombre')<p class="text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror
                            </label>
                            <label class="block space-y-2">
                                <span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Proceso</span>
                                <input value="PROGRAMA" type="text" disabled class="w-full rounded-xl border-slate-300 bg-slate-50 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" />
                            </label>
                            <label class="inline-flex items-center gap-3 text-sm font-medium text-slate-700 dark:text-slate-300">
                                <input wire:model="programWorkflow.activo" type="checkbox" class="rounded border-slate-300 text-primary dark:border-slate-700 dark:bg-slate-800" />
                                Flujo activo para este tipo
                            </label>
                            <label class="block space-y-2 md:col-span-2">
                                <span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Descripcion</span>
                                <textarea wire:model="programWorkflow.descripcion" rows="4" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"></textarea>
                                @error('programWorkflow.descripcion')<p class="text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror
                            </label>
                        </div>
                    @endif
                </section>

                @if ($selectedTipoPrograma)
                    <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Etapas del flujo</h2>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Ordena el recorrido. La primera tarjeta sera la primera etapa en ejecutarse y la ultima sera el cierre del flujo.</p>
                            </div>
                            <button wire:click="addStage" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-700 dark:text-slate-200">Agregar etapa</button>
                        </div>
                
                <div class="mt-5 space-y-4">
                    @if (count($programStages) > 0)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-800/50">
                            <div class="flex flex-wrap items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">Inicio</span>
                                <span class="material-symbols-outlined text-base text-slate-400">east</span>
                                @foreach ($programStages as $stage)
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

                
                        <div class="mt-5 space-y-4">
                            @foreach ($programStages as $index => $stage)
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
                                            <input wire:model.live.debounce.300ms="programStages.{{ $index }}.codigo" type="text" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" />
                                            @error("programStages.$index.codigo")<p class="text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror
                                        </label>
                                        <label class="block space-y-2 xl:col-span-2">
                                            <span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Nombre</span>
                                            <input wire:model.live.debounce.300ms="programStages.{{ $index }}.nombre" type="text" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" />
                                            @error("programStages.$index.nombre")<p class="text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror
                                        </label>
                                        <label class="block space-y-2">
                                            <span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Alcance academico</span>
                                            <select wire:model.live="programStages.{{ $index }}.alcance_academico" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                                @foreach ($alcancesAcademicos as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            @error("programStages.$index.alcance_academico")<p class="text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror
                                        </label>
                                        <label class="block space-y-2">
                                            <span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Multiplicidad</span>
                                            <select wire:model.live="programStages.{{ $index }}.multiplicidad_revision" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                                @foreach ($multiplicidadesRevision as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            @error("programStages.$index.multiplicidad_revision")<p class="text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror
                                        </label>
                                        <label class="block space-y-2">
                                            <span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Rol revisor</span>
                                            <select wire:model.live="programStages.{{ $index }}.rol_revisor_id" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                                <option value="">Sin rol especifico</option>
                                                @foreach ($roles as $role)
                                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                                                @endforeach
                                            </select>
                                            @error("programStages.$index.rol_revisor_id")<p class="text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror
                                        </label>
                                        <label class="block space-y-2">
                                            <span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Responsable</span>
                                            @php($usuariosEtapa = $usuariosPorRol[(string) ($stage['rol_revisor_id'] ?? '')] ?? [])
                                            <select wire:model="programStages.{{ $index }}.usuario_responsable_id" @disabled(!($stage['rol_revisor_id'] ?? null) || !($stage['requiere_asignacion'] ?? false) || ($stage['emisor_define_destinatario'] ?? false)) class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:disabled:bg-slate-800/60 dark:disabled:text-slate-500">
                                                <option value="">{{ ($stage['rol_revisor_id'] ?? null) ? 'Sin responsable fijo' : 'Seleccione un rol primero' }}</option>
                                                @foreach ($usuariosEtapa as $usuario)
                                                    <option value="{{ $usuario['id'] }}">{{ $usuario['name'] }}</option>
                                                @endforeach
                                            </select>
                                            @error("programStages.$index.usuario_responsable_id")<p class="text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror
                                        </label>
                                    </div>

                                    <div class="mt-4 flex flex-wrap gap-x-6 gap-y-3">
                                        <label class="inline-flex cursor-pointer items-center gap-3">
                                            <span class="relative inline-flex h-6 w-11 flex-shrink-0">
                                                <input wire:model.live="programStages.{{ $index }}.requiere_asignacion" type="checkbox" class="peer sr-only" />
                                                <span class="h-6 w-11 rounded-full bg-slate-200 transition-colors peer-checked:bg-primary dark:bg-slate-700 dark:peer-checked:bg-primary"></span>
                                                <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-5"></span>
                                            </span>
                                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Requiere asignacion del responsable</span>
                                        </label>
                                        <label class="inline-flex cursor-pointer items-center gap-3">
                                            <span class="relative inline-flex h-6 w-11 flex-shrink-0">
                                                <input wire:model.live="programStages.{{ $index }}.emisor_define_destinatario" type="checkbox" class="peer sr-only" />
                                                <span class="h-6 w-11 rounded-full bg-slate-200 transition-colors peer-checked:bg-primary dark:bg-slate-700 dark:peer-checked:bg-primary"></span>
                                                <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-5"></span>
                                            </span>
                                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">El emisor define el destinatario al enviar</span>
                                        </label>
                                        <label class="inline-flex items-center gap-3 text-sm font-medium text-slate-700 dark:text-slate-300">
                                            <input wire:model="programStages.{{ $index }}.activo" type="checkbox" class="rounded border-slate-300 text-primary dark:border-slate-700 dark:bg-slate-800" />
                                            Etapa activa
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>
        </section>
    @endif
</div>
