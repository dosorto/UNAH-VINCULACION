<?php

namespace App\Services\PpsServicioSocial;

use App\Models\PpsDocumentoGenerado;
use App\Models\PpsServicioSocial;
use App\Models\Personal\Empleado;
use App\Models\User;
use App\Support\Fichas\FirmaImagen;
use App\Support\PpsServicioSocial\PpsDocumentoRequirements;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PpsDocumentoGenerator
{
    public const SOLICITUD = 'solicitud_practica';
    public const AUTORIZACION = 'autorizacion_pps';

    public function __construct(
        private readonly PpsSolicitudPracticaDocumento $solicitud,
        private readonly PpsAutorizacionDocumento $autorizacion,
    ) {}

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
        PpsDocumentoRequirements::validate($pps, $tipo);

        $version = ((int) $pps->documentosGenerados()->where('tipo', $tipo)->max('version')) + 1;
        $nombre = $tipo.'-'.$pps->codigo_registro.'-v'.$version.'.pdf';
        $ruta = 'pps-servicio-social/generados/'.$pps->id.'/'.$nombre;
        // Ambas cartas salen de su plantilla de Word (LibreOffice) y las firma quien llena el formulario.
        $carta = $tipo === PpsDocumentoRequirements::SOLICITUD ? $this->solicitud : $this->autorizacion;
        $contenido = $carta->pdf($pps, $this->firmante($usuarioId, $tipo));
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

    /** Las cartas las firma el coordinador que llena el formulario, con su firma registrada. */
    private function firmante(int $usuarioId, string $tipo): array
    {
        $empleado = User::with('empleado.firma')->find($usuarioId)?->empleado;

        if (! $empleado || blank($empleado->nombre_completo)) {
            $documento = $tipo === PpsDocumentoRequirements::AUTORIZACION ? 'la AUTORIZACIÓN DE PPS' : 'la SOLICITUD DE PRÁCTICA';

            throw new RuntimeException("No se puede generar {$documento}: su usuario no tiene un empleado con nombre registrado.");
        }

        return [
            'nombre' => $empleado->nombre_completo,
            'cargo' => self::cargoFirmante($empleado->sexo),
            'sexo' => $empleado->sexo,
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
