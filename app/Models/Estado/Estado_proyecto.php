<?php

namespace App\Models\Estado;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Estado_proyecto extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logOnly(['id', 'proyecto_id', 'empleado_id', 'tipo_estado_id', 'fecha', 'comentario'])
        ->setDescriptionForEvent(fn (string $eventName) => "El registro {$this->nombre} ha sido {$eventName}");
    }
    
    protected $fillable = [
        'id',
        'proyecto_id',
        'empleado_id', 
        'tipo_estado_id', 
        'fecha',
        'comentario',
    ];

    public function estado_proyecto()
    {
        return $this->belongsTo(Estado_proyecto::class, 'proyecto_id',);
    }


    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id',);
    }

    public function tipo_estado_proyecto()
    {
        return $this->belongsTo(Tipo_estado_proyecto::class, 'tipo_estado_id',);
    }
    protected $table = 'estado_proyecto';
}
