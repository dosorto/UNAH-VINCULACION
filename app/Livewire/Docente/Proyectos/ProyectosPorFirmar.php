<?php

namespace App\Livewire\Docente\Proyectos;

use App\Models\Estado\TipoEstado;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\DocumentoProyecto;
use App\Models\Proyecto\FichaActualizacion;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\Proyecto\Proyecto;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ProyectosPorFirmar extends Component
{
    use WithPagination;

    public Empleado $docente;
    public bool $viewModal = false;
    public ?int $viewId = null;
    public bool $rechazarModal = false;
    public ?int $rechazarId = null;
    public string $rechazarComentario = '';

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

        if ($firma->firmable_type == Proyecto::class) {
            $firma->update([
                'estado_revision' => 'Aprobado',
                'firma_id'        => auth()->user()?->empleado?->firma?->id,
                'sello_id'        => auth()->user()?->empleado?->sello?->id,
                'fecha_firma'     => now(),
            ]);

            $firma->proyecto?->anularFirmasPendientesDuplicadasDeCargo($firma->cargo_firma_id, $firma->id);

            $nextEstadoId = $firma->proyecto?->nextEstadoIdForCargo($firma->cargo_firma_id)
                ?? $firma->cargo_firma->estado_siguiente_id;

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
            ]);

            $firma->documento_proyecto->estado_documento()->create([
                'empleado_id'    => $this->docente->id,
                'tipo_estado_id' => $firma->cargo_firma->estado_siguiente_id,
                'fecha'          => now(),
                'comentario'     => 'Firmado y aprobado en este estado',
            ]);
        }

        $this->viewModal = false;
        $this->viewId = null;

        Notification::make()->title('¡Realizado!')->body('Proyecto Aprobado correctamente')->info()->send();
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

        return view('livewire.docente.proyectos.proyectos-por-firmar', compact('records', 'viewFirma', 'viewProyecto', 'viewDocumento'));
    }

    private function firmasDisponiblesQuery()
    {
        $user = Auth::user();
        $activeRoleName = $user?->activeRole?->name;
        $empleadoId = $user?->empleado?->id;

        return FirmaProyecto::query()
            ->select('firma_proyecto.*')
            ->join('cargo_firma', 'firma_proyecto.cargo_firma_id', '=', 'cargo_firma.id')
            ->where('firma_proyecto.estado_revision', 'Pendiente')
            ->where(function ($query) use ($activeRoleName, $empleadoId) {
                if ($activeRoleName) {
                    $query->whereHas('cargo_firma.tipoCargoFirma', fn ($roleQuery) => $roleQuery->where('nombre', $activeRoleName));
                    return;
                }

                if ($empleadoId) {
                    $query->where('firma_proyecto.empleado_id', $empleadoId);
                }
            })
            ->where(function ($query) {
                $query->where(function ($projectQuery) {
                    $projectQuery
                        ->where('firma_proyecto.firmable_type', Proyecto::class)
                        ->whereExists(function ($estadoQuery) {
                            $estadoQuery
                                ->selectRaw('1')
                                ->from('estado_proyecto')
                                ->whereColumn('estado_proyecto.estadoable_id', 'firma_proyecto.firmable_id')
                                ->where('estado_proyecto.estadoable_type', Proyecto::class)
                                ->where('estado_proyecto.es_actual', true)
                                ->whereColumn('estado_proyecto.tipo_estado_id', 'cargo_firma.tipo_estado_id');
                        });
                })->orWhere(function ($documentQuery) {
                    $documentQuery
                        ->where('firma_proyecto.firmable_type', DocumentoProyecto::class)
                        ->whereExists(function ($estadoQuery) {
                            $estadoQuery
                                ->selectRaw('1')
                                ->from('estado_proyecto')
                                ->whereColumn('estado_proyecto.estadoable_id', 'firma_proyecto.firmable_id')
                                ->where('estado_proyecto.estadoable_type', DocumentoProyecto::class)
                                ->where('estado_proyecto.es_actual', true)
                                ->whereColumn('estado_proyecto.tipo_estado_id', 'cargo_firma.tipo_estado_id');
                        });
                })->orWhere(function ($otherQuery) {
                    $otherQuery
                        ->where('firma_proyecto.firmable_type', '!=', Proyecto::class)
                        ->where('firma_proyecto.firmable_type', '!=', DocumentoProyecto::class);
                });
            });
    }

    private function authorizeFirmaAction(int $firmaId): FirmaProyecto
    {
        $firma = FirmaProyecto::with(['cargo_firma.tipoCargoFirma', 'proyecto.estadoActual'])->findOrFail($firmaId);

        abort_unless($this->canActOnFirma($firma), 403);

        return $firma;
    }

    private function canActOnFirma(FirmaProyecto $firma): bool
    {
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
