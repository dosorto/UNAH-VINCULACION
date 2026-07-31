<?php

namespace App\Http\Controllers\Proyectos\Vinculacion;

use App\Http\Controllers\Controller;
use App\Models\PpsServicioSocial;
use App\Support\PpsServicioSocial\FormDvus014Data;
use PDF;

class PpsServicioSocialPdfController extends Controller
{
    public function __invoke(int $id)
    {
        $registro = PpsServicioSocial::with(['flujoAprobacion', 'etapaActual'])->findOrFail($id);

        abort_unless($registro->puedeDescargarPdf(auth()->id(), auth()->user()), 403);

        $pdf = PDF::loadView('pdf.pps-servicio-social.form-014', [
            'registro' => $registro,
            'formData' => FormDvus014Data::from($registro),
        ])->setPaper('letter', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans')
            ->setOption('dpi', 96);

        return $pdf->download("FORM-DVUS-014-{$registro->id}.pdf");
    }
}
