<?php

namespace App\Services\PpsServicioSocial;

use App\Models\PpsDocumentoGenerado;
use App\Models\PpsServicioSocial;
use App\Models\User;
use App\Support\Fichas\FirmaImagen;
use App\Support\PpsServicioSocial\FormDvus014Data;
use App\Support\PpsServicioSocial\PpsDocumentoRequirements;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PpsDocumentoGenerator
{
    public const SOLICITUD = 'solicitud_practica';
    public const AUTORIZACION = 'autorizacion_pps';

    public function generarSolicitud(PpsServicioSocial $pps, int $usuarioId): PpsDocumentoGenerado
    {
        return $this->generar($pps, PpsDocumentoRequirements::SOLICITUD, $usuarioId);
    }

    public function generarAutorizacion(PpsServicioSocial $pps, int $usuarioId): PpsDocumentoGenerado
    {
        return $this->generar($pps, PpsDocumentoRequirements::AUTORIZACION, $usuarioId);
    }

    private function generar(PpsServicioSocial $pps, string $tipo, int $usuarioId): PpsDocumentoGenerado
    {
        $pps->loadMissing([
            'firmasDeEtapa.empleado.firma',
            'firmasDeEtapa.flujoEtapa',
            'firmasDeEtapa.cargo_firma.tipoCargoFirma',
        ]);
        PpsDocumentoRequirements::validate($pps, $tipo);

        $formData = FormDvus014Data::from($pps);
        $firmante = $tipo === PpsDocumentoRequirements::SOLICITUD ? $this->firmanteSolicitud($pps, $usuarioId) : null;
        $version = ((int) $pps->documentosGenerados()->where('tipo', $tipo)->max('version')) + 1;
        $nombre = $tipo.'-'.$pps->codigo_registro.'-v'.$version.'.pdf';
        $ruta = 'pps-servicio-social/generados/'.$pps->id.'/'.$nombre;
        $contenido = Pdf::loadView('pdf.pps-servicio-social.generado', compact('pps', 'tipo', 'formData', 'firmante'))
            ->setPaper('letter')
            ->setOption('isRemoteEnabled', false)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('defaultFont', 'Arial')
            ->setOption('chroot', realpath(base_path()))
            ->output();
        Storage::disk('local')->put($ruta, $contenido);

        return $pps->documentosGenerados()->create([
            'tipo' => $tipo,
            'archivo' => $ruta,
            'nombre_original' => $nombre,
            'version' => $version,
            'generado_por' => $usuarioId,
            'generado_en' => now(),
        ]);
    }

    /** La solicitud la firma quien llena el formulario, con el cargo que indicó en él. */
    private function firmanteSolicitud(PpsServicioSocial $pps, int $usuarioId): array
    {
        $empleado = User::with('empleado.firma')->find($usuarioId)?->empleado;

        if (! $empleado || blank($empleado->nombre_completo)) {
            throw new RuntimeException('No se puede generar la SOLICITUD DE PRÁCTICA: su usuario no tiene un empleado con nombre registrado.');
        }

        return [
            'nombre' => $empleado->nombre_completo,
            'cargo' => filled($pps->solicitud_firmante_cargo)
                ? $pps->solicitud_firmante_cargo
                : self::cargoFirmantePorDefecto($empleado->sexo),
            'src' => FirmaImagen::resolver(trim((string) $empleado->firma?->ruta_storage), true)['src'] ?? null,
        ];
    }

    public static function cargoFirmantePorDefecto(?string $sexo): string
    {
        return $sexo === 'Femenino' ? 'Coordinadora Académica' : 'Coordinador Académico';
    }
}
