<?php

namespace App\Models\Demografia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Departamento extends Model
{
    use HasFactory;
    use SoftDeletes;

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
