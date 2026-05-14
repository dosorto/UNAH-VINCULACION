<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
        <thead class="bg-slate-50 dark:bg-slate-800/70">
            <tr>
                <th class="px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Programa</th>
                <th class="px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Centro / facultad</th>
                <th class="px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Etapa actual</th>
                <th class="px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Estado</th>
                <th class="px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Asignado</th>
                <th class="px-6 py-4 text-right text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
            @forelse ($records as $row)
                <tr class="align-top hover:bg-slate-50/70 dark:hover:bg-slate-800/60">
                    <td class="px-6 py-4">
                        <div class="font-semibold text-slate-900 dark:text-slate-100">{{ $row->programa?->nombre ?? 'Sin programa' }}</div>
                        <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $row->programa?->codigo ?? 'Sin codigo' }} · ciclo {{ $row->programa?->revision_ciclo ?? 'N/D' }}</div>
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-300">{{ $row->programa?->centroFacultad?->nombre ?? 'Sin centro' }}</td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ $row->etapa_nombre ?? 'Sin etapa activa' }}</div>
                        @if ($row->rol_requerido)
                            <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $row->rol_requerido }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <span class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                            {{ $row->estado ?? 'N/D' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-300">
                        {{ $row->asignadoUsuario?->name ?? $row->responsableUsuario?->name ?? 'Pendiente' }}
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex min-w-64 flex-col items-end gap-2">
                            @if (in_array($row->estado, ['ASIGNADO', 'PENDIENTE'], true))
                                <textarea wire:model="observaciones.{{ $row->id }}" rows="2" placeholder="Observaciones" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"></textarea>
                                @error("observaciones.$row->id") <p class="text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p> @enderror
                                <div class="flex justify-end gap-2">
                                    <button wire:click="approveRevision({{ $row->id }})" wire:confirm="¿Aprobar esta etapa?" class="rounded-full border border-emerald-300 px-3 py-1.5 text-xs font-semibold text-emerald-700 dark:border-emerald-800 dark:text-emerald-300">
                                        Aprobar
                                    </button>
                                    <button wire:click="rejectRevision({{ $row->id }})" wire:confirm="¿Enviar el programa a subsanacion?" class="rounded-full border border-rose-300 px-3 py-1.5 text-xs font-semibold text-rose-700 dark:border-rose-800 dark:text-rose-300">
                                        Subsanar
                                    </button>
                                </div>
                            @elseif ($row->estado === 'PENDIENTE_ASIGNACION')
                                <button wire:click="assignToMe({{ $row->id }})" class="rounded-full border border-cyan-300 px-3 py-1.5 text-xs font-semibold text-cyan-700 dark:border-cyan-800 dark:text-cyan-300">
                                    Tomar revision
                                </button>
                            @else
                                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Sin acciones</span>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-sm text-slate-500 dark:text-slate-400">
                        {{ $emptyMessage }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
