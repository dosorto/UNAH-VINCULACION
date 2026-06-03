<?php

namespace App\Models\ENF;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnfPresupuestoDetalle extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'enf_presupuesto_detalles';

    protected $guarded = [];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'costo_unitario' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function presupuesto()
    {
        return $this->belongsTo(EnfPresupuesto::class, 'enf_presupuesto_id');
    }
}
