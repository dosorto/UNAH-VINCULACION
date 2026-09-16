@props([
    // Objetos con: tipo, codigo, nombre, etapa, dias_espera, href
    'items' => [],
    'vacio' => 'No hay nada pendiente.',
    'iconoVacio' => 'heroicon-o-check-circle',
    'verTodosHref' => null,
    'verTodosTexto' => 'Ver todo',
])

@if (empty($items) || (is_countable($items) && count($items) === 0))
    <x-dashboard.estado-vacio :titulo="$vacio" :icono="$iconoVacio" tono="exito" />
@else
    <ul {{ $attributes->merge(['class' => 'divide-y divide-slate-100 dark:divide-slate-800']) }}>
        @foreach ($items as $item)
            @php
                $dias = (int) ($item->dias_espera ?? 0);

                // Semáforo de espera. El color nunca va solo: la cifra de días
                // y el icono viajan siempre con él, como exige un canal de estado.
                [$tonoDias, $iconoDias] = match (true) {
                    $dias >= 15 => ['bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300', 'heroicon-o-exclamation-triangle'],
                    $dias >= 7 => ['bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300', 'heroicon-o-clock'],
                    default => ['bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300', 'heroicon-o-clock'],
                };

                $etiqueta = $dias === 0 ? 'hoy' : ($dias === 1 ? '1 día' : $dias.' días');
            @endphp

            <li>
                <{{ $item->href ?? null ? 'a' : 'div' }}
                    @if ($item->href ?? null) href="{{ $item->href }}" wire:navigate @endif
                    class="flex items-start gap-3 px-5 py-3 transition {{ ($item->href ?? null) ? 'hover:bg-slate-50 dark:hover:bg-slate-800/60' : '' }}"
                >
                    <span class="min-w-0 flex-1">
                        <span class="flex flex-wrap items-center gap-1.5">
                            <span class="inline-flex items-center rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                {{ $item->tipo }}
                            </span>
                            @if ($item->codigo ?? null)
                                <span class="text-[11px] font-mono text-slate-400">{{ $item->codigo }}</span>
                            @endif
                        </span>

                        <span class="mt-1 block truncate text-sm font-semibold text-slate-900 dark:text-white" title="{{ $item->nombre }}">
                            {{ $item->nombre }}
                        </span>

                        @if ($item->etapa ?? null)
                            <span class="mt-0.5 block truncate text-xs text-slate-500 dark:text-slate-400">{{ $item->etapa }}</span>
                        @endif
                    </span>

                    <span class="inline-flex shrink-0 items-center gap-1 rounded-full px-2 py-1 text-[11px] font-bold {{ $tonoDias }}">
                        @svg($iconoDias, ['class' => 'h-3.5 w-3.5'])
                        {{ $etiqueta }}
                    </span>
                </{{ $item->href ?? null ? 'a' : 'div' }}>
            </li>
        @endforeach
    </ul>

    @if ($verTodosHref)
        <div class="border-t border-slate-100 px-5 py-3 dark:border-slate-800">
            <a href="{{ $verTodosHref }}" wire:navigate class="text-xs font-semibold text-primary-600 hover:underline dark:text-primary-300">
                {{ $verTodosTexto }} →
            </a>
        </div>
    @endif
@endif
