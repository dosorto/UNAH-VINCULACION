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
use App\Models\Proyecto\Superavit;
use App\Models\Proyecto\Categoria;
use App\Models\Proyecto\Od;
use App\Models\Proyecto\FirmaProyecto;

use App\Models\Estado\EstadoProyecto;
use App\Models\Proyecto\DocumentoProyecto;



class Proyecto extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;



    protected static $logAttributes = [
        'nombre_proyecto',
        // 'coordinador_id',
        'modalidad_id',
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
        'fecha_registro',
        'responsable_revision_id',
        'fecha_aprobacion',
        'numero_libro',
        'numero_tomo',
        'numero_folio',
        'numero_dictamen'
    ];

    protected static $logName = 'Proyecto';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'nombre_proyecto',
                // 'coordinador_id',
                'modalidad_id',
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
                'fecha_registro',
                'responsable_revision_id',
                'fecha_aprobacion',
                'numero_libro',
                'numero_tomo',
                'numero_folio',
                'numero_dictamen'
            ])
            ->setDescriptionForEvent(fn(string $eventName) => "El registro {$this->nombre} ha sido {$eventName}");
    }




    protected $fillable = [
        'nombre_proyecto',
        // 'coordinador_id',
        'modalidad_id',
        // 'municipio_id',
        // 'departamento_id',
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
        'fecha_registro',
        'responsable_revision_id',
        'fecha_aprobacion',
        'numero_libro',
        'numero_tomo',
        'numero_folio',
        'numero_dictamen'


    ];

    public function getDocumentoIntermedioAttribute()
    {
        return $this->documentos()
            ->where('tipo_documento', 'Intermedio')
            ->first(); // Obtiene el primer documento con tipo "Intermedio"
    }

    public function getDocumentoFinalAttribute()
    {
        return $this->documentos()
            ->where('tipo_documento', 'Final')
            ->first(); // Obtiene el primer documento con tipo "Final"
    }

    // relacion uno a muchos con el DocuemntoProyecto
    public function documentos()
    {
        return $this->hasMany(DocumentoProyecto::class, 'proyecto_id');
    }


    // public function coordinador()
    // {
    //     return $this->belongsTo(Empleado::class, 'coordinador_id',);
    // }

    public function responsable_revision()
    {
        return $this->belongsTo(Empleado::class, 'responsable_revision_id',);
    }

    public function odss()
    {
        return $this->belongsTo(Od::class, 'od_id',);
    }

    public function modalidad()
    {
        return $this->belongsTo(Modalidad::class, 'modalidad_id',);
    }

    public function municipio()
    {
        return $this->belongsToMany(Municipio::class, 'proyecto_municipio', 'proyecto_id', 'municipio_id');
    }

    public function departamento()
    {
        return $this->belongsToMany(Departamento::class, 'proyecto_departamento', 'proyecto_id', 'departamento_id');
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
        return $this->hasMany(EmpleadoProyecto::class, 'proyecto_id')
            ->whereNot('rol', 'Coordinador');
    }

    // relacion uno a muchos con el modelo empleado_proyecto
    public function coordinador_proyecto()
    {
        return $this->hasMany(EmpleadoProyecto::class, 'proyecto_id')
            ->where('rol', 'Coordinador');
    }
    // obtener el coordinador del proyecto
    public function  getCoordinadorAttribute()
    {
        return $this->coordinador_proyecto->first()->empleado;
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

    // relacion uno a uno con el modelo superavit
    public function superavit()
    {
        return $this->hasOne(Superavit::class, 'proyecto_id');
    }

    // relacion muchos a muchos con el modelo categoria
    public function ods()
    {
        return $this->belongsToMany(Od::class, 'proyecto_ods', 'proyecto_id', 'ods_id');
    }

    // relacion muchos a muchos con el modelo categoria
    public function categoria()
    {
        return $this->belongsToMany(Categoria::class, 'proyecto_categoria', 'proyecto_id', 'categoria_id');
    }

    // contar la cantidad de estudiantes que tiene el proyecto
    public function cantidad_estudiantes()
    {
        return $this->estudiante_proyecto()->count();
    }

    // relacion uno a muchos con el modelo FirmaProyecto
    public function firma_proyecto()
    {
        return $this->morphMany(FirmaProyecto::class, 'firmable');
    }

    public function firma_coodinador_proyecto()
    {
        return $this->morphMany(FirmaProyecto::class, 'firmable')
            ->join('cargo_firma', 'firma_proyecto.cargo_firma_id', '=', 'cargo_firma.id')
            ->join('tipo_cargo_firma', 'cargo_firma.tipo_cargo_firma_id', '=', 'tipo_cargo_firma.id')
            ->where('tipo_cargo_firma.nombre', 'Coordinador Proyecto');
    }


    // firma_enlace_vinculacion
    public function firma_proyecto_enlace()
    {
        return $this->morphMany(FirmaProyecto::class, 'firmable')
            ->join('cargo_firma', 'firma_proyecto.cargo_firma_id', '=', 'cargo_firma.id')
            ->join('tipo_cargo_firma', 'cargo_firma.tipo_cargo_firma_id', '=', 'tipo_cargo_firma.id')
            ->where('tipo_cargo_firma.nombre', 'Enlace Vinculacion');
    }
    // firma del decano
    public function firma_proyecto_decano()
    {
        return $this->morphMany(FirmaProyecto::class, 'firmable')
            ->join('cargo_firma', 'firma_proyecto.cargo_firma_id', '=', 'cargo_firma.id')
            ->join('tipo_cargo_firma', 'cargo_firma.tipo_cargo_firma_id', '=', 'tipo_cargo_firma.id')
            ->where('tipo_cargo_firma.nombre', 'Director centro');
    }

    public function firma_proyecto_jefe()
    {
        return $this->morphMany(FirmaProyecto::class, 'firmable')
            ->join('cargo_firma', 'firma_proyecto.cargo_firma_id', '=', 'cargo_firma.id')
            ->join('tipo_cargo_firma', 'cargo_firma.tipo_cargo_firma_id', '=', 'tipo_cargo_firma.id')
            ->where('tipo_cargo_firma.nombre', 'Jefe Departamento');
    }

    public function firma_proyecto_cargo()
    {
        return $this->hasOne(FirmaProyecto::class, 'proyecto_id')
            ->where('empleado_id', auth()->user()->empleado->id);
    }

    // relacion uno a  uno con el modelo firma_proyecto
    public function firma_proyecto_uno()
    {
        return $this->hasOne(FirmaProyecto::class, 'proyecto_id');
    }

    // relacion uno a muchos con el modelo do_proyecto
    public function estado_proyecto()
    {
        return $this->morphMany(EstadoProyecto::class, 'estadoable');
    }


    // relacion uno a muchos con actividad 
    public function actividades()
    {
        return $this->hasMany(Actividad::class, 'proyecto_id');
    }

    public function anexos()
    {
        return $this->hasMany(Anexo::class, 'proyecto_id');
    }

    // obtener el estado actual del proyecto
    public function getEstadoAttribute()
    {
        return $this->estado_proyecto()
            ->where('es_actual', true)
            ->first();
    }



    protected $table = 'proyecto';
}
