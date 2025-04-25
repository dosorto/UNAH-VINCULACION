<?php

namespace App\Traits;

use App\Models\Estado\EstadoProyecto;
use Illuminate\Support\Str;

trait EstadoTrait
{
    public function estados()
    {
        return $this->morphMany(EstadoProyecto::class, 'estadoable');
    }


    // 
    public function getEstadoAttribute()
    {
        return $this->estados()
            ->where('es_actual', true)
            ->first();
    }
}
