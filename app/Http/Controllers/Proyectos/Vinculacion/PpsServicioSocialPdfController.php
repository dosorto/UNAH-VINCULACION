<?php

namespace App\Http\Controllers\Proyectos\Vinculacion;

use App\Http\Controllers\Controller;
use App\Models\PpsServicioSocial;
use App\Services\PpsServicioSocial\FormDvus014DocumentService;

class PpsServicioSocialPdfController extends Controller
{
    /**
     * Ficha FORM-DVUS-014 generada desde la plantilla de Word con LibreOffice. Con ?ver=1 se
     * muestra en el visor del navegador; si no, se descarga.
     */
    public function __invoke(int $id, FormDvus014DocumentService $documentos)
    {
        $registro = PpsServicioSocial::findOrFail($id);

        abort_unless($registro->puedeConsultarse(auth()->id(), auth()->user()), 403);

        try {
            $pdf = $documentos->generatePdf($registro);
        } catch (\Throwable $e) {
            report($e);

            return response()->view('pdf.documento-no-disponible', [], 503);
        }

        $nombre = "FORM-DVUS-014-{$registro->id}.pdf";

        return request()->boolean('ver')
            ? response()->file($pdf, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$nombre.'"',
                'Cache-Control' => 'private, no-cache',
            ])
            : response()->download($pdf, $nombre, ['Content-Type' => 'application/pdf']);
    }
}
