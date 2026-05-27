<?php

namespace App\Livewire\Docente\Proyectos;

use App\Http\Controllers\Docente\VerificarConstancia;
use App\Models\Estado\TipoEstado;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\Proyecto;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ProyectosDocenteList extends Component
{
    use WithPagination;
    use WithFileUploads;

    public Empleado $docente;

    public string $search = '';
    public string $filterCategoria = '';
    public string $filterRol = '';
    public string $filterEstado = '';

    public bool $informeIntermedioModal = false;
    public ?int $informeIntermedioProyectoId = null;
    public $informeIntermedioFile = null;

    public bool $informeFinalModal = false;
    public ?int $informeFinalProyectoId = null;
    public $informeFinalFile = null;

    public bool $deleteModal = false;
    public ?int $deleteProyectoId = null;

    public function mount($docente = null): void
    {
        $this->docente = $docente ?? auth()->user()->empleado;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openSubirIntermedio(int $proyectoId): void
    {
        $this->informeIntermedioProyectoId = $proyectoId;
        $this->informeIntermedioFile = null;
        $this->informeIntermedioModal = true;
    }

    public function subirInformeIntermedio(): void
    {
        $this->validate(['informeIntermedioFile' => 'required|file|mimes:pdf|max:10240']);

        $proyecto = Proyecto::findOrFail($this->informeIntermedioProyectoId);
        $path = $this->informeIntermedioFile->store('documentos', 'public');

        try {
            $proyecto->registrarDocumentoDesdeFlujo('Informe Intermedio', $path, auth()->user()->empleado);
        } catch (\Throwable $e) {
            Notification::make()->title('No se pudo enviar el informe')->body($e->getMessage())->danger()->send();
            return;
        }

        $this->informeIntermedioModal = false;
        $this->informeIntermedioFile = null;
        Notification::make()->title('Informe subido')->body('El informe intermedio fue enviado correctamente.')->success()->send();
    }

    public function openSubirFinal(int $proyectoId): void
    {
        $this->informeFinalProyectoId = $proyectoId;
        $this->informeFinalFile = null;
        $this->informeFinalModal = true;
    }

    public function subirInformeFinal(): void
    {
        $this->validate(['informeFinalFile' => 'required|file|mimes:pdf|max:10240']);

        $proyecto = Proyecto::findOrFail($this->informeFinalProyectoId);
        $path = $this->informeFinalFile->store('documentos', 'public');

        try {
            $proyecto->registrarDocumentoDesdeFlujo('Informe Final', $path, auth()->user()->empleado);
        } catch (\Throwable $e) {
            Notification::make()->title('No se pudo enviar el informe')->body($e->getMessage())->danger()->send();
            return;
        }

        $this->informeFinalModal = false;
        $this->informeFinalFile = null;
        Notification::make()->title('Informe subido')->body('El informe final fue enviado correctamente.')->success()->send();
    }

    public function constanciaInscripcion(int $proyectoId): mixed
    {
        $proyecto = Proyecto::findOrFail($proyectoId);
        $empleadoProyecto = $proyecto->docentes_proyecto()->where('empleado_id', $this->docente->id)->first();
        return VerificarConstancia::CrearPdfInscripcion($empleadoProyecto);
    }

    public function constanciaFinalizacion(int $proyectoId): mixed
    {
        $proyecto = Proyecto::findOrFail($proyectoId);
        $empleadoProyecto = $proyecto->docentes_proyecto()->where('empleado_id', $this->docente->id)->first();
        return VerificarConstancia::CrearPdfFinalizacion($empleadoProyecto);
    }

    public function openDelete(int $proyectoId): void
    {
        $this->deleteProyectoId = $proyectoId;
        $this->deleteModal = true;
    }

    public function deleteProyecto(): void
    {
        $proyecto = Proyecto::findOrFail($this->deleteProyectoId);
        $proyecto->delete();
        $this->deleteModal = false;
        $this->deleteProyectoId = null;
        Notification::make()->title('Proyecto eliminado')->body('El proyecto fue eliminado correctamente.')->success()->send();
    }

    public function render(): View
    {
        $records = Proyecto::query()
            ->join('empleado_proyecto', 'empleado_proyecto.proyecto_id', '=', 'proyecto.id')
            ->join('estado_proyecto', function ($join) {
                $join->on('estado_proyecto.estadoable_id', '=', 'proyecto.id')
                    ->where('estado_proyecto.estadoable_type', '=', 'App\Models\Proyecto\Proyecto')
                    ->where('estado_proyecto.es_actual', '=', true);
            })
            ->join('tipo_estado', 'estado_proyecto.tipo_estado_id', '=', 'tipo_estado.id')
            ->select('proyecto.*')
            ->where('empleado_proyecto.empleado_id', $this->docente->id)
            ->where('tipo_estado.nombre', '!=', 'PendienteInformacion')
            ->whereNotExists(function ($query) {
                $query->select('*')
                    ->from('empleado_codigos_investigacion')
                    ->whereRaw('empleado_codigos_investigacion.codigo_proyecto = proyecto.codigo_proyecto')
                    ->where('tipo_estado.nombre', '=', 'Finalizado');
            })
            ->when($this->search, fn($q) => $q->where(fn($q2) => $q2
                ->where('proyecto.nombre_proyecto', 'like', '%' . $this->search . '%')
                ->orWhere('proyecto.codigo_proyecto', 'like', '%' . $this->search . '%')
                ->orWhere('proyecto.numero_dictamen', 'like', '%' . $this->search . '%')
            ))
            ->when($this->filterCategoria !== '', fn($q) => $q->whereHas(
                'categoria',
                fn($q2) => $q2->where('categorias.id', $this->filterCategoria)
            ))
            ->when($this->filterRol, fn($q) => $q->where('empleado_proyecto.rol', $this->filterRol))
            ->when($this->filterEstado, fn($q) => $q->where('tipo_estado.id', $this->filterEstado))
            ->distinct()
            ->paginate(10);

        $categorias = \App\Models\Proyecto\Categoria::orderBy('nombre')->pluck('nombre', 'id');
        $estadosTipo = TipoEstado::orderBy('nombre')->pluck('nombre', 'id');

        return view('livewire.docente.proyectos.proyectos-docente-list', compact('records', 'categorias', 'estadosTipo'));
    }
}
