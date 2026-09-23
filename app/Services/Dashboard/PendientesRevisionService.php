<?php

namespace App\Services\Dashboard;

use App\Concerns\ResolvesFirmasPendientes;
use App\Models\ENF\EnfRevision;
use App\Models\Pasantia;
use App\Models\PpsServicioSocial;
use App\Models\Proyecto\DocumentoProyecto;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\Proyecto\Proyecto;
use App\Models\User;
use App\Support\Proyecto\EtapaActualFirma;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Bandeja de revisión del rol activo: proyectos y sus informes, PPS/SS,
 * pasantías y ENF en una sola lista ordenada por antigüedad.
 *
 * Unifica lo que estaba repartido entre DashboardDirector (proyectos y PPS) y
 * DasboardDocente (ENF), de modo que cada panel mostraba una parte distinta de
 * lo mismo. Cada tipo sale de la misma consulta que la bandeja de tareas
 * (ProyectosPorFirmar) y el contador de la barra, para que los tres coincidan.
 *
 * El orden es del más antiguo al más reciente, al revés que antes: a quien
 * revisa le importa lo que lleva más tiempo esperando, no lo último que entró.
 *
 * Nada de esto se cachea entre peticiones: quien aprueba algo debe ver bajar
 * el contador en el siguiente render. Dentro de una misma petición sí se
 * reutiliza: el panel del director pide la lista, el total, el conteo por tipo
 * y la espera más larga, y antes cada uno la recalculaba entera.
 */
class PendientesRevisionService
{
    use ResolvesFirmasPendientes;

    /** @var array<string, Collection<int, object>> */
    private array $memo = [];

    /**
     * @return Collection<int, object{tipo:string,codigo:?string,nombre:string,etapa:?string,dias_espera:int,fecha_inicio:mixed,href:?string,sort_date:mixed}>
     */
    public function paraRolActivo(?User $user, int $limite = 50): Collection
    {
        if (! $user?->activeRole) {
            return collect();
        }

        $clave = $user->id.':'.$user->activeRole->id;

        $this->memo[$clave] ??= $this->proyectos()
            ->concat($this->pps($user))
            ->concat($this->pasantias($user))
            ->concat($this->enf($user))
            ->sortBy('sort_date')       // más antiguo primero
            ->values();

        return $this->memo[$clave]->take($limite)->values();
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

    /**
     * Inscripciones de proyecto e informes (intermedio y final) que esperan
     * la firma del usuario.
     *
     * Antes solo se miraban las firmas del proyecto, así que un informe final
     * en revisión —el cierre de un FORM-DVUS-015, por ejemplo— aparecía en la
     * bandeja y en el contador de la barra, pero no en el panel.
     *
     * @return Collection<int, object>
     */
    private function proyectos(): Collection
    {
        $firmas = $this->firmasDisponiblesQuery()
            ->whereIn('firma_proyecto.firmable_type', [Proyecto::class, DocumentoProyecto::class])
            ->selectRaw(EtapaActualFirma::llegadaSql().' as espera_desde')
            ->with('cargo_firma.tipoCargoFirma')
            ->get()
            // Una fila por expediente: la etapa puede tener varias firmas
            // candidatas cuando se envió a todos los usuarios de un rol.
            ->groupBy(fn (FirmaProyecto $firma): string => $firma->firmable_type.'#'.$firma->firmable_id)
            ->map(fn (Collection $candidatas): FirmaProyecto => $candidatas->sortBy('espera_desde')->first());

        if ($firmas->isEmpty()) {
            return collect();
        }

        $documentos = DocumentoProyecto::query()
            ->whereIn('id', $firmas->where('firmable_type', DocumentoProyecto::class)->pluck('firmable_id'))
            ->get()
            ->keyBy('id');

        $proyectos = Proyecto::query()
            ->whereIn('id', $firmas->where('firmable_type', Proyecto::class)->pluck('firmable_id')
                ->concat($documentos->pluck('proyecto_id')))
            ->get()
            ->keyBy('id');

        return $firmas
            ->map(function (FirmaProyecto $firma) use ($documentos, $proyectos): ?object {
                $documento = $firma->firmable_type === DocumentoProyecto::class
                    ? $documentos->get($firma->firmable_id)
                    : null;
                $proyecto = $proyectos->get($documento ? $documento->proyecto_id : $firma->firmable_id);

                if (! $proyecto) {
                    return null;
                }

                $desde = Carbon::parse($firma->espera_desde ?: $firma->created_at);

                return (object) [
                    'tipo' => $documento ? ($documento->tipo_documento ?: 'Informe') : 'Proyecto',
                    'codigo' => $proyecto->codigo_proyecto,
                    'nombre' => $proyecto->nombre_proyecto,
                    // La etapa de la firma, no el estado: desde que el proyecto
                    // queda "En revision" toda la inscripción, el estado ya no
                    // dice en qué paso está.
                    'etapa' => $firma->etapa_nombre ?: $firma->cargo_firma?->tipoCargoFirma?->nombre,
                    'dias_espera' => $this->diasDesde($desde),
                    'fecha_inicio' => $proyecto->fecha_inicio,
                    // Los informes se revisan desde la bandeja, no desde el
                    // historial del proyecto.
                    'href' => $documento
                        ? route('SolicitudProyectosDocente')
                        : route('historialproyecto', $proyecto->id),
                    'sort_date' => $desde,
                ];
            })
            ->filter()
            ->values();
    }

    /** @return Collection<int, object> */
    private function pps(User $user): Collection
    {
        return PpsServicioSocial::pendientesParaUsuario($user)
            ->select('pps_servicio_social.*')
            ->selectRaw($this->ultimaAprobacionSql('pps_servicio_social').' as ultima_aprobacion', [PpsServicioSocial::class])
            ->with('etapaActual')
            ->get()
            ->map(function (PpsServicioSocial $r): object {
                $desde = Carbon::parse($r->ultima_aprobacion ?: $r->fecha_envio ?: $r->created_at);

                return (object) [
                    'tipo' => 'PPS/SS',
                    'codigo' => $r->codigo_registro,
                    'nombre' => $r->nombre_estudiante ?: $r->nombre_institucion,
                    'etapa' => $r->etapaActual?->nombre,
                    'dias_espera' => $this->diasDesde($desde),
                    'fecha_inicio' => $r->fecha_inicio,
                    'href' => route('pps-servicio-social.show', $r->id),
                    'sort_date' => $desde,
                ];
            });
    }

    /** @return Collection<int, object> */
    private function pasantias(User $user): Collection
    {
        return Pasantia::pendientesParaUsuario($user)
            ->select('pasantias.*')
            ->selectRaw($this->ultimaAprobacionSql('pasantias').' as ultima_aprobacion', [Pasantia::class])
            ->with('etapaActual')
            ->get()
            ->map(function (Pasantia $r): object {
                $desde = Carbon::parse($r->ultima_aprobacion ?: $r->fecha_envio ?: $r->created_at);

                return (object) [
                    'tipo' => 'Pasantía',
                    'codigo' => $r->codigo_registro,
                    'nombre' => $r->nombre_estudiante ?: ($r->nombre_institucion ?: 'Registro de pasantía'),
                    'etapa' => $r->etapaActual?->nombre,
                    'dias_espera' => $this->diasDesde($desde),
                    'fecha_inicio' => $r->fecha_inicio,
                    'href' => route('pasantias.show', $r->id),
                    'sort_date' => $desde,
                ];
            });
    }

    /** @return Collection<int, object> */
    private function enf(User $user): Collection
    {
        // Las revisiones de todas las etapas se crean al enviar: la de esta
        // etapa empieza a esperar cuando decide la anterior del mismo ciclo.
        return EnfRevision::pendientesParaUsuario($user)
            ->select('enf_revisiones.*')
            ->selectRaw(EnfRevision::llegadaSql().' as llegada')
            ->with('accion')
            ->get()
            ->map(function (EnfRevision $r): object {
                $desde = Carbon::parse($r->llegada ?: $r->created_at);

                return (object) [
                    'tipo' => 'ENF',
                    'codigo' => $r->accion?->codigo_formulario,
                    'nombre' => $r->accion?->nombre_accion ?: 'Educación no formal',
                    'etapa' => $r->etapa_nombre,
                    'dias_espera' => $this->diasDesde($desde),
                    'fecha_inicio' => $r->accion?->fecha_inicio,
                    // ENF se revisa en el modal de la bandeja, no tiene ruta propia.
                    'href' => route('SolicitudProyectosDocente'),
                    'sort_date' => $desde,
                ];
            });
    }

    /**
     * Última aprobación del ciclo vigente de un registro con firmas por etapa
     * (PPS/SS, pasantías): como se aprueba etapa por etapa, es la fecha en que
     * le llegó la etapa actual. Nula mientras espera la primera.
     */
    private function ultimaAprobacionSql(string $tabla): string
    {
        return "(SELECT MAX(aprobada.fecha_firma) FROM firma_proyecto aprobada
                 WHERE aprobada.firmable_type = ? AND aprobada.firmable_id = {$tabla}.id
                   AND aprobada.estado_revision = 'Aprobado' AND aprobada.deleted_at IS NULL
                   AND aprobada.revision_ciclo = (SELECT MAX(ciclo.revision_ciclo) FROM firma_proyecto ciclo
                        WHERE ciclo.firmable_type = aprobada.firmable_type
                          AND ciclo.firmable_id = aprobada.firmable_id
                          AND ciclo.deleted_at IS NULL))";
    }

    private function diasDesde(mixed $fecha): int
    {
        if (! $fecha) {
            return 0;
        }

        return (int) Carbon::parse($fecha)->startOfDay()->diffInDays(now()->startOfDay());
    }
}
