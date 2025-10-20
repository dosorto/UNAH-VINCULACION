<?php

namespace App\Models\Estudiante;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Proyecto\Proyecto;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EstudianteProyecto extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    protected static $logAttributes = ['id', 'estudiante_id', 'proyecto_id', 'tipo_participacion_estudiante'];

    protected static $logName = 'EstudianteProyecto';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logOnly(['id', 'estudiante_id', 'proyecto_id', 'tipo_participacion'])
        ->setDescriptionForEvent(fn (string $eventName) => "El registro {$this->estudiante_id} ha sido {$eventName}");
    }
    
    protected $fillable = [
        'id',
        'estudiante_id', 
        'proyecto_id', 
        'tipo_participacion_estudiante',
        'asignatura_id',
        'periodo_academico_id',
    ];

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id',);
    }

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id',);
    }

    public function tipoParticipacion()
    {
        return $this->belongsTo(TipoParticipacion::class, 'tipo_participacion_id');
    }

    public function asignatura()
    {
        return $this->belongsTo(\App\Models\Asignatura::class);
    }

    public function periodoAcademico()
    {
        return $this->belongsTo(\App\Models\PeriodoAcademico::class);
    }

    protected $table = 'estudiante_proyecto';
}