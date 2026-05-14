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

    public static function defaultForProyectos(): ?self
    {
        return self::query()
            ->where('proceso', 'PROYECTO')
            ->where('activo', true)
            ->orderBy('id')
            ->first();
    }
}
