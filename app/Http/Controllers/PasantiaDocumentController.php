<?php

namespace App\Http\Controllers;

use App\Models\Pasantia;
use App\Services\Pasantias\FormDvus013DocumentService;
use Illuminate\Support\Facades\Storage;

class PasantiaDocumentController extends Controller
{
    public function pdf(int $id, FormDvus013DocumentService $service)
    {
        $registro = $this->authorizedRecord($id);
        $tipo = request()->query('tipo', 'formulario');
        abort_unless(in_array($tipo, ['formulario', 'solicitud_practica', 'autorizacion_pps'], true), 404);
        if ($tipo !== 'formulario') {
            $doc = $registro->documentosGenerados()->where('tipo', $tipo)->latest('version')->firstOrFail();
            return response()->download(storage_path('app/'.$doc->archivo), $doc->nombre_original);
        }
        try {
            $path = $service->generatePdf($registro);
        } catch (\Throwable $e) {
            report($e);
            return response()->view('pdf.documento-no-disponible', [], 503);
        }
        $name = 'FORM-DVUS-013-'.$registro->id.'.pdf';
        return request()->boolean('inline')
            ? response()->file($path, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="'.$name.'"', 'Cache-Control' => 'private, no-cache'])
            : response()->download($path, $name, ['Content-Type' => 'application/pdf']);
    }

    public function attachment(int $id, string $tipo, FormDvus013DocumentService $service)
    {
        $registro = $this->authorizedRecord($id);
        $field = ['carta' => 'archivo_carta_formalizacion', 'convenio' => 'archivo_convenio_marco'][$tipo] ?? null;
        abort_unless($field, 404);
        $root = realpath(Storage::disk('public')->path(''));
        $path = filled($registro->{$field}) ? realpath(Storage::disk('public')->path($registro->{$field})) : false;
        abort_unless($root && $path && str_starts_with($path, $root.DIRECTORY_SEPARATOR) && is_file($path), 404);
        if (request()->boolean('download')) return response()->download($path);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($extension, ['doc', 'docx'], true)) {
            try { $path = $service->attachmentPdf($path); }
            catch (\Throwable $e) { report($e); return response()->view('pdf.documento-no-disponible', [], 503); }
        } else {
            abort_unless(in_array(mime_content_type($path), ['application/pdf', 'image/png', 'image/jpeg'], true), 415);
        }
        return response()->file($path, ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-cache']);
    }

    private function authorizedRecord(int $id): Pasantia
    {
        $registro = Pasantia::findOrFail($id);
        abort_unless($registro->perteneceAlUsuario(auth()->id()) || auth()->user()?->can('proyectos.historial')
            || auth()->user()?->can('docente.proyectos') || $registro->usuarioPuedeRevisar(auth()->user()), 403);
        return $registro;
    }
}
