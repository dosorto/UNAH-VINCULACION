<?php

namespace App\Support\PpsServicioSocial;

use App\Models\PpsServicioSocial;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Requisitos de negocio para los documentos derivados del FORM-DVUS-014.
 *
 * El formulario puede permanecer como borrador con valores incompletos. Esta
 * clase es el único lugar donde se decide qué necesita cada documento para
 * ser generado.
 */

final class PpsDocumentoRequirements
{
    public const SOLICITUD = 'solicitud_practica';
    public const AUTORIZACION = 'autorizacion_pps';
    public const BORRADOR_FECHA = '1900-01-01';

    /**
     * @return array<string, string>
     */
    public static function missing(PpsServicioSocial $registro, string $tipo, bool $requireCoordinator = true): array
    {
        $fields = FormDvus014Data::from($registro)['fields'];
        $required = match ($tipo) {
            self::SOLICITUD => [
                'nombre_estudiante' => 'nombre completo del estudiante',
                'numero_cuenta' => 'número de cuenta',
                'carrera' => 'carrera',
                'facultad_centro' => 'facultad o centro',
                'nombre_institucion' => 'nombre de la empresa o institución',
                'modalidad_ejecucion' => 'modalidad',
                'total_horas' => 'total de horas de PPS',
                'nombre_jefe_directo' => 'nombre del destinatario de la empresa',
                'cargo_jefe_directo' => 'cargo del destinatario de la empresa',
            ],
            self::AUTORIZACION => [
                'fecha_inicio' => 'fecha de inicio',
                'fecha_finalizacion' => 'fecha de finalización',
                'nombre_institucion' => 'nombre de la empresa o institución',
                'modalidad_ejecucion' => 'modalidad',
                'total_horas' => 'total de horas de PPS',
                'carrera' => 'carrera',
                'facultad_centro' => 'facultad o centro',
                'nombre_estudiante' => 'nombre completo del estudiante',
                'numero_cuenta' => 'número de cuenta',
            ],
            default => throw new RuntimeException('Tipo de documento PPS/SS no válido.'),
        };

        $missing = [];

        foreach ($required as $field => $label) {
            if (self::isBlank($fields[$field] ?? null, $field)) {
                $missing[$field] = $label;
            }
        }

        if ($requireCoordinator && ($tipo === self::SOLICITUD || $tipo === self::AUTORIZACION)) {
            $coordinador = FormDvus014Data::coordinadorFirma($registro);

            if (! $coordinador || self::isBlank($coordinador->empleado?->nombre_completo)) {
                $missing['coordinador_responsable'] = 'coordinador responsable con nombre disponible';
            } elseif ($tipo === self::AUTORIZACION && ! FormDvus014Data::firmaDisponible($coordinador->empleado)) {
                $missing['coordinador_firma'] = 'firma disponible del coordinador responsable';
            }
        }

        return $missing;
    }

    public static function validate(PpsServicioSocial $registro, string $tipo): void
    {
        $missing = self::missing($registro, $tipo);

        if ($missing === []) {
            return;
        }

        $documento = $tipo === self::AUTORIZACION ? 'la AUTORIZACIÓN DE PPS' : 'la SOLICITUD DE PRÁCTICA';

        throw new RuntimeException(sprintf(
            'No se puede generar %s. Complete: %s.',
            $documento,
            implode(', ', array_values($missing))
        ));
    }

    public static function isBlank(mixed $value, ?string $field = null): bool
    {
        if ($value instanceof \DateTimeInterface) {
            return $field === 'fecha_inicio' || $field === 'fecha_finalizacion'
                ? $value->format('Y-m-d') === self::BORRADOR_FECHA
                : false;
        }

        if ($value === null) {
            return true;
        }

        $value = trim((string) $value);

        if ($value === '' || ($field === 'total_horas' && (int) $value < 1)) {
            return true;
        }

        return in_array(Str::lower(Str::ascii($value)), [
            'pendiente',
            'borrador sin titulo',
            'null',
            self::BORRADOR_FECHA,
        ], true);
    }
}
