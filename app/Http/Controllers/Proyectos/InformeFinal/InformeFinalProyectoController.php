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
        return Pdf::loadView('proyectos.informe-final.inf-001-pdf', compact('informe'))
            ->setPaper('letter')
            ->download('INF-001-'.$informe->numero_registro.'.pdf');
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
