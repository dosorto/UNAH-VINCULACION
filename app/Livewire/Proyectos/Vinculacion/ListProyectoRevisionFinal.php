<?php

namespace App\Livewire\Proyectos\Vinculacion;

use App\Models\Estado\TipoEstado;
use App\Models\Proyecto\Categoria;
use App\Models\Proyecto\Modalidad;
use App\Models\Proyecto\Od;
use App\Models\Proyecto\Proyecto;
use App\Models\UnidadAcademica\FacultadCentro;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ListProyectoRevisionFinal extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterOds = '';

    public string $filterCategoria = '';

    public string $filterModalidad = '';

    public ?int $filterCentroFacultad = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterOds(): void
    {
        $this->resetPage();
    }

    public function updatingFilterCategoria(): void
    {
        $this->resetPage();
    }

    public function updatingFilterModalidad(): void
    {
        $this->resetPage();
    }

    public function updatingFilterCentroFacultad(): void
    {
        $this->resetPage();
    }

    private function recordsQuery()
    {
        return Proyecto::query()
            ->whereIn('proyecto.id', function ($query) {
                $query->select('estadoable_id')
                    ->from('estado_proyecto')
                    ->where('estadoable_type', Proyecto::class)
                    ->where('tipo_estado_id', TipoEstado::where('nombre', 'En revision final')->first()->id)
                    ->where('es_actual', true);
            })
            ->leftJoin('proyecto_centro_facultad', 'proyecto_centro_facultad.proyecto_id', '=', 'proyecto.id')
            ->leftJoin('proyecto_depto_ac', 'proyecto_depto_ac.proyecto_id', '=', 'proyecto.id')
            ->select('proyecto.*')
            ->when($this->search, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('proyecto.nombre_proyecto', 'like', '%'.$this->search.'%')
                ->orWhere('proyecto.codigo_proyecto', 'like', '%'.$this->search.'%')
            ))
            ->when($this->filterOds, fn ($q) => $q->whereHas('ods', fn ($q2) => $q2->where('ods.id', $this->filterOds)))
            ->when($this->filterCategoria, fn ($q) => $q->whereHas('categoria', fn ($q2) => $q2->where('categorias.id', $this->filterCategoria)))
            ->when($this->filterModalidad, fn ($q) => $q->where('proyecto.modalidad_id', $this->filterModalidad))
            ->when($this->filterCentroFacultad, fn ($q) => $q->where('proyecto_centro_facultad.centro_facultad_id', $this->filterCentroFacultad))
            ->distinct();
    }

    public function render(): View
    {
        $records = $this->recordsQuery()
            ->paginate(10);

        $odsList = Od::orderBy('nombre')->pluck('nombre', 'id');
        $categorias = Categoria::orderBy('nombre')->pluck('nombre', 'id');
        $modalidades = Modalidad::orderBy('nombre')->pluck('nombre', 'id');
        $centros = FacultadCentro::orderBy('nombre')->pluck('nombre', 'id');

        return view('livewire.proyectos.vinculacion.list-proyecto-revision-final', compact(
            'records',
            'odsList',
            'categorias',
            'modalidades',
            'centros'
        ));
    }
}
