<?php

namespace App\Livewire\Docente\Proyectos;

use App\Concerns\ResolvesFirmasPendientes;
use App\Concerns\ResuelveFirmaPorEtapa;
use App\Mail\EnfRevisionAsignada;
use App\Models\ENF\EnfAccion;
use App\Models\ENF\EnfInformeFinal;
use App\Models\ENF\EnfInformeFinalDocumentoRevision;
use App\Models\ENF\EnfRevision;
use App\Models\Estado\TipoEstado;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\DocumentoProyecto;
use App\Models\Proyecto\FichaActualizacion;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\Proyecto\Proyecto;
use App\Models\PpsServicioSocial;
use App\Models\User;
use App\Services\PpsServicioSocial\PpsServicioSocialWorkflowService;
use App\Services\ENF\EnfWorkflowService;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ProyectosPorFirmar extends Component
{
    use WithPagination;
    use WithFileUploads;
    use ResolvesFirmasPendientes;
    use ResuelveFirmaPorEtapa;

    public Empleado $docente;
    public bool $viewModal = false;
    public ?int $viewId = null;
    public bool $rechazarModal = false;
    public ?int $rechazarId = null;
    public string $rechazarComentario = '';
    public bool $enfSubsanarModal = false;
    public ?int $enfSubsanarRevisionId = null;
    public string $enfSubsanarComentario = '';
    public bool $viewEnfModal = false;
    public ?int $viewEnfRevisionId = null;
    public string $enfAprobacionComentario = '';
    public bool $ppsSubsanarModal = false;
    public ?int $ppsSubsanarRegistroId = null;
    public string $ppsSubsanarComentario = '';
    public bool $reasignarModal = false;
    public ?int $reasignarFirmaId = null;
    public ?int $reasignarEnfRevisionId = null;
    public ?int $reasignarNuevoUsuarioId = null;
    public array $reasignarCandidatos = [];
    public $documentoRevision = null;

    public function mount($docente = null): void
    {
        $this->docente = $docente ?? Auth::user()->empleado;
    }

    public function openView(int $id): void
    {
        $this->viewId = $id;
        $this->viewModal = true;
    }

    public function closeView(): void
    {
        $this->viewModal = false;
        $this->viewId = null;
        $this->documentoRevision = null;
    }

    public function getDocumentosRevisionViewProperty()
    {
        $firma = $this->viewId ? FirmaProyecto::find($this->viewId) : null;
        $documento = $firma?->documento_proyecto;

        if ($documento?->tipo_documento !== 'Informe Final') {
            return collect();
        }

        return \App\Models\InformeFinal\InformeFinalDocumentoRevision::query()
            ->whereHas('informe', fn (Builder $query) => $query->where('proyecto_id', $documento->proyecto_id))
            ->with(['firma.flujoEtapa', 'usuario'])
            ->latest()
            ->get();
    }

    public function openEnfView(int $revisionId): void
    {
        EnfRevision::findOrFail($revisionId);
        $this->viewEnfRevisionId = $revisionId;
        $this->viewEnfModal = true;
    }

    public function closeEnfView(): void
    {
        $this->viewEnfModal = false;
        $this->viewEnfRevisionId = null;
        $this->documentoRevision = null;
        $this->enfAprobacionComentario = '';
    }

    public function getEnfDocumentosRevisionViewProperty()
    {
        $revision = $this->viewEnfRevisionId ? EnfRevision::with('accion.informeFinal')->find($this->viewEnfRevisionId) : null;
        $informe = $revision?->proceso === EnfAccion::PROCESO_INFORME_FINAL
            ? $revision->accion?->informeFinal
            : null;

        if (! $informe) {
            return collect();
        }

        return EnfInformeFinalDocumentoRevision::query()
            ->where('enf_informe_final_id', $informe->id)
            ->with(['revision', 'usuario'])
            ->latest()
            ->get();
    }

    public function puedeReasignar(?FirmaProyecto $firma): bool
    {
        return $firma
            && $firma->usaFlujoPorEtapa()
            && $firma->estado_revision === 'Pendiente'
            && $firma->responsable_usuario_id
            && (int) $firma->responsable_usuario_id === (int) Auth::id();
    }

    public function puedeReasignarEnf(?EnfRevision $revision): bool
    {
        if (! $revision || ! in_array($revision->estado, $this->estadosRevisionEnfPendiente(), true)) {
            return false;
        }

        $user = Auth::user();
        if (! $user || ! $this->canActOnEnfRevision($revision)) {
            return false;
        }

        return filled($revision->rol_requerido);
    }

    public function firmaPendienteDePps(PpsServicioSocial $registro): ?FirmaProyecto
    {
        if (! $registro->etapa_actual_id) {
            return null;
        }

        return $registro->firmasDeEtapa()
            ->where('flujo_aprobacion_etapa_id', $registro->etapa_actual_id)
            ->where('estado_revision', 'Pendiente')
            ->first();
    }

    public function openReasignar(int $firmaId): void
    {
        $firma = FirmaProyecto::findOrFail($firmaId);

        abort_unless($this->puedeReasignar($firma), 403);

        $this->reasignarFirmaId = $firmaId;
        $this->reasignarNuevoUsuarioId = null;
        $this->reasignarCandidatos = User::query()
            ->whereHas('roles', fn (Builder $query) => $query->where('roles.name', $firma->rol_requerido))
            ->where('id', '!=', Auth::id())
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user): array => ['id' => $user->id, 'name' => $user->name])
            ->all();
        $this->reasignarModal = true;
    }

    public function openEnfReasignar(int $revisionId): void
    {
        $revision = EnfRevision::findOrFail($revisionId);

        abort_unless($this->puedeReasignarEnf($revision), 403);

        $this->reasignarFirmaId = null;
        $this->reasignarEnfRevisionId = $revisionId;
        $this->reasignarNuevoUsuarioId = null;
        $this->reasignarCandidatos = User::query()
            ->whereHas('roles', fn (Builder $query) => $query->where('roles.name', $revision->rol_requerido))
            ->where('id', '!=', Auth::id())
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user): array => ['id' => $user->id, 'name' => $user->name])
            ->all();
        $this->reasignarModal = true;
    }

    public function closeReasignar(): void
    {
        $this->reasignarModal = false;
        $this->reasignarFirmaId = null;
        $this->reasignarEnfRevisionId = null;
        $this->reasignarNuevoUsuarioId = null;
        $this->reasignarCandidatos = [];
    }

    public function confirmarReasignacion(): void
    {
        $this->validate(['reasignarNuevoUsuarioId' => 'required|integer|exists:users,id']);

        if ($this->reasignarEnfRevisionId) {
            $revision = EnfRevision::findOrFail($this->reasignarEnfRevisionId);

            abort_unless($this->puedeReasignarEnf($revision), 403);

            $nuevoUsuario = User::findOrFail($this->reasignarNuevoUsuarioId);

            $revision->update([
                'asignado_usuario_id' => $nuevoUsuario->id,
                'responsable_usuario_id' => $revision->responsable_usuario_id ?: Auth::id(),
                'estado' => 'ASIGNADO',
            ]);

            $this->closeReasignar();
            $this->closeEnfView();

            Notification::make()->title('¡Realizado!')->body('La etapa ENF fue reasignada correctamente.')->success()->send();
            return;
        }

        $firma = FirmaProyecto::findOrFail($this->reasignarFirmaId);

        abort_unless($this->puedeReasignar($firma), 403);

        $nuevoUsuario = User::findOrFail($this->reasignarNuevoUsuarioId);

        try {
            $firma->reasignarA($nuevoUsuario, Auth::user());
        } catch (\RuntimeException $e) {
            Notification::make()->title('No se pudo reasignar')->body($e->getMessage())->danger()->send();
            return;
        }

        $this->closeReasignar();

        Notification::make()->title('¡Realizado!')->body('La etapa fue reasignada correctamente.')->success()->send();
    }

    public function openRechazar(int $id): void
    {
        $this->authorizeFirmaAction($id);

        $this->rechazarId = $id;
        $this->rechazarComentario = '';
        $this->rechazarModal = true;
    }

    public function rechazar(): void
    {
        $this->validate(['rechazarComentario' => 'required|string']);

        $firma = $this->authorizeFirmaAction((int) $this->rechazarId);

        if ($firma->usaFlujoPorEtapa()) {
            $this->rechazarFirmaPorEtapa($firma->fresh(), Auth::user(), $this->rechazarComentario);

            $this->rechazarModal = false;
            $this->rechazarId = null;
            $this->rechazarComentario = '';
            $this->viewModal = false;

            Notification::make()->title('¡Realizado!')->body('Proyecto enviado a subsanacion')->info()->send();

            return;
        }

        if ($firma->firmable_type == Proyecto::class) {
            $firma->proyecto->firma_proyecto()->update([
                'estado_revision' => 'Pendiente',
                'firma_id'        => null,
                'sello_id'        => null,
                'fecha_firma'     => null,
            ]);

            $firma->proyecto->estado_proyecto()->create([
                'empleado_id'    => $this->docente->id,
                'tipo_estado_id' => TipoEstado::where('nombre', 'Subsanacion')->first()->id,
                'fecha'          => now(),
                'comentario'     => $this->rechazarComentario,
            ]);
        } else {
            $firma->documento_proyecto->firma_documento()->update([
                'estado_revision' => 'Pendiente',
                'firma_id'        => null,
                'sello_id'        => null,
            ]);

            $firma->documento_proyecto->estado_documento()->create([
                'empleado_id'    => $this->docente->id,
                'tipo_estado_id' => TipoEstado::where('nombre', 'Subsanacion')->first()->id,
                'fecha'          => now(),
                'comentario'     => $this->rechazarComentario,
            ]);
        }

        $this->rechazarModal = false;
        $this->rechazarId = null;
        $this->rechazarComentario = '';
        $this->viewModal = false;

        Notification::make()->title('¡Realizado!')->body('Proyecto enviado a subsanacion')->info()->send();
    }

    public function aprobar(int $id): void
    {
        $firma = $this->authorizeFirmaAction($id);
        $informe = $firma->firmable_type === DocumentoProyecto::class
            && $firma->documento_proyecto?->tipo_documento === 'Informe Final'
            ? \App\Models\InformeFinal\InformeFinalProyecto::where('proyecto_id', $firma->documento_proyecto->proyecto_id)->first()
            : null;

        if ($this->documentoRevision) {
            abort_unless($informe, 422, 'El adjunto de revisión solo está disponible para INF-001.');
            $this->validate(['documentoRevision' => ['nullable', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:10240']]);
        }

        $rutaDocumentoRevision = null;
        if ($this->documentoRevision) {
            $rutaDocumentoRevision = $this->documentoRevision->store('informes-finales/'.$informe->id.'/revisiones', 'local');
        }

        try {
        if ($firma->usaFlujoPorEtapa()) {
            $nombreOriginal = $this->documentoRevision?->getClientOriginalName();
            $mimeType = $this->documentoRevision?->getMimeType();
            $tamanoBytes = $this->documentoRevision?->getSize();
            $firmaAprobada = $this->aprobarFirmaPorEtapa($firma->fresh(), Auth::user(), function (FirmaProyecto $firmaAprobada) use ($rutaDocumentoRevision, $informe, $nombreOriginal, $mimeType, $tamanoBytes): void {
                if (! $rutaDocumentoRevision) {
                    return;
                }

                $movimiento = $firmaAprobada->documento_proyecto?->estado_documento()->latest('id')->first();
                abort_unless($movimiento, 422, 'No se pudo asociar el documento al movimiento de revisión.');

                $informe->documentosRevision()->create([
                    'firma_proyecto_id' => $firmaAprobada->id,
                    'estado_proyecto_id' => $movimiento->id,
                    'flujo_aprobacion_id' => $firmaAprobada->flujo_aprobacion_id,
                    'flujo_aprobacion_etapa_id' => $firmaAprobada->flujo_aprobacion_etapa_id,
                    'subido_por' => Auth::id(),
                    'revision_ciclo' => $firmaAprobada->revision_ciclo,
                    'ruta' => $rutaDocumentoRevision,
                    'nombre_original' => $nombreOriginal,
                    'mime_type' => $mimeType,
                    'tamano_bytes' => $tamanoBytes,
                ]);
            });

            $this->viewModal = false;
            $this->viewId = null;
            $this->documentoRevision = null;

            Notification::make()->title('¡Realizado!')->body('Proyecto Aprobado correctamente')->info()->send();

            return;
        }

        if ($firma->firmable_type == Proyecto::class) {
            $firma->update([
                'estado_revision' => 'Aprobado',
                'firma_id'        => auth()->user()?->empleado?->firma?->id,
                'sello_id'        => auth()->user()?->empleado?->sello?->id,
                'fecha_firma'     => now(),
            ]);

            $firma->proyecto?->anularFirmasPendientesDuplicadasDeCargo($firma->cargo_firma_id, $firma->id);

            $firma->proyecto?->sincronizarFirmasDelFlujo();

            $nextEstadoId = $firma->proyecto?->nextEstadoIdEnFlujo($firma->cargo_firma_id)
                ?? $firma->proyecto?->estadoFinalProcesoId(Proyecto::FLUJO_INSCRIPCION);

            $firma->proyecto->estado_proyecto()->create([
                'empleado_id'    => auth()->user()->empleado->id,
                'tipo_estado_id' => $nextEstadoId,
                'fecha'          => now(),
                'comentario'     => 'Firmado y aprobado en este estado',
            ]);
        } else {
            $firma->update([
                'estado_revision' => 'Aprobado',
                'firma_id'        => auth()->user()?->empleado?->firma?->id,
                'sello_id'        => auth()->user()?->empleado?->sello?->id,
                'fecha_firma'     => now(),
            ]);

            $documento = $firma->documento_proyecto;
            $proceso = $documento?->tipo_documento
                ? Proyecto::procesoFlujoParaDocumento($documento->tipo_documento)
                : null;

            if ($proceso) {
                $documento?->proyecto?->sincronizarFirmasDelFlujo($proceso, $documento);
            }

            $nextEstadoId = $proceso
                ? $documento?->proyecto?->nextEstadoIdEnFlujo($firma->cargo_firma_id, $proceso)
                : null;

            if ($nextEstadoId) {
                $documento->estado_documento()->create([
                    'empleado_id'    => $this->docente->id,
                    'tipo_estado_id' => $nextEstadoId,
                    'fecha'          => now(),
                    'comentario'     => 'Firmado y aprobado en este estado',
                ]);
            } elseif ($documento) {
                $this->marcarDocumentoAprobado($documento);
            }
        }

        $this->viewModal = false;
        $this->viewId = null;

        Notification::make()->title('¡Realizado!')->body('Proyecto Aprobado correctamente')->info()->send();
        } catch (\Throwable $exception) {
            if ($rutaDocumentoRevision) {
                \Illuminate\Support\Facades\Storage::disk('local')->delete($rutaDocumentoRevision);
            }
            throw $exception;
        }
    }

    public function aprobarEnfRevision(int $revisionId): void
    {
        $revision = $this->authorizeEnfRevisionAction($revisionId);
        $informe = $revision->proceso === EnfAccion::PROCESO_INFORME_FINAL
            ? EnfInformeFinal::query()->where('enf_accion_id', $revision->enf_accion_id)->first()
            : null;

        $this->validate([
            'enfAprobacionComentario' => ['nullable', 'string', 'max:5000'],
            'documentoRevision' => ['nullable', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:10240'],
        ], [], [
            'enfAprobacionComentario' => 'observacion de aprobacion',
            'documentoRevision' => 'documento de revision',
        ]);

        abort_if($this->documentoRevision && ! $informe, 422, 'El adjunto de revision solo esta disponible para informe final ENF.');

        $rutaDocumentoRevision = null;
        $nombreOriginal = $this->documentoRevision?->getClientOriginalName();
        $mimeType = $this->documentoRevision?->getMimeType();
        $tamanoBytes = $this->documentoRevision?->getSize();

        if ($this->documentoRevision) {
            $rutaDocumentoRevision = $this->documentoRevision->store('enf/informes-finales/'.$informe->id.'/revisiones', 'local');
        }

        try {
            app(EnfWorkflowService::class)->aprobarRevision(
                $revision,
                Auth::user(),
                filled($this->enfAprobacionComentario) ? $this->enfAprobacionComentario : null
            );

            if ($rutaDocumentoRevision && $informe) {
                $informe->documentosRevision()->create([
                    'enf_revision_id' => $revision->id,
                    'enf_accion_id' => $revision->enf_accion_id,
                    'flujo_aprobacion_etapa_id' => $revision->flujo_aprobacion_etapa_id,
                    'subido_por' => Auth::id(),
                    'revision_ciclo' => $revision->revision_ciclo,
                    'ruta' => $rutaDocumentoRevision,
                    'nombre_original' => $nombreOriginal,
                    'mime_type' => $mimeType ?: 'application/pdf',
                    'tamano_bytes' => $tamanoBytes ?: 0,
                ]);
            }
        } catch (\Throwable $exception) {
            if ($rutaDocumentoRevision) {
                Storage::disk('local')->delete($rutaDocumentoRevision);
            }
            throw $exception;
        }

        $this->viewEnfModal = false;
        $this->viewEnfRevisionId = null;
        $this->documentoRevision = null;
        $this->enfAprobacionComentario = '';

        Notification::make()->title('¡Realizado!')->body('Etapa ENF aprobada correctamente.')->success()->send();
    }

    public function openEnfSubsanar(int $revisionId): void
    {
        $this->authorizeEnfRevisionAction($revisionId);

        $this->viewEnfModal = false;
        $this->enfSubsanarRevisionId = $revisionId;
        $this->enfSubsanarComentario = '';
        $this->enfSubsanarModal = true;
    }

    public function subsanarEnfRevision(): void
    {
        $this->validate([
            'enfSubsanarComentario' => ['required', 'string', 'min:5'],
        ], [
            'enfSubsanarComentario.required' => 'Debe indicar la observación de subsanación.',
            'enfSubsanarComentario.min' => 'La observación de subsanación debe tener al menos :min caracteres.',
        ]);

        $revision = $this->authorizeEnfRevisionAction((int) $this->enfSubsanarRevisionId);
        app(EnfWorkflowService::class)->subsanarRevision($revision, $this->enfSubsanarComentario, Auth::user());

        $this->enfSubsanarModal = false;
        $this->enfSubsanarRevisionId = null;
        $this->enfSubsanarComentario = '';

        Notification::make()->title('¡Realizado!')->body('Registro ENF enviado a subsanación.')->warning()->send();
    }

    public function aprobarPpsRegistro(int $registroId): void
    {
        $registro = $this->authorizePpsRegistroAction($registroId);
        $user = Auth::user();

        if (! $registro->puedeAprobarse(Auth::id(), $user)) {
            Notification::make()
                ->title('Revision no disponible')
                ->body('El registro PPS/SS no esta en una etapa revisable.')
                ->warning()
                ->send();

            return;
        }

        try {
            $registro = app(PpsServicioSocialWorkflowService::class)
                ->aprobarEtapa($registro, Auth::id(), $user);
        } catch (\RuntimeException $exception) {
            Notification::make()
                ->title('Flujo PPS/SS incompleto')
                ->body($exception->getMessage())
                ->warning()
                ->send();

            return;
        } catch (\Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Error')
                ->body('No se pudo aprobar el registro PPS/SS. Intente nuevamente.')
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title($registro->estado === 'aprobado' ? 'Registro aprobado' : 'Etapa aprobada')
            ->body($registro->estado === 'aprobado'
                ? 'El FORM-DVUS-014 fue aprobado correctamente.'
                : 'El registro PPS/SS avanzó a la siguiente etapa.')
            ->success()
            ->send();
    }

    public function openPpsSubsanar(int $registroId): void
    {
        $registro = $this->authorizePpsRegistroAction($registroId);
        $user = Auth::user();

        if (! $registro->puedeRechazarse(Auth::id(), $user)) {
            Notification::make()
                ->title('Revision no disponible')
                ->body('La etapa actual del flujo PPS/SS no permite enviar a subsanación.')
                ->warning()
                ->send();

            return;
        }

        $this->resetErrorBag('ppsSubsanarComentario');
        $this->ppsSubsanarRegistroId = $registroId;
        $this->ppsSubsanarComentario = '';
        $this->ppsSubsanarModal = true;
    }

    public function subsanarPpsRegistro(): void
    {
        $this->validate([
            'ppsSubsanarComentario' => ['required', 'string', 'min:5', 'max:5000'],
        ], [], [
            'ppsSubsanarComentario' => 'observaciones',
        ]);

        $registro = $this->authorizePpsRegistroAction((int) $this->ppsSubsanarRegistroId);
        $user = Auth::user();

        if (! $registro->puedeRechazarse(Auth::id(), $user)) {
            Notification::make()
                ->title('Revision no disponible')
                ->body('La etapa actual del flujo PPS/SS no permite enviar a subsanación.')
                ->warning()
                ->send();

            return;
        }

        try {
            app(PpsServicioSocialWorkflowService::class)
                ->rechazar($registro, $this->ppsSubsanarComentario, Auth::id(), $user);
        } catch (\RuntimeException $exception) {
            Notification::make()
                ->title('Revision no disponible')
                ->body($exception->getMessage())
                ->warning()
                ->send();

            return;
        } catch (\Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Error')
                ->body('No se pudo enviar el registro PPS/SS a subsanación. Intente nuevamente.')
                ->danger()
                ->send();

            return;
        }

        $this->ppsSubsanarModal = false;
        $this->ppsSubsanarRegistroId = null;
        $this->ppsSubsanarComentario = '';

        Notification::make()
            ->title('Registro enviado a subsanación')
            ->body('El FORM-DVUS-014 fue devuelto para correcciones.')
            ->warning()
            ->send();
    }

    public function puedeSubsanar(int $firmaId): bool
    {
        $firma = FirmaProyecto::with(['cargo_firma.tipoCargoFirma', 'proyecto.estadoActual'])->find($firmaId);

        return $firma ? $this->canActOnFirma($firma) : false;
    }

    public function render(): View
    {
        $records = $this->firmasDisponiblesQuery()
            ->where('firmable_type', '!=', FichaActualizacion::class)
            ->with(['proyecto', 'cargo_firma.tipoCargoFirma'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $enfRevisiones = $this->enfRevisionesDisponiblesQuery()
            ->with(['accion', 'flujoEtapa.rolRevisor'])
            ->orderBy('created_at', 'desc')
            ->get();

        $ppsRegistros = $this->ppsRegistrosDisponiblesQuery()
            ->with(['flujoAprobacion', 'etapaActual.rolRevisor', 'etapaActual.usuarioResponsable'])
            ->orderBy('created_at', 'desc')
            ->get();

        $viewFirma = $this->viewId ? FirmaProyecto::find($this->viewId) : null;
        $viewEnfRevision = $this->viewEnfRevisionId
            ? EnfRevision::with(['accion' => fn ($query) => $query->with($this->enfViewRelations()), 'flujoEtapa.rolRevisor'])->find($this->viewEnfRevisionId)
            : null;
        $viewProyecto = null;
        $viewDocumento = null;
        if ($viewFirma) {
            if ($viewFirma->firmable_type == Proyecto::class) {
                $viewProyecto = $viewFirma->proyecto?->load(['aporteInstitucional', 'presupuesto', 'ods', 'metasContribuye']);
            } else {
                $viewDocumento = $viewFirma->documento_proyecto?->load(['estadoActual.tipoestado']);
                $viewProyecto = $viewDocumento?->proyecto?->load(['aporteInstitucional', 'presupuesto', 'ods', 'metasContribuye']);
            }
        }

        return view('livewire.docente.proyectos.proyectos-por-firmar', compact('records', 'enfRevisiones', 'ppsRegistros', 'viewFirma', 'viewProyecto', 'viewDocumento', 'viewEnfRevision'));
    }

    private function authorizeFirmaAction(int $firmaId): FirmaProyecto
    {
        $firma = FirmaProyecto::with(['cargo_firma.tipoCargoFirma', 'proyecto.estadoActual'])->findOrFail($firmaId);

        abort_unless($this->canActOnFirma($firma), 403);

        return $firma;
    }

    private function authorizeEnfRevisionAction(int $revisionId): EnfRevision
    {
        $revision = EnfRevision::with(['accion.revisiones', 'flujoEtapa.rolRevisor'])->findOrFail($revisionId);

        abort_unless($this->canActOnEnfRevision($revision), 403);

        return $revision;
    }

    private function enfViewRelations(): array
    {
        return [
            'tipoAccion',
            'modalidad',
            'centroFacultad',
            'departamentoAcademico',
            'carrera',
            'lugaresEjecucion.campus',
            'lugaresEjecucion.departamento',
            'lugaresEjecucion.municipio',
            'beneficiarios',
            'equipo',
            'participacionUniversitaria',
            'practicasAsignatura.asignatura',
            'practicasAsignatura.periodoAcademico',
            'contrapartes.tipoContraparte',
            'contrapartes.instrumentoAlianza',
            'objetivosEspecificos',
            'resultados',
            'presupuestos.detalles',
            'cronograma',
            'certificado.tipoCertificado',
            'certificado.figuraAcreditacion',
            'certificado.carreras.carrera',
            'certificado.carreras.centroFacultad',
            'espaciosAprendizaje',
            'informeFinal.documentosRevision',
            'informeIntermedio',
            'documentos',
            'firmas',
            'accionCatalogos.catalogo',
            'ods',
            'metasContribuye',
            'ejesUnah',
        ];
    }

    private function authorizePpsRegistroAction(int $registroId): PpsServicioSocial
    {
        $registro = PpsServicioSocial::with(['flujoAprobacion', 'etapaActual'])->findOrFail($registroId);

        abort_unless($registro->usuarioPuedeRevisar(Auth::user()), 403);

        return $registro;
    }

    private function enfRevisionesDisponiblesQuery(): Builder
    {
        $user = Auth::user();
        $activeRoleName = $user?->activeRole?->name;

        if (! $user || ! $activeRoleName) {
            return EnfRevision::query()->whereRaw('1 = 0');
        }

        $pendingStates = $this->estadosRevisionEnfPendiente();

        return EnfRevision::query()
            ->whereIn('estado', $pendingStates)
            ->whereNotExists(function ($previousQuery) use ($pendingStates): void {
                $previousQuery
                    ->selectRaw('1')
                    ->from('enf_revisiones as enf_revisiones_anteriores')
                    ->whereColumn('enf_revisiones_anteriores.enf_accion_id', 'enf_revisiones.enf_accion_id')
                    ->whereColumn('enf_revisiones_anteriores.proceso', 'enf_revisiones.proceso')
                    ->whereColumn('enf_revisiones_anteriores.revision_ciclo', 'enf_revisiones.revision_ciclo')
                    ->whereColumn('enf_revisiones_anteriores.orden', '<', 'enf_revisiones.orden')
                    ->whereIn('enf_revisiones_anteriores.estado', $pendingStates);
            })
            ->whereNotExists(function ($newerCycleQuery): void {
                $newerCycleQuery
                    ->selectRaw('1')
                    ->from('enf_revisiones as enf_revisiones_ciclo_nuevo')
                    ->whereColumn('enf_revisiones_ciclo_nuevo.enf_accion_id', 'enf_revisiones.enf_accion_id')
                    ->whereColumn('enf_revisiones_ciclo_nuevo.proceso', 'enf_revisiones.proceso')
                    ->whereColumn('enf_revisiones_ciclo_nuevo.revision_ciclo', '>', 'enf_revisiones.revision_ciclo');
            })
            ->where(function (Builder $responsableQuery) use ($user, $activeRoleName): void {
                $responsableQuery
                    ->where(function (Builder $assignedQuery) use ($user, $activeRoleName): void {
                        $assignedQuery
                            ->where('asignado_usuario_id', $user->id)
                            ->where(function (Builder $roleQuery) use ($activeRoleName): void {
                                $roleQuery
                                    ->whereNull('rol_requerido')
                                    ->orWhere('rol_requerido', $activeRoleName);
                            });
                    })
                    ->orWhere(function (Builder $roleQuery) use ($activeRoleName): void {
                        $roleQuery
                            ->whereNull('asignado_usuario_id')
                            ->where('rol_requerido', $activeRoleName);
                    })
                    ->orWhere(function (Builder $assignmentQuery) use ($user, $activeRoleName): void {
                        $assignmentQuery
                            ->where('responsable_usuario_id', $user->id)
                            ->where(function (Builder $roleQuery) use ($activeRoleName): void {
                                $roleQuery
                                    ->whereNull('rol_requerido')
                                    ->orWhere('rol_requerido', $activeRoleName);
                            });
                    });
            });
    }

    private function ppsRegistrosDisponiblesQuery(): Builder
    {
        return PpsServicioSocial::pendientesParaUsuario(Auth::user());
    }

    private function canActOnEnfRevision(EnfRevision $revision): bool
    {
        $user = Auth::user();
        if (! $user || ! $revision->accion) {
            return false;
        }
        return app(EnfWorkflowService::class)->puedeRevisar($revision, $user);
    }

    private function estadosRevisionEnfPendiente(): array
    {
        return ['PENDIENTE', 'PENDIENTE_ASIGNACION', 'ASIGNADO', 'EN_PROCESO'];
    }

    private function notificarRevisionEnf(EnfAccion $accion, EnfRevision $revision): void
    {
        $users = collect();

        if ($revision->asignado_usuario_id) {
            $users = \App\Models\User::query()->whereKey($revision->asignado_usuario_id)->get();
        } elseif ($revision->responsable_usuario_id) {
            $users = \App\Models\User::query()->whereKey($revision->responsable_usuario_id)->get();
        } elseif ($revision->flujoEtapa?->rolRevisor?->name) {
            $users = \App\Models\User::role($revision->flujoEtapa->rolRevisor->name)->orderBy('name')->get();
        }

        $emails = $users->pluck('email')->filter()->unique()->values();

        if ($emails->isEmpty()) {
            return;
        }

        try {
            Mail::to($emails->all())->queue(new EnfRevisionAsignada($accion, $revision));
        } catch (\Throwable $exception) {
            Log::warning('No se pudo notificar la revisión ENF.', [
                'enf_accion_id' => $accion->id,
                'revision_id' => $revision->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function canActOnFirma(FirmaProyecto $firma): bool
    {
        if ($firma->usaFlujoPorEtapa()) {
            return $this->canActOnWorkflowStageFirma($firma);
        }

        if ($firma->estado_revision !== 'Pendiente') {
            return false;
        }

        if ($firma->firmable_type === Proyecto::class) {
            $estadoActualId = $firma->proyecto?->estado?->tipo_estado_id;
            $estadoFirmaId = $firma->cargo_firma?->tipo_estado_id;

            if (! $estadoActualId || ! $estadoFirmaId || (int) $estadoActualId !== (int) $estadoFirmaId) {
                return false;
            }
        }

        if ($firma->firmable_type === DocumentoProyecto::class) {
            $estadoActualId = $firma->documento_proyecto?->estado?->tipo_estado_id;
            $estadoFirmaId = $firma->cargo_firma?->tipo_estado_id;

            if (! $estadoActualId || ! $estadoFirmaId || (int) $estadoActualId !== (int) $estadoFirmaId) {
                return false;
            }
        }

        $user = Auth::user();

        $activeRoleName = $user?->activeRole?->name;
        $cargoRoleName = $firma->cargo_firma?->tipoCargoFirma?->nombre;

        if (filled($activeRoleName)) {
            return $activeRoleName === $cargoRoleName;
        }

        return $user?->empleado && (int) $firma->empleado_id === (int) $user->empleado->id;
    }
}
