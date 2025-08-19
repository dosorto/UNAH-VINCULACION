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
        'motivo_ampliacion',
        'motivo_responsabilidades_nuevos',
        'motivo_razones_cambio',
    ];

    protected $casts = [
        'integrantes_baja' => 'array',
        'fecha_registro' => 'datetime',
        'fecha_baja' => 'date',
        'fecha_ampliacion' => 'date',
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
}
