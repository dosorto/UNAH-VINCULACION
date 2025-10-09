<?php

namespace App\Models\Proyecto;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AporteInstitucional extends Model
{
    use SoftDeletes;

    protected $table = 'aporte_institucional';

    protected $fillable = [
        'proyecto_id',
        'concepto',
        'unidad',
        'cantidad',
        'costo_unitario',
        'costo_total'
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'costo_unitario' => 'decimal:2',
        'costo_total' => 'decimal:2'
    ];

    protected $attributes = [
        'cantidad' => 0,
        'costo_unitario' => 0,
        'costo_total' => 0
    ];

    protected $appends = [
        'concepto_label',
        'unidad_label'
    ];

    public function getConceptoLabelAttribute(): string
    {
        return match($this->concepto) {
            'horas_trabajo_docentes' => 'a) Horas de trabajo docentes',
            'horas_trabajo_estudiantes' => 'b) Horas de trabajo estudiantes',
            'gastos_movilizacion' => 'c) Gastos de movilización',
            'utiles_materiales_oficina' => 'd) Útiles y materiales de oficina',
            'gastos_impresion' => 'e) Gastos de impresión',
            'costos_indirectos_infraestructura' => 'f) Costos indirectos por infraestructura universidad',
            'costos_indirectos_servicios' => 'g) Costos indirectos por servicios públicos',
            default => $this->concepto
        };
    }

    public function getUnidadLabelAttribute(): string
    {
        return match($this->unidad) {
            'hra_profes' => 'Hra/profes',
            'hra_estud' => 'Hra/estud',
            'global' => 'Global',
            'porcentaje' => '%',
            default => $this->unidad
        };
    }

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

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }
}
