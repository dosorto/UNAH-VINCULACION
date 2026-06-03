<?php

namespace App\Models\ENF;

use App\Models\Proyecto\EjesPrioritariosUnah;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnfAccionEjeUnah extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'enf_accion_ejes_unah';

    protected $guarded = [];

    public function accion()
    {
        return $this->belongsTo(EnfAccion::class, 'enf_accion_id');
    }

    public function ejeUnah()
    {
        return $this->belongsTo(EjesPrioritariosUnah::class, 'eje_prioritario_unah_id');
    }
}
