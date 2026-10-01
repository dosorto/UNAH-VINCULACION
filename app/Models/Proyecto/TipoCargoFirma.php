<?php

namespace App\Models\Proyecto;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoCargoFirma extends Model
{
    use HasFactory;

    public const COORDINADOR_PROYECTO = 'Coordinador Proyecto';

    /**
     * Cargos de quien registra o coordina el expediente: firman, pero nunca
     * llevan sello, sin importar qué otros cargos tenga esa persona. El sello
     * es exclusivo de las etapas administrativas (ver CargoFirma::admiteSello).
     */
    public const CARGOS_SIN_SELLO = [self::COORDINADOR_PROYECTO];

    protected $table = 'tipo_cargo_firma';

    protected $fillable = [
        'id',
        'nombre',
    ];

}
