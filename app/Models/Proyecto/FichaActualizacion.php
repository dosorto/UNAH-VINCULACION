<?php

namespace App\Models\Proyecto;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FichaActualizacion extends Model
{
    use HasFactory;

    protected $table = 'ficha_actualizacion';

    protected $fillable = [
        'fecha_registro',
        'empleado_id',
        'proyecto_id',
        'integrantes_baja',
        'motivo_baja',
        'fecha_baja',
        'fecha_ampliacion',
        'fecha_finalizacion_actual',
        'motivo_ampliacion',
        'motivo_responsabilidades_nuevos',
        'motivo_razones_cambio',
    ];

    protected $casts = [
        'integrantes_baja' => 'array',
        'fecha_registro' => 'datetime',
        'fecha_baja' => 'date',
        'fecha_ampliacion' => 'date',
        'fecha_finalizacion_actual' => 'date',
    ];

    public function empleado()
    {
        return $this->belongsTo(\App\Models\Personal\Empleado::class, 'empleado_id');
    }

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id');
    }

    // Relación con las bajas del equipo ejecutor asociadas a esta ficha
    public function equipoEjecutorBajas()
    {
        return $this->hasMany(EquipoEjecutorBaja::class, 'ficha_actualizacion_id');
    }

    // Relación con los nuevos integrantes asociados a esta ficha
    public function equipoEjecutorNuevos()
    {
        return $this->hasMany(EquipoEjecutorNuevo::class, 'ficha_actualizacion_id');
    }

    // Relación morfológica con estados
    public function estado_proyecto()
    {
        return $this->morphMany(\App\Models\Estado\EstadoProyecto::class, 'estadoable');
    }

    // Relación morfológica con firmas
    public function firma_proyecto()
    {
        return $this->morphMany(FirmaProyecto::class, 'firmable');
    }

    // Obtener el último estado
    public function obtenerUltimoEstado()
    {
        return $this->estado_proyecto()->orderBy('created_at', 'desc')->first();
    }

    // Obtener el estado actual
    public function estado()
    {
        return $this->hasOneThrough(
            \App\Models\Estado\EstadoProyecto::class,
            FichaActualizacion::class,
            'id',
            'estadoable_id',
            'id',
            'id'
        )->where('estadoable_type', FichaActualizacion::class)
         ->where('es_actual', true)
         ->latest('fecha');
    }

    // Relaciones específicas de firmas para fichas de actualización
    public function firma_coordinador_proyecto()
    {
        return $this->morphMany(FirmaProyecto::class, 'firmable')
            ->join('cargo_firma', 'firma_proyecto.cargo_firma_id', '=', 'cargo_firma.id')
            ->join('tipo_cargo_firma', 'cargo_firma.tipo_cargo_firma_id', '=', 'tipo_cargo_firma.id')
            ->where('tipo_cargo_firma.nombre', 'Coordinador Proyecto')
            ->where('cargo_firma.descripcion', 'Ficha_actualizacion');
    }

    public function firma_enlace_vinculacion()
    {
        return $this->morphMany(FirmaProyecto::class, 'firmable')
            ->join('cargo_firma', 'firma_proyecto.cargo_firma_id', '=', 'cargo_firma.id')
            ->join('tipo_cargo_firma', 'cargo_firma.tipo_cargo_firma_id', '=', 'tipo_cargo_firma.id')
            ->where('tipo_cargo_firma.nombre', 'Enlace Vinculacion')
            ->where('cargo_firma.descripcion', 'Ficha_actualizacion');
    }

    public function firma_jefe_departamento()
    {
        return $this->morphMany(FirmaProyecto::class, 'firmable')
            ->join('cargo_firma', 'firma_proyecto.cargo_firma_id', '=', 'cargo_firma.id')
            ->join('tipo_cargo_firma', 'cargo_firma.tipo_cargo_firma_id', '=', 'tipo_cargo_firma.id')
            ->where('tipo_cargo_firma.nombre', 'Jefe Departamento')
            ->where('cargo_firma.descripcion', 'Ficha_actualizacion');
    }

    public function firma_director_centro()
    {
        return $this->morphMany(FirmaProyecto::class, 'firmable')
            ->join('cargo_firma', 'firma_proyecto.cargo_firma_id', '=', 'cargo_firma.id')
            ->join('tipo_cargo_firma', 'cargo_firma.tipo_cargo_firma_id', '=', 'tipo_cargo_firma.id')
            ->where('tipo_cargo_firma.nombre', 'Director centro')
            ->where('cargo_firma.descripcion', 'Ficha_actualizacion');
    }

    public function firma_revisor_vinculacion()
    {
        return $this->morphMany(FirmaProyecto::class, 'firmable')
            ->join('cargo_firma', 'firma_proyecto.cargo_firma_id', '=', 'cargo_firma.id')
            ->join('tipo_cargo_firma', 'cargo_firma.tipo_cargo_firma_id', '=', 'tipo_cargo_firma.id')
            ->where('tipo_cargo_firma.nombre', 'Revisor Vinculacion')
            ->where('cargo_firma.descripcion', 'Ficha_actualizacion');
    }

    // Método para procesar las bajas pendientes cuando la ficha sea aprobada
    public function procesarBajasPendientes()
    {
        $bajasPendientes = $this->equipoEjecutorBajas()->pendientes()->get();
        
        foreach ($bajasPendientes as $baja) {
            $baja->aplicarBaja();
        }
        
        return $bajasPendientes->count();
    }

    // Método para procesar los integrantes nuevos cuando la ficha sea aprobada
    public function procesarIntegrantesNuevos()
    {
        $integrantesNuevos = $this->equipoEjecutorNuevos()->pendientes()->get();
        
        foreach ($integrantesNuevos as $nuevo) {
            $nuevo->aplicarIncorporacion();
        }
        
        return $integrantesNuevos->count();
    }

    // Método para verificar si tiene bajas pendientes
    public function tieneBajasPendientes()
    {
        return $this->equipoEjecutorBajas()->pendientes()->exists();
    }

    // Método para verificar si tiene integrantes nuevos pendientes
    public function tieneIntegrantesNuevosPendientes()
    {
        return $this->equipoEjecutorNuevos()->pendientes()->exists();
    }

    // Método para verificar si todas las firmas están aprobadas
    public function todasLasFirmasAprobadas()
    {
        $firmasTotal = $this->firma_proyecto()->count();
        $firmasAprobadas = $this->firma_proyecto()->where('estado_revision', 'Aprobado')->count();
        
        return $firmasTotal > 0 && $firmasTotal === $firmasAprobadas;
    }

    // Método para verificar si el estado actual es "Actualización realizada"
    public function esActualizacionRealizada()
    {
        $ultimoEstado = $this->obtenerUltimoEstado();
        return $ultimoEstado && $ultimoEstado->tipoestado->nombre === 'Actualizacion realizada';
    }

    // Método para verificar si la ficha puede ser eliminada
    public function puedeSerEliminada()
    {
        // Obtener todas las firmas aprobadas
        $firmasAprobadas = $this->firma_proyecto()
            ->where('estado_revision', 'Aprobado')
            ->with('cargo_firma.tipoCargoFirma')
            ->get();
        
        // Si no tiene ninguna firma aprobada, puede ser eliminada
        if ($firmasAprobadas->count() === 0) {
            return true;
        }
        
        // Si solo tiene la firma del coordinador del proyecto aprobada, puede ser eliminada
        if ($firmasAprobadas->count() === 1) {
            $primeraFirma = $firmasAprobadas->first();
            if ($primeraFirma->cargo_firma->tipoCargoFirma->nombre === 'Coordinador Proyecto') {
                return true;
            }
        }
        
        // Si tiene más firmas o firmas de otros tipos, no puede ser eliminada
        return false;
    }

    // Método para eliminar la ficha de forma segura
    public function eliminarFichaSiEsSeguro()
    {
        if (!$this->puedeSerEliminada()) {
            return [
                'eliminada' => false,
                'razon' => 'La ficha no puede ser eliminada porque ya tiene firmas aprobadas'
            ];
        }

        try {
            // Eliminar solicitudes de nuevos integrantes asociadas
            $this->equipoEjecutorNuevos()->delete();
            
            // Eliminar solicitudes de bajas asociadas
            $this->equipoEjecutorBajas()->delete();
            
            // Eliminar firmas asociadas
            $this->firma_proyecto()->delete();
            
            // Eliminar estados asociados
            $this->estado_proyecto()->delete();
            
            // Eliminar la ficha
            $this->delete();
            
            return [
                'eliminada' => true,
                'mensaje' => 'Ficha de actualización eliminada exitosamente'
            ];
        } catch (\Exception $e) {
            return [
                'eliminada' => false,
                'razon' => 'Error al eliminar la ficha: ' . $e->getMessage()
            ];
        }
    }

    // Método para aplicar la nueva fecha de finalización al proyecto
    public function aplicarNuevaFechaFinalizacion()
    {
        if ($this->fecha_ampliacion && $this->proyecto) {
            $fechaAnterior = $this->proyecto->fecha_finalizacion;
            
            // Actualizar la fecha de finalización del proyecto
            $this->proyecto->update([
                'fecha_finalizacion' => $this->fecha_ampliacion
            ]);
            
            return [
                'fecha_anterior' => $fechaAnterior,
                'fecha_nueva' => $this->fecha_ampliacion,
                'fecha_guardada_en_ficha' => $this->fecha_finalizacion_actual, // Fecha que se guardó al crear la ficha
                'actualizada' => true
            ];
        }
        
        return [
            'actualizada' => false,
            'razon' => 'No hay nueva fecha de finalización especificada o proyecto no encontrado'
        ];
    }

    // Método para cancelar solicitudes cuando la ficha es rechazada
    public function cancelarSolicitudesPorRechazo()
    {
        try {
            // Cancelar solicitudes de nuevos integrantes pendientes
            $nuevosIntegrantesCancelados = $this->equipoEjecutorNuevos()
                ->where('estado_incorporacion', 'pendiente')
                ->update([
                    'estado_incorporacion' => 'cancelada',
                    'fecha_cancelacion' => now(),
                    'motivo_cancelacion' => 'Ficha de actualización rechazada'
                ]);

            // Cancelar solicitudes de bajas pendientes
            $bajasCanceladas = $this->equipoEjecutorBajas()
                ->where('estado_baja', 'pendiente')
                ->update([
                    'estado_baja' => 'cancelada',
                    'fecha_cancelacion' => now(),
                    'motivo_cancelacion' => 'Ficha de actualización rechazada'
                ]);

            return [
                'canceladas' => true,
                'nuevos_integrantes_cancelados' => $nuevosIntegrantesCancelados,
                'bajas_canceladas' => $bajasCanceladas,
                'mensaje' => "Se cancelaron {$nuevosIntegrantesCancelados} solicitudes de nuevos integrantes y {$bajasCanceladas} solicitudes de baja"
            ];
        } catch (\Exception $e) {
            return [
                'canceladas' => false,
                'error' => 'Error al cancelar solicitudes: ' . $e->getMessage()
            ];
        }
    }

    // Devuelve el equipo ejecutor (todos los EmpleadoProyecto) del proyecto asociado a la ficha
    public function equipoEjecutor()
    {
        return \App\Models\Personal\EmpleadoProyecto::where('proyecto_id', $this->proyecto_id)->get();
    }

    // Método para verificar si tiene solicitudes pendientes que pueden ser canceladas
    public function tieneSolicitudesPendientes()
    {
        $nuevosIntegrantes = $this->equipoEjecutorNuevos()->where('estado_incorporacion', 'pendiente')->count();
        $bajas = $this->equipoEjecutorBajas()->where('estado_baja', 'pendiente')->count();
        
        return $nuevosIntegrantes > 0 || $bajas > 0;
    }
}
