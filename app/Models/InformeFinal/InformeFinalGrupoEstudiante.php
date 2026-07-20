<?php

namespace App\Models\InformeFinal;

use App\Models\Asignatura;
use App\Models\Estudiante\EstudianteProyecto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InformeFinalGrupoEstudiante extends Model
{
    protected $table = 'informe_final_grupos_estudiantes';

    protected $guarded = ['id'];

    protected $casts = [
        'hombres_planificados' => 'integer',
        'mujeres_planificadas' => 'integer',
    ];

    public function informe(): BelongsTo
    {
        return $this->belongsTo(InformeFinalProyecto::class, 'informe_final_proyecto_id');
    }

    public function planificacion(): BelongsTo
    {
        return $this->belongsTo(EstudianteProyecto::class, 'estudiante_proyecto_id')->withTrashed();
    }

    public function asignatura(): BelongsTo
    {
        return $this->belongsTo(Asignatura::class);
    }

    public function estudiantes(): HasMany
    {
        return $this->hasMany(InformeFinalEstudiante::class, 'informe_final_grupo_estudiante_id');
    }
}
