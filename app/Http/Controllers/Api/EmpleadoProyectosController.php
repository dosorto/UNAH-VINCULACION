<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Personal\Empleado;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Throwable;

class EmpleadoProyectosController extends Controller
{
    // Lista cerrada: los estados desconocidos, rechazados, cancelados y borradores
    // no se publican como proyectos en ejecución.
    private const ESTADOS = [
        'finalizado' => 'finalizado',
        'aprobado' => 'aprobado',
        'en curso' => 'en curso',
        'coordinador proyecto' => 'en curso',
        'enlace vinculacion' => 'en curso',
        'jefe departamento' => 'en curso',
        'director centro' => 'en curso',
        'en revision' => 'en curso',
        'en revisión' => 'en curso',
        'en revision final' => 'en curso',
        'en revisión final' => 'en curso',
        'subsanacion' => 'en curso',
        'subsanación' => 'en curso',
        'inscrito' => 'en curso',
        'actualizacion realizada' => 'en curso',
        'actualización realizada' => 'en curso',
        'informe final habilitado' => 'en curso',
    ];

    public function __invoke(string $identificador): JsonResponse
    {
        if (trim($identificador) === '' || strlen($identificador) > 80 || trim($identificador) !== $identificador) {
            return response()->json(['message' => 'Identificador inválido.'], 422);
        }

        try {
            // Prioridad contractual: numero_empleado exacto > id > user_id.
            // Cada búsqueda termina antes de intentar el siguiente tipo.
            $empleado = Empleado::where('numero_empleado', $identificador)->orderBy('id')->first();
            $numero = ltrim($identificador, '0') ?: '0';
            $maximo = (string) PHP_INT_MAX;
            if (! $empleado && ctype_digit($identificador)
                && (strlen($numero) < strlen($maximo) || (strlen($numero) === strlen($maximo) && strcmp($numero, $maximo) <= 0))) {
                $empleado = Empleado::find((int) $numero)
                    ?? Empleado::where('user_id', (int) $numero)->orderBy('id')->first();
            }

            if (! $empleado) {
                return response()->json(['message' => 'Empleado no encontrado.'], 404);
            }

            $data = $empleado->proyectos()
                ->wherePivotNull('deleted_at')
                ->withPivot(['id', 'rol'])
                ->with(['estadoActual' => fn ($query) => $query->with('tipoestado')->orderByDesc('estado_proyecto.id')])
                ->select(['proyecto.id', 'proyecto.nombre_proyecto', 'proyecto.codigo_proyecto'])
                ->orderBy('proyecto.id')->orderByPivot('id', 'desc')
                ->get()
                // Una fila por proyecto: prevalece la participación activa de mayor id.
                ->unique('id')
                ->map(function ($proyecto): ?array {
                    // Leer la relación cargada explícitamente evita el accessor homónimo.
                    $estadoActual = $proyecto->getRelation('estadoActual');
                    $nombreEstado = Str::lower(trim((string) $estadoActual?->tipoestado?->nombre));
                    $estado = self::ESTADOS[$nombreEstado] ?? null;
                    if ($estado === null) {
                        return null;
                    }

                    return [
                        'nombre_proyecto' => $proyecto->nombre_proyecto,
                        'codigo_proyecto' => $proyecto->codigo_proyecto,
                        'rol' => $proyecto->pivot->rol,
                        'estado' => $estado,
                    ];
                })->filter()->values();

            return response()->json(['data' => $data]);
        } catch (Throwable $e) {
            try {
                report($e);
            } catch (Throwable) {
                // Un fallo del logger tampoco debe alterar el contrato de error.
            }

            return response()->json(['message' => 'No fue posible consultar los proyectos.'], 500);
        }
    }
}
