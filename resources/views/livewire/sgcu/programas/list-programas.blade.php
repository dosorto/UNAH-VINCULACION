<div class="space-y-8 px-4 py-8 sm:px-6 lg:px-8">
    <section class="flex flex-col gap-4 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.28em] text-cyan-700 dark:text-cyan-400">Fase 1</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900 dark:text-slate-100">Programas y ediciones</h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Gestiona la oferta academica base del SGCU antes de matricula, pagos y emision.</p>
        </div>
        <button wire:click="openCreate" class="inline-flex items-center justify-center rounded-2xl bg-primary px-5 py-3 text-sm font-semibold text-white transition hover:bg-primary-container">
            Nuevo programa
        </button>
    </section>

    @if (session('programas_status'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300">
            {{ session('programas_status') }}
        </div>
    @endif

    <section class="grid gap-4 md:grid-cols-3">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-sm text-slate-500 dark:text-slate-400">Total</p>
            <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-slate-100">{{ $metricas['programas'] }}</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-sm text-slate-500 dark:text-slate-400">En elaboracion</p>
            <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-slate-100">{{ $metricas['borradores'] }}</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-sm text-slate-500 dark:text-slate-400">En revision</p>
            <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-slate-100">{{ $metricas['revision'] }}</p>
        </article>
    </section>

    <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
        <div class="grid gap-4 md:grid-cols-[1fr,220px,180px,220px]">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar por codigo o nombre" class="w-full rounded-2xl border-slate-300 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" />
            <select wire:model.live="estado" class="w-full rounded-2xl border-slate-300 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                <option value="">Todos los estados</option>
                @foreach ($estadosFlujo as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            <label class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:text-slate-200">
                <input type="checkbox" wire:model.live="showTrashed" class="rounded border-slate-300 text-primary dark:border-slate-700 dark:bg-slate-800">
                Eliminados
            </label>
            <a href="{{ route('sgcu.bandeja-revision') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:text-slate-200">Bandeja de revision</a>
        </div>

        <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                    <thead class="bg-slate-50 dark:bg-slate-800/70">
                        <tr class="text-left text-slate-600 dark:text-slate-300">
                            <th class="px-4 py-3 font-semibold">Codigo</th>
                            <th class="px-4 py-3 font-semibold">Programa</th>
                            <th class="px-4 py-3 font-semibold">Version</th>
                            <th class="px-4 py-3 font-semibold">Centro / facultad</th>
                            <th class="px-4 py-3 font-semibold">Estado</th>
                            <th class="px-4 py-3 font-semibold">Etapa actual</th>
                            <th class="px-4 py-3 font-semibold">Creado</th>
                            <th class="px-4 py-3 text-right font-semibold">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white dark:divide-slate-800 dark:bg-slate-900">
                        @forelse ($records as $row)
                            @php($currentStage = $row->etapaActual())
                            <tr class="transition hover:bg-slate-50 dark:hover:bg-slate-800/60 {{ $row->trashed() ? 'opacity-60' : '' }}">
                                <td class="px-4 py-4 font-semibold text-slate-700 dark:text-slate-200">{{ $row->codigo ?: 'S/C' }}</td>
                                <td class="px-4 py-4">
                                    <div class="font-semibold text-slate-900 dark:text-slate-100">{{ $row->nombre }}</div>
                                    <div class="text-slate-500 dark:text-slate-400">{{ $row->tipoPrograma?->nombre ?? $row->tipo_programa ?? 'Sin tipo' }}</div>
                                    @if ($row->descripcion)
                                        <div class="mt-1 text-slate-500 dark:text-slate-400">{{ \Illuminate\Support\Str::limit($row->descripcion, 70) }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-slate-700 dark:text-slate-300">V{{ $row->version_actual }}</td>
                                <td class="px-4 py-4 text-slate-700 dark:text-slate-300">{{ $row->centroFacultad?->nombre ?? 'Sin centro' }}</td>
                                <td class="px-4 py-4"><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ str_replace('_', ' ', $row->estado_flujo ?? 'N/D') }}</span></td>
                                <td class="px-4 py-4 text-slate-700 dark:text-slate-300">{{ $currentStage?->etapa_nombre ?? $row->subsanacion_etapa_nombre ?? 'Sin etapa' }}</td>
                                <td class="px-4 py-4 text-slate-500 dark:text-slate-400">{{ optional($row->created_at)->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-4">
                                    <div class="flex justify-end gap-2">
                                        @if ($row->trashed())
                                            <button wire:click="restorePrograma({{ $row->id }})" wire:confirm="¿Restaurar este programa?" class="rounded-full border border-primary/40 px-3 py-1.5 text-xs font-semibold text-primary">
                                                Restaurar
                                            </button>
                                        @else
                                            <button wire:click="openEdit({{ $row->id }})" class="rounded-full border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 dark:border-slate-700 dark:text-slate-200">
                                                Editar
                                            </button>
                                            @if ($row->estaEditable())
                                                <button wire:click="sendToReview({{ $row->id }})" wire:confirm="¿Enviar este programa a revision?" class="rounded-full border border-cyan-300 px-3 py-1.5 text-xs font-semibold text-cyan-700 dark:border-cyan-800 dark:text-cyan-300">
                                                    Enviar
                                                </button>
                                            @endif
                                            <button wire:click="deletePrograma({{ $row->id }})" wire:confirm="¿Eliminar este programa?" class="rounded-full border border-rose-300 px-3 py-1.5 text-xs font-semibold text-rose-700">
                                                Eliminar
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-10 text-center text-slate-500 dark:text-slate-400">No hay programas registrados con ese criterio.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">{{ $records->links() }}</div>
    </section>

    @if ($formModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 px-4 py-6" wire:click.self="cancelForm">
            <section class="max-h-[92vh] w-full max-w-5xl overflow-y-auto rounded-3xl bg-white p-6 shadow-2xl dark:bg-slate-900">
                <div class="flex flex-col gap-3 border-b border-slate-200 pb-4 dark:border-slate-800 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100">{{ $editingProgramaId ? 'Editar programa' : 'Nuevo programa' }}</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Completa los datos base para administrar el programa dentro de SGCU.</p>
                    </div>
                    <button wire:click="cancelForm" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:text-slate-200">Cerrar</button>
                </div>

                <div class="mt-6 grid gap-5 lg:grid-cols-2">
                    <label class="space-y-2">
                        <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Centro / facultad principal *</span>
                        <select wire:model="programaForm.centro_facultad_id" class="w-full rounded-2xl border-slate-300 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                            <option value="">Seleccione un centro</option>
                            @foreach ($centrosFacultad as $centro)
                                <option value="{{ $centro->id }}">{{ $centro->nombre }}</option>
                            @endforeach
                        </select>
                        @error('programaForm.centro_facultad_id') <p class="text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                    </label>

                    <label class="space-y-2">
                        <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Codigo *</span>
                        <input wire:model="programaForm.codigo" type="text" class="w-full rounded-2xl border-slate-300 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" />
                        @error('programaForm.codigo') <p class="text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                    </label>

                    <label class="space-y-2">
                        <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Tipo de programa *</span>
                        <select wire:model="programaForm.tipo_programa_id" class="w-full rounded-2xl border-slate-300 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                            <option value="">Seleccione un tipo</option>
                            @foreach ($tiposPrograma as $tipo)
                                <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                            @endforeach
                        </select>
                        @error('programaForm.tipo_programa_id') <p class="text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                    </label>

                    <label class="space-y-2">
                        <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Nombre *</span>
                        <input wire:model="programaForm.nombre" type="text" class="w-full rounded-2xl border-slate-300 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" />
                        @error('programaForm.nombre') <p class="text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                    </label>

                    <label class="space-y-2 lg:col-span-2">
                        <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Descripcion</span>
                        <textarea wire:model="programaForm.descripcion" rows="3" class="w-full rounded-2xl border-slate-300 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"></textarea>
                        @error('programaForm.descripcion') <p class="text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                    </label>
                </div>

                <div class="mt-6 flex justify-end gap-3 border-t border-slate-200 pt-4 dark:border-slate-800">
                    <button wire:click="cancelForm" class="rounded-2xl border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:text-slate-200">Cancelar</button>
                    <button wire:click="savePrograma" class="rounded-2xl bg-primary px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-container">
                        Guardar programa
                    </button>
                </div>
            </section>
        </div>
    @endif
</div>
