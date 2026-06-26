<?php

namespace App\Models\Proyecto;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FlujoAprobacion extends Model
{
    use HasFactory;

    protected $table = 'flujos_aprobacion';

    protected $fillable = [
        'codigo',
        'nombre',
        'proceso',
        'tipo_accion_id',
        'tipo_programa_id',
        'codigo_formulario',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function etapas(): HasMany
    {
        return $this->hasMany(FlujoAprobacionEtapa::class, 'flujo_aprobacion_id')
            ->orderBy('orden');
    }

    public function tipoPrograma()
    {
        return $this->belongsTo(\App\Models\SGCU\TipoPrograma::class, 'tipo_programa_id');
    }

    public function tipoAccion()
    {
        return $this->belongsTo(VinculacionTipoAccion::class, 'tipo_accion_id');
    }

    public static function defaultForProyectos(?int $tipoAccionId = null, ?string $codigoFormulario = null): ?self
    {
        return self::query()
            ->where('proceso', 'PROYECTO')
            ->where('activo', true)
            ->when($tipoAccionId, fn ($query) => $query->where('tipo_accion_id', $tipoAccionId))
            ->when($codigoFormulario, fn ($query) => $query->where('codigo_formulario', $codigoFormulario))
            ->orderBy('id')
            ->first();
    }
}
