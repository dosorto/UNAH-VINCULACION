<?php
namespace App\Models\Proyecto;

use Illuminate\Database\Eloquent\Model;

class EquipoEjecutorBaja extends Model
{
    protected $fillable = [
        'proyecto_id',
        'tipo_integrante',
        'integrante_id',
        'fecha_baja',
        'motivo_baja',
    ];
}
