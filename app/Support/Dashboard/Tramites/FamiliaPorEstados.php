<?php

namespace App\Support\Dashboard\Tramites;

use App\Support\Dashboard\AmbitoPanel;
use App\Support\Dashboard\EstadosProyecto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Familia de trámites que sigue el patrón habitual de NEXO: el modelo guarda su
 * estado actual en `estado_proyecto` (relación polimórfica `estadoable`) y sus
 * firmas en `flujos_aprobacion`.
 *
 * Es el caso de Proyecto, PpsServicioSocial, FichaActualizacion y
 * ServicioTecnologico, y previsiblemente de la mayoría de los formularios que
 * queden por digitalizar. Todos ellos se declaran con configuración —modelo,
 * fases y estados de cada fase— sin escribir una clase nueva.
 *
 * Solo necesitan clase propia los que se salen del patrón, como ENF, que lleva
 * su estado en una columna suya y sus revisiones en tablas aparte.
 */
class FamiliaPorEstados implements FamiliaTramite
{
    /**
     * @param  class-string<Model>  $modelo
     * @param  list<string>  $formularios
     * @param  list<array{clave:string,etiqueta:string,tono:string,estados:list<string>}>  $fases
     * @param  ?string  $columnaCentro  columna que permite acotar por centro/facultad; null si la familia no lo admite
     */
    public function __construct(
        private readonly string $clave,
        private readonly string $etiqueta,
        private readonly string $modelo,
        private readonly array $formularios,
        private readonly array $fases,
        private readonly ?string $columnaCentro = null,
        private readonly ?string $pivoteCentro = null,
    ) {}

    public function clave(): string
    {
        return $this->clave;
    }

    public function etiqueta(): string
    {
        return $this->etiqueta;
    }

    public function formularios(): array
    {
        return $this->formularios;
    }

    public function itinerario(): array
    {
        return array_map(fn (array $fase): array => [
            'clave' => $fase['clave'],
            'etiqueta' => $fase['etiqueta'],
            'tono' => $fase['tono'] ?? 'neutro',
        ], $this->fases);
    }

    public function conteos(AmbitoPanel $ambito): array
    {
        $tabla = $this->tabla();
        $select = [];
        $bindings = [];

        foreach ($this->fases as $fase) {
            $ids = EstadosProyecto::ids($fase['estados']);
            $clave = $fase['clave'];

            if ($ids === []) {
                $select[] = "0 as `{$clave}`";

                continue;
            }

            $marcadores = implode(',', array_fill(0, count($ids), '?'));
            $select[] = "SUM(CASE WHEN ep.tipo_estado_id IN ({$marcadores}) THEN 1 ELSE 0 END) as `{$clave}`";
            $bindings = array_merge($bindings, $ids);
        }

        if ($select === []) {
            return [];
        }

        $fila = $this->consultaBase($ambito)
            ->leftJoin('estado_proyecto as ep', function ($join) use ($tabla): void {
                $join->on('ep.estadoable_id', '=', "{$tabla}.id")
                    ->where('ep.estadoable_type', '=', $this->modelo)
                    ->where('ep.es_actual', '=', true);
            })
            ->selectRaw(implode(', ', $select), $bindings)
            ->first();

        $conteos = [];

        foreach ($this->fases as $fase) {
            $conteos[$fase['clave']] = (int) ($fila->{$fase['clave']} ?? 0);
        }

        return $conteos;
    }

    public function total(AmbitoPanel $ambito): int
    {
        return $this->consultaBase($ambito)->count();
    }

    public function sinIniciar(AmbitoPanel $ambito): int
    {
        $tabla = $this->tabla();
        $ids = EstadosProyecto::ids(EstadosProyecto::SIN_ENVIAR);

        return $this->consultaBase($ambito)
            ->where(function (Builder $query) use ($tabla, $ids): void {
                // Sin fila en estado_proyecto tampoco ha empezado el recorrido.
                $query->whereNotExists(
                    fn ($sub) => $sub->selectRaw('1')
                        ->from('estado_proyecto')
                        ->whereColumn('estado_proyecto.estadoable_id', "{$tabla}.id")
                        ->where('estado_proyecto.estadoable_type', $this->modelo)
                        ->where('estado_proyecto.es_actual', true)
                );

                if ($ids !== []) {
                    $query->orWhereExists(
                        fn ($sub) => $sub->selectRaw('1')
                            ->from('estado_proyecto')
                            ->whereColumn('estado_proyecto.estadoable_id', "{$tabla}.id")
                            ->where('estado_proyecto.estadoable_type', $this->modelo)
                            ->where('estado_proyecto.es_actual', true)
                            ->whereIn('estado_proyecto.tipo_estado_id', $ids)
                    );
                }
            })
            ->count();
    }

    public function admiteAmbito(AmbitoPanel $ambito): bool
    {
        if (! $ambito->esInstitucional() || $ambito->tipo->value === 'global') {
            return true;
        }

        return $this->columnaCentro !== null || $this->pivoteCentro !== null;
    }

    /** @return Builder<Model> */
    protected function consultaBase(AmbitoPanel $ambito): Builder
    {
        $query = $this->modelo::query();

        return $this->acotarPorAmbito($query, $ambito);
    }

    /**
     * Acota al centro cuando la familia sabe hacerlo. Si no —PPS guarda la
     * facultad como texto libre—, se devuelve sin filtrar y admiteAmbito()
     * avisa de que la cifra es institucional, no del centro.
     *
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    protected function acotarPorAmbito(Builder $query, AmbitoPanel $ambito): Builder
    {
        $tabla = $this->tabla();

        return match (true) {
            ! $ambito->esInstitucional() && $ambito->tipo->value === 'ninguno' => $query->whereRaw('1 = 0'),

            $ambito->centroFacultadId !== null && $this->columnaCentro !== null => $query->where(
                "{$tabla}.{$this->columnaCentro}",
                $ambito->centroFacultadId
            ),

            $ambito->centroFacultadId !== null && $this->pivoteCentro !== null => $query->whereExists(
                fn ($sub) => $sub->selectRaw('1')
                    ->from($this->pivoteCentro)
                    ->whereColumn("{$this->pivoteCentro}.proyecto_id", "{$tabla}.id")
                    ->where("{$this->pivoteCentro}.centro_facultad_id", $ambito->centroFacultadId)
            ),

            default => $query,
        };
    }

    protected function tabla(): string
    {
        return (new $this->modelo)->getTable();
    }

    protected function modelo(): string
    {
        return $this->modelo;
    }

    /** Acceso a la conexión para las subclases que necesiten consultas propias. */
    protected function db(): \Illuminate\Database\ConnectionInterface
    {
        return DB::connection();
    }
}
