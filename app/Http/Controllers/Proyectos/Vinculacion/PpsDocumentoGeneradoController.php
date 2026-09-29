<?php

namespace App\Http\Controllers\Proyectos\Vinculacion;

use App\Http\Controllers\Controller;
use App\Models\PpsDocumentoGenerado;
use Illuminate\Support\Facades\Storage;

class PpsDocumentoGeneradoController extends Controller
{
    /** Con ?ver=1 el PDF se muestra en el visor del navegador; si no, se descarga. */
    public function __invoke(PpsDocumentoGenerado $documento)
    {
        $pps = $documento->pps;
        abort_unless($pps && $pps->puedeDescargarPdf(auth()->id(), auth()->user()), 403);
        abort_unless(Storage::disk('local')->exists($documento->archivo), 404);

        return Storage::disk('local')->response(
            $documento->archivo,
            $documento->nombre_original,
            ['Content-Type' => 'application/pdf'],
            request()->boolean('ver') ? 'inline' : 'attachment'
        );
    }
}
