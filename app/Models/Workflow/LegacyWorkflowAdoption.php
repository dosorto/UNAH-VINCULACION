<?php

namespace App\Models\Workflow;

use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LegacyWorkflowAdoption extends Model
{
    protected $table = 'workflow_legacy_adoptions';

    protected $fillable = [
        'flujo_aprobacion_id',
        'etapa_inicio_id',
        'orden_inicio',
        'proceso',
        'modo',
        'estado_origen_id',
        'estado_origen',
        'revisor_usuario_id',
        'adoptado_por_usuario_id',
        'evidencia',
        'adoptado_en',
    ];

    protected function casts(): array
    {
        return [
            'flujo_aprobacion_id' => 'integer',
            'etapa_inicio_id' => 'integer',
            'orden_inicio' => 'integer',
            'estado_origen_id' => 'integer',
            'revisor_usuario_id' => 'integer',
            'adoptado_por_usuario_id' => 'integer',
            'evidencia' => 'array',
            'adoptado_en' => 'datetime',
        ];
    }

    public function adoptable(): MorphTo
    {
        return $this->morphTo();
    }

    public function flujo(): BelongsTo
    {
        return $this->belongsTo(FlujoAprobacion::class, 'flujo_aprobacion_id');
    }

    public function etapaInicio(): BelongsTo
    {
        return $this->belongsTo(FlujoAprobacionEtapa::class, 'etapa_inicio_id');
    }

    public function revisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisor_usuario_id');
    }

    public function adoptadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adoptado_por_usuario_id');
    }
}
