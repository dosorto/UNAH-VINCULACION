<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
        <thead class="bg-slate-50 dark:bg-slate-800/70">
            <tr>
                <th class="px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Programa</th>
                <th class="px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Centro / facultad</th>
                <th class="px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Etapa actual</th>
                <th class="px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Estado</th>
                <th class="px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Responsable</th>
                <th class="px-6 py-4 text-right text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
            @forelse ($records as $row)
                @php
                    $estadoEtapa = match ($row->estado) {
                        'PENDIENTE_ASIGNACION' => 'Pendiente de asignación',
                        'ASIGNADO' => 'Asignado',
                        'EN_PROCESO' => 'En proceso',
                        'APROBADO' => 'Aprobado',
                        'RECHAZADO' => 'Rechazado',
                        default => ucfirst(strtolower(str_replace('_', ' ', (string) $row->estado))),
                    };
                    $responsableEtapa = $row->asignadoUsuario?->name ?? $row->responsableUsuario?->name;
                @endphp
                <tr tabindex="0" role="link" aria-label="Revisar {{ $row->programa?->nombre }}" onclick="window.location.href='{{ route('daft.bandeja-revision.show', $row) }}'" onkeydown="if(event.key === 'Enter') window.location.href='{{ route('daft.bandeja-revision.show', $row) }}'" class="cursor-pointer align-top transition hover:bg-slate-50/70 focus:bg-slate-50/70 focus:outline-none dark:hover:bg-slate-800/60 dark:focus:bg-slate-800/60">
                    <td class="px-6 py-4">
                        <a href="{{ route('daft.bandeja-revision.show', $row) }}" wire:navigate class="font-semibold text-slate-900 hover:text-primary hover:underline dark:text-slate-100 dark:hover:text-sky-300">{{ $row->programa?->nombre ?? 'Sin programa' }}</a>
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
                            {{ $estadoEtapa }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-300">
                        {{ $responsableEtapa ? 'Responsable: '.$responsableEtapa : 'Sin responsable asignado' }}
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex min-w-64 flex-col items-stretch gap-2" onclick="event.stopPropagation()">
                            @if ($row->estado === 'PENDIENTE_ASIGNACION')
                                @php($reviewerCandidates = $reviewerCandidatesByRevision->get($row->id, collect()))
                                @php($requiredRoleName = $row->rol_requerido ?: $row->flujoEtapa?->rolRevisor?->name)
                                <select wire:model="reviewerSelections.{{ $row->id }}" aria-label="Seleccionar responsable para {{ $row->programa?->nombre }}" class="w-full rounded-xl border-slate-300 bg-white text-xs text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                    <option value="">Seleccione responsable</option>
                                    @foreach ($reviewerCandidates as $reviewer)
                                        <option value="{{ $reviewer->id }}">{{ $reviewer->name }}{{ filled($reviewer->email) ? ' — '.$reviewer->email : '' }}{{ filled($requiredRoleName) && $reviewer->activeRole?->name !== $requiredRoleName ? ' — debe activar el rol' : '' }}</option>
                                    @endforeach
                                </select>
                                @error("reviewerSelections.$row->id")<p class="text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>@enderror
                                @if ($reviewerCandidates->isNotEmpty())
                                    <button type="button" wire:click="assignReviewer({{ $row->id }})" wire:loading.attr="disabled" wire:target="assignReviewer" class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-primary px-4 py-2 text-xs font-bold text-white transition hover:bg-primary-container disabled:opacity-60">
                                        @svg('heroicon-o-user-plus', ['class' => 'h-4 w-4'])
                                        Asignar responsable
                                    </button>
                                @else
                                    <p class="rounded-lg bg-amber-50 px-3 py-2 text-xs font-medium text-amber-700 dark:bg-amber-950/30 dark:text-amber-300">No hay usuarios elegibles para esta etapa.</p>
                                @endif
                            @endif
                            <a href="{{ route('daft.bandeja-revision.show', $row) }}" wire:navigate class="inline-flex items-center justify-center gap-2 rounded-full border border-slate-300 px-4 py-2 text-xs font-bold text-slate-700 transition hover:border-primary hover:text-primary dark:border-slate-700 dark:text-slate-200 dark:hover:border-sky-500 dark:hover:text-sky-300">
                                @svg('heroicon-o-arrow-top-right-on-square', ['class' => 'h-4 w-4'])
                                Revisar programa
                            </a>
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
