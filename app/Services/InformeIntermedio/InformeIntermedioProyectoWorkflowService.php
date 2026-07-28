<?php

namespace App\Services\InformeIntermedio;

use App\Models\InformeIntermedio\InformeIntermedioProyecto;
use App\Models\Proyecto\DocumentoProyecto;
use App\Models\Proyecto\Proyecto;
use App\Models\User;
use App\Services\Proyecto\DocumentoProyectoWorkflowService;
use App\Services\Proyecto\ProyectoWorkflowService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InformeIntermedioProyectoWorkflowService
{
    public const MAX_BYTES = 20 * 1024 * 1024;

    public function __construct(
        private readonly ProyectoWorkflowService $workflow,
        private readonly DocumentoProyectoWorkflowService $documentos,
    ) {}

    public function estaDisponible(Proyecto $proyecto, ?User $user): bool
    {
        return $proyecto->usuarioPuedeGestionarInformeFinal($user)
            && $proyecto->estado?->tipoestado?->nombre === 'En curso'
            && $proyecto->tieneFlujoInformeIntermedio()
            && ! ($proyecto->documento_intermedio() && ! $proyecto->informeIntermedio()->exists())
            && $this->workflow->inscripcionCompletada($proyecto);
    }

    public function usuarioPuedeVer(InformeIntermedioProyecto $informe, ?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->hasRole('admin') || $informe->proyecto->usuarioPuedeGestionarInformeFinal($user)) {
            return true;
        }

        $empleadoId = $user->empleado?->id;

        return (bool) ($empleadoId && $informe->documento
            && $informe->documento->firma_documento()->where('empleado_id', $empleadoId)->exists());
    }

    public function guardarArchivo(Proyecto $proyecto, UploadedFile $archivo, User $user): InformeIntermedioProyecto
    {
        $existente = $proyecto->informeIntermedio()->first();

        if (! $this->estaDisponible($proyecto, $user) && ! ($existente?->esEditable()
            && $proyecto->usuarioPuedeGestionarInformeFinal($user))) {
            abort(403);
        }

        $metadatos = $this->validarPdf($archivo);
        $path = sprintf('informes-intermedios/%d/%s.pdf', $proyecto->id, Str::uuid());

        if (! Storage::disk('local')->putFileAs(
            dirname($path),
            $archivo,
            basename($path)
        )) {
            throw new \RuntimeException('No se pudo almacenar el PDF del Informe Intermedio.');
        }

        $pathAnterior = $existente?->archivo_pdf;

        try {
            $informe = DB::transaction(function () use ($proyecto, $user, $path, $metadatos): InformeIntermedioProyecto {
                Proyecto::query()->whereKey($proyecto->id)->lockForUpdate()->firstOrFail();
                $informe = InformeIntermedioProyecto::query()
                    ->where('proyecto_id', $proyecto->id)
                    ->lockForUpdate()
                    ->first();

                if ($informe && ! $informe->esEditable()) {
                    throw new \RuntimeException('El PDF no puede reemplazarse mientras el informe está en revisión o aprobado.');
                }

                return InformeIntermedioProyecto::updateOrCreate(
                    ['proyecto_id' => $proyecto->id],
                    [
                        'archivo_pdf' => $path,
                        'nombre_original' => $metadatos['nombre'],
                        'mime_type' => $metadatos['mime'],
                        'tamano_bytes' => $metadatos['tamano'],
                        'hash_sha256' => $metadatos['hash'],
                        'estado' => $informe?->estado ?? InformeIntermedioProyecto::ESTADO_BORRADOR,
                        'subido_por' => $user->id,
                        'fecha_carga' => now(),
                    ]
                );
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        if ($pathAnterior && $pathAnterior !== $path) {
            Storage::disk('local')->delete($pathAnterior);
        }

        return $informe->fresh();
    }

    public function eliminarArchivo(InformeIntermedioProyecto $informe, User $user): void
    {
        abort_unless($informe->proyecto->usuarioPuedeGestionarInformeFinal($user), 403);

        if ($informe->estado !== InformeIntermedioProyecto::ESTADO_BORRADOR || $informe->documento_proyecto_id) {
            throw new \RuntimeException('El PDF solo puede eliminarse mientras el Informe Intermedio está en borrador.');
        }

        $path = $informe->archivo_pdf;

        DB::transaction(fn () => $informe->delete());
        Storage::disk('local')->delete($path);
    }

    public function enviar(
        InformeIntermedioProyecto $informe,
        User $user,
        array $usuariosElegidosPorEtapa = []
    ): DocumentoProyecto {
        abort_unless($informe->proyecto->usuarioPuedeGestionarInformeFinal($user), 403);
        $this->validarArchivoPersistido($informe);

        return DB::transaction(function () use ($informe, $user, $usuariosElegidosPorEtapa): DocumentoProyecto {
            $bloqueado = InformeIntermedioProyecto::query()->whereKey($informe->id)->lockForUpdate()->firstOrFail();
            $proyecto = Proyecto::query()->whereKey($bloqueado->proyecto_id)->lockForUpdate()->firstOrFail();
            $bloqueado->setRelation('proyecto', $proyecto);

            if (! in_array($bloqueado->estado, [
                InformeIntermedioProyecto::ESTADO_BORRADOR,
                InformeIntermedioProyecto::ESTADO_SUBSANACION,
            ], true)) {
                throw new \RuntimeException('El Informe Intermedio ya fue enviado.');
            }

            if ($bloqueado->estado === InformeIntermedioProyecto::ESTADO_BORRADOR) {
                if (! $this->estaDisponible($proyecto, $user)) {
                    throw new \RuntimeException('El Informe Intermedio todavía no está disponible para este proyecto.');
                }

                $documento = $this->documentos->iniciar(
                    $proyecto,
                    'Informe Intermedio',
                    $bloqueado->archivo_pdf,
                    $user,
                    $usuariosElegidosPorEtapa
                );
            } else {
                $documento = $bloqueado->documento;

                if (! $documento || $documento->estado?->tipoestado?->nombre !== 'Subsanacion') {
                    throw new \RuntimeException('No existe una subsanación vigente para reenviar.');
                }

                $documento = $this->documentos->reenviarDesdeSubsanacion(
                    $proyecto,
                    $documento,
                    $bloqueado->archivo_pdf,
                    $user
                );
            }

            $firmaActual = $this->firmaActual($proyecto, $documento);
            $bloqueado->update([
                'documento_proyecto_id' => $documento->id,
                'estado' => InformeIntermedioProyecto::ESTADO_EN_REVISION,
                'etapa_actual_id' => $firmaActual?->flujo_aprobacion_etapa_id,
                'etapa_retorno_id' => null,
                'enviado_por' => $user->id,
                'fecha_envio' => now(),
                'observaciones' => null,
            ]);

            return $documento->fresh();
        });
    }

    public function resumen(Proyecto $proyecto, ?User $user): array
    {
        $informe = $proyecto->informeIntermedio()
            ->with(['documento.estadoActual.tipoestado', 'etapaActual'])
            ->first();
        $legacy = $informe ? null : $proyecto->documento_intermedio();
        $legacyVisible = $legacy && $user && (
            $user->hasRole('admin')
            || $proyecto->usuarioPuedeGestionarInformeFinal($user)
            || ($user->empleado && $legacy->firma_documento()
                ->where('empleado_id', $user->empleado->id)
                ->exists())
        );
        $disponible = $this->estaDisponible($proyecto, $user);

        return [
            'visible' => $disponible
                || ($informe && $this->usuarioPuedeVer($informe, $user))
                || (bool) $legacyVisible,
            'disponible' => $disponible,
            'informe' => $informe,
            'legacy' => $legacy,
            'estado' => $informe?->estado,
            'etiqueta' => $legacy?->estado?->tipoestado?->nombre ?? match ($informe?->estado) {
                InformeIntermedioProyecto::ESTADO_BORRADOR => 'Borrador',
                InformeIntermedioProyecto::ESTADO_EN_REVISION => 'En revisión',
                InformeIntermedioProyecto::ESTADO_SUBSANACION => 'En subsanación',
                InformeIntermedioProyecto::ESTADO_APROBADO => 'Aprobado',
                default => 'Pendiente de carga',
            },
            'puede_editar' => (bool) ($informe?->esEditable() ?? $disponible),
            'puede_enviar' => (bool) ($informe?->esEditable() && $proyecto->usuarioPuedeGestionarInformeFinal($user)),
            'etapa_actual' => $informe?->etapaActual?->nombre,
            'historial' => $informe?->documento?->estado_documento()
                ->with(['tipoestado', 'empleado'])
                ->latest('id')
                ->get() ?? collect(),
        ];
    }

    private function validarPdf(UploadedFile $archivo): array
    {
        if (! $archivo->isValid()) {
            throw new \RuntimeException('El archivo PDF no se cargó íntegramente.');
        }

        $tamano = (int) $archivo->getSize();
        $mime = (string) $archivo->getMimeType();
        $handle = fopen($archivo->getRealPath(), 'rb');
        $cabecera = $handle ? fread($handle, 5) : false;

        if (is_resource($handle)) {
            fclose($handle);
        }

        if ($tamano < 5 || $tamano > self::MAX_BYTES) {
            throw new \RuntimeException('El PDF debe pesar como máximo 20 MB.');
        }

        if (! in_array($mime, ['application/pdf', 'application/x-pdf'], true)
            || $cabecera !== '%PDF-'
            || strtolower($archivo->getClientOriginalExtension()) !== 'pdf') {
            throw new \RuntimeException('El archivo debe ser un PDF válido.');
        }

        return [
            'nombre' => $archivo->getClientOriginalName(),
            'mime' => 'application/pdf',
            'tamano' => $tamano,
            'hash' => hash_file('sha256', $archivo->getRealPath()),
        ];
    }

    private function validarArchivoPersistido(InformeIntermedioProyecto $informe): void
    {
        $disk = Storage::disk('local');

        if (! $informe->archivo_pdf || ! $disk->exists($informe->archivo_pdf)) {
            throw new \RuntimeException('Debe cargar un archivo PDF antes de enviar el Informe Intermedio.');
        }

        $path = $disk->path($informe->archivo_pdf);
        $handle = fopen($path, 'rb');
        $cabecera = $handle ? fread($handle, 5) : false;

        if (is_resource($handle)) {
            fclose($handle);
        }

        if ($cabecera !== '%PDF-'
            || hash_file('sha256', $path) !== $informe->hash_sha256
            || filesize($path) !== $informe->tamano_bytes) {
            throw new \RuntimeException('El PDF almacenado no supera la validación de integridad.');
        }
    }

    private function firmaActual(Proyecto $proyecto, DocumentoProyecto $documento)
    {
        $flujoId = $proyecto->resolveFlujoAprobacion()?->id;
        $ciclo = (int) $documento->firma_documento()->max('revision_ciclo');

        return $flujoId && $ciclo
            ? $proyecto->firmaActualDeEtapasDelFlujo($flujoId, $ciclo, $documento)
            : null;
    }
}
