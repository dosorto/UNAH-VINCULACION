<?php

namespace App\Models\Estudiante;

use Illuminate\Database\Eloquent\Model;
use App\Models\Proyecto\Proyecto;
use App\Models\Estudiante\EstudianteProyecto;
use App\Models\Estudiante\Estudiante;
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

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function getNombreFormateadoAttribute(): string
    {
        return ucfirst(strtolower($this->nombre));
    }
}