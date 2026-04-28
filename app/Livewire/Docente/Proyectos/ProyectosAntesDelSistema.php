<?php

namespace App\Livewire\Docente\Proyectos;

use App\Models\Proyecto\Proyecto;
use App\Models\Estado\TipoEstado;
use App\Models\Personal\Empleado;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ProyectosAntesDelSistema extends Component
{
    use WithPagination;

    public Empleado $docente;
    public string $search = '';
    public bool $viewModal = false;
    public ?int $viewId = null;

    public function mount($docente = null): void
    {
        $this->docente = $docente ?? Auth::user()->empleado;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
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

    public function render(): View
    {
        $empleadoId = $this->docente->id;

        $records = Proyecto::query()
            ->whereHas('estado_proyecto', function (Builder $query) {
                $tipoEstados = TipoEstado::whereIn('nombre', ['PendienteInformacion', 'Finalizado'])->pluck('id');
                if ($tipoEstados->isNotEmpty()) {
                    $query->whereIn('tipo_estado_id', $tipoEstados)->where('es_actual', true);
                }
            })
            ->whereHas('docentes_proyecto', function (Builder $query) use ($empleadoId) {
                $query->where('empleado_id', $empleadoId);
            })
            ->with(['tipo_estado', 'docentes_proyecto.empleado'])
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('codigo_proyecto', 'like', '%' . $this->search . '%')
                  ->orWhere('nombre_proyecto', 'like', '%' . $this->search . '%')
                  ->orWhere('numero_dictamen', 'like', '%' . $this->search . '%');
            }))
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $viewProyecto = $this->viewId
            ? Proyecto::with(['aporteInstitucional', 'presupuesto', 'ods', 'metasContribuye'])->find($this->viewId)
            : null;

        return view('livewire.docente.proyectos.proyectos-antes-del-sistema', compact('records', 'viewProyecto'));
    }
}