<?php

namespace App\Models\InformeFinal;

use App\Models\Estudiante\Estudiante;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class InformeFinalEstudiante extends Model
{
    protected $table = 'informe_final_estudiantes';

    protected $guarded = ['id'];

    protected $casts = ['horas_dedicadas' => 'decimal:2'];

    protected static function booted(): void
    {
        static::saving(function (self $estudiante) {
            if (! $estudiante->informe_final_grupo_estudiante_id) {
                throw ValidationException::withMessages([
                    'informe_final_grupo_estudiante_id' => 'El estudiante debe pertenecer a un grupo del Informe Final.',
                ]);
            }

            $grupo = InformeFinalGrupoEstudiante::query()
                ->whereKey($estudiante->informe_final_grupo_estudiante_id)
                ->where('informe_final_proyecto_id', $estudiante->informe_final_proyecto_id)
                ->first();
            if (! $grupo) {
                throw ValidationException::withMessages([
                    'informe_final_grupo_estudiante_id' => 'El grupo seleccionado no pertenece a este Informe Final.',
                ]);
            }

            $estudiante->tipo_participacion = $grupo->tipo_participacion;
        });
    }

    public function informe() { return $this->belongsTo(InformeFinalProyecto::class, 'informe_final_proyecto_id'); }
    public function grupo() { return $this->belongsTo(InformeFinalGrupoEstudiante::class, 'informe_final_grupo_estudiante_id'); }
    public function estudiante() { return $this->belongsTo(Estudiante::class); }
}
