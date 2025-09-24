<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Asignatura;
use App\Models\Estudiante\EstudianteProyecto;

class PeriodoAcademico extends Model
{
    use HasFactory;
    protected $table = 'periodos_academicos';

     /**
     * The attributes that are mass assignable.
     *
     * @var array
     */ 

    protected $fillable = ['nombre'];

    public function asignaturas()
    {
        return $this->hasMany(Asignatura::class);
    }

    public function estudianteProyectos()
    {
        return $this->hasMany(EstudianteProyecto::class);
    }
}