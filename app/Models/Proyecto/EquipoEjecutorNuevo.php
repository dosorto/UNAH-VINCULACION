<?php

namespace App\Models\Proyecto;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipoEjecutorNuevo extends Model
{
    protected $table = 'equipo_ejecutor_nuevos';
    
    protected $fillable = [
        'proyecto_id',
        'integrante_id',
        'tipo_integrante',
        'nombre_integrante',
        'rol_nuevo',
        'motivo_incorporacion',
        'fecha_solicitud',
        'usuario_solicitud_id',
        'estado_incorporacion',
        'ficha_actualizacion_id',
    ];

    protected $casts = [
        'fecha_solicitud' => 'datetime',
        'estado_incorporacion' => 'string',
        'tipo_integrante' => 'string',
    ];

    // Relaciones
    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function usuarioSolicitud(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'usuario_solicitud_id');
    }

    public function fichaActualizacion(): BelongsTo
    {
        return $this->belongsTo(FichaActualizacion::class);
    }

    // Relaciones polimórficas según el tipo
    public function empleado(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Personal\Empleado::class, 'integrante_id');
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Estudiante\Estudiante::class, 'integrante_id');
    }

    public function integranteInternacional(): BelongsTo
    {
        return $this->belongsTo(IntegranteInternacional::class, 'integrante_id');
    }

    // Scopes
    public function scopePendientes($query)
    {
        return $query->where('estado_incorporacion', 'pendiente');
    }

    public function scopeAplicados($query)
    {
        return $query->where('estado_incorporacion', 'aplicado');
    }

    // Métodos
    public function aplicarIncorporacion()
    {
        switch ($this->tipo_integrante) {
            case 'empleado':
                \App\Models\Personal\EmpleadoProyecto::create([
                    'proyecto_id' => $this->proyecto_id,
                    'empleado_id' => $this->integrante_id,
                    'rol' => $this->rol_nuevo,
                ]);
                break;
            
            case 'estudiante':
                \App\Models\Estudiante\EstudianteProyecto::create([
                    'proyecto_id' => $this->proyecto_id,
                    'estudiante_id' => $this->integrante_id,
                    'tipo_participacion_estudiante' => $this->rol_nuevo,
                ]);
                break;
            
            case 'integrante_internacional':
                IntegranteInternacionalProyecto::create([
                    'proyecto_id' => $this->proyecto_id,
                    'integrante_internacional_id' => $this->integrante_id,
                    'rol' => $this->rol_nuevo,
                ]);
                break;
        }

        $this->update(['estado_incorporacion' => 'aplicado']);
    }
}
