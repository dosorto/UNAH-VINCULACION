<?php

namespace App\Models\InformeFinal;

use App\Models\Proyecto\Proyecto;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class InformeFinalProyecto extends Model
{
    use SoftDeletes;

    protected $table = 'informe_final_proyectos';

    protected $guarded = ['id'];

    protected $casts = [
        'fecha_registro' => 'date',
        'fecha_inicio' => 'date',
        'fecha_finalizacion' => 'date',
        'fecha_cierre' => 'date',
        'confirmacion_veracidad' => 'boolean',
        'presupuesto_planificado' => 'decimal:2',
        'aporte_beneficiarios' => 'decimal:2',
        'otros_aportes' => 'decimal:2',
    ];

    public function proyecto(): BelongsTo { return $this->belongsTo(Proyecto::class); }
    public function creador(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function actualizador(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
    public function beneficiarios(): HasOne { return $this->hasOne(InformeFinalBeneficiario::class); }
    public function equipoDocente(): HasMany { return $this->hasMany(InformeFinalEquipoDocente::class); }
    public function cooperacion(): HasMany { return $this->hasMany(InformeFinalCooperacion::class); }
    public function estudiantes(): HasMany { return $this->hasMany(InformeFinalEstudiante::class); }
    public function voluntarios(): HasMany { return $this->hasMany(InformeFinalVoluntario::class); }
    public function contrapartes(): HasMany { return $this->hasMany(InformeFinalContraparte::class); }
    public function resultados(): HasMany { return $this->hasMany(InformeFinalResultado::class); }
    public function actividades(): HasMany { return $this->hasMany(InformeFinalActividad::class); }
    public function accionesNoEjecutadas(): HasMany { return $this->hasMany(InformeFinalAccionNoEjecutada::class); }
    public function accionesEmergentes(): HasMany { return $this->hasMany(InformeFinalAccionEmergente::class); }
    public function ods(): HasMany { return $this->hasMany(InformeFinalOds::class); }
    public function presupuestoDetalles(): HasMany { return $this->hasMany(InformeFinalPresupuestoDetalle::class); }
    public function anexos(): HasMany { return $this->hasMany(InformeFinalAnexo::class); }

    public function scopePorUnidadAcademica(Builder $query, int $id): Builder { return $query->where('centro_facultad_id', $id); }
    public function scopePorModalidad(Builder $query, int $id): Builder { return $query->where('modalidad_id', $id); }
    public function scopePorCategoria(Builder $query, int $id): Builder { return $query->where('categoria_id', $id); }
    public function scopePorDepartamentoTerritorial(Builder $query, int $id): Builder { return $query->where('departamento_territorial_id', $id); }
    public function scopePorOds(Builder $query, int $id): Builder { return $query->whereHas('ods', fn (Builder $q) => $q->where('ods_id', $id)); }
    public function scopePorAnioCierre(Builder $query, int $anio): Builder { return $query->whereYear('fecha_cierre', $anio); }
    public function scopeCompletos(Builder $query): Builder { return $query->where('estado', 'COMPLETO'); }

    public function getDuracionSemanasAttribute(): int
    {
        return $this->fecha_inicio && $this->fecha_finalizacion
            ? (int) ceil($this->fecha_inicio->diffInDays($this->fecha_finalizacion) / 7)
            : 0;
    }

    public function getTotalUnahAttribute(): float
    {
        return round($this->subtotal_unah_base + $this->infraestructura_unah + $this->servicios_unah, 2);
    }

    public function getSubtotalUnahBaseAttribute(): float
    {
        return (float) $this->presupuestoDetalles->where('fuente', 'UNAH')->reject(fn ($fila) => str_contains(mb_strtolower($fila->concepto), 'infraestructura') || str_contains(mb_strtolower($fila->concepto), 'servicio'))->sum(fn ($fila) => $fila->costo_total);
    }

    public function getInfraestructuraUnahAttribute(): float
    {
        $rows = $this->presupuestoDetalles->where('fuente', 'UNAH')->filter(fn ($fila) => str_contains(mb_strtolower($fila->concepto), 'infraestructura'));
        return round($rows->isNotEmpty() ? $rows->sum(fn ($fila) => $fila->costo_total) : $this->subtotal_unah_base * .03, 2);
    }

    public function getServiciosUnahAttribute(): float
    {
        $rows = $this->presupuestoDetalles->where('fuente', 'UNAH')->filter(fn ($fila) => str_contains(mb_strtolower($fila->concepto), 'servicio'));
        return round($rows->isNotEmpty() ? $rows->sum(fn ($fila) => $fila->costo_total) : $this->subtotal_unah_base * .03, 2);
    }

    public function getTotalContraparteAttribute(): float
    {
        return (float) $this->presupuestoDetalles->where('fuente', 'CONTRAPARTE')->sum(fn ($fila) => $fila->costo_total);
    }

    public function getEjecucionTotalAttribute(): float
    {
        return $this->total_unah + $this->total_contraparte + (float) $this->aporte_beneficiarios + (float) $this->otros_aportes;
    }

    public function getPorcentajeEjecucionAttribute(): float
    {
        return (float) $this->presupuesto_planificado > 0
            ? round($this->ejecucion_total / (float) $this->presupuesto_planificado * 100, 2)
            : 0;
    }
}
