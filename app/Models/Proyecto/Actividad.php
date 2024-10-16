<?php

namespace App\Models\Proyecto;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Actividad extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    protected static $logAttributes = ['id', 'proyecto_id', 'responsable_id', 'descripcion', 'fecha_ejecucion'];

    protected static $logName = 'Actividad';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logOnly(['id', 'proyecto_id', 'responsable_id', 'descripcion', 'fecha_ejecucion'])
        ->setDescriptionForEvent(fn (string $eventName) => "El registro {$this->descripcion} ha sido {$eventName}");
    }
    
    protected $fillable = [
        'id',
        'proyecto_id', 
        'responsable_id', 
        'descripcion', 
        'fecha_ejecucion'
    ];

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id',);
    }

    protected $table = 'actividades';
}
