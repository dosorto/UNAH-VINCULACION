<?php

namespace App\Support\Pasantias;

use App\Models\Pasantia;
use App\Support\Fichas\FirmaImagen;
use Illuminate\Support\Str;
use RuntimeException;

/** Requisitos propios de los documentos derivados del FORM-DVUS-013. */
final class PasantiaDocumentoRequirements
{
    public const SOLICITUD = 'solicitud_practica';
    public const AUTORIZACION = 'autorizacion_pps';

    public static function missing(Pasantia $registro, string $tipo): array
    {
        $required = match ($tipo) {
            self::SOLICITUD => [
                'nombre_estudiante' => 'nombre completo del estudiante',
                'numero_cuenta' => 'número de cuenta',
                'carrera' => 'carrera',
                'facultad_centro' => 'facultad o centro',
                'nombre_institucion' => 'nombre de la institución',
                'modalidad_ejecucion' => 'modalidad',
                'total_horas' => 'total de horas',
                'nombre_contacto_directo' => 'nombre del destinatario',
                'cargo_contacto_directo' => 'cargo del destinatario',
            ],
            self::AUTORIZACION => [
                'nombre_estudiante' => 'nombre completo del estudiante',
                'numero_cuenta' => 'número de cuenta',
                'carrera' => 'carrera',
                'facultad_centro' => 'facultad o centro',
                'nombre_institucion' => 'nombre de la institución',
                'modalidad_ejecucion' => 'modalidad',
                'total_horas' => 'total de horas',
                'fecha_inicio' => 'fecha de inicio',
                'fecha_finalizacion' => 'fecha de finalización',
                'nombre_firma_coordinador' => 'nombre del coordinador',
                'firma_coordinador' => 'firma disponible del coordinador',
            ],
            default => throw new RuntimeException('Tipo de documento de Pasantía no válido.'),
        };

        return collect($required)->filter(function ($label, $field) use ($registro): bool {
            if (self::isBlank($registro->{$field})) {
                return true;
            }

            return $field === 'firma_coordinador'
                && FirmaImagen::resolver((string) $registro->{$field}, true) === null;
        })->all();
    }

    public static function validate(Pasantia $registro, string $tipo): void
    {
        $missing = self::missing($registro, $tipo);
        if ($missing === []) {
            return;
        }

        $documento = $tipo === self::AUTORIZACION ? 'la AUTORIZACIÓN DE PASANTÍA' : 'la SOLICITUD DE PASANTÍA';
        throw new RuntimeException(sprintf('No se puede generar %s. Complete: %s.', $documento, implode(', ', array_values($missing))));
    }

    private static function isBlank(mixed $value): bool
    {
        if ($value instanceof \DateTimeInterface) {
            return false;
        }

        if ($value === null || trim((string) $value) === '') {
            return true;
        }

        return in_array(Str::lower(trim((string) $value)), ['null', 'pendiente'], true)
            || (is_numeric($value) && (int) $value < 1);
    }
}
