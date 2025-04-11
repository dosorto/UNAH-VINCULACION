<?php

namespace App\Models\Constancia;

use Illuminate\Database\Eloquent\Model;

class TipoConstancia extends Model
{
    protected $table = 'tipos_constancia';

    protected $fillable = ['nombre', 'descripcion'];

    public function constancias()
    {
        return $this->hasMany(Constancia::class);
    }
}