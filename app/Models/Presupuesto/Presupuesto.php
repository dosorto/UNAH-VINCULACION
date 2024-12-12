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

    protected static $logAttributes = [
        'id',
        'proyecto_id',
        'aporte_estudiantes',
        'aporte_profesores',
        'aporte_academico_unah',
        'aporte_transporte_unah',
        'aporte_contraparte',
        'aporte_comunidad',
    ];

    protected static $logName = 'Presupuesto';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['id', 'tipo_presupuesto_id', 'administrador_id', 'proyecto_id'])
            ->setDescriptionForEvent(fn(string $eventName) => "El registro {$this->nombre} ha sido {$eventName}");
    }

    protected $fillable = [
        'id',
        'proyecto_id',
        'aporte_estudiantes',
        'aporte_profesores',
        'aporte_academico_unah',
        'aporte_transporte_unah',
        'aporte_contraparte',
        'aporte_comunidad',
    ];

    public function tipopresupuesto()
    {
        return $this->belongsTo(TipoPresupuesto::class, 'tipo_presupuesto_id',);
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
