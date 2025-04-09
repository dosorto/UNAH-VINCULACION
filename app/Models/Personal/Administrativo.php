<?php


namespace App\Models\Personal;

class Administrativo extends Empleado
{
    protected static function booted()
    {
        static::addGlobalScope('administrativos', function ($builder) {
            $builder->where('tipo_empleado', 'administrativo');
        });
    }
}
