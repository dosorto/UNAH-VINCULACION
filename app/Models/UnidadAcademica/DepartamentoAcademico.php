<?php

namespace App\Models\UnidadAcademica;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\UnidadAcademica\FacultadCentro as CentroFacultad;
use App\Models\UnidadAcademica\Carrera;

use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;


class DepartamentoAcademico extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nombre', 'siglas', 'centro_facultad_id'])
            ->setDescriptionForEvent(fn(string $eventName) => "El registro {$this->nombre} ha sido {$eventName}");
    }


    protected $table = 'departamento_academico';

    protected $fillable = [
        'nombre',
        'siglas',
        'centro_facultad_id',
    ];

    public function centroFacultad()
    {
        return $this->belongsTo(CentroFacultad::class, 'centro_facultad_id');
    }

    // relacion muchos a muchos con carreras
    public function carreras()
    {
        return $this->belongsToMany(Carrera::class, 'carrera_departamento_academico', 'departamento_academico_id', 'carrera_id');
    }
}
