<?php

namespace App\Models\ServicioInfraestructura;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Proyecto\Modalidad;
use App\Models\Demografia\Departamento;
use App\Models\Demografia\Municipio;
use App\Models\UnidadAcademica\DepartamentoAcademico;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\UnidadAcademica\Carrera;
use App\Models\Personal\Empleado;
use App\Models\Estudiante\EstudianteServicio;
use App\Models\PresupuestoServicio\PresupuestoServicio;


use App\Models\ServicioInfraestructura\ObjetivoEspecificoServicio;

class ServicioTecnologico extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'servicios_tecnologicos';

    protected $fillable = [
        'nombre_accion',
        'modalidad_id',
        'aldea',
        'hombres',
        'mujeres',
        'otros',
        'indigenas_hombres',
        'indigenas_mujeres',
        'afroamericanos_hombres',
        'afroamericanos_mujeres',
        'mestizos_hombres',
        'mestizos_mujeres',
        'descripción_servicio',
        'descripcion_problema',
        'descripcion_participante',
        'objetivo_general',
        'objetivo_especifico',
        'resultados_esperados',
        'indicadores_resultados',
        'descripción_ser_infraestructura',
        'ubicacion',
        'unidad_gestora',
        'fecha_inicio',
        'fecha_finalizacion',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_finalizacion' => 'date',
        'hombres' => 'integer',
        'mujeres' => 'integer',
        'otros' => 'integer',
        'indigenas_hombres' => 'integer',
        'indigenas_mujeres' => 'integer',
        'afroamericanos_hombres' => 'integer',
        'afroamericanos_mujeres' => 'integer',
        'mestizos_hombres' => 'integer',
        'mestizos_mujeres' => 'integer',
    ];

    // Relación con Modalidad
    public function modalidad()
    {
        return $this->belongsTo(Modalidad::class, 'modalidad_id');
    }

    //  Relación con Facultades y Centros
    public function centrosFacultades()
    {
        return $this->belongsToMany(FacultadCentro::class, 'servicio_centro_facultad', 'servicio_tecnologico_id', 'centro_facultad_id');
    }

    //  Relación con Departamentos Académicos
    public function departamentosAcademicos()
    {
        return $this->belongsToMany(DepartamentoAcademico::class, 'servicio_depto_ac', 'servicio_tecnologico_id', 'departamento_academico_id');
    }

    //  Relación con Departamentos
    public function departamento()
    {
        return $this->belongsToMany(Departamento::class, 'servicio_departamento', 'servicio_tecnologico_id', 'departamento_id');
    }

    public function carreras()
    {
        return $this->belongsToMany(Carrera::class, 'servicio_carrera', 'servicio_tecnologico_id', 'carrera_id');
    }

    //  Relación con Municipios
    public function municipio()
    {
        return $this->belongsToMany(Municipio::class, 'servicio_municipio', 'servicio_tecnologico_id', 'municipio_id');
    }

     public function aldea()
    {
        return $this->belongsTo(Aldea::class, 'aldea_id',);
    }


    //  Relación con Empleados
    public function empleados()
    {
        return $this->belongsToMany(
            Empleado::class,
            'empleado_servicio',
            'servicio_tecnologico_id',
            'empleado_id'
        )->withPivot('rol', 'hash');
    }

    public function empleado_servicio()
    {
        return $this->hasMany(EmpleadoServicio::class, 'servicio_tecnologico_id');
    }

    public function coordinador_servicio()
    {
        return $this->hasMany(EmpleadoServicio::class, 'servicio_tecnologico_id');
    }

    public function getCoordinadoresAttribute()
    {
        return $this->coordinador_servicio()->where('rol', 'Coordinador')->get();
    }

    public function getIntegrantesAttribute()
    {
        return $this->empleado_servicio()->where('rol', '!=', 'Coordinador')->get();
    }

    public function getCoordinadorAttribute()
    {
        return optional($this->coordinador_servicio->first())->empleado;
    }

    //  Otras relaciones
    public function estudiante_servicio()
    {
        return $this->hasMany(EstudianteServicio::class,'servicio_tecnologico_id'
        );
    }

    public function actividades()
    {
        return $this->hasMany(ActividadServicio::class, 'servicio_tecnologico_id');
    }

    public function estados()
    {
        return $this->morphMany(EstadoServicioTecnologico::class, 'estadoable');
    }

    public function firmas()
    {
        return $this->morphMany(FirmaServicio::class, 'firmable');
    }

    public function objetivosEspecificos()
    {
        return $this->hasMany(ObjetivoEspecificoServicio::class, 'servicio_tecnologico_id');
    }

    public function presupuesto()
    {
        return $this->hasOne(PresupuestoServicio::class, 'servicio_tecnologico_id');
    }
}
