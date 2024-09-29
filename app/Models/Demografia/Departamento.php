<?php

namespace App\Models\Demografia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Departamento extends Model
{
    use HasFactory;

    protected $fillable = [
        'pais_id',
        'nombre',
        'codigo_departamento'
    ];

    protected $table = 'departamento';


    //

    public function pais()
    {
        return $this->belongsTo(Pais::class, 'pais_id');
    }
    
}
