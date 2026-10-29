{{--
    Progreso del flujo en una sola línea: fase, una barra por etapa y la etapa en curso.
    El detalle de cada etapa sale en un tooltip al pasar el mouse o al enfocar con el teclado.
    El tooltip se teletransporta al <body> y se ancla con x-anchor para que el contenedor
    con scroll de la tabla no lo recorte.
--}}
@props(['stepper' => [], 'fase' => 'Aprobación'])

@if (empty($stepper))
    <span class="text-xs text-slate-400">Sin enviar</span>
@else
    @php
        // [etiqueta, círculo del tooltip, icono, segmento de la barra]
        $estados = [
            'aprobado' => ['Aprobada', 'bg-emerald-500 text-white', '✓', 'bg-emerald-500'],
            'adoptado' => ['Completada antes de adoptar el flujo', 'bg-sky-500 text-white', '↑', 'bg-sky-500'],
            'rechazado' => ['En subsanación', 'bg-red-500 text-white', '✕', 'bg-red-500'],
            'actual' => ['Etapa actual', 'bg-amber-400 text-white', '●', 'bg-amber-400'],
            'pendiente' => ['Pendiente', 'bg-slate-200 text-slate-500 dark:bg-slate-700 dark:text-slate-300', '○', 'bg-slate-200 dark:bg-slate-700'],
        ];
        $estadoDe = fn (array $paso) => $estados[$paso['estado']] ?? $estados['pendiente'];
        $total = count($stepper);
        $completadas = collect($stepper)->whereIn('estado', ['aprobado', 'adoptado'])->count();
        $indiceActual = collect($stepper)->search(fn (array $paso) => in_array($paso['estado'], ['actual', 'rechazado'], true));
        $resumen = match (true) {
            $indiceActual !== false && $stepper[$indiceActual]['estado'] === 'rechazado' => 'En subsanación: '.$stepper[$indiceActual]['nombre'],
            $indiceActual !== false => $stepper[$indiceActual]['nombre'],
            $completadas === $total => 'Flujo completado',
            default => 'Pendiente de revisión',
        };
        $numeroEtapa = $indiceActual !== false ? $indiceActual + 1 : $completadas;
        $faseClase = match ($fase) {
            'Informe Intermedio' => 'bg-sky-50 text-sky-700 dark:bg-sky-950 dark:text-sky-300',
            'Informe Final' => 'bg-purple-50 text-purple-700 dark:bg-purple-950 dark:text-purple-300',
            default => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
        };
    @endphp
    <div x-data="{ abierto: false }" x-id="['etapas']" class="flex max-w-full">
        <div
            x-ref="disparador"
            tabindex="0"
            x-on:mouseenter="abierto = true"
            x-on:mouseleave="abierto = false"
            x-on:focus="abierto = true"
            x-on:blur="abierto = false"
            x-on:keydown.escape="abierto = false"
            x-bind:aria-describedby="$id('etapas')"
            class="flex min-w-0 max-w-full cursor-default items-center gap-2 rounded-md outline-none focus-visible:ring-2 focus-visible:ring-primary-400"
        >
            <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide {{ $faseClase }}">{{ $fase }}</span>
            <span class="flex shrink-0 items-center gap-0.5" aria-hidden="true">
                @foreach ($stepper as $paso)
                    <span class="h-1.5 w-3 rounded-full {{ $estadoDe($paso)[3] }}"></span>
                @endforeach
            </span>
            <span class="min-w-0 truncate text-xs text-slate-700 dark:text-slate-200">{{ $resumen }}</span>
            <span class="shrink-0 text-[11px] text-slate-400">{{ $numeroEtapa }}/{{ $total }}</span>
        </div>

        <template x-teleport="body">
            <div
                x-show="abierto"
                x-cloak
                x-anchor.bottom-start.offset.6="$refs.disparador"
                x-transition.opacity.duration.150ms
                x-bind:id="$id('etapas')"
                role="tooltip"
                class="pointer-events-none z-[80] w-72 rounded-lg bg-slate-900 p-3 text-xs text-white shadow-lg dark:bg-slate-100 dark:text-slate-900"
            >
                <p class="font-semibold">{{ $fase }}</p>
                <p class="mb-2 text-slate-300 dark:text-slate-600">{{ $completadas }} de {{ $total }} etapas completadas</p>
                <ol class="space-y-1.5">
                    @foreach ($stepper as $paso)
                        @php [$etiqueta, $color, $icono] = $estadoDe($paso); @endphp
                        <li class="flex items-center gap-2">
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[10px] font-bold {{ $color }}">{{ $icono }}</span>
                            <span class="min-w-0 flex-1 truncate">{{ $paso['nombre'] }}</span>
                            <span class="shrink-0 text-slate-300 dark:text-slate-600">{{ $etiqueta }}</span>
                        </li>
                    @endforeach
                </ol>
            </div>
        </template>
    </div>
@endif
