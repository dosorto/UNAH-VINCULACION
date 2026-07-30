<?php

namespace App\Http\Controllers\Proyectos\InformeFinal;

use App\Http\Controllers\Controller;
use App\Models\InformeFinal\InformeFinalAnexo;
use App\Services\InformeFinal\InformeFinalProyectoWorkflowService;
use Illuminate\Support\Facades\Storage;

class InformeFinalAnexoController extends Controller
{
    public function mostrar(InformeFinalAnexo $anexo)
    {
        $this->autorizarAcceso($anexo);
        $ruta = $this->resolverRutaSegura($anexo);
        $nombre = $this->obtenerNombreDescarga($anexo, $ruta);
        $mime = Storage::disk('public')->mimeType($ruta) ?: 'application/octet-stream';

        $respuesta = str_starts_with($mime, 'application/pdf') || str_starts_with($mime, 'image/')
            ? Storage::disk('public')->response($ruta, $nombre, ['Content-Type' => $mime])
            : Storage::disk('public')->download($ruta, $nombre);

        $respuesta->headers->set('Content-Disposition', 'inline; filename="'.$nombre.'"');

        return $respuesta;
    }

    public function descargar(InformeFinalAnexo $anexo)
    {
        $this->autorizarAcceso($anexo);
        $ruta = $this->resolverRutaSegura($anexo);
        $nombre = $this->obtenerNombreDescarga($anexo, $ruta);

        $respuesta = Storage::disk('public')->download($ruta, $nombre);
        $respuesta->headers->set('Content-Disposition', 'attachment; filename="'.$nombre.'"');

        return $respuesta;
    }

    private function autorizarAcceso(InformeFinalAnexo $anexo): void
    {
        $informe = $anexo->informe()->with('proyecto')->firstOrFail();
        abort_unless(
            app(InformeFinalProyectoWorkflowService::class)->usuarioPuedeVer($informe, request()->user()),
            403
        );
    }

    private function resolverRutaSegura(InformeFinalAnexo $anexo): string
    {
        $ruta = $this->rutaLocal($anexo->archivo);
        abort_unless($ruta && Storage::disk('public')->exists($ruta), 404);

        return $ruta;
    }

    private function obtenerNombreDescarga(InformeFinalAnexo $anexo, string $ruta): string
    {
        return $anexo->nombre_archivo ?: basename($ruta);
    }

    private function rutaLocal(?string $ruta): ?string
    {
        if (blank($ruta) || filter_var($ruta, FILTER_VALIDATE_URL)) return null;
        $ruta = preg_replace('#^(storage|public|app/public)/#', '', ltrim($ruta, '/'));
        return str_contains($ruta, '..') ? null : $ruta;
    }
}
