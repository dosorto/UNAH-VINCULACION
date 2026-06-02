<?php

namespace App\Livewire\Proyectos\Vinculacion;

use App\Http\Controllers\Docente\VerificarConstancia;
use App\Models\Estado\TipoEstado;
use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\Categoria;
use App\Models\Proyecto\Modalidad;
use App\Models\Proyecto\Od;
use App\Models\Proyecto\Proyecto;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\ProyectoEstadoCambiado;
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

    public bool $viewModal = false;
    public ?int $viewProyectoId = null;

    public bool $rechazarModal = false;
    public ?int $rechazarProyectoId = null;
    public string $rechazarComentario = '';

    public bool $aprobarModal = false;
    public ?int $aprobarProyectoId = null;

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFilterOds(): void { $this->resetPage(); }
    public function updatingFilterCategoria(): void { $this->resetPage(); }
    public function updatingFilterModalidad(): void { $this->resetPage(); }
    public function updatingFilterCentroFacultad(): void { $this->resetPage(); }

    public function openView(int $id): void
    {
        $this->viewProyectoId = $id;
        $this->viewModal = true;
    }

    public function openRechazar(int $id): void
    {
        $this->rechazarProyectoId = $id;
        $this->rechazarComentario = '';
        $this->rechazarModal = true;
    }

    public function rechazar(): void
    {
        $this->validate(['rechazarComentario' => 'required|string']);

        $record = Proyecto::findOrFail($this->rechazarProyectoId);

        $record->firma_proyecto()->update([
            'estado_revision' => 'Pendiente',
            'firma_id'        => null,
            'sello_id'        => null,
            'fecha_firma'     => null,
        ]);
        $record->firma_revisor_vinculacion()->delete();
        $record->responsable_revision_id = null;
        $record->numero_dictamen  = null;
        $record->fecha_aprobacion = null;
        $record->fecha_registro   = null;
        $record->numero_libro     = null;
        $record->numero_tomo      = null;
        $record->numero_folio     = null;
        $record->save();
        $record->categoria()->detach();
        $record->ods()->detach();

        $record->estado_proyecto()->create([
            'empleado_id'   => Auth::user()->empleado->id,
            'tipo_estado_id' => TipoEstado::where('nombre', 'Subsanacion')->first()->id,
            'fecha'         => now(),
            'comentario'    => $this->rechazarComentario,
        ]);

        try {
            $coordinador = $record->coordinador_proyecto->first()?->empleado->user ?? null;
            if ($coordinador) {
                Mail::to($coordinador->email)->send(
                    new ProyectoEstadoCambiado($record, $coordinador, 'Proyecto Rechazado', $this->rechazarComentario, 'rechazo')
                );
            }
        } catch (\Exception $e) {
            Log::error('Error correo rechazo revisión final: ' . $e->getMessage());
        }

        $this->rechazarModal = false;
        $this->viewModal = false;
        Notification::make()->title('¡Realizado!')->body('Proyecto Rechazado')->info()->send();
    }

    public function openAprobar(int $id): void
    {
        $this->aprobarProyectoId = $id;
        $this->aprobarModal = true;
    }

    public function aprobar(): void
    {
        $proyecto = Proyecto::findOrFail($this->aprobarProyectoId);
        $cargoFirma = CargoFirma::join('tipo_cargo_firma', 'tipo_cargo_firma.id', '=', 'cargo_firma.tipo_cargo_firma_id')
            ->where('tipo_cargo_firma.nombre', 'Director Vinculacion')
            ->where('cargo_firma.descripcion', 'Proyecto')
            ->first();

        $proyecto->sincronizarFirmasDelFlujo();

        $nextEstadoId = $cargoFirma
            ? $proyecto->nextEstadoIdEnFlujo($cargoFirma->id)
            : null;

        $nextEstadoId ??= $proyecto->estadoFinalProcesoId(Proyecto::FLUJO_INSCRIPCION);

        $nextEstadoNombre = $nextEstadoId
            ? TipoEstado::find($nextEstadoId)?->nombre
            : 'En curso';

        $proyecto->estado_proyecto()->create([
            'empleado_id'   => Auth::user()->empleado->id,
            'tipo_estado_id' => $nextEstadoId,
            'fecha'         => now(),
            'comentario'    => 'El proyecto ha cambiado de estado a '.$nextEstadoNombre,
        ]);

        if ($cargoFirma) {
            $proyecto->guardarFirmaDeCargo($cargoFirma->id, auth()->user()->empleado, [
                'estado_revision' => 'Aprobado',
                'firma_id'        => auth()->user()?->empleado?->firma?->id,
                'sello_id'        => auth()->user()?->empleado?->sello?->id,
                'fecha_firma'     => now(),
            ]);
        }
        $proyecto->save();

        try {
            $coordinador = $proyecto->coordinador_proyecto->first()?->empleado->user ?? null;
            if ($coordinador) {
                Mail::to($coordinador->email)->send(
                    new ProyectoEstadoCambiado($proyecto, $coordinador, $nextEstadoNombre, 'Su proyecto fue aprobado y cambio a '.$nextEstadoNombre.'.', 'aprobación')
                );
            }
        } catch (\Exception $e) {
            Log::error('Error correo aprobación revisión final: ' . $e->getMessage());
        }

        VerificarConstancia::makeConstanciasProyecto($proyecto);

        $this->aprobarModal = false;
        $this->viewModal = false;
        Notification::make()->title('¡Realizado!')->body('Proyecto Aprobado correctamente')->info()->send();
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
            ->when($this->search, fn($q) => $q->where(fn($q2) => $q2
                ->where('proyecto.nombre_proyecto', 'like', '%' . $this->search . '%')
                ->orWhere('proyecto.codigo_proyecto', 'like', '%' . $this->search . '%')
            ))
            ->when($this->filterOds, fn($q) => $q->whereHas('ods', fn($q2) => $q2->where('ods.id', $this->filterOds)))
            ->when($this->filterCategoria, fn($q) => $q->whereHas('categoria', fn($q2) => $q2->where('categorias.id', $this->filterCategoria)))
            ->when($this->filterModalidad, fn($q) => $q->where('proyecto.modalidad_id', $this->filterModalidad))
            ->when($this->filterCentroFacultad, fn($q) => $q->where('proyecto_centro_facultad.centro_facultad_id', $this->filterCentroFacultad))
            ->distinct();
    }

    public function render(): View
    {
        $records = $this->recordsQuery()
            ->paginate(10);

        $viewProyecto = $this->viewProyectoId
            ? Proyecto::with(['aporteInstitucional', 'presupuesto', 'ods', 'metasContribuye'])->find($this->viewProyectoId)
            : null;

        $odsList = Od::orderBy('nombre')->pluck('nombre', 'id');
        $categorias = Categoria::orderBy('nombre')->pluck('nombre', 'id');
        $modalidades = Modalidad::orderBy('nombre')->pluck('nombre', 'id');
        $centros = FacultadCentro::orderBy('nombre')->pluck('nombre', 'id');

        return view('livewire.proyectos.vinculacion.list-proyecto-revision-final', compact(
            'records',
            'viewProyecto',
            'odsList',
            'categorias',
            'modalidades',
            'centros'
        ));
    }
}
