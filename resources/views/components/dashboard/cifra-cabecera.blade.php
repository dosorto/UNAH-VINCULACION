@props([
    'valor',
    'etiqueta',
    'icono' => null,
    'destacada' => false,
])

{{--
    Cifra de resumen dentro de la banda azul de la cabecera.

    Existe para las magnitudes que se leen una vez y no se vuelven a mirar
    —horas acumuladas, aporte en lempiras, personas alcanzadas—: como tarjeta
    propia ocupaban media pantalla para tres números.
--}}
<div {{ $attributes->merge(['class' => 'min-w-0']) }}>
    <p class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">
        @if ($icono)
            @svg($icono, ['class' => 'h-3 w-3 shrink-0 text-amber-300'])
        @endif
        <span class="truncate">{{ $etiqueta }}</span>
    </p>

    <p class="mt-1 truncate {{ $destacada ? 'text-3xl' : 'text-xl' }} font-black leading-none text-white">
        {{ is_numeric($valor) ? number_format((float) $valor) : $valor }}
    </p>
</div>
