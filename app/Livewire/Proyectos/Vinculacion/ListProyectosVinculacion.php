<?php

namespace App\Livewire\Proyectos\Vinculacion;

use App\Models\Estado\TipoEstado;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\Categoria;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\Proyecto\Modalidad;
use App\Models\Proyecto\Od;
use App\Models\Proyecto\Proyecto;
use App\Support\AdminCsv;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Livewire\WithPagination;

class ListProyectosVinculacion extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterEstado = '';
    public string $filterCategoria = '';
    public string $filterModalidad = '';
    public string $filterOds = '';
    public string $filterFechaInicio = '';
    public string $filterFechaFin = '';
    public ?int $filterCentroFacultad = null;
    public ?int $filterDepartamento = null;

    public bool $viewModal = false;
    public ?int $viewProyectoId = null;

    public bool $firmasModal = false;
    public ?int $firmasProyectoId = null;
    public ?int $firmas_jefe_id = null;
    public ?int $firmas_director_id = null;
    public ?int $firmas_enlace_id = null;

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFilterCentroFacultad(): void { $this->filterDepartamento = null; }

    public function openView(int $id): void
    {
        $this->viewProyectoId = $id;
        $this->viewModal = true;
    }

    public function openFirmas(int $id): void
    {
        $this->firmasProyectoId = $id;

        $get = fn(string $cargo) => FirmaProyecto::join('cargo_firma', 'firma_proyecto.cargo_firma_id', '=', 'cargo_firma.id')
            ->join('tipo_cargo_firma', 'cargo_firma.tipo_cargo_firma_id', '=', 'tipo_cargo_firma.id')
            ->where('firma_proyecto.firmable_type', Proyecto::class)
            ->where('firma_proyecto.firmable_id', $id)
            ->where('tipo_cargo_firma.nombre', $cargo)
            ->where('cargo_firma.descripcion', 'Proyecto')
            ->select('firma_proyecto.*')
            ->first();

        $this->firmas_jefe_id    = $get('Jefe Departamento')?->empleado_id;
        $this->firmas_director_id = $get('Director centro')?->empleado_id;
        $this->firmas_enlace_id  = $get('Enlace Vinculacion')?->empleado_id;
        $this->firmasModal       = true;
    }

    public function saveFirmas(): void
    {
        $this->validate([
            'firmas_jefe_id'     => 'required|integer',
            'firmas_director_id' => 'required|integer',
            'firmas_enlace_id'   => 'required|integer',
        ]);

        $actualizar = function (string $cargo, ?int $nuevoId) {
            if (!$nuevoId) return;
            $firma = FirmaProyecto::join('cargo_firma', 'firma_proyecto.cargo_firma_id', '=', 'cargo_firma.id')
                ->join('tipo_cargo_firma', 'cargo_firma.tipo_cargo_firma_id', '=', 'tipo_cargo_firma.id')
                ->where('firma_proyecto.firmable_type', Proyecto::class)
                ->where('firma_proyecto.firmable_id', $this->firmasProyectoId)
                ->where('tipo_cargo_firma.nombre', $cargo)
                ->where('cargo_firma.descripcion', 'Proyecto')
                ->select('firma_proyecto.*')
                ->first();
            if ($firma) {
                FirmaProyecto::where('id', $firma->id)->update(['empleado_id' => $nuevoId]);
            }
        };

        $actualizar('Jefe Departamento', $this->firmas_jefe_id);
        $actualizar('Director centro', $this->firmas_director_id);
        $actualizar('Enlace Vinculacion', $this->firmas_enlace_id);

        $this->firmasModal = false;
        Notification::make()->title('Firmas actualizadas')->body('Los responsables de firma fueron actualizados correctamente.')->success()->send();
    }

    public function exportExcel()
    {
        return AdminCsv::download('historial-vinculacion-' . now()->format('Y-m-d') . '.csv', [
            'Codigo',
            'Numero Dictamen',
            'Nombre del Proyecto',
            'Estado',
            'Fecha Inicio',
        ], function () {
            foreach ($this->recordsQuery()->with(['estadoActual.tipoestado'])->orderBy('proyecto.nombre_proyecto')->lazy() as $proyecto) {
                yield [
                    $proyecto->codigo_proyecto,
                    $proyecto->numero_dictamen,
                    $proyecto->nombre_proyecto,
                    $proyecto->estadoActual?->tipoestado?->nombre,
                    $proyecto->fecha_inicio,
                ];
            }
        });
    }

    private function recordsQuery()
    {
        return Proyecto::query()
            ->whereNotIn('proyecto.id', function ($query) {
                $query->select('estadoable_id')
                    ->from('estado_proyecto')
                    ->where('estadoable_type', Proyecto::class)
                    ->whereIn('tipo_estado_id', TipoEstado::whereIn('nombre', ['Borrador'])->pluck('id')->toArray())
                    ->where('es_actual', true);
            })
            ->leftJoin('proyecto_centro_facultad', 'proyecto_centro_facultad.proyecto_id', '=', 'proyecto.id')
            ->leftJoin('proyecto_depto_ac', 'proyecto_depto_ac.proyecto_id', '=', 'proyecto.id')
            ->leftJoin('estado_proyecto', function ($join) {
                $join->on('estado_proyecto.estadoable_id', '=', 'proyecto.id')
                    ->where('estado_proyecto.estadoable_type', Proyecto::class)
                    ->where('estado_proyecto.es_actual', true);
            })
            ->leftJoin('tipo_estado', 'estado_proyecto.tipo_estado_id', '=', 'tipo_estado.id')
            ->select('proyecto.*')
            ->when($this->search, fn($q) => $q->where(fn($q2) => $q2
                ->where('proyecto.nombre_proyecto', 'like', '%' . $this->search . '%')
                ->orWhere('proyecto.codigo_proyecto', 'like', '%' . $this->search . '%')
                ->orWhere('proyecto.numero_dictamen', 'like', '%' . $this->search . '%')
            ))
            ->when($this->filterEstado, fn($q) => $q->where('tipo_estado.id', $this->filterEstado))
            ->when($this->filterModalidad, fn($q) => $q->where('proyecto.modalidad_id', $this->filterModalidad))
            ->when($this->filterCategoria, fn($q) => $q->whereHas('categoria', fn($q2) => $q2->where('categorias.id', $this->filterCategoria)))
            ->when($this->filterOds, fn($q) => $q->whereHas('ods', fn($q2) => $q2->where('ods.id', $this->filterOds)))
            ->when($this->filterFechaInicio, fn($q) => $q->whereDate('proyecto.fecha_inicio', '>=', $this->filterFechaInicio))
            ->when($this->filterFechaFin, fn($q) => $q->whereDate('proyecto.fecha_finalizacion', '<=', $this->filterFechaFin))
            ->when($this->filterCentroFacultad, fn($q) => $q->where('proyecto_centro_facultad.centro_facultad_id', $this->filterCentroFacultad))
            ->when($this->filterDepartamento, fn($q) => $q->where('proyecto_depto_ac.departamento_academico_id', $this->filterDepartamento))
            ->distinct();
    }

    public function render(): View
    {
        $records = $this->recordsQuery()
            ->paginate(10);

        $viewProyecto = $this->viewProyectoId
            ? Proyecto::with(['aporteInstitucional', 'presupuesto', 'ods', 'metasContribuye'])->find($this->viewProyectoId)
            : null;

        $estadosTipo     = TipoEstado::orderBy('nombre')->pluck('nombre', 'id');
        $centros         = \App\Models\UnidadAcademica\FacultadCentro::orderBy('nombre')->pluck('nombre', 'id');
        $departamentos   = $this->filterCentroFacultad
            ? \App\Models\UnidadAcademica\DepartamentoAcademico::where('centro_facultad_id', $this->filterCentroFacultad)->orderBy('nombre')->pluck('nombre', 'id')
            : collect();
        $empleados       = Empleado::orderBy('nombre_completo')->pluck('nombre_completo', 'id');
        $categorias      = Categoria::orderBy('nombre')->pluck('nombre', 'id');
        $modalidades     = Modalidad::orderBy('nombre')->pluck('nombre', 'id');
        $odsList         = Od::orderBy('nombre')->pluck('nombre', 'id');

        return view('livewire.proyectos.vinculacion.list-proyectos-vinculacion', compact(
            'records', 'viewProyecto', 'estadosTipo', 'centros', 'departamentos',
            'empleados', 'categorias', 'modalidades', 'odsList'
        ));
    }
}
