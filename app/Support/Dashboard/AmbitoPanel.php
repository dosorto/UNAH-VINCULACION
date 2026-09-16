<?php

namespace App\Support\Dashboard;

use App\Models\Proyecto\Proyecto;
use Illuminate\Database\Eloquent\Builder;

/**
 * Ámbito resuelto de un usuario en el panel estadístico.
 *
 * Su razón de ser es aplicarA(): concentra en un solo sitio cómo se restringe
 * una consulta de proyectos, para que ninguna métrica del panel pueda filtrar
 * de forma distinta a otra.
 */
final readonly class AmbitoPanel
{
    public function __construct(
        public TipoAmbito $tipo,
        public ?int $empleadoId = null,
        public ?int $userId = null,
        public ?int $centroFacultadId = null,
        public ?int $departamentoAcademicoId = null,
        /** Texto mostrado al usuario: "Toda la UNAH", "CU Valle de Sula"... */
        public string $etiqueta = '',
        public ?string $rolActivo = null,
        /** true si se cayó al ámbito de revisión por no tener centro asignado. */
        public bool $centroIndeterminado = false,
    ) {}

    public static function ninguno(?string $rolActivo = null): self
    {
        return new self(
            tipo: TipoAmbito::Ninguno,
            etiqueta: 'Sin datos disponibles',
            rolActivo: $rolActivo,
        );
    }

    public function esInstitucional(): bool
    {
        return $this->tipo->esInstitucional();
    }

    public function esPersonal(): bool
    {
        return $this->tipo === TipoAmbito::Personal;
    }

    /** Clave estable para cachear métricas por ámbito. */
    public function clave(): string
    {
        return match ($this->tipo) {
            TipoAmbito::Global => 'global',
            TipoAmbito::Centro => 'centro:'.$this->centroFacultadId,
            TipoAmbito::Departamento => 'depto:'.$this->departamentoAcademicoId,
            TipoAmbito::Revision => 'rev:'.$this->empleadoId,
            TipoAmbito::Personal => 'emp:'.$this->empleadoId,
            TipoAmbito::Ninguno => 'nada',
        };
    }

    /**
     * Restringe una consulta de proyectos a este ámbito.
     *
     * Usa whereExists en lugar de join a propósito: los pivotes son de
     * cardinalidad N (un proyecto puede tener varios centros o integrantes), y
     * con join cada fila se multiplicaba, obligando a un distinct() disperso
     * que además rompía los count(). whereExists no altera la cardinalidad y
     * permite respetar el deleted_at de los pivotes, que las consultas
     * anteriores ignoraban.
     *
     * @param  Builder<Proyecto>  $query
     * @return Builder<Proyecto>
     */
    public function aplicarA(Builder $query, string $tabla = 'proyecto'): Builder
    {
        return match ($this->tipo) {
            TipoAmbito::Global => $query,

            TipoAmbito::Centro => $query->whereExists(
                fn ($sub) => $sub->selectRaw('1')
                    ->from('proyecto_centro_facultad')
                    ->whereColumn('proyecto_centro_facultad.proyecto_id', "{$tabla}.id")
                    ->where('proyecto_centro_facultad.centro_facultad_id', $this->centroFacultadId)
            ),

            TipoAmbito::Departamento => $query->whereExists(
                fn ($sub) => $sub->selectRaw('1')
                    ->from('proyecto_depto_ac')
                    ->whereColumn('proyecto_depto_ac.proyecto_id', "{$tabla}.id")
                    ->where('proyecto_depto_ac.departamento_academico_id', $this->departamentoAcademicoId)
            ),

            TipoAmbito::Revision => $query->whereExists(
                fn ($sub) => $sub->selectRaw('1')
                    ->from('firma_proyecto')
                    ->whereColumn('firma_proyecto.firmable_id', "{$tabla}.id")
                    ->where('firma_proyecto.firmable_type', Proyecto::class)
                    ->where('firma_proyecto.empleado_id', $this->empleadoId)
                    ->whereNull('firma_proyecto.deleted_at')
            ),

            TipoAmbito::Personal => $query->whereExists(
                fn ($sub) => $sub->selectRaw('1')
                    ->from('empleado_proyecto')
                    ->whereColumn('empleado_proyecto.proyecto_id', "{$tabla}.id")
                    ->where('empleado_proyecto.empleado_id', $this->empleadoId)
                    ->whereNull('empleado_proyecto.deleted_at')
            ),

            TipoAmbito::Ninguno => $query->whereRaw('1 = 0'),
        };
    }

    /**
     * Subconsulta con los ids de proyecto del ámbito. Para métricas que parten
     * de un pivote (ODS, centros, actividades) en vez de la tabla proyecto.
     */
    public function idsProyectoQuery(): \Illuminate\Database\Query\Builder
    {
        return $this->aplicarA(Proyecto::query())
            ->getQuery()
            ->select('proyecto.id')
            ->from('proyecto')
            ->whereNull('proyecto.deleted_at');
    }
}
