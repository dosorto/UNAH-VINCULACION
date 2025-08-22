<?php

namespace App\Models\PresupuestoServicio;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Models\PresupuestoServicio\PresupuestoServicio;

class AporteUnah extends Model
{
    protected $table = 'aportes_unah';

    protected $fillable = [
        'presupuesto_servicio_id',
        'descripcion',
        'unidad',
        'cantidad',
        'costo_unitario',
        'costo_total',
    ];

    public function presupuesto(): BelongsTo
    {
        return $this->belongsTo(PresupuestoServicio::class, 'presupuesto_servicio_id');
    }
}
