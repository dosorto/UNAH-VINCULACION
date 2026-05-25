<?php

namespace App\Models\SGCU;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudEdicion extends Model
{
    protected $table = 'solicitudes_edicion';

    protected $fillable = [
        'edicion_programa_id',
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

    public function edicion(): BelongsTo
    {
        return $this->belongsTo(EdicionPrograma::class, 'edicion_programa_id');
    }
}
