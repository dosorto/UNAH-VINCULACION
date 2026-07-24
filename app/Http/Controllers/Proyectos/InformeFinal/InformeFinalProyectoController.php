<?php

namespace App\Http\Controllers\Proyectos\InformeFinal;

use App\Http\Controllers\Controller;
use App\Models\InformeFinal\InformeFinalProyecto;
use App\Services\InformeFinal\InformeFinalPdfGenerator;
use App\Services\InformeFinal\InformeFinalProyectoWorkflowService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

class InformeFinalProyectoController extends Controller
{
    public function preview(InformeFinalProyecto $informe, InformeFinalPdfGenerator $generator): View
    {
        $this->authorizeInforme($informe);

        return view('proyectos.informe-final.inf-001', $generator->viewData($informe, false));
    }

    public function print(InformeFinalProyecto $informe, InformeFinalPdfGenerator $generator): Response
    {
        $this->authorizeInforme($informe);
        $pdf = $generator->make($informe);
        $nombreArchivo = $generator->filename($informe);
        $response = $pdf->stream($nombreArchivo, ['Attachment' => false]);

        return $this->aplicarHeadersPdf($response, 'inline', $nombreArchivo);
    }

    public function pdf(InformeFinalProyecto $informe, InformeFinalPdfGenerator $generator): Response
    {
        $this->authorizeInforme($informe);
        $pdf = $generator->make($informe);
        $nombreArchivo = $generator->filename($informe);
        $response = $pdf->download($nombreArchivo);

        return $this->aplicarHeadersPdf($response, 'attachment', $nombreArchivo);
    }

    private function aplicarHeadersPdf(Response $response, string $disposition, string $nombreArchivo): Response
    {
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', $disposition.'; filename="'.$nombreArchivo.'"');

        return $response;
    }

    private function authorizeInforme(InformeFinalProyecto $informe): void
    {
        abort_unless(
            app(InformeFinalProyectoWorkflowService::class)->usuarioPuedeVer($informe, auth()->user()),
            403
        );
    }

}
