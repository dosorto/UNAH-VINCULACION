<?php

namespace App\Models\ENF;

use App\Models\Personal\Empleado;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnfCronograma extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'enf_cronograma';

    protected $guarded = [];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_finalizacion' => 'date',
        'porcentaje_avance' => 'integer',
    ];

    public function accion()
    {
        return $this->belongsTo(EnfAccion::class, 'enf_accion_id');
    }

    public function responsable()
    {
        return $this->belongsTo(Empleado::class, 'responsable_empleado_id');
    }
}
