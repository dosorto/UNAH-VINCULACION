<?php

namespace App\Models\Proyecto;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Superavit extends Model
{
    use HasFactory;


    protected $table = 'superavit_proyecto';

    protected $fillable = [
        'id',
        'proyecto_id',
        'inversion',
        'monto',
    ];
}
