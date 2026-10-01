<?php

namespace App\Services\PpsServicioSocial;

use App\Support\PpsServicioSocial\TratamientoDestinatario;

/**
 * SOLICITUD DE PRÁCTICA del FORM-DVUS-014 (storage/app/templates/solicitud-practica-pps.docx),
 * dirigida al destinatario de la institución y firmada por quien llena el formulario.
 */
class PpsSolicitudPracticaDocumento extends PpsCartaDocumento
{
    protected function plantilla(): string
    {
        return (string) config('documents.solicitud_practica_pps_template');
    }

    protected function documento(): string
    {
        return 'la SOLICITUD DE PRÁCTICA';
    }

    protected function valoresPropios(array $campos, array $firmante): array
    {
        $modalidad = self::texto($campos, 'modalidad_ejecucion');

        return [
            'destinatario_tratamiento' => mb_strtoupper(self::texto($campos, 'destinatario_tratamiento')),
            'destinatario_nombre' => mb_strtoupper(self::texto($campos, 'destinatario_nombre')),
            'destinatario_cargo' => mb_strtoupper(self::texto($campos, 'destinatario_cargo')),
            'institucion' => mb_strtoupper(self::texto($campos, 'nombre_institucion')),
            'saludo' => TratamientoDestinatario::saludo(self::texto($campos, 'destinatario_tratamiento')),
            'practica' => self::esServicioSocial($campos) ? 'el servicio social' : 'la práctica profesional supervisada',
            'modalidad' => match ($modalidad) {
                '100% presencial' => 'presencial',
                'Híbrida' => 'híbrida (presencial y teletrabajo)',
                'Teletrabajo' => 'de teletrabajo',
                default => mb_strtolower($modalidad),
            },
        ];
    }
}
