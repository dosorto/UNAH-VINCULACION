<?php

namespace App\Models\UnidadAcademica;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campus extends Model
{
    use HasFactory;

    protected $table = 'campus';

    protected $fillable = [
        'nombre_campus',
        'direccion',
        'telefono',
        'url'
    ];

}
