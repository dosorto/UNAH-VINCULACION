<?php

namespace App\Http\Controllers\Proyectos\InformeIntermedio;

use App\Http\Controllers\Controller;
use App\Models\InformeIntermedio\InformeIntermedioProyecto;
use App\Services\InformeIntermedio\InformeIntermedioProyectoWorkflowService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InformeIntermedioProyectoController extends Controller
{
    public function ver(
        InformeIntermedioProyecto $informe,
        InformeIntermedioProyectoWorkflowService $workflow
    ): StreamedResponse {
        return $this->servir($informe, $workflow, 'inline');
    }

    public function descargar(
        InformeIntermedioProyecto $informe,
        InformeIntermedioProyectoWorkflowService $workflow
    ): StreamedResponse {
        return $this->servir($informe, $workflow, 'attachment');
    }

    private function servir(
        InformeIntermedioProyecto $informe,
        InformeIntermedioProyectoWorkflowService $workflow,
        string $disposition
    ): StreamedResponse {
        abort_unless($workflow->usuarioPuedeVer($informe, auth()->user()), 403);
        abort_unless(Storage::disk('local')->exists($informe->archivo_pdf), 404);

        return Storage::disk('local')->response(
            $informe->archivo_pdf,
            $informe->nombre_original,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => $disposition.'; filename="'.addslashes($informe->nombre_original).'"',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }
}
