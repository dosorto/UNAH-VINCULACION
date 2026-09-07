<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Personal\Empleado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
class EmpleadoProyectosController extends Controller
{
    public function __invoke(Request $request, string $identificador): JsonResponse
    {
        if (trim($identificador) === '' || strlen($identificador) > 80) return response()->json(['message' => 'Identificador inválido.'], 422);
        try {
            $empleado = Empleado::query()->where('numero_empleado', $identificador)
                ->when(ctype_digit($identificador), fn ($q) => $q->orWhere('id', (int) $identificador)->orWhere('user_id', (int) $identificador))->first();
            if (! $empleado) return response()->json(['message' => 'Empleado no encontrado.'], 404);
            $data = $empleado->proyectos()->withPivot('rol')->with(['tipoAccion', 'estadoActual.tipoestado'])->select('proyecto.*')->distinct()->get()->reject(function ($proyecto): bool {
                $estado = Str::lower((string) ($proyecto->estadoActual?->tipoestado?->nombre ?? ''));
                return Str::contains($estado, ['borrador', 'autoguardado']);
            })->map(function ($proyecto) use ($empleado): array {
                $pivot = $proyecto->pivot;
                $estado = Str::lower((string) ($proyecto->estadoActual?->tipoestado?->nombre ?? ''));
                $estado = Str::contains($estado, 'final') ? 'finalizado' : (Str::contains($estado, 'aprob') ? 'aprobado' : 'en curso');
                return ['nombre_proyecto' => $proyecto->nombre_proyecto, 'codigo_proyecto' => $proyecto->codigo_proyecto, 'rol' => $pivot->rol, 'estado' => $estado];
            })->values();
            return response()->json(['data' => $data]);
        } catch (Throwable $e) {
            Log::error('Error consultando proyectos por empleado', ['identificador' => $identificador, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'No fue posible consultar los proyectos.'], 500);
        }
    }
}
