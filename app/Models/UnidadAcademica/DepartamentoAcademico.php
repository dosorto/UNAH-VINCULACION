<?php

namespace App\Models\UnidadAcademica;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\UnidadAcademica\FacultadCentro as CentroFacultad;

class DepartamentoAcademico extends Model
{
    use HasFactory;

    protected $table = 'departamento_academico';

    protected $fillable = [
        'nombre',
        'centro_facultad_id',
    ];

    public function centroFacultad()
    {
        return $this->belongsTo(CentroFacultad::class, 'centro_facultad_id');
    }


}
