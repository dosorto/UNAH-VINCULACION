<?php

namespace App\Livewire\Proyectos\Vinculacion;

use App\Models\Estado\TipoEstado;
use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\Proyecto;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\ProyectoEstadoCambiado;
use Livewire\Component;
use Livewire\WithPagination;

class ListProyectosSolicitado extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $viewModal = false;
    public ?int $viewProyectoId = null;

    public bool $rechazarModal = false;
    public ?int $rechazarProyectoId = null;
    public string $rechazarComentario = '';

    public bool $aprobarModal = false;
    public ?int $aprobarProyectoId = null;
    public string $aprobar_codigo = '';
    public string $aprobar_dictamen = '';

    public function updatingSearch(): void { $this->resetPage(); }

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

        $record->estado_proyecto()->create([
            'empleado_id'   => Auth::user()->empleado->id,
            'tipo_estado_id' => TipoEstado::where('nombre', 'Subsanacion')->first()->id,
            'fecha'         => now(),
            'comentario'    => $this->rechazarComentario,
        ]);

        try {
            $record->refresh();
            $record->load(['coordinador_proyecto.empleado.user']);
            if ($record->coordinador?->user) {
                Mail::to($record->coordinador->user->email)->send(
                    new ProyectoEstadoCambiado($record, $record->coordinador->user, 'Subsanación', $this->rechazarComentario, 'rechazo')
                );
            }
        } catch (\Exception $e) {
            Log::error('Error correo rechazo proyecto: ' . $e->getMessage());
        }

        $this->rechazarModal = false;
        $this->viewModal = false;
        Notification::make()->title('¡Realizado!')->body('Proyecto Rechazado')->warning()->send();
    }

    public function openAprobar(int $id): void
    {
        $proyecto = Proyecto::findOrFail($id);
        $siglas   = $proyecto->coordinador?->centro_facultad?->siglas ?? 'XX';
        $year     = date('Y');
        $nextId   = str_pad($id + 1, 3, '0', STR_PAD_LEFT);

        $this->aprobarProyectoId = $id;
        $this->aprobar_codigo    = "VRA-DVUS-{$siglas}-{$year}-{$nextId}";
        $this->aprobar_dictamen  = "DICTAMEN-VRA-DVUS-{$siglas}-{$year}-{$nextId}";
        $this->aprobarModal      = true;
    }

    public function aprobar(): void
    {
        $proyecto = Proyecto::findOrFail($this->aprobarProyectoId);

        $proyecto->estado_proyecto()->create([
            'empleado_id'   => Auth::user()->empleado->id,
            'tipo_estado_id' => TipoEstado::where('nombre', 'En revision final')->first()->id,
            'fecha'         => now(),
            'comentario'    => 'El proyecto ha sido enviado a revisión final.',
        ]);

        $proyecto->firma_proyecto()->updateOrCreate(
            [
                'empleado_id'   => auth()->user()->empleado->id,
                'cargo_firma_id' => CargoFirma::join('tipo_cargo_firma', 'tipo_cargo_firma.id', '=', 'cargo_firma.tipo_cargo_firma_id')
                    ->where('tipo_cargo_firma.nombre', 'Revisor Vinculacion')
                    ->where('cargo_firma.descripcion', 'Proyecto')
                    ->first()->id,
            ],
            [
                'estado_revision' => 'Aprobado',
                'firma_id'        => auth()->user()?->empleado?->firma?->id,
                'sello_id'        => auth()->user()?->empleado?->sello?->id,
                'hash'            => 'hash',
                'fecha_firma'     => now(),
            ]
        );

        $proyecto->update([
            'codigo_proyecto'         => $this->aprobar_codigo,
            'numero_dictamen'         => $this->aprobar_dictamen,
            'responsable_revision_id' => auth()->user()->empleado->id,
        ]);

        try {
            $proyecto->refresh();
            $proyecto->load(['coordinador_proyecto.empleado.user']);
            if ($proyecto->coordinador?->user) {
                Mail::to($proyecto->coordinador->user->email)->send(
                    new ProyectoEstadoCambiado($proyecto, $proyecto->coordinador->user, 'En revisión final', 'Su proyecto fue aprobado y enviado a revisión final.', 'aprobación')
                );
            }
        } catch (\Exception $e) {
            Log::error('Error correo aprobación proyecto: ' . $e->getMessage());
        }

        $this->aprobarModal = false;
        $this->viewModal = false;
        Notification::make()->title('¡Realizado!')->body('Proyecto aprobado y enviado a revisión final.')->success()->send();
    }

    public function render(): View
    {
        $records = Proyecto::query()
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
            ->when($this->search, fn($q) => $q->where(fn($q2) => $q2
                ->where('proyecto.nombre_proyecto', 'like', '%' . $this->search . '%')
                ->orWhere('proyecto.codigo_proyecto', 'like', '%' . $this->search . '%')
            ))
            ->distinct()
            ->paginate(10);

        $viewProyecto = $this->viewProyectoId
            ? Proyecto::with(['aporteInstitucional', 'presupuesto', 'ods', 'metasContribuye'])->find($this->viewProyectoId)
            : null;

        return view('livewire.proyectos.vinculacion.list-proyectos-vinculacion-solicitados', compact('records', 'viewProyecto'));
    }
}