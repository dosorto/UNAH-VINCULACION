<?php

namespace App\Services\Dashboard;

use App\Concerns\ResolvesFirmasPendientes;
use App\Models\ENF\EnfRevision;
use App\Models\PpsServicioSocial;
use App\Models\Proyecto\Proyecto;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Bandeja de revisión del rol activo: Proyecto + PPS/SS + ENF en una sola lista
 * ordenada por antigüedad.
 *
 * Unifica lo que estaba repartido entre DashboardDirector (proyectos y PPS) y
 * DasboardDocente (ENF), de modo que cada panel mostraba una parte distinta de
 * lo mismo.
 *
 * El orden es del más antiguo al más reciente, al revés que antes: a quien
 * revisa le importa lo que lleva más tiempo esperando, no lo último que entró.
 *
 * Nada de esto se cachea: quien aprueba algo debe ver bajar el contador en el
 * siguiente render.
 */
class PendientesRevisionService
{
    use ResolvesFirmasPendientes;

    /**
     * @return Collection<int, object{tipo:string,codigo:?string,nombre:string,etapa:?string,dias_espera:int,fecha_inicio:mixed,href:?string,sort_date:mixed}>
     */
    public function paraRolActivo(?User $user, int $limite = 50): Collection
    {
        if (! $user?->activeRole) {
            return collect();
        }

        return $this->proyectos($user)
            ->concat($this->pps($user))
            ->concat($this->enf($user))
            ->sortBy('sort_date')       // más antiguo primero
            ->take($limite)
            ->values();
    }

    public function total(?User $user): int
    {
        return $this->paraRolActivo($user, PHP_INT_MAX)->count();
    }

    /** @return array<string,int> */
    public function porTipo(?User $user): array
    {
        return $this->paraRolActivo($user, PHP_INT_MAX)
            ->groupBy('tipo')
            ->map(fn (Collection $g): int => $g->count())
            ->all();
    }

    /** Días que lleva esperando el elemento más antiguo, para el KPI de bandeja. */
    public function masAntiguoEnDias(?User $user): ?int
    {
        $primero = $this->paraRolActivo($user, PHP_INT_MAX)->first();

        return $primero?->dias_espera;
    }

    /** @return Collection<int, object> */
    private function proyectos(User $user): Collection
    {
        $ids = $this->proyectoIdsConFirmaPendienteParaRolActivo();

        if ($ids->isEmpty()) {
            return collect();
        }

        // La fecha de creación de la firma pendiente es la que marca desde
        // cuándo espera este proyecto, no la del proyecto.
        $esperaDesde = $this->firmasDisponiblesQuery()
            ->where('firma_proyecto.firmable_type', Proyecto::class)
            ->get(['firma_proyecto.firmable_id', 'firma_proyecto.created_at'])
            ->groupBy('firmable_id')
            ->map(fn (Collection $g) => $g->min('created_at'));

        return Proyecto::query()
            ->whereIn('id', $ids)
            ->with(['estadoActual.tipoestado'])
            ->get()
            ->map(function (Proyecto $p) use ($esperaDesde): object {
                $desde = $esperaDesde->get($p->id) ?: $p->created_at;

                return (object) [
                    'tipo' => 'Proyecto',
                    'codigo' => $p->codigo_proyecto,
                    'nombre' => $p->nombre_proyecto,
                    'etapa' => $p->estadoActual?->tipoestado?->nombre,
                    'dias_espera' => $this->diasDesde($desde),
                    'fecha_inicio' => $p->fecha_inicio,
                    'href' => route('historialproyecto', $p->id),
                    'sort_date' => $desde,
                ];
            });
    }

    /** @return Collection<int, object> */
    private function pps(User $user): Collection
    {
        return PpsServicioSocial::pendientesParaUsuario($user)
            ->with('etapaActual')
            ->get()
            ->map(fn (PpsServicioSocial $r): object => (object) [
                'tipo' => 'PPS/SS',
                'codigo' => $r->codigo_registro,
                'nombre' => $r->nombre_estudiante ?: $r->nombre_institucion,
                'etapa' => $r->etapaActual?->nombre,
                'dias_espera' => $this->diasDesde($r->fecha_envio ?: $r->created_at),
                'fecha_inicio' => $r->fecha_inicio,
                'href' => null,
                'sort_date' => $r->fecha_envio ?: $r->created_at,
            ]);
    }

    /** @return Collection<int, object> */
    private function enf(User $user): Collection
    {
        return $this->revisionesEnfDisponibles($user)
            ->with('accion')
            ->get()
            ->map(fn (EnfRevision $r): object => (object) [
                'tipo' => 'ENF',
                'codigo' => $r->accion?->codigo_formulario,
                'nombre' => $r->accion?->nombre_accion ?: 'Educación no formal',
                'etapa' => $r->etapa_nombre,
                'dias_espera' => $this->diasDesde($r->created_at),
                'fecha_inicio' => $r->accion?->fecha_inicio,
                'href' => null,
                'sort_date' => $r->created_at,
            ]);
    }

    /**
     * Revisiones ENF que le tocan al rol activo.
     *
     * Movido tal cual desde DasboardDocente::enfRevisionesDisponiblesQuery():
     * vivía solo en el panel del docente, así que el director nunca veía sus
     * pendientes de Educación No Formal.
     */
    private function revisionesEnfDisponibles(User $user): Builder
    {
        $rolActivo = $user->activeRole?->name;

        if (! $rolActivo) {
            return EnfRevision::query()->whereRaw('1 = 0');
        }

        $pendientes = ['PENDIENTE', 'PENDIENTE_ASIGNACION', 'ASIGNADO', 'EN_PROCESO'];

        return EnfRevision::query()
            ->whereHas('accion', fn (Builder $q): Builder => $q->whereIn('codigo_formulario', ['FORM-DVUS-016', 'FORM-DVUS-018']))
            ->whereIn('estado', $pendientes)
            // Solo la primera etapa pendiente de cada ciclo: las siguientes aún
            // no le tocan a nadie.
            ->whereNotExists(function ($anterior) use ($pendientes): void {
                $anterior->selectRaw('1')
                    ->from('enf_revisiones as previas')
                    ->whereColumn('previas.enf_accion_id', 'enf_revisiones.enf_accion_id')
                    ->whereColumn('previas.proceso', 'enf_revisiones.proceso')
                    ->whereColumn('previas.revision_ciclo', 'enf_revisiones.revision_ciclo')
                    ->whereColumn('previas.orden', '<', 'enf_revisiones.orden')
                    ->whereIn('previas.estado', $pendientes);
            })
            // Y descarta ciclos superados por una subsanación posterior.
            ->whereNotExists(function ($cicloNuevo): void {
                $cicloNuevo->selectRaw('1')
                    ->from('enf_revisiones as posteriores')
                    ->whereColumn('posteriores.enf_accion_id', 'enf_revisiones.enf_accion_id')
                    ->whereColumn('posteriores.proceso', 'enf_revisiones.proceso')
                    ->whereColumn('posteriores.revision_ciclo', '>', 'enf_revisiones.revision_ciclo');
            })
            ->where(function (Builder $responsable) use ($user, $rolActivo): void {
                $responsable
                    ->where(fn (Builder $q) => $q->where('asignado_usuario_id', $user->id)
                        ->where(fn (Builder $r) => $r->whereNull('rol_requerido')->orWhere('rol_requerido', $rolActivo)))
                    ->orWhere(fn (Builder $q) => $q->whereNull('asignado_usuario_id')
                        ->where('rol_requerido', $rolActivo))
                    ->orWhere(fn (Builder $q) => $q->where('responsable_usuario_id', $user->id)
                        ->where(fn (Builder $r) => $r->whereNull('rol_requerido')->orWhere('rol_requerido', $rolActivo)));
            });
    }

    private function diasDesde(mixed $fecha): int
    {
        if (! $fecha) {
            return 0;
        }

        return (int) \Carbon\Carbon::parse($fecha)->startOfDay()->diffInDays(now()->startOfDay());
    }
}
