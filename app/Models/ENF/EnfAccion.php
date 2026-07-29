<?php

namespace App\Models\ENF;

use App\Models\Personal\Empleado;
use App\Models\Proyecto\EjesPrioritariosUnah;
use App\Models\Proyecto\MetaContribuye;
use App\Models\Proyecto\Modalidad;
use App\Models\Proyecto\Od;
use App\Models\Proyecto\VinculacionTipoAccion;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\UnidadAcademica\Carrera;
use App\Models\UnidadAcademica\DepartamentoAcademico;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnfAccion extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const PROCESO_INSCRIPCION = 'INSCRIPCION';
    public const PROCESO_INFORME_INTERMEDIO = 'INFORME_INTERMEDIO';
    public const PROCESO_INFORME_FINAL = 'INFORME_FINAL';

    protected $table = 'enf_acciones';

    protected $guarded = [];

    protected $casts = [
        'fecha_solicitud' => 'date',
        'fecha_inicio' => 'date',
        'fecha_finalizacion' => 'date',
        'fecha_aprobacion' => 'date',
        'fecha_registro' => 'date',
        'genera_ingresos' => 'boolean',
        'numero_edicion' => 'integer',
        'horas_teoricas' => 'integer',
        'horas_practicas' => 'integer',
        'total_horas' => 'integer',
        'carga_horaria_creditos' => 'integer',
        'revision_ciclo' => 'integer',
    ];

    public function tipoAccion()
    {
        return $this->belongsTo(VinculacionTipoAccion::class, 'tipo_accion_id');
    }

    public function modalidad()
    {
        return $this->belongsTo(Modalidad::class, 'modalidad_id');
    }

    public function centroFacultad()
    {
        return $this->belongsTo(FacultadCentro::class, 'centro_facultad_id');
    }

    public function departamentoAcademico()
    {
        return $this->belongsTo(DepartamentoAcademico::class, 'departamento_academico_id');
    }

    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'carrera_id');
    }

    public function responsableRevision()
    {
        return $this->belongsTo(Empleado::class, 'responsable_revision_id');
    }

    public function flujoAprobacion()
    {
        return $this->belongsTo(FlujoAprobacion::class, 'flujo_aprobacion_id');
    }

    public function revisiones()
    {
        return $this->hasMany(EnfRevision::class, 'enf_accion_id');
    }

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por_usuario_id');
    }

    public function modificadoPor()
    {
        return $this->belongsTo(User::class, 'modificado_por_usuario_id');
    }

    public function lugaresEjecucion()
    {
        return $this->hasMany(EnfLugarEjecucion::class, 'enf_accion_id');
    }

    public function catalogos()
    {
        return $this->belongsToMany(EnfCatalogo::class, 'enf_accion_catalogo', 'enf_accion_id', 'enf_catalogo_id')
            ->withPivot(['tipo', 'valor_texto'])
            ->withTimestamps();
    }

    public function accionCatalogos()
    {
        return $this->hasMany(EnfAccionCatalogo::class, 'enf_accion_id');
    }

    public function beneficiarios()
    {
        return $this->hasOne(EnfBeneficiario::class, 'enf_accion_id');
    }

    public function equipo()
    {
        return $this->hasMany(EnfEquipo::class, 'enf_accion_id');
    }

    public function participacionUniversitaria()
    {
        return $this->hasMany(EnfParticipacionUniversitaria::class, 'enf_accion_id');
    }

    public function practicasAsignatura()
    {
        return $this->hasMany(EnfPracticaAsignatura::class, 'enf_accion_id');
    }

    public function contrapartes()
    {
        return $this->hasMany(EnfContraparte::class, 'enf_accion_id');
    }

    public function objetivosEspecificos()
    {
        return $this->hasMany(EnfObjetivoEspecifico::class, 'enf_accion_id');
    }

    public function resultados()
    {
        return $this->hasMany(EnfResultado::class, 'enf_accion_id');
    }

    public function presupuestos()
    {
        return $this->hasMany(EnfPresupuesto::class, 'enf_accion_id');
    }

    public function cronograma()
    {
        return $this->hasMany(EnfCronograma::class, 'enf_accion_id');
    }

    public function certificado()
    {
        return $this->hasOne(EnfCertificado::class, 'enf_accion_id');
    }

    public function espaciosAprendizaje()
    {
        return $this->hasMany(EnfEspacioAprendizaje::class, 'enf_accion_id');
    }

    public function informeFinal()
    {
        return $this->hasOne(EnfInformeFinal::class, 'enf_accion_id');
    }

    public function informeIntermedio()
    {
        return $this->hasOne(EnfInformeIntermedio::class, 'enf_accion_id');
    }

    public function sistematizacion()
    {
        return $this->hasOne(EnfSistematizacion::class, 'enf_accion_id');
    }

    public function documentos()
    {
        return $this->hasMany(EnfDocumento::class, 'enf_accion_id');
    }

    public function firmas()
    {
        return $this->hasMany(EnfFirma::class, 'enf_accion_id');
    }

    public function ods()
    {
        return $this->belongsToMany(Od::class, 'enf_accion_ods', 'enf_accion_id', 'ods_id')
            ->withPivot(['meta_contribuye_id', 'contribucion'])
            ->withTimestamps();
    }

    public function accionOds()
    {
        return $this->hasMany(EnfAccionOds::class, 'enf_accion_id');
    }

    public function metasContribuye()
    {
        return $this->belongsToMany(MetaContribuye::class, 'enf_accion_ods', 'enf_accion_id', 'meta_contribuye_id')
            ->withPivot(['ods_id', 'contribucion'])
            ->withTimestamps();
    }

    public function ejesUnah()
    {
        return $this->belongsToMany(EjesPrioritariosUnah::class, 'enf_accion_ejes_unah', 'enf_accion_id', 'eje_prioritario_unah_id')
            ->withPivot(['contribucion'])
            ->withTimestamps();
    }

    public function accionEjesUnah()
    {
        return $this->hasMany(EnfAccionEjeUnah::class, 'enf_accion_id');
    }
}
