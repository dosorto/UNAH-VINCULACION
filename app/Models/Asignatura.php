<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\PeriodoAcademico;

class Asignatura extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'asignaturas';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'codigo',
        'nombre',
        'periodo_academico_id',
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
        return $this->belongsTo(PeriodoAcademico::class);
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