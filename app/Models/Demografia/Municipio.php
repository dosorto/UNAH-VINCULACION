<?php

namespace App\Models\Demografia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Municipio extends Model
{
    use HasFactory;

    protected $fillable = [
        'departamento_id',
        'nombre',
        'codigo_municipio'
    ];

    protected $table = 'municipio';

    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'departamento_id');
    }
}
