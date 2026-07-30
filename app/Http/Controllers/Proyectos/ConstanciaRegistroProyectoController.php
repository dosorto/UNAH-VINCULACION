<?php

namespace App\Http\Controllers\Proyectos;

use App\Http\Controllers\Controller;
use App\Models\Constancias\ConstanciaRegistroProyecto;
use App\Services\Constancias\ConstanciaRegistroAuthorization;
use App\Support\DownloadFilename;
use Illuminate\Support\Facades\Storage;

class ConstanciaRegistroProyectoController extends Controller
{
    public function descargar(ConstanciaRegistroProyecto $constancia, ConstanciaRegistroAuthorization $authorization)
    {
        $constancia->loadMissing(['proyecto']);
        abort_unless($authorization->puedeDescargar($constancia, auth()->user()), 403);
        abort_unless($constancia->puedeDescargarse(), 409, 'La constancia aún no está disponible para descargar.');
        abort_unless(Storage::disk('local')->exists($constancia->ruta_archivo), 404);

        if ($constancia->hash_archivo && hash_file('sha256', Storage::disk('local')->path($constancia->ruta_archivo)) !== $constancia->hash_archivo) {
            abort(409, 'La integridad del archivo no pudo verificarse.');
        }

        return Storage::disk('local')->download(
            $constancia->ruta_archivo,
            DownloadFilename::withExtension('Constancia-Registro-'.$constancia->numero, 'pdf'),
            ['Content-Type' => 'application/pdf']
        );
    }
}
