<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
        <thead class="bg-slate-50 dark:bg-slate-800/70">
            <tr>
                <th class="px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Programa</th>
                <th class="px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Centro / facultad</th>
                <th class="px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Etapa actual</th>
                <th class="px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Estado</th>
                <th class="px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Asignado</th>
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
                        {{ $row->asignado_usuario_id ?? 'Pendiente' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-sm text-slate-500 dark:text-slate-400">
                        {{ $emptyMessage }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
