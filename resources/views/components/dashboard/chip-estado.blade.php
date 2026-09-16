@props([
    'nombre' => null,
    // sm | xs
    'tamano' => 'sm',
])

@php
    // Este match estaba copiado en ocho vistas con colores distintos para el
    // mismo estado. Aquí queda una sola vez y con variantes de modo oscuro,
    // que la mayoría de aquellas copias no tenían.
    $clave = mb_strtolower(trim((string) $nombre));

    $color = match (true) {
        $clave === '' => 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
        in_array($clave, ['borrador', 'autoguardado'], true) => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
        in_array($clave, ['subsanacion', 'subsanación', 'subsanar documento'], true) => 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300',
        in_array($clave, ['rechazado', 'cancelado'], true) => 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300',
        $clave === 'en curso' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
        $clave === 'finalizado' => 'bg-primary-50 text-primary-700 dark:bg-primary-900 dark:text-primary-200',
        in_array($clave, ['aprobado', 'inscrito', 'actualizacion realizada'], true) => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
        default => 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
    };

    $medida = $tamano === 'xs'
        ? 'px-1.5 py-0.5 text-[10px]'
        : 'px-2 py-0.5 text-[11px]';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full font-semibold {$medida} {$color}"]) }}>
    {{ $nombre ?: 'Sin estado' }}
</span>
