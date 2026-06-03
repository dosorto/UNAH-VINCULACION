<?php

namespace App\Models;

use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Services\PpsServicioSocial\PpsServicioSocialWorkflowService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class PpsServicioSocial extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const ESTADO_BORRADOR = 'borrador';
    public const ESTADO_ENVIADO = 'enviado';
    public const ESTADO_APROBADO = 'aprobado';
    public const ESTADO_RECHAZADO = 'rechazado';
    public const PROCESO_FLUJO = 'PPS_SERVICIO_SOCIAL';

    protected $table = 'pps_servicio_social';

    protected $fillable = [
        'codigo_registro',
        'estado',
        'flujo_aprobacion_id',
        'etapa_actual_id',
        'fecha_envio',
        'fecha_revision',
        'facultad_centro',
        'carrera',
        'numero_cuenta',
        'nombre_estudiante',
        'celular_estudiante',
        'correo_institucional',
        'correo_personal',
        'tipo_pps_ss',
        'fecha_inicio',
        'fecha_finalizacion',
        'tipo_instrumento',
        'territorio_ejecucion',
        'departamento',
        'municipio',
        'aldea_ciudad',
        'caserio',
        'descripcion_tipo_pps',
        'total_horas',
        'area_realizacion',
        'resumen_responsabilidades',
        'modalidad_ejecucion',
        'nombre_institucion',
        'compromisos_institucion',
        'direccion_institucion',
        'representante_legal',
        'telefono_representante',
        'correo_rrhh',
        'tipo_institucion',
        'sector_institucion',
        'nombre_jefe_directo',
        'celular_jefe_directo',
        'correo_jefe_directo',
        'cargo_jefe_directo',
        'grado_academico_jefe_directo',
        'nombre_docente_supervisor',
        'numero_empleado_docente',
        'celular_docente',
        'correo_docente',
        'categoria_docente',
        'departamento_docente',
        'jornada_laboral_docente',
        'ubicacion_cubiculo_docente',
        'adjunta_carta_formalizacion',
        'archivo_carta_formalizacion',
        'adjunta_convenio_marco',
        'archivo_convenio_marco',
        'created_by',
        'updated_by',
        'enviado_por',
        'revisado_por',
        'motivo_rechazo',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_finalizacion' => 'date',
        'fecha_envio' => 'datetime',
        'fecha_revision' => 'datetime',
        'total_horas' => 'integer',
        'adjunta_carta_formalizacion' => 'boolean',
        'adjunta_convenio_marco' => 'boolean',
    ];

    public function flujoAprobacion(): BelongsTo
    {
        return $this->belongsTo(FlujoAprobacion::class, 'flujo_aprobacion_id');
    }

    public function etapaActual(): BelongsTo
    {
        return $this->belongsTo(FlujoAprobacionEtapa::class, 'etapa_actual_id');
    }

    public function historialRevisiones(): HasMany
    {
        return $this->hasMany(PpsServicioSocialRevisionHistorial::class, 'pps_servicio_social_id')
            ->latest();
    }

    public function perteneceAlUsuario(?int $userId): bool
    {
        return $this->created_by !== null
            && $userId !== null
            && (int) $this->created_by === (int) $userId;
    }

    public function puedeEnviarse(?int $userId): bool
    {
        return $this->estado === self::ESTADO_BORRADOR
            && $this->perteneceAlUsuario($userId);
    }

    public function puedeRevisarse(?int $userId, ?object $user = null): bool
    {
        if (!$this->usuarioPuedeRevisar($user)) {
            return false;
        }

        return $this->estaEnRevision();
    }

    public function estaEnRevision(): bool
    {
        try {
            app(PpsServicioSocialWorkflowService::class)->validarEtapaActualDelFlujo($this);
        } catch (\Throwable) {
            return false;
        }

        return true;
    }

    public function puedeAprobarse(?int $userId, ?object $user = null): bool
    {
        return $this->puedeRevisarse($userId, $user);
    }

    public function puedeRechazarse(?int $userId, ?object $user = null): bool
    {
        return $this->puedeRevisarse($userId, $user)
            && app(PpsServicioSocialWorkflowService::class)->puedeRechazarEtapaActual($this);
    }

    public function puedeSubsanarse(?int $userId): bool
    {
        if ($this->estado !== self::ESTADO_RECHAZADO || !$this->perteneceAlUsuario($userId)) {
            return false;
        }

        if (!$this->flujo_aprobacion_id || !$this->etapa_actual_id) {
            return false;
        }

        try {
            app(PpsServicioSocialWorkflowService::class)->obtenerEtapaEditable($this);
        } catch (\Throwable) {
            return false;
        }

        return true;
    }

    public function puedeDescargarPdf(?int $userId, ?object $user = null): bool
    {
        return app(PpsServicioSocialWorkflowService::class)->esEstadoFinalAprobado($this)
            && (
                $this->perteneceAlUsuario($userId)
                || $this->usuarioPuedeRevisar($user)
            );
    }

    public function camposFaltantesParaEnvio(): array
    {
        $faltantes = [];

        $camposObligatorios = [
            'facultad_centro' => 'Facultad / Centro',
            'carrera' => 'Carrera',
            'numero_cuenta' => 'Numero de cuenta',
            'nombre_estudiante' => 'Nombre del estudiante',
            'celular_estudiante' => 'Celular del estudiante',
            'correo_institucional' => 'Correo institucional',
            'tipo_pps_ss' => 'Tipo PPS/SS',
            'fecha_inicio' => 'Fecha de inicio',
            'fecha_finalizacion' => 'Fecha de finalizacion',
            'tipo_instrumento' => 'Tipo de instrumento',
            'territorio_ejecucion' => 'Territorio de ejecucion',
            'total_horas' => 'Total de horas',
            'modalidad_ejecucion' => 'Modalidad de ejecucion',
            'nombre_institucion' => 'Institucion / Organizacion',
            'nombre_jefe_directo' => 'Jefe directo',
            'nombre_docente_supervisor' => 'Docente supervisor',
        ];

        foreach ($camposObligatorios as $campo => $etiqueta) {
            if (!$this->valorCompletoParaEnvio($this->getAttribute($campo))) {
                $faltantes[] = $etiqueta;
            }
        }

        if ($this->territorio_ejecucion === 'Nacional') {
            foreach (['departamento' => 'Departamento', 'municipio' => 'Municipio'] as $campo => $etiqueta) {
                if (!$this->valorCompletoParaEnvio($this->getAttribute($campo))) {
                    $faltantes[] = $etiqueta;
                }
            }
        }

        if ($this->valorCompletoParaEnvio($this->correo_institucional)
            && !filter_var($this->correo_institucional, FILTER_VALIDATE_EMAIL)
        ) {
            $faltantes[] = 'Correo institucional con formato valido';
        }

        if ($this->valorCompletoParaEnvio($this->total_horas)
            && (!is_numeric($this->total_horas) || (int) $this->total_horas < 1)
        ) {
            $faltantes[] = 'Total de horas mayor a 0';
        }

        if ($this->valorCompletoParaEnvio($this->fecha_inicio)
            && $this->valorCompletoParaEnvio($this->fecha_finalizacion)
        ) {
            try {
                $fechaInicio = $this->fechaParaComparar($this->fecha_inicio);
                $fechaFinalizacion = $this->fechaParaComparar($this->fecha_finalizacion);

                if ($fechaFinalizacion->lt($fechaInicio)) {
                    $faltantes[] = 'Fecha de finalizacion mayor o igual a fecha de inicio';
                }
            } catch (\Throwable) {
                $faltantes[] = 'Fechas con formato valido';
            }
        }

        return array_values(array_unique($faltantes));
    }

    private function valorCompletoParaEnvio(mixed $value): bool
    {
        if ($value instanceof \DateTimeInterface) {
            return true;
        }

        if ($value === null) {
            return false;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return false;
        }

        $normalizado = Str::ascii(Str::lower($value));

        return !in_array($normalizado, ['pendiente', 'borrador sin titulo', 'null'], true);
    }

    public function usuarioPuedeRevisar(?object $user): bool
    {
        if (!$user) {
            return false;
        }

        if (empty($user->active_role_id)) {
            return false;
        }

        $activeRole = $user->activeRole ?? null;

        if (!$activeRole) {
            return false;
        }

        $activeRoleId = (int) $activeRole->id;

        if ($activeRole?->name === 'admin') {
            return true;
        }

        $etapaActual = $this->relationLoaded('etapaActual')
            ? $this->etapaActual
            : $this->etapaActual()->first();

        if (!$etapaActual || !($etapaActual->activo ?? true)) {
            return false;
        }

        if ((bool) ($etapaActual->requiere_asignacion ?? false)) {
            $usuarioAsignado = $etapaActual->usuario_responsable_id !== null
                && isset($user->id)
                && (int) $etapaActual->usuario_responsable_id === (int) $user->id;

            if (!$usuarioAsignado) {
                return false;
            }

            if (!$etapaActual->rol_revisor_id) {
                return true;
            }

            return (int) $etapaActual->rol_revisor_id === $activeRoleId;
        }

        if (!$etapaActual->rol_revisor_id) {
            return false;
        }

        return (int) $etapaActual->rol_revisor_id === $activeRoleId;
    }

    private function fechaParaComparar(mixed $value): Carbon
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }

        return Carbon::parse($value);
    }
}
