<?php

namespace App\Livewire\CVinculacion\Proyectos;

use App\Models\Proyecto\Proyecto;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class CVinculacionProyectosList extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $records = Proyecto::when($this->search, fn($q) =>
                $q->where('nombre_proyecto', 'like', '%'.$this->search.'%')
            )
            ->latest()
            ->paginate(15);

        return view('livewire.c-vinculacion.proyectos.c-vinculacion-proyectos-list', compact('records'));
    }
}