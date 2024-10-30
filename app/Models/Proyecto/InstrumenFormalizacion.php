<?php

namespace App\Models\Proyecto;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstrumenFormalizacion extends Model
{
    //
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'id',
        'entidad_contraparte_id',
        'documento_url',
    ];

    public function entidadContraparte()
    {
        return $this->belongsTo(EntidadContraparte::class, 'entidad_contraparte_id');
    }

    protected $table = 'instrumento_formalizacion';
}
