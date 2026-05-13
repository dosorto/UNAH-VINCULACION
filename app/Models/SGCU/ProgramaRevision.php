<?php

namespace App\Models\SGCU;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramaRevision extends Model
{
    protected $table = 'programa_revisiones';

    protected $fillable = [
        'programa_certificacion_id',
        'revision_ciclo',
        'orden',
        'etapa_codigo',
        'etapa_nombre',
        'rol_requerido',
        'estado',
        'asignado_usuario_id',
        'decidido_por_usuario_id',
        'observaciones',
        'firma_nombre',
        'firmado_en',
    ];

    protected $casts = [
        'firmado_en' => 'datetime',
        'revision_ciclo' => 'integer',
        'orden' => 'integer',
    ];

    public function programa(): BelongsTo
    {
        return $this->belongsTo(ProgramaCertificacion::class, 'programa_certificacion_id');
    }
}
