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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
            'modalidad_id' => 'nullable|exists:modalidad,id',
            'fecha_inicio' => 'required|date',
            'fecha_finalizacion' => 'required|date|after_or_equal:fecha_inicio',
            'facultades_centros' => 'array',
            'facultades_centros.*' => 'integer|exists:centro_facultad,id',
            'departamentos_academicos' => 'array',
            'departamentos_academicos.*' => 'integer|exists:departamento_academico,id',
            'carreras' => 'array',
            'carreras.*' => 'integer|exists:carrera,id',
            'departamento_geo' => 'array',
            'departamento_geo.*' => 'integer|exists:departamento,id',
            'municipio_geo' => 'array',
            'municipio_geo.*' => 'integer|exists:municipio,id',
            'empleados' => 'array',
            'empleados.*.empleado_id' => 'nullable|integer|exists:empleado,id',
            'empleados.*.rol' => 'nullable|in:Coordinador,Subcoordinador,Integrante',
            'aporte_unah' => 'numeric|min:0',
            'aporte_contraparte' => 'numeric|min:0',
            'otros_aportes' => 'numeric|min:0',
            'actividades' => 'array',
            'actividades.*.descripcion' => 'nullable|string',
            'actividades.*.fecha_inicio' => 'nullable|date',
            'actividades.*.fecha_finalizacion' => 'nullable|date',
            'actividades.*.objetivos' => 'nullable|string',
            'actividades.*.resultados' => 'nullable|string',
            'actividades.*.horas' => 'nullable|numeric|min:0',
        ]);

        $actividades = $this->actividadesParaGuardar();

        $this->calcTotales();

        try {
            DB::transaction(function () use ($actividades) {
                $record = ServicioTecnologico::create([
                    'nombre_accion' => $this->nombre_accion,
                    'modalidad_id' => $this->modalidad_id,
                    'descripción_servicio' => $this->descripcion_servicio,
                    'descripcion_problema' => $this->descripcion_problema,
                    'descripcion_participante' => $this->descripcion_participante,
                    'fecha_inicio' => $this->fecha_inicio,
                    'fecha_finalizacion' => $this->fecha_finalizacion,
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

                $record->centrosFacultades()->sync($this->facultades_centros);
                $record->departamentosAcademicos()->sync($this->departamentos_academicos);
                $record->carreras()->sync($this->carreras);
                $record->departamento()->sync($this->departamento_geo);
                $record->municipio()->sync($this->municipio_geo);

                foreach ($actividades as $actividad) {
                    $record->actividades()->create($actividad);
                }

                foreach ($this->empleados as $item) {
                    if (!empty($item['empleado_id'])) {
                        $record->empleados()->syncWithoutDetaching([
                            (int) $item['empleado_id'] => [
                                'rol' => $item['rol'] ?? 'Integrante',
                            ],
                        ]);
                    }
                }

                $totalIngresos = $this->aporte_unah + $this->aporte_contraparte + $this->otros_aportes;

                $record->presupuesto()->create([
                    'total_aporte_unah' => $this->aporte_unah,
                    'total_ingresos' => $totalIngresos,
                    'total_egresos' => 0,
                    'excedente' => $totalIngresos,
                ]);
            });

            Notification::make()->title('¡Éxito!')->body('Servicio tecnológico creado correctamente.')->success()->send();
            $this->recordId = null;
            $this->reset(['nombre_accion', 'modalidad_id', 'descripcion_servicio', 'objetivo_general', 'currentStep']);
            $this->initDefaults();
        } catch (\Exception $e) {
            Notification::make()->title('Error')->body('Error al crear el servicio: ' . $e->getMessage())->danger()->send();
        }
    }

    protected function actividadesParaGuardar(): array
    {
        $actividades = [];
        $errors = [];

        foreach ($this->actividades as $index => $actividad) {
            $descripcion = trim((string) ($actividad['descripcion'] ?? ''));
            $fechaInicio = $actividad['fecha_inicio'] ?? null;
            $fechaFinalizacion = $actividad['fecha_finalizacion'] ?? null;
            $objetivos = trim((string) ($actividad['objetivos'] ?? ''));
            $resultados = trim((string) ($actividad['resultados'] ?? ''));
            $horas = $actividad['horas'] ?? null;

            $tieneDatos = $descripcion !== ''
                || !empty($fechaInicio)
                || !empty($fechaFinalizacion)
                || $objetivos !== ''
                || $resultados !== ''
                || ($horas !== null && $horas !== '');

            if (!$tieneDatos) {
                continue;
            }

            if ($descripcion === '') {
                $errors["actividades.{$index}.descripcion"] = 'La descripción de la actividad es obligatoria.';
            }

            if (empty($fechaInicio)) {
                $errors["actividades.{$index}.fecha_inicio"] = 'La fecha de inicio de la actividad es obligatoria.';
            }

            if (empty($fechaFinalizacion)) {
                $errors["actividades.{$index}.fecha_finalizacion"] = 'La fecha de finalización de la actividad es obligatoria.';
            }

            if (!empty($fechaInicio) && !empty($fechaFinalizacion) && strtotime($fechaFinalizacion) < strtotime($fechaInicio)) {
                $errors["actividades.{$index}.fecha_finalizacion"] = 'La fecha de finalización debe ser posterior o igual a la fecha de inicio.';
            }

            $actividades[] = [
                'descripcion' => $descripcion,
                'fecha_inicio' => $fechaInicio,
                'fecha_finalizacion' => $fechaFinalizacion,
                'objetivos' => $objetivos !== '' ? $objetivos : null,
                'resultados' => $resultados !== '' ? $resultados : null,
                'horas' => $horas !== null && $horas !== '' ? $horas : null,
            ];
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        return $actividades;
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
