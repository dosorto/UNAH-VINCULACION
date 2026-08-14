<?php

namespace App\Models\Proyecto;

use App\Models\Estado\TipoEstado;
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
use App\Models\Proyecto\EntidadContraparteProyecto;

use App\Models\Demografia\Municipio;
use App\Models\Demografia\Departamento;
use App\Models\Demografia\Ciudad;
use App\Models\Demografia\Aldea;
use App\Models\Estudiante\TipoParticipacion;
use App\Models\Presupuesto\Presupuesto;
use App\Models\Proyecto\Superavit;
use App\Models\Proyecto\Categoria;
use App\Models\Proyecto\Od;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\Proyecto\IntegranteInternacional;
use App\Models\Proyecto\IntegranteInternacionalProyecto;
use App\Models\Estudiante\Estudiante;
use App\Models\Estado\EstadoProyecto;
use App\Models\Proyecto\DocumentoProyecto;
use App\Models\Proyecto\ObjetivoEspecifico;
use App\Models\Proyecto\ResultadoEsperado;
use App\Models\Proyecto\AporteInstitucional;
use App\Models\Proyecto\FichaActualizacion;
use App\Models\Proyecto\MetaContribuye;
use App\Models\Proyecto\EquipoEjecutorBaja;
use App\Models\Proyecto\EquipoEjecutorNuevo;
use DragonCode\Contracts\Cashier\Config\Payments\Statuses;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\User;
use App\Models\InformeFinal\InformeFinalProyecto;
use App\Models\InformeIntermedio\InformeIntermedioProyecto;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\Proyecto\ProyectoWorkflowService;
use App\Services\Workflow\WorkflowResumptionPolicy;
use App\Models\Workflow\LegacyWorkflowAdoption;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Proyecto extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    public const FLUJO_INSCRIPCION = 'inscripcion';
    public const FLUJO_INFORME_INTERMEDIO = 'informe_intermedio';
    public const FLUJO_CIERRE_PROYECTO = 'cierre_proyecto';

    protected static $logAttributes = [
        'nombre_proyecto',
        'codigo_proyecto',
        // 'coordinador_id',
        'modalidad_id',
        'municipio_id',
        'departamento_id',
        'lineas_investigacion_academica',
        'programa_pertenece',
        'aldea',
        'resumen',
        'descripcion_participantes',
        'participacion_unah',
        'participacion_contraparte',
        'participacion_comunidad',
        'definicion_problema',
        'objetivo_general',
        'objetivos_especificos',
        'fecha_inicio',
        'fecha_finalizacion',
        'evaluacion_intermedia',
        'evaluacion_final',
        'poblacion_participante',
        'hombres',
        'mujeres',
        'otros',
        'indigenas_hombres',
        'indigenas_mujeres',
        'afroamericanos_hombres',
        'afroamericanos_mujeres',
        'mestizos_hombres',
        'mestizos_mujeres',
        'indigenas_hombres_marcado',
        'indigenas_mujeres_marcado',
        'afroamericanos_hombres_marcado',
        'afroamericanos_mujeres_marcado',
        'mestizos_hombres_marcado',
        'mestizos_mujeres_marcado',
        'modalidad_ejecucion',
        'pais',
        'region',
        'caserio',
        'resultados_esperados',
        'indicadores_medicion_resultados',
        'impacto_deseado',
        'alineamiento_reforma',
        'metodologia',
        'bibliografia',
        'fecha_registro',
        'responsable_revision_id',
        'fecha_aprobacion',
        'numero_libro',
        'numero_tomo',
        'numero_folio',
        'numero_dictamen',
        'flujo_aprobacion_id'
    ];

    protected static $logName = 'Proyecto';
    protected $table = 'proyecto';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'nombre_proyecto',
                'codigo_proyecto',
                'descripcion',
                'fecha_inicio',
                'fecha_finalizacion',
                'evaluacion_intermedia',
                'evaluacion_final',
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
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "El registro {$this->nombre} ha sido {$eventName}");
    }




    protected $fillable = [
        'nombre_proyecto',
        'codigo_proyecto',
        'tipo_accion_id',
        'tipo_accion_opcion_id',
        'modalidad_id',
        'municipio_id',
        'departamento_id',
        'ciudad_id',
        'aldea',
        'resumen',
        'descripcion_participantes',
        'participacion_unah',
        'participacion_contraparte',
        'participacion_comunidad',
        'definicion_problema',
        'objetivo_general',
        'objetivos_especificos',
        'fecha_inicio',
        'fecha_finalizacion',
        'evaluacion_intermedia',
        'evaluacion_final',
        'poblacion_participante',
        'hombres',
        'mujeres',
        'cantidad_estudiantes_hombres',
        'cantidad_estudiantes_mujeres',
        'total_estudiantes',
        'otros',
        'indigenas_hombres',
        'indigenas_mujeres',
        'afroamericanos_hombres',
        'afroamericanos_mujeres',
        'mestizos_hombres',
        'mestizos_mujeres',
        'indigenas_hombres_marcado',
        'indigenas_mujeres_marcado',
        'afroamericanos_hombres_marcado',
        'afroamericanos_mujeres_marcado',
        'mestizos_hombres_marcado',
        'mestizos_mujeres_marcado',
        'modalidad_ejecucion',
        'pais',
        'region',
        'caserio',
        'resultados_esperados',
        'indicadores_medicion_resultados',
        'impacto_deseado',
        'alineamiento_reforma',
        'metodologia',
        'bibliografia',
        'total_aporte_institucional',
        'fecha_registro',
        'fecha_aprobacion',
        'numero_libro',
        'numero_tomo',
        'numero_folio',
        'numero_dictamen',
        'programa_pertenece',
        'lineas_investigacion_academica',
        'responsable_revision_id',
        'flujo_aprobacion_id',
        // FORM-DVUS-015 (Voluntariado Académico)
        'tematica_principal',
        'tematica_principal_otro',
        'metodologia_seguimiento',
        'experiencia_conocimientos_teoricos',
        'experiencia_habilidades_tecnicas',
        'experiencia_competencias_blandas',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_finalizacion' => 'date',
        'evaluacion_intermedia' => 'date',
        'evaluacion_final' => 'date',
        'fecha_registro' => 'date',
        'fecha_aprobacion' => 'date',
        'pais' => 'array',
        'region' => 'array',
        'caserio' => 'array',
        'metodologia_seguimiento' => 'array',
    ];

    // funcion para capturar cada ves que se crea un proyecto
    protected static function booted(): void
    {
        static::created(function ($proyecto) {
            $proyecto->fecha_registro = now();
            $proyecto->save();
        });
    }


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

    public function documento_intermedio()
    {
        return $this->documentos()
            ->where('tipo_documento', 'Informe Intermedio')
            ->first();
    }

    public function estudiantes()
    {
        return $this->belongsToMany(Estudiante::class, 'estudiante_proyecto', 'proyecto_id', 'estudiante_id')
            ->using(EstudianteProyecto::class)
            ->withPivot('tipo_participacion_id')
            ->withTimestamps();
    }


    public function tipoParticipaciones()
    {
        return $this->belongsToMany(TipoParticipacion::class, 'estudiante_proyecto', 'proyecto_id', 'tipo_participacion_id')
            ->withPivot('estudiante_id');
    }


    public function participacionesEstudiantes()
    {
        return $this->hasMany(EstudianteProyecto::class, 'proyecto_id')
            ->with(['estudiante', 'tipoParticipacion']);
    }

    public function documento_final()
    {
        return $this->documentos()
            ->where('tipo_documento', 'Informe Final')
            ->first();
    }

    public function informeFinalInf001()
    {
        return $this->hasOne(InformeFinalProyecto::class, 'proyecto_id');
    }

    public function informeIntermedio()
    {
        return $this->hasOne(InformeIntermedioProyecto::class, 'proyecto_id');
    }



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

    public function docentes_proyecto()
    {
        return $this->hasMany(EmpleadoProyecto::class, 'proyecto_id');
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

    public function gruposEstudiantesPlanificados()
    {
        return $this->hasMany(EstudianteProyecto::class, 'proyecto_id');
    }

    // relacion muchos a muchos con integrantes internacionales
    public function integrante_internacional_proyecto()
    {
        return $this->hasMany(IntegranteInternacionalProyecto::class, 'proyecto_id');
    }

    public function integrantesInternacionales()
    {
        return $this->belongsToMany(
            IntegranteInternacional::class,
            'integrante_internacional_proyecto',
            'proyecto_id',
            'integrante_internacional_id'
        )->withPivot([
            'rol',
        ])->withTimestamps();
    }

    // relación pivote de contrapartes (hasMany al modelo intermedio)
    public function entidad_contraparte_proyecto()
    {
        return $this->hasMany(EntidadContraparteProyecto::class, 'proyecto_id');
    }

    // relación muchos a muchos con el catálogo de contrapartes
    public function entidad_contraparte()
    {
        return $this->belongsToMany(
            EntidadContraparte::class,
            'entidad_contraparte_proyecto',
            'proyecto_id',
            'entidad_contraparte_id'
        )->withPivot(['rtn', 'descripcion_acuerdos'])
         ->withTimestamps();
    }

    // relacion uno a uno con el modelo presupuesto
    public function presupuesto()
    {
        return $this->hasOne(Presupuesto::class, 'proyecto_id');
    }

    // relacion uno a uno con el modelo superavit
    public function superavit()
    {
        return $this->hasMany(Superavit::class, 'proyecto_id');
    }

    // relacion muchos a muchos con el modelo categoria
    public function ods()
    {
        return $this->belongsToMany(Od::class, 'proyecto_ods', 'proyecto_id', 'ods_id');
    }

    // relacion muchos a muchos con las metas de ODS
    public function metasContribuye()
    {
        return $this->belongsToMany(MetaContribuye::class, 'proyecto_meta_contribuye', 'proyecto_id', 'meta_contribuye_id');
    }

    // relacion muchos a muchos con asignaturas
    public function asignaturas()
    {
        return $this->belongsToMany(\App\Models\Asignatura::class, 'proyecto_asignatura', 'proyecto_id', 'asignatura_id');
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

    // Métodos para cuantificación de trabajo voluntario

    // Estudiantes por género
    public function getEstudiantesHombresAttribute()
    {
        return $this->estudiante_proyecto()
            ->join('estudiante', 'estudiante_proyecto.estudiante_id', '=', 'estudiante.id')
            ->where('estudiante.sexo', 'Masculino')
            ->count();
    }

    public function getEstudiantesMujeresAttribute()
    {
        return $this->estudiante_proyecto()
            ->join('estudiante', 'estudiante_proyecto.estudiante_id', '=', 'estudiante.id')
            ->where('estudiante.sexo', 'Femenino')
            ->count();
    }

    // Estudiantes por tipo de participación y género (actualmente ya no se utiliza por que cmambiamos a cantidades)
    public function getEstudiantesPorTipo($tipo, $genero = null)
    {
        $query = $this->estudiante_proyecto()
            ->join('estudiante', 'estudiante_proyecto.estudiante_id', '=', 'estudiante.id')
            ->where('estudiante_proyecto.tipo_participacion_estudiante', $tipo);

        if ($genero) {
            $query->where('estudiante.sexo', $genero);
        }

        return $query->count();
    }

    // Personal docente por género
    public function getDocentesHombresAttribute()
    {
        return $this->empleado_proyecto()
            ->join('empleado', 'empleado_proyecto.empleado_id', '=', 'empleado.id')
            ->join('categoria', 'empleado.categoria_id', '=', 'categoria.id')
            ->where('empleado.sexo', 'Masculino')
            ->where(function ($q) {
                $q->whereRaw("LOWER(categoria.nombre) LIKE '%profesores x hora%'")
                    ->orWhereRaw("LOWER(categoria.nombre) LIKE '%profesores horarios%'")
                    ->orWhereRaw("LOWER(categoria.nombre) LIKE '%titular i%'")
                    ->orWhereRaw("LOWER(categoria.nombre) LIKE '%titular ii%'")
                    ->orWhereRaw("LOWER(categoria.nombre) LIKE '%titular iii%'")
                    ->orWhereRaw("LOWER(categoria.nombre) LIKE '%titular iv%'")
                    ->orWhereRaw("LOWER(categoria.nombre) LIKE '%titular v%'");
            })
            ->count();
    }

    public function getDocentesMujeresAttribute()
    {
        return $this->empleado_proyecto()
            ->join('empleado', 'empleado_proyecto.empleado_id', '=', 'empleado.id')
            ->join('categoria', 'empleado.categoria_id', '=', 'categoria.id')
            ->where('empleado.sexo', 'Femenino')
            ->where(function ($q) {
                $q->whereRaw("LOWER(categoria.nombre) LIKE '%profesores x hora%'")
                    ->orWhereRaw("LOWER(categoria.nombre) LIKE '%profesores horarios%'")
                    ->orWhereRaw("LOWER(categoria.nombre) LIKE '%titular i%'")
                    ->orWhereRaw("LOWER(categoria.nombre) LIKE '%titular ii%'")
                    ->orWhereRaw("LOWER(categoria.nombre) LIKE '%titular iii%'")
                    ->orWhereRaw("LOWER(categoria.nombre) LIKE '%titular iv%'")
                    ->orWhereRaw("LOWER(categoria.nombre) LIKE '%titular v%'");
            })
            ->count();
    }

    public function getDocentesPorCategoria($categoria, $genero = null)
    {
        $query = $this->empleado_proyecto()
            ->join('empleado', 'empleado_proyecto.empleado_id', '=', 'empleado.id')
            ->join('categoria', 'empleado.categoria_id', '=', 'categoria.id')
            ->where('empleado.tipo_empleado', 'docente'); // Corregido: minúscula

        if (strtolower($categoria) === 'permanente') {
            $query->where(function ($q) {
                $q->whereRaw('LOWER(categoria.nombre) LIKE ?', ['%titular i%'])
                    ->orWhereRaw('LOWER(categoria.nombre) LIKE ?', ['%titular ii%'])
                    ->orWhereRaw('LOWER(categoria.nombre) LIKE ?', ['%titular iii%'])
                    ->orWhereRaw('LOWER(categoria.nombre) LIKE ?', ['%titular iv%'])
                    ->orWhereRaw('LOWER(categoria.nombre) LIKE ?', ['%titular v%']);
            });
        } else {
            // Búsqueda más precisa para las categorías específicas
            $categoriaLower = strtolower($categoria);
            if ($categoriaLower === 'profesores x hora') {
                $query->whereRaw('LOWER(categoria.nombre) = ?', ['profesores x hora']);
            } elseif ($categoriaLower === 'profesores horarios') {
                $query->whereRaw('LOWER(categoria.nombre) = ?', ['profesores horarios']);
            } else {
                $query->whereRaw('LOWER(categoria.nombre) LIKE ?', ['%' . $categoriaLower . '%']);
            }
        }

        if ($genero) {
            $query->where('empleado.sexo', $genero);
        }

        return $query->count();
    }

    // Personal administrativo por género
    public function getAdministrativosHombresAttribute()
    {
        return $this->empleado_proyecto()
            ->join('empleado', 'empleado_proyecto.empleado_id', '=', 'empleado.id')
            ->join('categoria', 'empleado.categoria_id', '=', 'categoria.id')
            ->where('empleado.sexo', 'Masculino')
            ->where(function ($q) {
                $q->whereRaw("LOWER(categoria.nombre) LIKE '%administrativo%'")
                    ->orWhereRaw("LOWER(categoria.nombre) LIKE '%servicio%'")
                    ->orWhereRaw("LOWER(categoria.nombre) LIKE '%tecnico%'")
                    ->orWhereRaw("LOWER(categoria.nombre) LIKE '%instructor%'");
            })
            ->count();
    }

    public function getAdministrativasMujeresAttribute()
    {
        return $this->empleado_proyecto()
            ->join('empleado', 'empleado_proyecto.empleado_id', '=', 'empleado.id')
            ->join('categoria', 'empleado.categoria_id', '=', 'categoria.id')
            ->where('empleado.sexo', 'Femenino')
            ->where(function ($q) {
                $q->whereRaw("LOWER(categoria.nombre) LIKE '%administrativo%'")
                    ->orWhereRaw("LOWER(categoria.nombre) LIKE '%servicio%'")
                    ->orWhereRaw("LOWER(categoria.nombre) LIKE '%tecnico%'")
                    ->orWhereRaw("LOWER(categoria.nombre) LIKE '%instructor%'");
            })
            ->count();
    }

    // Personal administrativo por tipo y género
    public function getAdministrativosPorTipo($tipo, $genero = null)
    {
        $query = $this->empleado_proyecto()
            ->join('empleado', 'empleado_proyecto.empleado_id', '=', 'empleado.id')
            ->join('categoria', 'empleado.categoria_id', '=', 'categoria.id')
            ->where('categoria.nombre', 'LIKE', '%' . $tipo . '%');

        if ($genero) {
            $query->where('empleado.sexo', $genero);
        }

        return $query->count();
    }

    // 
    public function getFirmabyCargo($cargo)
    {
        return $this->firma_proyecto()
            ->join('cargo_firma', 'firma_proyecto.cargo_firma_id', '=', 'cargo_firma.id')
            ->join('tipo_cargo_firma', 'cargo_firma.tipo_cargo_firma_id', '=', 'tipo_cargo_firma.id')
            ->where('tipo_cargo_firma.nombre', $cargo)
            ->first();
    }

    // relacion uno a muchos con el modelo FirmaProyecto
    public function firma_proyecto()
    {
        return $this->morphMany(FirmaProyecto::class, 'firmable');
    }

    public function firmasDeEtapa(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(FirmaProyecto::class, 'firmable')
            ->whereNotNull('flujo_aprobacion_etapa_id')
            ->orderBy('orden_revision');
    }

    public function adopcionFlujoLegacy(): MorphOne
    {
        return $this->morphOne(LegacyWorkflowAdoption::class, 'adoptable');
    }

    public function flujoAprobacion()
    {
        return $this->belongsTo(FlujoAprobacion::class, 'flujo_aprobacion_id');
    }

    public function tipoAccion()
    {
        return $this->belongsTo(VinculacionTipoAccion::class, 'tipo_accion_id');
    }

    public function firma_coodinador_proyecto()
    {
        return $this->morphMany(FirmaProyecto::class, 'firmable')
            ->join('cargo_firma', 'firma_proyecto.cargo_firma_id', '=', 'cargo_firma.id')
            ->join('tipo_cargo_firma', 'cargo_firma.tipo_cargo_firma_id', '=', 'tipo_cargo_firma.id')
            ->where('tipo_cargo_firma.nombre', 'Coordinador Proyecto')
            ->orderByDesc('firma_proyecto.revision_ciclo')
            ->orderByDesc('firma_proyecto.id');
    }


    public function firma_revisor_vinculacion()
    {
        return $this->morphMany(FirmaProyecto::class, 'firmable')
            ->join('cargo_firma', 'firma_proyecto.cargo_firma_id', '=', 'cargo_firma.id')
            ->join('tipo_cargo_firma', 'cargo_firma.tipo_cargo_firma_id', '=', 'tipo_cargo_firma.id')
            ->where('tipo_cargo_firma.nombre', 'Revisor Vinculacion')
            ->orderByDesc('firma_proyecto.revision_ciclo')
            ->orderByDesc('firma_proyecto.id');
    }

    public function firma_director_vinculacion()
    {
        return $this->morphMany(FirmaProyecto::class, 'firmable')
            ->join('cargo_firma', 'firma_proyecto.cargo_firma_id', '=', 'cargo_firma.id')
            ->join('tipo_cargo_firma', 'cargo_firma.tipo_cargo_firma_id', '=', 'tipo_cargo_firma.id')
            ->where('tipo_cargo_firma.nombre', 'Director Vinculacion')
            ->orderByDesc('firma_proyecto.revision_ciclo')
            ->orderByDesc('firma_proyecto.id');
    }

    public function getDirectorVinculacionAttribute()
    {
        return $this->firma_director_vinculacion()->first()?->empleado;
    }



    // Relación uno a muchos con ficha_actualizacion
    public function ficha_actualizacion()
    {
        return $this->hasMany(FichaActualizacion::class, 'proyecto_id');
    }



    // firma_enlace_vinculacion
    public function firma_proyecto_enlace()
    {
        return $this->morphMany(FirmaProyecto::class, 'firmable')
            ->join('cargo_firma', 'firma_proyecto.cargo_firma_id', '=', 'cargo_firma.id')
            ->join('tipo_cargo_firma', 'cargo_firma.tipo_cargo_firma_id', '=', 'tipo_cargo_firma.id')
            ->where('tipo_cargo_firma.nombre', 'Enlace Vinculacion')
            ->orderByDesc('firma_proyecto.revision_ciclo')
            ->orderByDesc('firma_proyecto.id');
    }
    // firma del decano
    public function firma_proyecto_decano()
    {
        return $this->morphMany(FirmaProyecto::class, 'firmable')
            ->join('cargo_firma', 'firma_proyecto.cargo_firma_id', '=', 'cargo_firma.id')
            ->join('tipo_cargo_firma', 'cargo_firma.tipo_cargo_firma_id', '=', 'tipo_cargo_firma.id')
            ->where('tipo_cargo_firma.nombre', 'Director centro')
            ->orderByDesc('firma_proyecto.revision_ciclo')
            ->orderByDesc('firma_proyecto.id');
    }

    public function firma_proyecto_jefe()
    {
        return $this->morphMany(FirmaProyecto::class, 'firmable')
            ->join('cargo_firma', 'firma_proyecto.cargo_firma_id', '=', 'cargo_firma.id')
            ->join('tipo_cargo_firma', 'cargo_firma.tipo_cargo_firma_id', '=', 'tipo_cargo_firma.id')
            ->where('tipo_cargo_firma.nombre', 'Jefe Departamento')
            ->orderByDesc('firma_proyecto.revision_ciclo')
            ->orderByDesc('firma_proyecto.id');
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

    public function obtenerUltimoEstado()
    {
        return $this->estado_proyecto()
            ->latest('created_at') // Ordenar por la columna que representa el último registro
            ->first();
    }

    public function resolveFlujoAprobacion(): ?FlujoAprobacion
    {
        if ($this->flujoAprobacion) {
            return $this->flujoAprobacion->loadMissing('etapas.cargoFirma.tipoCargoFirma');
        }

        $codigoFormulario = $this->codigoFormularioFlujo();

        return FlujoAprobacion::query()
            ->with('etapas.cargoFirma.tipoCargoFirma')
            ->where('proceso', 'PROYECTO')
            ->where('activo', true)
            ->when($this->tipo_accion_id, fn ($query) => $query->where('tipo_accion_id', $this->tipo_accion_id))
            ->when($codigoFormulario, fn ($query) => $query->where('codigo_formulario', $codigoFormulario))
            ->orderBy('id')
            ->first();
    }

    public function codigoFormularioFlujo(): ?string
    {
        $codigo = $this->tipo_accion_id
            ? DB::table('vinculacion_tipos_accion')->where('id', $this->tipo_accion_id)->value('codigo')
            : null;

        return match ($codigo) {
            'DESARROLLO_LOCAL_REGIONAL' => 'FORM-DVUS-001',
            'VOLUNTARIADO' => 'FORM-DVUS-015',
            default => null,
        };
    }

    public function flujoEtapasOrdenadas(?string $proceso = null): Collection
    {
        $proceso = $proceso ?: self::FLUJO_INSCRIPCION;

        return app(ProyectoWorkflowService::class)->etapas($this, $proceso, false);
    }

    public function flujoEtapasActivasOrdenadas(?string $proceso = null): Collection
    {
        return $this->flujoEtapasOrdenadas($proceso)
            ->filter(fn ($etapa) => (bool) $etapa->activo)
            ->values();
    }

    public function procesoTieneEtapasConfiguradas(string $proceso): bool
    {
        return $this->flujoEtapasOrdenadas($proceso)
            ->contains(fn ($etapa) => $etapa->activo && $etapa->cargo_firma_id);
    }

    public function tieneFlujoInformeIntermedio(): bool
    {
        return $this->procesoTieneEtapasConfiguradas(self::FLUJO_INFORME_INTERMEDIO);
    }

    public function tieneFlujoCierreProyecto(): bool
    {
        return $this->procesoTieneEtapasConfiguradas(self::FLUJO_CIERRE_PROYECTO);
    }

    public function constanciaFinalizacionProyecto()
    {
        return $this->hasOne(\App\Models\Constancias\ConstanciaFinalizacionProyecto::class, 'proyecto_id');
    }

    public function constanciaRegistroProyecto()
    {
        return $this->hasOne(\App\Models\Constancias\ConstanciaRegistroProyecto::class, 'proyecto_id');
    }

    public function usuarioPuedeGestionarInformeFinal(?User $user = null): bool
    {
        $user ??= auth()->user();
        $empleadoId = $user?->empleado?->id;

        return (bool) ($empleadoId && $this->coordinador_proyecto()
            ->where('empleado_id', $empleadoId)
            ->exists());
    }

    public function usuarioEsParticipante(?User $user = null): bool
    {
        $user ??= auth()->user();
        $empleadoId = $user?->empleado?->id;

        return (bool) ($empleadoId && $this->docentes_proyecto()
            ->where('empleado_id', $empleadoId)
            ->exists());
    }

    public function usuarioPuedeAuditarInformeFinal(?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        $empleadoId = $user->empleado?->id;
        $documento = $this->documentos()
            ->where('tipo_documento', 'Informe Final')
            ->first();

        return (bool) ($empleadoId && $documento && $documento->firma_documento()
            ->where('empleado_id', $empleadoId)
            ->exists());
    }

    public function puedeMostrarCierreProyecto(?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user
            || (! $this->usuarioPuedeGestionarInformeFinal($user) && ! $this->usuarioPuedeAuditarInformeFinal($user))
            || $this->estado?->tipoestado?->nombre !== 'En curso'
            || ! $this->tieneFlujoCierreProyecto()
        ) {
            return false;
        }

        if (! app(ProyectoWorkflowService::class)->inscripcionCompletada($this)) {
            return false;
        }

        if (! $this->tieneFlujoInformeIntermedio()) {
            return true;
        }

        $informeIntermedio = $this->informeIntermedio;

        if ($informeIntermedio) {
            return $informeIntermedio->estado === InformeIntermedioProyecto::ESTADO_APROBADO;
        }

        return $this->documento_intermedio()?->estado?->tipoestado?->nombre === 'Aprobado';
    }

    public function nextCargoFirmaId(?int $cargoFirmaId, ?string $proceso = null): ?int
    {
        if (! $cargoFirmaId) {
            return null;
        }

        $etapas = $this->flujoEtapasOrdenadas($proceso);
        $currentIndex = $etapas->search(fn ($etapa) => (int) $etapa->cargo_firma_id === (int) $cargoFirmaId);

        if ($currentIndex === false) {
            return null;
        }

        return $etapas->get($currentIndex + 1)?->cargo_firma_id;
    }

    public function nextEstadoIdForCargo(?int $cargoFirmaId, ?string $proceso = null): ?int
    {
        if (! $cargoFirmaId) {
            return null;
        }

        $cargoActual = CargoFirma::find($cargoFirmaId);
        if (! $cargoActual) {
            return null;
        }

        $nextCargoId = $this->nextCargoFirmaId($cargoFirmaId, $proceso);
        if ($nextCargoId) {
            return CargoFirma::find($nextCargoId)?->tipo_estado_id;
        }

        return $cargoActual->estado_siguiente_id;
    }

    public function nextEstadoIdEnFlujo(?int $cargoFirmaId, ?string $proceso = null): ?int
    {
        $nextCargoId = $this->nextCargoFirmaId($cargoFirmaId, $proceso);

        return $nextCargoId
            ? CargoFirma::find($nextCargoId)?->tipo_estado_id
            : null;
    }

    public function estadoFinalProcesoId(string $proceso): ?int
    {
        $estadoNombre = match ($proceso) {
            self::FLUJO_INFORME_INTERMEDIO,
            self::FLUJO_CIERRE_PROYECTO => 'Aprobado',
            default => 'En curso',
        };

        return TipoEstado::where('nombre', $estadoNombre)->value('id');
    }

    public function firstEstadoIdForProceso(string $proceso): ?int
    {
        return $this->flujoEtapasOrdenadas($proceso)
            ->first()
            ?->cargoFirma
            ?->tipo_estado_id;
    }

    public function isLastCargoFirmaForProceso(?int $cargoFirmaId, string $proceso): bool
    {
        return filled($cargoFirmaId)
            && $this->flujoEtapasOrdenadas($proceso)->isNotEmpty()
            && $this->nextCargoFirmaId($cargoFirmaId, $proceso) === null;
    }

    public static function procesoFlujoParaDocumento(string $tipoDocumento): ?string
    {
        return match ($tipoDocumento) {
            'Informe Intermedio' => self::FLUJO_INFORME_INTERMEDIO,
            'Informe Final' => self::FLUJO_CIERRE_PROYECTO,
            default => null,
        };
    }

    protected static function columnaAplicacionFlujo(string $proceso): string
    {
        return match ($proceso) {
            self::FLUJO_INFORME_INTERMEDIO => 'aplica_informe_intermedio',
            self::FLUJO_CIERRE_PROYECTO => 'aplica_cierre_proyecto',
            default => 'aplica_inscripcion',
        };
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

    // Sección VI de FORM-DVUS-015: uso de espacios, servicios y medios institucionales
    public function espaciosInstitucionales()
    {
        return $this->hasMany(EspacioInstitucional::class, 'proyecto_id');
    }

    // obtener el estado actual del proyecto
    public function getEstadoAttribute()
    {
        return $this->estado_proyecto()
            ->where('es_actual', true)
            ->first();
    }

    public function empleados()
    {
        return $this->belongsToMany(Empleado::class, 'empleado_proyecto');
    }
    public function estadoActual()
    {
        return $this->hasOne(EstadoProyecto::class, 'estadoable_id')
            ->where('estadoable_type', self::class)
            ->where('es_actual', true);
    }

    /**
     * Acceso directo al tipo de estado actual mediante un "hasOneThrough".
     * Alternativamente, si prefieres acceder como $proyecto->tipo_estado, puedes definir:
     */
    public function tipo_estado()
    {
        return $this->hasOneThrough(
            TipoEstado::class,      // Modelo final
            EstadoProyecto::class,  // Modelo intermedio
            'estadoable_id',        // FK en estado_proyecto que referencia a proyecto
            'id',                   // Clave primaria de TipoEstado
            'id',                   // Clave primaria de Proyecto
            'tipo_estado_id'        // FK en estado_proyecto que referencia a TipoEstado
        )
            ->where('estado_proyecto.estadoable_type', self::class)
            ->where('estado_proyecto.es_actual', true);
    }

    /**
     * Accessor para obtener el estado actual del proyecto
     * Retorna el TipoEstado actual del proyecto
     */
    public function getEstadoActualAttribute()
    {
        return $this->tipo_estado;
    }

    public function estudianteProyecto()
    {
        return $this->hasMany(EstudianteProyecto::class, 'proyecto_id');
    }

    // Relaciones del Marco Lógico
    public function objetivosEspecificos()
    {
        return $this->hasMany(ObjetivoEspecifico::class)->orderBy('orden');
    }

    // Resultados de mediano/largo plazo (efectos e impacto): pertenecen al proyecto
    // en general, no a un objetivo específico.
    public function resultadosProyecto()
    {
        return $this->hasMany(ResultadoEsperado::class, 'proyecto_id')->orderBy('orden');
    }

    // Relación con Aporte Institucional
    public function aporteInstitucional()
    {
        return $this->hasMany(AporteInstitucional::class);
    }

    public function aportesInstitucionales()
    {
        return $this->hasMany(AporteInstitucional::class, 'proyecto_id');
    }

    public function ejes_prioritarios_unah()
    {
        return $this->belongsToMany(
            \App\Models\Proyecto\EjesPrioritariosUnah::class,
            'eje_prioritario_proyecto',
            'proyecto_id',
            'ejes_prioritarios_unah_id'
        );
    }

    // Relación con integrantes dados de baja
    public function equipoEjecutorBajas()
    {
        return $this->hasMany(EquipoEjecutorBaja::class, 'proyecto_id')
            ->with(['empleado', 'estudiante', 'integranteInternacional']);
    }

    // Relación con nuevos integrantes pendientes
    public function equipoEjecutorNuevos()
    {
        return $this->hasMany(EquipoEjecutorNuevo::class, 'proyecto_id')
            ->with(['empleado', 'estudiante', 'integranteInternacional']);
    }


    public  static function createFromData(array $data): self
    {
        $proyecto = self::create($data);
        return $proyecto;
    }

    public function agregarFirma(
        string $cargoFirma,
        Empleado $empleado
    ): FirmaProyecto {
        $cargo = CargoFirma::query()
            ->join('tipo_cargo_firma', 'tipo_cargo_firma.id', '=', 'cargo_firma.tipo_cargo_firma_id')
            ->where('tipo_cargo_firma.nombre', $cargoFirma)
            ->where('cargo_firma.descripcion', 'Proyecto')
            ->select('cargo_firma.id')
            ->first();

        if (! $cargo) {
            throw new \RuntimeException(sprintf(
                'No se encontró el cargo de firma "%s" configurado para proyectos.',
                $cargoFirma
            ));
        }

        return $this->guardarFirmaDeCargo((int) $cargo->id, $empleado, [
            'estado_revision' => 'Aprobado',
            'firma_id' => $empleado?->firma?->id,
            'sello_id' => $empleado?->sello?->id,
            'fecha_firma' => now(),
        ]);
    }

    public function guardarFirmaDeCargo(
        int $cargoFirmaId,
        Empleado $empleado,
        array $attributes = [],
        ?DocumentoProyecto $documento = null
    ): FirmaProyecto
    {
        if (! CargoFirma::query()->whereKey($cargoFirmaId)->exists()) {
            throw new \RuntimeException(sprintf(
                'No se encontró el cargo de firma configurado (ID: %d).',
                $cargoFirmaId
            ));
        }

        if (! $empleado->exists || $empleado->trashed()) {
            throw new \RuntimeException('El empleado indicado para la firma no existe.');
        }

        $relation = $documento
            ? $documento->firma_documento()
            : $this->firma_proyecto();

        $firma = $relation->updateOrCreate(
            ['cargo_firma_id' => $cargoFirmaId],
            array_merge([
                'empleado_id' => $empleado->id,
                'hash' => 'hash',
            ], $attributes)
        );

        $this->anularFirmasPendientesDuplicadasDeCargo($cargoFirmaId, $firma->id, $documento);

        return $firma;
    }

    /**
     * @param  bool  $etapaCompartidaPorRol  true cuando la etapa se envía a todos
     *                                        los usuarios de un rol (sin responsable
     *                                        fijo): se crea una fila por candidato en
     *                                        vez de reutilizar/anular una sola fila
     *                                        compartida por etapa+ciclo.
     */
    public function guardarFirmaDeEtapa(
        FlujoAprobacionEtapa $etapa,
        Empleado $empleado,
        array $attributes = [],
        ?DocumentoProyecto $documento = null,
        int $revisionCiclo = 1,
        bool $etapaCompartidaPorRol = false
    ): FirmaProyecto {
        $this->validarFirmaDeEtapa($etapa, $empleado, $documento, $revisionCiclo);

        $relation = $documento
            ? $documento->firma_documento()
            : $this->firma_proyecto();

        $etapa->loadMissing('rolRevisor');

        $matchKey = [
            'flujo_aprobacion_etapa_id' => $etapa->id,
            'revision_ciclo' => $revisionCiclo,
        ];

        if ($etapaCompartidaPorRol) {
            $matchKey['empleado_id'] = $empleado->id;
        }

        $firma = $relation->updateOrCreate(
            $matchKey,
            array_merge($attributes, [
                'empleado_id' => $empleado->id,
                'cargo_firma_id' => $etapa->cargo_firma_id,
                'flujo_aprobacion_id' => $etapa->flujo_aprobacion_id,
                'flujo_aprobacion_etapa_id' => $etapa->id,
                'orden_revision' => $etapa->orden,
                'etapa_codigo' => $etapa->codigo,
                'etapa_nombre' => $etapa->nombre,
                'rol_requerido' => $etapa->rolRevisor?->name,
                'responsable_usuario_id' => $etapaCompartidaPorRol ? null : $etapa->usuario_responsable_id,
                'revision_ciclo' => $revisionCiclo,
                // Se congela aquí para que editar el flujo después no cambie
                // si esta firma en curso puede reasignarse (ver puedeReasignar()).
                'requiere_asignacion' => (bool) $etapa->requiere_asignacion,
                'hash' => $attributes['hash'] ?? 'hash',
            ])
        );

        if (! $etapaCompartidaPorRol) {
            $this->anularFirmasPendientesDuplicadasDeEtapa($etapa->id, $revisionCiclo, $firma->id, $documento);
        }

        return $firma;
    }

    public function anularFirmasPendientesDuplicadasDeEtapa(
        int $flujoEtapaId,
        int $revisionCiclo = 1,
        ?int $firmaPrincipalId = null,
        ?DocumentoProyecto $documento = null
    ): void {
        $relation = $documento
            ? $documento->firma_documento()
            : $this->firma_proyecto();

        $relation
            ->where('flujo_aprobacion_etapa_id', $flujoEtapaId)
            ->where('revision_ciclo', $revisionCiclo)
            ->when($firmaPrincipalId, fn ($query) => $query->where('id', '!=', $firmaPrincipalId))
            ->where('estado_revision', 'Pendiente')
            ->update(['estado_revision' => 'Anulado']);
    }

    public function sincronizarFirmasDeEtapasDelFlujo(
        array $empleadosPorEtapa,
        string $proceso = self::FLUJO_INSCRIPCION,
        ?DocumentoProyecto $documento = null,
        int $revisionCiclo = 1
    ): Collection {
        if ($revisionCiclo < 1) {
            throw new \RuntimeException('El ciclo de revisión debe ser mayor o igual a 1.');
        }

        if ($documento && (int) $documento->proyecto_id !== (int) $this->id) {
            throw new \RuntimeException('El documento indicado no pertenece al proyecto actual.');
        }

        $etapas = $this->flujoEtapasActivasOrdenadas($proceso);

        if ($etapas->isEmpty()) {
            throw new \RuntimeException('No hay etapas activas configuradas para el proceso de inscripción.');
        }

        // Cada valor puede ser un solo empleado_id (responsable fijo o emisor
        // define destinatario) o un array de empleado_id (etapa enviada a
        // todos los usuarios de un rol: cualquiera de ellos puede resolverla).
        $empleadosNormalizados = $this->normalizarEmpleadosPorEtapa($empleadosPorEtapa);
        $etapaIds = $etapas->pluck('id')->map(fn ($id) => (int) $id);

        foreach (array_keys($empleadosNormalizados) as $etapaId) {
            if (! $etapaIds->contains((int) $etapaId)) {
                throw new \RuntimeException('La etapa indicada no pertenece al flujo del proyecto.');
            }
        }

        $todosLosEmpleadoIds = collect($empleadosNormalizados)->flatten()->unique()->values();
        $empleados = Empleado::whereIn('id', $todosLosEmpleadoIds)
            ->get()
            ->keyBy('id');

        foreach ($etapas as $etapa) {
            if (! $etapa->cargo_firma_id) {
                throw new \RuntimeException(sprintf('La etapa "%s" no tiene cargo de firma.', $etapa->nombre));
            }

            if (! array_key_exists((int) $etapa->id, $empleadosNormalizados) || empty($empleadosNormalizados[(int) $etapa->id])) {
                throw new \RuntimeException(sprintf('No se indicó un empleado para la etapa "%s".', $etapa->nombre));
            }

            foreach ((array) $empleadosNormalizados[(int) $etapa->id] as $empleadoId) {
                if (! $empleados->has($empleadoId)) {
                    throw new \RuntimeException(sprintf('El empleado indicado para la etapa "%s" no existe.', $etapa->nombre));
                }
            }
        }

        return DB::transaction(function () use ($etapas, $empleadosNormalizados, $empleados, $documento, $revisionCiclo): Collection {
            $firmas = collect();

            foreach ($etapas as $etapa) {
                $empleadoIds = (array) $empleadosNormalizados[(int) $etapa->id];
                $etapaCompartidaPorRol = count($empleadoIds) > 1;

                foreach ($empleadoIds as $empleadoId) {
                    $firmas->push($this->guardarFirmaDeEtapa(
                        $etapa,
                        $empleados->get($empleadoId),
                        [
                            'estado_revision' => 'Pendiente',
                            'firma_id' => null,
                            'sello_id' => null,
                            'fecha_firma' => null,
                        ],
                        $documento,
                        $revisionCiclo,
                        $etapaCompartidaPorRol
                    ));
                }
            }

            return $firmas->values();
        });
    }

    /**
     * Determina qué sub-flujo mostrarle al usuario como "progreso actual":
     * el de inscripción (por defecto) o, si ya se envió un Informe Intermedio
     * o Informe Final al flujo, el de ese documento (el más reciente que
     * tenga firmas generadas), sin importar si ya se completó o sigue
     * pendiente. Pensado para paneles/listados (ej. dashboard del docente).
     *
     * @return array{0: string, 1: ?DocumentoProyecto}
     */
    public function procesoActivoParaStepper(): array
    {
        $documentoActivo = $this->documentos()
            ->whereIn('tipo_documento', ['Informe Intermedio', 'Informe Final'])
            ->latest('id')
            ->get()
            ->first(fn (DocumentoProyecto $doc) => FirmaProyecto::query()
                ->where('firmable_type', DocumentoProyecto::class)
                ->where('firmable_id', $doc->id)
                ->exists());

        if ($documentoActivo) {
            return [self::procesoFlujoParaDocumento($documentoActivo->tipo_documento) ?? self::FLUJO_INSCRIPCION, $documentoActivo];
        }

        return [self::FLUJO_INSCRIPCION, null];
    }

    /**
     * Etapas de tipo "APROBACION" del proceso indicado, junto con la firma
     * vigente (del ciclo de revisión actual) para cada una, si existe. Es la
     * fuente de verdad para pintar los cuadros de firma en las fichas/PDF: a
     * diferencia de las relaciones firma_coodinador_proyecto/firma_proyecto_*
     * (fijas a 6 cargos hardcodeados), esto se adapta a cualquier flujo,
     * mostrando exactamente las etapas que ese flujo definió como firmantes.
     *
     * @return Collection<int, array{etapa: FlujoAprobacionEtapa, firma: ?FirmaProyecto, adoptada_antes: bool}>
     */
    public function firmasParaFicha(string $proceso = self::FLUJO_INSCRIPCION, ?DocumentoProyecto $documento = null): Collection
    {
        $etapasFirmantes = $this->flujoEtapasActivasOrdenadas($proceso)
            ->filter(fn (FlujoAprobacionEtapa $etapa) => $etapa->tipo_etapa === 'APROBACION' && $etapa->cargo_firma_id)
            ->values();

        if ($etapasFirmantes->isEmpty()) {
            return collect();
        }

        $flujoId = $this->flujoAprobacion?->id ?? $this->flujo_aprobacion_id;
        $firmasPorEtapa = $flujoId
            ? $this->relacionFirmasDeEtapas($documento)
                ->where('flujo_aprobacion_id', $flujoId)
                ->whereIn('flujo_aprobacion_etapa_id', $etapasFirmantes->pluck('id'))
                ->whereNull('deleted_at')
                ->where('estado_revision', '!=', 'Anulado')
                ->orderByDesc('revision_ciclo')
                ->orderByDesc('id')
                ->get()
                ->groupBy('flujo_aprobacion_etapa_id')
            : collect();

        $adopcion = ! $documento && $proceso === self::FLUJO_INSCRIPCION
            ? $this->adopcionFlujoLegacy()->first()
            : null;

        return $etapasFirmantes->map(fn (FlujoAprobacionEtapa $etapa) => [
            'etapa' => $etapa,
            'firma' => $firmasPorEtapa->get($etapa->id)?->first(),
            'adoptada_antes' => $adopcion !== null && (
                $adopcion->modo === \App\Services\Proyecto\ProyectoLegacyWorkflowAdoptionService::MODO_COMPLETADO
                || ($adopcion->orden_inicio !== null && (int) $etapa->orden < (int) $adopcion->orden_inicio)
            ),
        ]);
    }

    /**
     * Todas las etapas activas del proceso con su revisión vigente. A diferencia
     * de firmasParaFicha(), incluye etapas de revisión y de aprobación porque se
     * utiliza para representar el progreso completo del flujo en pantalla.
     *
     * @return Collection<int, array{etapa: FlujoAprobacionEtapa, firma: ?FirmaProyecto, adoptada_antes: bool}>
     */
    public function etapasParaStepper(string $proceso = self::FLUJO_INSCRIPCION, ?DocumentoProyecto $documento = null): Collection
    {
        $etapas = $this->flujoEtapasActivasOrdenadas($proceso)->values();

        if ($etapas->isEmpty()) {
            return collect();
        }

        $flujoId = $this->flujoAprobacion?->id ?? $this->flujo_aprobacion_id;
        $firmasPorEtapa = $flujoId
            ? $this->relacionFirmasDeEtapas($documento)
                ->where('flujo_aprobacion_id', $flujoId)
                ->whereIn('flujo_aprobacion_etapa_id', $etapas->pluck('id'))
                ->whereNull('deleted_at')
                ->where('estado_revision', '!=', 'Anulado')
                ->orderByDesc('revision_ciclo')
                ->orderByDesc('id')
                ->get()
                ->groupBy('flujo_aprobacion_etapa_id')
            : collect();

        $adopcion = ! $documento && $proceso === self::FLUJO_INSCRIPCION
            ? $this->adopcionFlujoLegacy()->first()
            : null;

        return $etapas->map(fn (FlujoAprobacionEtapa $etapa) => [
            'etapa' => $etapa,
            'firma' => $firmasPorEtapa->get($etapa->id)?->first(),
            'adoptada_antes' => $adopcion !== null && (
                $adopcion->modo === \App\Services\Proyecto\ProyectoLegacyWorkflowAdoptionService::MODO_COMPLETADO
                || ($adopcion->orden_inicio !== null && (int) $etapa->orden < (int) $adopcion->orden_inicio)
            ),
        ]);
    }

    public function firmasDeEtapasDelFlujo(
        int $flujoAprobacionId,
        int $revisionCiclo = 1,
        ?DocumentoProyecto $documento = null
    ): Collection {
        $this->validarParametrosFirmasDeEtapas($flujoAprobacionId, $revisionCiclo, $documento);

        return $this->relacionFirmasDeEtapas($documento)
            ->where('flujo_aprobacion_id', $flujoAprobacionId)
            ->where('revision_ciclo', $revisionCiclo)
            ->whereNotNull('flujo_aprobacion_etapa_id')
            ->whereNull('deleted_at')
            ->orderBy('orden_revision')
            ->orderBy('id')
            ->get();
    }

    public function firmaActualDeEtapasDelFlujo(
        int $flujoAprobacionId,
        int $revisionCiclo = 1,
        ?DocumentoProyecto $documento = null
    ): ?FirmaProyecto {
        foreach ($this->firmasDeEtapasDelFlujo($flujoAprobacionId, $revisionCiclo, $documento) as $firma) {
            if (in_array($firma->estado_revision, ['Aprobado', 'Anulado'], true)) {
                continue;
            }

            if ($firma->estado_revision === 'Pendiente') {
                return $firma;
            }

            if ($firma->estado_revision === 'Rechazado') {
                return null;
            }
        }

        return null;
    }

    public function firmaEsActualEnFlujoPorEtapa(FirmaProyecto $firma): bool
    {
        if (! $this->firmaPerteneceAlFlujoPorEtapaDelProyecto($firma) || $firma->estado_revision !== 'Pendiente') {
            return false;
        }

        $documento = $this->documentoDeFirmaDelProyecto($firma);

        if ($firma->firmable_type === DocumentoProyecto::class && ! $documento) {
            return false;
        }

        $firmaActual = $this->firmaActualDeEtapasDelFlujo(
            (int) $firma->flujo_aprobacion_id,
            (int) $firma->revision_ciclo,
            $documento
        );

        if (! $firmaActual) {
            return false;
        }

        // Cuando una etapa se manda a todos los usuarios de un rol (sin
        // responsable fijo), puede haber varias firmas Pendiente "candidatas"
        // para la misma etapa/orden: cualquiera de ellas es "la actual", no
        // solo la primera encontrada por id.
        return (int) $firmaActual->flujo_aprobacion_etapa_id === (int) $firma->flujo_aprobacion_etapa_id
            && (int) $firmaActual->orden_revision === (int) $firma->orden_revision;
    }

    public function siguienteFirmaDeEtapa(FirmaProyecto $firma): ?FirmaProyecto
    {
        if (! $this->firmaPerteneceAlFlujoPorEtapaDelProyecto($firma) || blank($firma->orden_revision)) {
            return null;
        }

        $documento = $this->documentoDeFirmaDelProyecto($firma);

        if ($firma->firmable_type === DocumentoProyecto::class && ! $documento) {
            return null;
        }

        return $this->relacionFirmasDeEtapas($documento)
            ->where('flujo_aprobacion_id', $firma->flujo_aprobacion_id)
            ->where('revision_ciclo', $firma->revision_ciclo)
            ->whereNotNull('flujo_aprobacion_etapa_id')
            ->whereNull('deleted_at')
            ->where('estado_revision', '!=', 'Anulado')
            ->where('orden_revision', '>', $firma->orden_revision)
            ->orderBy('orden_revision')
            ->orderBy('id')
            ->first();
    }

    public function firmasDeEtapasCompletadas(
        int $flujoAprobacionId,
        int $revisionCiclo = 1,
        ?DocumentoProyecto $documento = null
    ): bool {
        $firmas = $this->firmasDeEtapasDelFlujo($flujoAprobacionId, $revisionCiclo, $documento);

        if ($firmas->isEmpty()) {
            return false;
        }

        if ($firmas->contains(fn (FirmaProyecto $firma) => in_array($firma->estado_revision, ['Pendiente', 'Rechazado'], true))) {
            return false;
        }

        return $firmas->contains(fn (FirmaProyecto $firma) => $firma->estado_revision === 'Aprobado')
            && $firmas->every(fn (FirmaProyecto $firma) => in_array($firma->estado_revision, ['Aprobado', 'Anulado'], true));
    }

    public function crearNuevoCicloDesdeFirmaRechazada(
        FirmaProyecto $firmaRechazada,
        array $empleadosPorEtapa
    ): Collection {
        return DB::transaction(function () use ($firmaRechazada, $empleadosPorEtapa): Collection {
            self::query()->whereKey($this->id)->lockForUpdate()->firstOrFail();

            $documento = $this->documentoDeFirmaDelProyecto($firmaRechazada);

            if ($firmaRechazada->firmable_type === DocumentoProyecto::class) {
                if (! $documento) {
                    throw new \RuntimeException('La firma no pertenece al proyecto indicado.');
                }

                DocumentoProyecto::query()->whereKey($documento->id)->lockForUpdate()->firstOrFail();
            }

            $firmaBloqueada = FirmaProyecto::query()
                ->whereKey($firmaRechazada->id)
                ->lockForUpdate()
                ->first();

            if (! $firmaBloqueada) {
                throw new \RuntimeException('La firma indicada no corresponde a una etapa rechazada.');
            }

            $documento = $this->documentoDeFirmaDelProyecto($firmaBloqueada);
            $this->validarFirmaRechazadaParaNuevoCiclo($firmaBloqueada, $documento);

            $firmasCiclo = $this->relacionFirmasDeEtapas($documento)
                ->where('flujo_aprobacion_id', $firmaBloqueada->flujo_aprobacion_id)
                ->where('revision_ciclo', $firmaBloqueada->revision_ciclo)
                ->whereNotNull('flujo_aprobacion_etapa_id')
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->orderBy('orden_revision')
                ->orderBy('id')
                ->get();

            $this->validarFirmaRechazadaParaNuevoCiclo($firmaBloqueada->fresh(), $documento, $firmasCiclo);

            $nuevoCiclo = (int) $firmaBloqueada->revision_ciclo + 1;

            if ($this->existenFirmasDeCicloPorEtapa((int) $firmaBloqueada->flujo_aprobacion_id, $nuevoCiclo, $documento)) {
                throw new \RuntimeException('Ya existe el siguiente ciclo de revisión para este registro.');
            }

            $firmasBase = $this->firmasBaseParaNuevoCicloDesdeRechazo($firmaBloqueada->fresh(), $firmasCiclo);
            $etapasCicloAnterior = $firmasCiclo
                ->pluck('flujo_aprobacion_etapa_id')
                ->map(fn ($id): int => (int) $id);

            foreach (array_keys($empleadosPorEtapa) as $etapaAsignadaId) {
                if (! $etapasCicloAnterior->contains((int) $etapaAsignadaId)) {
                    throw new \RuntimeException('Se indicó una asignación para una etapa que no pertenece al nuevo ciclo.');
                }
            }

            $etapasReanudadas = $firmasBase
                ->pluck('flujo_aprobacion_etapa_id')
                ->map(fn ($id): int => (int) $id)
                ->flip();
            $asignacionesReanudadas = collect($empleadosPorEtapa)
                ->filter(fn ($empleadoId, $etapaId): bool => $etapasReanudadas->has((int) $etapaId))
                ->all();
            $empleados = $this->validarAsignacionesParaNuevoCiclo($firmasBase, $asignacionesReanudadas);
            $cantidadCicloAnterior = $firmasCiclo->count();
            $relation = $this->relacionFirmasDeEtapas($documento);

            $firmasCreadas = $firmasBase
                ->map(function (FirmaProyecto $firmaBase) use ($relation, $empleados, $nuevoCiclo): FirmaProyecto {
                    return $relation->create([
                        'empleado_id' => $empleados->get((int) $firmaBase->flujo_aprobacion_etapa_id)->id,
                        'cargo_firma_id' => $firmaBase->cargo_firma_id,
                        'flujo_aprobacion_id' => $firmaBase->flujo_aprobacion_id,
                        'flujo_aprobacion_etapa_id' => $firmaBase->flujo_aprobacion_etapa_id,
                        'orden_revision' => $firmaBase->orden_revision,
                        'etapa_codigo' => $firmaBase->etapa_codigo,
                        'etapa_nombre' => $firmaBase->etapa_nombre,
                        'rol_requerido' => $firmaBase->rol_requerido,
                        'responsable_usuario_id' => $empleados->get((int) $firmaBase->flujo_aprobacion_etapa_id)->user_id,
                        'requiere_asignacion' => $firmaBase->requiere_asignacion,
                        'revision_ciclo' => $nuevoCiclo,
                        'estado_revision' => 'Pendiente',
                        'firma_id' => null,
                        'sello_id' => null,
                        'fecha_firma' => null,
                        'hash' => (string) Str::uuid(),
                    ]);
                })
                ->sortBy([
                    ['orden_revision', 'asc'],
                    ['id', 'asc'],
                ])
                ->values();

            $this->validarNuevoCicloCreadoDesdeRechazo(
                $firmaBloqueada->fresh(),
                $firmasBase,
                $firmasCreadas,
                $nuevoCiclo,
                $cantidadCicloAnterior,
                $documento
            );

            return $firmasCreadas;
        });
    }

    protected function validarFirmaRechazadaParaNuevoCiclo(
        FirmaProyecto $firmaRechazada,
        ?DocumentoProyecto $documento = null,
        ?Collection $firmasCiclo = null
    ): void {
        if (! $firmaRechazada->exists
            || filled($firmaRechazada->deleted_at)
            || ! $firmaRechazada->usaFlujoPorEtapa()
            || $firmaRechazada->estado_revision !== 'Rechazado'
            || ! $firmaRechazada->flujo_aprobacion_id
            || ! $firmaRechazada->flujo_aprobacion_etapa_id
            || (int) $firmaRechazada->revision_ciclo < 1
            || blank($firmaRechazada->orden_revision)
            || ! in_array($firmaRechazada->firmable_type, [self::class, DocumentoProyecto::class], true)
        ) {
            throw new \RuntimeException('La firma indicada no corresponde a una etapa rechazada.');
        }

        if (! $this->firmaPerteneceAlFlujoPorEtapaDelProyecto($firmaRechazada)) {
            throw new \RuntimeException('La firma no pertenece al proyecto indicado.');
        }

        if ($firmaRechazada->firmable_type === DocumentoProyecto::class && ! $documento) {
            throw new \RuntimeException('La firma no pertenece al proyecto indicado.');
        }

        if ($this->existenFirmasDeCicloPorEtapa(
            (int) $firmaRechazada->flujo_aprobacion_id,
            (int) $firmaRechazada->revision_ciclo + 1,
            $documento
        )) {
            throw new \RuntimeException('Ya existe el siguiente ciclo de revisión para este registro.');
        }

        $ultimoCiclo = $this->ultimoCicloDeFirmasPorEtapa((int) $firmaRechazada->flujo_aprobacion_id, $documento);

        if ((int) $firmaRechazada->revision_ciclo !== $ultimoCiclo) {
            throw new \RuntimeException('La firma rechazada no pertenece al último ciclo de revisión.');
        }

        $firmasCiclo = $firmasCiclo ?: $this->firmasDeEtapasDelFlujo(
            (int) $firmaRechazada->flujo_aprobacion_id,
            (int) $firmaRechazada->revision_ciclo,
            $documento
        );

        if ($firmasCiclo->where('estado_revision', 'Rechazado')->count() !== 1) {
            throw new \RuntimeException('El ciclo de revisión contiene más de una etapa rechazada.');
        }
    }

    protected function firmasBaseParaNuevoCicloDesdeRechazo(FirmaProyecto $firmaRechazada, Collection $firmasCiclo): Collection
    {
        $plan = app(WorkflowResumptionPolicy::class)->plan(
            $firmasCiclo
                ->reject(fn (FirmaProyecto $firma): bool => $firma->estado_revision === 'Anulado')
                // Una etapa enviada a "todos los usuarios del rol" puede tener
                // varias firmas Pendiente candidatas para la misma etapa (aún
                // no alcanzada): WorkflowResumptionPolicy asume una firma por
                // etapa, así que se colapsa a una sola representante.
                ->unique(fn (FirmaProyecto $firma): int => (int) $firma->flujo_aprobacion_etapa_id)
                ->map(fn (FirmaProyecto $firma): array => [
                    'stage_id' => (int) $firma->flujo_aprobacion_etapa_id,
                    'order' => (int) $firma->orden_revision,
                    'status' => match ($firma->estado_revision) {
                        'Aprobado' => 'APPROVED',
                        'Rechazado' => 'REJECTED',
                        'Pendiente' => 'PENDING',
                        default => 'INVALID',
                    },
                    'source' => $firma,
                ])
                ->values()
        );

        $firmasBase = $plan->stages->pluck('source')->values();

        if ((int) $plan->rejectedStage['source']->id !== (int) $firmaRechazada->id) {
            throw new \RuntimeException('No se pudo preparar de forma segura el nuevo ciclo de revisión.');
        }

        return $firmasBase;
    }

    protected function validarAsignacionesParaNuevoCiclo(Collection $firmasBase, array $empleadosPorEtapa): Collection
    {
        $etapasRequeridas = $firmasBase
            ->pluck('flujo_aprobacion_etapa_id')
            ->map(fn ($id): int => (int) $id)
            ->values();

        $asignaciones = collect($empleadosPorEtapa)
            ->mapWithKeys(fn ($empleadoId, $etapaId): array => [(int) $etapaId => $empleadoId]);

        foreach ($asignaciones->keys() as $etapaId) {
            if (! $etapasRequeridas->contains((int) $etapaId)) {
                throw new \RuntimeException('Se indicó una asignación para una etapa que no pertenece al nuevo ciclo.');
            }
        }

        $empleados = collect();

        foreach ($firmasBase as $firmaBase) {
            $etapaId = (int) $firmaBase->flujo_aprobacion_etapa_id;
            $etapaNombre = $firmaBase->etapa_nombre ?: $firmaBase->etapa_codigo ?: $etapaId;

            if (! $asignaciones->has($etapaId)) {
                throw new \RuntimeException(sprintf('No se indicó un empleado para la etapa "%s".', $etapaNombre));
            }

            $empleadoId = $asignaciones->get($etapaId);

            if (! is_numeric($empleadoId) || (int) $empleadoId < 1) {
                throw new \RuntimeException(sprintf('El empleado indicado para la etapa "%s" no existe.', $etapaNombre));
            }

            $empleado = Empleado::withTrashed()->with('user')->find((int) $empleadoId);

            if (! $empleado || $empleado->trashed()) {
                throw new \RuntimeException(sprintf('El empleado indicado para la etapa "%s" no existe.', $etapaNombre));
            }

            $usuarioElegible = app(WorkflowResumptionPolicy::class)->eligibleRecipient(
                $empleado->user,
                $firmaBase->rol_requerido,
                true
            );

            if (! $usuarioElegible || (int) $usuarioElegible->empleado?->id !== (int) $empleado->id) {
                throw new \RuntimeException(sprintf(
                    'El revisor anterior de la etapa "%s" ya no es elegible; seleccione un reemplazo válido.',
                    $etapaNombre
                ));
            }

            $empleados->put($etapaId, $empleado);
        }

        return $empleados;
    }

    protected function validarNuevoCicloCreadoDesdeRechazo(
        FirmaProyecto $firmaRechazada,
        Collection $firmasBase,
        Collection $firmasCreadas,
        int $nuevoCiclo,
        int $cantidadCicloAnterior,
        ?DocumentoProyecto $documento = null
    ): void {
        $firmasNuevoCiclo = $this->firmasDeEtapasDelFlujo(
            (int) $firmaRechazada->flujo_aprobacion_id,
            $nuevoCiclo,
            $documento
        );

        if ($firmasNuevoCiclo->count() !== $firmasBase->count()
            || $firmasCreadas->count() !== $firmasBase->count()
            || $firmasNuevoCiclo->contains(fn (FirmaProyecto $firma): bool => $firma->estado_revision !== 'Pendiente')
            || $firmasNuevoCiclo->contains(fn (FirmaProyecto $firma): bool => (int) $firma->revision_ciclo !== $nuevoCiclo)
            || (int) $firmasNuevoCiclo->first()?->flujo_aprobacion_etapa_id !== (int) $firmasBase->first()?->flujo_aprobacion_etapa_id
        ) {
            throw new \RuntimeException('No se pudo preparar de forma segura el nuevo ciclo de revisión.');
        }

        $firmaActual = $this->firmaActualDeEtapasDelFlujo(
            (int) $firmaRechazada->flujo_aprobacion_id,
            $nuevoCiclo,
            $documento
        );

        if ((int) $firmaActual?->id !== (int) $firmasNuevoCiclo->first()?->id
            || $this->firmasDeEtapasCompletadas((int) $firmaRechazada->flujo_aprobacion_id, $nuevoCiclo, $documento)
        ) {
            throw new \RuntimeException('No se pudo preparar de forma segura el nuevo ciclo de revisión.');
        }

        $cantidadActualCicloAnterior = $this->firmasDeEtapasDelFlujo(
            (int) $firmaRechazada->flujo_aprobacion_id,
            (int) $firmaRechazada->revision_ciclo,
            $documento
        )->count();

        if ($cantidadActualCicloAnterior !== $cantidadCicloAnterior) {
            throw new \RuntimeException('No se pudo preparar de forma segura el nuevo ciclo de revisión.');
        }
    }

    protected function ultimoCicloDeFirmasPorEtapa(int $flujoAprobacionId, ?DocumentoProyecto $documento = null): int
    {
        return (int) $this->relacionFirmasDeEtapas($documento)
            ->where('flujo_aprobacion_id', $flujoAprobacionId)
            ->whereNotNull('flujo_aprobacion_etapa_id')
            ->whereNull('deleted_at')
            ->max('revision_ciclo');
    }

    protected function existenFirmasDeCicloPorEtapa(
        int $flujoAprobacionId,
        int $revisionCiclo,
        ?DocumentoProyecto $documento = null
    ): bool {
        return $this->relacionFirmasDeEtapas($documento)
            ->where('flujo_aprobacion_id', $flujoAprobacionId)
            ->where('revision_ciclo', $revisionCiclo)
            ->whereNotNull('flujo_aprobacion_etapa_id')
            ->whereNull('deleted_at')
            ->exists();
    }

    private function validarFirmaDeEtapa(
        FlujoAprobacionEtapa $etapa,
        Empleado $empleado,
        ?DocumentoProyecto $documento,
        int $revisionCiclo
    ): void {
        if (! $etapa->exists) {
            throw new \RuntimeException('La etapa indicada no existe.');
        }

        if (! $etapa->flujo_aprobacion_id) {
            throw new \RuntimeException('La etapa indicada no pertenece a un flujo.');
        }

        if (! $etapa->activo) {
            throw new \RuntimeException(sprintf('La etapa "%s" no está activa.', $etapa->nombre));
        }

        if (! $etapa->cargo_firma_id) {
            throw new \RuntimeException(sprintf('La etapa "%s" no tiene cargo de firma.', $etapa->nombre));
        }

        if (! $etapa->cargoFirma()->exists()) {
            throw new \RuntimeException(sprintf(
                'No se encontró el cargo de firma configurado para la etapa "%s".',
                $etapa->nombre
            ));
        }

        if ($revisionCiclo < 1) {
            throw new \RuntimeException('El ciclo de revisión debe ser mayor o igual a 1.');
        }

        if (! $empleado->exists || $empleado->trashed()) {
            throw new \RuntimeException(sprintf('El empleado indicado para la etapa "%s" no existe.', $etapa->nombre));
        }

        if ($documento && (int) $documento->proyecto_id !== (int) $this->id) {
            throw new \RuntimeException('El documento indicado no pertenece al proyecto actual.');
        }
    }

    private function normalizarEmpleadosPorEtapa(array $empleadosPorEtapa): array
    {
        $normalizados = [];

        foreach ($empleadosPorEtapa as $etapaId => $empleado) {
            if (is_array($empleado) || $empleado instanceof Collection) {
                $normalizados[(int) $etapaId] = collect($empleado)
                    ->map(fn ($item) => $item instanceof Empleado ? (int) $item->id : (int) $item)
                    ->unique()
                    ->values()
                    ->all();

                continue;
            }

            $normalizados[(int) $etapaId] = $empleado instanceof Empleado
                ? (int) $empleado->id
                : (int) $empleado;
        }

        return $normalizados;
    }

    private function validarParametrosFirmasDeEtapas(
        int $flujoAprobacionId,
        int $revisionCiclo,
        ?DocumentoProyecto $documento = null
    ): void {
        if ($flujoAprobacionId < 1) {
            throw new \RuntimeException('El flujo de aprobación indicado no es válido.');
        }

        if ($revisionCiclo < 1) {
            throw new \RuntimeException('El ciclo de revisión debe ser mayor o igual a 1.');
        }

        if ($documento && (int) $documento->proyecto_id !== (int) $this->id) {
            throw new \RuntimeException('El documento indicado no pertenece al proyecto actual.');
        }
    }

    private function relacionFirmasDeEtapas(?DocumentoProyecto $documento = null)
    {
        return $documento
            ? $documento->firma_documento()
            : $this->firma_proyecto();
    }

    private function firmaPerteneceAlFlujoPorEtapaDelProyecto(FirmaProyecto $firma): bool
    {
        if (! $firma->exists || filled($firma->deleted_at)) {
            return false;
        }

        if (! $firma->usaFlujoPorEtapa()) {
            return false;
        }

        if (! $firma->flujo_aprobacion_id || ! $firma->revision_ciclo) {
            return false;
        }

        if ($firma->firmable_type === self::class) {
            return (int) $firma->firmable_id === (int) $this->id;
        }

        if ($firma->firmable_type === DocumentoProyecto::class) {
            return (bool) $this->documentoDeFirmaDelProyecto($firma);
        }

        return false;
    }

    private function documentoDeFirmaDelProyecto(FirmaProyecto $firma): ?DocumentoProyecto
    {
        if ($firma->firmable_type !== DocumentoProyecto::class) {
            return null;
        }

        return DocumentoProyecto::query()
            ->whereKey($firma->firmable_id)
            ->where('proyecto_id', $this->id)
            ->first();
    }

    public function anularFirmasPendientesDuplicadasDeCargo(
        int $cargoFirmaId,
        ?int $firmaPrincipalId = null,
        ?DocumentoProyecto $documento = null
    ): void
    {
        $relation = $documento
            ? $documento->firma_documento()
            : $this->firma_proyecto();

        $relation
            ->where('cargo_firma_id', $cargoFirmaId)
            ->when($firmaPrincipalId, fn ($query) => $query->where('id', '!=', $firmaPrincipalId))
            ->where('estado_revision', 'Pendiente')
            ->update(['estado_revision' => 'Anulado']);
    }

    public function sincronizarFirmasDelFlujo(
        string $proceso = self::FLUJO_INSCRIPCION,
        ?DocumentoProyecto $documento = null
    ): void
    {
        $etapas = $this->flujoEtapasOrdenadas($proceso);
        $firmaRelation = fn () => $documento
            ? $documento->firma_documento()
            : $this->firma_proyecto();

        foreach ($etapas as $etapa) {
            if (! $etapa->activo || ! $etapa->cargo_firma_id) {
                continue;
            }

            $firmaExistente = $firmaRelation()
                ->where('cargo_firma_id', $etapa->cargo_firma_id)
                ->first();

            if ($firmaExistente?->estado_revision === 'Aprobado') {
                $this->anularFirmasPendientesDuplicadasDeCargo($etapa->cargo_firma_id, $firmaExistente->id, $documento);
                continue;
            }

            $usuario = $etapa->usuarioResponsable;

            if (! $usuario && $etapa->rolRevisor?->name) {
                $usuario = User::role($etapa->rolRevisor->name)
                    ->whereHas('empleado')
                    ->with('empleado')
                    ->orderBy('name')
                    ->first();
            }

            $empleado = $usuario?->empleado;

            if (! $empleado) {
                continue;
            }

            $this->guardarFirmaDeCargo($etapa->cargo_firma_id, $empleado, [
                'estado_revision' => 'Pendiente',
                'firma_id' => null,
                'sello_id' => null,
                'fecha_firma' => null,
            ], $documento);
        }
    }

    public function registrarDocumentoDesdeFlujo(
        string $tipoDocumento,
        string $path,
        Empleado $empleado,
        array $usuariosElegidosPorEtapa = []
    ): DocumentoProyecto
    {
        $proceso = self::procesoFlujoParaDocumento($tipoDocumento);

        if (! $proceso) {
            throw new \RuntimeException('El tipo de documento no tiene un proceso de flujo configurable.');
        }

        $etapas = $this->flujoEtapasOrdenadas($proceso)
            ->filter(fn ($etapa) => $etapa->activo && $etapa->cargo_firma_id)
            ->values();

        if ($etapas->isEmpty()) {
            throw new \RuntimeException(
                $proceso === self::FLUJO_INFORME_INTERMEDIO
                    ? 'No hay etapas configuradas para Informe Intermedio.'
                    : 'No hay etapas configuradas para Cierre de Proyecto.'
            );
        }

        $primerEstadoId = $etapas->first()?->cargoFirma?->tipo_estado_id;

        if (! $primerEstadoId) {
            throw new \RuntimeException('La primera etapa configurada no tiene un estado asociado.');
        }

        $empleadosPorEtapa = app(ProyectoWorkflowService::class)
            ->resolverEmpleados($this, $proceso, $usuariosElegidosPorEtapa);

        return DB::transaction(function () use ($tipoDocumento, $path, $empleado, $proceso, $etapas, $primerEstadoId, $empleadosPorEtapa) {
            self::query()->whereKey($this->id)->lockForUpdate()->firstOrFail();

            $documentos = $this->documentos()
                ->where('tipo_documento', $tipoDocumento)
                ->lockForUpdate()
                ->get();

            if ($documentos->count() > 1) {
                throw new \RuntimeException('Existe más de un documento del mismo tipo y no puede iniciarse el flujo de forma segura.');
            }

            $documento = $documentos->first();

            if ($documento && ($documento->firma_documento()->exists() || $documento->estado_documento()->exists())) {
                throw new \RuntimeException('El documento ya inició su flujo. Para reenviarlo debe encontrarse en subsanación.');
            }

            if ($documento) {
                $documento->update(['documento_url' => $path]);
            } else {
                $documento = $this->documentos()->create([
                    'tipo_documento' => $tipoDocumento,
                    'documento_url' => $path,
                ]);
            }

            $firmasCreadas = $this->sincronizarFirmasDeEtapasDelFlujo($empleadosPorEtapa, $proceso, $documento, 1);

            if ($firmasCreadas->count() !== $etapas->count()) {
                throw new \RuntimeException('No se pudieron crear todas las firmas del flujo. Revise roles y responsables configurados.');
            }

            $documento->estado_documento()->create([
                'empleado_id' => $empleado->id,
                'tipo_estado_id' => $primerEstadoId,
                'fecha' => now(),
                'comentario' => $tipoDocumento === 'Informe Final'
                    ? '[Cierre INF-001] Informe final enviado a revisión.'
                    : 'Documento creado',
            ]);

            return $documento;
        });
    }

    public function agregarEstado(
        Empleado $empleado,
        int $tipoEstadoId,
        string $comentario = "Comentario"
    ) {
        $this->estado_proyecto()->create([
            'empleado_id' => $empleado->id,
            'tipo_estado_id' => $tipoEstadoId,
            'fecha' => now(),
            'comentario' => $comentario,
        ]);
    }

    public function agregarEstadoByName(
        Empleado $empleado,
        string $tipoEstadoNombre,
        string $comentario = "Comentario"
    ) {
        $tipoEstado = TipoEstado::where('nombre', $tipoEstadoNombre)->first();

        if (!$tipoEstado) {
            throw new \Exception("Tipo de estado '{$tipoEstadoNombre}' no encontrado.");
        }

        $this->agregarEstado($empleado, $tipoEstado->id, $comentario);
    }

    public function proyectoIsInEstadoByName(string $estadoNombre): bool
    {
        return $this->estado->tipoestado->nombre === $estadoNombre;
    }

    public function proyectoIsInAnyEstados(array $estadoNombres): bool
    {
        return in_array($this->obtenerUltimoEstado()
            ->tipo_estado_id, TipoEstado::whereIn('nombre', $estadoNombres)
            ->pluck('id')->toArray());
    }

    /**
     * Guardar contenido nunca constituye una transición del flujo. Un registro
     * nuevo nace como Borrador; uno existente conserva exactamente su estado.
     */
    public function estadoDespuesDeGuardar(): string
    {
        return $this->estado?->tipoestado?->nombre ?? 'Borrador';
    }

    public function firmaRechazadaSubsanacionVigente(): ?FirmaProyecto
    {
        $firma = $this->firma_proyecto()
            ->whereNull('deleted_at')
            ->where('estado_revision', 'Rechazado')
            ->whereNotNull('flujo_aprobacion_id')
            ->whereNotNull('flujo_aprobacion_etapa_id')
            ->whereNotNull('revision_ciclo')
            ->orderByDesc('revision_ciclo')
            ->orderByDesc('id')
            ->first();

        if (! $firma) {
            return null;
        }

        $ultimoCiclo = $this->firma_proyecto()
            ->whereNull('deleted_at')
            ->where('flujo_aprobacion_id', $firma->flujo_aprobacion_id)
            ->whereNotNull('flujo_aprobacion_etapa_id')
            ->max('revision_ciclo');

        if ($ultimoCiclo === null || (int) $ultimoCiclo !== (int) $firma->revision_ciclo) {
            return null;
        }

        $rechazadas = $this->firma_proyecto()
            ->whereNull('deleted_at')
            ->where('flujo_aprobacion_id', $firma->flujo_aprobacion_id)
            ->where('revision_ciclo', $firma->revision_ciclo)
            ->whereNotNull('flujo_aprobacion_etapa_id')
            ->where('estado_revision', 'Rechazado')
            ->count();

        return $rechazadas === 1 ? $firma : null;
    }

    public function estadoSubsanacionVigente(): ?EstadoProyecto
    {
        return $this->estado_proyecto()
            ->whereHas('tipoestado', fn ($query) => $query->where('nombre', 'Subsanacion'))
            ->whereNotNull('comentario')
            ->where('comentario', '!=', '')
            ->latest('id')
            ->first();
    }

    public function tieneEvidenciaSubsanacionActiva(): bool
    {
        return $this->firmaRechazadaSubsanacionVigente() !== null
            && $this->estadoSubsanacionVigente() !== null;
    }

    public function estaEnSubsanacionActiva(): bool
    {
        return $this->estadoDespuesDeGuardar() === 'Subsanacion'
            && $this->tieneEvidenciaSubsanacionActiva();
    }

    public function puedeRepararSubsanacionDegradada(): bool
    {
        if ($this->estadoDespuesDeGuardar() !== 'Borrador' || ! $this->tieneEvidenciaSubsanacionActiva()) {
            return false;
        }

        $ultimosEstados = $this->estado_proyecto()
            ->with('tipoestado')
            ->latest('id')
            ->limit(2)
            ->get();

        if ($ultimosEstados->count() !== 2) {
            return false;
        }

        $estadoBorrador = $ultimosEstados->first();
        $estadoSubsanacion = $ultimosEstados->last();

        return $estadoBorrador?->tipoestado?->nombre === 'Borrador'
            && Str::contains(Str::lower((string) $estadoBorrador->comentario), 'guardado como borrador')
            && $estadoSubsanacion?->tipoestado?->nombre === 'Subsanacion'
            && trim((string) $estadoSubsanacion->comentario) !== '';
    }

    public function restaurarSubsanacionDegradada(User $actor): void
    {
        if (! $this->puedeRepararSubsanacionDegradada() || ! $actor->empleado) {
            throw new \RuntimeException(
                'Se detectó una inconsistencia de subsanación que no puede repararse automáticamente de forma segura.'
            );
        }

        $motivo = trim((string) $this->estadoSubsanacionVigente()?->comentario);
        $tipoEstadoId = TipoEstado::query()->where('nombre', 'Subsanacion')->value('id');

        if (! $tipoEstadoId || $motivo === '') {
            throw new \RuntimeException(
                'Se detectó una inconsistencia de subsanación que requiere restauración administrativa.'
            );
        }

        DB::transaction(function () use ($actor, $motivo, $tipoEstadoId): void {
            $this->estado_proyecto()->update(['es_actual' => false]);

            EstadoProyecto::withoutEvents(function () use ($actor, $motivo, $tipoEstadoId): void {
                $this->estado_proyecto()->create([
                    'empleado_id' => $actor->empleado->id,
                    'tipo_estado_id' => $tipoEstadoId,
                    'fecha' => now(),
                    'comentario' => 'Estado Subsanación restaurado de forma segura. Motivo original: '.$motivo,
                    'es_actual' => true,
                ]);
            });

            activity('Proyecto')
                ->performedOn($this)
                ->causedBy($actor)
                ->withProperties([
                    'correccion' => 'Borrador a Subsanacion',
                    'firma_rechazada_id' => $this->firmaRechazadaSubsanacionVigente()?->id,
                ])
                ->log('Se corrigió un estado degradado por un guardado durante subsanación.');
        });

        $this->unsetRelation('estado');
    }

    public function detalleSubsanacionActiva(): ?array
    {
        $firma = $this->firmaRechazadaSubsanacionVigente();
        $estado = $this->estadoSubsanacionVigente();

        if (! $firma || ! $estado) {
            return null;
        }

        $firma->loadMissing(['empleado', 'flujoEtapa']);

        return [
            'motivo' => $estado->comentario,
            'rechazado_por' => $firma->empleado?->nombre_completo ?? 'Revisor no disponible',
            'fecha' => $firma->fecha_firma ?? $estado->fecha ?? $estado->created_at,
            'etapa' => $firma->etapa_nombre ?: $firma->flujoEtapa?->nombre ?: 'Etapa no disponible',
            'ciclo' => (int) $firma->revision_ciclo,
            'documento' => ProyectoDocumentoSubsanacion::where('estado_proyecto_id', $estado->id)
                ->latest('id')
                ->first(),
        ];
    }

    public function coordinadorIsCurrentUser(): bool
    {
        $coordinador = $this->coordinador;
        $empleadoActual = auth()->user()?->empleado;

        return $coordinador && $empleadoActual && $coordinador->id === $empleadoActual?->id;
    }
}
