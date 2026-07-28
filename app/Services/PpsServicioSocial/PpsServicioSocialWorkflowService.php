<?php

namespace App\Services\PpsServicioSocial;

use App\Mail\PpsServicioSocialRevisionPendiente;
use App\Models\Estado\TipoEstado;
use App\Models\PpsServicioSocial;
use App\Models\PpsServicioSocialRevisionHistorial;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

/**
 * Motor de aprobación de PPS/Servicio Social sobre las mismas tablas
 * polimórficas que usa Proyecto (firma_proyecto/estado_proyecto), vía el
 * trait App\Concerns\TieneFlujoPorEtapas que adopta PpsServicioSocial.
 * La columna `estado` de pps_servicio_social se mantiene como espejo
 * (ver PpsServicioSocial::sincronizarEstadoCorto()) para no romper el resto
 * de la app, que sigue leyéndola tal cual.
 */
class PpsServicioSocialWorkflowService
{
    public function obtenerFlujoActivo(): ?FlujoAprobacion
    {
        return $this->flujoPpsQuery()
            ->where('activo', true)
            ->with(['etapas' => fn ($query) => $query->where('activo', true)->orderBy('orden')])
            ->orderBy('id')
            ->first();
    }

    public function tieneFlujoActivo(): bool
    {
        return $this->flujoPpsQuery()->where('activo', true)->exists();
    }

    public function enviarARevision(PpsServicioSocial $registro, ?int $userId): PpsServicioSocial
    {
        return DB::transaction(function () use ($registro, $userId): PpsServicioSocial {
            $registro = PpsServicioSocial::query()->lockForUpdate()->findOrFail($registro->id);

            if ($registro->estado !== PpsServicioSocial::ESTADO_BORRADOR) {
                throw new RuntimeException('Solo los registros en estado borrador pueden enviarse a revision.');
            }

            if (!$registro->perteneceAlUsuario($userId)) {
                throw new RuntimeException('Solo el usuario creador puede enviar este registro a revision.');
            }

            $flujo = $this->resolverFlujoActivoParaEnvio($registro);
            $etapas = $registro->etapasActivasDelFlujo($flujo);

            if ($etapas->isEmpty()) {
                throw new RuntimeException('El flujo PPS/SS activo no tiene etapas activas configuradas.');
            }

            if (!$registro->flujo_aprobacion_id) {
                $registro->forceFill(['flujo_aprobacion_id' => $flujo->id])->saveQuietly();
            }

            $empleadosPorEtapa = $this->resolverEmpleadosPorEtapa($registro, $etapas);

            $ciclo = $this->obtenerCicloParaEnvio($registro);

            $registro->sincronizarFirmasDeEtapasDelFlujo($empleadosPorEtapa, $flujo, $ciclo);

            $primeraFirma = $registro->firmaActualDeEtapasDelFlujo((int) $flujo->id, $ciclo);

            if (!$primeraFirma) {
                throw new RuntimeException('No se pudo iniciar el flujo de revision de PPS/SS.');
            }

            $empleadoActor = $userId ? User::find($userId)?->empleado : null;

            if (!$empleadoActor) {
                throw new RuntimeException('El usuario no tiene un empleado activo asociado.');
            }

            $tipoEstadoId = $primeraFirma->cargo_firma()->value('tipo_estado_id');

            if (!$tipoEstadoId) {
                throw new RuntimeException('No se pudo determinar el estado inicial del flujo PPS/SS.');
            }

            $estadoOrigen = $registro->estado;
            $etapaOrigenId = $registro->etapa_actual_id;

            $registro->agregarEstado($empleadoActor, (int) $tipoEstadoId, 'Registro enviado a revision mediante flujo configurable PPS/SS.');
            $registro->forceFill([
                'etapa_actual_id' => $primeraFirma->flujo_aprobacion_etapa_id,
                'fecha_envio' => now(),
                'enviado_por' => $userId,
                'updated_by' => $userId,
            ])->saveQuietly();
            $registro->sincronizarEstadoCorto();

            $this->registrarHistorial($registro, 'enviar_revision', [
                'flujo_aprobacion_id' => $flujo->id,
                'etapa_origen_id' => $etapaOrigenId,
                'etapa_destino_id' => $primeraFirma->flujo_aprobacion_etapa_id,
                'estado_origen' => $estadoOrigen,
                'estado_destino' => $registro->estado,
                'comentario' => 'Registro enviado a revision mediante flujo configurable PPS/SS.',
                'realizado_por' => $userId,
            ]);

            $this->notificarRevisionPendiente($registro, $primeraFirma->flujoEtapa, 'envio_revision');

            return $registro->fresh(['flujoAprobacion', 'etapaActual']) ?? $registro;
        });
    }

    public function aprobarEtapa(PpsServicioSocial $registro, ?int $userId, ?object $user = null): PpsServicioSocial
    {
        return DB::transaction(function () use ($registro, $userId, $user): PpsServicioSocial {
            $registro = PpsServicioSocial::query()->lockForUpdate()->findOrFail($registro->id);
            $this->validarUsuarioRevisor($registro, $userId, $user);

            $firmaActual = $this->firmaActualODie($registro);
            $estadoOrigen = $registro->estado;

            $empleadoActor = $userId ? User::find($userId)?->empleado : null;

            if (!$empleadoActor) {
                throw new RuntimeException('El usuario no tiene un empleado activo asociado.');
            }

            $firmaActual->update([
                'estado_revision' => 'Aprobado',
                'fecha_firma' => now(),
            ]);

            $firmaAprobada = $firmaActual->fresh();
            $registro->anularFirmasPendientesDuplicadasDeEtapa(
                (int) $firmaAprobada->flujo_aprobacion_etapa_id,
                (int) $firmaAprobada->revision_ciclo,
                $firmaAprobada->id
            );

            $siguienteFirma = $registro->siguienteFirmaDeEtapa($firmaAprobada);

            if ($siguienteFirma) {
                $tipoEstadoId = $siguienteFirma->cargo_firma()->value('tipo_estado_id');

                if (!$tipoEstadoId) {
                    throw new RuntimeException('No se pudo determinar la siguiente etapa del flujo PPS/SS.');
                }

                $registro->agregarEstado($empleadoActor, (int) $tipoEstadoId, 'Registro avanzado a la siguiente etapa mediante flujo configurable PPS/SS.');
                $registro->forceFill([
                    'etapa_actual_id' => $siguienteFirma->flujo_aprobacion_etapa_id,
                    'updated_by' => $userId,
                ])->saveQuietly();
                $registro->sincronizarEstadoCorto();

                $this->registrarHistorial($registro, 'aprobar_etapa', [
                    'flujo_aprobacion_id' => $registro->flujo_aprobacion_id,
                    'etapa_origen_id' => $firmaAprobada->flujo_aprobacion_etapa_id,
                    'etapa_destino_id' => $siguienteFirma->flujo_aprobacion_etapa_id,
                    'estado_origen' => $estadoOrigen,
                    'estado_destino' => $registro->estado,
                    'comentario' => 'Registro avanzado a la siguiente etapa mediante flujo configurable PPS/SS.',
                    'realizado_por' => $userId,
                ]);

                $this->notificarRevisionPendiente($registro, $siguienteFirma->flujoEtapa, 'avance_etapa');

                return $registro->fresh(['flujoAprobacion', 'etapaActual']) ?? $registro;
            }

            if (!$registro->firmasDeEtapasCompletadas((int) $firmaAprobada->flujo_aprobacion_id, (int) $firmaAprobada->revision_ciclo)) {
                throw new RuntimeException('El recorrido de firmas no esta completo o contiene una etapa bloqueada.');
            }

            $estadoAprobadoId = TipoEstado::where('nombre', 'Aprobado')->value('id');

            if (!$estadoAprobadoId) {
                throw new RuntimeException('No existe un estado "Aprobado" configurado.');
            }

            $registro->agregarEstado($empleadoActor, (int) $estadoAprobadoId, 'Registro aprobado en etapa final mediante flujo configurable PPS/SS.');
            $registro->forceFill([
                'fecha_revision' => now(),
                'revisado_por' => $userId,
                'motivo_rechazo' => null,
                'updated_by' => $userId,
            ])->saveQuietly();
            $registro->sincronizarEstadoCorto();

            $this->registrarHistorial($registro, 'aprobar_final', [
                'flujo_aprobacion_id' => $registro->flujo_aprobacion_id,
                'etapa_origen_id' => $firmaAprobada->flujo_aprobacion_etapa_id,
                'etapa_destino_id' => $firmaAprobada->flujo_aprobacion_etapa_id,
                'estado_origen' => $estadoOrigen,
                'estado_destino' => $registro->estado,
                'comentario' => 'Registro aprobado en etapa final mediante flujo configurable PPS/SS.',
                'realizado_por' => $userId,
            ]);

            return $registro->fresh(['flujoAprobacion', 'etapaActual']) ?? $registro;
        });
    }

    public function rechazar(PpsServicioSocial $registro, string $motivo, ?int $userId, ?object $user = null): PpsServicioSocial
    {
        return DB::transaction(function () use ($registro, $motivo, $userId, $user): PpsServicioSocial {
            $registro = PpsServicioSocial::query()->lockForUpdate()->findOrFail($registro->id);
            $this->validarUsuarioRevisor($registro, $userId, $user);

            $motivo = trim($motivo);

            if ($motivo === '') {
                throw new RuntimeException('El motivo de rechazo es obligatorio.');
            }

            $firmaActual = $this->firmaActualODie($registro);
            $estadoOrigen = $registro->estado;

            $estadoRechazadoId = TipoEstado::where('nombre', 'Rechazado')->value('id');

            if (!$estadoRechazadoId) {
                throw new RuntimeException('No existe un estado "Rechazado" configurado.');
            }

            $empleadoActor = $userId ? User::find($userId)?->empleado : null;

            if (!$empleadoActor) {
                throw new RuntimeException('El usuario no tiene un empleado activo asociado.');
            }

            $firmaActual->update([
                'estado_revision' => 'Rechazado',
                'fecha_firma' => now(),
            ]);

            $registro->anularFirmasPendientesDuplicadasDeEtapa(
                (int) $firmaActual->flujo_aprobacion_etapa_id,
                (int) $firmaActual->revision_ciclo,
                $firmaActual->id
            );

            $registro->agregarEstado($empleadoActor, (int) $estadoRechazadoId, $motivo);
            $registro->forceFill([
                'fecha_revision' => now(),
                'revisado_por' => $userId,
                'motivo_rechazo' => $motivo,
                'updated_by' => $userId,
            ])->saveQuietly();
            $registro->sincronizarEstadoCorto();

            $this->registrarHistorial($registro, 'rechazar', [
                'flujo_aprobacion_id' => $registro->flujo_aprobacion_id,
                'etapa_origen_id' => $firmaActual->flujo_aprobacion_etapa_id,
                'etapa_destino_id' => $firmaActual->flujo_aprobacion_etapa_id,
                'estado_origen' => $estadoOrigen,
                'estado_destino' => $registro->estado,
                'motivo_rechazo' => $motivo,
                'comentario' => 'Registro rechazado mediante flujo configurable PPS/SS.',
                'realizado_por' => $userId,
            ]);

            return $registro->fresh(['flujoAprobacion', 'etapaActual']) ?? $registro;
        });
    }

    public function iniciarSubsanacion(PpsServicioSocial $registro, ?int $userId): PpsServicioSocial
    {
        return DB::transaction(function () use ($registro, $userId): PpsServicioSocial {
            $registro = PpsServicioSocial::query()->lockForUpdate()->findOrFail($registro->id);
            $this->validarPuedeSubsanar($registro, $userId);

            $empleadoActor = $userId ? User::find($userId)?->empleado : null;

            if (!$empleadoActor) {
                throw new RuntimeException('El usuario no tiene un empleado activo asociado.');
            }

            $estadoBorradorId = TipoEstado::where('nombre', 'Borrador')->value('id');

            if (!$estadoBorradorId) {
                throw new RuntimeException('No existe un estado "Borrador" configurado.');
            }

            $estadoOrigen = $registro->estado;
            $etapaOrigenId = $registro->etapa_actual_id;

            $registro->agregarEstado($empleadoActor, (int) $estadoBorradorId, 'Registro devuelto a borrador para subsanacion.');
            $registro->forceFill(['updated_by' => $userId])->saveQuietly();
            $registro->sincronizarEstadoCorto();

            $this->registrarHistorial($registro, 'iniciar_subsanacion', [
                'flujo_aprobacion_id' => $registro->flujo_aprobacion_id,
                'etapa_origen_id' => $etapaOrigenId,
                'etapa_destino_id' => $etapaOrigenId,
                'estado_origen' => $estadoOrigen,
                'estado_destino' => $registro->estado,
                'comentario' => 'Registro devuelto a borrador para subsanacion.',
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

    /**
     * Conservado por compatibilidad con PpsServicioSocial::puedeSubsanarse().
     * PPS ya no distingue una "etapa editable": la subsanación siempre
     * devuelve el registro a Borrador (ver iniciarSubsanacion()).
     */
    public function obtenerEtapaEditable(PpsServicioSocial $registro): ?FlujoAprobacionEtapa
    {
        return null;
    }

    public function validarEtapaActualDelFlujo(PpsServicioSocial $registro): FlujoAprobacionEtapa
    {
        $firma = $this->firmaActualODie($registro);

        return $firma->flujoEtapa;
    }

    public function puedeRechazarEtapaActual(PpsServicioSocial $registro): bool
    {
        try {
            $this->firmaActualODie($registro);
        } catch (\Throwable) {
            return false;
        }

        return true;
    }

    public function esEstadoFinalAprobado(PpsServicioSocial $registro): bool
    {
        return $registro->estado === PpsServicioSocial::ESTADO_APROBADO;
    }

    public function registrarHistorial(PpsServicioSocial $registro, string $accion, array $datos = []): PpsServicioSocialRevisionHistorial
    {
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

    private function firmaActualODie(PpsServicioSocial $registro): FirmaProyecto
    {
        if (!$registro->flujo_aprobacion_id) {
            throw new RuntimeException('El registro no tiene un flujo asignado.');
        }

        $firma = $registro->firmaActualDeEtapasDelFlujo(
            (int) $registro->flujo_aprobacion_id,
            $this->obtenerCicloVigente($registro)
        );

        if (!$firma) {
            throw new RuntimeException('El registro no esta en una etapa revisable del flujo PPS/SS.');
        }

        return $firma;
    }

    public function obtenerUltimoCiclo(PpsServicioSocial $registro): int
    {
        return max(0, (int) $registro->firma_proyecto()
            ->whereNotNull('flujo_aprobacion_etapa_id')
            ->max('revision_ciclo'));
    }

    public function obtenerCicloVigente(PpsServicioSocial $registro): int
    {
        return max(1, $this->obtenerUltimoCiclo($registro));
    }

    public function esReenvioDespuesDeRechazo(PpsServicioSocial $registro): bool
    {
        $ciclo = $this->obtenerUltimoCiclo($registro);

        return $ciclo > 0 && $registro->firma_proyecto()
            ->where('revision_ciclo', $ciclo)
            ->where('estado_revision', 'Rechazado')
            ->exists();
    }

    public function obtenerCicloParaEnvio(PpsServicioSocial $registro): int
    {
        $ultimoCiclo = $this->obtenerUltimoCiclo($registro);

        return $this->esReenvioDespuesDeRechazo($registro)
            ? $ultimoCiclo + 1
            : max(1, $ultimoCiclo ?: 1);
    }

    private function flujoPpsQuery(): Builder
    {
        return FlujoAprobacion::query()->where('proceso', PpsServicioSocial::PROCESO_FLUJO);
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

        $flujoAsignado = $this->flujoPpsQuery()
            ->whereKey($registro->flujo_aprobacion_id)
            ->where('activo', true)
            ->first();

        if (!$flujoAsignado) {
            throw new RuntimeException('El flujo asignado al registro no esta activo o no pertenece al proceso PPS/Servicio Social.');
        }

        return $flujoAsignado;
    }

    private function resolverEmpleadosPorEtapa(PpsServicioSocial $registro, Collection $etapas): array
    {
        $destinatariosEmisor = $registro->destinatarios_emisor ?? [];
        $empleadosPorEtapa = [];

        foreach ($etapas as $etapa) {
            $etapaId = (int) $etapa->id;

            if ($etapa->emisor_define_destinatario && isset($destinatariosEmisor[$etapaId])) {
                $empleado = User::find((int) $destinatariosEmisor[$etapaId])?->empleado;

                if (!$empleado) {
                    throw new RuntimeException(sprintf('El usuario seleccionado para la etapa "%s" no tiene empleado vinculado.', $etapa->nombre));
                }

                $empleadosPorEtapa[$etapaId] = $empleado->id;
                continue;
            }

            if ($etapa->usuario_responsable_id) {
                $empleado = User::find((int) $etapa->usuario_responsable_id)?->empleado;

                if (!$empleado) {
                    throw new RuntimeException(sprintf('El usuario responsable configurado para la etapa "%s" no tiene empleado vinculado.', $etapa->nombre));
                }

                $empleadosPorEtapa[$etapaId] = $empleado->id;
                continue;
            }

            throw new RuntimeException(sprintf('La etapa "%s" no tiene destinatario configurado. Active "El emisor define el destinatario" o establezca un usuario responsable.', $etapa->nombre));
        }

        return $empleadosPorEtapa;
    }

    private function validarUsuarioRevisor(PpsServicioSocial $registro, ?int $userId, ?object $user = null): void
    {
        if (!$registro->usuarioPuedeRevisar($user ?? auth()->user())) {
            throw new RuntimeException('El usuario no tiene permisos para revisar registros PPS/SS.');
        }
    }

    private function notificarRevisionPendiente(PpsServicioSocial $registro, FlujoAprobacionEtapa $etapa, string $evento): void
    {
        $destinatarios = $this->usuariosResponsablesDeEtapa($etapa, $registro);
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
                ->map(fn (User $user): array => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email])
                ->values()
                ->all(),
        ]);
    }

    private function usuariosResponsablesDeEtapa(FlujoAprobacionEtapa $etapa, ?PpsServicioSocial $registro = null): Collection
    {
        $query = User::query()->select(['id', 'name', 'email']);

        $destinatariosEmisores = $registro?->destinatarios_emisor ?? [];
        $usuarioEmisorId = $destinatariosEmisores[$etapa->id] ?? null;

        if ((bool) ($etapa->emisor_define_destinatario ?? false) && $usuarioEmisorId) {
            $query->whereKey((int) $usuarioEmisorId);
        } elseif ((bool) $etapa->requiere_asignacion) {
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
}
