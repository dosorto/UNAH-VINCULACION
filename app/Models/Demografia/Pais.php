<?php

namespace App\Models\Demografia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pais extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'codigo_area',
        'codigo_iso',
        'codigo_iso_numerico',
        'codigo_iso_alpha_2',
        'nombre',
        'gentilicio',
    ];

    
    protected $table = 'pais';
}
