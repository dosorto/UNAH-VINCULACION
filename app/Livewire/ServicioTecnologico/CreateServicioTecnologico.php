<?php

namespace App\Livewire\ServicioTecnologico;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\ServicioInfraestructura\ServicioTecnologico;
use App\Models\Proyecto\Modalidad;
use App\Models\Personal\Empleado;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\UnidadAcademica\DepartamentoAcademico;
use App\Models\UnidadAcademica\Carrera;
use App\Support\Notification;
use Illuminate\Contracts\View\View;

class CreateServicioTecnologico extends Component
{
    use WithFileUploads;

    public int $currentStep = 1;
    public ?int $recordId = null;

    // Step 1: Información General
    public string $nombre_accion = '';
    public ?int $modalidad_id = null;
    public array $facultades_centros = [];
    public array $departamentos_academicos = [];
    public array $carreras = [];
    public string $descripcion_servicio = '';
    public string $descripcion_problema = '';
    public string $descripcion_participante = '';
    public string $fecha_inicio = '';
    public string $fecha_finalizacion = '';

    // Step 2: Equipo Ejecutor
    public array $empleados = [];
    public array $estudiantes = [];

    // Step 3: Actividades / Cronograma
    public array $actividades = [];

    // Step 4: Información del Servicio
    public string $objetivo_general = '';
    public string $objetivo_especifico = '';
    public string $resultados_esperados = '';
    public string $indicadores_resultados = '';
    public string $descripcion_servicio_infraestructura = '';
    public string $ubicacion = '';
    public string $unidad_gestora = '';

    // Step 5: Beneficiarios / Detalles
    public int $indigenas_hombres = 0;
    public int $indigenas_mujeres = 0;
    public int $afroamericanos_hombres = 0;
    public int $afroamericanos_mujeres = 0;
    public int $mestizos_hombres = 0;
    public int $mestizos_mujeres = 0;
    public int $hombres = 0;
    public int $mujeres = 0;
    public string $aldea = '';
    public array $departamento_geo = [];
    public array $municipio_geo = [];

    // Step 6: Presupuesto
    public float $aporte_unah = 0;
    public float $aporte_contraparte = 0;
    public float $otros_aportes = 0;

    public function mount(): void
    {
        $this->initDefaults();
    }

    protected function initDefaults(): void
    {
        if (empty($this->actividades)) {
            $this->actividades = [['descripcion' => '', 'fecha_inicio' => '', 'fecha_finalizacion' => '', 'responsable' => '']];
        }
    }

    public function nextStep(): void
    {
        if ($this->currentStep < 6) $this->currentStep++;
    }

    public function prevStep(): void
    {
        if ($this->currentStep > 1) $this->currentStep--;
    }

    public function calcTotales(): void
    {
        $this->hombres = $this->indigenas_hombres + $this->afroamericanos_hombres + $this->mestizos_hombres;
        $this->mujeres = $this->indigenas_mujeres + $this->afroamericanos_mujeres + $this->mestizos_mujeres;
    }

    public function addActividad(): void { $this->actividades[] = ['descripcion' => '', 'fecha_inicio' => '', 'fecha_finalizacion' => '', 'responsable' => '']; }
    public function removeActividad(int $i): void { array_splice($this->actividades, $i, 1); }
    public function addEmpleado(): void { $this->empleados[] = ['empleado_id' => null, 'rol' => 'Integrante']; }
    public function removeEmpleado(int $i): void { array_splice($this->empleados, $i, 1); }

    public function create(): void
    {
        $this->validate([
            'nombre_accion' => 'required|string|max:255',
        ]);

        $this->calcTotales();

        try {
            $record = ServicioTecnologico::create([
                'nombre_accion' => $this->nombre_accion,
                'modalidad_id' => $this->modalidad_id,
                'descripción_servicio' => $this->descripcion_servicio,
                'descripcion_problema' => $this->descripcion_problema,
                'descripcion_participante' => $this->descripcion_participante,
                'fecha_inicio' => $this->fecha_inicio ?: null,
                'fecha_finalizacion' => $this->fecha_finalizacion ?: null,
                'objetivo_general' => $this->objetivo_general,
                'objetivo_especifico' => $this->objetivo_especifico,
                'resultados_esperados' => $this->resultados_esperados,
                'indicadores_resultados' => $this->indicadores_resultados,
                'descripción_ser_infraestructura' => $this->descripcion_servicio_infraestructura,
                'ubicacion' => $this->ubicacion,
                'unidad_gestora' => $this->unidad_gestora,
                'indigenas_hombres' => $this->indigenas_hombres,
                'indigenas_mujeres' => $this->indigenas_mujeres,
                'afroamericanos_hombres' => $this->afroamericanos_hombres,
                'afroamericanos_mujeres' => $this->afroamericanos_mujeres,
                'mestizos_hombres' => $this->mestizos_hombres,
                'mestizos_mujeres' => $this->mestizos_mujeres,
                'hombres' => $this->hombres,
                'mujeres' => $this->mujeres,
                'aldea' => $this->aldea,
            ]);

            if (!empty($this->facultades_centros)) $record->facultades_centros()->sync($this->facultades_centros);
            if (!empty($this->departamentos_academicos)) $record->departamentos_academicos()->sync($this->departamentos_academicos);
            if (!empty($this->carreras)) $record->carreras()->sync($this->carreras);
            if (!empty($this->departamento_geo)) $record->departamento()->sync($this->departamento_geo);
            if (!empty($this->municipio_geo)) $record->municipio()->sync($this->municipio_geo);

            foreach ($this->empleados as $item) {
                if (!empty($item['empleado_id'])) {
                    $record->empleados()->firstOrCreate(['empleado_id' => $item['empleado_id']], ['rol' => $item['rol'] ?? 'Integrante']);
                }
            }

            $record->presupuesto()->create([
                'aporte_unah' => $this->aporte_unah,
                'aporte_contraparte' => $this->aporte_contraparte,
                'otros_aportes' => $this->otros_aportes,
            ]);

            Notification::make()->title('¡Éxito!')->body('Servicio tecnológico creado correctamente.')->success()->send();
            $this->recordId = null;
            $this->reset(['nombre_accion', 'modalidad_id', 'descripcion_servicio', 'objetivo_general', 'currentStep']);
            $this->initDefaults();
        } catch (\Exception $e) {
            Notification::make()->title('Error')->body('Error al crear el servicio: ' . $e->getMessage())->danger()->send();
        }
    }

    public function render(): View
    {
        return view('livewire.ServicioTecnologico.create-servicio-tecnologico', [
            'modalidades' => Modalidad::orderBy('nombre')->pluck('nombre', 'id'),
            'facultadesCentros' => FacultadCentro::orderBy('nombre')->pluck('nombre', 'id'),
            'departamentosAcademicos' => empty($this->facultades_centros)
                ? collect()
                : DepartamentoAcademico::whereIn('centro_facultad_id', $this->facultades_centros)->orderBy('nombre')->pluck('nombre', 'id'),
            'carrerasOpts' => empty($this->departamentos_academicos)
                ? collect()
                : Carrera::where(function($q) { $q->whereHas('departamentosAcademicos', fn($dq) => $dq->whereIn('departamento_academico.id', $this->departamentos_academicos))->orWhereIn('departamento_academico_id', $this->departamentos_academicos); })->orderBy('nombre')->pluck('nombre', 'id'),
            'empleadosOpts' => Empleado::where('user_id', '!=', auth()->id())->orderBy('nombre_completo')->pluck('nombre_completo', 'id'),
            'departamentosGeo' => \App\Models\Demografia\Departamento::orderBy('nombre')->pluck('nombre', 'id'),
            'municipiosGeo' => empty($this->departamento_geo) ? collect() : \App\Models\Demografia\Municipio::whereIn('departamento_id', $this->departamento_geo)->orderBy('nombre')->pluck('nombre', 'id'),
        ]);
    }
}
