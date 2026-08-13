<?php

namespace App\Models\Proyecto;

use App\Models\Estado\EstadoProyecto;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProyectoDocumentoSubsanacion extends Model
{
    protected $table = 'proyecto_documentos_subsanacion';

    protected $guarded = ['id'];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id');
    }

    public function movimiento(): BelongsTo
    {
        return $this->belongsTo(EstadoProyecto::class, 'estado_proyecto_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subido_por');
    }
}
