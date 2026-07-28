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
        $informe = $anexo->informe()->with('proyecto')->firstOrFail();
        abort_unless(app(InformeFinalProyectoWorkflowService::class)->usuarioPuedeVer($informe, request()->user()), 403);

        $ruta = $this->rutaLocal($anexo->archivo);
        abort_unless($ruta && Storage::disk('public')->exists($ruta), 404);

        $nombre = $anexo->nombre_archivo ?: basename($ruta);
        $mime = Storage::disk('public')->mimeType($ruta) ?: 'application/octet-stream';

        return str_starts_with($mime, 'application/pdf') || str_starts_with($mime, 'image/')
            ? Storage::disk('public')->response($ruta, $nombre, ['Content-Type' => $mime])
            : Storage::disk('public')->download($ruta, $nombre);
    }

    private function rutaLocal(?string $ruta): ?string
    {
        if (blank($ruta) || filter_var($ruta, FILTER_VALIDATE_URL)) return null;
        $ruta = preg_replace('#^(storage|public|app/public)/#', '', ltrim($ruta, '/'));
        return str_contains($ruta, '..') ? null : $ruta;
    }
}
