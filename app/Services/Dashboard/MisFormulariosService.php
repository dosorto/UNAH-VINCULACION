<?php

namespace App\Services\Dashboard;

use App\Models\ENF\EnfAccion;
use App\Models\ENF\EnfRevision;
use App\Models\PpsServicioSocial;
use App\Models\Proyecto\Proyecto;
use App\Support\Proyecto\ProyectoFlujoStepper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * "Mis proyectos": unifica en una sola lista los tres tipos de formulario que
 * puede tener un usuario —Proyecto (Desarrollo Local/Voluntariado), PPS/
 * Servicio Social y ENF— cada uno con su stepper de progreso calculado según
 * el flujo de aprobación que le corresponde.
 *
 * Extraído de DasboardDocente para que el panel del director pueda usarlo
 * igual. Hasta ahora DashboardDirector solo miraba empleado_proyecto, así que
 * un Coordinador Proyecto no veía sus PPS ni sus ENF en ninguna parte.
 *
 * IMPORTANTE: las filas conservan las claves 'nombre' y 'stepper' porque las
 * vistas las consumen así y DashboardMisProyectosLayoutTest lo verifica
 * literalmente.
 */
class MisFormulariosService
{
    /** Códigos de formulario que pertenecen al módulo de Educación No Formal. */
    private const FORMULARIOS_ENF = ['FORM-DVUS-016', 'FORM-DVUS-018'];

    /**
     * @return Collection<int, array{kind:string,codigo:?string,nombre:string,categoria:?string,fecha_inicio:mixed,fecha_fin:mixed,fase:string,stepper:array,href:?string,sort_date:mixed}>
     */
    public function para(?int $empleadoId, ?int $userId, int $limite = 15): Collection
    {
        $filas = collect();

        if ($empleadoId) {
            $this->agregarProyectos($filas, $empleadoId);
        }

        if ($userId) {
            $this->agregarPps($filas, $userId);
            $this->agregarEnf($filas, $userId);
        }

        return $filas->sortByDesc('sort_date')->take($limite)->values();
    }

    /**
     * Conteo unificado por estado de los tres tipos de formulario.
     *
     * @return array{total:int,borrador:int,en_revision:int,en_curso:int,finalizado:int,subsanar:int}
     */
    public function resumen(?int $empleadoId, ?int $userId): array
    {
        $resumen = ['total' => 0, 'borrador' => 0, 'en_revision' => 0,
            'en_curso' => 0, 'finalizado' => 0, 'subsanar' => 0];

        foreach ($this->para($empleadoId, $userId, PHP_INT_MAX) as $fila) {
            $resumen['total']++;
            $clave = $this->claveDeEstado($fila['estado'] ?? null);

            if ($clave !== null) {
                $resumen[$clave]++;
            }
        }

        return $resumen;
    }

    private function agregarProyectos(Collection $filas, int $empleadoId): void
    {
        Proyecto::query()
            ->whereExists(
                fn ($sub) => $sub->selectRaw('1')
                    ->from('empleado_proyecto')
                    ->whereColumn('empleado_proyecto.proyecto_id', 'proyecto.id')
                    ->where('empleado_proyecto.empleado_id', $empleadoId)
                    ->whereNull('empleado_proyecto.deleted_at')
            )
            ->with(['categoria', 'estadoActual.tipoestado'])
            ->get()
            ->each(function (Proyecto $proyecto) use ($filas): void {
                [$proceso, $documento] = $proyecto->procesoActivoParaStepper();

                $filas->push([
                    'kind' => 'proyecto',
                    'codigo' => $proyecto->codigo_proyecto,
                    'nombre' => $proyecto->nombre_proyecto,
                    'categoria' => $proyecto->categoria->pluck('nombre')->implode(', ') ?: null,
                    'fecha_inicio' => $proyecto->fecha_inicio,
                    'fecha_fin' => $proyecto->fecha_finalizacion,
                    'fase' => $this->etiquetaDeFase($proceso),
                    'estado' => $proyecto->estadoActual?->tipoestado?->nombre,
                    'stepper' => ProyectoFlujoStepper::desdeFilas(
                        $proyecto->etapasParaStepper($proceso, $documento)
                    ),
                    'href' => route('historialproyecto', $proyecto->id),
                    'sort_date' => $proyecto->created_at,
                ]);
            });
    }

    private function agregarPps(Collection $filas, int $userId): void
    {
        PpsServicioSocial::query()
            ->where('created_by', $userId)
            ->get()
            ->each(function (PpsServicioSocial $registro) use ($filas): void {
                $filas->push([
                    'kind' => 'pps',
                    'codigo' => $registro->codigo_registro,
                    'nombre' => $registro->nombre_estudiante ?: $registro->nombre_institucion,
                    'categoria' => 'PPS / Servicio Social',
                    'fecha_inicio' => $registro->fecha_inicio ?: $registro->created_at,
                    'fecha_fin' => $registro->fecha_finalizacion,
                    'fase' => 'Aprobación',
                    'estado' => $registro->estado,
                    'stepper' => ProyectoFlujoStepper::desdeFilas($registro->stepperDeAprobacion()),
                    'href' => null,
                    'sort_date' => $registro->created_at,
                ]);
            });
    }

    private function agregarEnf(Collection $filas, int $userId): void
    {
        EnfAccion::query()
            ->where('creado_por_usuario_id', $userId)
            ->where(fn (Builder $q): Builder => $this->soloFormulariosEnf($q))
            ->get()
            ->each(function (EnfAccion $accion) use ($filas): void {
                $filas->push([
                    'kind' => 'enf',
                    'codigo' => $accion->codigo_formulario,
                    'nombre' => $accion->nombre_accion ?: 'Educación no formal',
                    'categoria' => 'Educación no formal',
                    'fecha_inicio' => $accion->fecha_solicitud ?: $accion->created_at,
                    'fecha_fin' => $accion->fecha_finalizacion,
                    'fase' => 'Aprobación',
                    'estado' => $this->etiquetaEstadoEnf($accion->estado_flujo),
                    'stepper' => $this->stepperEnf($accion),
                    'href' => null,
                    'sort_date' => $accion->created_at,
                ]);
            });
    }

    /**
     * Fase que representa el stepper de un Proyecto. Solo aplica a Proyecto:
     * PPS y ENF tienen un único flujo, siempre "Aprobación".
     */
    private function etiquetaDeFase(string $proceso): string
    {
        return match ($proceso) {
            Proyecto::FLUJO_INFORME_INTERMEDIO => 'Informe Intermedio',
            Proyecto::FLUJO_CIERRE_PROYECTO => 'Informe Final',
            default => 'Aprobación',
        };
    }

    /**
     * ENF no usa estado_proyecto ni firma_proyecto: lleva su propia tabla de
     * revisiones, así que el stepper se arma aparte.
     */
    private function stepperEnf(EnfAccion $accion): array
    {
        $proceso = EnfRevision::where('enf_accion_id', $accion->id)
            ->orderByDesc('id')
            ->value('proceso');

        if (! $proceso) {
            return [];
        }

        $actualMarcado = false;

        return EnfRevision::where('enf_accion_id', $accion->id)
            ->where('proceso', $proceso)
            ->orderByDesc('revision_ciclo')
            ->orderByDesc('id')
            ->get()
            ->unique('flujo_aprobacion_etapa_id')
            ->sortBy('orden')
            ->values()
            ->map(function (EnfRevision $revision) use (&$actualMarcado): array {
                $estado = 'pendiente';

                if ($revision->estado === 'APROBADO') {
                    $estado = 'aprobado';
                } elseif ($revision->estado === 'SUBSANACION') {
                    $estado = 'rechazado';
                } elseif (! $actualMarcado) {
                    $estado = 'actual';
                    $actualMarcado = true;
                }

                return ['nombre' => $revision->etapa_nombre, 'estado' => $estado];
            })
            ->all();
    }

    private function soloFormulariosEnf(Builder $query): Builder
    {
        return $query->whereIn('codigo_formulario', self::FORMULARIOS_ENF);
    }

    private function etiquetaEstadoEnf(?string $estado): string
    {
        return match (strtoupper((string) $estado)) {
            'BORRADOR' => 'Borrador',
            'EN_REVISION' => 'En revision',
            'APROBADO' => 'En curso',
            'FINALIZADO' => 'Finalizado',
            'SUBSANACION', 'SUBSANACIÓN' => 'Subsanacion',
            default => $estado ?: 'Borrador',
        };
    }

    /**
     * Normaliza los nombres de estado de los tres módulos a las categorías del
     * panel. Devuelve null si el estado no encaja en ninguna tarjeta.
     */
    private function claveDeEstado(?string $estado): ?string
    {
        $normalizado = mb_strtolower(trim((string) $estado));

        return match (true) {
            $normalizado === '' => 'borrador',
            in_array($normalizado, ['borrador', 'autoguardado'], true) => 'borrador',
            in_array($normalizado, ['subsanacion', 'subsanación', 'rechazado'], true) => 'subsanar',
            $normalizado === 'en curso' => 'en_curso',
            $normalizado === 'finalizado' => 'finalizado',
            in_array($normalizado, [
                'esperando documento', 'subsanar documento', 'enlace vinculacion',
                'coordinador proyecto', 'jefe departamento', 'director centro',
                'en revision', 'en revision final', 'enviado',
            ], true) => 'en_revision',
            default => null,
        };
    }
}
