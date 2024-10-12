<?php

namespace App\Models\Demografia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Aldea extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'municipio_id',
        'nombre'
    ];

    protected $table = 'aldea';

    public function municipio()
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }
}
