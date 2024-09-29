<?php

namespace App\Models\Demografia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ciudad extends Model
{
    use HasFactory;
    protected $fillable = [
        'municipio_id',
        'nombre',
        'codigo_postal'
    ];

    protected $table = 'ciudad';

    public function municipio()
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }
}
