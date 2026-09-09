<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PpsDocumentoGenerado extends Model
{
    protected $table = 'pps_documentos_generados';

    protected $fillable = ['pps_servicio_social_id', 'tipo', 'archivo', 'nombre_original', 'version', 'generado_por', 'generado_en'];

    protected $casts = ['generado_en' => 'datetime'];

    public function pps(): BelongsTo { return $this->belongsTo(PpsServicioSocial::class, 'pps_servicio_social_id'); }
    public function usuario(): BelongsTo { return $this->belongsTo(User::class, 'generado_por'); }
}
