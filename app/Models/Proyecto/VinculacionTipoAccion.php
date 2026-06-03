<?php

namespace App\Models\Proyecto;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VinculacionTipoAccion extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'vinculacion_tipos_accion';

    protected $guarded = [];

    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer',
    ];
}
