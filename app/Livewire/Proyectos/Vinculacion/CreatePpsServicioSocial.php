<?php

namespace App\Livewire\Proyectos\Vinculacion;

use App\Models\Demografia\Aldea;
use App\Models\Demografia\Ciudad;
use App\Models\Demografia\Departamento;
use App\Models\Demografia\Municipio;
use App\Models\Personal\Empleado;
use App\Models\PpsServicioSocial;
use App\Models\UnidadAcademica\Carrera;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class CreatePpsServicioSocial extends Component
{
    use WithFileUploads;

    public int $currentStep = 1;
    public int $totalSteps = 10;
    public bool $registroGuardado = false;

    // Paso 1: Informacion general
    public ?int $facultad_centro_id = null;
    public ?int $carrera_id = null;

    // Paso 2: Datos del estudiante
    public string $numero_cuenta = '';
    public string $estudiante_nombre_completo = '';
    public string $estudiante_celular = '';
    public string $estudiante_correo_institucional = '';
    public string $estudiante_correo_personal = '';

    // Paso 3: Informacion de la PPS / Servicio Social
    public string $tipo_pps_ss = '';
    public string $fecha_inicio = '';
    public string $fecha_finalizacion = '';
    public string $tipo_instrumento = '';
    public string $territorio_ejecucion = 'Nacional';

    // Paso 4: Datos territoriales
    public ?int $departamento_id = null;
    public ?int $municipio_id = null;
    public ?int $aldea_id = null;
    public ?int $ciudad_id = null;
    public string $caserio = '';

    // Paso 5: Alcance de la PPS / Servicio Social
    public string $descripcion_tipo_pps = '';
    public string $total_horas = '';
    public string $area_realizacion = '';
    public string $resumen_responsabilidades = '';
    public string $modalidad_ejecucion = '';

    // Paso 6: Institucion / Organizacion
    public string $institucion_nombre = '';
    public string $institucion_compromisos = '';
    public string $institucion_direccion = '';
    public string $institucion_representante = '';
    public string $institucion_telefono = '';
    public string $institucion_correo_rrhh = '';
    public string $institucion_tipo = '';
    public string $institucion_sector = '';

    // Paso 7: Jefe directo de la PPS / SS
    public string $jefe_directo_nombre = '';
    public string $jefe_directo_celular = '';
    public string $jefe_directo_correo = '';
    public string $jefe_directo_cargo = '';
    public string $jefe_directo_grado = '';

    // Paso 8: Docente supervisor
    public ?int $docente_supervisor_id = null;
    public string $docente_supervisor_nombre = '';
    public string $docente_numero_empleado = '';
    public string $docente_celular = '';
    public string $docente_correo = '';
    public string $docente_categoria = '';
    public string $docente_departamento = '';
    public string $docente_jornada = '';
    public string $docente_cubiculo = '';

    // Paso 9: Documentos adjuntos
    public string $carta_formalizacion_aplica = 'No';
    public $carta_formalizacion_archivo = null;
    public string $convenio_marco_aplica = 'No';
    public $convenio_marco_archivo = null;

    public array $tipoPpsOpciones = [
        'Practica Profesional Supervisada',
        'Servicio Social',
    ];

    public array $instrumentoOpciones = [
        'carta_formal_solicitud' => 'Carta formal de solicitud a la unidad academica',
        'carta_intenciones' => 'Carta de intenciones con la UNAH',
        'convenio_marco' => 'Convenio marco con la UNAH',
    ];

    public array $tipoInstitucionOpciones = [
        'gobierno_nacional' => 'Gobierno Nacional',
        'gobierno_municipal' => 'Gobierno Municipal',
        'ong' => 'ONG',
        'sociedad_civil' => 'Sociedad civil organizada',
        'sector_privado' => 'Sector Privado',
        'internacional' => 'Internacional',
    ];

    public array $sectorOpciones = [
        'agricultura_alimentacion_silvicultura' => 'Agricultura, alimentacion y silvicultura',
        'energia_mineria' => 'Energia y mineria',
        'produccion' => 'Produccion',
        'servicios_privados' => 'Sectores de servicios privados',
        'infraestructura_construccion' => 'Infraestructura, construccion y sectores relacionados',
        'educacion_investigacion' => 'Educacion e investigacion',
        'servicios_funcion_publicos' => 'Servicios y funcion publicos',
        'transporte' => 'Transporte, transporte maritimo y aereo',
    ];

    public function updatedFacultadCentroId(): void
    {
        $this->carrera_id = null;
    }

    public function updatedDepartamentoId(): void
    {
        $this->municipio_id = null;
        $this->aldea_id = null;
        $this->ciudad_id = null;
    }

    public function updatedMunicipioId(): void
    {
        $this->aldea_id = null;
        $this->ciudad_id = null;
    }

    public function updatedTerritorioEjecucion(string $value): void
    {
        if ($value === 'Internacional') {
            $this->departamento_id = null;
            $this->municipio_id = null;
            $this->aldea_id = null;
            $this->ciudad_id = null;
            $this->caserio = '';
        }
    }

    public function updatedDocenteSupervisorId($docenteId): void
    {
        if (!$docenteId) {
            $this->reset([
                'docente_supervisor_nombre',
                'docente_numero_empleado',
                'docente_celular',
                'docente_correo',
                'docente_categoria',
                'docente_departamento',
            ]);

            return;
        }

        $docente = Empleado::with(['user', 'categoria', 'departamento_academico'])->find((int) $docenteId);

        if (!$docente) {
            return;
        }

        $this->docente_supervisor_nombre = $docente->nombre_completo ?? '';
        $this->docente_numero_empleado = $docente->numero_empleado ?? '';
        $this->docente_celular = $docente->celular ?? '';
        $this->docente_correo = $docente->user?->email ?? '';
        $this->docente_categoria = $docente->categoria?->nombre ?? '';
        $this->docente_departamento = $docente->departamento_academico?->nombre ?? '';
    }

    public function nextStep(): void
    {
        $this->resetErrorBag();
        $this->validateCurrentStep();

        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
        }
    }

    public function prevStep(): void
    {
        if ($this->currentStep > 1) {
            $this->resetErrorBag();
            $this->currentStep--;
        }
    }

    public function goToStep(int $step): void
    {
        if ($step < 1 || $step > $this->totalSteps || $step === $this->currentStep) {
            return;
        }

        if ($step < $this->currentStep) {
            $this->resetErrorBag();
            $this->currentStep = $step;

            return;
        }

        // Avoid skipping required sections when the record is still only in memory.
        $this->nextStep();
    }

    public function goToReview(): void
    {
        $this->resetErrorBag();
        $this->validateCurrentStep();
        $this->currentStep = 10;
    }

    public function guardar(): void
    {
        $this->resetErrorBag();
        $this->validate($this->rules(), [], $this->validationAttributes());

        // TODO: Confirmar politica final de almacenamiento y retencion de adjuntos FORM-DVUS-015/016.
        $archivoCarta = $this->carta_formalizacion_archivo
            ? $this->carta_formalizacion_archivo->store('pps-servicio-social/documentos', 'public')
            : null;

        $archivoConvenio = $this->convenio_marco_archivo
            ? $this->convenio_marco_archivo->store('pps-servicio-social/documentos', 'public')
            : null;

        PpsServicioSocial::create([
            'codigo_registro' => $this->generarCodigoRegistro(),
            'estado' => 'borrador',
            'facultad_centro' => $this->nombreFacultadCentro(),
            'carrera' => $this->nombreCarrera(),
            'numero_cuenta' => $this->numero_cuenta,
            'nombre_estudiante' => $this->estudiante_nombre_completo,
            'celular_estudiante' => $this->estudiante_celular,
            'correo_institucional' => $this->estudiante_correo_institucional,
            'correo_personal' => $this->estudiante_correo_personal ?: null,
            'tipo_pps_ss' => $this->tipo_pps_ss,
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_finalizacion' => $this->fecha_finalizacion,
            'tipo_instrumento' => $this->instrumentoOpciones[$this->tipo_instrumento] ?? $this->tipo_instrumento,
            'territorio_ejecucion' => $this->territorio_ejecucion,
            'departamento' => $this->territorio_ejecucion === 'Nacional' ? $this->nombreDepartamento() : null,
            'municipio' => $this->territorio_ejecucion === 'Nacional' ? $this->nombreMunicipio() : null,
            'aldea_ciudad' => $this->territorio_ejecucion === 'Nacional' ? $this->nombreAldeaCiudad() : null,
            'caserio' => $this->territorio_ejecucion === 'Nacional' ? ($this->caserio ?: null) : null,
            'descripcion_tipo_pps' => $this->descripcion_tipo_pps ?: null,
            'total_horas' => (int) $this->total_horas,
            'area_realizacion' => $this->area_realizacion ?: null,
            'resumen_responsabilidades' => $this->resumen_responsabilidades ?: null,
            'modalidad_ejecucion' => $this->modalidad_ejecucion,
            'nombre_institucion' => $this->institucion_nombre,
            'compromisos_institucion' => $this->institucion_compromisos ?: null,
            'direccion_institucion' => $this->institucion_direccion ?: null,
            'representante_legal' => $this->institucion_representante ?: null,
            'telefono_representante' => $this->institucion_telefono ?: null,
            'correo_rrhh' => $this->institucion_correo_rrhh ?: null,
            'tipo_institucion' => $this->tipoInstitucionOpciones[$this->institucion_tipo] ?? ($this->institucion_tipo ?: null),
            'sector_institucion' => $this->sectorOpciones[$this->institucion_sector] ?? ($this->institucion_sector ?: null),
            'nombre_jefe_directo' => $this->jefe_directo_nombre,
            'celular_jefe_directo' => $this->jefe_directo_celular ?: null,
            'correo_jefe_directo' => $this->jefe_directo_correo ?: null,
            'cargo_jefe_directo' => $this->jefe_directo_cargo ?: null,
            'grado_academico_jefe_directo' => $this->jefe_directo_grado ?: null,
            'nombre_docente_supervisor' => $this->docente_supervisor_nombre,
            'numero_empleado_docente' => $this->docente_numero_empleado ?: null,
            'celular_docente' => $this->docente_celular ?: null,
            'correo_docente' => $this->docente_correo ?: null,
            'categoria_docente' => $this->docente_categoria ?: null,
            'departamento_docente' => $this->docente_departamento ?: null,
            'jornada_laboral_docente' => $this->docente_jornada ?: null,
            'ubicacion_cubiculo_docente' => $this->docente_cubiculo ?: null,
            'adjunta_carta_formalizacion' => $this->carta_formalizacion_aplica === 'Si',
            'archivo_carta_formalizacion' => $archivoCarta,
            'adjunta_convenio_marco' => $this->convenio_marco_aplica === 'Si',
            'archivo_convenio_marco' => $archivoConvenio,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        Notification::make()
            ->title('Registro guardado')
            ->body('El FORM-DVUS-015/016 fue guardado correctamente como borrador.')
            ->success()
            ->send();

        $this->registroGuardado = true;
        $this->redirectRoute('inicio');
    }

    protected function validateCurrentStep(): void
    {
        $rules = $this->rulesForStep($this->currentStep);

        if ($rules) {
            $this->validate($rules, [], $this->validationAttributes());
        }
    }

    protected function rulesForStep(int $step): array
    {
        return match ($step) {
            1 => [
                'facultad_centro_id' => 'required|integer|exists:centro_facultad,id',
                'carrera_id' => 'required|integer|exists:carrera,id',
            ],
            2 => [
                'numero_cuenta' => 'required|string|max:50',
                'estudiante_nombre_completo' => 'required|string|max:255',
                'estudiante_celular' => 'required|string|max:30',
                'estudiante_correo_institucional' => 'required|email|max:255',
                'estudiante_correo_personal' => 'nullable|email|max:255',
            ],
            3 => [
                'tipo_pps_ss' => 'required|string|in:Practica Profesional Supervisada,Servicio Social',
                'fecha_inicio' => 'required|date',
                'fecha_finalizacion' => 'required|date|after_or_equal:fecha_inicio',
                'tipo_instrumento' => 'required|string|in:carta_formal_solicitud,carta_intenciones,convenio_marco',
                'territorio_ejecucion' => 'required|string|in:Nacional,Internacional',
            ],
            4 => [
                'departamento_id' => 'required_if:territorio_ejecucion,Nacional|nullable|integer|exists:departamento,id',
                'municipio_id' => 'required_if:territorio_ejecucion,Nacional|nullable|integer|exists:municipio,id',
                'aldea_id' => 'nullable|integer|exists:aldea,id',
                'ciudad_id' => 'nullable|integer|exists:ciudad,id',
                'caserio' => 'nullable|string|max:255',
            ],
            5 => [
                'descripcion_tipo_pps' => 'nullable|string',
                'total_horas' => 'required|integer|min:1',
                'area_realizacion' => 'nullable|string|max:255',
                'resumen_responsabilidades' => 'nullable|string',
                'modalidad_ejecucion' => 'required|string|in:Presencial,100% virtual,Hibrida',
            ],
            6 => [
                'institucion_nombre' => 'required|string|max:255',
                'institucion_compromisos' => 'nullable|string',
                'institucion_direccion' => 'nullable|string',
                'institucion_representante' => 'nullable|string|max:255',
                'institucion_telefono' => 'nullable|string|max:30',
                'institucion_correo_rrhh' => 'nullable|email|max:255',
                'institucion_tipo' => 'nullable|string',
                'institucion_sector' => 'nullable|string',
            ],
            7 => [
                'jefe_directo_nombre' => 'required|string|max:255',
                'jefe_directo_celular' => 'nullable|string|max:30',
                'jefe_directo_correo' => 'nullable|email|max:255',
                'jefe_directo_cargo' => 'nullable|string|max:255',
                'jefe_directo_grado' => 'nullable|string|max:255',
            ],
            8 => [
                'docente_supervisor_nombre' => 'required|string|max:255',
                'docente_numero_empleado' => 'nullable|string|max:50',
                'docente_celular' => 'nullable|string|max:30',
                'docente_correo' => 'nullable|email|max:255',
                'docente_categoria' => 'nullable|string|max:255',
                'docente_departamento' => 'nullable|string|max:255',
                'docente_jornada' => 'nullable|string|max:255',
                'docente_cubiculo' => 'nullable|string|max:255',
            ],
            9 => [
                'carta_formalizacion_aplica' => 'required|in:Si,No',
                'carta_formalizacion_archivo' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
                'convenio_marco_aplica' => 'required|in:Si,No',
                'convenio_marco_archivo' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
            ],
            default => [],
        };
    }

    protected function rules(): array
    {
        return collect(range(1, 9))
            ->flatMap(fn (int $step) => $this->rulesForStep($step))
            ->all();
    }

    public function isStepComplete(int $step): bool
    {
        return match ($step) {
            1 => filled($this->facultad_centro_id) && filled($this->carrera_id),
            2 => filled($this->numero_cuenta)
                && filled($this->estudiante_nombre_completo)
                && filled($this->estudiante_celular)
                && filled($this->estudiante_correo_institucional),
            3 => filled($this->tipo_pps_ss)
                && filled($this->fecha_inicio)
                && filled($this->fecha_finalizacion)
                && filled($this->tipo_instrumento)
                && filled($this->territorio_ejecucion),
            4 => $this->territorio_ejecucion === 'Internacional'
                || (filled($this->departamento_id) && filled($this->municipio_id)),
            5 => filled($this->total_horas) && filled($this->modalidad_ejecucion),
            6 => filled($this->institucion_nombre),
            7 => filled($this->jefe_directo_nombre),
            8 => filled($this->docente_supervisor_nombre),
            9 => filled($this->carta_formalizacion_aplica) && filled($this->convenio_marco_aplica),
            10 => collect(range(1, 9))->every(fn (int $step) => $this->isStepComplete($step)),
            default => false,
        };
    }

    protected function validationAttributes(): array
    {
        return [
            'facultad_centro_id' => 'facultad o centro',
            'carrera_id' => 'carrera',
            'numero_cuenta' => 'numero de cuenta',
            'estudiante_nombre_completo' => 'nombre completo del estudiante',
            'estudiante_celular' => 'celular del estudiante',
            'estudiante_correo_institucional' => 'correo institucional',
            'tipo_pps_ss' => 'tipo PPS/SS',
            'tipo_instrumento' => 'tipo de instrumento',
            'fecha_inicio' => 'fecha de inicio',
            'fecha_finalizacion' => 'fecha de finalizacion',
            'departamento_id' => 'departamento',
            'municipio_id' => 'municipio',
            'total_horas' => 'total de horas',
            'modalidad_ejecucion' => 'modalidad de ejecucion',
            'institucion_nombre' => 'institucion u organizacion',
            'jefe_directo_nombre' => 'jefe directo',
            'docente_supervisor_nombre' => 'docente supervisor',
        ];
    }

    protected function generarCodigoRegistro(): string
    {
        do {
            $codigo = 'PPS-SS-' . now()->format('Y') . '-' . Str::upper(Str::random(6));
        } while (PpsServicioSocial::where('codigo_registro', $codigo)->exists());

        return $codigo;
    }

    protected function nombreFacultadCentro(): string
    {
        return FacultadCentro::find($this->facultad_centro_id)?->nombre ?? (string) $this->facultad_centro_id;
    }

    protected function nombreCarrera(): string
    {
        return Carrera::find($this->carrera_id)?->nombre ?? (string) $this->carrera_id;
    }

    protected function nombreDepartamento(): ?string
    {
        return $this->departamento_id ? Departamento::find($this->departamento_id)?->nombre : null;
    }

    protected function nombreMunicipio(): ?string
    {
        return $this->municipio_id ? Municipio::find($this->municipio_id)?->nombre : null;
    }

    protected function nombreAldeaCiudad(): ?string
    {
        $aldea = $this->aldea_id ? Aldea::find($this->aldea_id)?->nombre : null;
        $ciudad = $this->ciudad_id ? Ciudad::find($this->ciudad_id)?->nombre : null;

        return collect([$aldea, $ciudad])->filter()->implode(' / ') ?: null;
    }

    public function render(): View
    {
        $facultadesCentros = FacultadCentro::orderBy('nombre')->pluck('nombre', 'id');

        $carreras = $this->facultad_centro_id
            ? Carrera::query()
                ->where(function ($query) {
                    $query->where('facultad_centro_id', $this->facultad_centro_id)
                        ->orWhereHas('facultadCentros', fn ($q) => $q->where('centro_facultad.id', $this->facultad_centro_id));
                })
                ->orderBy('nombre')
                ->pluck('nombre', 'id')
            : collect();

        $departamentos = Departamento::orderBy('nombre')->pluck('nombre', 'id');

        $municipios = $this->departamento_id
            ? Municipio::where('departamento_id', $this->departamento_id)->orderBy('nombre')->pluck('nombre', 'id')
            : collect();

        $aldeas = $this->municipio_id
            ? Aldea::where('municipio_id', $this->municipio_id)->orderBy('nombre')->pluck('nombre', 'id')
            : collect();

        $ciudades = $this->municipio_id
            ? Ciudad::where('municipio_id', $this->municipio_id)->orderBy('nombre')->pluck('nombre', 'id')
            : collect();

        $docentes = Empleado::docentes()
            ->with(['user', 'categoria', 'departamento_academico'])
            ->orderBy('nombre_completo')
            ->limit(100)
            ->get();

        return view('livewire.proyectos.vinculacion.create-pps-servicio-social', [
            'facultadesCentros' => $facultadesCentros,
            'carreras' => $carreras,
            'departamentos' => $departamentos,
            'municipios' => $municipios,
            'aldeas' => $aldeas,
            'ciudades' => $ciudades,
            'docentes' => $docentes,
        ]);
    }
}
