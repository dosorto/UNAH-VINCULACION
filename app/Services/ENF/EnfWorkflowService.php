<?php

namespace App\Services\ENF;

use App\Mail\EnfRevisionAsignada;
use App\Models\ENF\EnfAccion;
use App\Models\ENF\EnfInformeFinal;
use App\Models\ENF\EnfInformeIntermedio;
use App\Models\ENF\EnfRevision;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\User;
use App\Services\ENF\Constancias\EmitirConstanciaFinalizacionEnf;
use App\Services\ENF\Constancias\EmitirConstanciaRegistroEnf;
use App\Services\Workflow\WorkflowResumptionPolicy;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EnfWorkflowService
{
    public const MAX_PDF_BYTES = 20 * 1024 * 1024;

    public function usuarioPuedeGestionar(EnfAccion $accion, ?User $user): bool
    {
        return $user && ((int) $accion->creado_por_usuario_id === (int) $user->id || $user->can('docente.proyectos') || $user->hasRole('admin'));
    }

    public function puedeGestionarInformeIntermedio(EnfAccion $accion, ?User $user): bool
    {
        return $this->usuarioPuedeGestionar($accion, $user)
            && $this->inscripcionAprobada($accion)
            && $this->etapas($accion, EnfAccion::PROCESO_INFORME_INTERMEDIO)->isNotEmpty();
    }

    public function puedeGestionarInformeFinal(EnfAccion $accion, ?User $user): bool
    {
        return $this->usuarioPuedeGestionar($accion, $user)
            && $this->inscripcionAprobada($accion)
            && (! $this->etapas($accion, EnfAccion::PROCESO_INFORME_INTERMEDIO)->isNotEmpty()
                || $accion->informeIntermedio?->estado === EnfInformeIntermedio::ESTADO_APROBADO)
            && $this->etapas($accion, EnfAccion::PROCESO_INFORME_FINAL)->isNotEmpty();
    }

    public function resumenInformeFinal(EnfAccion $accion, ?User $user): array
    {
        $visible = $this->puedeGestionarInformeFinal($accion, $user) || (bool) $accion->informeFinal;
        $informe = $accion->informeFinal;

        if (! $informe) {
            return [
                'visible' => $visible,
                'informe' => null,
                'estado' => null,
                'etiqueta' => 'Pendiente de creación',
                'accion' => $visible ? 'crear' : null,
                'texto_accion' => 'Crear informe final',
                'puede_enviar' => false,
            ];
        }

        $revisionActual = $this->revisionActualQuery($accion, EnfAccion::PROCESO_INFORME_FINAL)
            ->with(['asignadoUsuario.empleado', 'responsableUsuario.empleado'])
            ->orderBy('orden')
            ->first();

        $accionBoton = match ($informe->estado) {
            EnfInformeFinal::ESTADO_BORRADOR => 'continuar',
            EnfInformeFinal::ESTADO_COMPLETO => 'enviar',
            EnfInformeFinal::ESTADO_EN_REVISION => 'ver',
            EnfInformeFinal::ESTADO_SUBSANACION => 'subsanar',
            EnfInformeFinal::ESTADO_APROBADO => 'aprobado',
            default => null,
        };

        return [
            'visible' => $visible,
            'informe' => $informe,
            'estado' => $informe->estado,
            'etiqueta' => match ($informe->estado) {
                EnfInformeFinal::ESTADO_BORRADOR => 'Borrador',
                EnfInformeFinal::ESTADO_COMPLETO => 'Completo, listo para envío',
                EnfInformeFinal::ESTADO_EN_REVISION => 'En revisión',
                EnfInformeFinal::ESTADO_SUBSANACION => 'Pendiente de subsanación',
                EnfInformeFinal::ESTADO_APROBADO => 'Aprobado',
                default => str_replace('_', ' ', (string) $informe->estado),
            },
            'accion' => $visible ? $accionBoton : null,
            'texto_accion' => match ($accionBoton) {
                'continuar' => 'Continuar informe final',
                'enviar' => 'Revisar y enviar informe final',
                'ver' => 'Informe final en revisión',
                'subsanar' => 'Subsanar informe final',
                'aprobado' => 'Ver informe final aprobado',
                default => null,
            },
            'puede_enviar' => $this->puedeGestionarInformeFinal($accion, $user)
                && in_array($informe->estado, [EnfInformeFinal::ESTADO_COMPLETO, EnfInformeFinal::ESTADO_SUBSANACION], true),
            'fecha_creacion' => $informe->created_at,
            'fecha_envio' => $informe->fecha_envio,
            'etapa_actual' => $revisionActual?->etapa_nombre,
            'revisor_actual' => $revisionActual?->asignadoUsuario?->empleado?->nombre_completo
                ?? $revisionActual?->responsableUsuario?->empleado?->nombre_completo,
            'motivo_rechazo' => $informe->estado === EnfInformeFinal::ESTADO_SUBSANACION
                ? $informe->observaciones_revision
                : null,
        ];
    }

    public function guardarInformeIntermedio(EnfAccion $accion, UploadedFile $archivo, User $user): EnfInformeIntermedio
    {
        abort_unless($this->puedeGestionarInformeIntermedio($accion, $user), 403);
        $existente = $accion->informeIntermedio;
        if ($existente && ! $existente->esEditable()) {
            throw new \RuntimeException('El informe intermedio no puede reemplazarse mientras esta en revision o aprobado.');
        }

        $metadatos = $this->validarPdf($archivo);
        $path = sprintf('enf/informes-intermedios/%d/%s.pdf', $accion->id, Str::uuid());
        Storage::disk('local')->putFileAs(dirname($path), $archivo, basename($path));
        $pathAnterior = $existente?->archivo_pdf;

        $informe = EnfInformeIntermedio::updateOrCreate(
            ['enf_accion_id' => $accion->id],
            [
                'archivo_pdf' => $path,
                'nombre_original' => $metadatos['nombre'],
                'mime_type' => 'application/pdf',
                'tamano_bytes' => $metadatos['tamano'],
                'hash_sha256' => $metadatos['hash'],
                'estado' => $existente?->estado ?? EnfInformeIntermedio::ESTADO_BORRADOR,
                'subido_por_usuario_id' => $user->id,
                'fecha_carga' => now(),
            ]
        );

        if ($pathAnterior && $pathAnterior !== $path) {
            Storage::disk('local')->delete($pathAnterior);
        }

        return $informe->fresh();
    }

    public function enviarInscripcion(EnfAccion $accion, User $user, array $usuariosElegidosPorEtapa = []): bool
    {
        abort_unless($this->usuarioPuedeGestionar($accion, $user), 403);

        DB::transaction(function () use ($accion, $user, $usuariosElegidosPorEtapa): void {
            $bloqueada = EnfAccion::query()->whereKey($accion->id)->lockForUpdate()->firstOrFail();

            if (! in_array(strtoupper((string) $bloqueada->estado_flujo), ['BORRADOR', 'SUBSANACION', 'SUBSANACIÓN'], true)) {
                throw new \RuntimeException('La inscripción ENF ya fue enviada a revisión.');
            }

            $ciclo = $this->iniciarRevisiones($bloqueada, EnfAccion::PROCESO_INSCRIPCION, $usuariosElegidosPorEtapa);
            $bloqueada->update([
                'estado_flujo' => 'EN_REVISION',
                'revision_ciclo' => $ciclo,
                'modificado_por_usuario_id' => $user->id,
            ]);
        });

        return true;
    }

    public function enviarInformeIntermedio(EnfInformeIntermedio $informe, User $user, array $usuariosElegidosPorEtapa = []): void
    {
        abort_unless($this->puedeGestionarInformeIntermedio($informe->accion, $user), 403);
        $this->validarPdfPersistido($informe->archivo_pdf, $informe->hash_sha256, $informe->tamano_bytes, 'local');

        DB::transaction(function () use ($informe, $user, $usuariosElegidosPorEtapa): void {
            $bloqueado = EnfInformeIntermedio::query()->whereKey($informe->id)->lockForUpdate()->firstOrFail();
            if (! $bloqueado->esEditable()) {
                throw new \RuntimeException('El informe intermedio ya fue enviado.');
            }

            $ciclo = $this->iniciarRevisiones($bloqueado->accion, EnfAccion::PROCESO_INFORME_INTERMEDIO, $usuariosElegidosPorEtapa);
            $bloqueado->update([
                'estado' => EnfInformeIntermedio::ESTADO_EN_REVISION,
                'revision_ciclo' => $ciclo,
                'enviado_por_usuario_id' => $user->id,
                'fecha_envio' => now(),
                'observaciones_revision' => null,
            ]);
        });
    }

    public function enviarInformeFinal(EnfInformeFinal $informe, User $user, array $usuariosElegidosPorEtapa = []): void
    {
        $accion = $informe->accion;
        abort_unless($this->puedeGestionarInformeFinal($accion, $user), 403);
        if (! in_array($informe->estado, [EnfInformeFinal::ESTADO_COMPLETO, EnfInformeFinal::ESTADO_SUBSANACION], true)) {
            throw new \RuntimeException('Debe validar el informe final antes de enviarlo a revision.');
        }

        $contenido = Pdf::loadView('pdf.enf.informes-finales.form-final', [
            'accion' => $accion->loadMissing($this->relacionesPdf()),
            'informe' => $informe->fresh(['participantesFinales', 'accionesEjecutadas', 'accionesNoEjecutadas']),
            'isPdf' => true,
        ])->setPaper('letter', 'portrait')->output();

        $path = sprintf('enf/informes-finales/%d/revision-%s.pdf', $accion->id, Str::uuid());
        Storage::disk('public')->put($path, $contenido);

        try {
            DB::transaction(function () use ($informe, $user, $path, $usuariosElegidosPorEtapa): void {
                $bloqueado = EnfInformeFinal::query()->whereKey($informe->id)->lockForUpdate()->firstOrFail();
                if (! $bloqueado->esEditable()) {
                    throw new \RuntimeException('El informe final ya fue enviado.');
                }

                $ciclo = $this->iniciarRevisiones($bloqueado->accion, EnfAccion::PROCESO_INFORME_FINAL, $usuariosElegidosPorEtapa);
                $pathAnterior = $bloqueado->archivo_pdf;
                $bloqueado->update([
                    'estado' => EnfInformeFinal::ESTADO_EN_REVISION,
                    'revision_ciclo' => $ciclo,
                    'archivo_pdf' => $path,
                    'fecha_envio' => now(),
                    'enviado_por_usuario_id' => $user->id,
                    'observaciones_revision' => null,
                ]);
                if ($pathAnterior && $pathAnterior !== $path) {
                    Storage::disk('public')->delete($pathAnterior);
                }
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($path);
            throw $exception;
        }
    }

    public function aprobarRevision(EnfRevision $revision, User $user, ?string $observacion = null): void
    {
        DB::transaction(function () use ($revision, $user, $observacion): void {
            $revision->update([
                'estado' => 'APROBADO',
                'observaciones' => filled($observacion) ? $observacion : $revision->observaciones,
                'decidido_por_usuario_id' => $user->id,
                'firmado_en' => now(),
            ]);

            $accion = $revision->accion;
            $siguiente = $this->revisionActualQuery($accion, $revision->proceso)
                ->where('orden', '>', $revision->orden)
                ->orderBy('orden')
                ->first();

            if ($siguiente) {
                $siguiente->update(['estado' => $siguiente->asignado_usuario_id || $siguiente->responsable_usuario_id ? 'ASIGNADO' : 'PENDIENTE']);
                $this->notificarRevision($accion, $siguiente->fresh('flujoEtapa.rolRevisor'));

                return;
            }

            $this->marcarProcesoAprobado($accion, $revision->proceso, $user);
        });
    }

    public function subsanarRevision(EnfRevision $revision, string $comentario, User $user): void
    {
        DB::transaction(function () use ($revision, $comentario, $user): void {
            $revision->update([
                'estado' => 'SUBSANACION',
                'observaciones' => $comentario,
                'decidido_por_usuario_id' => $user->id,
                'firmado_en' => now(),
            ]);

            match ($revision->proceso) {
                EnfAccion::PROCESO_INFORME_INTERMEDIO => $revision->accion->informeIntermedio?->update([
                    'estado' => EnfInformeIntermedio::ESTADO_SUBSANACION,
                    'observaciones_revision' => $comentario,
                ]),
                EnfAccion::PROCESO_INFORME_FINAL => $revision->accion->informeFinal?->update([
                    'estado' => EnfInformeFinal::ESTADO_SUBSANACION,
                    'observaciones_revision' => $comentario,
                ]),
                default => $revision->accion->update(['estado_flujo' => 'SUBSANACION']),
            };
        });
    }

    public function puedeRevisar(EnfRevision $revision, User $user): bool
    {
        $activeRoleName = $user->activeRole?->name;
        if (! $activeRoleName || ! in_array($revision->estado, $this->estadosPendientes(), true)) {
            return false;
        }

        $actual = $this->revisionActualQuery($revision->accion, $revision->proceso)
            ->orderBy('orden')
            ->first();
        if (! $actual || (int) $actual->id !== (int) $revision->id) {
            return false;
        }

        if (filled($revision->rol_requerido) && $revision->rol_requerido !== $activeRoleName) {
            return false;
        }

        if ($revision->asignado_usuario_id) {
            return (int) $revision->asignado_usuario_id === (int) $user->id;
        }

        if ($revision->responsable_usuario_id) {
            return (int) $revision->responsable_usuario_id === (int) $user->id;
        }

        return filled($revision->rol_requerido) && $revision->rol_requerido === $activeRoleName;
    }

    public function etapas(EnfAccion $accion, string $proceso): Collection
    {
        $flujo = $this->resolverFlujo($accion);
        if (! $flujo) {
            return collect();
        }

        $columna = match ($proceso) {
            EnfAccion::PROCESO_INFORME_INTERMEDIO => 'aplica_informe_intermedio',
            EnfAccion::PROCESO_INFORME_FINAL => 'aplica_cierre_proyecto',
            default => 'aplica_inscripcion',
        };

        return $flujo->etapas
            ->where('activo', true)
            ->filter(fn (FlujoAprobacionEtapa $etapa): bool => (bool) $etapa->{$columna})
            ->sortBy('orden')
            ->values();
    }

    public function estadosPendientes(): array
    {
        return ['PENDIENTE', 'PENDIENTE_ASIGNACION', 'ASIGNADO', 'EN_PROCESO'];
    }

    public function destinatariosSeleccionables(EnfAccion $accion, string $proceso): Collection
    {
        if ($this->procesoEnSubsanacion($accion, $proceso)) {
            return $this->planHistorico($accion, $proceso)->stages
                ->filter(function (array $snapshot): bool {
                    /** @var EnfRevision $revision */
                    $revision = $snapshot['source'];

                    return ! $this->responsableHistoricoElegible($revision);
                })
                ->mapWithKeys(function (array $snapshot): array {
                    /** @var EnfRevision $revision */
                    $revision = $snapshot['source'];
                    $etapa = $revision->flujoEtapa;

                    if (! $etapa) {
                        $etapa = new FlujoAprobacionEtapa;
                        $etapa->forceFill([
                            'id' => $revision->flujo_aprobacion_etapa_id,
                            'orden' => $revision->orden,
                            'codigo' => $revision->etapa_codigo,
                            'nombre' => $revision->etapa_nombre,
                        ]);
                    }

                    $usuarios = $revision->rol_requerido
                        ? User::role($revision->rol_requerido)
                            ->whereHas('empleado')
                            ->with('empleado')
                            ->orderBy('name')
                            ->get()
                        : User::query()->whereHas('empleado')->with('empleado')->orderBy('name')->get();
                    $usuarios = $usuarios
                        ->filter(fn (User $user): bool => filled($user->email) && filter_var($user->email, FILTER_VALIDATE_EMAIL))
                        ->values();

                    return [(int) $revision->flujo_aprobacion_etapa_id => [
                        'etapa' => $etapa,
                        'usuarios' => $usuarios,
                        'rol_requerido' => $revision->rol_requerido,
                    ]];
                });
        }

        return $this->etapas($accion, $proceso)
            ->filter(fn (FlujoAprobacionEtapa $etapa): bool => (bool) $etapa->emisor_define_destinatario)
            ->mapWithKeys(function (FlujoAprobacionEtapa $etapa): array {
                $usuarios = $etapa->rolRevisor?->name
                    ? User::role($etapa->rolRevisor->name)
                        ->whereHas('empleado')
                        ->with('empleado')
                        ->orderBy('name')
                        ->get()
                    : collect();
                $usuarios = $usuarios
                    ->filter(fn (User $user): bool => filled($user->email) && filter_var($user->email, FILTER_VALIDATE_EMAIL))
                    ->values();

                return [$etapa->id => [
                    'etapa' => $etapa,
                    'usuarios' => $usuarios,
                ]];
            });
    }

    public function revisionActualQuery(EnfAccion $accion, string $proceso)
    {
        $ciclo = (int) $accion->revisiones()->where('proceso', $proceso)->max('revision_ciclo');

        return $accion->revisiones()
            ->where('proceso', $proceso)
            ->where('revision_ciclo', $ciclo)
            ->whereIn('estado', $this->estadosPendientes());
    }

    public function inscripcionAprobada(EnfAccion $accion): bool
    {
        return strtoupper((string) $accion->estado_flujo) === 'APROBADO';
    }

    private function iniciarRevisiones(EnfAccion $accion, string $proceso, array $usuariosElegidosPorEtapa = []): int
    {
        EnfAccion::query()->whereKey($accion->id)->lockForUpdate()->firstOrFail();
        $esReenvio = $this->procesoEnSubsanacion($accion, $proceso);
        $etapas = $esReenvio
            ? $this->planHistorico($accion, $proceso)->stages->pluck('source')->values()
            : $this->etapas($accion, $proceso);

        if ($etapas->isEmpty()) {
            throw new \RuntimeException('No hay etapas configuradas para este proceso ENF.');
        }

        $nextCycle = ((int) $accion->revisiones()->where('proceso', $proceso)->max('revision_ciclo')) + 1;
        $creadas = collect();
        foreach ($etapas as $stage) {
            if ($stage instanceof EnfRevision) {
                $stageId = (int) $stage->flujo_aprobacion_etapa_id;
                $defaultReviewer = $this->responsableHistoricoElegible($stage)
                    ?? $this->resolverReemplazoHistorico($stage, $usuariosElegidosPorEtapa);
                $requiresAssignment = false;
                $orden = $stage->orden;
                $codigo = $stage->etapa_codigo;
                $nombre = $stage->etapa_nombre;
                $rol = $stage->rol_requerido;
                $responsableId = $defaultReviewer->id;
            } else {
                $stageId = (int) $stage->id;
                $defaultReviewer = $this->resolverUsuarioEtapa($stage, $usuariosElegidosPorEtapa);
                $requiresAssignment = (bool) ($stage->requiere_asignacion ?? false) && ! $defaultReviewer;
                $orden = $stage->orden;
                $codigo = $stage->codigo;
                $nombre = $stage->nombre;
                $rol = $stage->rolRevisor?->name;
                $responsableId = $stage->emisor_define_destinatario ? null : $stage->usuario_responsable_id;
            }

            $creadas->push($accion->revisiones()->create([
                'proceso' => $proceso,
                'flujo_aprobacion_etapa_id' => $stageId,
                'revision_ciclo' => $nextCycle,
                'orden' => $orden,
                'etapa_codigo' => $codigo,
                'etapa_nombre' => $nombre,
                'rol_requerido' => $rol,
                'responsable_usuario_id' => $responsableId,
                'asignado_usuario_id' => $requiresAssignment ? null : $defaultReviewer?->id,
                'estado' => $requiresAssignment ? 'PENDIENTE_ASIGNACION' : ($defaultReviewer ? 'ASIGNADO' : 'PENDIENTE'),
            ]));
        }

        if (! $esReenvio) {
            $flujo = $this->resolverFlujo($accion);
            if ($flujo && ! $accion->flujo_aprobacion_id) {
                $accion->update(['flujo_aprobacion_id' => $flujo->id]);
            }
        }

        $primera = $creadas->sortBy('orden')->first();
        if ($primera) {
            $this->notificarRevision($accion, $primera->fresh('flujoEtapa.rolRevisor'));
        }

        Log::info('Ciclo ENF preparado', [
            'proceso' => $proceso,
            'registro_id' => $accion->id,
            'flujo_id' => $accion->flujo_aprobacion_id ?: $this->resolverFlujo($accion)?->id,
            'ciclo_anterior' => $esReenvio ? $nextCycle - 1 : null,
            'ciclo_nuevo' => $nextCycle,
            'etapa_retorno_id' => $primera?->flujo_aprobacion_etapa_id,
            'revisor_usuario_id' => $primera?->asignado_usuario_id ?: $primera?->responsable_usuario_id,
        ]);

        return $nextCycle;
    }

    private function procesoEnSubsanacion(EnfAccion $accion, string $proceso): bool
    {
        return match ($proceso) {
            EnfAccion::PROCESO_INFORME_INTERMEDIO => $accion->informeIntermedio?->estado === EnfInformeIntermedio::ESTADO_SUBSANACION,
            EnfAccion::PROCESO_INFORME_FINAL => $accion->informeFinal?->estado === EnfInformeFinal::ESTADO_SUBSANACION,
            default => in_array(strtoupper((string) $accion->estado_flujo), ['SUBSANACION', 'SUBSANACIÓN'], true),
        };
    }

    private function planHistorico(EnfAccion $accion, string $proceso): \App\Services\Workflow\WorkflowResumptionPlan
    {
        $ultimoCiclo = (int) $accion->revisiones()->where('proceso', $proceso)->max('revision_ciclo');
        $revisiones = $accion->revisiones()
            ->with(['flujoEtapa.rolRevisor', 'asignadoUsuario', 'responsableUsuario', 'decididoPorUsuario'])
            ->where('proceso', $proceso)
            ->where('revision_ciclo', $ultimoCiclo)
            ->orderBy('orden')
            ->get();

        return app(WorkflowResumptionPolicy::class)->plan(
            $revisiones->map(fn (EnfRevision $revision): array => [
                'stage_id' => (int) $revision->flujo_aprobacion_etapa_id,
                'order' => (int) $revision->orden,
                'status' => match ($revision->estado) {
                    'APROBADO' => 'APPROVED',
                    'SUBSANACION' => 'REJECTED',
                    'PENDIENTE', 'PENDIENTE_ASIGNACION', 'ASIGNADO', 'EN_PROCESO' => 'PENDING',
                    default => 'INVALID',
                },
                'source' => $revision,
            ])->values()
        );
    }

    private function responsableHistoricoElegible(EnfRevision $revision): ?User
    {
        $usuarioId = $revision->estado === 'SUBSANACION'
            ? $revision->decidido_por_usuario_id
            : ($revision->asignado_usuario_id ?: $revision->responsable_usuario_id);
        $usuario = $usuarioId ? User::withTrashed()->find($usuarioId) : null;

        return app(WorkflowResumptionPolicy::class)->eligibleRecipient($usuario, $revision->rol_requerido, true);
    }

    private function resolverReemplazoHistorico(EnfRevision $revision, array $usuariosElegidosPorEtapa): User
    {
        $usuarioId = $usuariosElegidosPorEtapa[(int) $revision->flujo_aprobacion_etapa_id] ?? null;
        $usuario = $usuarioId ? User::with('empleado')->find((int) $usuarioId) : null;
        $elegible = app(WorkflowResumptionPolicy::class)->eligibleRecipient($usuario, $revision->rol_requerido, true);

        if (! $elegible) {
            throw new \RuntimeException(sprintf(
                'El revisor anterior de la etapa "%s" ya no es elegible; seleccione un reemplazo válido.',
                $revision->etapa_nombre
            ));
        }

        return $elegible;
    }

    private function marcarProcesoAprobado(EnfAccion $accion, string $proceso, ?User $user = null): void
    {
        match ($proceso) {
            EnfAccion::PROCESO_INFORME_INTERMEDIO => $accion->informeIntermedio?->update([
                'estado' => EnfInformeIntermedio::ESTADO_APROBADO,
                'fecha_aprobacion' => now(),
            ]),
            EnfAccion::PROCESO_INFORME_FINAL => $this->marcarInformeFinalAprobado($accion, $user),
            default => $this->marcarInscripcionAprobada($accion, $user),
        };
    }

    private function marcarInscripcionAprobada(EnfAccion $accion, ?User $user = null): void
    {
        $accion->update([
            'estado_flujo' => 'APROBADO',
            'fecha_aprobacion' => now()->toDateString(),
        ]);

        $accionId = $accion->id;
        $usuarioId = $user?->id;
        DB::afterCommit(function () use ($accionId, $usuarioId): void {
            try {
                $accion = EnfAccion::query()->find($accionId);
                if ($accion) {
                    app(EmitirConstanciaRegistroEnf::class)->emitir($accion, $usuarioId);
                }
            } catch (\Throwable $exception) {
                Log::error('No se pudo emitir la constancia de registro ENF.', ['enf_accion_id' => $accionId, 'error' => $exception->getMessage()]);
            }
        });
    }

    private function marcarInformeFinalAprobado(EnfAccion $accion, ?User $user = null): void
    {
        $accion->informeFinal?->update([
            'estado' => EnfInformeFinal::ESTADO_APROBADO,
            'fecha_aprobacion' => now()->toDateString(),
        ]);
        $accion->update(['estado_flujo' => 'FINALIZADO']);

        $accionId = $accion->id;
        $informeId = $accion->informeFinal?->id;
        $usuarioId = $user?->id;
        DB::afterCommit(function () use ($accionId, $informeId, $usuarioId): void {
            try {
                $accion = EnfAccion::query()->find($accionId);
                $informe = $informeId ? EnfInformeFinal::query()->find($informeId) : null;
                if ($accion && $informe) {
                    app(EmitirConstanciaFinalizacionEnf::class)->emitir($accion, $informe, $usuarioId);
                }
            } catch (\Throwable $exception) {
                Log::error('No se pudo emitir la constancia de finalizacion ENF.', ['enf_accion_id' => $accionId, 'informe_id' => $informeId, 'error' => $exception->getMessage()]);
            }
        });
    }

    private function resolverFlujo(EnfAccion $accion): ?FlujoAprobacion
    {
        if ($accion->flujo_aprobacion_id) {
            return FlujoAprobacion::query()
                ->with(['etapas' => fn ($query) => $query->where('activo', true)->orderBy('orden'), 'etapas.rolRevisor', 'etapas.usuarioResponsable'])
                ->whereKey($accion->flujo_aprobacion_id)
                ->where('proceso', 'PROYECTO')
                ->first();
        }

        return FlujoAprobacion::query()
            ->with(['etapas' => fn ($query) => $query->where('activo', true)->orderBy('orden'), 'etapas.rolRevisor', 'etapas.usuarioResponsable'])
            ->where('proceso', 'PROYECTO')
            ->where('codigo_formulario', $accion->codigo_formulario)
            ->where('activo', true)
            ->when($accion->tipo_accion_id, fn ($query) => $query->orderByRaw('tipo_accion_id = ? desc', [(int) $accion->tipo_accion_id]))
            ->orderBy('id')
            ->first();
    }

    private function resolverRevisorPredeterminado(FlujoAprobacionEtapa $stage): ?User
    {
        if ($stage->usuario_responsable_id && ! $stage->requiere_asignacion) {
            return $stage->usuarioResponsable;
        }

        return $stage->rolRevisor?->name
            ? User::role($stage->rolRevisor->name)->orderBy('name')->first()
            : null;
    }

    private function resolverUsuarioEtapa(FlujoAprobacionEtapa $stage, array $usuariosElegidosPorEtapa): ?User
    {
        $stage->loadMissing(['usuarioResponsable', 'rolRevisor']);

        if ($stage->emisor_define_destinatario) {
            $usuarioId = $usuariosElegidosPorEtapa[$stage->id] ?? null;

            if (! $usuarioId) {
                throw new \RuntimeException(sprintf(
                    'Debe seleccionar un destinatario para la etapa "%s".',
                    $stage->nombre
                ));
            }

            $usuario = User::with('empleado')->find($usuarioId);
            $rol = $stage->rolRevisor?->name;

            if (! $usuario || ! $rol || ! $usuario->hasRole($rol)) {
                throw new \RuntimeException(sprintf(
                    'El destinatario seleccionado para la etapa "%s" no pertenece al rol requerido.',
                    $stage->nombre
                ));
            }

            return $usuario;
        }

        return $this->resolverRevisorPredeterminado($stage);
    }

    private function validarPdf(UploadedFile $archivo): array
    {
        if (! $archivo->isValid() || (int) $archivo->getSize() > self::MAX_PDF_BYTES) {
            throw new \RuntimeException('El PDF debe ser valido y pesar como maximo 20 MB.');
        }

        $handle = fopen($archivo->getRealPath(), 'rb');
        $cabecera = $handle ? fread($handle, 5) : false;
        if (is_resource($handle)) {
            fclose($handle);
        }

        if ($cabecera !== '%PDF-' || strtolower($archivo->getClientOriginalExtension()) !== 'pdf') {
            throw new \RuntimeException('El archivo debe ser un PDF valido.');
        }

        return [
            'nombre' => $archivo->getClientOriginalName(),
            'tamano' => (int) $archivo->getSize(),
            'hash' => hash_file('sha256', $archivo->getRealPath()),
        ];
    }

    private function validarPdfPersistido(?string $path, ?string $hash, int $tamano, string $disk): void
    {
        if (! $path || ! Storage::disk($disk)->exists($path)) {
            throw new \RuntimeException('Debe cargar un PDF antes de enviar.');
        }

        $realPath = Storage::disk($disk)->path($path);
        if (filesize($realPath) !== $tamano || hash_file('sha256', $realPath) !== $hash) {
            throw new \RuntimeException('El PDF almacenado no supera la validacion de integridad.');
        }
    }

    private function notificarRevision(EnfAccion $accion, EnfRevision $revision): void
    {
        $users = collect();
        if ($revision->asignado_usuario_id) {
            $users = User::query()->whereKey($revision->asignado_usuario_id)->get();
        } elseif ($revision->responsable_usuario_id) {
            $users = User::query()->whereKey($revision->responsable_usuario_id)->get();
        } elseif ($revision->flujoEtapa?->rolRevisor?->name) {
            $users = User::role($revision->flujoEtapa->rolRevisor->name)->orderBy('name')->get();
        }

        $emails = $users->pluck('email')
            ->filter(fn (?string $email): bool => filled($email) && filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values();
        if ($emails->isEmpty()) {
            throw new \RuntimeException(sprintf(
                'La etapa "%s" no tiene un revisor asignado con correo válido.',
                $revision->etapa_nombre ?: $revision->etapa_codigo
            ));
        }

        try {
            Mail::to($emails->all())->queue((new EnfRevisionAsignada($accion, $revision))->afterCommit());
        } catch (\Throwable $exception) {
            Log::warning('No se pudo notificar la revision ENF.', ['enf_accion_id' => $accion->id, 'revision_id' => $revision->id, 'error' => $exception->getMessage()]);
        }
    }

    private function relacionesPdf(): array
    {
        return [
            'tipoAccion', 'modalidad', 'centroFacultad', 'departamentoAcademico', 'carrera',
            'lugaresEjecucion.campus', 'lugaresEjecucion.departamento', 'lugaresEjecucion.municipio',
            'beneficiarios', 'equipo', 'participacionUniversitaria', 'contrapartes.tipoContraparte',
            'contrapartes.instrumentoAlianza', 'objetivosEspecificos', 'resultados', 'presupuestos.detalles',
            'cronograma', 'certificado.tipoCertificado', 'certificado.figuraAcreditacion',
            'certificado.carreras.carrera', 'certificado.carreras.centroFacultad', 'espaciosAprendizaje',
            'documentos', 'firmas', 'accionCatalogos.catalogo', 'ods', 'metasContribuye', 'ejesUnah',
        ];
    }
}
