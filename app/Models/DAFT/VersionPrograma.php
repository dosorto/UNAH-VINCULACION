<?php

namespace App\Models\DAFT;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VersionPrograma extends Model
{
    protected $table = 'versiones_programa';

    protected $fillable = [
        'programa_certificacion_id',
        'numero_version',
        'estado',
        'vigente',
        'publicado_en',
        'publicado_por_usuario_id',
        'notas',
        'datos_programa',
        'centros_facultad',
        'asignaturas',
    ];

    protected $casts = [
        'vigente' => 'boolean',
        'publicado_en' => 'datetime',
        'datos_programa' => 'array',
        'centros_facultad' => 'array',
        'asignaturas' => 'array',
    ];

    public function programa(): BelongsTo
    {
        return $this->belongsTo(ProgramaCertificacion::class, 'programa_certificacion_id');
    }
}
