<?php

namespace App\Models\ENF;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnfObjetivoEspecifico extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'enf_objetivos_especificos';

    protected $guarded = [];

    public function accion()
    {
        return $this->belongsTo(EnfAccion::class, 'enf_accion_id');
    }

    public function resultados()
    {
        return $this->hasMany(EnfResultado::class, 'enf_objetivo_especifico_id');
    }
}
