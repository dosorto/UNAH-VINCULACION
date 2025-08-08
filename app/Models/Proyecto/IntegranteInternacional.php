<?php

namespace App\Models\Proyecto;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class IntegranteInternacional extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'integrante_internacional';

    protected $fillable = [
        'nombre_completo',
        'documento_identidad', // Pasaporte
        'sexo',
        'email',
        'pais',
        'institucion',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'nombre_completo',
                'email',
                'pais',
                'institucion'
            ]);
    }

    // Relaciones
    public function proyectos(): BelongsToMany
    {
        return $this->belongsToMany(
            Proyecto::class,
            'integrante_internacional_proyecto',
            'integrante_internacional_id',
            'proyecto_id'
        )->withPivot([
            'rol',
        ])->withTimestamps();
    }

    // Accessors
    public function getNombreCompletoConPaisAttribute(): string
    {
        return "{$this->nombre_completo} ({$this->pais})";
    }

    public function getInstitucionCompletaAttribute(): string
    {
        return $this->institucion;
    }
}
