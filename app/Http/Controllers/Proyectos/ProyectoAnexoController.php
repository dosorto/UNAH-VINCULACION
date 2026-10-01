<?php

namespace App\Http\Controllers\Proyectos;

use App\Http\Controllers\Controller;
use App\Models\Proyecto\Anexo;
use App\Models\Proyecto\Proyecto;
use App\Services\Proyecto\ProyectoAnexoPreviewService;
use App\Services\Proyecto\ProyectoDetalleAuthorization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProyectoAnexoController extends Controller
{
    /** Muestra un anexo en el visor o entrega su archivo original con ?download=1. */
    public function __invoke(
        Request $request,
        Proyecto $proyecto,
        Anexo $anexo,
        ProyectoDetalleAuthorization $authorization,
        ProyectoAnexoPreviewService $preview,
    ) {
        abort_unless((int) $anexo->proyecto_id === (int) $proyecto->id, 404);
        abort_unless($authorization->puedeVer($proyecto, $request->user()), 403);

        $path = $this->normalizePublicPath((string) $anexo->documento_url);
        abort_unless($path !== '' && Storage::disk('public')->exists($path), 404);

        $filename = $anexo->nombre_archivo ?: basename($path);
        if ($request->boolean('download')) {
            return Storage::disk('public')->download($path, $filename);
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($extension, ['doc', 'docx', 'xls', 'xlsx'], true)) {
            try {
                $pdf = $preview->officeToPdf(Storage::disk('public')->path($path));
            } catch (\Throwable $e) {
                report($e);

                return response()->view('pdf.documento-no-disponible', [], 503);
            }

            $previewName = Str::slug(pathinfo($filename, PATHINFO_FILENAME)) ?: 'anexo';

            return response()->file($pdf, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$previewName.'.pdf"',
                'Cache-Control' => 'private, no-cache',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        abort_unless(in_array($extension, ['pdf', 'png', 'jpg', 'jpeg'], true), 415);

        return Storage::disk('public')->response($path, $filename, [
            'Cache-Control' => 'private, no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function normalizePublicPath(string $path): string
    {
        $path = ltrim($path, '/');
        $path = preg_replace('#^(storage|public|app/public)/#', '', $path);

        return (string) $path;
    }
}
