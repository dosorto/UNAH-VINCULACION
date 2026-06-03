<?php

namespace App\Models\ENF;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnfSistematizacionDocumento extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'enf_sistematizacion_documentos';

    protected $guarded = [];

    public function sistematizacion()
    {
        return $this->belongsTo(EnfSistematizacion::class, 'enf_sistematizacion_id');
    }
}
