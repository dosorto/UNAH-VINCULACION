<?php

namespace App\Models;

use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PpsServicioSocialRevisionHistorial extends Model
{
    use HasFactory;

    protected $table = 'pps_servicio_social_revision_historial';

    protected $fillable = [
        'pps_servicio_social_id',
        'flujo_aprobacion_id',
        'etapa_origen_id',
        'etapa_destino_id',
        'accion',
        'estado_origen',
        'estado_destino',
        'comentario',
        'motivo_rechazo',
        'realizado_por',
    ];

    public function registro(): BelongsTo
    {
        return $this->belongsTo(PpsServicioSocial::class, 'pps_servicio_social_id');
    }

    public function flujoAprobacion(): BelongsTo
    {
        return $this->belongsTo(FlujoAprobacion::class, 'flujo_aprobacion_id');
    }

    public function etapaOrigen(): BelongsTo
    {
        return $this->belongsTo(FlujoAprobacionEtapa::class, 'etapa_origen_id');
    }

    public function etapaDestino(): BelongsTo
    {
        return $this->belongsTo(FlujoAprobacionEtapa::class, 'etapa_destino_id');
    }

    public function realizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'realizado_por');
    }
}
