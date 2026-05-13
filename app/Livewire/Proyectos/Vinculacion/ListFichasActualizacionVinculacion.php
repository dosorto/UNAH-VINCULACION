<?php

namespace App\Livewire\Proyectos\Vinculacion;

use App\Http\Controllers\Docente\VerificarConstancia;
use App\Models\Estado\TipoEstado;
use App\Models\Proyecto\FichaActualizacion;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\ProyectoEstadoCambiado;
use Livewire\Component;
use Livewire\WithPagination;

class ListFichasActualizacionVinculacion extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $viewModal = false;
    public ?int $viewFichaId = null;

    public bool $rechazarModal = false;
    public ?int $rechazarFichaId = null;
    public string $rechazarComentario = '';

    public bool $aprobarModal = false;
    public ?int $aprobarFichaId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openView(int $id): void
    {
        $this->viewFichaId = $id;
        $this->viewModal = true;
    }

    public function openRechazar(int $id): void
    {
        $this->rechazarFichaId = $id;
        $this->rechazarComentario = '';
        $this->rechazarModal = true;
    }

    public function rechazar(): void
    {
        $this->validate(['rechazarComentario' => 'required|string']);

        $ficha = FichaActualizacion::findOrFail($this->rechazarFichaId);

        $firmaRevisor = $ficha->firma_proyecto()
            ->whereHas('cargo_firma.tipoCargoFirma', fn($q) => $q->where('nombre', 'Revisor Vinculacion'))
            ->where('empleado_id', auth()->user()->empleado->id)
            ->first();

        if ($firmaRevisor) {
            $firmaRevisor->update([
                'estado_revision' => 'Pendiente',
                'firma_id'        => null,
                'sello_id'        => null,
                'fecha_firma'     => null,
            ]);

            $ficha->estado_proyecto()->create([
                'empleado_id'   => auth()->user()->empleado->id,
                'tipo_estado_id' => TipoEstado::where('nombre', 'Rechazado')->first()->id,
                'fecha'         => now(),
                'comentario'    => $this->rechazarComentario,
            ]);

            $ficha->cancelarSolicitudesPorRechazo();

            try {
                $coordinador = $ficha->proyecto->coordinador_proyecto->first()?->empleado->user ?? null;
                if ($coordinador) {
                    Mail::to($coordinador->email)->send(
                        new ProyectoEstadoCambiado($ficha->proyecto, $coordinador, 'Ficha de Actualización Rechazada', $this->rechazarComentario, 'rechazo de ficha de actualización')
                    );
                }
            } catch (\Exception $e) {
                Log::error('Error enviando correo rechazo ficha: ' . $e->getMessage());
            }
        }

        $this->rechazarModal = false;
        $this->rechazarFichaId = null;
        $this->viewModal = false;
        Notification::make()->title('¡Realizado!')->body('Ficha de Actualización Rechazada')->info()->send();
    }

    public function openAprobar(int $id): void
    {
        $this->aprobarFichaId = $id;
        $this->aprobarModal = true;
    }

    public function aprobar(): void
    {
        $ficha = FichaActualizacion::findOrFail($this->aprobarFichaId);

        $firmaRevisor = $ficha->firma_proyecto()
            ->whereHas('cargo_firma.tipoCargoFirma', fn($q) => $q->where('nombre', 'Revisor Vinculacion'))
            ->where('empleado_id', auth()->user()->empleado->id)
            ->first();

        if ($firmaRevisor) {
            $firmaRevisor->update([
                'estado_revision' => 'Aprobado',
                'firma_id'        => auth()->user()?->empleado?->firma?->id,
                'sello_id'        => auth()->user()?->empleado?->sello?->id,
                'fecha_firma'     => now(),
            ]);

            $ficha->estado_proyecto()->create([
                'empleado_id'   => auth()->user()->empleado->id,
                'tipo_estado_id' => TipoEstado::where('nombre', 'Actualizacion realizada')->first()->id,
                'fecha'         => now(),
                'comentario'    => 'Ficha de actualización aprobada por Revisor de Vinculación.',
            ]);

            $bajas           = $ficha->procesarBajasPendientes();
            $nuevos          = $ficha->procesarIntegrantesNuevos();
            $fechaActualizada = $ficha->aplicarNuevaFechaFinalizacion();

            $mensaje = 'Ficha Aprobada.';
            if ($bajas > 0) $mensaje .= " Bajas aplicadas: {$bajas}.";
            if ($nuevos > 0) $mensaje .= " Integrantes incorporados: {$nuevos}.";
            if ($fechaActualizada['actualizada']) {
                $f = \Carbon\Carbon::parse($fechaActualizada['fecha_nueva'])->format('d/m/Y');
                $mensaje .= " Fecha fin actualizada a {$f}.";
            }

            try {
                $coordinador = $ficha->proyecto->coordinador_proyecto->first()?->empleado->user ?? null;
                if ($coordinador) {
                    Mail::to($coordinador->email)->send(
                        new ProyectoEstadoCambiado($ficha->proyecto, $coordinador, 'Ficha de Actualización Aprobada', $mensaje, 'aprobación de ficha de actualización')
                    );
                }
            } catch (\Exception $e) {
                Log::error('Error enviando correo aprobación ficha: ' . $e->getMessage());
            }

            VerificarConstancia::makeConstanciasActualizacion($ficha);
        }

        $this->aprobarModal = false;
        $this->viewModal = false;
        Notification::make()->title('¡Realizado!')->body($mensaje ?? 'Ficha Aprobada')->success()->send();
    }

    public function render(): View
    {
        $records = FichaActualizacion::query()
            ->whereHas('estado_proyecto', function (Builder $query) {
                $query->where('tipo_estado_id', TipoEstado::where('nombre', 'En revision')->first()?->id)
                      ->where('es_actual', true);
            })
            ->whereHas('firma_proyecto', function (Builder $query) {
                $query->whereHas('cargo_firma.tipoCargoFirma', fn($q) => $q->where('nombre', 'Revisor Vinculacion'))
                    ->where('empleado_id', auth()->user()->empleado->id)
                    ->where('estado_revision', 'Pendiente');
            })
            ->when($this->search, fn($q) => $q->whereHas('proyecto', fn($q2) =>
                $q2->where('nombre_proyecto', 'like', '%' . $this->search . '%')
                   ->orWhere('codigo_proyecto', 'like', '%' . $this->search . '%')
            ))
            ->latest()
            ->paginate(10);

        $viewFicha = $this->viewFichaId
            ? FichaActualizacion::with(['proyecto.aporteInstitucional', 'proyecto.presupuesto', 'proyecto.ods', 'proyecto.metasContribuye'])->find($this->viewFichaId)
            : null;

        return view('livewire.proyectos.vinculacion.list-fichas-actualizacion-vinculacion', compact('records', 'viewFicha'));
    }
}