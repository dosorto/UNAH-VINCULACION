<?php

namespace App\Support\InformeFinal;

final class ParticipacionEstudiantil
{
    public const ASIGNATURA = 'practica_asignatura';
    public const PPS = 'pps_servicio_social';
    public const VOLUNTARIADO = 'voluntariado';

    public static function normalizar(?string $tipo): string
    {
        $tipo = mb_strtolower(trim((string) $tipo));

        return match (true) {
            str_contains($tipo, 'volunt') => self::VOLUNTARIADO,
            str_contains($tipo, 'pps'), str_contains($tipo, 'servicio') => self::PPS,
            default => self::ASIGNATURA,
        };
    }

    public static function etiqueta(string $tipo): string
    {
        return match ($tipo) {
            self::ASIGNATURA => 'Práctica de asignatura',
            self::PPS => 'PPS / Servicio Social',
            self::VOLUNTARIADO => 'Voluntariado',
            default => $tipo,
        };
    }

    public static function codigo(string $tipo): string
    {
        return match ($tipo) {
            self::ASIGNATURA => 'ASIG',
            self::PPS => 'PPS',
            self::VOLUNTARIADO => 'VOL',
            default => $tipo,
        };
    }
}
