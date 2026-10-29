@props([
    'valor',
    'etiqueta',
    'icono' => null,
    'destacada' => false,
    // A dónde lleva la cifra: una ruta con el detalle ya filtrado, o «#id» de un
    // panel-expandible de la misma página, que se abre. Sin href es solo texto.
    'href' => null,
])

{{--
    Cifra de resumen dentro de la banda azul de la cabecera.

    Existe para las magnitudes que se leen una vez y no se vuelven a mirar
    —horas acumuladas, aporte en lempiras, personas alcanzadas—: como tarjeta
    propia ocupaban media pantalla para tres números.
--}}
@php
    $esPanel = $href && str_starts_with($href, '#');
    $etiquetaHtml = $href ? 'a' : 'div';
@endphp
<{{ $etiquetaHtml }}
    @if ($href)
        href="{{ $href }}"
        @if ($esPanel) x-on:click.prevent="$dispatch('abrir-panel', '{{ substr($href, 1) }}')" @else wire:navigate @endif
    @endif
    {{ $attributes->merge(['class' => 'min-w-0'.($href ? ' group -m-2 block rounded-xl p-2 transition hover:bg-white/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-300' : '')]) }}
>
    <p class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">
        @if ($icono)
            @svg($icono, ['class' => 'h-3 w-3 shrink-0 text-amber-300'])
        @endif
        <span class="truncate">{{ $etiqueta }}</span>
        @if ($href)
            @svg('heroicon-o-arrow-right', ['class' => 'h-3 w-3 shrink-0 opacity-0 transition group-hover:opacity-100 group-focus-visible:opacity-100'])
        @endif
    </p>

    <p class="mt-1 truncate {{ $destacada ? 'text-3xl' : 'text-xl' }} font-black leading-none text-white">
        {{ is_numeric($valor) ? number_format((float) $valor) : $valor }}
    </p>
</{{ $etiquetaHtml }}>
