<?php

namespace App\Models\ENF;

use App\Models\Estudiante\Estudiante;
use App\Models\Personal\Empleado;
use App\Models\UnidadAcademica\Carrera;
use App\Models\UnidadAcademica\FacultadCentro;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnfParticipacionUniversitaria extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'enf_participacion_universitaria';

    protected $guarded = [];

    public function accion()
    {
        return $this->belongsTo(EnfAccion::class, 'enf_accion_id');
    }

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
    }

    public function centroFacultad()
    {
        return $this->belongsTo(FacultadCentro::class, 'centro_facultad_id');
    }

    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'carrera_id');
    }
}
