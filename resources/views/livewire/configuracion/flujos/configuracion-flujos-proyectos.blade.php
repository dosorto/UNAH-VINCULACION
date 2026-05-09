<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Configuracion de flujos de proyectos</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Administra el orden de las etapas de aprobacion para proyectos.</p>
        </div>
        <button wire:click="newWorkflow" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Nuevo flujo</button>
    </div>

    @if (session('status'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[260px,1fr]">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Flujos</p>
            <div class="mt-3 space-y-2">
                @forelse ($flows as $flow)
                    <button wire:click="selectWorkflow({{ $flow->id }})"
                            class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium
                                {{ $selectedWorkflowId === $flow->id ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200' }}">
                        {{ $flow->nombre }}
                    </button>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No hay flujos registrados.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Codigo</label>
                    <input wire:model="workflow.codigo" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                    @error('workflow.codigo') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Nombre</label>
                    <input wire:model="workflow.nombre" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                    @error('workflow.nombre') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Descripcion</label>
                    <textarea wire:model="workflow.descripcion" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white"></textarea>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" wire:model="workflow.activo" class="rounded border-gray-300 text-blue-600" />
                    <span class="text-sm text-gray-700 dark:text-gray-300">Activo</span>
                </div>
            </div>

            <div class="mt-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Etapas</h3>
                    <button wire:click="addStage" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">Agregar etapa</button>
                </div>

                <div class="mt-3 space-y-3">
                    @foreach ($stages as $index => $stage)
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800">
                            <div class="flex flex-wrap items-center gap-3">
                                <input wire:model="stages.{{ $index }}.codigo" placeholder="Codigo"
                                       class="w-28 rounded-md border border-gray-300 bg-white px-2 py-1 text-xs text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                                <input wire:model="stages.{{ $index }}.nombre" placeholder="Nombre"
                                       class="flex-1 rounded-md border border-gray-300 bg-white px-2 py-1 text-xs text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                                <select wire:model="stages.{{ $index }}.cargo_firma_id"
                                        class="w-56 rounded-md border border-gray-300 bg-white px-2 py-1 text-xs text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                    <option value="">Cargo de firma</option>
                                    @foreach ($cargos as $cargo)
                                        <option value="{{ $cargo->id }}">{{ $cargo->cargo_nombre }}</option>
                                    @endforeach
                                </select>
                                <button wire:click="moveStageUp({{ $index }})" class="text-xs text-gray-500 hover:text-gray-700">▲</button>
                                <button wire:click="moveStageDown({{ $index }})" class="text-xs text-gray-500 hover:text-gray-700">▼</button>
                                <button wire:click="removeStage({{ $index }})" class="text-xs text-red-600 hover:text-red-800">Eliminar</button>
                            </div>
                            @error("stages.$index.codigo") <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                            @error("stages.$index.nombre") <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                            @error("stages.$index.cargo_firma_id") <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button wire:click="save" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Guardar flujo</button>
            </div>
        </div>
    </div>
</div>
