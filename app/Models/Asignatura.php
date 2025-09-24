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
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    public function periodoAcademico()
    {
        return $this->belongsTo(PeriodoAcademico::class);
    }

    public function estudianteProyectos()
    {
        return $this->hasMany(\App\Models\Estudiante\EstudianteProyecto::class);
    }
}