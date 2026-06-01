<?php

namespace App\Http\Controllers\Proyectos\Vinculacion;

use App\Http\Controllers\Controller;
use App\Models\PpsServicioSocial;
use PDF;

class PpsServicioSocialPdfController extends Controller
{
    public function __invoke(int $id)
    {
        $registro = PpsServicioSocial::findOrFail($id);

        abort_unless($registro->estado === PpsServicioSocial::ESTADO_APROBADO, 403);
        abort_unless($registro->puedeDescargarPdf(auth()->id(), auth()->user()), 403);

        $pdf = PDF::loadView('pdf.pps-servicio-social.form-015-016', [
            'registro' => $registro,
        ])->setPaper('letter', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans')
            ->setOption('dpi', 96);

        return $pdf->download("FORM-DVUS-015-016-{$registro->id}.pdf");
    }
}
