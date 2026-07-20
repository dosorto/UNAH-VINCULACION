<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IntegracionApi extends Model
{
    use SoftDeletes;

    protected $table = 'integraciones_api';
    protected $guarded = ['id'];
    protected $casts = [
        'token_encriptado' => 'encrypted', 'usuario_api_encriptado' => 'encrypted', 'password_api_encriptado' => 'encrypted',
        'headers_json' => 'encrypted:array', 'mapeo_campos_json' => 'array', 'activo' => 'boolean',
        'ultima_prueba_at' => 'datetime',
    ];
}
