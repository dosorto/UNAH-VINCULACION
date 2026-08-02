<?php

namespace App\Http\Controllers\Proyectos;

use App\Http\Controllers\Controller;
use App\Models\Constancias\ConstanciaFinalizacionProyecto;
use App\Services\Constancias\ConstanciaFinalizacionAuthorization;
use App\Support\DownloadFilename;
use Illuminate\Support\Facades\Storage;

class ConstanciaFinalizacionProyectoController extends Controller
{
    public function descargar(ConstanciaFinalizacionProyecto $constancia, ConstanciaFinalizacionAuthorization $authorization)
    {
        $constancia->loadMissing(['proyecto', 'informeFinal', 'documentoProyecto']);
        abort_unless($authorization->puedeDescargar($constancia, auth()->user()), 403);
        abort_unless($constancia->puedeDescargarse(), 409, 'La constancia aún no está disponible para descargar.');
        abort_unless(
            $constancia->proyecto->estado?->tipoestado?->nombre === 'Finalizado'
            && $constancia->documentoProyecto->estado?->tipoestado?->nombre === 'Aprobado'
            && $constancia->informeFinal->fecha_cierre,
            409,
            'La constancia no corresponde a un cierre vigente.'
        );
        abort_unless(Storage::disk('local')->exists($constancia->ruta_archivo), 404);

        if ($constancia->hash_archivo && hash_file('sha256', Storage::disk('local')->path($constancia->ruta_archivo)) !== $constancia->hash_archivo) {
            abort(409, 'La integridad del archivo no pudo verificarse.');
        }

        return Storage::disk('local')->download(
            $constancia->ruta_archivo,
            DownloadFilename::withExtension('Constancia-Finalizacion-'.$constancia->numero, 'pdf'),
            ['Content-Type' => 'application/pdf']
        );
    }
}
