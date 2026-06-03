<?php

namespace App\Models\ENF;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnfRevision extends Model
{
    protected $table = 'enf_revisiones';

    protected $guarded = [];

    protected $casts = [
        'revision_ciclo' => 'integer',
        'orden' => 'integer',
        'firmado_en' => 'datetime',
    ];

    public function accion(): BelongsTo
    {
        return $this->belongsTo(EnfAccion::class, 'enf_accion_id');
    }

    public function flujoEtapa(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Proyecto\FlujoAprobacionEtapa::class, 'flujo_aprobacion_etapa_id');
    }

    public function responsableUsuario(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'responsable_usuario_id');
    }

    public function asignadoUsuario(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'asignado_usuario_id');
    }
}
