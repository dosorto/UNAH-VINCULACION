<?php

namespace App\Models;

use App\Concerns\TieneFlujoPorEtapas;
use App\Models\Estado\EstadoProyecto;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pasantia extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TieneFlujoPorEtapas;

    public const PROCESO_FLUJO = 'PASANTIAS_DEFAULT';

    public const FORMULARIO = 'FORM-DVUS-013';

    protected $table = 'pasantias';

    protected $fillable = [
        'codigo_registro', 'estado', 'proceso', 'tipo_accion_id', 'flujo_aprobacion_id', 'etapa_actual_id',
        'fecha_envio', 'fecha_revision', 'enviado_por', 'revisado_por', 'motivo_rechazo', 'destinatarios_emisor',
        'fecha_registro', 'facultad_centro', 'escuela_departamento', 'carrera', 'numero_cuenta', 'nombre_estudiante',
        'celular_estudiante', 'correo_institucional', 'correo_personal', 'tipo_pasantia', 'fecha_inicio',
        'fecha_finalizacion', 'duracion_semanas', 'total_horas', 'horas_semanales', 'pasantia_obligatoria',
        'otorga_creditos', 'cantidad_creditos', 'modalidad_ejecucion', 'descripcion_experiencia', 'descripcion_cargo',
        'resumen_responsabilidades', 'area_departamento', 'area_conocimiento', 'asignaturas', 'codigo_asignatura',
        'nombre_asignatura', 'descripcion_conocimientos_teoricos', 'habilidades_desarrollar', 'pasantia_remunerada',
        'monto_remuneracion', 'nombre_institucion', 'direccion_institucion', 'ciudad_institucion', 'pais_institucion',
        'representante_legal', 'telefono_representante', 'correo_rrhh', 'tipo_institucion', 'sector_institucion',
        'compromisos_institucion', 'nombre_contacto_directo', 'celular_contacto_directo', 'correo_contacto_directo',
        'cargo_contacto_directo', 'grado_academico_contacto_directo', 'tipo_instrumento', 'nombre_docente_supervisor',
        'numero_empleado_docente', 'celular_docente', 'correo_docente', 'categoria_docente', 'departamento_docente',
        'jornada_laboral_docente', 'ubicacion_cubiculo_docente', 'nombre_firma_coordinador', 'firma_coordinador',
        'nombre_firma_supervisor', 'firma_supervisor', 'nombre_firma_estudiante', 'firma_estudiante',
        'adjunta_carta_formalizacion', 'archivo_carta_formalizacion', 'adjunta_convenio_marco', 'archivo_convenio_marco',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'fecha_registro' => 'date', 'fecha_inicio' => 'date', 'fecha_finalizacion' => 'date',
        'fecha_envio' => 'datetime', 'fecha_revision' => 'datetime', 'destinatarios_emisor' => 'array',
        'asignaturas' => 'array', 'duracion_semanas' => 'integer', 'total_horas' => 'integer',
        'horas_semanales' => 'integer', 'cantidad_creditos' => 'decimal:2', 'monto_remuneracion' => 'decimal:2',
        'pasantia_obligatoria' => 'boolean', 'otorga_creditos' => 'boolean', 'pasantia_remunerada' => 'boolean',
        'adjunta_carta_formalizacion' => 'boolean', 'adjunta_convenio_marco' => 'boolean',
    ];

    public function flujoAprobacion(): BelongsTo { return $this->belongsTo(FlujoAprobacion::class, 'flujo_aprobacion_id'); }

    public function etapaActual(): BelongsTo { return $this->belongsTo(FlujoAprobacionEtapa::class, 'etapa_actual_id'); }

    public function firmasDeEtapa(): MorphMany { return $this->morphMany(FirmaProyecto::class, 'firmable'); }

    public function historialEstados(): MorphMany { return $this->morphMany(EstadoProyecto::class, 'estadoable'); }

    public function estadoActual(): HasOne
    {
        return $this->hasOne(EstadoProyecto::class, 'estadoable_id')
            ->where('estadoable_type', self::class)
            ->where('es_actual', true);
    }

    public function creadoPor(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function actualizadoPor(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
    public function documentosGenerados() { return $this->hasMany(PasantiaDocumentoGenerado::class); }

    public function getEstadoAttribute(): string
    {
        return match ($this->estadoActual?->tipoestado?->nombre) {
            'Aprobado' => 'aprobado', 'Rechazado' => 'rechazado', 'Subsanacion' => 'subsanacion',
            'Borrador' => 'borrador', default => $this->estadoActual ? 'enviado' : 'borrador',
        };
    }

    public function resolveFlujoAprobacion(): ?FlujoAprobacion
    {
        return $this->flujoAprobacion
            ?: $this->resolveFlujoAprobacionPorProceso(self::PROCESO_FLUJO, self::FORMULARIO);
    }

    public function perteneceAlUsuario(?int $userId): bool
    {
        return $userId !== null && $this->created_by !== null && (int) $this->created_by === $userId;
    }

    public function puedeEliminarBorrador(?int $userId): bool
    {
        return $this->perteneceAlUsuario($userId) && $this->estado === 'borrador';
    }
}
