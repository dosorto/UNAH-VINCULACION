<?php

namespace App\Models\Proyecto;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlujoAprobacionEtapa extends Model
{
    use HasFactory;

    protected $table = 'flujos_aprobacion_etapas';

    protected $fillable = [
        'flujo_aprobacion_id',
        'orden',
        'codigo',
        'nombre',
        'cargo_firma_id',
        'requiere_asignacion',
        'emisor_define_destinatario',
        'activo',
    ];

    protected $casts = [
        'requiere_asignacion' => 'boolean',
        'emisor_define_destinatario' => 'boolean',
        'activo' => 'boolean',
    ];

    public function flujo(): BelongsTo
    {
        return $this->belongsTo(FlujoAprobacion::class, 'flujo_aprobacion_id');
    }

    public function cargoFirma(): BelongsTo
    {
        return $this->belongsTo(CargoFirma::class, 'cargo_firma_id');
    }
}
