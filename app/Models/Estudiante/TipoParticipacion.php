<?php

namespace App\Models\Estudiante;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TipoParticipacion extends Model
{
    use HasFactory;
    protected $table = 'tipo_participacion';
    protected $fillable = [
        'nombre',
        'descripcion',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function participaciones(): HasMany
    {
        return $this->hasMany(EstudianteProyecto::class, 'tipo_participacion_id');
    }

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    public function getNombreFormateadoAttribute(): string
    {
        return ucfirst(strtolower($this->nombre));
    }
}