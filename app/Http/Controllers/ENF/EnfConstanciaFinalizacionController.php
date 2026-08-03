<?php

namespace App\Http\Controllers\ENF;

use App\Http\Controllers\Controller;
use App\Models\ENF\EnfConstanciaFinalizacion;
use App\Models\ENF\EnfInformeFinal;
use App\Support\DownloadFilename;
use Illuminate\Support\Facades\Storage;

class EnfConstanciaFinalizacionController extends Controller
{
    public function descargar(EnfConstanciaFinalizacion $constancia)
    {
        $constancia->loadMissing(['accion', 'informeFinal']);
        $user = request()->user();
        $autorizado = $user && (
            (int) $constancia->accion->creado_por_usuario_id === (int) $user->id
            || $user->can('docente.proyectos')
            || $user->hasRole('admin')
        );

        abort_unless($autorizado, 403);
        abort_unless($constancia->puedeDescargarse(), 409, 'La constancia aun no esta disponible para descargar.');
        abort_unless(
            strtoupper((string) $constancia->accion->estado_flujo) === 'FINALIZADO'
            && $constancia->informeFinal->estado === EnfInformeFinal::ESTADO_APROBADO,
            409,
            'La constancia no corresponde a un cierre vigente.'
        );
        abort_unless(Storage::disk('local')->exists($constancia->ruta_archivo), 404);

        if ($constancia->hash_archivo && hash_file('sha256', Storage::disk('local')->path($constancia->ruta_archivo)) !== $constancia->hash_archivo) {
            abort(409, 'La integridad del archivo no pudo verificarse.');
        }

        return Storage::disk('local')->download(
            $constancia->ruta_archivo,
            DownloadFilename::withExtension('Constancia-Finalizacion-ENF-'.$constancia->numero, 'pdf'),
            ['Content-Type' => 'application/pdf']
        );
    }
}
