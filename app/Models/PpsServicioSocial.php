<?php

namespace App\Models;

use App\Concerns\TieneFlujoPorEtapas;
use App\Models\Estado\EstadoProyecto;
use App\Models\Estado\TipoEstado;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Services\PpsServicioSocial\PpsServicioSocialWorkflowService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class PpsServicioSocial extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TieneFlujoPorEtapas;

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
        'region',
        'pais',
        'departamento_provincia',
        'departamento',
        'municipio',
        'aldea_ciudad',
        'caserio',
        'pais_sede_principal',
        'departamento_provincia_sede_principal',
        'municipio_sede_principal',
        'aldea_ciudad_sede_principal',
        'descripcion_tipo_pps',
        'descripcion_horas_tipo_pps_ss',
        'total_horas',
        'horas_presenciales',
        'horas_teletrabajo',
        'area_realizacion',
        'resumen_responsabilidades',
        'modalidad_ejecucion',
        'nombre_institucion',
        'institucion_nacionalidad',
        'institucion_pais',
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
        'destinatarios_emisor',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_finalizacion' => 'date',
        'fecha_envio' => 'datetime',
        'fecha_revision' => 'datetime',
        'total_horas' => 'integer',
        'horas_presenciales' => 'integer',
        'horas_teletrabajo' => 'integer',
        'adjunta_carta_formalizacion' => 'boolean',
        'adjunta_convenio_marco' => 'boolean',
        'destinatarios_emisor' => 'array',
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

    /**
     * Registros PPS/SS pendientes de revisión para el usuario y rol activo
     * indicados. Única fuente de verdad usada por la bandeja de tareas
     * (ProyectosPorFirmar), el contador de la barra de navegación
     * (DataNavBar) y los dashboards, para que los tres coincidan siempre.
     */
    public static function pendientesParaUsuario(?\App\Models\User $user): \Illuminate\Database\Eloquent\Builder
    {
        if (! $user || empty($user->active_role_id) || ! $user->activeRole) {
            return self::query()->whereRaw('1 = 0');
        }

        $activeRole = $user->activeRole;
        $isActiveAdmin = $activeRole->name === 'admin';

        return self::query()
            ->whereNotIn('estado', [
                self::ESTADO_BORRADOR,
                self::ESTADO_APROBADO,
                self::ESTADO_RECHAZADO,
                'subsanacion',
            ])
            ->whereNotNull('flujo_aprobacion_id')
            ->whereNotNull('etapa_actual_id')
            ->whereHas('flujoAprobacion', fn ($query) => $query->where('proceso', self::PROCESO_FLUJO))
            ->whereHas('etapaActual', fn ($query) => $query
                ->whereColumn('flujos_aprobacion_etapas.flujo_aprobacion_id', 'pps_servicio_social.flujo_aprobacion_id')
                ->where('activo', true))
            // Igual que Proyecto: la firma pendiente de la etapa actual (por
            // registro, ya puede haber sido reasignada) es la fuente de verdad
            // de autorización, no el campo compartido de la etapa.
            ->when(! $isActiveAdmin, fn ($query) => $query->whereHas('firmasDeEtapa', function ($firmaQuery) use ($user, $activeRole): void {
                $firmaQuery
                    ->whereColumn('firma_proyecto.flujo_aprobacion_etapa_id', 'pps_servicio_social.etapa_actual_id')
                    ->where('estado_revision', 'Pendiente')
                    ->where(function ($responsableQuery) use ($user, $activeRole): void {
                        $responsableQuery
                            ->where(function ($asignacionQuery) use ($user, $activeRole): void {
                                $asignacionQuery
                                    ->where('responsable_usuario_id', $user->id)
                                    ->where(function ($roleQuery) use ($activeRole): void {
                                        $roleQuery
                                            ->whereNull('rol_requerido')
                                            ->orWhere('rol_requerido', $activeRole->name);
                                    });
                            })
                            ->orWhere(function ($rolQuery) use ($activeRole): void {
                                $rolQuery
                                    ->whereNull('responsable_usuario_id')
                                    ->where('rol_requerido', $activeRole->name);
                            });
                    });
            }));
    }

    // ── Motor de flujo por etapas compartido (App\Concerns\TieneFlujoPorEtapas) ──

    public function firmasDeEtapa(): MorphMany
    {
        return $this->morphMany(FirmaProyecto::class, 'firmable');
    }

    public function historialEstados(): MorphMany
    {
        return $this->morphMany(EstadoProyecto::class, 'estadoable');
    }

    public function estadoActual(): HasOne
    {
        return $this->hasOne(EstadoProyecto::class, 'estadoable_id')
            ->where('estadoable_type', self::class)
            ->where('es_actual', true);
    }

    public function resolveFlujoAprobacion(): ?FlujoAprobacion
    {
        if ($this->flujoAprobacion) {
            return $this->flujoAprobacion->loadMissing('etapas.cargoFirma.tipoCargoFirma');
        }

        return $this->resolveFlujoAprobacionPorProceso(self::PROCESO_FLUJO, 'FORM-DVUS-014');
    }

    /**
     * Refleja el estado actual (tabla estado_proyecto, fuente de verdad) en la
     * columna corta `estado` para no romper el resto de la app (List/Edit/Show),
     * que sigue leyendo `estado` como antes.
     */
    public function sincronizarEstadoCorto(): void
    {
        $nombre = $this->estadoActual()->with('tipoestado')->first()?->tipoestado?->nombre;

        if (! $nombre) {
            return;
        }

        $estadoCorto = match ($nombre) {
            'Borrador' => self::ESTADO_BORRADOR,
            'Aprobado' => self::ESTADO_APROBADO,
            'Rechazado' => self::ESTADO_RECHAZADO,
            'Subsanacion' => 'subsanacion',
            default => self::ESTADO_ENVIADO,
        };

        $this->forceFill(['estado' => $estadoCorto])->saveQuietly();
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
            'numero_cuenta' => 'Número de cuenta',
            'nombre_estudiante' => 'Nombre del estudiante',
            'celular_estudiante' => 'Celular del estudiante',
            'correo_institucional' => 'Correo institucional',
            'tipo_pps_ss' => 'Tipo PPS/SS',
            'fecha_inicio' => 'Fecha de inicio',
            'fecha_finalizacion' => 'Fecha de finalización',
            'tipo_instrumento' => 'Tipo de instrumento',
            'territorio_ejecucion' => 'Territorio de ejecución',
            'total_horas' => 'Total de horas',
            'modalidad_ejecucion' => 'Modalidad de ejecución',
            'nombre_institucion' => 'Institución / Organización',
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
            $faltantes[] = 'Correo institucional con formato válido';
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
                    $faltantes[] = 'Fecha de finalización mayor o igual a fecha de inicio';
                }
            } catch (\Throwable) {
                $faltantes[] = 'Fechas con formato válido';
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

        if ($activeRole->name === 'admin') {
            return true;
        }

        $etapaActual = $this->relationLoaded('etapaActual')
            ? $this->etapaActual
            : $this->etapaActual()->first();

        if (!$etapaActual || !($etapaActual->activo ?? true) || !$this->etapa_actual_id) {
            return false;
        }

        // Igual que Proyecto: la autorización se resuelve por la firma pendiente
        // de esta etapa (que ya tiene su propio responsable_usuario_id/rol_requerido
        // por registro), no por el campo compartido de la etapa. Esto permite que
        // una etapa reasignada a otra persona (FirmaProyecto::reasignarA()) se
        // refleje correctamente aquí sin afectar a otros registros en la misma etapa.
        $firma = $this->firmasDeEtapa()
            ->where('flujo_aprobacion_etapa_id', $this->etapa_actual_id)
            ->where('estado_revision', 'Pendiente')
            ->first();

        if (!$firma) {
            return false;
        }

        if (blank($firma->rol_requerido) && !$firma->responsable_usuario_id) {
            return false;
        }

        if ($firma->responsable_usuario_id) {
            if ((int) $firma->responsable_usuario_id !== (int) $user->id) {
                return false;
            }

            return blank($firma->rol_requerido) || $firma->rol_requerido === $activeRole->name;
        }

        return filled($firma->rol_requerido) && $firma->rol_requerido === $activeRole->name;
    }

    private function fechaParaComparar(mixed $value): Carbon
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }

        return Carbon::parse($value);
    }
}
