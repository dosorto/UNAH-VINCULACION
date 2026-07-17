<?php

namespace App\Http\Controllers\Proyectos\InformeFinal;

use App\Http\Controllers\Controller;
use App\Models\InformeFinal\InformeFinalProyecto;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

class InformeFinalProyectoController extends Controller
{
    public function preview(InformeFinalProyecto $informe): View
    {
        $this->authorizeInforme($informe);
        return view('proyectos.informe-final.inf-001', ['informe' => $this->load($informe), 'print' => false]);
    }

    public function print(InformeFinalProyecto $informe): View
    {
        $this->authorizeInforme($informe);
        return view('proyectos.informe-final.inf-001', ['informe' => $this->load($informe), 'print' => true]);
    }

    public function pdf(InformeFinalProyecto $informe): Response
    {
        $this->authorizeInforme($informe);
        $informe = $this->load($informe);
        $pdf = Pdf::loadView('pdf.informes-finales.inf-001', compact('informe'))
            ->setPaper('letter', 'landscape');
        $pdf->render();
        $dompdf = $pdf->getDomPDF();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $dompdf->getCanvas()->page_text(650, 585, 'Página {PAGE_NUM} de {PAGE_COUNT}', $font, 7, [0.29, 0.33, 0.39]);

        return $pdf->download('INF-001-'.$informe->numero_registro.'.pdf');
    }

    private function authorizeInforme(InformeFinalProyecto $informe): void
    {
        $user = auth()->user();
        $empleadoId = $user?->empleado?->id;
        $esCoordinador = $empleadoId && $informe->proyecto->coordinador_proyecto()->where('empleado_id', $empleadoId)->exists();
        abort_unless($user && ($user->hasRole('admin') || $esCoordinador), 403);
    }

    private function load(InformeFinalProyecto $informe): InformeFinalProyecto
    {
        return $informe->load(['proyecto.objetivosEspecificos', 'beneficiarios', 'equipoDocente', 'cooperacion', 'estudiantes', 'voluntarios', 'contrapartes', 'resultados', 'actividades', 'accionesNoEjecutadas', 'accionesEmergentes.resultado', 'ods.ods', 'ods.meta', 'presupuestoDetalles', 'anexos']);
    }
}
