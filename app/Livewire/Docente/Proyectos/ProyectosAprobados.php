<?php

namespace App\Livewire\Docente\Proyectos;

use App\Models\Personal\Empleado;
use App\Models\Proyecto\FirmaProyecto;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ProyectosAprobados extends Component
{
    use WithPagination;

    public Empleado $docente;

    public function mount(): void
    {
        $this->docente = auth()->user()->empleado;
    }

    public function render(): View
    {
        $records = FirmaProyecto::with(['proyecto', 'cargo_firma.tipoCargoFirma'])
            ->where('empleado_id', $this->docente->id)
            ->where('estado_revision', 'Aprobado')
            ->paginate(10);

        return view('livewire.docente.proyectos.proyectos-aprobados', compact('records'));
    }
}