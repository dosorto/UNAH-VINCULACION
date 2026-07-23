<?php

namespace App\Models\DAFT;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramaAsignatura extends Model
{
    protected $table = 'programas_asignaturas';

    protected $fillable = [
        'programa_certificacion_id',
        'asignatura_id',
        'orden',
        'es_obligatoria',
    ];

    protected $casts = [
        'es_obligatoria' => 'boolean',
    ];

    public function programa(): BelongsTo
    {
        return $this->belongsTo(ProgramaCertificacion::class, 'programa_certificacion_id');
    }

    public function asignatura(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Asignatura::class, 'asignatura_id');
    }
}
