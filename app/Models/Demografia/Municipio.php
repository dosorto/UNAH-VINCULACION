<?php

namespace App\Models\Demografia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Municipio extends Model
{
    use HasFactory;
    use SoftDeletes;

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
