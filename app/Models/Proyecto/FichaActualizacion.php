<?php

namespace App\Models\Proyecto;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FichaActualizacion extends Model
{
    use HasFactory;

    protected $table = 'ficha_actualizacion';

    protected $fillable = [
        'fecha_registro',
        'empleado_id',
        'proyecto_id',
        'integrantes_baja',
        'motivo_baja',
        'fecha_baja',
        'fecha_ampliacion',
        'motivo_ampliacion',
    ];

    protected $casts = [
        'integrantes_baja' => 'array',
        'fecha_registro' => 'datetime',
        'fecha_baja' => 'date',
        'fecha_ampliacion' => 'date',
    ];

    public function empleado()
    {
        return $this->belongsTo(\App\Models\Empleado::class, 'empleado_id');
    }

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id');
    }
}
