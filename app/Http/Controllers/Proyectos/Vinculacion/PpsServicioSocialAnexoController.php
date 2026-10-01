<?php

namespace App\Http\Controllers\Proyectos\Vinculacion;

use App\Http\Controllers\Controller;
use App\Models\PpsServicioSocial;
use App\Services\PpsServicioSocial\FormDvus014DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PpsServicioSocialAnexoController extends Controller
{
    /**
     * Muestra el anexo en el visor (los de Word, convertidos a PDF con LibreOffice) o, con
     * ?download=1, descarga el archivo original.
     */
    public function __invoke(Request $request, int $id, string $tipo, FormDvus014DocumentService $documentos)
    {
        $registro = PpsServicioSocial::findOrFail($id);

        abort_unless($registro->puedeConsultarse(auth()->id(), auth()->user()), 403);

        $path = match ($tipo) {
            'carta-formalizacion' => $registro->archivo_carta_formalizacion,
            'convenio-marco' => $registro->archivo_convenio_marco,
            default => null,
        };

        abort_unless(filled($path), 404);

        $path = $this->normalizePublicPath($path);

        abort_unless(Storage::disk('public')->exists($path), 404);

        $filename = basename($path);

        if ($request->boolean('download')) {
            return Storage::disk('public')->download($path, $filename);
        }

        if (in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['doc', 'docx'], true)) {
            try {
                $pdf = $documentos->attachmentPdf(Storage::disk('public')->path($path));
            } catch (\Throwable $e) {
                report($e);

                return response()->view('pdf.documento-no-disponible', [], 503);
            }

            return response()->file($pdf, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.(Str::slug(pathinfo($filename, PATHINFO_FILENAME)) ?: 'anexo').'.pdf"',
                'Cache-Control' => 'private, no-cache',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        return Storage::disk('public')->response($path, $filename, ['X-Content-Type-Options' => 'nosniff']);
    }

    private function normalizePublicPath(string $path): string
    {
        $path = ltrim($path, '/');
        $path = preg_replace('#^storage/#', '', $path);
        $path = preg_replace('#^public/#', '', $path);
        $path = preg_replace('#^app/public/#', '', $path);

        return $path;
    }
}
