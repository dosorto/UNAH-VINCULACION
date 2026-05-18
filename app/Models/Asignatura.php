<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asignatura extends Model
{
    use HasFactory;

    protected $table = 'asignaturas';

    protected $fillable = [
        'nombre',
        'codigo',
        'carrera_id',
        'departamento_academico_id',
    ];

    public function carrera()
    {
        return $this->belongsTo(\App\Models\UnidadAcademica\Carrera::class, 'carrera_id');
    }

    public function departamentoAcademico()
    {
        return $this->belongsTo(\App\Models\UnidadAcademica\DepartamentoAcademico::class, 'departamento_academico_id');
    }

    public function proyectos()
    {
        return $this->belongsToMany(\App\Models\Proyecto\Proyecto::class, 'proyecto_asignatura', 'asignatura_id', 'proyecto_id');
    }

    public function estudianteProyectos()
    {
        return $this->hasMany(\App\Models\Estudiante\EstudianteProyecto::class);
    }
}
