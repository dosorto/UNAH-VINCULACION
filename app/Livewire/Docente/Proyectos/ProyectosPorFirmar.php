<?php

namespace App\Livewire\Docente\Proyectos;

use App\Models\Estado\TipoEstado;
use App\Models\Personal\Empleado;
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
        $this->rechazarId = $id;
        $this->rechazarComentario = '';
        $this->rechazarModal = true;
    }

    public function rechazar(): void
    {
        $this->validate(['rechazarComentario' => 'required|string']);

        $firma = FirmaProyecto::findOrFail($this->rechazarId);

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

        Notification::make()->title('¡Realizado!')->body('Proyecto Rechazado')->info()->send();
    }

    public function aprobar(int $id): void
    {
        $firma = FirmaProyecto::findOrFail($id);

        if ($firma->firmable_type == Proyecto::class) {
            $firma->update([
                'estado_revision' => 'Aprobado',
                'firma_id'        => auth()->user()?->empleado?->firma?->id,
                'sello_id'        => auth()->user()?->empleado?->sello?->id,
                'fecha_firma'     => now(),
            ]);

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

    public function render(): View
    {
        $records = $this->docente->firmaProyecto()
            ->where('firmable_type', '!=', FichaActualizacion::class)
            ->whereIn('id', $this->docente->getIdValidos())
            ->with(['proyecto', 'cargo_firma.tipoCargoFirma'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $viewFirma = $this->viewId ? FirmaProyecto::find($this->viewId) : null;
        $viewProyecto = null;
        if ($viewFirma) {
            $viewProyecto = $viewFirma->firmable_type == Proyecto::class
                ? $viewFirma->proyecto?->load(['aporteInstitucional', 'presupuesto', 'ods', 'metasContribuye'])
                : $viewFirma->documento_proyecto?->proyecto?->load(['aporteInstitucional', 'presupuesto', 'ods', 'metasContribuye']);
        }

        return view('livewire.docente.proyectos.proyectos-por-firmar', compact('records', 'viewFirma', 'viewProyecto'));
    }
}