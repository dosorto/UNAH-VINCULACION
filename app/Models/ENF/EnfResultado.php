<?php

namespace App\Models\ENF;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnfResultado extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'enf_resultados';

    protected $guarded = [];

    public function accion()
    {
        return $this->belongsTo(EnfAccion::class, 'enf_accion_id');
    }

    public function objetivoEspecifico()
    {
        return $this->belongsTo(EnfObjetivoEspecifico::class, 'enf_objetivo_especifico_id');
    }
}
