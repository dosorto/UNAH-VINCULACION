<?php

namespace App\Concerns;

use App\Models\Proyecto\DocumentoProyecto;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\Proyecto\Proyecto;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Único punto de verdad para determinar qué FirmaProyecto están pendientes
 * de acción para el rol activo del usuario autenticado, y para qué proyecto.
 *
 * Se usa tanto en la bandeja de tareas (ProyectosPorFirmar) como en los
 * contadores de dashboards, para que ambos coincidan siempre: si aquí se
 * corrige un criterio de asignación, se corrige en todos los lugares que
 * cuentan "pendientes" a la vez.
 */
trait ResolvesFirmasPendientes
{
    private function firmasDisponiblesQuery()
    {
        $user = Auth::user();
        $activeRoleName = $user?->activeRole?->name;
        $empleadoId = $user?->empleado?->id;
        $firmasPorEtapaIds = $this->firmasPorEtapaDisponiblesIds($user);

        return FirmaProyecto::query()
            ->select('firma_proyecto.*')
            ->join('cargo_firma', 'firma_proyecto.cargo_firma_id', '=', 'cargo_firma.id')
            ->where('firma_proyecto.estado_revision', 'Pendiente')
            ->whereNull('firma_proyecto.deleted_at')
            ->where(function ($query) use ($activeRoleName, $empleadoId, $firmasPorEtapaIds) {
                $query->where(function ($legacyQuery) use ($activeRoleName, $empleadoId) {
                    $legacyQuery->whereNull('firma_proyecto.flujo_aprobacion_etapa_id')
                        ->where(function ($authorizationQuery) use ($activeRoleName, $empleadoId) {
                            if (! $empleadoId) {
                                $authorizationQuery->whereRaw('1 = 0');
                                return;
                            }

                            $authorizationQuery->where('firma_proyecto.empleado_id', $empleadoId);

                            if ($activeRoleName) {
                                $authorizationQuery->whereHas('cargo_firma.tipoCargoFirma', fn ($roleQuery) => $roleQuery->where('nombre', $activeRoleName));
                            }
                        });
                })->orWhere(function ($workflowStageQuery) use ($firmasPorEtapaIds) {
                    $workflowStageQuery->whereNotNull('firma_proyecto.flujo_aprobacion_etapa_id')
                        ->whereIn('firma_proyecto.id', $firmasPorEtapaIds ?: [0]);
                });
            })
            ->where(function ($query) {
                $query->where(function ($projectQuery) {
                    $projectQuery
                        ->where('firma_proyecto.firmable_type', Proyecto::class)
                        ->whereExists(function ($estadoQuery) {
                            $estadoQuery
                                ->selectRaw('1')
                                ->from('estado_proyecto')
                                ->whereColumn('estado_proyecto.estadoable_id', 'firma_proyecto.firmable_id')
                                ->where('estado_proyecto.estadoable_type', Proyecto::class)
                                ->where('estado_proyecto.es_actual', true)
                                ->whereColumn('estado_proyecto.tipo_estado_id', 'cargo_firma.tipo_estado_id');
                        });
                })->orWhere(function ($documentQuery) {
                    $documentQuery
                        ->where('firma_proyecto.firmable_type', DocumentoProyecto::class)
                        ->whereExists(function ($estadoQuery) {
                            $estadoQuery
                                ->selectRaw('1')
                                ->from('estado_proyecto')
                                ->whereColumn('estado_proyecto.estadoable_id', 'firma_proyecto.firmable_id')
                                ->where('estado_proyecto.estadoable_type', DocumentoProyecto::class)
                                ->where('estado_proyecto.es_actual', true)
                                ->whereColumn('estado_proyecto.tipo_estado_id', 'cargo_firma.tipo_estado_id');
                        });
                })->orWhere(function ($otherQuery) {
                    $otherQuery
                        ->where('firma_proyecto.firmable_type', '!=', Proyecto::class)
                        ->where('firma_proyecto.firmable_type', '!=', DocumentoProyecto::class);
                });
            });
    }

    private function firmasPorEtapaDisponiblesIds(?User $user): array
    {
        $activeRole = $user?->activeRole;
        $empleadoId = $user?->empleado?->id;

        if (! $user || ! $activeRole || ! $empleadoId) {
            return [];
        }

        if (! $user->roles()
            ->where('roles.id', $activeRole->id)
            ->where('roles.name', $activeRole->name)
            ->exists()) {
            return [];
        }

        return FirmaProyecto::query()
            ->where('estado_revision', 'Pendiente')
            ->whereNull('deleted_at')
            ->whereNotNull('flujo_aprobacion_id')
            ->whereNotNull('flujo_aprobacion_etapa_id')
            ->whereNotNull('revision_ciclo')
            ->where('revision_ciclo', '>=', 1)
            ->whereNotNull('orden_revision')
            ->whereIn('firmable_type', [Proyecto::class, DocumentoProyecto::class])
            ->where('empleado_id', $empleadoId)
            ->where(function ($query) use ($user): void {
                $query
                    ->whereNull('responsable_usuario_id')
                    ->orWhere('responsable_usuario_id', $user->id);
            })
            ->where('rol_requerido', $activeRole->name)
            ->orderBy('created_at', 'desc')
            ->limit(250)
            ->get()
            ->filter(fn (FirmaProyecto $firma): bool => $this->canActOnWorkflowStageFirma($firma, $user))
            ->pluck('id')
            ->all();
    }

    protected function canActOnWorkflowStageFirma(FirmaProyecto $firma, ?User $user = null): bool
    {
        $user = $user ?: Auth::user();

        if (! $user || $firma->estado_revision !== 'Pendiente' || filled($firma->deleted_at)) {
            return false;
        }

        if (! $firma->usaFlujoPorEtapa()
            || ! $firma->flujo_aprobacion_id
            || ! $firma->flujo_aprobacion_etapa_id
            || (int) $firma->revision_ciclo < 1
            || blank($firma->orden_revision)
            || ! in_array($firma->firmable_type, [Proyecto::class, DocumentoProyecto::class], true)
        ) {
            return false;
        }

        $proyecto = $this->proyectoDeFirmaPorEtapa($firma);

        if (! $proyecto || ! $proyecto->firmaEsActualEnFlujoPorEtapa($firma)) {
            return false;
        }

        if (! $this->estadoActualCoincideConCargoDeFirma($firma)) {
            return false;
        }

        if (! $this->usuarioCoincideConEmpleadoDeFirma($user, $firma)) {
            return false;
        }

        if ($firma->responsable_usuario_id) {
            if ((int) $firma->responsable_usuario_id !== (int) $user->id) {
                return false;
            }

            return blank($firma->rol_requerido)
                || $this->usuarioCumpleRolRequeridoDeFirma($user, $firma);
        }

        return filled($firma->rol_requerido)
            && $this->usuarioCumpleRolRequeridoDeFirma($user, $firma);
    }

    protected function proyectoDeFirmaPorEtapa(FirmaProyecto $firma): ?Proyecto
    {
        if ($firma->firmable_type === Proyecto::class) {
            return Proyecto::query()->whereKey($firma->firmable_id)->first();
        }

        if ($firma->firmable_type === DocumentoProyecto::class) {
            return $this->documentoDeFirmaPorEtapa($firma)?->proyecto()->first();
        }

        return null;
    }

    protected function usuarioCoincideConEmpleadoDeFirma(User $user, FirmaProyecto $firma): bool
    {
        if (! $firma->empleado_id) {
            return false;
        }

        $empleado = $user->empleado()->first();

        return $empleado && (int) $empleado->id === (int) $firma->empleado_id;
    }

    protected function usuarioCumpleRolRequeridoDeFirma(User $user, FirmaProyecto $firma): bool
    {
        if (blank($firma->rol_requerido) || blank($user->active_role_id)) {
            return false;
        }

        $activeRole = $user->activeRole;

        if (! $activeRole || $activeRole->name !== $firma->rol_requerido) {
            return false;
        }

        return $user->roles()
            ->where('roles.id', $activeRole->id)
            ->where('roles.name', $firma->rol_requerido)
            ->exists();
    }

    protected function estadoActualCoincideConCargoDeFirma(FirmaProyecto $firma): bool
    {
        $cargoEstadoId = $firma->cargo_firma()->value('tipo_estado_id');

        if (! $cargoEstadoId) {
            return false;
        }

        if ($firma->firmable_type === Proyecto::class) {
            $estadoActualId = Proyecto::query()
                ->whereKey($firma->firmable_id)
                ->first()
                ?->estado
                ?->tipo_estado_id;

            return $estadoActualId && (int) $estadoActualId === (int) $cargoEstadoId;
        }

        if ($firma->firmable_type === DocumentoProyecto::class) {
            $estadoActualId = $this->documentoDeFirmaPorEtapa($firma)
                ?->estado
                ?->tipo_estado_id;

            return $estadoActualId && (int) $estadoActualId === (int) $cargoEstadoId;
        }

        return false;
    }

    protected function documentoDeFirmaPorEtapa(FirmaProyecto $firma): ?DocumentoProyecto
    {
        if ($firma->firmable_type !== DocumentoProyecto::class) {
            return null;
        }

        return DocumentoProyecto::query()->whereKey($firma->firmable_id)->first();
    }

    /**
     * IDs de proyectos (Proyecto::class) con al menos una firma pendiente
     * asignable al rol activo del usuario autenticado.
     */
    private function proyectoIdsConFirmaPendienteParaRolActivo(): \Illuminate\Support\Collection
    {
        return $this->firmasDisponiblesQuery()
            ->where('firma_proyecto.firmable_type', Proyecto::class)
            ->pluck('firma_proyecto.firmable_id')
            ->unique()
            ->values();
    }
}
