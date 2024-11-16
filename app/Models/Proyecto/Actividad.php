<?php

namespace App\Models\Proyecto;

use App\Models\Personal\Empleado;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use App\Models\Personal\EmpleadoProyecto;
use Spatie\Activitylog\Traits\LogsActivity;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Actividad extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    protected static $logAttributes = ['id', 'proyecto_id', 'descripcion', 'objetivos', 'horas', 'fecha_ejecucion'];

    protected static $logName = 'Actividad';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logOnly(['id', 'proyecto_id', 'responsable_id', 'descripcion', 'fecha_ejecucion'])
        ->setDescriptionForEvent(fn (string $eventName) => "El registro {$this->descripcion} ha sido {$eventName}");
    }
    
    protected $fillable = [
        'id',
        'descripcion',
        'fecha_ejecucion',
        'objetivos',
        'horas',
        'proyecto_id',
    ];
    
    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id',);
    }

    // relacion 
    public function empleados()
    {
        return $this->belongsToMany(Empleado::class, 'actividad_empleado');
    }

    protected $table = 'actividades';
}
