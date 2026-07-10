<?php

namespace App\Livewire\Docente\Proyectos;

use App\Concerns\ResolvesFirmasPendientes;
use App\Http\Controllers\Docente\VerificarConstancia;
use App\Mail\EnfRevisionAsignada;
use App\Models\ENF\EnfAccion;
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
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Livewire\WithPagination;

class ProyectosPorFirmar extends Component
{
    use WithPagination;
    use ResolvesFirmasPendientes;

    public Empleado $docente;
    public bool $viewModal = false;
    public ?int $viewId = null;
    public bool $rechazarModal = false;
    public ?int $rechazarId = null;
    public string $rechazarComentario = '';
    public bool $enfSubsanarModal = false;
    public ?int $enfSubsanarRevisionId = null;
    public string $enfSubsanarComentario = '';
    public bool $ppsSubsanarModal = false;
    public ?int $ppsSubsanarRegistroId = null;
    public string $ppsSubsanarComentario = '';
    public bool $reasignarModal = false;
    public ?int $reasignarFirmaId = null;
    public ?int $reasignarNuevoUsuarioId = null;
    public array $reasignarCandidatos = [];

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
    }

    public function puedeReasignar(?FirmaProyecto $firma): bool
    {
        return $firma
            && $firma->usaFlujoPorEtapa()
            && $firma->estado_revision === 'Pendiente'
            && $firma->responsable_usuario_id
            && (int) $firma->responsable_usuario_id === (int) Auth::id();
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

    public function closeReasignar(): void
    {
        $this->reasignarModal = false;
        $this->reasignarFirmaId = null;
        $this->reasignarNuevoUsuarioId = null;
        $this->reasignarCandidatos = [];
    }

    public function confirmarReasignacion(): void
    {
        $this->validate(['reasignarNuevoUsuarioId' => 'required|integer|exists:users,id']);

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

        if ($firma->usaFlujoPorEtapa()) {
            $this->aprobarFirmaPorEtapa($firma->fresh(), Auth::user());

            $this->viewModal = false;
            $this->viewId = null;

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
    }

    public function aprobarEnfRevision(int $revisionId): void
    {
        $revision = $this->authorizeEnfRevisionAction($revisionId);
        $accion = $revision->accion;

        DB::transaction(function () use ($accion, $revision): void {
            $revision->update([
                'estado' => 'APROBADO',
                'decidido_por_usuario_id' => Auth::id(),
                'firmado_en' => now(),
            ]);

            $siguiente = $accion->revisiones()
                ->where('revision_ciclo', $accion->revision_ciclo)
                ->where('orden', '>', $revision->orden)
                ->whereIn('estado', $this->estadosRevisionEnfPendiente())
                ->orderBy('orden')
                ->first();

            if ($siguiente) {
                $siguiente->update([
                    'estado' => $siguiente->asignado_usuario_id || $siguiente->responsable_usuario_id
                        ? 'ASIGNADO'
                        : 'PENDIENTE',
                ]);

                $accion->update(['estado_flujo' => 'EN_REVISION']);
                $this->notificarRevisionEnf($accion->fresh(), $siguiente->fresh('flujoEtapa.rolRevisor'));

                return;
            }

            $accion->update([
                'estado_flujo' => 'APROBADO',
                'fecha_aprobacion' => now()->toDateString(),
            ]);
        });

        Notification::make()->title('¡Realizado!')->body('Etapa ENF aprobada correctamente.')->success()->send();
    }

    public function openEnfSubsanar(int $revisionId): void
    {
        $this->authorizeEnfRevisionAction($revisionId);

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
        $accion = $revision->accion;

        DB::transaction(function () use ($accion, $revision): void {
            $revision->update([
                'estado' => 'SUBSANACION',
                'observaciones' => $this->enfSubsanarComentario,
                'decidido_por_usuario_id' => Auth::id(),
                'firmado_en' => now(),
            ]);

            $accion->update(['estado_flujo' => 'SUBSANACION']);
        });

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
            ->title($registro->estado === PpsServicioSocial::ESTADO_APROBADO ? 'Registro aprobado' : 'Etapa aprobada')
            ->body($registro->estado === PpsServicioSocial::ESTADO_APROBADO
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

        return view('livewire.docente.proyectos.proyectos-por-firmar', compact('records', 'enfRevisiones', 'ppsRegistros', 'viewFirma', 'viewProyecto', 'viewDocumento'));
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
            ->whereHas('accion', fn (Builder $query) => $query
                ->where('estado_flujo', 'EN_REVISION')
                ->whereColumn('enf_revisiones.revision_ciclo', 'enf_acciones.revision_ciclo'))
            ->whereNotExists(function ($previousQuery) use ($pendingStates): void {
                $previousQuery
                    ->selectRaw('1')
                    ->from('enf_revisiones as enf_revisiones_anteriores')
                    ->whereColumn('enf_revisiones_anteriores.enf_accion_id', 'enf_revisiones.enf_accion_id')
                    ->whereColumn('enf_revisiones_anteriores.revision_ciclo', 'enf_revisiones.revision_ciclo')
                    ->whereColumn('enf_revisiones_anteriores.orden', '<', 'enf_revisiones.orden')
                    ->whereIn('enf_revisiones_anteriores.estado', $pendingStates);
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
        $activeRoleName = $user?->activeRole?->name;
        $accion = $revision->accion;

        if (! $user || ! $activeRoleName || ! $accion || $accion->estado_flujo !== 'EN_REVISION') {
            return false;
        }

        $revisionActual = $accion->revisiones
            ->where('revision_ciclo', (int) $accion->revision_ciclo)
            ->whereIn('estado', $this->estadosRevisionEnfPendiente())
            ->sortBy('orden')
            ->first();

        if (! $revisionActual || (int) $revisionActual->id !== (int) $revision->id) {
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

    protected function aprobarFirmaPorEtapa(FirmaProyecto $firma, User $user): FirmaProyecto
    {
        return DB::transaction(function () use ($firma, $user): FirmaProyecto {
            $firmaBloqueada = FirmaProyecto::query()
                ->whereKey($firma->id)
                ->lockForUpdate()
                ->first();

            if (! $firmaBloqueada || ! $this->canActOnWorkflowStageFirma($firmaBloqueada, $user)) {
                throw new \RuntimeException('La firma ya no se encuentra disponible para aprobación.');
            }

            $proyecto = $this->proyectoDeFirmaPorEtapa($firmaBloqueada);

            if (! $proyecto) {
                throw new \RuntimeException('La firma no pertenece a un proyecto válido.');
            }

            $documento = $this->documentoDeFirmaPorEtapa($firmaBloqueada);
            $empleado = $user->empleado()->with(['firma', 'sello'])->first();

            if (! $empleado) {
                throw new \RuntimeException('El usuario no tiene un empleado activo asociado.');
            }

            $firmaBloqueada->update([
                'estado_revision' => 'Aprobado',
                'firma_id' => $empleado->firma?->id,
                'sello_id' => $empleado->sello?->id,
                'fecha_firma' => now(),
            ]);

            $firmaAprobada = $firmaBloqueada->fresh();

            $proyecto->anularFirmasPendientesDuplicadasDeEtapa(
                (int) $firmaAprobada->flujo_aprobacion_etapa_id,
                (int) $firmaAprobada->revision_ciclo,
                $firmaAprobada->id,
                $documento
            );

            $siguienteFirma = $proyecto->siguienteFirmaDeEtapa($firmaAprobada);

            if ($siguienteFirma) {
                $this->registrarEstadoSiguienteDeFirmaPorEtapa($firmaAprobada, $siguienteFirma, $user);
            } else {
                $this->finalizarFlujoDeFirmaPorEtapa($firmaAprobada, $user);
            }

            return $firmaAprobada->fresh();
        });
    }

    protected function registrarEstadoSiguienteDeFirmaPorEtapa(
        FirmaProyecto $firmaAprobada,
        FirmaProyecto $siguienteFirma,
        User $user
    ): void {
        $proyecto = $this->proyectoDeFirmaPorEtapa($firmaAprobada);
        $documento = $this->documentoDeFirmaPorEtapa($firmaAprobada);

        if (! $proyecto || $siguienteFirma->estado_revision !== 'Pendiente' || ! $proyecto->firmaEsActualEnFlujoPorEtapa($siguienteFirma)) {
            throw new \RuntimeException('No se pudo determinar de forma segura la siguiente etapa del flujo.');
        }

        $tipoEstadoId = $siguienteFirma->cargo_firma()->value('tipo_estado_id');

        if (! $tipoEstadoId) {
            throw new \RuntimeException('No se pudo determinar de forma segura la siguiente etapa del flujo.');
        }

        $empleadoId = $user->empleado?->id;

        if (! $empleadoId) {
            throw new \RuntimeException('El usuario no tiene un empleado activo asociado.');
        }

        $payload = [
            'empleado_id' => $empleadoId,
            'tipo_estado_id' => $tipoEstadoId,
            'fecha' => now(),
            'comentario' => $documento
                ? 'Firma aprobada y documento avanzado a la siguiente etapa del flujo.'
                : 'Firma aprobada y proyecto avanzado a la siguiente etapa del flujo.',
        ];

        if ($documento) {
            $documento->estado_documento()->create($payload);
            return;
        }

        $proyecto->estado_proyecto()->create($payload);
    }

    protected function finalizarFlujoDeFirmaPorEtapa(FirmaProyecto $firmaAprobada, User $user): void
    {
        $proyecto = $this->proyectoDeFirmaPorEtapa($firmaAprobada);
        $documento = $this->documentoDeFirmaPorEtapa($firmaAprobada);

        if (! $proyecto || ! $firmaAprobada->flujo_aprobacion_id || ! $firmaAprobada->revision_ciclo) {
            throw new \RuntimeException('El recorrido de firmas no está completo o contiene una etapa bloqueada.');
        }

        if (! $proyecto->firmasDeEtapasCompletadas(
            (int) $firmaAprobada->flujo_aprobacion_id,
            (int) $firmaAprobada->revision_ciclo,
            $documento
        )) {
            throw new \RuntimeException('El recorrido de firmas no está completo o contiene una etapa bloqueada.');
        }

        if ($documento) {
            $proceso = Proyecto::procesoFlujoParaDocumento($documento->tipo_documento);

            if (! $proceso) {
                throw new \RuntimeException('No se puede determinar el proceso del documento.');
            }

            $this->marcarDocumentoAprobado($documento);
            return;
        }

        $estadoFinalId = $proyecto->estadoFinalProcesoId(Proyecto::FLUJO_INSCRIPCION);

        if (! $estadoFinalId) {
            throw new \RuntimeException('No existe un estado final configurado para el flujo de inscripción.');
        }

        $empleadoId = $user->empleado?->id;

        if (! $empleadoId) {
            throw new \RuntimeException('El usuario no tiene un empleado activo asociado.');
        }

        $proyecto->estado_proyecto()->create([
            'empleado_id' => $empleadoId,
            'tipo_estado_id' => $estadoFinalId,
            'fecha' => now(),
            'comentario' => 'Todas las etapas del flujo de inscripción fueron aprobadas.',
        ]);
    }

    protected function rechazarFirmaPorEtapa(FirmaProyecto $firma, User $user, string $comentario): FirmaProyecto
    {
        $comentario = trim($comentario);

        if ($comentario === '') {
            throw new \RuntimeException('Debe indicar el motivo de la subsanación.');
        }

        return DB::transaction(function () use ($firma, $user, $comentario): FirmaProyecto {
            $firmaBloqueada = FirmaProyecto::query()
                ->whereKey($firma->id)
                ->lockForUpdate()
                ->first();

            if (! $firmaBloqueada || ! $this->canActOnWorkflowStageFirma($firmaBloqueada, $user)) {
                throw new \RuntimeException('La firma ya no se encuentra disponible para rechazo.');
            }

            $proyecto = $this->proyectoDeFirmaPorEtapa($firmaBloqueada);

            if (! $proyecto) {
                throw new \RuntimeException('La firma no pertenece a un proyecto válido.');
            }

            $documento = $this->documentoDeFirmaPorEtapa($firmaBloqueada);
            $empleadoId = $user->empleado?->id;

            if (! $empleadoId) {
                throw new \RuntimeException('El usuario no tiene un empleado activo asociado.');
            }

            $firmaBloqueada->update([
                'estado_revision' => 'Rechazado',
                'firma_id' => null,
                'sello_id' => null,
                'fecha_firma' => now(),
            ]);

            $firmaRechazada = $firmaBloqueada->fresh();

            $proyecto->anularFirmasPendientesDuplicadasDeEtapa(
                (int) $firmaRechazada->flujo_aprobacion_etapa_id,
                (int) $firmaRechazada->revision_ciclo,
                $firmaRechazada->id,
                $documento
            );

            $this->registrarSubsanacionPorRechazoDeEtapa($proyecto, $documento, $empleadoId, $comentario);
            $this->verificarRecorridoBloqueadoPorRechazo($firmaRechazada->fresh(), $proyecto, $documento);

            return $firmaRechazada->fresh();
        });
    }

    protected function verificarRecorridoBloqueadoPorRechazo(
        FirmaProyecto $firmaRechazada,
        Proyecto $proyecto,
        ?DocumentoProyecto $documento = null
    ): void {
        $firmaRechazada = $firmaRechazada->fresh();

        if ($firmaRechazada->estado_revision !== 'Rechazado') {
            throw new \RuntimeException('No se pudo bloquear de forma segura el recorrido después del rechazo.');
        }

        if ($proyecto->firmaActualDeEtapasDelFlujo(
            (int) $firmaRechazada->flujo_aprobacion_id,
            (int) $firmaRechazada->revision_ciclo,
            $documento
        )) {
            throw new \RuntimeException('No se pudo bloquear de forma segura el recorrido después del rechazo.');
        }

        if ($proyecto->firmasDeEtapasCompletadas(
            (int) $firmaRechazada->flujo_aprobacion_id,
            (int) $firmaRechazada->revision_ciclo,
            $documento
        )) {
            throw new \RuntimeException('No se pudo bloquear de forma segura el recorrido después del rechazo.');
        }

        $firmasPosteriores = $proyecto->firmasDeEtapasDelFlujo(
            (int) $firmaRechazada->flujo_aprobacion_id,
            (int) $firmaRechazada->revision_ciclo,
            $documento
        )->filter(fn (FirmaProyecto $firma): bool => (int) $firma->orden_revision > (int) $firmaRechazada->orden_revision);

        foreach ($firmasPosteriores as $firmaPosterior) {
            if ($firmaPosterior->estado_revision !== 'Pendiente' || $proyecto->firmaEsActualEnFlujoPorEtapa($firmaPosterior)) {
                throw new \RuntimeException('No se pudo bloquear de forma segura el recorrido después del rechazo.');
            }
        }
    }

    protected function registrarSubsanacionPorRechazoDeEtapa(
        Proyecto $proyecto,
        ?DocumentoProyecto $documento,
        int $empleadoId,
        string $comentario
    ): void {
        $subsanacionId = TipoEstado::query()
            ->where('nombre', 'Subsanacion')
            ->value('id');

        if (! $subsanacionId) {
            throw new \RuntimeException('No existe un estado de subsanación configurado.');
        }

        $payload = [
            'empleado_id' => $empleadoId,
            'tipo_estado_id' => $subsanacionId,
            'fecha' => now(),
            'comentario' => $comentario,
        ];

        if ($documento) {
            $documento->estado_documento()->create($payload);
            return;
        }

        $proyecto->estado_proyecto()->create($payload);
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

    private function marcarDocumentoAprobado(DocumentoProyecto $documento): void
    {
        if ($documento->tipo_documento === 'Informe Final') {
            $proyecto = $documento->proyecto;

            $proyecto->estado_proyecto()->create([
                'empleado_id' => auth()->user()->empleado->id,
                'tipo_estado_id' => TipoEstado::where('nombre', 'Finalizado')->first()->id,
                'fecha' => now(),
                'comentario' => 'El informe ha sido aprobado correctamente',
            ]);

            VerificarConstancia::makeConstanciasProyecto($proyecto);
        }

        $documento->estado_documento()->create([
            'empleado_id' => auth()->user()->empleado->id,
            'tipo_estado_id' => TipoEstado::where('nombre', 'Aprobado')->first()->id,
            'fecha' => now(),
            'comentario' => 'El informe ha sido aprobado correctamente',
        ]);
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
