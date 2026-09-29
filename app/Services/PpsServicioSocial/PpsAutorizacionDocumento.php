<?php

namespace App\Services\PpsServicioSocial;

use App\Support\PpsServicioSocial\PpsDocumentoRequirements;
use Illuminate\Support\Carbon;

/**
 * AUTORIZACIÓN DE PRÁCTICA PROFESIONAL del FORM-DVUS-014 (storage/app/templates/autorizacion-pps.docx):
 * el coordinador(a) que llena el formulario autoriza al estudiante a realizar la práctica en la
 * institución, con sus fechas y modalidad. Se genera al enviar el formulario a firmar.
 */
class PpsAutorizacionDocumento extends PpsCartaDocumento
{
    protected function plantilla(): string
    {
        return (string) config('documents.autorizacion_pps_template');
    }

    protected function documento(): string
    {
        return 'la AUTORIZACIÓN DE PPS';
    }

    protected function valoresPropios(array $campos, array $firmante): array
    {
        $servicioSocial = self::esServicioSocial($campos);
        $campus = $this->campus(self::texto($campos, 'facultad_centro'));
        $siglas = (string) $campus?->siglas;
        $nombreCampus = $siglas !== '' ? config('documents.nombre_campus.'.$siglas) : null;
        $modalidad = self::texto($campos, 'modalidad_ejecucion');
        $hoy = Carbon::now();

        return [
            'titulo' => $servicioSocial ? 'AUTORIZACIÓN DE SERVICIO SOCIAL' : 'AUTORIZACIÓN DE PRÁCTICA PROFESIONAL',
            'suscrito' => ($firmante['sexo'] ?? null) === 'Femenino' ? 'La suscrita Coordinadora' : 'El suscrito Coordinador',
            // «el Centro Universitario Regional del Litoral Pacífico – UNAH-CURLP».
            'centro' => $nombreCampus
                ? $nombreCampus.' – '.(str_starts_with($siglas, 'UNAH') ? $siglas : 'UNAH-'.$siglas)
                : self::texto($campos, 'facultad_centro'),
            'institucion' => self::texto($campos, 'nombre_institucion'),
            'practica' => $servicioSocial ? 'el Servicio Social' : 'la Práctica Profesional Supervisada',
            'modalidad' => match ($modalidad) {
                '100% presencial' => 'en modalidad presencial',
                'Híbrida' => 'en modalidad híbrida (presencial y teletrabajo)',
                'Teletrabajo' => 'en modalidad de teletrabajo',
                default => 'en modalidad '.mb_strtolower($modalidad),
            },
            'fecha_inicio' => $this->fecha($campos['fecha_inicio'] ?? null),
            'fecha_finalizacion' => $this->fecha($campos['fecha_finalizacion'] ?? null),
            // «firmo la presente en el CURLP»; en Ciudad Universitaria, «en la Ciudad Universitaria».
            'lugar_firma' => match (true) {
                $siglas === 'CU' && $nombreCampus !== null => $nombreCampus,
                $siglas !== '' => 'el '.$siglas,
                default => self::texto($campos, 'solicitud_lugar') ?: 'Tegucigalpa, M.D.C.',
            },
            'dias_mes' => ($hoy->day === 1 ? 'al primer día' : 'a los '.$hoy->day.' días')
                .' del mes de '.self::MESES[$hoy->month - 1].' del año '.$hoy->year,
        ];
    }

    private function fecha(mixed $fecha): string
    {
        return $fecha instanceof \DateTimeInterface && $fecha->format('Y-m-d') !== PpsDocumentoRequirements::BORRADOR_FECHA
            ? self::fechaLarga($fecha, true)
            : '';
    }
}
