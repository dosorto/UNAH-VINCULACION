<?php

namespace App\Models\SGCU;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProgramaCertificacion extends Model
{
    use SoftDeletes;

    protected $table = 'programas_certificacion';

    protected $fillable = [
        'centro_facultad_id',
        'codigo',
        'nombre',
        'tipo_programa',
        'tipo_programa_id',
        'horas_maximas_programa',
        'version_actual',
        'descripcion',
        'estado',
        'estado_flujo',
        'revision_ciclo',
        'enviado_revision_en',
        'observaciones_revision',
        'subsanacion_revision_id',
        'subsanacion_etapa_orden',
        'subsanacion_etapa_nombre',
        'subsanacion_devuelto_en',
        'flujo_aprobacion_id',
        'creado_por_usuario_id',
        'modificado_por_usuario_id',
    ];

    protected $casts = [
        'horas_maximas_programa' => 'integer',
        'version_actual' => 'integer',
        'revision_ciclo' => 'integer',
        'subsanacion_revision_id' => 'integer',
        'subsanacion_etapa_orden' => 'integer',
        'enviado_revision_en' => 'datetime',
        'subsanacion_devuelto_en' => 'datetime',
    ];

    public function centroFacultad(): BelongsTo
    {
        return $this->belongsTo(\App\Models\UnidadAcademica\FacultadCentro::class, 'centro_facultad_id');
    }

    public function tipoPrograma(): BelongsTo
    {
        return $this->belongsTo(TipoPrograma::class, 'tipo_programa_id');
    }

    public function flujoAprobacion(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Proyecto\FlujoAprobacion::class, 'flujo_aprobacion_id');
    }

    public function versiones(): HasMany
    {
        return $this->hasMany(VersionPrograma::class, 'programa_certificacion_id')->orderByDesc('numero_version');
    }

    public function asignaturasPrograma(): HasMany
    {
        return $this->hasMany(ProgramaAsignatura::class, 'programa_certificacion_id')->orderBy('orden');
    }

    public function centrosPrograma(): HasMany
    {
        return $this->hasMany(ProgramaCentroFacultad::class, 'programa_certificacion_id');
    }

    public function ediciones(): HasMany
    {
        return $this->hasMany(EdicionPrograma::class, 'programa_certificacion_id');
    }

    public function revisiones(): HasMany
    {
        return $this->hasMany(ProgramaRevision::class, 'programa_certificacion_id');
    }
}
