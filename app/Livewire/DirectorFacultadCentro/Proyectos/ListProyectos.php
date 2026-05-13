<?php

namespace App\Livewire\DirectorFacultadCentro\Proyectos;

use App\Models\Estado\TipoEstado;
use App\Models\Proyecto\Proyecto;
use App\Models\UnidadAcademica\DepartamentoAcademico;
use App\Models\UnidadAcademica\FacultadCentro;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ListProyectos extends Component
{
    use WithPagination;

    public FacultadCentro $facultadCentro;
    public string $titulo;
    public string $descripcion;

    public string $search = '';
    public array $filterEstados = [];
    public ?int $filterDepartamento = null;
    public bool $viewModal = false;
    public ?int $viewId = null;

    public function mount($facultadCentro = null): void
    {
        $user = auth()->user();
        $facultadCentro = $user->empleado->centro_facultad;

        if ($user->hasPermissionTo('director.proyectos') && !$user->hasPermissionTo('proyectos.historial')) {
            if ($user->empleado->centro_facultad_id !== $facultadCentro->id) {
                abort(403);
            }
        }

        $this->facultadCentro = $facultadCentro;
        $this->titulo = 'Proyectos de Vinculación de ' . $this->facultadCentro->nombre;
        $this->descripcion = 'Listado de proyectos de vinculación de la Facultad/Centro';
    }

    public function updatingSearch(): void { $this->resetPage(); }

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
        $excludedIds = \DB::table('estado_proyecto')
            ->where('estadoable_type', Proyecto::class)
            ->whereIn('tipo_estado_id', TipoEstado::where('nombre', 'Borrador')->pluck('id'))
            ->where('es_actual', true)
            ->pluck('estadoable_id');

        $records = Proyecto::query()
            ->whereNotIn('proyecto.id', $excludedIds)
            ->leftJoin('proyecto_centro_facultad', 'proyecto_centro_facultad.proyecto_id', '=', 'proyecto.id')
            ->where('proyecto_centro_facultad.centro_facultad_id', $this->facultadCentro->id)
            ->select('proyecto.*')
            ->distinct()
            ->with(['tipo_estado', 'departamentos_academicos', 'facultades_centros'])
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('proyecto.nombre_proyecto', 'like', '%' . $this->search . '%')
                  ->orWhere('proyecto.codigo_proyecto', 'like', '%' . $this->search . '%')
                  ->orWhere('proyecto.numero_dictamen', 'like', '%' . $this->search . '%');
            }))
            ->when(!empty($this->filterEstados), fn($q) =>
                $q->whereIn('proyecto.id', function ($sub) {
                    $sub->select('estadoable_id')
                        ->from('estado_proyecto')
                        ->whereIn('tipo_estado_id', $this->filterEstados)
                        ->where('es_actual', true);
                })
            )
            ->when($this->filterDepartamento, fn($q) =>
                $q->leftJoin('proyecto_depto_ac', 'proyecto_depto_ac.proyecto_id', '=', 'proyecto.id')
                  ->where('proyecto_depto_ac.departamento_academico_id', $this->filterDepartamento)
            )
            ->paginate(10);

        $viewProyecto = $this->viewId
            ? Proyecto::with(['aporteInstitucional', 'presupuesto', 'ods', 'metasContribuye'])->find($this->viewId)
            : null;

        $tiposEstado = TipoEstado::orderBy('nombre')->pluck('nombre', 'id');
        $departamentos = DepartamentoAcademico::where('centro_facultad_id', $this->facultadCentro->id)->orderBy('nombre')->pluck('nombre', 'id');

        return view('livewire.director-facultad-centro.proyectos.list-proyectos', compact('records', 'viewProyecto', 'tiposEstado', 'departamentos'));
    }
}