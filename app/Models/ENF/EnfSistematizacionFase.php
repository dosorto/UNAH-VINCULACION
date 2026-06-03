<?php

namespace App\Models\ENF;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnfSistematizacionFase extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'enf_sistematizacion_fases';

    protected $guarded = [];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_finalizacion' => 'date',
        'orden' => 'integer',
    ];

    public function sistematizacion()
    {
        return $this->belongsTo(EnfSistematizacion::class, 'enf_sistematizacion_id');
    }
}
