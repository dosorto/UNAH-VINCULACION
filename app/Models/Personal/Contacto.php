<?php

namespace App\Models\Personal;

use Illuminate\Database\Eloquent\Model;

class Contacto extends Model
{
    protected $table = 'contactanos';

    protected $fillable = [
        'nombres',
        'apellidos',
        'institucion',
        'email',
        'telefono',
        'mensaje'
    ];

}
