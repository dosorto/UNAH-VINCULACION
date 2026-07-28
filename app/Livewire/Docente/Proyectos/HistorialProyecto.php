<?php

namespace App\Livewire\Docente\Proyectos;

use App\Models\Estado\EstadoProyecto;
use App\Models\User;
use App\Models\Proyecto\EmpleadoProyecto;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\DocumentoProyecto;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\Proyecto\FichaActualizacion;
use App\Models\Estado\TipoEstado;
use App\Concerns\ReenviaDesdeSubsanacionPorEtapa;
use App\Support\Notification;
use App\Services\InformeFinal\InformeFinalProyectoWorkflowService;
use App\Services\InformeIntermedio\InformeIntermedioProyectoWorkflowService;
use App\Services\Proyecto\ProyectoWorkflowService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;

class HistorialProyecto extends Component
{
    use WithFileUploads;
    use ReenviaDesdeSubsanacionPorEtapa;

    public Proyecto $proyecto;
    public bool $esCoordinador = false;

    public bool $informeIntermedioModal = false;
    public $informeIntermedioFile = null;
    public array $destinatariosIntermedio = [];
    public array $destinatariosCierre = [];

    public bool $subsanarModal = false;
    public string $subsanarComentario = '';

    public function mount(Proyecto $proyecto, InformeFinalProyectoWorkflowService $workflow): void
    {
        $this->proyecto = $proyecto;

        $user = auth()->user();
        $esAdminSistema = $user && $user->hasAnyRole(['admin', 'Director/Enlace', 'Revisor Vinculacion']);
        $puedeAuditarInformeFinal = $proyecto->usuarioPuedeAuditarInformeFinal($user);

        $this->esCoordinador = $workflow->usuarioPuedeGestionar($proyecto, $user);

        if (!$esAdminSistema && ! $puedeAuditarInformeFinal) {
            if (!$user || !$user->empleado) {
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

                if (!$this->esCoordinador && !$esFirmante) {
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

    public function openSubsanar(): void
    {
        $this->authorizeFirmaPendiente();
        $this->subsanarComentario = '';
        $this->subsanarModal = true;
    }

    public function subsanar(): void
    {
        $this->validate(['subsanarComentario' => 'required|string']);

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

        $this->proyecto->firma_proyecto()->update([
            'estado_revision' => 'Pendiente',
            'firma_id'        => null,
            'sello_id'        => null,
            'fecha_firma'     => null,
        ]);

        $this->proyecto->estado_proyecto()->create([
            'empleado_id'    => auth()->user()->empleado->id,
            'tipo_estado_id' => TipoEstado::where('nombre', 'Subsanacion')->first()->id,
            'fecha'          => now(),
            'comentario'     => $this->subsanarComentario,
        ]);

        $this->subsanarModal = false;
        $this->subsanarComentario = '';
        $this->proyecto = $this->proyecto->fresh();

        Notification::make()
            ->title('Proyecto enviado a subsanacion')
            ->body('La etapa '.$firma->cargo_firma?->tipoCargoFirma?->nombre.' devolvio el proyecto para correcciones.')
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
    ): View
    {
        $proyecto = $this->proyecto;

        $documentosIds = DocumentoProyecto::where('proyecto_id', $proyecto->id)->pluck('id')->toArray();

        $estados = EstadoProyecto::where(function ($query) use ($proyecto, $documentosIds) {
            $query->where(function ($q) use ($proyecto) {
                $q->where('estadoable_type', Proyecto::class)->where('estadoable_id', $proyecto->id);
            });
            if (!empty($documentosIds)) {
                $query->orWhere(function ($q) use ($documentosIds) {
                    $q->where('estadoable_type', DocumentoProyecto::class)->whereIn('estadoable_id', $documentosIds);
                });
            }
        })
        ->with(['empleado', 'tipoestado', 'estadoable'])
        ->orderByDesc('created_at')
        ->get();

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

        return view('livewire.docente.proyectos.historial-proyecto', compact(
            'proyecto',
            'estados',
            'diasTranscurridos',
            'cierreInformeFinal',
            'fichaActualizacionPendiente',
            'informeIntermedio',
            'opcionesDestinatariosIntermedio',
            'opcionesDestinatariosCierre'
        ));
    }

    private function authorizeFirmaPendiente(): FirmaProyecto
    {
        $firma = $this->firmaPendienteRevision();

        abort_unless($firma, 403);

        return $firma;
    }

    private function canActOnFirma(FirmaProyecto $firma): bool
    {
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

    private function estadoActualProyectoId(): ?int
    {
        return $this->proyecto
            ->estado_proyecto()
            ->where('es_actual', true)
            ->value('tipo_estado_id');
    }
}
