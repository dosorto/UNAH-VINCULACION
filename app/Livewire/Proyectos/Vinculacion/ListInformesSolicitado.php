<?php

namespace App\Livewire\Proyectos\Vinculacion;

use App\Http\Controllers\Docente\VerificarConstancia;
use App\Models\Estado\TipoEstado;
use App\Models\Proyecto\DocumentoProyecto;
use App\Models\Proyecto\FirmaProyecto;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\ProyectoEstadoCambiado;
use Livewire\Component;
use Livewire\WithPagination;

class ListInformesSolicitado extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $viewModal = false;
    public ?int $viewDocumentoId = null;

    public bool $rechazarModal = false;
    public ?int $rechazarDocumentoId = null;
    public string $rechazarComentario = '';

    public bool $aprobarModal = false;
    public ?int $aprobarDocumentoId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openView(int $id): void
    {
        $this->viewDocumentoId = $id;
        $this->viewModal = true;
    }

    public function openRechazar(int $id): void
    {
        $this->authorizeInformeAction($id);

        $this->rechazarDocumentoId = $id;
        $this->rechazarComentario = '';
        $this->rechazarModal = true;
    }

    public function rechazar(): void
    {
        $this->validate(['rechazarComentario' => 'required|string']);

        $doc = DocumentoProyecto::findOrFail($this->rechazarDocumentoId);
        $this->authorizeInformeAction($doc);

        $doc->firma_documento()->update([
            'estado_revision' => 'Pendiente',
            'firma_id'        => null,
            'sello_id'        => null,
        ]);

        $doc->estado_documento()->create([
            'empleado_id'   => Auth::user()->empleado->id,
            'tipo_estado_id' => TipoEstado::where('nombre', 'Subsanacion')->first()->id,
            'fecha'         => now(),
            'comentario'    => $this->rechazarComentario,
        ]);

        try {
            $doc->refresh();
            $doc->load(['proyecto.coordinador_proyecto.empleado.user']);
            if ($doc->proyecto?->coordinador?->user) {
                Mail::to($doc->proyecto->coordinador->user->email)->send(
                    new ProyectoEstadoCambiado($doc->proyecto, $doc->proyecto->coordinador->user, 'Subsanación de ' . $doc->tipo_documento, $this->rechazarComentario, 'rechazo de informe')
                );
            }
        } catch (\Exception $e) {
            Log::error('Error enviando correo rechazo informe: ' . $e->getMessage());
        }

        $this->rechazarModal = false;
        $this->rechazarDocumentoId = null;
        $this->viewModal = false;
        Notification::make()->title('¡Realizado!')->body('Informe rechazado correctamente.')->warning()->send();
    }

    public function openAprobar(int $id): void
    {
        $this->authorizeInformeAction($id);

        $this->aprobarDocumentoId = $id;
        $this->aprobarModal = true;
    }

    public function aprobar(): void
    {
        $doc = DocumentoProyecto::findOrFail($this->aprobarDocumentoId);
        $firma = $this->authorizeInformeAction($doc);

        $firma->update([
            'estado_revision' => 'Aprobado',
            'firma_id'        => Auth::user()?->empleado?->firma?->id,
            'sello_id'        => Auth::user()?->empleado?->sello?->id,
            'fecha_firma'     => now(),
        ]);

        if ($doc->tipo_documento === 'Informe Final') {
            $proyecto = $doc->proyecto;
            $proyecto->estado_proyecto()->create([
                'empleado_id'   => Auth::user()->empleado->id,
                'tipo_estado_id' => TipoEstado::where('nombre', 'Finalizado')->first()->id,
                'fecha'         => now(),
                'comentario'    => 'El informe ha sido aprobado correctamente',
            ]);
            VerificarConstancia::makeConstanciasProyecto($proyecto);
        }

        $doc->estado_documento()->create([
            'empleado_id'   => Auth::user()->empleado->id,
            'tipo_estado_id' => TipoEstado::where('nombre', 'Aprobado')->first()->id,
            'fecha'         => now(),
            'comentario'    => 'El informe ha sido aprobado correctamente',
        ]);

        $estadoInforme = $doc->tipo_documento . ' Aprobado';
        $mensajeAprobacion = $doc->tipo_documento === 'Informe Final'
            ? 'Su ' . $doc->tipo_documento . ' ha sido aprobado. El proyecto ha sido marcado como FINALIZADO.'
            : 'Su ' . $doc->tipo_documento . ' ha sido aprobado. Puede continuar con las siguientes etapas.';

        try {
            $doc->refresh();
            $doc->load(['proyecto.coordinador_proyecto.empleado.user']);
            if ($doc->proyecto?->coordinador?->user) {
                Mail::to($doc->proyecto->coordinador->user->email)->send(
                    new ProyectoEstadoCambiado($doc->proyecto, $doc->proyecto->coordinador->user, $estadoInforme, $mensajeAprobacion, 'aprobación de informe')
                );
            }
        } catch (\Exception $e) {
            Log::error('Error enviando correo aprobación informe: ' . $e->getMessage());
        }

        $this->aprobarModal = false;
        $this->viewModal = false;
        Notification::make()->title('¡Realizado!')->body('Informe aprobado correctamente.')->success()->send();
    }

    public function puedeAprobarInforme(DocumentoProyecto $documento): bool
    {
        return (bool) $this->firmaPendienteDelDocumento($documento);
    }

    public function textoAprobarInforme(DocumentoProyecto $documento): string
    {
        return $documento->tipo_documento === 'Informe Final'
            ? 'Aprobar y finalizar'
            : 'Aprobar informe';
    }

    public function render(): View
    {
        $estadoRevisionId = TipoEstado::where('nombre', 'En revision')->first()->id;
        $user = Auth::user();
        $activeRoleName = $user?->activeRole?->name;
        $isAdmin = (bool) $user?->hasRole('admin');

        $records = DocumentoProyecto::query()
            ->whereIn('id', function ($query) use ($estadoRevisionId) {
                $query->select('estadoable_id')
                    ->from('estado_proyecto')
                    ->where('estadoable_type', DocumentoProyecto::class)
                    ->where('tipo_estado_id', $estadoRevisionId)
                    ->where('es_actual', true);
            })
            ->whereHas('firma_documento', function ($query) use ($estadoRevisionId, $activeRoleName, $isAdmin) {
                $query
                    ->where('estado_revision', 'Pendiente')
                    ->whereHas('cargo_firma', fn ($cargoQuery) => $cargoQuery->where('tipo_estado_id', $estadoRevisionId));

                if (! $isAdmin) {
                    $query->whereHas('cargo_firma.tipoCargoFirma', fn ($roleQuery) => $roleQuery->where('nombre', $activeRoleName));
                }
            })
            ->when($this->search, fn($q) => $q->whereHas('proyecto', fn($q2) =>
                $q2->where('nombre_proyecto', 'like', '%' . $this->search . '%')
            ))
            ->with(['proyecto', 'estadoActual.tipoestado', 'firma_documento.cargo_firma.tipoCargoFirma'])
            ->paginate(10);

        $viewDocumento = $this->viewDocumentoId
            ? DocumentoProyecto::with(['proyecto', 'estadoActual.tipoestado', 'firma_documento.cargo_firma.tipoCargoFirma'])->find($this->viewDocumentoId)
            : null;

        return view('livewire.proyectos.vinculacion.list-informes-solicitado', compact('records', 'viewDocumento'));
    }

    private function authorizeInformeAction(int|DocumentoProyecto $documento): FirmaProyecto
    {
        $documento = $documento instanceof DocumentoProyecto
            ? $documento
            : DocumentoProyecto::with(['estadoActual.tipoestado', 'firma_documento.cargo_firma.tipoCargoFirma'])->findOrFail($documento);

        $firma = $this->firmaPendienteDelDocumento($documento);

        abort_unless($firma, 403);

        return $firma;
    }

    private function firmaPendienteDelDocumento(DocumentoProyecto $documento): ?FirmaProyecto
    {
        $user = Auth::user();

        if (! $user || ! $user->can('proyectos.informes')) {
            return null;
        }

        $estadoActual = $documento->estadoActual ?? $documento->estado;
        $estadoActualId = $estadoActual?->tipo_estado_id;

        if (! $estadoActualId || $estadoActual?->tipoestado?->nombre !== 'En revision') {
            return null;
        }

        $activeRoleName = $user->activeRole?->name;
        $isAdmin = $user->hasRole('admin');

        return $documento
            ->firma_documento()
            ->with('cargo_firma.tipoCargoFirma')
            ->where('estado_revision', 'Pendiente')
            ->whereHas('cargo_firma', fn ($query) => $query->where('tipo_estado_id', $estadoActualId))
            ->get()
            ->first(function (FirmaProyecto $firma) use ($activeRoleName, $isAdmin) {
                if ($isAdmin) {
                    return true;
                }

                return filled($activeRoleName)
                    && $activeRoleName === $firma->cargo_firma?->tipoCargoFirma?->nombre;
            });
    }
}
