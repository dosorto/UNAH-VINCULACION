<?php
namespace App\Models\Proyecto;

use Illuminate\Database\Eloquent\Model;
use App\Models\Personal\Empleado;
use App\Models\Estudiante\Estudiante;

class EquipoEjecutorBaja extends Model
{
    protected $fillable = [
        'proyecto_id',
        'tipo_integrante',
        'integrante_id',
        'fecha_baja',
        'fecha_cancelacion',
        'motivo_cancelacion',
        'motivo_baja',
        'usuario_baja_id',
        'rol_anterior',
        'estado_baja',
        'ficha_actualizacion_id',
    ];

    protected $casts = [
        'fecha_baja' => 'datetime',
        'fecha_cancelacion' => 'datetime',
    ];

    // Relación con el proyecto
    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id');
    }

    // Relación con empleado (cuando tipo_integrante es 'empleado')
    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'integrante_id');
    }

    // Relación con estudiante (cuando tipo_integrante es 'estudiante')
    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'integrante_id');
    }

    // Relación con integrante internacional (cuando tipo_integrante es 'integrante_internacional')
    public function integranteInternacional()
    {
        return $this->belongsTo(IntegranteInternacional::class, 'integrante_id');
    }

    // Relación con el usuario que realizó la baja
    public function usuarioBaja()
    {
        return $this->belongsTo(\App\Models\User::class, 'usuario_baja_id');
    }

    // Relación con la ficha de actualización
    public function fichaActualizacion()
    {
        return $this->belongsTo(FichaActualizacion::class, 'ficha_actualizacion_id');
    }

    // Método para obtener el nombre del integrante según el tipo
    public function getNombreIntegranteAttribute()
    {
        switch ($this->tipo_integrante) {
            case 'empleado':
                return $this->empleado?->nombre_completo ?? 'N/A';
            case 'estudiante':
                return ($this->estudiante?->nombre ?? '') . ' ' . ($this->estudiante?->apellido ?? '');
            case 'integrante_internacional':
                return $this->integranteInternacional?->nombre_completo ?? 'N/A';
            default:
                return 'N/A';
        }
    }

    // Scopes para filtrar por estado
    public function scopePendientes($query)
    {
        return $query->where('estado_baja', 'pendiente');
    }

    public function scopeAplicadas($query)
    {
        return $query->where('estado_baja', 'aplicada');
    }

    public function scopePorFicha($query, $fichaId)
    {
        return $query->where('ficha_actualizacion_id', $fichaId);
    }

    // Método para marcar la baja como aplicada
    public function aplicarBaja()
    {
        $this->update(['estado_baja' => 'aplicada']);
        
        // Aquí es donde realmente eliminamos al integrante del equipo ejecutor
        $this->eliminarDelEquipoEjecutor();
    }

    // Método para eliminar al integrante del equipo ejecutor
    private function eliminarDelEquipoEjecutor()
    {
        switch ($this->tipo_integrante) {
            case 'empleado':
                \App\Models\Personal\EmpleadoProyecto::where('proyecto_id', $this->proyecto_id)
                    ->where('empleado_id', $this->integrante_id)
                    ->delete();
                break;
                
            case 'estudiante':
                \App\Models\Estudiante\EstudianteProyecto::where('proyecto_id', $this->proyecto_id)
                    ->where('estudiante_id', $this->integrante_id)
                    ->delete();
                break;
                
            case 'integrante_internacional':
                \App\Models\Proyecto\IntegranteInternacionalProyecto::where('proyecto_id', $this->proyecto_id)
                    ->where('integrante_internacional_id', $this->integrante_id)
                    ->delete();
                break;
        }
    }
}
