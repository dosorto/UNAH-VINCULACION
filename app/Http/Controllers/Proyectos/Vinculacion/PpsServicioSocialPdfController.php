<?php

namespace App\Http\Controllers\Proyectos\Vinculacion;

use App\Http\Controllers\Controller;
use App\Models\PpsServicioSocial;
use App\Support\PpsServicioSocial\FormDvus014Data;
use Illuminate\Support\Facades\Log;
use PDF;

class PpsServicioSocialPdfController extends Controller
{
    public function __invoke(int $id)
    {
        $registro = PpsServicioSocial::with(['flujoAprobacion', 'etapaActual'])->findOrFail($id);

        abort_unless($registro->puedeDescargarPdf(auth()->id(), auth()->user()), 403);

        Log::info('Fechas PPS para PDF', [
            'registro_id' => $registro->id,
            'codigo_registro' => $registro->codigo_registro,
            'fecha_inicio_raw' => $registro->fecha_inicio,
            'fecha_finalizacion_raw' => $registro->fecha_finalizacion,
            'fecha_inicio_formateada' => optional($registro->fecha_inicio)->format('d/m/Y'),
            'fecha_finalizacion_formateada' => optional($registro->fecha_finalizacion)->format('d/m/Y'),
        ]);

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
