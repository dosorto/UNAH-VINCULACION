<?php

namespace App\Services\InformeFinal;

use App\Models\Estado\EstadoProyecto;
use App\Models\InformeFinal\InformeFinalProyecto;
use App\Models\Proyecto\DocumentoProyecto;
use App\Models\Proyecto\Proyecto;
use App\Models\User;
use App\Services\Constancias\ConstanciaFinalizacionAuthorization;
use App\Services\Proyecto\DocumentoProyectoWorkflowService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InformeFinalProyectoWorkflowService
{
    public function __construct(
        private readonly InformeFinalProyectoInitializer $initializer,
        private readonly InformeFinalProyectoValidator $validator,
        private readonly InformeFinalPdfGenerator $pdfGenerator,
        private readonly DocumentoProyectoWorkflowService $documentos,
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

        if ($proyecto->puedeMostrarCierreProyecto($user) || $this->puedeVerCierreFinalizado($informe, $user)) {
            return $proyecto->usuarioPuedeGestionarInformeFinal($user)
                || $proyecto->usuarioPuedeAuditarInformeFinal($user);
        }

        return $proyecto->usuarioPuedeAuditarInformeFinal($user);
    }

    public function puedeVerCierreFinalizado(InformeFinalProyecto $informe, ?User $user): bool
    {
        $proyecto = $informe->proyecto;
        $documento = $informe->documentoCierre;

        return app(ConstanciaFinalizacionAuthorization::class)->puedeVerProyecto($proyecto, $user)
            && $proyecto->estado?->tipoestado?->nombre === 'Finalizado'
            && $documento?->estado?->tipoestado?->nombre === 'Aprobado'
            && filled($informe->fecha_cierre);
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
            abort_unless(
                $this->puedeContinuarInformeFinal($existente, $user),
                403,
                'No puede continuar el Informe Final: el proyecto debe estar en curso, con el flujo de cierre habilitado y el informe en edición.'
            );

            return $existente;
        }

        abort_unless(
            $this->puedeIniciarInformeFinal($proyecto, $user),
            403,
            'No puede crear el Informe Final: el proyecto debe estar en curso, con inscripción aprobada y flujo de cierre habilitado.'
        );
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

    public function enviarInformeFinal(
        InformeFinalProyecto $informe,
        User $user,
        array $usuariosElegidosPorEtapa = []
    ): DocumentoProyecto {
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
            return DB::transaction(function () use ($informe, $user, $path, $usuariosElegidosPorEtapa): DocumentoProyecto {
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
                    return $this->documentos->iniciar(
                        $proyecto,
                        'Informe Final',
                        $path,
                        $user,
                        $usuariosElegidosPorEtapa
                    );
                }

                if ($documento->estado?->tipoestado?->nombre !== 'Subsanacion') {
                    throw new \RuntimeException('El informe final ya inició su flujo de cierre.');
                }

                return $this->documentos->reenviarDesdeSubsanacion(
                    $proyecto,
                    $documento,
                    $path,
                    $user,
                    $usuariosElegidosPorEtapa
                );
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($path);
            throw $exception;
        }
    }

    public function resumenCierre(Proyecto $proyecto, ?User $user): array
    {
        $relacionesInforme = [
            'documentoCierre.estadoActual.tipoestado',
            'documentoCierre.estado_documento.tipoestado',
            'documentoCierre.firma_documento.empleado',
        ];
        if (Schema::hasTable('constancias_finalizacion_proyecto')) {
            $relacionesInforme[] = 'constanciaFinalizacion';
        }
        $informe = $proyecto->informeFinalInf001()->with($relacionesInforme)->first();

        if (! $informe) {
            $visible = $proyecto->puedeMostrarCierreProyecto($user);

            return [
                'visible' => $visible,
                'estado' => null,
                'etiqueta' => 'Pendiente de creación',
                'accion' => $visible && $this->puedeIniciarInformeFinal($proyecto, $user) ? 'crear' : null,
                'texto_accion' => 'Crear informe final',
            ];
        }

        $visible = $proyecto->puedeMostrarCierreProyecto($user) || $this->puedeVerCierreFinalizado($informe, $user);
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
            'constancia_finalizacion' => $constancia = Schema::hasTable('constancias_finalizacion_proyecto')
                ? $informe->constanciaFinalizacion
                : null,
            'puede_descargar_constancia' => $constancia
                && $constancia->puedeDescargarse()
                && app(ConstanciaFinalizacionAuthorization::class)->puedeDescargar($constancia, $user),
        ];
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
