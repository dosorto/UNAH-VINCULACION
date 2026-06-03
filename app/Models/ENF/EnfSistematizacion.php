<?php

namespace App\Models\ENF;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnfSistematizacion extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'enf_sistematizaciones';

    protected $guarded = [];

    public function accion()
    {
        return $this->belongsTo(EnfAccion::class, 'enf_accion_id');
    }

    public function informeFinal()
    {
        return $this->belongsTo(EnfInformeFinal::class, 'enf_informe_final_id');
    }

    public function documentos()
    {
        return $this->hasMany(EnfSistematizacionDocumento::class, 'enf_sistematizacion_id');
    }

    public function fases()
    {
        return $this->hasMany(EnfSistematizacionFase::class, 'enf_sistematizacion_id');
    }
}
