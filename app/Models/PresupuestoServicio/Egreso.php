<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Models\PresupuestoServicio\PresupuestoServicio;

class Egreso extends Model
{
    protected $table = 'egresos';

    protected $fillable = [
        'presupuesto_servicio_id',
        'descripcion',
        'unidad',
        'cantidad',
        'costo_unitario',
        'costo_total',
    ];

    protected $attributes = [
        'cantidad' => 0,
        'costo_unitario' => 0,
        'costo_total' => 0
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'costo_unitario' => 'decimal:2',
        'costo_total' => 'decimal:2'
    ];

    // Mutadores para convertir valores null a 0
    public function setCantidadAttribute($value)
    {
        $this->attributes['cantidad'] = $value ?? 0;
    }

    public function setCostoUnitarioAttribute($value)
    {
        $this->attributes['costo_unitario'] = $value ?? 0;
    }

    public function setCostoTotalAttribute($value)
    {
        $this->attributes['costo_total'] = $value ?? 0;
    }

    public function presupuesto(): BelongsTo
    {
        return $this->belongsTo(PresupuestoServicio::class, 'presupuesto_servicio_id');
    }
}
