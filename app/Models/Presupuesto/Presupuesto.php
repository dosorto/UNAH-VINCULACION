<?php

namespace App\Models\Presupuesto;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Presupuesto extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logOnly(['id', 'tipo_presupuesto_id', 'administrador_id', 'proyecto_id'])
        ->setDescriptionForEvent(fn (string $eventName) => "El registro {$this->nombre} ha sido {$eventName}");
    }
    
    protected $fillable = [
        'id',
        'tipo_presupuesto_id', 
        'administrador_id', 
        'proyecto_id',
    ];

    public function tipo_presupuesto()
    {
        return $this->belongsTo(Tipo_presupuesto::class, 'tipo_presupuesto_id',);
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'administrador_id',);
    }

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id',);
    }

    protected $table = 'presupuesto';
}
