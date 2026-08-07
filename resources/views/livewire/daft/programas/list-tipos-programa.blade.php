<div class="space-y-8">
    <section class="flex flex-col gap-4 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.28em] text-cyan-700 dark:text-cyan-400">Configuracion</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900 dark:text-slate-100">Tipos de programa</h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Define los tipos de programa y carga la plantilla .docx del espacio de aprendizaje.</p>
        </div>
    </section>

    @if (session('tipos_programa_status'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300">
            {{ session('tipos_programa_status') }}
        </div>
    @endif

    <div class="grid gap-8 xl:grid-cols-[420px,minmax(0,1fr)]">
        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $editingTipoProgramaId ? 'Editar tipo de programa' : 'Nuevo tipo de programa' }}</h2>
                @if ($editingTipoProgramaId)
                    <button wire:click="cancelEditTipoPrograma" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-700 dark:text-slate-200">Cancelar</button>
                @endif
            </div>
            <form wire:submit.prevent="saveTipoPrograma" class="mt-5 space-y-4">
                <label class="space-y-2">
                    <span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Nombre <span class="text-red-500">*</span></span>
                    <input wire:model="tipoPrograma.nombre" type="text" required placeholder="Ej. Diplomado" class="w-full rounded-2xl border-slate-300 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" />
                    @error('tipoPrograma.nombre') <p class="text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p> @enderror
                </label>

                <label class="space-y-2">
                    <span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Duración definida por <span class="text-red-500">*</span></span>
                    <select wire:model.live="tipoPrograma.modalidad_duracion" required class="w-full rounded-2xl border-slate-300 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        <option value="HORAS">Total de horas</option>
                        <option value="DIAS">Días y horas por día</option>
                    </select>
                    @error('tipoPrograma.modalidad_duracion') <p class="text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p> @enderror
                </label>

                @if (($tipoPrograma['modalidad_duracion'] ?? 'HORAS') === 'DIAS')
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="space-y-2">
                            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Días mínimos <span class="text-red-500">*</span></span>
                            <input wire:model="tipoPrograma.dias_minimos" type="number" min="1" required placeholder="Ej. 2" class="w-full rounded-2xl border-slate-300 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" />
                            @error('tipoPrograma.dias_minimos') <p class="text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p> @enderror
                        </label>

                        <label class="space-y-2">
                            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Días máximos <span class="text-red-500">*</span></span>
                            <input wire:model="tipoPrograma.dias_maximos" type="number" min="1" required placeholder="Ej. 5" class="w-full rounded-2xl border-slate-300 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" />
                            @error('tipoPrograma.dias_maximos') <p class="text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p> @enderror
                        </label>
                    </div>

                    <label class="space-y-2">
                        <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Horas mínimas por día <span class="text-red-500">*</span></span>
                        <input wire:model="tipoPrograma.horas_minimas_por_dia" type="number" min="1" required placeholder="Ej. 6" class="w-full rounded-2xl border-slate-300 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" />
                        @error('tipoPrograma.horas_minimas_por_dia') <p class="text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p> @enderror
                    </label>

                    <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/60">
                        <input wire:model="tipoPrograma.dias_consecutivos" type="checkbox" class="rounded border-slate-300 text-primary focus:ring-primary dark:border-slate-600 dark:bg-slate-800" />
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Los días deben ser consecutivos</span>
                    </label>
                @else
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="space-y-2">
                            <span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Horas mínimas <span class="text-red-500">*</span></span>
                            <input wire:model="tipoPrograma.horas_minimas" type="number" min="0" required placeholder="Ej. 8" class="w-full rounded-2xl border-slate-300 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" />
                            @error('tipoPrograma.horas_minimas') <p class="text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p> @enderror
                        </label>

                        <label class="space-y-2">
                            <span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Horas máximas <span class="text-red-500">*</span></span>
                            <input wire:model="tipoPrograma.horas_maximas" type="number" min="0" required placeholder="Ej. 120" class="w-full rounded-2xl border-slate-300 bg-white text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" />
                            @error('tipoPrograma.horas_maximas') <p class="text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p> @enderror
                        </label>
                    </div>
                @endif

                <label class="space-y-2">
                    <span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">
                        Plantilla .docx
                        @if (! $editingTipoProgramaId)
                            <span class="text-red-500">*</span>
                        @endif
                    </span>
                    <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-5 py-6 text-center dark:border-slate-700 dark:bg-slate-800/50">
                        <input wire:model="plantillaDocumento" type="file" @if (! $editingTipoProgramaId) required @endif accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document" class="w-full text-sm text-slate-600 file:mr-4 file:rounded-full file:border-0 file:bg-primary file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-primary-container dark:text-slate-300" />
                        <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">Solo se permite .docx.</p>
                        @if ($editingTipoProgramaId)
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Si no subes un archivo nuevo, se conserva la plantilla actual.</p>
                        @endif
                        <div wire:loading wire:target="plantillaDocumento" class="mt-3 text-xs font-medium text-slate-500 dark:text-slate-400">Cargando documento...</div>
                    </div>
                    @error('plantillaDocumento') <p class="text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p> @enderror
                </label>

                <button type="submit" wire:loading.attr="disabled" wire:target="plantillaDocumento,saveTipoPrograma" class="rounded-full bg-primary px-5 py-2.5 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-60">
                    <span wire:loading.remove wire:target="saveTipoPrograma">{{ $editingTipoProgramaId ? 'Guardar cambios' : 'Guardar tipo de programa' }}</span>
                    <span wire:loading wire:target="saveTipoPrograma">Guardando...</span>
                </button>
            </form>
        </section>

        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Tipos registrados</h2>
                <span wire:key="tipos-programa-count-{{ $tiposPrograma->count() }}" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ $tiposPrograma->count() }}</span>
            </div>

            <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                    <thead class="bg-slate-50 dark:bg-slate-800/70">
                        <tr>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Tipo</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Duración</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Plantilla</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Estado</th>
                            <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Acciones</th>
                        </tr>
                    </thead>
                    <tbody wire:key="tipos-programa-list-{{ $tiposPrograma->pluck('id')->join('-') ?: 'empty' }}" class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-900">
                        @forelse ($tiposPrograma as $tipo)
                            <tr wire:key="tipo-programa-{{ $tipo->id }}">
                                <td class="px-4 py-4 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $tipo->nombre }}</td>
                                <td class="px-4 py-4 text-sm text-slate-600 dark:text-slate-300">{{ $tipo->descripcionDuracion() }}</td>
                                <td class="px-4 py-4 text-sm text-slate-600 dark:text-slate-300">
                                    @if ($tipo->plantilla_docx_path)
                                        <a href="{{ Storage::url($tipo->plantilla_docx_path) }}" class="font-medium text-primary hover:underline">Descargar plantilla</a>
                                    @else
                                        Sin plantilla
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    <span class="rounded-full {{ $tipo->activo ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700' }} px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em]">
                                        {{ $tipo->activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex justify-end gap-2">
                                        <button wire:click="editTipoPrograma({{ $tipo->id }})" class="rounded-full border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 dark:border-slate-700 dark:text-slate-200">Editar</button>
                                        <button wire:click="toggleTipoPrograma({{ $tipo->id }})" class="rounded-full border px-3 py-1.5 text-xs font-semibold {{ $tipo->activo ? 'border-amber-300 text-amber-700' : 'border-emerald-300 text-emerald-700' }}">
                                            {{ $tipo->activo ? 'Deshabilitar' : 'Habilitar' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">Todavia no hay tipos de programa registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
