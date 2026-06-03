<?php

namespace App\Models\ENF;

use App\Models\UnidadAcademica\Carrera;
use App\Models\UnidadAcademica\FacultadCentro;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnfCertificadoCarrera extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'enf_certificado_carreras';

    protected $guarded = [];

    public function certificado()
    {
        return $this->belongsTo(EnfCertificado::class, 'enf_certificado_id');
    }

    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'carrera_id');
    }

    public function centroFacultad()
    {
        return $this->belongsTo(FacultadCentro::class, 'centro_facultad_id');
    }
}
