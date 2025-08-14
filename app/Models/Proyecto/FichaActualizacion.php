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
        'motivo_responsabilidades_nuevos',
        'motivo_razones_cambio',
    ];

    protected $casts = [
        'integrantes_baja' => 'array',
        'fecha_registro' => 'datetime',
        'fecha_baja' => 'date',
        'fecha_ampliacion' => 'date',
    ];

    public function empleado()
    {
        return $this->belongsTo(\App\Models\Personal\Empleado::class, 'empleado_id');
    }

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id');
    }

    // Relación morfológica con estados
    public function estado_proyecto()
    {
        return $this->morphMany(\App\Models\Estado\EstadoProyecto::class, 'estadoable');
    }

    // Relación morfológica con firmas
    public function firma_proyecto()
    {
        return $this->morphMany(FirmaProyecto::class, 'firmable');
    }

    // Obtener el último estado
    public function obtenerUltimoEstado()
    {
        return $this->estado_proyecto()->orderBy('created_at', 'desc')->first();
    }

    // Obtener el estado actual
    public function estado()
    {
        return $this->hasOneThrough(
            \App\Models\Estado\EstadoProyecto::class,
            FichaActualizacion::class,
            'id',
            'estadoable_id',
            'id',
            'id'
        )->where('estadoable_type', FichaActualizacion::class)
         ->where('es_actual', true)
         ->latest('fecha');
    }
}
