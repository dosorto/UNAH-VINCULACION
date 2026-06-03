<?php

namespace App\Models\ENF;

use App\Models\Asignatura;
use App\Models\PeriodoAcademico;
use App\Models\UnidadAcademica\Carrera;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnfPracticaAsignatura extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'enf_practicas_asignatura';

    protected $guarded = [];

    public function accion()
    {
        return $this->belongsTo(EnfAccion::class, 'enf_accion_id');
    }

    public function asignatura()
    {
        return $this->belongsTo(Asignatura::class, 'asignatura_id');
    }

    public function periodoAcademico()
    {
        return $this->belongsTo(PeriodoAcademico::class, 'periodo_academico_id');
    }

    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'carrera_id');
    }
}
