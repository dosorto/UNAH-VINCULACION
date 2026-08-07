@props(['stepper' => []])

@if (empty($stepper))
    <span class="text-xs text-slate-400">Sin enviar</span>
@else
    <div class="flex items-center gap-1">
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
            @endphp
            <div class="flex flex-col items-center gap-0.5" title="{{ $paso['detalle'] ?? $paso['nombre'] }}">
                <span class="flex h-6 w-6 items-center justify-center rounded-full text-[10px] font-bold {{ $colorClase }}">
                    {{ $icono }}
                </span>
                <span class="hidden text-[9px] text-slate-400 dark:text-slate-500 max-w-[40px] text-center leading-tight truncate sm:block">
                    {{ \Illuminate\Support\Str::limit($paso['nombre'], 8) }}
                </span>
            </div>
            @if (! $loop->last)
                <div class="h-px w-3 bg-slate-300 dark:bg-slate-600 mb-3 shrink-0"></div>
            @endif
        @endforeach
    </div>
@endif
