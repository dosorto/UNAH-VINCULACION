<?php

namespace App\Services\InformeFinal;

use App\Models\Estado\EstadoProyecto;
use App\Models\InformeFinal\InformeFinalProyecto;
use App\Models\Proyecto\DocumentoProyecto;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\Proyecto\Proyecto;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InformeFinalProyectoWorkflowService
{
    public function __construct(
        private readonly InformeFinalProyectoInitializer $initializer,
        private readonly InformeFinalProyectoValidator $validator,
        private readonly InformeFinalPdfGenerator $pdfGenerator,
    ) {}

    public function usuarioPuedeGestionar(Proyecto $proyecto, ?User $user): bool
    {
        return $proyecto->usuarioPuedeGestionarInformeFinal($user);
    }

    public function usuarioPuedeVer(InformeFinalProyecto $informe, ?User $user): bool
    {
        if (! $user) {
            return false;
        }

        $proyecto = $informe->proyecto;

        if ($proyecto->puedeMostrarCierreProyecto($user)) {
            return $proyecto->usuarioPuedeGestionarInformeFinal($user)
                || $proyecto->usuarioPuedeAuditarInformeFinal($user);
        }

        return $proyecto->usuarioPuedeAuditarInformeFinal($user);
    }

    public function puedeIniciarInformeFinal(Proyecto $proyecto, ?User $user): bool
    {
        return $proyecto->tipoAccion?->codigo === 'DESARROLLO_LOCAL_REGIONAL'
            && $this->usuarioPuedeGestionar($proyecto, $user)
            && ! $proyecto->informeFinalInf001()->exists()
            && $proyecto->puedeMostrarCierreProyecto($user);
    }

    public function puedeContinuarInformeFinal(InformeFinalProyecto $informe, ?User $user): bool
    {
        return $this->usuarioPuedeGestionar($informe->proyecto, $user)
            && $informe->proyecto->puedeMostrarCierreProyecto($user)
            && $informe->esEditable();
    }

    public function puedeEnviarInformeFinal(InformeFinalProyecto $informe, ?User $user): bool
    {
        return $this->puedeContinuarInformeFinal($informe, $user)
            && $informe->estado === InformeFinalProyecto::ESTADO_COMPLETO
            && in_array($informe->estadoFlujo(), [
                InformeFinalProyecto::ESTADO_COMPLETO,
                InformeFinalProyecto::ESTADO_RECHAZADO,
            ], true);
    }

    public function crearInformeFinal(Proyecto $proyecto, User $user): InformeFinalProyecto
    {
        $existente = $proyecto->informeFinalInf001()->first();
        if ($existente) {
            abort_unless($this->puedeContinuarInformeFinal($existente, $user), 403);

            return $existente;
        }

        abort_unless($this->puedeIniciarInformeFinal($proyecto, $user), 403);
        $informe = $this->initializer->initialize($proyecto, $user->id);
        $this->registrarMovimientoProyecto($proyecto, $user, '[Cierre INF-001] Informe final creado en borrador.');

        return $informe;
    }

    public function registrarInformeCompleto(InformeFinalProyecto $informe, User $user): void
    {
        $this->registrarMovimientoProyecto(
            $informe->proyecto,
            $user,
            '[Cierre INF-001] Informe final completado y listo para envío.'
        );
    }

    public function enviarInformeFinal(InformeFinalProyecto $informe, User $user): DocumentoProyecto
    {
        abort_unless($this->puedeEnviarInformeFinal($informe, $user), 403);
        $this->validator->validateForCompletion($informe->fresh());

        $contenido = $this->pdfGenerator->content($informe->fresh());
        $ciclo = max(1, (int) $informe->documentoCierre?->firma_documento()->max('revision_ciclo') + 1);
        $path = sprintf(
            'documentos/informes-finales/inf-001-%d-ciclo-%d-%s.pdf',
            $informe->id,
            $ciclo,
            Str::uuid()
        );
        if (! Storage::disk('public')->put($path, $contenido)) {
            throw new \RuntimeException('No se pudo almacenar el PDF canónico del informe final.');
        }

        try {
            return DB::transaction(function () use ($informe, $user, $path): DocumentoProyecto {
                $informeBloqueado = InformeFinalProyecto::query()->whereKey($informe->id)->lockForUpdate()->firstOrFail();
                $proyecto = Proyecto::query()->whereKey($informeBloqueado->proyecto_id)->lockForUpdate()->firstOrFail();
                $informeBloqueado->setRelation('proyecto', $proyecto);

                if (! $this->puedeEnviarInformeFinal($informeBloqueado, $user)) {
                    throw new \RuntimeException('El informe final ya no se encuentra disponible para envío.');
                }

                $documento = $proyecto->documentos()
                    ->where('tipo_documento', 'Informe Final')
                    ->lockForUpdate()
                    ->first();

                if (! $documento) {
                    return $proyecto->registrarDocumentoDesdeFlujo('Informe Final', $path, $user->empleado);
                }

                if ($documento->estado?->tipoestado?->nombre !== 'Subsanacion') {
                    throw new \RuntimeException('El informe final ya inició su flujo de cierre.');
                }

                $this->reenviarDocumentoRechazado($proyecto, $documento, $path, $user);

                return $documento->fresh();
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($path);
            throw $exception;
        }
    }

    public function resumenCierre(Proyecto $proyecto, ?User $user): array
    {
        $informe = $proyecto->informeFinalInf001()->with([
            'documentoCierre.estadoActual.tipoestado',
            'documentoCierre.estado_documento.tipoestado',
            'documentoCierre.firma_documento.empleado',
        ])->first();

        if (! $informe) {
            $visible = $proyecto->puedeMostrarCierreProyecto($user);

            return [
                'visible' => $visible,
                'advertencia_interna' => false,
                'estado' => null,
                'etiqueta' => 'Pendiente de creación',
                'accion' => $visible && $this->puedeIniciarInformeFinal($proyecto, $user) ? 'crear' : null,
                'texto_accion' => 'Crear informe final',
            ];
        }

        $visible = $proyecto->puedeMostrarCierreProyecto($user);
        $advertenciaInterna = ! $visible && $proyecto->usuarioPuedeAuditarInformeFinal($user);
        $estado = $informe->estadoFlujo();
        $firmaActual = $informe->firmaCierreActual();
        $documento = $informe->documentoCierre;
        $accion = match ($estado) {
            InformeFinalProyecto::ESTADO_BORRADOR => 'continuar',
            InformeFinalProyecto::ESTADO_COMPLETO => 'enviar',
            InformeFinalProyecto::ESTADO_EN_REVISION => 'ver',
            InformeFinalProyecto::ESTADO_RECHAZADO => 'subsanar',
            InformeFinalProyecto::ESTADO_APROBADO => 'aprobado',
            default => null,
        };
        $texto = match ($accion) {
            'continuar' => 'Continuar informe final',
            'enviar' => 'Revisar y enviar informe final',
            'ver' => 'Informe final en revisión',
            'subsanar' => 'Subsanar informe final',
            'aprobado' => 'Ver informe final aprobado',
            default => null,
        };
        $etiqueta = match ($estado) {
            InformeFinalProyecto::ESTADO_BORRADOR => 'Borrador',
            InformeFinalProyecto::ESTADO_COMPLETO => 'Completo, listo para envío',
            InformeFinalProyecto::ESTADO_EN_REVISION => 'En revisión',
            InformeFinalProyecto::ESTADO_RECHAZADO => 'Rechazado, pendiente de subsanación',
            InformeFinalProyecto::ESTADO_APROBADO => 'Aprobado',
            default => $estado,
        };

        return [
            'visible' => $visible,
            'advertencia_interna' => $advertenciaInterna,
            'informe' => $informe,
            'estado' => $estado,
            'etiqueta' => $etiqueta,
            'accion' => $visible ? $accion : null,
            'texto_accion' => $texto,
            'puede_gestionar' => $this->usuarioPuedeGestionar($proyecto, $user),
            'puede_enviar' => $this->puedeEnviarInformeFinal($informe, $user),
            'fecha_creacion' => $informe->created_at,
            'fecha_envio' => $documento?->created_at,
            'etapa_actual' => $firmaActual?->etapa_nombre,
            'revisor_actual' => $firmaActual?->empleado?->nombre_completo,
            'motivo_rechazo' => $estado === InformeFinalProyecto::ESTADO_RECHAZADO
                ? $documento?->estado?->comentario
                : null,
        ];
    }

    private function reenviarDocumentoRechazado(
        Proyecto $proyecto,
        DocumentoProyecto $documento,
        string $path,
        User $user
    ): void {
        $ultimoCiclo = (int) $documento->firma_documento()
            ->whereNotNull('flujo_aprobacion_etapa_id')
            ->whereNull('deleted_at')
            ->max('revision_ciclo');
        $firmas = $documento->firma_documento()
            ->where('revision_ciclo', $ultimoCiclo)
            ->whereNotNull('flujo_aprobacion_etapa_id')
            ->whereNull('deleted_at')
            ->orderBy('orden_revision')
            ->get();
        $rechazadas = $firmas->where('estado_revision', 'Rechazado');

        if ($rechazadas->count() !== 1) {
            throw new \RuntimeException('No se pudo identificar una única etapa rechazada para reenviar el informe.');
        }

        $empleadosPorEtapa = $firmas
            ->reject(fn (FirmaProyecto $firma) => $firma->estado_revision === 'Anulado')
            ->mapWithKeys(fn (FirmaProyecto $firma) => [
                (int) $firma->flujo_aprobacion_etapa_id => (int) $firma->empleado_id,
            ])->all();
        $firmasNuevas = $proyecto->crearNuevoCicloDesdeFirmaRechazada($rechazadas->first(), $empleadosPorEtapa);
        $primera = $firmasNuevas->first();
        $tipoEstadoId = $primera?->cargo_firma()->value('tipo_estado_id');

        if (! $primera || ! $tipoEstadoId) {
            throw new \RuntimeException('No se pudo iniciar el nuevo ciclo de cierre.');
        }

        $documento->update(['documento_url' => $path]);
        $documento->estado_documento()->create([
            'empleado_id' => $user->empleado->id,
            'tipo_estado_id' => $tipoEstadoId,
            'fecha' => now(),
            'comentario' => sprintf('[Cierre INF-001] Informe final reenviado a la etapa "%s".', $primera->etapa_nombre),
        ]);
    }

    private function registrarMovimientoProyecto(Proyecto $proyecto, User $user, string $comentario): void
    {
        $estadoActual = $proyecto->estado;
        $empleadoId = $user->empleado?->id;

        if (! $estadoActual || ! $empleadoId) {
            return;
        }

        DB::transaction(function () use ($proyecto, $estadoActual, $empleadoId, $comentario): void {
            $proyecto->estado_proyecto()->update(['es_actual' => false]);
            EstadoProyecto::withoutEvents(function () use ($proyecto, $estadoActual, $empleadoId, $comentario): void {
                $proyecto->estado_proyecto()->create([
                    'empleado_id' => $empleadoId,
                    'tipo_estado_id' => $estadoActual->tipo_estado_id,
                    'fecha' => now(),
                    'comentario' => $comentario,
                    'es_actual' => true,
                ]);
            });
        });
    }
}
