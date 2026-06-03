<?php

namespace App\Models\ENF;

use App\Models\Personal\Empleado;
use App\Models\Proyecto\IntegranteInternacional;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnfEquipo extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'enf_equipo';

    protected $guarded = [];

    protected $casts = [
        'es_coordinador' => 'boolean',
        'horas_dedicadas' => 'integer',
    ];

    public function accion()
    {
        return $this->belongsTo(EnfAccion::class, 'enf_accion_id');
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function integranteInternacional()
    {
        return $this->belongsTo(IntegranteInternacional::class, 'integrante_internacional_id');
    }
}
