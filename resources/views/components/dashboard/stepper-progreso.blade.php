@props(['stepper' => []])

@if (empty($stepper))
    <span class="text-xs text-slate-400">Sin enviar</span>
@else
    <div class="flex w-full min-w-[280px] items-start" aria-label="Progreso del flujo">
        @foreach ($stepper as $paso)
            @php
                $colorClase = match ($paso['estado']) {
                    'aprobado' => 'bg-emerald-500 text-white',
                    'adoptado' => 'bg-sky-500 text-white',
                    'rechazado' => 'bg-red-500 text-white',
                    'actual' => 'bg-amber-400 text-white ring-2 ring-amber-300 ring-offset-1',
                    default => 'bg-slate-200 text-slate-400 dark:bg-slate-700',
                };
                $icono = match ($paso['estado']) {
                    'aprobado' => '✓',
                    'adoptado' => '↑',
                    'rechazado' => '✕',
                    'actual' => '●',
                    default => '○',
                };
                $estadoEtiqueta = match ($paso['estado']) {
                    'aprobado' => 'Aprobada',
                    'adoptado' => 'Completada antes de adoptar el flujo',
                    'rechazado' => 'En subsanación',
                    'actual' => 'Etapa actual',
                    default => 'Pendiente',
                };
                $tooltip = $paso['nombre'].' — '.$estadoEtiqueta;
            @endphp
            <div
                class="group relative flex min-w-0 flex-1 flex-col items-center gap-1 outline-none"
                tabindex="0"
                title="{{ $tooltip }}"
                aria-label="{{ $tooltip }}"
            >
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[10px] font-bold {{ $colorClase }}">
                    {{ $icono }}
                </span>
                <span class="hidden w-full truncate px-1 text-center text-[10px] leading-tight text-slate-500 dark:text-slate-400 sm:block">
                    {{ $paso['nombre'] }}
                </span>
                <span
                    role="tooltip"
                    class="pointer-events-none invisible absolute bottom-full left-1/2 z-50 mb-2 w-max max-w-xs -translate-x-1/2 rounded-lg bg-slate-900 px-3 py-2 text-center text-xs font-normal leading-5 text-white opacity-0 shadow-lg transition-opacity group-hover:visible group-hover:opacity-100 group-focus:visible group-focus:opacity-100 dark:bg-slate-100 dark:text-slate-900"
                >
                    <span class="block font-semibold">{{ $paso['nombre'] }}</span>
                    <span class="block text-slate-300 dark:text-slate-600">{{ $estadoEtiqueta }}</span>
                </span>
            </div>
            @if (! $loop->last)
                <div class="mt-3.5 h-px w-4 shrink-0 bg-slate-300 dark:bg-slate-600"></div>
            @endif
        @endforeach
    </div>
@endif
