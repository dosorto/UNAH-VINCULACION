<?php

namespace App\Models\DAFT;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramaRevision extends Model
{
    protected $table = 'programa_revisiones';

    protected $fillable = [
        'programa_certificacion_id',
        'flujo_aprobacion_etapa_id',
        'revision_ciclo',
        'orden',
        'etapa_codigo',
        'etapa_nombre',
        'rol_requerido',
        'responsable_usuario_id',
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

    public function decididoPorUsuario(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'decidido_por_usuario_id');
    }
}
