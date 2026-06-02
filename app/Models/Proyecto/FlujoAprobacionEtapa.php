<?php

namespace App\Models\Proyecto;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role;

class FlujoAprobacionEtapa extends Model
{
    use HasFactory;

    protected $table = 'flujos_aprobacion_etapas';

    protected $fillable = [
        'flujo_aprobacion_id',
        'orden',
        'codigo',
        'aplica_inscripcion',
        'aplica_informe_intermedio',
        'aplica_cierre_proyecto',
        'nombre',
        'tipo_etapa',
        'rol_revisor_id',
        'usuario_responsable_id',
        'cargo_firma_id',
        'requiere_asignacion',
        'emisor_define_destinatario',
        'activo',
        'estado_resultante',
        'permite_edicion',
        'permite_rechazo',
        'es_estado_final_aprobado',
    ];

    protected $casts = [
        'aplica_inscripcion' => 'boolean',
        'aplica_informe_intermedio' => 'boolean',
        'aplica_cierre_proyecto' => 'boolean',
        'requiere_asignacion' => 'boolean',
        'emisor_define_destinatario' => 'boolean',
        'activo' => 'boolean',
        'permite_edicion' => 'boolean',
        'permite_rechazo' => 'boolean',
        'es_estado_final_aprobado' => 'boolean',
    ];

    public function flujo(): BelongsTo
    {
        return $this->belongsTo(FlujoAprobacion::class, 'flujo_aprobacion_id');
    }

    public function cargoFirma(): BelongsTo
    {
        return $this->belongsTo(CargoFirma::class, 'cargo_firma_id');
    }

    public function rolRevisor(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'rol_revisor_id');
    }

    public function usuarioResponsable(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'usuario_responsable_id');
    }
}
