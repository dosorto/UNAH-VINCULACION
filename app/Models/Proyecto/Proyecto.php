<?php

namespace App\Models\Proyecto;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Unidad_Academica\Entidad_academica;
use App\Models\UnidadAcademica\Carrera;
use App\Models\UnidadAcademica\FacultadCentro;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

use App\Models\Personal\Empleado;

use App\Models\OD\Od;
use App\Models\UnidadAcademica\DepartamentoAcademico;

use App\Models\Proyecto\Modalidad;
use App\Models\Personal\EmpleadoProyecto;
use App\Models\Estudiante\EstudianteProyecto;
use App\Models\Proyecto\EntidadContraparte;

use App\Models\Demografia\Municipio;
use App\Models\Demografia\Departamento;
use App\Models\Demografia\Ciudad;
use App\Models\Demografia\Aldea;

use App\Models\Presupuesto\Presupuesto;



class Proyecto extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    protected static $logAttributes = [
        'id',
        'nombre',
        'carrera_facultad_centro_id',
        'entidad_academica_id',
        'coordinador_id',
        'od_id',
        'modalidad_id',
        'categoria_id',
        'municipio_id',
        'departamento_id',
        'ciudad_id',
        'aldea_id',
        'resumen',
        'objetivo_general',
        'objetivos_especificos',
        'fecha_inicio',
        'fecha_finalizacion',
        'evaluacion_intermedia',
        'evaluacion_final',
        'poblacion_participante',
        'modalidad_ejecucion',
        'resultados_esperados',
        'indicadores_medicion_resultados'
    ];

    protected static $logName = 'Proyecto';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'id',
                'nombre',
                'carrera_facultad_centro_id',
                'entidad_academica_id',
                'coordinador_id',
                'od_id',
                'modalidad_id',
                'categoria_id',
                'municipio_id',
                'departamento_id',
                'ciudad_id',
                'aldea_id',
                'resumen',
                'objetivo_general',
                'objetivos_especificos',
                'fecha_inicio',
                'fecha_finalizacion',
                'evaluacion_intermedia',
                'evaluacion_final',
                'poblacion_participante',
                'modalidad_ejecucion',
                'resultados_esperados',
                'indicadores_medicion_resultados'
            ])
            ->setDescriptionForEvent(fn(string $eventName) => "El registro {$this->nombre} ha sido {$eventName}");
    }

    protected $fillable = [
        'id',
        'nombre',
        'carrera_facultad_centro_id',
        'entidad_academica_id',
        'coordinador_id',
        'od_id',
        'modalidad_id',
        'categoria_id',
        'municipio_id',
        'departamento_id',
        'ciudad_id',
        'aldea',
        'resumen',
        'objetivo_general',
        'objetivos_especificos',
        'fecha_inicio',
        'fecha_finalizacion',
        'evaluacion_intermedia',
        'evaluacion_final',
        'poblacion_participante',
        'modalidad_ejecucion',
        'resultados_esperados',
        'indicadores_medicion_resultados',
    ];

    public function carrerafacultadcentro()
    {
        return $this->belongsTo(CarreraFacultadCentro::class, 'carrera_facultad_centro_id',);
    }


    public function entidadacademica()
    {
        return $this->belongsTo(EntidadAcademica::class, 'entidad_academica_id',);
    }


    public function coordinador()
    {
        return $this->belongsTo(Empleado::class, 'coordinador_id',);
    }

    public function od()
    {
        return $this->belongsTo(Od::class, 'od_id',);
    }

    public function modalidad()
    {
        return $this->belongsTo(Modalidad::class, 'modalidad_id',);
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id',);
    }

    public function municipio()
    {
        return $this->belongsTo(Municipio::class, 'municipio_id',);
    }

    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'departamento_id',);
    }

    public function ciudad()
    {
        return $this->belongsTo(Ciudad::class, 'ciudad_id',);
    }

    public function aldea()
    {
        return $this->belongsTo(Aldea::class, 'aldea_id',);
    }


    // relacion muchos a muchos con el modelo Carrera
    public function carreras()
    {
        return $this->belongsToMany(Carrera::class, 'proyecto_carrera', 'proyecto_id', 'carrera_id');
    }

    // relacion muchos a muchos con el modelo facultad_centro a traves de la tabla  proyecto_facultad_centro
    public function facultades_centros()
    {
        return $this->belongsToMany(FacultadCentro::class, 'proyecto_centro_facultad', 'proyecto_id', 'centro_facultad_id');
    }

    // relacion muchos a muchos con el modelo empleado Entidad_academica
    public function entidades_academicas()
    {
        return $this->belongsToMany(Entidad_academica::class, 'proyecto_entidad_academica', 'proyecto_id', 'entidad_academica_id');
    }

    // relacion muchos a muchos con el modelo departamento academico
    public function departamentos_academicos()
    {
        return $this->belongsToMany(DepartamentoAcademico::class, 'proyecto_depto_ac', 'proyecto_id', 'departamento_academico_id');
    }

    // relacion de muchos a muchos con el modelo empleado llamada integrantes del proyecto
    public function integrantes()
    {
        return $this->belongsToMany(Empleado::class, 'empleado_proyecto', 'proyecto_id', 'empleado_id');
    }

    // relacion uno a muchos con el modelo empleado_proyecto
    public function empleado_proyecto()
    {
        return $this->hasMany(EmpleadoProyecto::class, 'proyecto_id');
    }

    // realacion uno a muchos con el modelo estudiante_proyecto
    public function estudiante_proyecto()
    {
        return $this->hasMany(EstudianteProyecto::class, 'proyecto_id');
    }

    // relacion uno a muchos con el modelo entidad contraparte
    public function entidad_contraparte()
    {
        return $this->hasMany(EntidadContraparte::class, 'proyecto_id');
    }

    // relacion uno a uno con el modelo presupuesto
    public function presupuesto()
    {
        return $this->hasOne(Presupuesto::class, 'proyecto_id');
    }





    protected $table = 'proyecto';
}
