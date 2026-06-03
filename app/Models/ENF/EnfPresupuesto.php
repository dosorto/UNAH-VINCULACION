<?php

namespace App\Models\ENF;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnfPresupuesto extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'enf_presupuestos';

    protected $guarded = [];

    protected $casts = [
        'monto_solicitado' => 'decimal:2',
        'monto_aprobado' => 'decimal:2',
        'monto_ejecutado' => 'decimal:2',
    ];

    public function accion()
    {
        return $this->belongsTo(EnfAccion::class, 'enf_accion_id');
    }

    public function detalles()
    {
        return $this->hasMany(EnfPresupuestoDetalle::class, 'enf_presupuesto_id');
    }
}
