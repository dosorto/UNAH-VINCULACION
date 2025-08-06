<?php

namespace App\Models\ServicioInfraestructura;

use App\Models\SevicioInfraestructura\EmpleadoServicio;
use App\Models\Personal\Empleado;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ActividadServicio extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    protected static $logAttributes = ['id', 'servicio_tecnologico_id', 'descripcion', 'objetivos', 'horas', 'fecha_inicio', 'fecha_finalizacion', 'resultados'];

    protected static $logName = 'Actividad';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logOnly(['id', 'servicio_tecnologico_id', 'responsable_id', 'descripcion', 'fecha_inicio', 'fecha_finalizacion', 'objetivos', 'resultados', 'horas'])
        ->setDescriptionForEvent(fn (string $eventName) => "El registro {$this->descripcion} ha sido {$eventName}");
    }
    
    protected $fillable = [
        'id',
        'descripcion',
        'fecha_inicio',
        'fecha_finalizacion',
        'servicio_tecnologico_id',
        'objetivos',
        'resultados',
        'horas',
    ];
    
    public function servicio()
    {
        return $this->belongsTo(ServicioTecnologico::class, 'servicio_tecnologico_id',);
    }

    public function empleados()
    {
        return $this->belongsToMany(Empleado::class,'acti_empleado_srvc','actividad_id','empleado_id'
        );
    }

    protected $table = 'actividades_servicio';
}
