<?php

namespace App\Http\Controllers\ENF;

use App\Http\Controllers\Controller;
use App\Http\Requests\ENF\StoreEnfInformeFinalRequest;
use App\Models\ENF\EnfAccion;
use App\Models\ENF\EnfInformeFinal;
use App\Services\ENF\EnfWorkflowService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class EnfInformeFinalController extends Controller
{
    public function previewByAccion(int $accion): View
    {
        $accion = $this->loadAccion($accion);

        return view('enf.informes-finales.form-final', $this->viewData($accion, false));
    }

    public function printByAccion(int $accion): Response
    {
        $accion = $this->loadAccion($accion);
        $pdf = $this->crearPdf($accion);
        $filename = $this->nombreArchivo($accion);
        $response = $pdf->stream($filename, ['Attachment' => false]);

        return $this->aplicarHeadersPdf($response, 'inline', $filename);
    }

    public function pdfByAccion(int $accion): Response
    {
        $accion = $this->loadAccion($accion);
        $pdf = $this->crearPdf($accion);
        $filename = $this->nombreArchivo($accion);
        $response = $pdf->download($filename);

        return $this->aplicarHeadersPdf($response, 'attachment', $filename);
    }

    public function enviar(Request $request, EnfInformeFinal $informeFinal, EnfWorkflowService $workflow): RedirectResponse
    {
        try {
            $workflow->enviarInformeFinal($informeFinal, $request->user(), $request->input('destinatarios', []));
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['informe_final' => $exception->getMessage()]);
        }

        return redirect()
            ->route('enf.acciones.show', $informeFinal->accion)
            ->with('status', 'Informe final ENF enviado a revision.');
    }

    public function index(): JsonResponse
    {
        return response()->json(EnfInformeFinal::with(['accion', 'aprobadoPor'])->latest()->paginate());
    }

    public function create(): JsonResponse
    {
        return response()->json(['message' => 'Formulario de informe final ENF pendiente de interfaz.']);
    }

    public function store(StoreEnfInformeFinalRequest $request): JsonResponse
    {
        return response()->json(EnfInformeFinal::create($request->validated()), 201);
    }

    public function show(int $informeFinal): JsonResponse
    {
        return response()->json(
            EnfInformeFinal::with(['accion', 'participantesFinales', 'accionesEjecutadas', 'accionesNoEjecutadas'])
                ->findOrFail($informeFinal)
        );
    }

    public function update(Request $request, int $informeFinal): JsonResponse
    {
        $record = EnfInformeFinal::findOrFail($informeFinal);
        $record->update($request->validate((new StoreEnfInformeFinalRequest())->rules()));

        return response()->json($record->fresh());
    }

    public function edit(int $informeFinal): JsonResponse
    {
        return $this->show($informeFinal);
    }

    public function destroy(int $informeFinal): JsonResponse
    {
        EnfInformeFinal::findOrFail($informeFinal)->delete();

        return response()->json(status: 204);
    }

    private function crearPdf(EnfAccion $accion)
    {
        @set_time_limit(180);
        @ini_set('max_execution_time', '180');
        @ini_set('memory_limit', '512M');

        return Pdf::loadView('pdf.enf.informes-finales.form-final', $this->viewData($accion, true))
            ->setPaper('letter', 'portrait')
            ->setOption('isRemoteEnabled', false)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('defaultFont', 'Arial')
            ->setOption('dpi', 96)
            ->setOption('chroot', realpath(base_path()));
    }

    private function viewData(EnfAccion $accion, bool $isPdf): array
    {
        return [
            'accion' => $accion,
            'informe' => $accion->informeFinal,
            'isPdf' => $isPdf,
        ];
    }

    private function loadAccion(int $accion): EnfAccion
    {
        $accion = EnfAccion::with([
            'tipoAccion',
            'modalidad',
            'centroFacultad',
            'departamentoAcademico',
            'carrera',
            'lugaresEjecucion.campus',
            'lugaresEjecucion.departamento',
            'lugaresEjecucion.municipio',
            'beneficiarios',
            'equipo',
            'participacionUniversitaria',
            'contrapartes.tipoContraparte',
            'contrapartes.instrumentoAlianza',
            'objetivosEspecificos',
            'resultados',
            'presupuestos.detalles',
            'cronograma',
            'certificado.tipoCertificado',
            'certificado.figuraAcreditacion',
            'certificado.carreras.carrera',
            'certificado.carreras.centroFacultad',
            'espaciosAprendizaje',
            'documentos',
            'firmas',
            'accionCatalogos.catalogo',
            'ods',
            'metasContribuye',
            'ejesUnah',
            'informeFinal.aprobadoPor',
            'informeFinal.participantesFinales',
            'informeFinal.accionesEjecutadas',
            'informeFinal.accionesNoEjecutadas',
        ])->findOrFail($accion);

        abort_unless(in_array($accion->codigo_formulario, ['FORM-DVUS-016', 'FORM-DVUS-018'], true), 404);

        return $accion;
    }

    private function nombreArchivo(EnfAccion $accion): string
    {
        $identificador = $accion->numero_registro ?: $accion->certificado?->codigo_certificado ?: $accion->id;
        $identificador = preg_replace('/[\/\\\\:\*\?"<>\|]+/', '', (string) $identificador);
        $identificador = preg_replace('/\s+/', '-', trim((string) $identificador));
        $identificador = trim((string) $identificador, '-.');

        return 'Informe-Final-ENF-'.($identificador ?: $accion->id).'.pdf';
    }

    private function aplicarHeadersPdf(Response $response, string $disposition, string $filename): Response
    {
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', $disposition.'; filename="'.$filename.'"');

        return $response;
    }
}
