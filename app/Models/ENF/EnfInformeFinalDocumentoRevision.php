<?php

namespace App\Models\ENF;

use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnfInformeFinalDocumentoRevision extends Model
{
    protected $table = 'enf_informe_final_documentos_revision';

    protected $guarded = ['id'];

    public function informe(): BelongsTo { return $this->belongsTo(EnfInformeFinal::class, 'enf_informe_final_id'); }
    public function revision(): BelongsTo { return $this->belongsTo(EnfRevision::class, 'enf_revision_id'); }
    public function accion(): BelongsTo { return $this->belongsTo(EnfAccion::class, 'enf_accion_id'); }
    public function etapa(): BelongsTo { return $this->belongsTo(FlujoAprobacionEtapa::class, 'flujo_aprobacion_etapa_id'); }
    public function usuario(): BelongsTo { return $this->belongsTo(User::class, 'subido_por'); }
}
