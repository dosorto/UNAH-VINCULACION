<?php

namespace App\Models\PresupuestoServicio;

use App\Models\ServicioInfraestructura\ServicioTecnologico;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PresupuestoServicio extends Model
{
    protected $table = 'presupuesto_servicio';

    protected $fillable = [
        'servicio_tecnologico_id',
        'descripcion_excedente',
        'total_ingresos',
        'total_egresos',
        'excedente',
        'total_aporte_unah',
    ];

    public function ingresos(): HasMany
    {
        return $this->hasMany(Ingreso::class, 'presupuesto_servicio_id');
    }

    public function egresos(): HasMany
    {
        return $this->hasMany(Egreso::class, 'presupuesto_servicio_id');
    }

    public function aportesUnah(): HasMany
    {
        return $this->hasMany(AporteUnah::class, 'presupuesto_servicio_id');
    }

    public function servicio()
    {
        return $this->belongsTo(ServicioTecnologico::class, 'servicio_tecnologico_id');
    }
}
