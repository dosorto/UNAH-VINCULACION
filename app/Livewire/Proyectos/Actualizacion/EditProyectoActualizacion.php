<?php

namespace App\Livewire\Proyectos\Actualizacion;

use Livewire\Component;
use App\Support\Notification;
use App\Models\Estado\TipoEstado;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\FichaActualizacion;
use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\EquipoEjecutorBaja;
use App\Models\Proyecto\EquipoEjecutorNuevo;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\IntegranteInternacional;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;

class EditProyectoActualizacion extends Component
{
    public int $currentStep = 1;
    public Proyecto $record;

    // Step 2 - extension of time
    public string $fecha_ampliacion = '';
    public string $motivo_ampliacion = '';
    public string $motivo_responsabilidades_nuevos = '';
    public string $motivo_razones_cambio = '';

    // Para estudiante_proyecto (editable cantidades)
    public array $estudiante_proyecto = [];

    // Modal dar de baja
    public bool $bajaModal = false;
    public string $bajaTipo = ''; // 'empleado' | 'internacional'
    public ?int $bajaId = null;
    public string $bajaNombre = '';
    public string $bajaMotivo = '';

    // Modal agregar nuevo empleado
    public bool $nuevoEmpleadoModal = false;
    public ?int $nuevoEmpleadoId = null;
    public string $nuevoEmpleadoMotivo = '';

    // Modal agregar nuevo internacional
    public bool $nuevoInternacionalModal = false;
    public ?int $nuevoInternacionalId = null;
    public string $nuevoInternacionalMotivo = '';

    // Modal cancelar solicitud
    public bool $cancelarSolicitudModal = false;
    public ?int $cancelarSolicitudId = null;

    // Modal reincorporar
    public bool $reincorporarModal = false;
    public ?int $reincorporarBajaId = null;

    public function mount(Proyecto $proyecto): void
    {
        if ($proyecto->coordinador_proyecto->first()?->empleado_id !== auth()->user()->empleado?->id) {
            Notification::make()->title('Error')->body('No tienes permisos para editar este proyecto')->danger()->send();
            redirect()->route('proyectosDocente');
            return;
        }

        if (!$proyecto->proyectoIsInAnyEstados(['En Curso'])) {
            Notification::make()->title('Error')->body('El proyecto no está En Curso.')->danger()->send();
            redirect()->route('proyectosDocente');
            return;
        }

        $this->record = $proyecto;
        $this->loadEstudianteProyecto();
    }

    protected function loadEstudianteProyecto(): void
    {
        $this->estudiante_proyecto = $this->record->estudiante_proyecto->map(fn($ep) => [
            'id' => $ep->id,
            'tipo_participacion_estudiante' => $ep->tipo_participacion_estudiante,
            'asignatura_id' => $ep->asignatura_id,
            'periodo_academico_id' => $ep->periodo_academico_id,
            'cantidad_estudiantes_hombres' => $ep->cantidad_estudiantes_hombres ?? 0,
            'cantidad_estudiantes_mujeres' => $ep->cantidad_estudiantes_mujeres ?? 0,
            'total_estudiantes' => $ep->total_estudiantes ?? 0,
        ])->toArray();
    }

    public function nextStep(): void
    {
        if ($this->currentStep < 2) $this->currentStep++;
    }

    public function prevStep(): void
    {
        if ($this->currentStep > 1) $this->currentStep--;
    }

    public function updateEstudianteTotal(int $i): void
    {
        $h = (int)($this->estudiante_proyecto[$i]['cantidad_estudiantes_hombres'] ?? 0);
        $m = (int)($this->estudiante_proyecto[$i]['cantidad_estudiantes_mujeres'] ?? 0);
        $this->estudiante_proyecto[$i]['total_estudiantes'] = $h + $m;
    }

    public function addEstudiante(): void
    {
        $this->estudiante_proyecto[] = [
            'id' => null,
            'tipo_participacion_estudiante' => '',
            'asignatura_id' => null,
            'periodo_academico_id' => null,
            'cantidad_estudiantes_hombres' => 0,
            'cantidad_estudiantes_mujeres' => 0,
            'total_estudiantes' => 0,
        ];
    }

    public function removeEstudiante(int $i): void
    {
        array_splice($this->estudiante_proyecto, $i, 1);
    }

    public function openBaja(int $integranteId, string $nombre, string $tipo): void
    {
        $this->bajaId = $integranteId;
        $this->bajaNombre = $nombre;
        $this->bajaTipo = $tipo;
        $this->bajaMotivo = '';
        $this->bajaModal = true;
    }

    public function confirmarBaja(): void
    {
        $this->validate(['bajaMotivo' => 'required|string|min:5']);

        EquipoEjecutorBaja::create([
            'proyecto_id' => $this->record->id,
            'integrante_id' => $this->bajaId,
            'tipo_integrante' => $this->bajaTipo,
            'nombre_integrante' => $this->bajaNombre,
            'rol_anterior' => $this->bajaTipo === 'empleado' ? 'Integrante' : 'Integrante Internacional',
            'motivo_baja' => $this->bajaMotivo,
            'fecha_baja' => now(),
            'usuario_baja_id' => auth()->id(),
            'estado_baja' => 'pendiente',
            'ficha_actualizacion_id' => null,
        ]);

        $this->bajaModal = false;
        $this->bajaId = null;
        $this->bajaMotivo = '';
        Notification::make()->title('Baja registrada')->body('Se aplicará cuando la ficha sea aprobada.')->success()->send();
    }

    public function openNuevoEmpleado(): void
    {
        $this->nuevoEmpleadoId = null;
        $this->nuevoEmpleadoMotivo = '';
        $this->nuevoEmpleadoModal = true;
    }

    public function agregarNuevoEmpleado(): void
    {
        $this->validate([
            'nuevoEmpleadoId' => 'required|integer',
            'nuevoEmpleadoMotivo' => 'required|string|min:5',
        ]);

        $tieneBaja = EquipoEjecutorBaja::where('proyecto_id', $this->record->id)
            ->where('tipo_integrante', 'empleado')->where('integrante_id', $this->nuevoEmpleadoId)
            ->whereIn('estado_baja', ['pendiente', 'aplicada'])->exists();

        $tieneSolicitud = EquipoEjecutorNuevo::where('proyecto_id', $this->record->id)
            ->where('tipo_integrante', 'empleado')->where('integrante_id', $this->nuevoEmpleadoId)
            ->where('estado_incorporacion', 'pendiente')->exists();

        if ($tieneBaja || $tieneSolicitud) {
            Notification::make()->title('Error')->body('Este empleado ya tiene una solicitud pendiente o baja.')->danger()->send();
            return;
        }

        $empleado = Empleado::find($this->nuevoEmpleadoId);
        EquipoEjecutorNuevo::create([
            'proyecto_id' => $this->record->id,
            'integrante_id' => $this->nuevoEmpleadoId,
            'tipo_integrante' => 'empleado',
            'nombre_integrante' => $empleado?->nombre_completo ?? 'Empleado',
            'rol_nuevo' => 'Integrante',
            'motivo_incorporacion' => $this->nuevoEmpleadoMotivo,
            'fecha_solicitud' => now(),
            'usuario_solicitud_id' => auth()->id(),
            'estado_incorporacion' => 'pendiente',
        ]);

        $this->nuevoEmpleadoModal = false;
        Notification::make()->title('Solicitud registrada')->body('Se aplicará cuando la ficha sea aprobada.')->success()->send();
    }

    public function openNuevoInternacional(): void
    {
        $this->nuevoInternacionalId = null;
        $this->nuevoInternacionalMotivo = '';
        $this->nuevoInternacionalModal = true;
    }

    public function agregarNuevoInternacional(): void
    {
        $this->validate([
            'nuevoInternacionalId' => 'required|integer',
            'nuevoInternacionalMotivo' => 'required|string|min:5',
        ]);

        $integrante = IntegranteInternacional::find($this->nuevoInternacionalId);
        EquipoEjecutorNuevo::create([
            'proyecto_id' => $this->record->id,
            'integrante_id' => $this->nuevoInternacionalId,
            'tipo_integrante' => 'integrante_internacional',
            'nombre_integrante' => $integrante?->nombre_completo ?? 'Integrante Internacional',
            'rol_nuevo' => 'Integrante Internacional',
            'motivo_incorporacion' => $this->nuevoInternacionalMotivo,
            'fecha_solicitud' => now(),
            'usuario_solicitud_id' => auth()->id(),
            'estado_incorporacion' => 'pendiente',
        ]);

        $this->nuevoInternacionalModal = false;
        Notification::make()->title('Solicitud registrada')->success()->send();
    }

    public function cancelarSolicitud(int $id): void
    {
        EquipoEjecutorNuevo::find($id)?->delete();
        Notification::make()->title('Solicitud cancelada')->success()->send();
    }

    public function reincorporar(int $bajaId): void
    {
        $baja = EquipoEjecutorBaja::find($bajaId);
        if (!$baja) return;

        if ($baja->tipo_integrante === 'empleado') {
            $this->record->empleado_proyecto()->firstOrCreate(['empleado_id' => $baja->integrante_id], ['rol' => $baja->rol_anterior ?? 'Integrante']);
        } elseif ($baja->tipo_integrante === 'integrante_internacional') {
            $this->record->integrante_internacional_proyecto()->firstOrCreate(['integrante_internacional_id' => $baja->integrante_id], []);
        }

        $baja->delete();
        Notification::make()->title('Integrante reincorporado')->success()->send();
    }

    public function save(): void
    {
        $hayBajas = EquipoEjecutorBaja::where('proyecto_id', $this->record->id)
            ->whereNull('ficha_actualizacion_id')->where('estado_baja', 'pendiente')->exists();

        $hayNuevos = EquipoEjecutorNuevo::where('proyecto_id', $this->record->id)
            ->whereNull('ficha_actualizacion_id')->where('estado_incorporacion', 'pendiente')->exists();

        $hayExtension = !empty($this->fecha_ampliacion) && !empty($this->motivo_ampliacion);

        $hayCambiosEstudiantes = false;
        $cambiosEstudiantes = [];
        foreach ($this->estudiante_proyecto as $item) {
            if (!empty($item['id'])) {
                $original = \App\Models\Estudiante\EstudianteProyecto::find($item['id']);
                if ($original && ($original->cantidad_estudiantes_hombres != $item['cantidad_estudiantes_hombres'] || $original->cantidad_estudiantes_mujeres != $item['cantidad_estudiantes_mujeres'])) {
                    $hayCambiosEstudiantes = true;
                    $cambiosEstudiantes[] = [
                        'estudiante_proyecto_id' => $item['id'],
                        'tipo_participacion' => $item['tipo_participacion_estudiante'],
                        'cantidad_hombres_anterior' => $original->cantidad_estudiantes_hombres,
                        'cantidad_mujeres_anterior' => $original->cantidad_estudiantes_mujeres,
                        'cantidad_hombres_nueva' => $item['cantidad_estudiantes_hombres'],
                        'cantidad_mujeres_nueva' => $item['cantidad_estudiantes_mujeres'],
                    ];
                }
            } else {
                $hayCambiosEstudiantes = true;
                $cambiosEstudiantes[] = ['estudiante_proyecto_id' => null, 'tipo_participacion' => $item['tipo_participacion_estudiante'], 'cantidad_hombres_anterior' => 0, 'cantidad_mujeres_anterior' => 0, 'cantidad_hombres_nueva' => $item['cantidad_estudiantes_hombres'], 'cantidad_mujeres_nueva' => $item['cantidad_estudiantes_mujeres']];
            }
        }

        if (!$hayBajas && !$hayNuevos && !$hayExtension && !$hayCambiosEstudiantes) {
            Notification::make()->title('Sin cambios')->body('Debe realizar al menos un cambio para enviar la ficha.')->warning()->send();
            return;
        }

        if ((!empty($this->fecha_ampliacion) && empty($this->motivo_ampliacion)) || (empty($this->fecha_ampliacion) && !empty($this->motivo_ampliacion))) {
            Notification::make()->title('Campos incompletos')->body('Para extensión de tiempo, complete fecha y motivo.')->danger()->send();
            return;
        }

        $estadoActualEstudiantes = $this->record->estudiante_proyecto->map(fn($ep) => [
            'id' => $ep->id, 'tipo_participacion' => $ep->tipo_participacion_estudiante,
            'cantidad_hombres' => $ep->cantidad_estudiantes_hombres, 'cantidad_mujeres' => $ep->cantidad_estudiantes_mujeres,
        ])->toArray();

        $ficha = FichaActualizacion::create([
            'fecha_registro' => now(),
            'empleado_id' => auth()->user()->empleado->id,
            'proyecto_id' => $this->record->id,
            'integrantes_baja' => [],
            'motivo_baja' => 'Actualización del equipo ejecutor',
            'fecha_baja' => now(),
            'fecha_ampliacion' => $this->fecha_ampliacion ?: null,
            'fecha_finalizacion_actual' => $this->record->fecha_finalizacion,
            'motivo_ampliacion' => $this->motivo_ampliacion ?: null,
            'motivo_responsabilidades_nuevos' => $this->motivo_responsabilidades_nuevos ?: null,
            'motivo_razones_cambio' => $this->motivo_razones_cambio ?: null,
            'cambios_cantidades_estudiantes' => $cambiosEstudiantes,
            'estado_estudiantes_en_momento_ficha' => $estadoActualEstudiantes,
        ]);

        EquipoEjecutorBaja::where('proyecto_id', $this->record->id)->where('estado_baja', 'pendiente')->whereNull('ficha_actualizacion_id')->update(['ficha_actualizacion_id' => $ficha->id]);
        EquipoEjecutorNuevo::where('proyecto_id', $this->record->id)->where('estado_incorporacion', 'pendiente')->whereNull('ficha_actualizacion_id')->update(['ficha_actualizacion_id' => $ficha->id]);

        $cargosFirma = CargoFirma::where('descripcion', 'Ficha_actualizacion')->get();
        foreach ($cargosFirma as $cargo) {
            $firmaOriginal = $this->record->firma_proyecto()->whereHas('cargo_firma.tipoCargoFirma', fn($q) => $q->where('nombre', $cargo->tipoCargoFirma->nombre))->where('firmable_type', Proyecto::class)->first();
            if ($firmaOriginal) {
                $esCoord = $cargo->tipoCargoFirma->nombre === 'Coordinador Proyecto';
                $ficha->firma_proyecto()->create([
                    'empleado_id' => $firmaOriginal->empleado_id,
                    'cargo_firma_id' => $cargo->id,
                    'estado_revision' => $esCoord ? 'Aprobado' : 'Pendiente',
                    'firma_id' => $esCoord ? auth()->user()?->empleado?->firma?->id : null,
                    'sello_id' => $esCoord ? auth()->user()?->empleado?->sello?->id : null,
                    'hash' => 'hash',
                    'fecha_firma' => $esCoord ? now() : null,
                ]);
            }
        }

        $primerCargo = CargoFirma::where('descripcion', 'Ficha_actualizacion')->join('tipo_cargo_firma', 'tipo_cargo_firma.id', '=', 'cargo_firma.tipo_cargo_firma_id')->where('tipo_cargo_firma.nombre', 'Coordinador Proyecto')->select('cargo_firma.*')->first();
        if ($primerCargo) {
            $ficha->estado_proyecto()->create([
                'empleado_id' => auth()->user()->empleado->id,
                'tipo_estado_id' => $primerCargo->estado_siguiente_id,
                'fecha' => now(),
                'comentario' => 'Ficha de actualización enviada a firmar',
            ]);
        }

        try { \Illuminate\Support\Facades\Mail::to(auth()->user()->email)->send(new \App\Mail\FichaActualizacionCreada($ficha, auth()->user())); } catch (\Exception $e) { Log::warning($e->getMessage()); }

        Notification::make()->title('Ficha enviada a firmar')->success()->send();
        redirect()->route('FichasActualizacionDocente');
    }

    public function render(): View
    {
        $proyectoId = $this->record->id;
        return view('livewire.proyectos.actualizacion.create-proyecto-actualizacion', [
            'coordinador' => $this->record->coordinador_proyecto->first()?->empleado,
            'empleadosProyecto' => $this->record->empleado_proyecto()->with('empleado')->get(),
            'internacionalesProyecto' => $this->record->integrante_internacional_proyecto()->with('integranteInternacional')->get(),
            'bajasPendientes' => EquipoEjecutorBaja::where('proyecto_id', $proyectoId)->where('estado_baja', 'pendiente')->whereNull('ficha_actualizacion_id')->get(),
            'nuevosPendientes' => EquipoEjecutorNuevo::where('proyecto_id', $proyectoId)->where('estado_incorporacion', 'pendiente')->whereNull('ficha_actualizacion_id')->get(),
            'empleadosDisponibles' => Empleado::where('user_id', '!=', auth()->id())
                ->whereNotIn('id', \App\Models\Personal\EmpleadoProyecto::where('proyecto_id', $proyectoId)->pluck('empleado_id'))
                ->orderBy('nombre_completo')->pluck('nombre_completo', 'id'),
            'internacionalesDisponibles' => IntegranteInternacional::orderBy('nombre_completo')->get()->mapWithKeys(fn($i) => [$i->id => "{$i->nombre_completo} ({$i->pais})"]),
        ]);
    }
}
