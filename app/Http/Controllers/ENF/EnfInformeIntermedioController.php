<?php

namespace App\Http\Controllers\ENF;

use App\Http\Controllers\Controller;
use App\Models\ENF\EnfAccion;
use App\Models\ENF\EnfInformeIntermedio;
use App\Models\ENF\EnfRevision;
use App\Services\ENF\EnfWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EnfInformeIntermedioController extends Controller
{
    public function store(Request $request, EnfAccion $accion, EnfWorkflowService $workflow): RedirectResponse
    {
        $request->validate([
            'archivo_pdf' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        try {
            $workflow->guardarInformeIntermedio($accion, $request->file('archivo_pdf'), $request->user());
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['archivo_pdf' => $exception->getMessage()]);
        }

        return back()->with('status', 'Informe intermedio guardado como borrador.');
    }

    public function enviar(Request $request, EnfInformeIntermedio $informe, EnfWorkflowService $workflow): RedirectResponse
    {
        try {
            $workflow->enviarInformeIntermedio($informe, $request->user(), $request->input('destinatarios', []));
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['informe_intermedio' => $exception->getMessage()]);
        }

        return back()->with('status', 'Informe intermedio enviado a revision.');
    }

    public function ver(EnfInformeIntermedio $informe, EnfWorkflowService $workflow): StreamedResponse
    {
        return $this->servir($informe, $workflow, 'inline');
    }

    public function descargar(EnfInformeIntermedio $informe, EnfWorkflowService $workflow): StreamedResponse
    {
        return $this->servir($informe, $workflow, 'attachment');
    }

    private function servir(EnfInformeIntermedio $informe, EnfWorkflowService $workflow, string $disposition): StreamedResponse
    {
        $user = auth()->user();
        $puedeRevisar = $user && EnfRevision::query()
            ->where('enf_accion_id', $informe->enf_accion_id)
            ->where('proceso', EnfAccion::PROCESO_INFORME_INTERMEDIO)
            ->where('revision_ciclo', $informe->revision_ciclo)
            ->get()
            ->contains(fn (EnfRevision $revision): bool => $workflow->puedeRevisar($revision->load('accion'), $user));

        abort_unless($workflow->usuarioPuedeGestionar($informe->accion, $user) || $user?->hasRole('admin') || $puedeRevisar, 403);
        abort_unless($informe->archivo_pdf && Storage::disk('local')->exists($informe->archivo_pdf), 404);

        $filename = $informe->nombre_original ?: 'informe-intermedio.pdf';

        return Storage::disk('local')->response($informe->archivo_pdf, $filename, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="'.addslashes($filename).'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
