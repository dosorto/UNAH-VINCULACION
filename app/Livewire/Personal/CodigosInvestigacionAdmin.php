<?php

namespace App\Livewire\Personal;

use App\Models\Personal\EmpleadoCodigoInvestigacion;
use App\Models\Personal\EmpleadoProyecto;
use App\Models\Proyecto\Proyecto;
use App\Models\Estado\TipoEstado;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class CodigosInvestigacionAdmin extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterEstado = '';
    public string $filterRol = '';
    public string $filterAnio = '';

    public bool $verificarModal = false;
    public ?int $verificarId = null;
    public string $verificarDictamen = '';
    public string $verificarObservaciones = '';

    public bool $rechazarModal = false;
    public ?int $rechazarId = null;
    public string $rechazarObservaciones = '';

    public bool $viewModal = false;
    public ?int $viewId = null;

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFilterEstado(): void { $this->resetPage(); }
    public function updatingFilterRol(): void { $this->resetPage(); }
    public function updatingFilterAnio(): void { $this->resetPage(); }

    public function openVerificar(int $id): void
    {
        $this->verificarId = $id;
        $this->verificarDictamen = '';
        $this->verificarObservaciones = '';
        $this->verificarModal = true;
    }

    public function verificar(): void
    {
        $this->validate([
            'verificarDictamen' => 'required|string',
        ]);

        $record = EmpleadoCodigoInvestigacion::findOrFail($this->verificarId);
        $data = ['numero_dictamen' => $this->verificarDictamen, 'observaciones_admin' => $this->verificarObservaciones];
        $proyectoExistente = Proyecto::where('codigo_proyecto', $record->codigo_proyecto)->first();

        if ($proyectoExistente) {
            $empleadoYaRegistrado = EmpleadoProyecto::where('empleado_id', $record->empleado_id)
                ->where('proyecto_id', $proyectoExistente->id)->exists();

            if (!$empleadoYaRegistrado) {
                EmpleadoProyecto::create([
                    'empleado_id' => $record->empleado_id,
                    'proyecto_id' => $proyectoExistente->id,
                    'rol'         => $record->rol_docente === 'coordinador' ? 'Coordinador' : 'Integrante',
                    'hash'        => Str::random(32),
                ]);
                $body = "El código {$record->codigo_proyecto} ha sido verificado y el docente registrado en el proyecto existente.";
            } else {
                $body = "El código {$record->codigo_proyecto} ha sido verificado. El docente ya estaba registrado en este proyecto.";
            }

            $record->update([
                'estado_verificacion' => 'verificado',
                'observaciones_admin' => ($data['observaciones_admin'] ?? '') . ' | Dictamen: ' . $data['numero_dictamen'],
                'verificado_por'      => auth()->user()->empleado->id,
                'fecha_verificacion'  => now(),
            ]);
            $proyectoExistente->update(['numero_dictamen' => $data['numero_dictamen']]);
        } else {
            try {
                $nuevoProyecto = Proyecto::create([
                    'nombre_proyecto'            => $record->nombre_proyecto,
                    'codigo_proyecto'            => $record->codigo_proyecto,
                    'resumen'                    => $record->descripcion ?? 'Proyecto registrado mediante código de investigación',
                    'descripcion_participantes'  => 'Proyecto verificado administrativamente',
                    'definicion_problema'        => 'Por definir',
                    'objetivo_general'           => 'Por definir',
                    'fecha_inicio'               => now(),
                    'fecha_finalizacion'         => now()->addYear(),
                    'evaluacion_intermedia'      => now()->addMonths(6),
                    'evaluacion_final'           => now()->addYear(),
                    'poblacion_participante'     => 0,
                    'hombres'                    => 0,
                    'mujeres'                    => 0,
                    'otros'                      => 0,
                    'modalidad_id'               => 1,
                    'fecha_registro'             => now(),
                    'numero_dictamen'            => $data['numero_dictamen'] ?? null,
                ]);

                EmpleadoProyecto::create([
                    'empleado_id' => $record->empleado_id,
                    'proyecto_id' => $nuevoProyecto->id,
                    'rol'         => $record->rol_docente === 'coordinador' ? 'Coordinador' : 'Integrante',
                    'hash'        => Str::random(32),
                ]);

                $tipoEstadoPendiente = TipoEstado::firstOrCreate(
                    ['nombre' => 'PendienteInformacion'],
                    ['descripcion' => 'Estado para proyectos creados automáticamente que requieren completar información']
                );

                $nuevoProyecto->estado_proyecto()->create([
                    'empleado_id'    => auth()->user()->empleado->id,
                    'tipo_estado_id' => $tipoEstadoPendiente->id,
                    'fecha'          => now(),
                    'comentario'     => 'Proyecto creado automáticamente al verificar código de investigación. Dictamen: ' . ($data['numero_dictamen'] ?? 'Sin dictamen'),
                ]);

                $record->update([
                    'estado_verificacion' => 'verificado',
                    'observaciones_admin' => ($data['observaciones_admin'] ?? 'Proyecto creado automáticamente') . ' | Dictamen: ' . ($data['numero_dictamen'] ?? 'Sin dictamen'),
                    'verificado_por'      => auth()->user()->empleado->id,
                    'fecha_verificacion'  => now(),
                ]);

                $body = "El código {$record->codigo_proyecto} ha sido verificado con dictamen {$data['numero_dictamen']}. Se ha creado un nuevo proyecto.";
            } catch (\Exception $e) {
                $this->verificarModal = false;
                Notification::make()->title('Error al crear proyecto')->body($e->getMessage())->danger()->send();
                return;
            }
        }

        $this->verificarModal = false;
        $this->verificarId = null;
        Notification::make()->title('Código verificado')->body($body)->success()->send();
    }

    public function openRechazar(int $id): void
    {
        $this->rechazarId = $id;
        $this->rechazarObservaciones = '';
        $this->rechazarModal = true;
    }

    public function rechazar(): void
    {
        $this->validate(['rechazarObservaciones' => 'required|string']);

        $record = EmpleadoCodigoInvestigacion::findOrFail($this->rechazarId);
        $record->update([
            'estado_verificacion' => 'rechazado',
            'observaciones_admin' => $this->rechazarObservaciones,
            'verificado_por'      => auth()->user()->empleado->id,
            'fecha_verificacion'  => now(),
        ]);

        $this->rechazarModal = false;
        $this->rechazarId = null;

        Notification::make()->title('Código rechazado')->body("El código {$record->codigo_proyecto} ha sido rechazado.")->warning()->send();
    }

    public function revertir(int $id): void
    {
        $record = EmpleadoCodigoInvestigacion::findOrFail($id);
        $record->update([
            'estado_verificacion' => 'pendiente',
            'observaciones_admin' => null,
            'verificado_por'      => null,
            'fecha_verificacion'  => null,
        ]);

        $proyecto = Proyecto::where('codigo_proyecto', $record->codigo_proyecto)->first();
        if ($proyecto) {
            $proyecto->update(['numero_dictamen' => null]);
        }

        Notification::make()->title('Estado revertido')->body("El código {$record->codigo_proyecto} ha sido revertido a pendiente.")->info()->send();
    }

    public function openView(int $id): void
    {
        $this->viewId = $id;
        $this->viewModal = true;
    }

    public function render(): View
    {
        $records = EmpleadoCodigoInvestigacion::query()
            ->with(['empleado', 'verificadoPor'])
            ->when($this->search, fn($q) => $q->whereHas('empleado', fn($q) =>
                $q->where('nombre_completo', 'like', '%' . $this->search . '%')
            )->orWhere('codigo_proyecto', 'like', '%' . $this->search . '%')
             ->orWhere('nombre_proyecto', 'like', '%' . $this->search . '%'))
            ->when($this->filterEstado, fn($q) => $q->where('estado_verificacion', $this->filterEstado))
            ->when($this->filterRol, fn($q) => $q->where('rol_docente', $this->filterRol))
            ->when($this->filterAnio, fn($q) => $q->where('año', $this->filterAnio))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $viewRecord = $this->viewId ? EmpleadoCodigoInvestigacion::with(['empleado', 'verificadoPor'])->find($this->viewId) : null;

        $anios = collect(range(date('Y') - 10, date('Y') + 2))->mapWithKeys(fn($y) => [$y => $y]);

        return view('livewire.personal.codigos-investigacion-admin', compact('records', 'viewRecord', 'anios'));
    }
}