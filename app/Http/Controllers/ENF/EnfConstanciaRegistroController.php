<?php

namespace App\Http\Controllers\ENF;

use App\Http\Controllers\Controller;
use App\Models\ENF\EnfConstanciaRegistro;
use App\Support\DownloadFilename;
use Illuminate\Support\Facades\Storage;

class EnfConstanciaRegistroController extends Controller
{
    public function descargar(EnfConstanciaRegistro $constancia)
    {
        $constancia->loadMissing('accion');
        $user = request()->user();
        $autorizado = $user && (
            (int) $constancia->accion->creado_por_usuario_id === (int) $user->id
            || $constancia->accion->usuarioEsParticipante($user)
            || $user->can('docente.proyectos')
            || $user->hasRole('admin')
        );

        abort_unless($autorizado, 403);
        abort_unless($constancia->puedeDescargarse(), 409, 'La constancia aun no esta disponible para descargar.');
        abort_unless(Storage::disk('local')->exists($constancia->ruta_archivo), 404);

        if ($constancia->hash_archivo && hash_file('sha256', Storage::disk('local')->path($constancia->ruta_archivo)) !== $constancia->hash_archivo) {
            abort(409, 'La integridad del archivo no pudo verificarse.');
        }

        return Storage::disk('local')->download(
            $constancia->ruta_archivo,
            DownloadFilename::withExtension('Constancia-Registro-ENF-'.$constancia->numero, 'pdf'),
            ['Content-Type' => 'application/pdf']
        );
    }
}
