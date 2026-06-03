<?php

namespace App\Models\ENF;

use App\Models\Personal\Empleado;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnfInformeFinal extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'enf_informes_finales';

    protected $guarded = [];

    protected $casts = [
        'fecha_presentacion' => 'date',
        'fecha_aprobacion' => 'date',
    ];

    public function accion()
    {
        return $this->belongsTo(EnfAccion::class, 'enf_accion_id');
    }

    public function aprobadoPor()
    {
        return $this->belongsTo(Empleado::class, 'aprobado_por_empleado_id');
    }

    public function participantesFinales()
    {
        return $this->hasMany(EnfParticipanteFinal::class, 'enf_informe_final_id');
    }

    public function accionesEjecutadas()
    {
        return $this->hasMany(EnfAccionEjecutada::class, 'enf_informe_final_id');
    }

    public function accionesNoEjecutadas()
    {
        return $this->hasMany(EnfAccionNoEjecutada::class, 'enf_informe_final_id');
    }
}
