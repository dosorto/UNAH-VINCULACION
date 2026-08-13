<?php

namespace App\Livewire\Docente\Proyectos;

use App\Concerns\ReenviaDesdeSubsanacionPorEtapa;
use App\Concerns\ResolvesFirmasPendientes;
use App\Concerns\ResuelveFirmaPorEtapa;
use App\Http\Controllers\Docente\VerificarConstancia;
use App\Mail\ProyectoEstadoCambiado;
use App\Models\Estado\EstadoProyecto;
use App\Models\Estado\TipoEstado;
use App\Models\InformeFinal\InformeFinalDocumentoRevision;
use App\Models\Proyecto\DocumentoProyecto;
use App\Models\Proyecto\EmpleadoProyecto;
use App\Models\Proyecto\FichaActualizacion;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\ProyectoDocumentoSubsanacion;
use App\Services\InformeFinal\InformeFinalProyectoWorkflowService;
use App\Services\InformeIntermedio\InformeIntermedioProyectoWorkflowService;
use App\Services\Proyecto\ProyectoWorkflowService;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

class HistorialProyecto extends Component
{
    use ReenviaDesdeSubsanacionPorEtapa;
    use ResolvesFirmasPendientes;
    use ResuelveFirmaPorEtapa;
    use WithFileUploads;

    public Proyecto $proyecto;

    public bool $esCoordinador = false;

    public bool $informeIntermedioModal = false;

    public $informeIntermedioFile = null;

    public array $destinatariosIntermedio = [];

    public array $destinatariosCierre = [];

    public bool $subsanarModal = false;

    public string $subsanarComentario = '';

    public $subsanarArchivo = null;

    public ?int $verMovimientoId = null;

    public bool $aprobarModal = false;

    public string $aprobarCodigo = '';

    public string $aprobarDictamen = '';

    public string $aprobacionComentario = '';

    #[Url(as: 'origen', except: '')]
    public string $origen = '';

    public function mount(Proyecto $proyecto, InformeFinalProyectoWorkflowService $workflow): void
    {
        $this->proyecto = $proyecto;

        $user = auth()->user();
        $activeRole = $user?->activeRole;
        $permissionContext = $activeRole ?? $user;
        $puedeVerTodos = $activeRole?->name === 'admin'
            || (bool) $permissionContext?->hasPermissionTo('proyectos.historial')
            || (bool) $permissionContext?->hasPermissionTo('proyectos.solicitados')
            || (bool) $permissionContext?->hasPermissionTo('proyectos.revision-final');
        $puedeVerCentro = (bool) $permissionContext?->hasPermissionTo('director.proyectos');
        $puedeAuditarInformeFinal = $proyecto->usuarioPuedeAuditarInformeFinal($user);

        $this->esCoordinador = $workflow->usuarioPuedeGestionar($proyecto, $user);

        if (! $puedeVerTodos && ! $puedeAuditarInformeFinal && $puedeVerCentro) {
            $centroFacultadId = $user?->empleado?->centro_facultad_id;
            $perteneceAlCentro = $centroFacultadId
                && $proyecto->facultades_centros()
                    ->whereKey($centroFacultadId)
                    ->exists();

            abort_unless($perteneceAlCentro, 403, 'No tiene permiso para ver proyectos de otra Facultad o Centro.');
        } elseif (! $puedeVerTodos && ! $puedeAuditarInformeFinal) {
            if (! $user || ! $user->empleado) {
                abort(403, 'No tiene permiso para ver este proyecto');
            }

            $empleadoProyecto = EmpleadoProyecto::where('proyecto_id', $proyecto->id)
                ->where('empleado_id', $user->empleado->id)
                ->first();

            if ($empleadoProyecto) {
                $this->authorize('view', $empleadoProyecto);
            } else {
                $esFirmante = FirmaProyecto::where('firmable_type', Proyecto::class)
                    ->where('firmable_id', $proyecto->id)
                    ->where('empleado_id', $user->empleado->id)
                    ->exists();

                if (! $this->esCoordinador && ! $esFirmante) {
                    abort(403, 'No tiene permiso para ver este proyecto. Solo el coordinador, firmantes o un administrador pueden acceder.');
                }
            }
        }
    }

    public function openSubirIntermedio(): void
    {
        $this->informeIntermedioFile = null;
        $this->informeIntermedioModal = true;
    }

    public function guardarInformeIntermedio(InformeIntermedioProyectoWorkflowService $workflow): void
    {
        $this->validate(['informeIntermedioFile' => 'required|file|mimes:pdf|max:20480']);

        try {
            $workflow->guardarArchivo($this->proyecto->fresh(), $this->informeIntermedioFile, auth()->user());
        } catch (\Throwable $e) {
            Notification::make()->title('No se pudo guardar el informe')->body($e->getMessage())->danger()->send();

            return;
        }

        $this->informeIntermedioModal = false;
        $this->informeIntermedioFile = null;

        $this->proyecto = $this->proyecto->fresh();
        Notification::make()->title('PDF guardado')->body('El Informe Intermedio quedó guardado como borrador.')->success()->send();
    }

    public function enviarInformeIntermedio(InformeIntermedioProyectoWorkflowService $workflow): void
    {
        $informe = $this->proyecto->informeIntermedio()->firstOrFail();

        try {
            $workflow->enviar($informe, auth()->user(), $this->destinatariosIntermedio);
            $this->proyecto = $this->proyecto->fresh();
            Notification::make()->title('Informe enviado')->body('El Informe Intermedio inició su flujo de revisión.')->success()->send();
        } catch (\Throwable $e) {
            Notification::make()->title('No se pudo enviar el informe')->body($e->getMessage())->danger()->send();
        }
    }

    public function eliminarInformeIntermedio(InformeIntermedioProyectoWorkflowService $workflow): void
    {
        $informe = $this->proyecto->informeIntermedio()->firstOrFail();

        try {
            $workflow->eliminarArchivo($informe, auth()->user());
            $this->proyecto = $this->proyecto->fresh();
            Notification::make()->title('PDF eliminado')->body('El borrador del Informe Intermedio fue eliminado.')->success()->send();
        } catch (\Throwable $e) {
            Notification::make()->title('No se pudo eliminar el PDF')->body($e->getMessage())->danger()->send();
        }
    }

    public function crearInformeFinal(InformeFinalProyectoWorkflowService $workflow)
    {
        $informe = $workflow->crearInformeFinal($this->proyecto->fresh(), auth()->user());

        return $this->redirectRoute('proyectos.informe-final', ['proyecto' => $informe->proyecto_id]);
    }

    public function enviarInformeFinal(InformeFinalProyectoWorkflowService $workflow): void
    {
        $informe = $this->proyecto->informeFinalInf001()->firstOrFail();
        abort_unless($workflow->puedeEnviarInformeFinal($informe, auth()->user()), 403);

        try {
            $workflow->enviarInformeFinal($informe, auth()->user(), $this->destinatariosCierre);
            $this->proyecto = $this->proyecto->fresh();
            Notification::make()
                ->title('Informe final enviado')
                ->body('El INF-001 inició el flujo de cierre del proyecto.')
                ->success()
                ->send();
        } catch (\Throwable $exception) {
            Notification::make()
                ->title('No se pudo enviar el informe final')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function firmaPendienteRevision(): ?FirmaProyecto
    {
        $estadoActualId = $this->estadoActualProyectoId();

        if (! $estadoActualId) {
            return null;
        }

        return $this->proyecto
            ->firma_proyecto()
            ->with(['cargo_firma.tipoCargoFirma', 'proyecto.estadoActual'])
            ->where('estado_revision', 'Pendiente')
            ->whereHas('cargo_firma', fn ($query) => $query->where('tipo_estado_id', $estadoActualId))
            ->get()
            ->first(fn (FirmaProyecto $firma) => $this->canActOnFirma($firma));
    }

    public function puedeSubsanar(): bool
    {
        return (bool) $this->firmaPendienteRevision();
    }

    public function openAprobar(): void
    {
        $this->authorizeFirmaPendiente();
        $this->aprobacionComentario = '';

        if ($this->esRevisionSolicitada()) {
            $siglas = $this->proyecto->coordinador?->centro_facultad?->siglas ?? 'XX';
            $year = now()->format('Y');
            $nextId = str_pad((string) ($this->proyecto->id + 1), 3, '0', STR_PAD_LEFT);

            $this->aprobarCodigo = "VRA-DVUS-{$siglas}-{$year}-{$nextId}";
            $this->aprobarDictamen = "DICTAMEN-VRA-DVUS-{$siglas}-{$year}-{$nextId}";
        } else {
            $this->aprobarCodigo = '';
            $this->aprobarDictamen = '';
        }

        $this->aprobarModal = true;
    }

    public function aprobarFirmaPendiente(): void
    {
        $firma = $this->authorizeFirmaPendiente();

        $rules = [
            'aprobacionComentario' => ['nullable', 'string', 'max:5000'],
        ];

        if ($this->esRevisionSolicitada()) {
            $rules['aprobarCodigo'] = ['required', 'string', 'max:255'];
            $rules['aprobarDictamen'] = ['required', 'string', 'max:255'];
        }

        $this->validate($rules, [], [
            'aprobarCodigo' => 'código del proyecto',
            'aprobarDictamen' => 'número de dictamen',
            'aprobacionComentario' => 'observación de aprobación',
        ]);

        $esRevisionSolicitada = $this->esRevisionSolicitada();
        $esRevisionFinal = $this->esRevisionFinal();
        $usaFlujoPorEtapa = $firma->usaFlujoPorEtapa();

        try {
            $nextEstadoNombre = DB::transaction(function () use ($firma, $esRevisionSolicitada): string {
                if ($esRevisionSolicitada) {
                    $this->proyecto->update([
                        'codigo_proyecto' => $this->aprobarCodigo,
                        'numero_dictamen' => $this->aprobarDictamen,
                        'responsable_revision_id' => auth()->user()->empleado->id,
                    ]);
                }

                return $this->aprobarFirmaActual(
                    $firma->fresh(),
                    filled($this->aprobacionComentario) ? $this->aprobacionComentario : null
                );
            });

        } catch (\Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('No se pudo aprobar el proyecto')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        $this->proyecto = $this->proyecto->fresh();

        if ($esRevisionFinal) {
            try {
                VerificarConstancia::makeConstanciasProyecto($this->proyecto);
            } catch (\Throwable $exception) {
                report($exception);
                Log::error('El proyecto fue aprobado, pero no se pudieron generar sus constancias.', [
                    'proyecto_id' => $this->proyecto->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        if (! $usaFlujoPorEtapa) {
            $this->notificarCoordinadorCambio(
                $nextEstadoNombre,
                'Su proyecto fue aprobado y enviado a '.$nextEstadoNombre.'.',
                'aprobación'
            );
        }

        $this->aprobarModal = false;
        $this->aprobacionComentario = '';

        Notification::make()
            ->title('¡Realizado!')
            ->body('Proyecto aprobado y enviado a '.$nextEstadoNombre.'.')
            ->success()
            ->send();
    }

    public function abrirMovimiento(int $estadoId): void
    {
        $this->verMovimientoId = $estadoId;
    }

    public function cerrarMovimiento(): void
    {
        $this->verMovimientoId = null;
    }

    public function openSubsanar(): void
    {
        $this->authorizeFirmaPendiente();
        $this->subsanarComentario = '';
        $this->subsanarArchivo = null;
        $this->resetErrorBag(['subsanarComentario', 'subsanarArchivo']);
        $this->subsanarModal = true;
    }

    public function subsanar(): void
    {
        $this->validate([
            'subsanarComentario' => ['required', 'string', 'max:5000'],
            'subsanarArchivo' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
        ], [], [
            'subsanarComentario' => 'correcciones requeridas',
            'subsanarArchivo' => 'documento de respaldo',
        ]);

        $proyecto = $this->proyecto->fresh();
        $user = auth()->user();

        try {
            $firmaRechazadaPorEtapa = $this->firmaRechazadaActualPorEtapa($proyecto);

            if ($firmaRechazadaPorEtapa) {
                if (! $user) {
                    throw new \RuntimeException('No tiene autorización para reenviar este registro desde subsanación.');
                }

                $this->reenviarDesdeSubsanacionPorEtapa(
                    $firmaRechazadaPorEtapa,
                    $user,
                    $this->empleadosPorEtapaParaReenvio($firmaRechazadaPorEtapa)
                );

                $this->subsanarModal = false;
                $this->subsanarComentario = '';
                $this->proyecto = $this->proyecto->fresh();

                Notification::make()
                    ->title('Éxito')
                    ->body('El registro fue reenviado correctamente para continuar su revisión.')
                    ->success()
                    ->send();

                return;
            }
        } catch (\RuntimeException $exception) {
            Notification::make()
                ->title('No se pudo reenviar el registro')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        $firma = $this->authorizeFirmaPendiente();
        $esRevisionFinal = $this->esRevisionFinal();

        try {
            if ($firma->usaFlujoPorEtapa()) {
                $this->rechazarFirmaPorEtapa($firma->fresh(), $user, $this->subsanarComentario);
            } else {
                DB::transaction(function () use ($esRevisionFinal): void {
                    $this->proyecto->firma_proyecto()->update([
                        'estado_revision' => 'Pendiente',
                        'firma_id' => null,
                        'sello_id' => null,
                        'fecha_firma' => null,
                    ]);

                    if ($esRevisionFinal) {
                        $this->limpiarAprobacionFinalParaSubsanacion();
                    }

                    $this->proyecto->estado_proyecto()->create([
                        'empleado_id' => auth()->user()->empleado->id,
                        'tipo_estado_id' => TipoEstado::where('nombre', 'Subsanacion')->firstOrFail()->id,
                        'fecha' => now(),
                        'comentario' => $this->subsanarComentario,
                    ]);
                });
            }

            if (! $firma->usaFlujoPorEtapa()) {
                $this->notificarCoordinadorCambio(
                    'Proyecto en subsanación',
                    $this->subsanarComentario,
                    'solicitud de subsanación'
                );
            }

            if ($this->subsanarArchivo) {
                $this->guardarDocumentoSubsanacion($this->proyecto->fresh());
            }
        } catch (\Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('No se pudo enviar a subsanación')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        $this->subsanarModal = false;
        $this->subsanarComentario = '';
        $this->subsanarArchivo = null;
        $this->proyecto = $this->proyecto->fresh();

        Notification::make()
            ->title('Proyecto enviado a subsanación')
            ->body('La etapa '.$firma->cargo_firma?->tipoCargoFirma?->nombre.' devolvió el proyecto para correcciones.')
            ->warning()
            ->send();
    }

    protected function proyectoEsperadoIdParaReenvio(): ?int
    {
        return isset($this->proyecto) && $this->proyecto->exists ? (int) $this->proyecto->id : null;
    }

    public function render(
        InformeFinalProyectoWorkflowService $workflow,
        InformeIntermedioProyectoWorkflowService $intermedioWorkflow,
        ProyectoWorkflowService $proyectoWorkflow
    ): View {
        $proyecto = $this->proyecto;

        $documentosIds = DocumentoProyecto::where('proyecto_id', $proyecto->id)->pluck('id')->toArray();

        $estados = EstadoProyecto::where(function ($query) use ($proyecto, $documentosIds) {
            $query->where(function ($q) use ($proyecto) {
                $q->where('estadoable_type', Proyecto::class)->where('estadoable_id', $proyecto->id);
            });
            if (! empty($documentosIds)) {
                $query->orWhere(function ($q) use ($documentosIds) {
                    $q->where('estadoable_type', DocumentoProyecto::class)->whereIn('estadoable_id', $documentosIds);
                });
            }
        })
            ->with(['empleado', 'tipoestado', 'estadoable'])
            ->orderByDesc('created_at')
            ->get();

        $documentosRevisionInformeFinal = Schema::hasTable('informe_final_documentos_revision')
            ? InformeFinalDocumentoRevision::query()
                ->whereHas('informe', fn ($query) => $query->where('proyecto_id', $proyecto->id))
                ->with(['firma', 'usuario'])
                ->get()
                ->groupBy('estado_proyecto_id')
            : collect();

        $documentosSubsanacion = ProyectoDocumentoSubsanacion::query()
            ->where('proyecto_id', $proyecto->id)
            ->with('usuario')
            ->get()
            ->groupBy('estado_proyecto_id');

        $diasTranscurridos = $proyecto->created_at
            ? (int) $proyecto->created_at->diffInDays(now())
            : 0;

        $cierreInformeFinal = $workflow->resumenCierre($proyecto, auth()->user());
        $fichaActualizacionPendiente = FichaActualizacion::query()
            ->where('proyecto_id', $proyecto->id)
            ->pendientes()
            ->latest('id')
            ->first();
        $informeIntermedio = $intermedioWorkflow->resumen($proyecto, auth()->user());
        $opcionesDestinatariosIntermedio = $proyectoWorkflow
            ->destinatariosSeleccionables($proyecto, Proyecto::FLUJO_INFORME_INTERMEDIO);
        $opcionesDestinatariosCierre = $proyectoWorkflow
            ->destinatariosSeleccionables($proyecto, Proyecto::FLUJO_CIERRE_PROYECTO);
        [$historialRouteName, $historialRouteParameters, $historialRouteLabel] = $this->historialRoute();
        $esRevisionSolicitada = $this->esRevisionSolicitada();
        $esRevisionFinal = $this->esRevisionFinal();

        return view('livewire.docente.proyectos.historial-proyecto', compact(
            'proyecto',
            'estados',
            'documentosRevisionInformeFinal',
            'documentosSubsanacion',
            'diasTranscurridos',
            'cierreInformeFinal',
            'fichaActualizacionPendiente',
            'informeIntermedio',
            'opcionesDestinatariosIntermedio',
            'opcionesDestinatariosCierre',
            'historialRouteName',
            'historialRouteParameters',
            'historialRouteLabel',
            'esRevisionSolicitada',
            'esRevisionFinal'
        ));
    }

    private function historialRoute(): array
    {
        $user = auth()->user();
        $permissionContext = $user?->activeRole ?? $user;

        if ($this->origen === 'solicitados' && $permissionContext?->hasPermissionTo('proyectos.solicitados')) {
            return ['listarProyectosSolicitado', [], 'Volver a proyectos solicitados'];
        }

        if ($this->origen === 'revision-final' && $permissionContext?->hasPermissionTo('proyectos.revision-final')) {
            return ['listarProyectoRevisionFinal', [], 'Volver a revisión final'];
        }

        if ($this->origen === 'por-firmar' && $permissionContext?->hasPermissionTo('docente.proyectos')) {
            return ['SolicitudProyectosDocente', [], 'Volver a proyectos por firmar'];
        }

        if ($permissionContext?->hasPermissionTo('proyectos.historial')) {
            return ['listarProyectosVinculacion', [], 'Volver al historial'];
        }

        if ($permissionContext?->hasPermissionTo('proyectos.revision-final')) {
            return ['listarProyectoRevisionFinal', [], 'Volver a revisión final'];
        }

        if ($permissionContext?->hasPermissionTo('director.proyectos')) {
            return ['proyectosCentroFacultad', [], 'Volver a proyectos'];
        }

        if ($permissionContext?->hasPermissionTo('docente.proyectos')) {
            return ['proyectosDocente', ['tipo' => 'proyectos'], 'Volver a mis proyectos'];
        }

        return ['inicio', [], 'Volver al inicio'];
    }

    private function authorizeFirmaPendiente(): FirmaProyecto
    {
        $firma = $this->firmaPendienteRevision();

        abort_unless($firma, 403);

        return $firma;
    }

    private function canActOnFirma(FirmaProyecto $firma): bool
    {
        if ($firma->usaFlujoPorEtapa()) {
            return $this->canActOnWorkflowStageFirma($firma);
        }

        if ($firma->estado_revision !== 'Pendiente') {
            return false;
        }

        $estadoActualId = $this->estadoActualProyectoId();
        $estadoFirmaId = $firma->cargo_firma?->tipo_estado_id;

        if (! $estadoActualId || ! $estadoFirmaId || (int) $estadoActualId !== (int) $estadoFirmaId) {
            return false;
        }

        $user = auth()->user();
        $activeRoleName = $user?->activeRole?->name;
        $cargoRoleName = $firma->cargo_firma?->tipoCargoFirma?->nombre;

        if (filled($activeRoleName)) {
            return $activeRoleName === $cargoRoleName;
        }

        return $user?->empleado && (int) $firma->empleado_id === (int) $user->empleado->id;
    }

    private function aprobarFirmaActual(FirmaProyecto $firma, ?string $comentario): string
    {
        $user = auth()->user();

        if (! $user) {
            throw new \RuntimeException('No tiene autorización para aprobar este proyecto.');
        }

        if ($firma->usaFlujoPorEtapa()) {
            $this->aprobarFirmaPorEtapa($firma, $user, comentario: $comentario);

            return $this->nombreEstadoActualProyecto();
        }

        $firma->update([
            'estado_revision' => 'Aprobado',
            'firma_id' => $user->empleado?->firma?->id,
            'sello_id' => $user->empleado?->sello?->id,
            'fecha_firma' => now(),
        ]);

        $this->proyecto->anularFirmasPendientesDuplicadasDeCargo($firma->cargo_firma_id, $firma->id);
        $this->proyecto->sincronizarFirmasDelFlujo();

        $nextEstadoId = $this->proyecto->nextEstadoIdEnFlujo($firma->cargo_firma_id)
            ?? $this->proyecto->estadoFinalProcesoId(Proyecto::FLUJO_INSCRIPCION);

        if (! $nextEstadoId) {
            throw new \RuntimeException('No se pudo determinar la siguiente etapa del proyecto.');
        }

        $nextEstadoNombre = TipoEstado::find($nextEstadoId)?->nombre ?? 'siguiente etapa';

        $this->proyecto->estado_proyecto()->create([
            'empleado_id' => $user->empleado->id,
            'tipo_estado_id' => $nextEstadoId,
            'fecha' => now(),
            'comentario' => $comentario ?: 'El proyecto fue aprobado y avanzó a '.$nextEstadoNombre.'.',
        ]);

        return $nextEstadoNombre;
    }

    private function limpiarAprobacionFinalParaSubsanacion(): void
    {
        $this->proyecto->firma_revisor_vinculacion()->delete();
        $this->proyecto->forceFill([
            'responsable_revision_id' => null,
            'numero_dictamen' => null,
            'fecha_aprobacion' => null,
            'fecha_registro' => null,
            'numero_libro' => null,
            'numero_tomo' => null,
            'numero_folio' => null,
        ])->save();
        $this->proyecto->categoria()->detach();
        $this->proyecto->ods()->detach();
    }

    private function guardarDocumentoSubsanacion(Proyecto $proyecto): void
    {
        $estadoSubsanacion = $proyecto->estado_proyecto()
            ->whereHas('tipoestado', fn ($q) => $q->where('nombre', 'Subsanacion'))
            ->latest('id')
            ->first();

        $ruta = $this->subsanarArchivo->store('proyectos/'.$proyecto->id.'/subsanaciones', 'local');

        ProyectoDocumentoSubsanacion::create([
            'proyecto_id' => $proyecto->id,
            'estado_proyecto_id' => $estadoSubsanacion?->id,
            'subido_por' => auth()->id(),
            'ruta' => $ruta,
            'nombre_original' => $this->subsanarArchivo->getClientOriginalName(),
            'mime_type' => $this->subsanarArchivo->getMimeType(),
            'tamano_bytes' => $this->subsanarArchivo->getSize(),
        ]);
    }

    private function notificarCoordinadorCambio(string $estado, string $comentario, string $tipo): void
    {
        try {
            $proyecto = $this->proyecto->fresh()->load(['coordinador_proyecto.empleado.user']);
            $coordinador = $proyecto->coordinador_proyecto->first()?->empleado?->user;

            if ($coordinador?->email) {
                Mail::to($coordinador->email)->send(
                    new ProyectoEstadoCambiado($proyecto, $coordinador, $estado, $comentario, $tipo)
                );
            }
        } catch (\Throwable $exception) {
            Log::error('No se pudo notificar el cambio de estado del proyecto.', [
                'proyecto_id' => $this->proyecto->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function esRevisionSolicitada(): bool
    {
        return $this->activeRolePuede('proyectos.solicitados')
            && $this->nombreEstadoActualProyecto() === 'En revision';
    }

    private function esRevisionFinal(): bool
    {
        return $this->activeRolePuede('proyectos.revision-final')
            && $this->nombreEstadoActualProyecto() === 'En revision final';
    }

    private function activeRolePuede(string $permission): bool
    {
        $user = auth()->user();
        $permissionContext = $user?->activeRole ?? $user;

        return (bool) $permissionContext?->hasPermissionTo($permission);
    }

    private function nombreEstadoActualProyecto(): string
    {
        return (string) ($this->proyecto->fresh()->estado?->tipoestado?->nombre ?? 'Sin estado');
    }

    private function estadoActualProyectoId(): ?int
    {
        return $this->proyecto
            ->estado_proyecto()
            ->where('es_actual', true)
            ->value('tipo_estado_id');
    }
}
