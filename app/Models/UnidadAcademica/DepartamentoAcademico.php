<?php

namespace App\Models\UnidadAcademica;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepartamentoAcademico extends Model
{
    use HasFactory;

    protected $table = 'departamento_academico';

    protected $fillable = [
        'nombre',
        'centro_facultad_id',
    ];


}
