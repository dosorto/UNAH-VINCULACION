<?php

namespace App\Models\InformeFinal;

use App\Models\Proyecto\FirmaProyecto;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\Estado\EstadoProyecto;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InformeFinalDocumentoRevision extends Model
{
    protected $table = 'informe_final_documentos_revision';

    protected $guarded = ['id'];

    public function informe(): BelongsTo { return $this->belongsTo(InformeFinalProyecto::class, 'informe_final_proyecto_id'); }
    public function firma(): BelongsTo { return $this->belongsTo(FirmaProyecto::class, 'firma_proyecto_id'); }
    public function movimiento(): BelongsTo { return $this->belongsTo(EstadoProyecto::class, 'estado_proyecto_id'); }
    public function etapa(): BelongsTo { return $this->belongsTo(FlujoAprobacionEtapa::class, 'flujo_aprobacion_etapa_id'); }
    public function usuario(): BelongsTo { return $this->belongsTo(User::class, 'subido_por'); }
}
