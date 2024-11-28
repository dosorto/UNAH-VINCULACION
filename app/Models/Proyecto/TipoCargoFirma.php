<?php

namespace App\Models\Proyecto;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoCargoFirma extends Model
{
    use HasFactory;


    protected $table = 'tipo_cargo_firma';

    protected $fillable = [
        'id',
        'nombre',
    ];

}
