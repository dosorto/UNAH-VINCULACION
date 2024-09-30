<?php

namespace App\Models\Personal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empleado extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'fecha_contratacion',
        'salario',
        'supervisor',
        'jornada',
    ];

    protected $table = 'empleados';
}


