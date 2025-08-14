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
        'motivo_baja',
        'usuario_baja_id',
        'rol_anterior',
    ];

    protected $casts = [
        'fecha_baja' => 'datetime',
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
}
