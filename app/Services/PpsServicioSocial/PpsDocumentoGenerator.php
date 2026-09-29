<?php

namespace App\Services\PpsServicioSocial;

use App\Models\PpsDocumentoGenerado;
use App\Models\PpsServicioSocial;
use App\Models\Personal\Empleado;
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

    public function __construct(private readonly PpsSolicitudPracticaDocumento $solicitud) {}

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

        $version = ((int) $pps->documentosGenerados()->where('tipo', $tipo)->max('version')) + 1;
        $nombre = $tipo.'-'.$pps->codigo_registro.'-v'.$version.'.pdf';
        $ruta = 'pps-servicio-social/generados/'.$pps->id.'/'.$nombre;
        // La solicitud sale de su plantilla de Word (LibreOffice); la autorización, de su vista.
        $contenido = $tipo === PpsDocumentoRequirements::SOLICITUD
            ? $this->solicitud->pdf($pps, $this->firmanteSolicitud($usuarioId))
            : Pdf::loadView('pdf.pps-servicio-social.generado', ['pps' => $pps, 'tipo' => $tipo, 'formData' => FormDvus014Data::from($pps)])
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

    /** La solicitud la firma el coordinador que llena el formulario, con su firma registrada. */
    private function firmanteSolicitud(int $usuarioId): array
    {
        $empleado = User::with('empleado.firma')->find($usuarioId)?->empleado;

        if (! $empleado || blank($empleado->nombre_completo)) {
            throw new RuntimeException('No se puede generar la SOLICITUD DE PRÁCTICA: su usuario no tiene un empleado con nombre registrado.');
        }

        return [
            'nombre' => $empleado->nombre_completo,
            'cargo' => self::cargoFirmante($empleado->sexo),
            'firma' => self::imagenFirma($empleado),
        ];
    }

    /** Imagen (bytes) de la firma registrada del empleado, o null si no tiene una usable. */
    public static function imagenFirma(?Empleado $empleado): ?string
    {
        $imagen = FirmaImagen::resolver(trim((string) $empleado?->firma?->ruta_storage), true);

        if (filled($imagen['path'] ?? null)) {
            return @file_get_contents($imagen['path']) ?: null;
        }

        $src = (string) ($imagen['src'] ?? '');

        return str_starts_with($src, 'data:image/')
            ? (base64_decode(explode(',', $src, 2)[1] ?? '', true) ?: null)
            : null;
    }

    public static function cargoFirmante(?string $sexo): string
    {
        return $sexo === 'Femenino' ? 'Coordinadora Académica' : 'Coordinador Académico';
    }
}
