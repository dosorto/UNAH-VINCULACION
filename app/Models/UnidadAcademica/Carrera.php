<?php

namespace App\Models\UnidadAcademica;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;


class Carrera extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    protected static $logAttributes = ['id', 'nombre', 'siglas', 'facultad_centro_id'];

    protected static $logName = 'Carrera';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logOnly(['id', 'nombre', 'siglas', 'facultad_centro_id'])
        ->setDescriptionForEvent(fn (string $eventName) => "El registro {$this->nombre} ha sido {$eventName}");
    }
    
    protected $fillable = [
        'id',
        'nombre',
        'siglas',
        'facultad_centro_id',
        'departamento_academico_id',
    ];

    public function facultadcentro()
    {
        return $this->belongsTo(FacultadCentro::class, 'facultad_centro_id',);
    }

    // relacion muchos a muchos con la tabla facultad_centro mediante la tabla intermedia carrera_facultad_centro
    public function facultadCentros()
    {
        return $this->belongsToMany(FacultadCentro::class, 'carrera_facultad_centro', 'carrera_id', 'facultad_centro_id');
    }

    // relacion muchos a muchos con departamentos académicos
    public function departamentosAcademicos()
    {
        return $this->belongsToMany(DepartamentoAcademico::class, 'carrera_departamento_academico', 'carrera_id', 'departamento_academico_id');
    }

    public function departamentoAcademico()
    {
        return $this->belongsTo(\App\Models\UnidadAcademica\DepartamentoAcademico::class, 'departamento_academico_id');
    }

    protected $table = 'carrera';

}
