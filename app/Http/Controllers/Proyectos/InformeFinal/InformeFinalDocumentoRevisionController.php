<?php

namespace App\Http\Controllers\Proyectos\InformeFinal;

use App\Http\Controllers\Controller;
use App\Models\InformeFinal\InformeFinalDocumentoRevision;
use Illuminate\Support\Facades\Storage;

class InformeFinalDocumentoRevisionController extends Controller
{
    public function descargar(InformeFinalDocumentoRevision $documento)
    {
        $informe = $documento->informe()->with('proyecto')->firstOrFail();
        $usuario = request()->user();
        $firma = $documento->firma;
        $participoComoRevisor = $usuario?->empleado && $informe->documentoCierre
            ? $informe->documentoCierre->firma_documento()
                ->where('empleado_id', $usuario->empleado->id)
                ->whereNotNull('flujo_aprobacion_etapa_id')
                ->exists()
            : false;
        $rolActivo = $usuario?->activeRole;
        $tienePermisoRevision = $rolActivo && (
            $rolActivo->hasPermissionTo('proyectos.revision-final')
            || $rolActivo->hasPermissionTo('proyectos.historial')
        );

        $autorizado = $usuario && (
            $informe->created_by === $usuario->id
            || $informe->proyecto->puedeMostrarCierreProyecto($usuario)
            || $firma?->responsable_usuario_id === $usuario->id
            || $firma?->empleado?->user_id === $usuario->id
            || $participoComoRevisor
            || $tienePermisoRevision
            || $usuario->hasAnyRole(['admin', 'Director/Enlace', 'Revisor Vinculacion'])
        );

        abort_unless($autorizado, 403);
        abort_unless(Storage::disk('local')->exists($documento->ruta), 404);

        return Storage::disk('local')->download($documento->ruta, $documento->nombre_original);
    }
}
