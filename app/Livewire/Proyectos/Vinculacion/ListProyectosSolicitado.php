<?php

namespace App\Livewire\Proyectos\Vinculacion;

use App\Models\Estado\TipoEstado;
use App\Models\Proyecto\Modalidad;
use App\Models\Proyecto\Proyecto;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Support\AdminCsv;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ListProyectosSolicitado extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterModalidad = '';

    public ?int $filterCentroFacultad = null;

    public function updatingSearch(): void
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

    public function exportExcel()
    {
        return AdminCsv::download('revision-dvus-'.now()->format('Y-m-d').'.csv', [
            'Codigo',
            'Nombre del Proyecto',
            'Modalidad',
            'Centro/Facultad',
            'Fecha Inicio',
        ], function () {
            foreach ($this->recordsQuery()->with(['modalidad', 'facultades_centros'])->orderBy('proyecto.nombre_proyecto')->lazy() as $proyecto) {
                yield [
                    $proyecto->codigo_proyecto,
                    $proyecto->nombre_proyecto,
                    $proyecto->modalidad?->nombre,
                    $proyecto->facultades_centros->pluck('nombre')->implode(', '),
                    $proyecto->fecha_inicio,
                ];
            }
        });
    }

    private function recordsQuery()
    {
        return Proyecto::query()
            ->whereIn('proyecto.id', function ($query) {
                $query->select('estadoable_id')
                    ->from('estado_proyecto')
                    ->where('estadoable_type', Proyecto::class)
                    ->where('tipo_estado_id', TipoEstado::where('nombre', 'En revision')->first()->id)
                    ->where('es_actual', true);
            })
            ->leftJoin('proyecto_centro_facultad', 'proyecto_centro_facultad.proyecto_id', '=', 'proyecto.id')
            ->leftJoin('proyecto_depto_ac', 'proyecto_depto_ac.proyecto_id', '=', 'proyecto.id')
            ->select('proyecto.*')
            ->when($this->search, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('proyecto.nombre_proyecto', 'like', '%'.$this->search.'%')
                ->orWhere('proyecto.codigo_proyecto', 'like', '%'.$this->search.'%')
            ))
            ->when($this->filterModalidad, fn ($q) => $q->where('proyecto.modalidad_id', $this->filterModalidad))
            ->when($this->filterCentroFacultad, fn ($q) => $q->where('proyecto_centro_facultad.centro_facultad_id', $this->filterCentroFacultad))
            ->distinct();
    }

    public function render(): View
    {
        $records = $this->recordsQuery()
            ->paginate(10);

        $modalidades = Modalidad::orderBy('nombre')->pluck('nombre', 'id');
        $centros = FacultadCentro::orderBy('nombre')->pluck('nombre', 'id');

        return view('livewire.proyectos.vinculacion.list-proyectos-vinculacion-solicitados', compact('records', 'modalidades', 'centros'));
    }
}
