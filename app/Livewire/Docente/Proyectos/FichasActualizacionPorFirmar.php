<?php

namespace App\Livewire\Docente\Proyectos;

use App\Models\Estado\TipoEstado;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\FichaActualizacion;
use App\Models\Proyecto\FirmaProyecto;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class FichasActualizacionPorFirmar extends Component
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
        $fichaActualizacion = $firma->ficha_actualizacion;

        $firma->update([
            'estado_revision' => 'Pendiente',
            'firma_id'        => null,
            'sello_id'        => null,
            'fecha_firma'     => null,
        ]);

        $fichaActualizacion->estado_proyecto()->create([
            'empleado_id'    => $this->docente->id,
            'tipo_estado_id' => TipoEstado::where('nombre', 'Rechazado')->first()->id,
            'fecha'          => now(),
            'comentario'     => $this->rechazarComentario,
        ]);

        $resultadoCancelacion = $fichaActualizacion->cancelarSolicitudesPorRechazo();

        $mensaje = 'Ficha de Actualización Rechazada';
        if ($resultadoCancelacion['canceladas']) {
            $mensaje .= '. ' . $resultadoCancelacion['mensaje'];
        }

        $this->rechazarModal = false;
        $this->rechazarId = null;
        $this->rechazarComentario = '';
        $this->viewModal = false;

        Notification::make()->title('¡Realizado!')->body($mensaje)->info()->send();
    }

    public function aprobar(int $id): void
    {
        $firma = FirmaProyecto::findOrFail($id);
        $fichaActualizacion = $firma->ficha_actualizacion;

        $firma->update([
            'estado_revision' => 'Aprobado',
            'firma_id'        => auth()->user()?->empleado?->firma?->id,
            'sello_id'        => auth()->user()?->empleado?->sello?->id,
            'fecha_firma'     => now(),
        ]);

        $fichaActualizacion->estado_proyecto()->create([
            'empleado_id'    => auth()->user()->empleado->id,
            'tipo_estado_id' => $firma->cargo_firma->estado_siguiente_id,
            'fecha'          => now(),
            'comentario'     => 'Ficha de actualización firmada y aprobada',
        ]);

        $this->viewModal = false;
        $this->viewId = null;

        Notification::make()->title('¡Realizado!')->body('Ficha de Actualización Aprobada correctamente')->info()->send();
    }

    public function render(): View
    {
        $records = $this->docente->firmaProyecto()
            ->where('firmable_type', FichaActualizacion::class)
            ->whereIn('id', $this->docente->getIdValidos())
            ->with(['cargo_firma.tipoCargoFirma'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $viewFirma = $this->viewId ? FirmaProyecto::find($this->viewId) : null;
        $viewFicha = null;
        $viewProyecto = null;
        if ($viewFirma) {
            $viewFicha = $viewFirma->ficha_actualizacion;
            $viewProyecto = $viewFicha?->proyecto?->load(['aporteInstitucional', 'presupuesto', 'ods', 'metasContribuye']);
        }

        return view('livewire.docente.proyectos.fichas-actualizacion-por-firmar', compact('records', 'viewFirma', 'viewFicha', 'viewProyecto'));
    }
}