<?php

namespace App\Models\ENF;

use App\Models\Estado\TipoEstado;
use App\Models\Personal\Empleado;
use App\Models\Personal\FirmaSelloEmpleado;
use App\Models\Proyecto\CargoFirma;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnfFirma extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'enf_firmas';

    protected $guarded = [];

    protected $casts = [
        'fecha_firma' => 'datetime',
    ];

    public function accion()
    {
        return $this->belongsTo(EnfAccion::class, 'enf_accion_id');
    }

    public function documento()
    {
        return $this->belongsTo(EnfDocumento::class, 'enf_documento_id');
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
    }

    public function firmaSello()
    {
        return $this->belongsTo(FirmaSelloEmpleado::class, 'firma_sello_empleado_id');
    }

    public function cargoFirma()
    {
        return $this->belongsTo(CargoFirma::class, 'cargo_firma_id');
    }

    public function tipoEstado()
    {
        return $this->belongsTo(TipoEstado::class, 'tipo_estado_id');
    }
}
