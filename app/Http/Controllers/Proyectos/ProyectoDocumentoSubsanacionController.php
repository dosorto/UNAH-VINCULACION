<?php

namespace App\Http\Controllers\Proyectos;

use App\Http\Controllers\Controller;
use App\Models\Proyecto\ProyectoDocumentoSubsanacion;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProyectoDocumentoSubsanacionController extends Controller
{
    public function descargar(ProyectoDocumentoSubsanacion $documento): StreamedResponse
    {
        $proyecto = $documento->proyecto()->firstOrFail();
        $usuario = request()->user();

        $esCoordinador = $usuario?->empleado && $proyecto->coordinador_proyecto()
            ->where('empleado_id', $usuario->empleado->id)
            ->exists();

        $participoComoRevisor = $usuario?->empleado && $proyecto->firma_proyecto()
            ->where('empleado_id', $usuario->empleado->id)
            ->whereNotNull('flujo_aprobacion_etapa_id')
            ->exists();

        $rolActivo = $usuario?->activeRole;
        $tienePermisoHistorial = $rolActivo && $rolActivo->hasPermissionTo('proyectos.historial');

        $autorizado = $usuario && (
            $esCoordinador
            || $participoComoRevisor
            || $tienePermisoHistorial
            || $usuario->hasAnyRole(['admin', 'Director/Enlace', 'Revisor Vinculacion'])
        );

        abort_unless($autorizado, 403);
        abort_unless(Storage::disk('local')->exists($documento->ruta), 404);

        return Storage::disk('local')->download($documento->ruta, $documento->nombre_original);
    }
}
