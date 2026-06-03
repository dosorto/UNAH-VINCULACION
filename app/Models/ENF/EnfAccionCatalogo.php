<?php

namespace App\Models\ENF;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnfAccionCatalogo extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'enf_accion_catalogo';

    protected $guarded = [];

    public function accion()
    {
        return $this->belongsTo(EnfAccion::class, 'enf_accion_id');
    }

    public function catalogo()
    {
        return $this->belongsTo(EnfCatalogo::class, 'enf_catalogo_id');
    }
}
