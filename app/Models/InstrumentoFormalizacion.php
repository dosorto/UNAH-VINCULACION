<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstrumentoFormalizacion extends Model
{
    protected $table = 'instrumento_formalizacion';

    protected $fillable = [
        'entidad_contraparte_id',
        'tipo_documento',
        'archivo',
        'nombre_archivo'
    ];

    protected $casts = [
        'tipo_documento' => 'string'
    ];

    public function entidadContraparte(): BelongsTo
    {
        return $this->belongsTo(EntidadContraparte::class);
    }
}
