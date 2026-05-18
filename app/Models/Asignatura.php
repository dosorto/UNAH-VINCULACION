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
        'descripcion',
        'carrera_id',
        'departamento_academico_id',
        'creditos_academicos',
        'horas_academicas',
        'ruta_documento_descripcion_minima',
        'activa',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    protected $casts = [
        'creditos_academicos' => 'decimal:2',
        'horas_academicas' => 'integer',
        'activa' => 'boolean',
    ];

    public function periodoAcademico()
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

    public function prerrequisitos()
    {
        return $this->belongsToMany(
            self::class,
            'asignatura_prerrequisitos',
            'asignatura_id',
            'prerrequisito_asignatura_id'
        )->withTimestamps();
    }

    public function esPrerrequisitoDe()
    {
        return $this->belongsToMany(
            self::class,
            'asignatura_prerrequisitos',
            'prerrequisito_asignatura_id',
            'asignatura_id'
        )->withTimestamps();
    }
}

