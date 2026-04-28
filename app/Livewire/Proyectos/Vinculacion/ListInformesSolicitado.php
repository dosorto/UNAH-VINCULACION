<?php

namespace App\Livewire\Proyectos\Vinculacion;

use App\Http\Controllers\Docente\VerificarConstancia;
use App\Models\Estado\TipoEstado;
use App\Models\Proyecto\DocumentoProyecto;
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
        $this->rechazarDocumentoId = $id;
        $this->rechazarComentario = '';
        $this->rechazarModal = true;
    }

    public function rechazar(): void
    {
        $this->validate(['rechazarComentario' => 'required|string']);

        $doc = DocumentoProyecto::findOrFail($this->rechazarDocumentoId);

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
        $this->aprobarDocumentoId = $id;
        $this->aprobarModal = true;
    }

    public function aprobar(): void
    {
        $doc = DocumentoProyecto::findOrFail($this->aprobarDocumentoId);

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

    public function render(): View
    {
        $records = DocumentoProyecto::query()
            ->whereIn('id', function ($query) {
                $query->select('estadoable_id')
                    ->from('estado_proyecto')
                    ->where('estadoable_type', DocumentoProyecto::class)
                    ->where('tipo_estado_id', TipoEstado::where('nombre', 'En revision')->first()->id)
                    ->where('es_actual', true);
            })
            ->when($this->search, fn($q) => $q->whereHas('proyecto', fn($q2) =>
                $q2->where('nombre_proyecto', 'like', '%' . $this->search . '%')
            ))
            ->with(['proyecto', 'estado.tipoestado'])
            ->paginate(10);

        $viewDocumento = $this->viewDocumentoId
            ? DocumentoProyecto::with(['proyecto'])->find($this->viewDocumentoId)
            : null;

        return view('livewire.proyectos.vinculacion.list-informes-solicitado', compact('records', 'viewDocumento'));
    }
}