<?php

namespace App\Http\Controllers\Proyectos\InformeFinal;

use App\Http\Controllers\Controller;
use App\Models\InformeFinal\InformeFinalProyecto;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class InformeFinalProyectoController extends Controller
{
    public function preview(InformeFinalProyecto $informe): View
    {
        $this->authorizeInforme($informe);

        return view('proyectos.informe-final.inf-001', $this->viewData($informe, false));
    }

    public function print(InformeFinalProyecto $informe): Response
    {
        $pdf = $this->crearPdfInf001($informe);
        $nombreArchivo = $this->nombreArchivoInf001($informe);
        $response = $pdf->stream($nombreArchivo, ['Attachment' => false]);

        return $this->aplicarHeadersPdf($response, 'inline', $nombreArchivo);
    }

    public function pdf(InformeFinalProyecto $informe): Response
    {
        $pdf = $this->crearPdfInf001($informe);
        $nombreArchivo = $this->nombreArchivoInf001($informe);
        $response = $pdf->download($nombreArchivo);

        return $this->aplicarHeadersPdf($response, 'attachment', $nombreArchivo);
    }

    private function crearPdfInf001(InformeFinalProyecto $informe)
    {
        $this->authorizeInforme($informe);

        @set_time_limit(180);
        @ini_set('max_execution_time', '180');
        @ini_set('memory_limit', '512M');

        $data = $this->viewData($informe, true);
        $pdf = Pdf::loadView('pdf.informes-finales.inf-001', $data)
            ->setPaper([0, 0, 612, 792])
            ->setOption('isRemoteEnabled', false)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('defaultFont', 'Arial')
            ->setOption('dpi', 96)
            ->setOption('chroot', realpath(base_path()));
        $pdf->render();
        $dompdf = $pdf->getDomPDF();
        $font = $dompdf->getFontMetrics()->getFont('Arial', 'normal');
        $dompdf->getCanvas()->page_text(505, 760, '{PAGE_NUM}', $font, 7, [0, 0.125, 0.375]);

        return $pdf;
    }

    private function viewData(InformeFinalProyecto $informe, bool $isPdf): array
    {
        $informe = $this->load($informe);
        $firmas = [
            'coordinador' => null,
            'jefe' => null,
            'enlace' => null,
            'decano' => null,
        ];

        $cargos = [
            'coordinador proyecto' => 'coordinador',
            'jefe departamento' => 'jefe',
            'enlace vinculacion' => 'enlace',
            'director centro' => 'decano',
        ];

        foreach ($informe->proyecto->firma_proyecto as $firma) {
            $cargo = mb_strtolower((string) $firma->cargo_firma?->tipoCargoFirma?->nombre);
            $slot = $cargos[$cargo] ?? null;
            if (! $slot) {
                continue;
            }

            $firmas[$slot] = [
                'nombre' => $firma->empleado?->nombre_completo,
                'firma' => $this->resolverRutaFirma($firma->firma?->ruta_storage, $isPdf),
                'sello' => $this->resolverRutaFirma($firma->sello?->ruta_storage, $isPdf),
                'fecha' => $firma->fecha_firma,
            ];
        }

        $coordinadorProyecto = $informe->proyecto->coordinador_proyecto->first()?->empleado;

        return compact('informe', 'firmas', 'coordinadorProyecto');
    }

    private function nombreArchivoInf001(InformeFinalProyecto $informe): string
    {
        $identificador = $informe->numero_registro ?: 'Pendiente-de-asignacion';
        $identificador = preg_replace('/[\/\\\\:\*\?"<>\|]+/', '', (string) $identificador);
        $identificador = preg_replace('/\s+/', '-', trim((string) $identificador));
        $identificador = preg_replace('/\.pdf$/i', '', (string) $identificador);
        $identificador = trim((string) $identificador, '-.');

        return 'INF-001-'.($identificador ?: 'Pendiente-de-asignacion').'.pdf';
    }

    private function aplicarHeadersPdf(Response $response, string $disposition, string $nombreArchivo): Response
    {
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', $disposition.'; filename="'.$nombreArchivo.'"');

        return $response;
    }

    private function resolverRutaFirma(?string $ruta, bool $isPdf): ?string
    {
        if (blank($ruta)) {
            return null;
        }

        if (filter_var($ruta, FILTER_VALIDATE_URL)) {
            return $ruta;
        }

        $rutaNormalizada = ltrim((string) $ruta, '/');
        if (str_starts_with($rutaNormalizada, 'storage/')) {
            $rutaNormalizada = substr($rutaNormalizada, strlen('storage/'));
        }

        $rutaPublica = public_path('storage/'.$rutaNormalizada);
        $rutaDisco = storage_path('app/public/'.$rutaNormalizada);

        if (is_file($rutaDisco) || Storage::disk('public')->exists($rutaNormalizada)) {
            return $isPdf ? 'file://'.$rutaDisco : Storage::url($rutaNormalizada);
        }

        if (is_file($rutaPublica)) {
            return $isPdf ? 'file://'.$rutaPublica : Storage::url($rutaNormalizada);
        }

        return null;
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
        return $informe->loadMissing([
            'proyecto.objetivosEspecificos',
            'proyecto.coordinador_proyecto.empleado',
            'proyecto.firma_proyecto.empleado',
            'proyecto.firma_proyecto.firma',
            'proyecto.firma_proyecto.sello',
            'proyecto.firma_proyecto.cargo_firma.tipoCargoFirma',
            'beneficiarios',
            'equipoDocente',
            'cooperacion',
            'estudiantes',
            'voluntarios',
            'contrapartes',
            'resultados',
            'actividades.participantes',
            'accionesNoEjecutadas',
            'accionesEmergentes.resultado',
            'ods.ods',
            'ods.meta',
            'presupuestoDetalles',
            'anexos',
        ]);
    }
}
