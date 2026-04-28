<?php

namespace App\Livewire\Docente\Proyectos;

use App\Http\Controllers\Docente\VerificarConstancia;
use App\Models\Proyecto\FichaActualizacion;
use App\Models\Personal\Empleado;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class FichasActualizacionDocente extends Component
{
    use WithPagination;

    public Empleado $docente;
    public bool $viewModal = false;
    public ?int $viewId = null;
    public bool $deleteModal = false;
    public ?int $deleteId = null;

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

    public function openDelete(int $id): void
    {
        $this->deleteId = $id;
        $this->deleteModal = true;
    }

    public function delete(): void
    {
        $ficha = FichaActualizacion::findOrFail($this->deleteId);
        $resultado = $ficha->eliminarFichaSiEsSeguro();

        if ($resultado['eliminada']) {
            Notification::make()->title('¡Eliminada!')->body($resultado['mensaje'])->success()->send();
        } else {
            Notification::make()->title('Error')->body($resultado['razon'])->danger()->send();
        }

        $this->deleteModal = false;
        $this->deleteId = null;
    }

    public function constancia(int $id): mixed
    {
        $ficha = FichaActualizacion::findOrFail($id);
        return VerificarConstancia::CrearPdfActualizacion(
            $ficha->equipoEjecutor()->where('empleado_id', $this->docente->id)->first()
        );
    }

    public function render(): View
    {
        $records = FichaActualizacion::query()
            ->whereHas('proyecto.coordinador_proyecto', function (Builder $query) {
                $query->where('empleado_id', auth()->user()->empleado->id);
            })
            ->with(['proyecto.coordinador_proyecto.empleado'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $viewFicha = $this->viewId
            ? FichaActualizacion::with(['proyecto' => fn($q) => $q->with(['aporteInstitucional', 'presupuesto', 'ods', 'metasContribuye'])])->find($this->viewId)
            : null;

        $deleteFicha = $this->deleteId ? FichaActualizacion::find($this->deleteId) : null;

        return view('livewire.docente.proyectos.fichas-actualizacion-docente', compact('records', 'viewFicha', 'deleteFicha'));
    }
}