<?php

namespace App\Models\Proyecto;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CargoFirma extends Model
{
    use HasFactory;

    protected $table = 'cargo_firma';

    protected $fillable = [
        'id',
        'nombre',
    ];
}
