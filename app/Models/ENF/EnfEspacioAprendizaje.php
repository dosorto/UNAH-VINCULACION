<?php

namespace App\Models\ENF;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnfEspacioAprendizaje extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'enf_espacios_aprendizaje';

    protected $guarded = [];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function accion()
    {
        return $this->belongsTo(EnfAccion::class, 'enf_accion_id');
    }

    public function plataforma()
    {
        return $this->belongsTo(EnfCatalogo::class, 'plataforma_id');
    }
}
