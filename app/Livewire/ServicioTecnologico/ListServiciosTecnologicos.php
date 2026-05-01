<?php

namespace App\Livewire\ServicioTecnologico;

use App\Models\ServicioInfraestructura\ServicioTecnologico;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ListServiciosTecnologicos extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $records = ServicioTecnologico::query()
            ->with(['modalidad', 'centrosFacultades', 'empleados'])
            ->withCount('actividades')
            ->when($this->search, fn ($query) => $query->where('nombre_accion', 'like', '%' . $this->search . '%'))
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('livewire.ServicioTecnologico.list-servicios-tecnologicos', compact('records'));
    }
}
