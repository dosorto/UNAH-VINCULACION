<?php

namespace App\Http\Controllers\ENF;

use App\Http\Controllers\Controller;
use App\Models\ENF\EnfInformeFinalDocumentoRevision;
use Illuminate\Support\Facades\Storage;

class EnfInformeFinalDocumentoRevisionController extends Controller
{
    public function descargar(EnfInformeFinalDocumentoRevision $documento)
    {
        $documento->loadMissing(['accion', 'revision']);
        $usuario = request()->user();
        $autorizado = $usuario && (
            (int) $documento->accion->creado_por_usuario_id === (int) $usuario->id
            || (int) $documento->revision->asignado_usuario_id === (int) $usuario->id
            || (int) $documento->revision->responsable_usuario_id === (int) $usuario->id
            || $usuario->can('docente.proyectos')
            || $usuario->hasRole('admin')
        );

        abort_unless($autorizado, 403);
        abort_unless(Storage::disk('local')->exists($documento->ruta), 404);

        return Storage::disk('local')->download($documento->ruta, $documento->nombre_original);
    }
}
