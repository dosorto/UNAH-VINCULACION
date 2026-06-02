<div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <form wire:submit.prevent="save" class="space-y-6">
        <section class="flex flex-col gap-3 rounded-2xl bg-white px-5 py-4 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-cyan-700 dark:text-cyan-400">Configuracion</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">Flujo PPS / Servicio Social</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Administra las etapas configurables del proceso {{ \App\Models\PpsServicioSocial::PROCESO_FLUJO }}.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" wire:click="cargarEtapasSugeridas" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-primary hover:text-primary dark:border-slate-700 dark:text-slate-200">
                    Cargar sugeridas
                </button>
                <button type="submit" class="rounded-full bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary/90">
                    Guardar flujo
                </button>
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

        <section class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
            <div class="space-y-6">
                <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                                {{ $workflowId ? 'Flujo configurado' : 'Nuevo flujo configurable' }}
                            </h2>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                Este flujo queda separado del flujo de Proyectos de Vinculacion y no usa PROYECTO_DEFAULT.
                            </p>
                        </div>

                        <span class="inline-flex w-fit rounded-full px-4 py-2 text-sm font-semibold {{ ($workflow['activo'] ?? false) ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }}">
                            {{ ($workflow['activo'] ?? false) ? 'Activo' : 'Inactivo' }}
                        </span>
                    </div>

                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <label class="block space-y-2">
                            <span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Codigo</span>
                            <input wire:model="workflow.codigo" type="text" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" />
                            @error('workflow.codigo')
                                <p class="text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>
                            @enderror
                        </label>

                        <label class="block space-y-2">
                            <span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Nombre</span>
                            <input wire:model.live.debounce.300ms="workflow.nombre" type="text" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" />
                            @error('workflow.nombre')
                                <p class="text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>
                            @enderror
                        </label>

                        <label class="block space-y-2">
                            <span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Proceso</span>
                            <input type="text" value="{{ \App\Models\PpsServicioSocial::PROCESO_FLUJO }}" disabled class="w-full rounded-xl border-slate-300 bg-slate-50 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-300" />
                        </label>

                        <label class="mt-7 inline-flex items-center gap-3 text-sm font-medium text-slate-700 dark:text-slate-300">
                            <input wire:model="workflow.activo" type="checkbox" class="rounded border-slate-300 text-primary dark:border-slate-700 dark:bg-slate-800" />
                            Flujo activo para PPS/SS
                        </label>

                        <label class="block space-y-2 md:col-span-2">
                            <span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Descripcion</span>
                            <textarea wire:model="workflow.descripcion" rows="4" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"></textarea>
                            @error('workflow.descripcion')
                                <p class="text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>
                            @enderror
                        </label>
                    </div>
                </section>

                <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Etapas del flujo</h2>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                Define orden, responsables y el estado resultante de cada etapa.
                            </p>
                        </div>

                        <button type="button" wire:click="addStage" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-primary hover:text-primary dark:border-slate-700 dark:text-slate-200">
                            Agregar etapa
                        </button>
                    </div>

                    @if (count($stages) > 0)
                        <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-800/50">
                            <div class="flex flex-wrap items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">Inicio</span>
                                <span class="material-symbols-outlined text-base text-slate-400">east</span>
                                @foreach ($stages as $stage)
                                    <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-700 ring-1 ring-slate-200 dark:bg-slate-900 dark:text-slate-200 dark:ring-slate-700">
                                        {{ $stage['nombre'] ?: 'Etapa '.$loop->iteration }}
                                    </span>
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
                        @foreach ($stages as $index => $stage)
                            <article wire:key="pps-stage-{{ $stage['id'] ?? 'new-'.$index }}" class="relative overflow-hidden rounded-2xl border border-slate-200 p-4 dark:border-slate-800">
                                <div class="absolute inset-y-0 left-0 w-1.5 {{ ($stage['es_estado_final_aprobado'] ?? false) ? 'bg-emerald-500' : 'bg-cyan-500' }}"></div>

                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="flex items-center gap-3 pl-2">
                                        <div class="flex h-9 w-9 items-center justify-center rounded-full {{ ($stage['activo'] ?? false) ? 'bg-cyan-100 text-cyan-700 dark:bg-cyan-950/40 dark:text-cyan-300' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300' }}">
                                            <span class="text-sm font-bold">{{ $index + 1 }}</span>
                                        </div>
                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                                                    {{ $stage['nombre'] ?: 'Etapa '.($index + 1) }}
                                                </span>
                                                @if ($stage['activo'] ?? false)
                                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">Activa</span>
                                                @else
                                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500 dark:bg-slate-800 dark:text-slate-300">Inactiva</span>
                                                @endif
                                                @if ($stage['es_estado_final_aprobado'] ?? false)
                                                    <span class="rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-primary">Final aprobado</span>
                                                @endif
                                            </div>
                                            <p class="mt-1 text-xs uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                                                Estado: {{ $estadoOpciones[$stage['estado_resultante'] ?? ''] ?? 'Sin definir' }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <button type="button" wire:click="moveStageUp({{ $index }})" @disabled($loop->first) class="rounded-full border border-slate-300 p-2 text-slate-600 transition hover:border-primary hover:text-primary disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:text-slate-300">
                                            <span class="material-symbols-outlined text-[18px]">arrow_upward</span>
                                        </button>
                                        <button type="button" wire:click="moveStageDown({{ $index }})" @disabled($loop->last) class="rounded-full border border-slate-300 p-2 text-slate-600 transition hover:border-primary hover:text-primary disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:text-slate-300">
                                            <span class="material-symbols-outlined text-[18px]">arrow_downward</span>
                                        </button>
                                        <button type="button" wire:click="removeStage({{ $index }})" class="rounded-full px-3 py-2 text-sm font-semibold text-rose-600 transition hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-950/30">
                                            Quitar
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-4 grid gap-4 pl-2 md:grid-cols-2 xl:grid-cols-3">
                                    <label class="block space-y-2">
                                        <span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Codigo</span>
                                        <input wire:model.live.debounce.300ms="stages.{{ $index }}.codigo" type="text" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" />
                                        @error("stages.$index.codigo")
                                            <p class="text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>
                                        @enderror
                                    </label>

                                    <label class="block space-y-2 xl:col-span-2">
                                        <span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Nombre</span>
                                        <input wire:model.live.debounce.300ms="stages.{{ $index }}.nombre" type="text" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" />
                                        @error("stages.$index.nombre")
                                            <p class="text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>
                                        @enderror
                                    </label>

                                    <label class="block space-y-2">
                                        <span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Orden</span>
                                        <input wire:model="stages.{{ $index }}.orden" type="number" min="1" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" />
                                        @error("stages.$index.orden")
                                            <p class="text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>
                                        @enderror
                                    </label>

                                    <label class="block space-y-2">
                                        <span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Tipo de etapa</span>
                                        <select wire:model="stages.{{ $index }}.tipo_etapa" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                            <option value="FORMULACION">Formulacion</option>
                                            <option value="REVISION">Revision</option>
                                            <option value="APROBACION">Aprobacion</option>
                                        </select>
                                        @error("stages.$index.tipo_etapa")
                                            <p class="text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>
                                        @enderror
                                    </label>

                                    <label class="block space-y-2">
                                        <span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Estado resultante</span>
                                        <select wire:model="stages.{{ $index }}.estado_resultante" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                            <option value="">Seleccione</option>
                                            @foreach ($estadoOpciones as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error("stages.$index.estado_resultante")
                                            <p class="text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>
                                        @enderror
                                    </label>

                                    <label class="block space-y-2">
                                        <span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Rol revisor</span>
                                        <select wire:model.live="stages.{{ $index }}.rol_revisor_id" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                            <option value="">Sin rol especifico</option>
                                            @foreach ($roles as $role)
                                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                                            @endforeach
                                        </select>
                                        @error("stages.$index.rol_revisor_id")
                                            <p class="text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>
                                        @enderror
                                    </label>

                                    <label class="block space-y-2">
                                        <span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Usuario responsable</span>
                                        <select wire:model.live="stages.{{ $index }}.usuario_responsable_id" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                            <option value="">Sin usuario fijo</option>
                                            @foreach ($usuarios as $usuario)
                                                <option value="{{ $usuario->id }}">{{ $usuario->name }}</option>
                                            @endforeach
                                        </select>
                                        @error("stages.$index.usuario_responsable_id")
                                            <p class="text-xs text-rose-600 dark:text-rose-300">{{ $message }}</p>
                                        @enderror
                                    </label>
                                </div>

                                <div class="mt-4 grid gap-3 pl-2 sm:grid-cols-2 xl:grid-cols-5">
                                    <label class="flex items-center gap-3 rounded-2xl border border-slate-200 px-3 py-3 text-sm font-medium text-slate-700 dark:border-slate-800 dark:text-slate-300">
                                        <input wire:model.live="stages.{{ $index }}.activo" type="checkbox" class="rounded border-slate-300 text-primary dark:border-slate-700 dark:bg-slate-800" />
                                        Activa
                                    </label>

                                    <label class="flex items-center gap-3 rounded-2xl border border-slate-200 px-3 py-3 text-sm font-medium text-slate-700 dark:border-slate-800 dark:text-slate-300">
                                        <input wire:model.live="stages.{{ $index }}.requiere_asignacion" type="checkbox" class="rounded border-slate-300 text-primary dark:border-slate-700 dark:bg-slate-800" />
                                        Requiere responsable
                                    </label>

                                    <label class="flex items-center gap-3 rounded-2xl border border-slate-200 px-3 py-3 text-sm font-medium text-slate-700 dark:border-slate-800 dark:text-slate-300">
                                        <input wire:model.live="stages.{{ $index }}.permite_edicion" type="checkbox" class="rounded border-slate-300 text-primary dark:border-slate-700 dark:bg-slate-800" />
                                        Permite edicion
                                    </label>

                                    <label class="flex items-center gap-3 rounded-2xl border border-slate-200 px-3 py-3 text-sm font-medium text-slate-700 dark:border-slate-800 dark:text-slate-300">
                                        <input wire:model.live="stages.{{ $index }}.permite_rechazo" type="checkbox" class="rounded border-slate-300 text-primary dark:border-slate-700 dark:bg-slate-800" />
                                        Permite rechazo
                                    </label>

                                    <label class="flex items-center gap-3 rounded-2xl border border-slate-200 px-3 py-3 text-sm font-medium text-slate-700 dark:border-slate-800 dark:text-slate-300">
                                        <input wire:model.live="stages.{{ $index }}.es_estado_final_aprobado" type="checkbox" class="rounded border-slate-300 text-primary dark:border-slate-700 dark:bg-slate-800" />
                                        Final aprobado
                                    </label>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            </div>

            <aside class="space-y-6">
                <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Resumen</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-500 dark:text-slate-400">Etapas</dt>
                            <dd class="font-semibold text-slate-900 dark:text-slate-100">{{ count($stages) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-500 dark:text-slate-400">Etapas activas</dt>
                            <dd class="font-semibold text-slate-900 dark:text-slate-100">{{ collect($stages)->where('activo', true)->count() }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-500 dark:text-slate-400">Final aprobada</dt>
                            <dd class="font-semibold text-slate-900 dark:text-slate-100">{{ collect($stages)->where('activo', true)->where('es_estado_final_aprobado', true)->count() }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200">
                    <h2 class="font-semibold">Importante</h2>
                    <p class="mt-2">
                        Esta pantalla solo deja listo el flujo configurable. Todavia no reemplaza enviar a revision, aprobar, rechazar ni subsanar en PPS/SS.
                    </p>
                </section>

                <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Reglas aplicadas</h2>
                    <ul class="mt-3 space-y-2 text-sm text-slate-600 dark:text-slate-300">
                        <li>El codigo de etapa debe ser unico dentro del flujo.</li>
                        <li>Debe existir exactamente una etapa final aprobada.</li>
                        <li>Las etapas activas necesitan estado resultante.</li>
                        <li>Si requiere responsable, debe tener rol o usuario.</li>
                        <li>Una etapa editable solo puede usar borrador o subsanacion.</li>
                    </ul>
                </section>
            </aside>
        </section>
    </form>
</div>
