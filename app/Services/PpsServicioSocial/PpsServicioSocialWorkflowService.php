<?php

namespace App\Services\PpsServicioSocial;

use App\Mail\PpsServicioSocialRevisionPendiente;
use App\Models\PpsServicioSocial;
use App\Models\PpsServicioSocialRevisionHistorial;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;
use RuntimeException;

class PpsServicioSocialWorkflowService
{
    private array $columnCache = [];

    public function obtenerFlujoActivo(): ?FlujoAprobacion
    {
        return $this->flujoPpsQuery()
            ->when(
                $this->hasColumn('flujos_aprobacion', 'activo'),
                fn (Builder $query) => $query->where('activo', true)
            )
            ->with(['etapas' => function (Relation $query): void {
                $this->aplicarFiltroEtapasActivas($query);
                $this->aplicarOrdenEtapas($query);
            }])
            ->orderBy('id')
            ->first();
    }

    public function tieneFlujoActivo(): bool
    {
        return $this->flujoPpsQuery()
            ->when(
                $this->hasColumn('flujos_aprobacion', 'activo'),
                fn (Builder $query) => $query->where('activo', true)
            )
            ->exists();
    }

    public function asignarFlujoInicial(PpsServicioSocial $registro): bool
    {
        if ($registro->flujo_aprobacion_id) {
            $this->asignarEtapaInicialSiFalta($registro);

            return true;
        }

        $flujo = $this->obtenerFlujoActivo();

        if (!$flujo) {
            return false;
        }

        $etapaInicial = $this->obtenerEtapaInicial($flujo);

        $registro->forceFill([
            'flujo_aprobacion_id' => $flujo->id,
            'etapa_actual_id' => $etapaInicial?->id,
        ])->save();

        return true;
    }

    public function obtenerPrimeraEtapaActiva(FlujoAprobacion $flujo): ?FlujoAprobacionEtapa
    {
        return $this->obtenerEtapaInicial($flujo);
    }

    public function aprobarEtapa(PpsServicioSocial $registro, ?int $userId, ?object $user = null): PpsServicioSocial
    {
        return DB::transaction(function () use ($registro, $userId, $user): PpsServicioSocial {
            $registro->refresh();
            $this->validarUsuarioRevisor($registro, $userId, $user);

            $etapaActual = $this->validarEtapaActualDelFlujo($registro);
            $estadoOrigen = $registro->estado;

            if ((bool) ($etapaActual->es_estado_final_aprobado ?? false)) {
                $estadoDestino = $this->estadoResultanteParaAprobacion($etapaActual);

                $registro->forceFill([
                    'estado' => $estadoDestino,
                    'fecha_revision' => now(),
                    'revisado_por' => $userId,
                    'motivo_rechazo' => null,
                    'updated_by' => $userId,
                ])->save();

                $this->registrarHistorial($registro, 'aprobar_final', [
                    'flujo_aprobacion_id' => $registro->flujo_aprobacion_id,
                    'etapa_origen_id' => $etapaActual->id,
                    'etapa_destino_id' => $etapaActual->id,
                    'estado_origen' => $estadoOrigen,
                    'estado_destino' => $estadoDestino,
                    'comentario' => 'Registro aprobado en etapa final mediante flujo configurable PPS/SS.',
                    'realizado_por' => $userId,
                ]);

                return $registro->fresh(['flujoAprobacion', 'etapaActual']) ?? $registro;
            }

            $siguienteEtapa = $this->obtenerSiguienteEtapaDesdeActual($registro);

            if (!$siguienteEtapa) {
                throw new RuntimeException('No se encontro la siguiente etapa del flujo PPS/SS.');
            }

            $estadoDestino = $this->estadoResultanteParaAvance($etapaActual, $siguienteEtapa);

            $payload = [
                'etapa_actual_id' => $siguienteEtapa->id,
                'estado' => $estadoDestino,
                'updated_by' => $userId,
            ];

            $registro->forceFill($payload)->save();

            $this->registrarHistorial($registro, 'aprobar_etapa', [
                'flujo_aprobacion_id' => $registro->flujo_aprobacion_id,
                'etapa_origen_id' => $etapaActual->id,
                'etapa_destino_id' => $siguienteEtapa->id,
                'estado_origen' => $estadoOrigen,
                'estado_destino' => $estadoDestino,
                'comentario' => 'Registro avanzado a la siguiente etapa mediante flujo configurable PPS/SS.',
                'realizado_por' => $userId,
            ]);

            $this->notificarRevisionPendiente($registro, $siguienteEtapa, 'avance_etapa');

            return $registro->fresh(['flujoAprobacion', 'etapaActual']) ?? $registro;
        });
    }

    public function rechazar(PpsServicioSocial $registro, string $motivo, ?int $userId, ?object $user = null): PpsServicioSocial
    {
        return DB::transaction(function () use ($registro, $motivo, $userId, $user): PpsServicioSocial {
            $registro->refresh();
            $this->validarUsuarioRevisor($registro, $userId, $user);

            $motivo = trim($motivo);

            if ($motivo === '') {
                throw new RuntimeException('El motivo de rechazo es obligatorio.');
            }

            $etapaActual = $this->validarEtapaActualDelFlujo($registro);

            if (!$this->puedeRechazarEtapaActual($registro)) {
                throw new RuntimeException('La etapa actual del flujo PPS/SS no permite rechazo.');
            }

            $estadoOrigen = $registro->estado;

            $registro->forceFill([
                'estado' => PpsServicioSocial::ESTADO_RECHAZADO,
                'fecha_revision' => now(),
                'revisado_por' => $userId,
                'motivo_rechazo' => $motivo,
                'updated_by' => $userId,
            ])->save();

            $this->registrarHistorial($registro, 'rechazar', [
                'flujo_aprobacion_id' => $registro->flujo_aprobacion_id,
                'etapa_origen_id' => $etapaActual->id,
                'etapa_destino_id' => $etapaActual->id,
                'estado_origen' => $estadoOrigen,
                'estado_destino' => PpsServicioSocial::ESTADO_RECHAZADO,
                'motivo_rechazo' => $motivo,
                'comentario' => 'Registro rechazado mediante flujo configurable PPS/SS.',
                'realizado_por' => $userId,
            ]);

            return $registro->fresh(['flujoAprobacion', 'etapaActual']) ?? $registro;
        });
    }

    public function obtenerEtapaEditable(PpsServicioSocial $registro): ?FlujoAprobacionEtapa
    {
        $this->validarFlujoYEtapaActual($registro);

        if (!$this->hasColumn('flujos_aprobacion_etapas', 'permite_edicion')) {
            return null;
        }

        return $this->etapasDelFlujoQuery((int) $registro->flujo_aprobacion_id)
            ->where('permite_edicion', true)
            ->first();
    }

    public function iniciarSubsanacion(PpsServicioSocial $registro, ?int $userId): PpsServicioSocial
    {
        return DB::transaction(function () use ($registro, $userId): PpsServicioSocial {
            $registro->refresh();
            $this->validarPuedeSubsanar($registro, $userId);

            $etapaOrigen = $this->validarFlujoYEtapaActual($registro);
            $etapaEditable = $this->obtenerEtapaEditable($registro);
            $estadoOrigen = $registro->estado;
            $estadoDestino = PpsServicioSocial::ESTADO_BORRADOR;
            $comentario = 'Registro devuelto a borrador para subsanacion sin etapa editable configurada en el flujo PPS/SS.';

            $payload = [
                'estado' => $estadoDestino,
                'updated_by' => $userId,
            ];

            if ($etapaEditable) {
                $estadoDestino = $this->estadoResultanteParaSubsanacion($etapaEditable);
                $payload['estado'] = $estadoDestino;
                $payload['etapa_actual_id'] = $etapaEditable->id;
                $comentario = 'Registro enviado a etapa editable para subsanacion mediante flujo configurable PPS/SS.';
            }

            $registro->forceFill($payload)->save();

            $this->registrarHistorial($registro, 'iniciar_subsanacion', [
                'flujo_aprobacion_id' => $registro->flujo_aprobacion_id,
                'etapa_origen_id' => $etapaOrigen->id,
                'etapa_destino_id' => $etapaEditable?->id ?? $etapaOrigen->id,
                'estado_origen' => $estadoOrigen,
                'estado_destino' => $estadoDestino,
                'comentario' => $comentario,
                'motivo_rechazo' => $registro->motivo_rechazo,
                'realizado_por' => $userId,
            ]);

            return $registro->fresh(['flujoAprobacion', 'etapaActual']) ?? $registro;
        });
    }

    public function validarPuedeSubsanar(PpsServicioSocial $registro, ?int $userId): void
    {
        if ($registro->estado !== PpsServicioSocial::ESTADO_RECHAZADO) {
            throw new RuntimeException('Solo los registros rechazados pueden pasar a subsanacion.');
        }

        if (!$registro->perteneceAlUsuario($userId)) {
            throw new RuntimeException('Solo el usuario creador puede iniciar la subsanacion del registro.');
        }
    }

    public function enviarARevision(PpsServicioSocial $registro, ?int $userId): PpsServicioSocial
    {
        return DB::transaction(function () use ($registro, $userId): PpsServicioSocial {
            $registro->refresh();

            if ($registro->estado !== PpsServicioSocial::ESTADO_BORRADOR) {
                throw new RuntimeException('Solo los registros en estado borrador pueden enviarse a revision.');
            }

            if (!$registro->perteneceAlUsuario($userId)) {
                throw new RuntimeException('Solo el usuario creador puede enviar este registro a revision.');
            }

            $flujo = $this->resolverFlujoActivoParaEnvio($registro);
            $primeraEtapa = $this->obtenerPrimeraEtapaActiva($flujo);

            if (!$primeraEtapa) {
                throw new RuntimeException('El flujo PPS/SS activo no tiene etapas activas configuradas.');
            }

            $estadoDestino = $this->estadoResultanteParaEnvio($primeraEtapa);
            $estadoOrigen = $registro->estado;
            $etapaOrigenId = $registro->etapa_actual_id;

            $registro->forceFill([
                'flujo_aprobacion_id' => $flujo->id,
                'etapa_actual_id' => $primeraEtapa->id,
                'estado' => $estadoDestino,
                'fecha_envio' => now(),
                'enviado_por' => $userId,
                'updated_by' => $userId,
            ])->save();

            $this->registrarHistorial($registro, 'enviar_revision', [
                'flujo_aprobacion_id' => $flujo->id,
                'etapa_origen_id' => $etapaOrigenId,
                'etapa_destino_id' => $primeraEtapa->id,
                'estado_origen' => $estadoOrigen,
                'estado_destino' => $estadoDestino,
                'comentario' => 'Registro enviado a revision mediante flujo configurable PPS/SS.',
                'realizado_por' => $userId,
            ]);

            $this->notificarRevisionPendiente($registro, $primeraEtapa, 'envio_revision');

            return $registro->fresh(['flujoAprobacion', 'etapaActual']) ?? $registro;
        });
    }

    public function obtenerEtapaActual(PpsServicioSocial $registro): ?FlujoAprobacionEtapa
    {
        if ($registro->etapa_actual_id) {
            $query = FlujoAprobacionEtapa::query()->whereKey($registro->etapa_actual_id);

            if ($registro->flujo_aprobacion_id) {
                $query->where('flujo_aprobacion_id', $registro->flujo_aprobacion_id);
            }

            return $query->first();
        }

        if (!$registro->flujo_aprobacion_id) {
            return null;
        }

        return $this->obtenerEtapaInicialPorFlujoId((int) $registro->flujo_aprobacion_id);
    }

    public function obtenerSiguienteEtapa(PpsServicioSocial $registro): ?FlujoAprobacionEtapa
    {
        if (!$registro->flujo_aprobacion_id || !$registro->etapa_actual_id) {
            return null;
        }

        if (!$this->hasColumn('flujos_aprobacion_etapas', 'orden')) {
            return null;
        }

        $etapaActual = $this->obtenerEtapaActual($registro);

        if (!$etapaActual || $etapaActual->orden === null) {
            return null;
        }

        return $this->etapasDelFlujoQuery((int) $registro->flujo_aprobacion_id)
            ->where('orden', '>', $etapaActual->orden)
            ->first();
    }

    public function obtenerSiguienteEtapaDesdeActual(PpsServicioSocial $registro): ?FlujoAprobacionEtapa
    {
        $etapaActual = $this->validarEtapaActualDelFlujo($registro);

        if (!$this->hasColumn('flujos_aprobacion_etapas', 'orden') || $etapaActual->orden === null) {
            throw new RuntimeException('La etapa actual del flujo PPS/SS no tiene orden configurado.');
        }

        return $this->etapasDelFlujoQuery((int) $registro->flujo_aprobacion_id)
            ->where('orden', '>', $etapaActual->orden)
            ->first();
    }

    public function validarEtapaActualDelFlujo(PpsServicioSocial $registro): FlujoAprobacionEtapa
    {
        $etapaActual = $this->validarFlujoYEtapaActual($registro);

        if (!$this->estadoDelRegistroCoincideConEtapa($registro, $etapaActual)) {
            throw new RuntimeException('El estado actual del registro no coincide con la etapa actual del flujo PPS/SS.');
        }

        if (in_array($registro->estado, [
            PpsServicioSocial::ESTADO_BORRADOR,
            PpsServicioSocial::ESTADO_APROBADO,
            PpsServicioSocial::ESTADO_RECHAZADO,
            'subsanacion',
        ], true)) {
            throw new RuntimeException('El registro no esta en una etapa revisable del flujo PPS/SS.');
        }

        return $etapaActual;
    }

    public function puedeRechazarEtapaActual(PpsServicioSocial $registro): bool
    {
        try {
            $etapaActual = $this->validarEtapaActualDelFlujo($registro);
        } catch (\Throwable) {
            return false;
        }

        if ($this->hasColumn('flujos_aprobacion_etapas', 'permite_rechazo')) {
            return (bool) $etapaActual->permite_rechazo;
        }

        return true;
    }

    public function registrarHistorial(
        PpsServicioSocial $registro,
        string $accion,
        array $datos = []
    ): PpsServicioSocialRevisionHistorial {
        return PpsServicioSocialRevisionHistorial::create([
            'pps_servicio_social_id' => $registro->id,
            'flujo_aprobacion_id' => array_key_exists('flujo_aprobacion_id', $datos) ? $datos['flujo_aprobacion_id'] : $registro->flujo_aprobacion_id,
            'etapa_origen_id' => array_key_exists('etapa_origen_id', $datos) ? $datos['etapa_origen_id'] : $registro->etapa_actual_id,
            'etapa_destino_id' => array_key_exists('etapa_destino_id', $datos) ? $datos['etapa_destino_id'] : null,
            'accion' => $accion,
            'estado_origen' => array_key_exists('estado_origen', $datos) ? $datos['estado_origen'] : $registro->estado,
            'estado_destino' => array_key_exists('estado_destino', $datos) ? $datos['estado_destino'] : null,
            'comentario' => array_key_exists('comentario', $datos) ? $datos['comentario'] : null,
            'motivo_rechazo' => array_key_exists('motivo_rechazo', $datos) ? $datos['motivo_rechazo'] : null,
            'realizado_por' => array_key_exists('realizado_por', $datos) ? $datos['realizado_por'] : auth()->id(),
        ]);
    }

    public function puedeUsarFlujoDinamico(PpsServicioSocial $registro): bool
    {
        return (bool) $registro->flujo_aprobacion_id || $this->tieneFlujoActivo();
    }

    public function esEstadoFinalAprobado(PpsServicioSocial $registro): bool
    {
        if (!$registro->flujo_aprobacion_id && !$registro->etapa_actual_id) {
            return $registro->estado === PpsServicioSocial::ESTADO_APROBADO;
        }

        if (!$registro->flujo_aprobacion_id || !$registro->etapa_actual_id) {
            return false;
        }

        $flujo = FlujoAprobacion::query()
            ->whereKey($registro->flujo_aprobacion_id)
            ->where('proceso', PpsServicioSocial::PROCESO_FLUJO)
            ->first();

        if (!$flujo) {
            return false;
        }

        $etapaActual = FlujoAprobacionEtapa::query()
            ->whereKey($registro->etapa_actual_id)
            ->where('flujo_aprobacion_id', $flujo->id)
            ->first();

        if (!$etapaActual) {
            return false;
        }

        if (!$this->hasColumn('flujos_aprobacion_etapas', 'es_estado_final_aprobado')) {
            return false;
        }

        if (!(bool) $etapaActual->es_estado_final_aprobado) {
            return false;
        }

        if ($this->hasColumn('flujos_aprobacion_etapas', 'estado_resultante')
            && filled($etapaActual->estado_resultante)
            && $registro->estado !== $etapaActual->estado_resultante
        ) {
            return false;
        }

        return $registro->estado === PpsServicioSocial::ESTADO_APROBADO;
    }

    private function flujoPpsQuery(): Builder
    {
        return FlujoAprobacion::query()
            ->where('proceso', PpsServicioSocial::PROCESO_FLUJO);
    }

    private function obtenerEtapaInicial(FlujoAprobacion $flujo): ?FlujoAprobacionEtapa
    {
        return $this->obtenerEtapaInicialPorFlujoId((int) $flujo->id);
    }

    private function resolverFlujoActivoParaEnvio(PpsServicioSocial $registro): FlujoAprobacion
    {
        $flujoActivo = $this->obtenerFlujoActivo();

        if (!$flujoActivo) {
            throw new RuntimeException('No existe un flujo activo configurado para PPS/Servicio Social.');
        }

        if (!$registro->flujo_aprobacion_id) {
            return $flujoActivo;
        }

        $query = FlujoAprobacion::query()
            ->whereKey($registro->flujo_aprobacion_id)
            ->where('proceso', PpsServicioSocial::PROCESO_FLUJO);

        if ($this->hasColumn('flujos_aprobacion', 'activo')) {
            $query->where('activo', true);
        }

        $flujoAsignado = $query->first();

        if (!$flujoAsignado) {
            throw new RuntimeException('El flujo asignado al registro no esta activo o no pertenece al proceso PPS/Servicio Social.');
        }

        return $flujoAsignado;
    }

    private function estadoResultanteParaEnvio(FlujoAprobacionEtapa $etapa): string
    {
        $estadoResultante = $this->estadoResultanteParaEtapa($etapa, 'La primera etapa activa del flujo PPS/SS');

        if (($etapa->es_estado_final_aprobado ?? false) || $estadoResultante === PpsServicioSocial::ESTADO_APROBADO) {
            throw new RuntimeException('La primera etapa activa del flujo PPS/SS no puede ser una aprobacion final.');
        }

        if (in_array($estadoResultante, [PpsServicioSocial::ESTADO_BORRADOR, PpsServicioSocial::ESTADO_RECHAZADO, 'subsanacion'], true)) {
            throw new RuntimeException('La primera etapa activa del flujo PPS/SS debe representar un estado de revision.');
        }

        return $estadoResultante;
    }

    private function estadoResultanteParaAprobacion(FlujoAprobacionEtapa $etapa): string
    {
        $estadoResultante = $this->estadoResultanteParaEtapa($etapa, 'La siguiente etapa del flujo PPS/SS');
        $esAprobacionFinal = (bool) ($etapa->es_estado_final_aprobado ?? false);

        if ($esAprobacionFinal && $estadoResultante !== PpsServicioSocial::ESTADO_APROBADO) {
            throw new RuntimeException('La etapa final aprobada del flujo PPS/SS debe usar estado aprobado.');
        }

        if (!$esAprobacionFinal && $estadoResultante === PpsServicioSocial::ESTADO_APROBADO) {
            throw new RuntimeException('Solo una etapa marcada como aprobacion final puede usar estado aprobado.');
        }

        if (in_array($estadoResultante, [PpsServicioSocial::ESTADO_BORRADOR, PpsServicioSocial::ESTADO_RECHAZADO, 'subsanacion'], true)) {
            throw new RuntimeException('La siguiente etapa del flujo PPS/SS debe representar una revision o aprobacion final.');
        }

        return $estadoResultante;
    }

    private function estadoResultanteParaAvance(FlujoAprobacionEtapa $etapaActual, FlujoAprobacionEtapa $siguienteEtapa): string
    {
        $estadoActual = $this->estadoResultanteParaEtapa($etapaActual, 'La etapa actual del flujo PPS/SS');
        $estadoSiguiente = $this->estadoResultanteParaEtapa($siguienteEtapa, 'La siguiente etapa del flujo PPS/SS');
        $siguienteEsFinal = (bool) ($siguienteEtapa->es_estado_final_aprobado ?? false);

        if ($estadoActual === PpsServicioSocial::ESTADO_APROBADO) {
            throw new RuntimeException('Solo una etapa final puede dejar el registro aprobado.');
        }

        if ($siguienteEsFinal) {
            if ($estadoSiguiente !== PpsServicioSocial::ESTADO_APROBADO) {
                throw new RuntimeException('La etapa final aprobada del flujo PPS/SS debe usar estado aprobado.');
            }

            return $estadoActual;
        }

        if ($estadoSiguiente === PpsServicioSocial::ESTADO_APROBADO) {
            throw new RuntimeException('Solo una etapa marcada como aprobacion final puede usar estado aprobado.');
        }

        if (in_array($estadoSiguiente, [PpsServicioSocial::ESTADO_BORRADOR, PpsServicioSocial::ESTADO_RECHAZADO, 'subsanacion'], true)) {
            throw new RuntimeException('La siguiente etapa del flujo PPS/SS debe representar una revision o aprobacion final.');
        }

        return $estadoSiguiente;
    }

    private function estadoResultanteParaSubsanacion(FlujoAprobacionEtapa $etapa): string
    {
        $estadoResultante = $this->estadoResultanteParaEtapa($etapa, 'La etapa editable del flujo PPS/SS');

        if (!in_array($estadoResultante, [PpsServicioSocial::ESTADO_BORRADOR, 'subsanacion'], true)) {
            throw new RuntimeException('La etapa editable del flujo PPS/SS debe usar estado borrador o subsanacion.');
        }

        return $estadoResultante;
    }

    private function estadoResultanteParaEtapa(FlujoAprobacionEtapa $etapa, string $etiqueta): string
    {
        if (!$this->hasColumn('flujos_aprobacion_etapas', 'estado_resultante')) {
            throw new RuntimeException('Las etapas del flujo PPS/SS no tienen configurado el campo estado_resultante.');
        }

        $estadoResultante = trim((string) $etapa->estado_resultante);

        if ($estadoResultante === '') {
            throw new RuntimeException($etiqueta.' no tiene estado resultante configurado.');
        }

        return $estadoResultante;
    }

    private function validarUsuarioRevisor(PpsServicioSocial $registro, ?int $userId, ?object $user = null): void
    {
        if ($registro->perteneceAlUsuario($userId)) {
            throw new RuntimeException('El usuario creador no puede aprobar ni rechazar su propio registro.');
        }

        if (!$registro->usuarioPuedeRevisar($user ?? auth()->user())) {
            throw new RuntimeException('El usuario no tiene permisos para revisar registros PPS/SS.');
        }
    }

    private function notificarRevisionPendiente(PpsServicioSocial $registro, FlujoAprobacionEtapa $etapa, string $evento): void
    {
        $destinatarios = $this->usuariosResponsablesDeEtapa($etapa);
        $registroParaCorreo = $registro->fresh(['flujoAprobacion', 'etapaActual']) ?? $registro;

        foreach ($destinatarios as $destinatario) {
            Mail::to($destinatario->email)->queue(
                new PpsServicioSocialRevisionPendiente($registroParaCorreo, $etapa, $destinatario)
            );
        }

        Log::info('Notificacion PPS/SS de revision pendiente enviada', [
            'registro_id' => $registro->id,
            'codigo_registro' => $registro->codigo_registro,
            'evento' => $evento,
            'etapa_id' => $etapa->id,
            'etapa_nombre' => $etapa->nombre,
            'rol_revisor_id' => $etapa->rol_revisor_id,
            'requiere_asignacion' => (bool) $etapa->requiere_asignacion,
            'usuario_responsable_id' => $etapa->usuario_responsable_id,
            'destinatarios' => $destinatarios
                ->map(fn (User $user): array => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ])
                ->values()
                ->all(),
        ]);
    }

    private function usuariosResponsablesDeEtapa(FlujoAprobacionEtapa $etapa): Collection
    {
        $query = User::query()->select(['id', 'name', 'email']);
        $this->aplicarFiltroUsuariosActivos($query);

        if ((bool) $etapa->requiere_asignacion) {
            if (!$etapa->usuario_responsable_id) {
                throw new RuntimeException("La etapa {$etapa->nombre} requiere asignacion pero no tiene usuario responsable configurado.");
            }

            $query->whereKey($etapa->usuario_responsable_id);
        } else {
            if (!$etapa->rol_revisor_id) {
                throw new RuntimeException("La etapa {$etapa->nombre} no tiene rol revisor configurado.");
            }

            $query->whereHas('roles', fn (Builder $roleQuery) => $roleQuery->where('roles.id', $etapa->rol_revisor_id));
        }

        $usuarios = $query
            ->get()
            ->filter(fn (User $user): bool => filled($user->email) && filter_var($user->email, FILTER_VALIDATE_EMAIL))
            ->values();

        if ($usuarios->isEmpty()) {
            Log::warning('Etapa PPS/SS sin destinatarios validos para notificar', [
                'etapa_id' => $etapa->id,
                'etapa_nombre' => $etapa->nombre,
                'rol_revisor_id' => $etapa->rol_revisor_id,
                'requiere_asignacion' => (bool) $etapa->requiere_asignacion,
                'usuario_responsable_id' => $etapa->usuario_responsable_id,
            ]);

            throw new RuntimeException("La etapa {$etapa->nombre} no tiene usuarios responsables con correo valido para notificar.");
        }

        return $usuarios;
    }

    private function aplicarFiltroUsuariosActivos(Builder $query): void
    {
        if ($this->hasColumn('users', 'activo')) {
            $query->where('activo', true);
        }

        if ($this->hasColumn('users', 'active')) {
            $query->where('active', true);
        }
    }

    private function validarFlujoYEtapaActual(PpsServicioSocial $registro): FlujoAprobacionEtapa
    {
        if (!$registro->flujo_aprobacion_id || !$registro->etapa_actual_id) {
            throw new RuntimeException('El registro no tiene flujo y etapa actual configurados.');
        }

        $flujo = FlujoAprobacion::query()
            ->whereKey($registro->flujo_aprobacion_id)
            ->where('proceso', PpsServicioSocial::PROCESO_FLUJO)
            ->first();

        if (!$flujo) {
            throw new RuntimeException('El flujo asignado al registro no pertenece al proceso PPS/Servicio Social.');
        }

        $query = FlujoAprobacionEtapa::query()
            ->whereKey($registro->etapa_actual_id)
            ->where('flujo_aprobacion_id', $flujo->id);

        $this->aplicarFiltroEtapasActivas($query);

        $etapaActual = $query->first();

        if (!$etapaActual) {
            throw new RuntimeException('La etapa actual no pertenece al flujo PPS/SS del registro o no esta activa.');
        }

        return $etapaActual;
    }

    private function obtenerEtapaInicialPorFlujoId(int $flujoId): ?FlujoAprobacionEtapa
    {
        return $this->etapasDelFlujoQuery($flujoId)->first();
    }

    private function estadoDelRegistroCoincideConEtapa(PpsServicioSocial $registro, FlujoAprobacionEtapa $etapaActual): bool
    {
        if (!$this->hasColumn('flujos_aprobacion_etapas', 'estado_resultante') || blank($etapaActual->estado_resultante)) {
            return true;
        }

        if ($registro->estado === $etapaActual->estado_resultante) {
            return true;
        }

        return (bool) ($etapaActual->es_estado_final_aprobado ?? false)
            && $registro->estado !== PpsServicioSocial::ESTADO_APROBADO
            && !in_array($registro->estado, [
                PpsServicioSocial::ESTADO_BORRADOR,
                PpsServicioSocial::ESTADO_RECHAZADO,
                'subsanacion',
            ], true);
    }

    private function asignarEtapaInicialSiFalta(PpsServicioSocial $registro): void
    {
        if ($registro->etapa_actual_id || !$registro->flujo_aprobacion_id) {
            return;
        }

        $etapaInicial = $this->obtenerEtapaInicialPorFlujoId((int) $registro->flujo_aprobacion_id);

        if (!$etapaInicial) {
            return;
        }

        $registro->forceFill([
            'etapa_actual_id' => $etapaInicial->id,
        ])->save();
    }

    private function etapasDelFlujoQuery(int $flujoId): Builder
    {
        $query = FlujoAprobacionEtapa::query()
            ->where('flujo_aprobacion_id', $flujoId);

        $this->aplicarFiltroEtapasActivas($query);
        $this->aplicarOrdenEtapas($query);

        return $query;
    }

    private function aplicarFiltroEtapasActivas(Builder|Relation $query): void
    {
        if ($this->hasColumn('flujos_aprobacion_etapas', 'activo')) {
            $query->where('activo', true);
        }
    }

    private function aplicarOrdenEtapas(Builder|Relation $query): void
    {
        if ($this->hasColumn('flujos_aprobacion_etapas', 'orden')) {
            $query->orderBy('orden');

            return;
        }

        $query->orderBy('id');
    }

    private function hasColumn(string $table, string $column): bool
    {
        $key = $table.'.'.$column;

        return $this->columnCache[$key] ??= Schema::hasColumn($table, $column);
    }
}
