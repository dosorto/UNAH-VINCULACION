<?php

namespace App\Models\InformeFinal;

use App\Models\Personal\Empleado;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InformeFinalActividadParticipante extends Model
{
    protected $table = 'informe_final_actividad_participantes';

    protected $guarded = ['id'];

    protected $casts = [
        'horas_dedicadas' => 'decimal:2',
        'es_responsable' => 'boolean',
    ];

    public function actividad(): BelongsTo
    {
        return $this->belongsTo(InformeFinalActividad::class, 'informe_final_actividad_id');
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(InformeFinalEstudiante::class, 'informe_final_estudiante_id');
    }

    public function voluntario(): BelongsTo
    {
        return $this->belongsTo(InformeFinalVoluntario::class, 'informe_final_voluntario_id');
    }
}
