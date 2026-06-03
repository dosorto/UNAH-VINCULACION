<?php

namespace App\Models\ENF;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnfCatalogo extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'enf_catalogos';

    protected $guarded = [];

    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    public function acciones()
    {
        return $this->belongsToMany(EnfAccion::class, 'enf_accion_catalogo', 'enf_catalogo_id', 'enf_accion_id')
            ->withPivot(['tipo', 'valor_texto'])
            ->withTimestamps();
    }
}
