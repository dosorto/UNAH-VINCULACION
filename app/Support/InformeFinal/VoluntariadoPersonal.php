<?php

namespace App\Support\InformeFinal;

use App\Models\Proyecto\Proyecto;

/**
 * Equivalencia entre el voluntariado personal de la UNAH del registro (ítem 13 del
 * FORM-DVUS-001, 15 del 015) y los tipos de voluntario del INF-001 (apartado IV).
 * Profesores y egresados permanentes no se planifican en el registro.
 */
final class VoluntariadoPersonal
{
    public const PROFESOR_HORA = 'profesor_hora';
    public const PAS = 'pas';

    /** Columnas del registro que suman en cada tipo del INF-001. */
    public const COLUMNAS_POR_TIPO = [
        self::PROFESOR_HORA => ['vol_profesores_hora'],
        self::PAS => ['vol_personal_administrativo', 'vol_personal_servicio', 'vol_asistentes_tecnicos'],
    ];

    /** Tipo del INF-001 de un voluntario del equipo según la categoría de su perfil. */
    public static function tipoParaCategoria(?string $categoria): string
    {
        return Proyecto::columnaVoluntariadoPersonal($categoria) === 'vol_profesores_hora'
            ? self::PROFESOR_HORA
            : self::PAS;
    }

    /** Cantidades planificadas en el registro: [tipo => ['hombres' => int, 'mujeres' => int]]. */
    public static function planificado(Proyecto $proyecto): array
    {
        return collect(self::COLUMNAS_POR_TIPO)
            ->map(fn (array $columnas) => [
                'hombres' => collect($columnas)->sum(fn (string $columna) => (int) $proyecto->{"{$columna}_hombres"}),
                'mujeres' => collect($columnas)->sum(fn (string $columna) => (int) $proyecto->{"{$columna}_mujeres"}),
            ])
            ->all();
    }
}
