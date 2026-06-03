<?php

namespace App\Models\ENF;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnfContraparte extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'enf_contrapartes';

    protected $guarded = [];

    protected $casts = [
        'aporte_monetario' => 'decimal:2',
        'aporte_especie' => 'decimal:2',
    ];

    public function accion()
    {
        return $this->belongsTo(EnfAccion::class, 'enf_accion_id');
    }

    public function tipoContraparte()
    {
        return $this->belongsTo(EnfCatalogo::class, 'tipo_contraparte_id');
    }

    public function instrumentoAlianza()
    {
        return $this->belongsTo(EnfCatalogo::class, 'instrumento_alianza_id');
    }
}
