<?php

namespace App\Clases;

use App\Models\ENF\EnfRevision;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\FichaActualizacion;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\Proyecto\ProyectoEstado;
use App\Models\Estado\TipoEstado;
use App\Models\Proyecto\DocumentoProyecto;
use App\Models\PpsServicioSocial;
use Illuminate\Database\Eloquent\Builder;

class DataNavBar
{
    public static function obtenerEnlaces()
    {
        return [
            ['nombre' => 'Inicio', 'url' => '/'],
            ['nombre' => 'Acerca de', 'url' => '/acerca'],
            ['nombre' => 'Contacto', 'url' => '/contacto']
        ];
    }


    // metodo para obtner toda la cantidad de proyectos
    public static function obtenerCantidadProyectos()
    {
        return Proyecto::count();
    }

    /// metodo para obtener la cantidad de proyectos en estado de "En revisión"
public static function obtenerCantidadProyectosEnRevision()
{
    $tipoEstado = TipoEstado::where('nombre', 'En revision')->first();
    
    if (!$tipoEstado) {
        return 0; // Return 0 if the estado doesn't exist
    }
    
    return Proyecto::query()
        ->whereIn('id', function ($query) use ($tipoEstado) {
            $query->select('estadoable_id')
                ->from('estado_proyecto')
                ->where('estadoable_type', Proyecto::class)
                ->where('tipo_estado_id', $tipoEstado->id)
                ->where('es_actual', true);
        })
        ->count();
}

// metodo para obtener la cantidad de proyectos en estado de "En revisión final"
public static function obtenerCantidadProyectosEnRevisionFinal()
{
    $tipoEstado = TipoEstado::where('nombre', 'En revision final')->first();
    
    if (!$tipoEstado) {
        return 0; // Return 0 if the estado doesn't exist
    }
    
    return Proyecto::query()
        ->whereIn('id', function ($query) use ($tipoEstado) {
            $query->select('estadoable_id')
                ->from('estado_proyecto')
                ->where('estadoable_type', Proyecto::class)
                ->where('tipo_estado_id', $tipoEstado->id)
                ->where('es_actual', true);
        })
        ->count();
}

// metodo para obtener todos los informes obtenerCantidadInformesSolicitados
public static function obtenerCantidadInformesSolicitados()
{
    $tipoEstado = TipoEstado::where('nombre', 'En revision')->first();
    
    if (!$tipoEstado) {
        return 0; // Return 0 if the estado doesn't exist
    }
    
    return DocumentoProyecto::query()
        ->whereIn('id', function ($query) use ($tipoEstado) {
            $query->select('estadoable_id')
                ->from('estado_proyecto')
                ->where('estadoable_type', DocumentoProyecto::class)
                ->where('tipo_estado_id', $tipoEstado->id)
                ->where('es_actual', true);
        })
        ->count();
}


    // metodo para obtener la cantidad de proyectos del usuario logueado
    public static function obtenerCantidadProyectosPorFirmar()
    {
        $user = auth()->user();

        if (! $user) {
            return 0;
        }

        $activeRoleName = $user->activeRole?->name;
        $empleadoId = $user->empleado?->id;

        $firmasPendientes = FirmaProyecto::query()
            ->join('cargo_firma', 'firma_proyecto.cargo_firma_id', '=', 'cargo_firma.id')
            ->where('firma_proyecto.estado_revision', 'Pendiente')
            ->where('firma_proyecto.firmable_type', '!=', FichaActualizacion::class)
            ->where(function ($query) use ($activeRoleName, $empleadoId) {
                if ($activeRoleName) {
                    $query->whereHas('cargo_firma.tipoCargoFirma', fn ($roleQuery) => $roleQuery->where('nombre', $activeRoleName));
                    return;
                }

                if ($empleadoId) {
                    $query->where('firma_proyecto.empleado_id', $empleadoId);
                    return;
                }

                $query->whereRaw('1 = 0');
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
                })->orWhere('firma_proyecto.firmable_type', '!=', Proyecto::class);
            })
            ->count();

        return $firmasPendientes
            + self::obtenerCantidadEnfPorRevisar()
            + self::obtenerCantidadPpsPorRevisar();
    }

    public static function obtenerCantidadEnfPorRevisar(): int
    {
        $user = auth()->user();
        $activeRoleName = $user?->activeRole?->name;

        if (! $user || ! $activeRoleName) {
            return 0;
        }

        $pendingStates = ['PENDIENTE', 'PENDIENTE_ASIGNACION', 'ASIGNADO', 'EN_PROCESO'];

        return EnfRevision::query()
            ->whereIn('estado', $pendingStates)
            ->whereHas('accion', fn (Builder $query) => $query
                ->where('estado_flujo', 'EN_REVISION')
                ->whereColumn('enf_revisiones.revision_ciclo', 'enf_acciones.revision_ciclo'))
            ->whereNotExists(function ($previousQuery) use ($pendingStates): void {
                $previousQuery
                    ->selectRaw('1')
                    ->from('enf_revisiones as enf_revisiones_anteriores')
                    ->whereColumn('enf_revisiones_anteriores.enf_accion_id', 'enf_revisiones.enf_accion_id')
                    ->whereColumn('enf_revisiones_anteriores.revision_ciclo', 'enf_revisiones.revision_ciclo')
                    ->whereColumn('enf_revisiones_anteriores.orden', '<', 'enf_revisiones.orden')
                    ->whereIn('enf_revisiones_anteriores.estado', $pendingStates);
            })
            ->where(function (Builder $responsableQuery) use ($user, $activeRoleName): void {
                $responsableQuery
                    ->where(function (Builder $assignedQuery) use ($user, $activeRoleName): void {
                        $assignedQuery
                            ->where('asignado_usuario_id', $user->id)
                            ->where(function (Builder $roleQuery) use ($activeRoleName): void {
                                $roleQuery
                                    ->whereNull('rol_requerido')
                                    ->orWhere('rol_requerido', $activeRoleName);
                            });
                    })
                    ->orWhere(function (Builder $roleQuery) use ($activeRoleName): void {
                        $roleQuery
                            ->whereNull('asignado_usuario_id')
                            ->where('rol_requerido', $activeRoleName);
                    })
                    ->orWhere(function (Builder $assignmentQuery) use ($user, $activeRoleName): void {
                        $assignmentQuery
                            ->where('responsable_usuario_id', $user->id)
                            ->where(function (Builder $roleQuery) use ($activeRoleName): void {
                                $roleQuery
                                    ->whereNull('rol_requerido')
                                    ->orWhere('rol_requerido', $activeRoleName);
                            });
                    });
            })
            ->count();
    }

    public static function obtenerCantidadPpsPorRevisar(): int
    {
        $user = auth()->user();

        if (! $user || empty($user->active_role_id)) {
            return 0;
        }

        $activeRole = $user->activeRole;

        if (! $activeRole) {
            return 0;
        }

        $activeRoleId = (int) $activeRole->id;
        $isActiveAdmin = $activeRole->name === 'admin';

        return PpsServicioSocial::query()
            ->whereNotIn('estado', [
                PpsServicioSocial::ESTADO_BORRADOR,
                PpsServicioSocial::ESTADO_APROBADO,
                PpsServicioSocial::ESTADO_RECHAZADO,
                'subsanacion',
            ])
            ->whereNotNull('flujo_aprobacion_id')
            ->whereNotNull('etapa_actual_id')
            ->whereHas('flujoAprobacion', fn (Builder $query) => $query
                ->where('proceso', PpsServicioSocial::PROCESO_FLUJO))
            ->whereHas('etapaActual', function (Builder $query) use ($user, $activeRoleId, $isActiveAdmin): void {
                $query
                    ->whereColumn('flujos_aprobacion_etapas.flujo_aprobacion_id', 'pps_servicio_social.flujo_aprobacion_id')
                    ->where('activo', true)
                    ->whereHas('flujo', fn (Builder $flujoQuery) => $flujoQuery
                        ->where('proceso', PpsServicioSocial::PROCESO_FLUJO));

                if ($isActiveAdmin) {
                    return;
                }

                $query->where(function (Builder $responsableQuery) use ($user, $activeRoleId): void {
                    $responsableQuery
                        ->where(function (Builder $asignacionQuery) use ($user, $activeRoleId): void {
                            $asignacionQuery
                                ->where('requiere_asignacion', true)
                                ->where('usuario_responsable_id', $user->id)
                                ->where(function (Builder $roleQuery) use ($activeRoleId): void {
                                    $roleQuery
                                        ->whereNull('rol_revisor_id')
                                        ->orWhere('rol_revisor_id', $activeRoleId);
                                });
                        })
                        ->orWhere(function (Builder $rolQuery) use ($activeRoleId): void {
                            $rolQuery
                                ->where('requiere_asignacion', false)
                                ->where('rol_revisor_id', $activeRoleId);
                        });
                });
            })
            ->count();
    }

    // metodo para obtener la cantidad de fichas de actualización por firmar del usuario logueado
    public static function obtenerCantidadFichasPorFirmar()
    {
        return auth()->user()->empleado->firmaProyectoPendientes()
            ->where('firmable_type', FichaActualizacion::class)
            ->count();
    }
}
