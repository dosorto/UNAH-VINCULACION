<?php

namespace App\Models\SGCU;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudCambioPrograma extends Model
{
    protected $table = 'solicitudes_cambio_programa';

    protected $fillable = [
        'programa_certificacion_id',
        'solicitado_por_usuario_id',
        'estado',
        'motivo',
        'solicitado_en',
        'resuelto_en',
        'resuelto_por_usuario_id',
    ];

    protected $casts = [
        'solicitado_en' => 'datetime',
        'resuelto_en' => 'datetime',
    ];

    public function programa(): BelongsTo
    {
        return $this->belongsTo(ProgramaCertificacion::class, 'programa_certificacion_id');
    }
}
