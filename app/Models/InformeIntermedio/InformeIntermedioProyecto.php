<?php

namespace App\Models\InformeIntermedio;

use App\Models\Proyecto\DocumentoProyecto;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\Proyecto\Proyecto;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InformeIntermedioProyecto extends Model
{
    public const ESTADO_BORRADOR = 'BORRADOR';
    public const ESTADO_EN_REVISION = 'EN_REVISION';
    public const ESTADO_SUBSANACION = 'SUBSANACION';
    public const ESTADO_APROBADO = 'APROBADO';

    protected $table = 'informe_intermedio_proyectos';

    protected $guarded = ['id'];

    protected $casts = [
        'tamano_bytes' => 'integer',
        'fecha_carga' => 'datetime',
        'fecha_envio' => 'datetime',
        'fecha_aprobacion' => 'datetime',
    ];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function documento(): BelongsTo
    {
        return $this->belongsTo(DocumentoProyecto::class, 'documento_proyecto_id');
    }

    public function etapaActual(): BelongsTo
    {
        return $this->belongsTo(FlujoAprobacionEtapa::class, 'etapa_actual_id');
    }

    public function etapaRetorno(): BelongsTo
    {
        return $this->belongsTo(FlujoAprobacionEtapa::class, 'etapa_retorno_id');
    }

    public function usuarioCarga(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subido_por');
    }

    public function esEditable(): bool
    {
        return in_array($this->estado, [self::ESTADO_BORRADOR, self::ESTADO_SUBSANACION], true);
    }
}
