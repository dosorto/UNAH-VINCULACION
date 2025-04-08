<?php

namespace App\Models\Personal;

class Docente extends Empleado
{
    protected static function booted()
    {
        static::addGlobalScope('docentes', function ($builder) {
            $builder->where('tipo_empleado', 'docente');
        });
    }
}
