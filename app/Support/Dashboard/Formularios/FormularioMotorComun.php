<?php

namespace App\Support\Dashboard\Formularios;

use App\Support\Dashboard\AmbitoPanel;
use App\Support\Dashboard\TipoAmbito;
use Illuminate\Database\Query\Builder;

/**
 * Cualquier formulario sobre el motor común (App\Concerns\TieneFlujoPorEtapas)
 * que tenga su propia tabla: PPS/Servicio Social, pasantías y los que se
 * digitalicen igual. Se declara en config('nexo.dashboard.formularios') con su
 * modelo y columnas; no hace falta escribir una clase.
 */
final class FormularioMotorComun extends FormularioConFirmas
{
    /**
     * @param  class-string  $modelo
     * @param  ?string  $columnaAutor  quien lo registró, para el ámbito personal
     * @param  ?string  $columnaCentro  FK a centro_facultad; null si no la guarda (o la guarda como texto)
     * @param  ?string  $columnaDepartamento  FK a departamento_academico; null si no la guarda
     */
    public function __construct(
        string $codigo,
        string $nombre,
        ?string $tipoAccion,
        private readonly string $modelo,
        private readonly ?string $columnaAutor = 'created_by',
        private readonly ?string $columnaCentro = null,
        private readonly ?string $columnaDepartamento = null,
    ) {
        parent::__construct($codigo, $nombre, $tipoAccion);
    }

    public function admiteAmbito(AmbitoPanel $ambito): bool
    {
        return match ($ambito->tipo) {
            TipoAmbito::Centro => $this->columnaCentro !== null,
            TipoAmbito::Departamento => $this->columnaDepartamento !== null,
            default => true,
        };
    }

    protected function modelo(): string
    {
        return $this->modelo;
    }

    protected function idsQuery(AmbitoPanel $ambito): Builder
    {
        $tabla = (new $this->modelo)->getTable();
        $query = $this->modelo::query();

        match ($ambito->tipo) {
            TipoAmbito::Ninguno => $query->whereRaw('1 = 0'),
            TipoAmbito::Centro => $this->columnaCentro !== null
                ? $query->where("{$tabla}.{$this->columnaCentro}", $ambito->centroFacultadId)
                : $query,
            TipoAmbito::Departamento => $this->columnaDepartamento !== null
                ? $query->where("{$tabla}.{$this->columnaDepartamento}", $ambito->departamentoAcademicoId)
                : $query,
            TipoAmbito::Personal => $this->columnaAutor !== null
                ? $query->where("{$tabla}.{$this->columnaAutor}", $ambito->userId)
                : $query->whereRaw('1 = 0'),
            TipoAmbito::Revision => $query->whereExists(fn ($firma) => $firma->selectRaw('1')
                ->from('firma_proyecto')
                ->where('firma_proyecto.firmable_type', $this->modelo)
                ->whereColumn('firma_proyecto.firmable_id', "{$tabla}.id")
                ->where('firma_proyecto.empleado_id', $ambito->empleadoId)
                ->whereNull('firma_proyecto.deleted_at')),
            TipoAmbito::Global => $query,
        };

        return $query->select("{$tabla}.id")->toBase();
    }
}
