<?php

namespace App\Http\Controllers\Proyectos\Vinculacion;

use App\Http\Controllers\Controller;
use App\Models\PpsServicioSocial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PpsServicioSocialAnexoController extends Controller
{
    public function __invoke(Request $request, int $id, string $tipo)
    {
        $registro = PpsServicioSocial::findOrFail($id);

        abort_unless($this->canViewRecord($registro), 403);

        $path = match ($tipo) {
            'carta-formalizacion' => $registro->archivo_carta_formalizacion,
            'convenio-marco' => $registro->archivo_convenio_marco,
            default => null,
        };

        abort_unless(filled($path), 404);

        $path = $this->normalizePublicPath($path);

        abort_unless(Storage::disk('public')->exists($path), 404);

        $filename = basename($path);

        if ($request->boolean('download')) {
            return Storage::disk('public')->download($path, $filename);
        }

        return Storage::disk('public')->response($path, $filename);
    }

    private function canViewRecord(PpsServicioSocial $registro): bool
    {
        $user = auth()->user();
        $activeRole = $user?->activeRole;

        if (
            $activeRole?->hasPermissionTo('proyectos.historial')
            || $activeRole?->hasPermissionTo('proyectos.revision-final')
            || in_array($activeRole?->name, ['admin', 'Director/Enlace'], true)
        ) {
            return true;
        }

        return $registro->perteneceAlUsuario(auth()->id())
            || $registro->usuarioPuedeRevisar($user);
    }

    private function normalizePublicPath(string $path): string
    {
        $path = ltrim($path, '/');
        $path = preg_replace('#^storage/#', '', $path);
        $path = preg_replace('#^public/#', '', $path);
        $path = preg_replace('#^app/public/#', '', $path);

        return $path;
    }
}
