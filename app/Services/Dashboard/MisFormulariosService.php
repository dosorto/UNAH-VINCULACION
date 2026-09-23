<?php

namespace App\Services\Dashboard;

use App\Models\ENF\EnfAccion;
use App\Models\ENF\EnfRevision;
use App\Models\Pasantia;
use App\Models\PpsServicioSocial;
use App\Models\Proyecto\Proyecto;
use App\Support\Dashboard\EstadoGeneral;
use App\Support\Proyecto\ProyectoFlujoStepper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * "Mis proyectos": unifica en una sola lista los formularios que puede tener
 * un usuario —Proyecto (Desarrollo Local/Voluntariado), PPS/Servicio Social,
 * pasantía y ENF— cada uno con su stepper de progreso calculado según el
 * flujo de aprobación que le corresponde.
 *
 * Los estados de cada formulario se traducen con EstadoGeneral, el mismo
 * vocabulario del panel institucional, para que las tarjetas de uno y otro
 * cuenten igual.
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

    /** @var array<string, Collection<int, array>> */
    private array $memo = [];

    /** Tarjeta del resumen en la que cuenta cada estado general. */
    private const TARJETA = [
        EstadoGeneral::BORRADOR => 'borrador',
        EstadoGeneral::EN_REVISION => 'en_revision',
        EstadoGeneral::SUBSANACION => 'subsanar',
        EstadoGeneral::APROBADO => 'en_curso',
        EstadoGeneral::FINALIZADO => 'finalizado',
    ];

    /**
     * @return Collection<int, array{kind:string,codigo:?string,nombre:string,categoria:?string,fecha_inicio:mixed,fecha_fin:mixed,fase:string,stepper:array,href:?string,sort_date:mixed}>
     */
    public function para(?int $empleadoId, ?int $userId, int $limite = 15): Collection
    {
        // El panel pide la lista, el resumen y las subsanaciones: se arma una
        // vez por petición.
        $this->memo[$empleadoId.':'.$userId] ??= $this->todas($empleadoId, $userId);

        return $this->memo[$empleadoId.':'.$userId]->take($limite)->values();
    }

    /**
     * Trámites devueltos para corregir, de toda la lista y no solo de los
     * visibles: una devolución antigua quedaba fuera de «Requiere tu atención».
     *
     * @return Collection<int, array>
     */
    public function porSubsanar(?int $empleadoId, ?int $userId): Collection
    {
        return $this->para($empleadoId, $userId, PHP_INT_MAX)
            ->filter(fn (array $fila): bool => $fila['clave_estado'] === 'subsanar')
            ->values();
    }

    private function todas(?int $empleadoId, ?int $userId): Collection
    {
        $filas = collect();

        if ($empleadoId) {
            $this->agregarProyectos($filas, $empleadoId);
        }

        if ($userId) {
            $this->agregarPps($filas, $userId);
            $this->agregarPasantias($filas, $userId);
            $this->agregarEnf($filas, $userId, $empleadoId);
        }

        return $filas->sortByDesc('sort_date')->values();
    }

    /**
     * Conteo unificado por estado de todos los formularios del usuario.
     *
     * @return array{total:int,borrador:int,en_revision:int,en_curso:int,finalizado:int,subsanar:int}
     */
    public function resumen(?int $empleadoId, ?int $userId): array
    {
        $resumen = ['total' => 0, 'borrador' => 0, 'en_revision' => 0,
            'en_curso' => 0, 'finalizado' => 0, 'subsanar' => 0];

        foreach ($this->para($empleadoId, $userId, PHP_INT_MAX) as $fila) {
            $resumen['total']++;

            if ($fila['clave_estado'] !== null) {
                $resumen[$fila['clave_estado']]++;
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
                    'estado' => $proyecto->estado_general,
                    'clave_estado' => $this->tarjeta($proyecto->estado_general),
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
                    'estado' => $this->etiqueta($registro->estado),
                    'clave_estado' => $this->tarjeta($registro->estado),
                    'stepper' => ProyectoFlujoStepper::desdeFilas($registro->stepperDeAprobacion()),
                    'href' => route('pps-servicio-social.show', $registro->id),
                    'sort_date' => $registro->created_at,
                ]);
            });
    }

    private function agregarPasantias(Collection $filas, int $userId): void
    {
        Pasantia::query()
            ->where('created_by', $userId)
            ->get()
            ->each(function (Pasantia $registro) use ($filas): void {
                $filas->push([
                    'kind' => 'pasantia',
                    'codigo' => $registro->codigo_registro,
                    'nombre' => $registro->nombre_estudiante ?: ($registro->nombre_institucion ?: 'Registro de pasantía'),
                    'categoria' => 'Pasantía universitaria',
                    'fecha_inicio' => $registro->fecha_inicio ?: $registro->created_at,
                    'fecha_fin' => $registro->fecha_finalizacion,
                    'fase' => 'Aprobación',
                    'estado' => $this->etiqueta($registro->estado),
                    'clave_estado' => $this->tarjeta($registro->estado),
                    'stepper' => ProyectoFlujoStepper::desdeFilas($registro->stepperDeAprobacion()),
                    'href' => route('pasantias.show', $registro->id),
                    'sort_date' => $registro->created_at,
                ]);
            });
    }

    private function agregarEnf(Collection $filas, int $userId, ?int $empleadoId): void
    {
        EnfAccion::query()
            ->perteneceA($userId, $empleadoId)
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
                    'clave_estado' => $this->claveDeEstadoEnf($accion->estado_flujo),
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
            'APROBADO' => 'Aprobado',
            'FINALIZADO' => 'Finalizado',
            'SUBSANACION', 'SUBSANACIÓN' => 'Subsanacion',
            default => $estado ?: 'Borrador',
        };
    }

    /** Tarjeta del resumen en la que cuenta una acción ENF según su estado de flujo. */
    private function claveDeEstadoEnf(?string $estado): ?string
    {
        return match (strtoupper((string) $estado)) {
            '', 'BORRADOR' => 'borrador',
            'EN_REVISION' => 'en_revision',
            'APROBADO' => 'en_curso',
            'FINALIZADO' => 'finalizado',
            'SUBSANACION', 'SUBSANACIÓN' => 'subsanar',
            default => null,
        };
    }

    /** Tarjeta del resumen en la que cuenta un estado; null si no encaja en ninguna. */
    private function tarjeta(?string $estado): ?string
    {
        return self::TARJETA[EstadoGeneral::clasificar($estado)] ?? null;
    }

    /** «en_revision» → «En revision», para el chip de estado. */
    private function etiqueta(?string $estado): string
    {
        return ucfirst(str_replace('_', ' ', (string) ($estado ?: 'borrador')));
    }
}
