<?php

namespace App\Clases;

use App\Concerns\ResolvesFirmasPendientes;
use App\Models\DAFT\ProgramaRevision;
use App\Models\ENF\EnfRevision;
use App\Models\PpsServicioSocial;
use App\Models\Proyecto\DocumentoProyecto;
use App\Models\Proyecto\FichaActualizacion;
use App\Models\Proyecto\Proyecto;
use App\Services\DAFT\ProgramaWorkflowService;
use App\Support\Dashboard\EstadosProyecto;
use Illuminate\Database\Eloquent\Builder;

class DataNavBar
{
    use ResolvesFirmasPendientes;

    public static function obtenerEnlaces()
    {
        return [
            ['nombre' => 'Inicio', 'url' => '/'],
            ['nombre' => 'Acerca de', 'url' => '/acerca'],
            ['nombre' => 'Contacto', 'url' => '/contacto'],
        ];
    }

    // metodo para obtner toda la cantidad de proyectos
    public static function obtenerCantidadProyectos()
    {
        return Proyecto::count();
    }

    // / metodo para obtener la cantidad de proyectos en estado de "En revisión"
    public static function obtenerCantidadProyectosEnRevision()
    {
        return self::contarPorEstadoActual(Proyecto::class, 'En revision');
    }

    // metodo para obtener la cantidad de proyectos en estado de "En revisión final"
    public static function obtenerCantidadProyectosEnRevisionFinal()
    {
        return self::contarPorEstadoActual(Proyecto::class, 'En revision final');
    }

    // metodo para obtener todos los informes obtenerCantidadInformesSolicitados
    public static function obtenerCantidadInformesSolicitados()
    {
        return self::contarPorEstadoActual(DocumentoProyecto::class, 'En revision');
    }

    /**
     * Cuenta registros cuyo estado actual es alguno de los nombres dados.
     *
     * Usa EstadosProyecto::ids() en vez de TipoEstado::where(...)->first(): el
     * catálogo está sembrado por duplicado en la base de datos y quedarse con el
     * primer id dejaba fuera la mitad de los registros, haciendo que la insignia
     * del sidebar y los conteos del panel no coincidieran.
     *
     * @param  class-string  $estadoableType
     * @param  string|list<string>  $nombres
     */
    private static function contarPorEstadoActual(string $estadoableType, string|array $nombres): int
    {
        $tipoEstadoIds = EstadosProyecto::ids($nombres);

        if ($tipoEstadoIds === []) {
            return 0;
        }

        return $estadoableType::query()
            ->whereIn('id', function ($query) use ($estadoableType, $tipoEstadoIds) {
                $query->select('estadoable_id')
                    ->from('estado_proyecto')
                    ->where('estadoable_type', $estadoableType)
                    ->whereIn('tipo_estado_id', $tipoEstadoIds)
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

        // La insignia debe usar exactamente la misma autorización que la
        // bandeja. Contar solo por el nombre del rol incluía firmas legacy
        // asignadas a otros empleados y producía el caso "1" con tabla vacía.
        $firmasPendientes = (new self)
            ->firmasDisponiblesQuery()
            ->where('firma_proyecto.firmable_type', '!=', FichaActualizacion::class)
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
        return PpsServicioSocial::pendientesParaUsuario(auth()->user())->count();
    }

    public static function obtenerCantidadRevisionesDaft(): int
    {
        $user = auth()->user();

        if (! $user || ! $user->activeRole) {
            return 0;
        }

        $workflow = app(ProgramaWorkflowService::class);

        return ProgramaRevision::query()
            ->with(['programa', 'flujoEtapa.rolRevisor'])
            ->whereIn('estado', ['PENDIENTE', 'PENDIENTE_ASIGNACION', 'ASIGNADO', 'EN_PROCESO'])
            ->get()
            ->filter(fn (ProgramaRevision $revision): bool => $workflow->usuarioPuedeVer($revision, $user))
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
