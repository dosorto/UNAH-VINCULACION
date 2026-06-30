<?php

namespace App\Livewire\Proyectos\Vinculacion;

use App\Models\Demografia\Departamento;
use App\Models\Demografia\Municipio;
use App\Models\Personal\Empleado;
use App\Models\PpsServicioSocial;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\UnidadAcademica\Carrera;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\User;
use App\Services\PpsServicioSocial\PpsServicioSocialWorkflowService;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class CreatePpsServicioSocial extends Component
{
    use WithFileUploads;

    public int $currentStep = 1;
    public int $totalSteps = 9;
    public bool $bloquearNavegacionPasos = true;
    public bool $registroGuardado = false;
    public ?int $registroId = null;
    public string $estadoAutoGuardado = '';
    public bool $autoguardadoActivo = true;

    // Modal enviar a firmar
    public bool $showEnviarModal = false;
    public int $modalStep = 1;
    public array $modalEtapas = [];
    public array $modalDestinatarios = [];

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
    public string $modalidad_ejecucion = '';
    public string $region = '';
    public string $pais = '';
    public string $departamento_provincia = '';
    public ?int $departamento_id = null;
    public ?int $municipio_id = null;
    public string $municipio_texto = '';
    public string $aldea_ciudad = '';
    public string $aldea = '';
    public string $ciudad = '';
    public string $caserio = '';
    public string $pais_sede_principal = '';
    public string $departamento_provincia_sede_principal = '';
    public string $municipio_sede_principal = '';
    public string $aldea_ciudad_sede_principal = '';
    public string $horas_presenciales = '';
    public string $horas_teletrabajo = '';

    // Paso 5: Alcance de la PPS / Servicio Social
    public string $descripcion_tipo_pps = '';
    public string $descripcion_horas_tipo_pps_ss = '';
    public string $total_horas = '';
    public string $area_realizacion = '';
    public string $resumen_responsabilidades = '';

    // Paso 6: Institucion / Organizacion
    public string $institucion_nombre = '';
    public string $institucion_nacionalidad = '';
    public string $institucion_pais = '';
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
        'Practica Profesional Supervisada' => 'Práctica Profesional Supervisada',
        'Servicio Social' => 'Servicio Social',
    ];

    public array $instrumentoOpciones = [
        'carta_formal_solicitud' => 'Carta formal de solicitud a la unidad académica',
        'carta_intenciones' => 'Carta de intenciones con la UNAH',
        'convenio_marco' => 'Convenio marco con la UNAH',
    ];

    public array $modalidadOpciones = [
        'Presencial' => '100% presencial',
        'Hibrida' => 'Híbrida',
        '100% virtual' => 'Teletrabajo',
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

    public array $institucionNacionalidadOpciones = [
        'Nacional',
        'Internacional',
    ];

    public function updatedFacultadCentroId(): void
    {
        $this->carrera_id = null;
    }

    public function updatedDepartamentoId(): void
    {
        $this->municipio_id = null;
        $this->aldea = '';
        $this->ciudad = '';

        if (filled($this->departamento_id)) {
            $this->resetValidation('departamento_id');
        }
    }

    public function updatedMunicipioId(): void
    {
        $this->aldea = '';
        $this->ciudad = '';

        if (filled($this->municipio_id)) {
            $this->resetValidation('municipio_id');
        }
    }

    public function updatedTerritorioEjecucion(string $value): void
    {
        if ($value === 'Internacional') {
            $this->departamento_id = null;
            $this->municipio_id = null;
            $this->resetValidation('departamento_id');
            $this->resetValidation('municipio_id');
        }
    }

    public function updatedAldea(): void
    {
        $this->autoGuardarCampoTerritorialManual();
    }

    public function updatedCiudad(): void
    {
        $this->autoGuardarCampoTerritorialManual();
    }

    public function updatedAldeaCiudad(): void
    {
        $this->autoGuardarCampoTerritorialManual();
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

    public function updatedCartaFormalizacionArchivo(): void
    {
        if ($this->carta_formalizacion_archivo) {
            $this->carta_formalizacion_aplica = 'Si';
        }
    }

    public function updatedConvenioMarcoArchivo(): void
    {
        if ($this->convenio_marco_archivo) {
            $this->convenio_marco_aplica = 'Si';
        }
    }

    public function nextStep(): void
    {
        $this->resetErrorBag();

        if ($this->shouldLockStepNavigation()) {
            $this->validateCurrentStep();
        }

        if ($this->shouldLockStepNavigation() && !$this->autoGuardarBorrador()) {
            return;
        }

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

        $this->resetErrorBag();

        if ($this->shouldLockStepNavigation() && $step > $this->currentStep) {
            $blockedStep = $this->firstIncompleteStepBefore($step);

            if ($blockedStep !== null) {
                $this->currentStep = $blockedStep;
                $this->validateCurrentStep();

                return;
            }
        }

        $this->currentStep = $step;
    }

    public function guardarBorrador(): void
    {
        $this->resetErrorBag();

        if ($this->shouldLockStepNavigation()) {
            $this->validateCurrentStep();

            if ($this->errors()->isNotEmpty()) {
                return;
            }
        }

        $this->guardar();
    }

    public function abrirModalEnviar(): void
    {
        $this->resetErrorBag();

        if ($this->shouldLockStepNavigation()) {
            $blockedStep = $this->firstIncompleteStepBefore($this->totalSteps + 1);

            if ($blockedStep !== null) {
                $this->currentStep = $blockedStep;
                $this->validateCurrentStep();

                return;
            }
        }

        if ($this->shouldLockStepNavigation() && ! $this->autoGuardarBorrador()) {
            return;
        }

        $this->cargarEtapasModal();

        $this->modalStep = 1;
        $this->modalDestinatarios = [];
        $this->showEnviarModal = true;
    }

    public function modalSiguiente(): void
    {
        $etapa = $this->modalEtapas[$this->modalStep - 1] ?? null;

        if ($etapa && empty($this->modalDestinatarios[$etapa['id']])) {
            $this->addError('modal_destinatario_'.$this->modalStep, 'Debe seleccionar un destinatario para esta etapa.');
            return;
        }

        $this->resetErrorBag('modal_destinatario_'.$this->modalStep);
        $this->modalStep++;
    }

    public function modalAnterior(): void
    {
        if ($this->modalStep > 1) {
            $this->modalStep--;
        }
    }

    public function cancelarModal(): void
    {
        $this->showEnviarModal = false;
        $this->modalStep = 1;
        $this->modalEtapas = [];
        $this->modalDestinatarios = [];
        $this->resetErrorBag();
    }

    public function confirmarEnvio(): void
    {
        $this->resetErrorBag();

        try {
            $registro = $this->ensureRegistroBorrador();
            $payload = $this->payloadParcial();

            $payload['archivo_carta_formalizacion'] = $registro->archivo_carta_formalizacion;
            if ($this->carta_formalizacion_archivo) {
                $payload['archivo_carta_formalizacion'] = $this->carta_formalizacion_archivo->store('pps-servicio-social/documentos', 'public');
                $this->carta_formalizacion_aplica = 'Si';
            } elseif ($this->carta_formalizacion_aplica === 'No') {
                $payload['archivo_carta_formalizacion'] = null;
            }

            $payload['archivo_convenio_marco'] = $registro->archivo_convenio_marco;
            if ($this->convenio_marco_archivo) {
                $payload['archivo_convenio_marco'] = $this->convenio_marco_archivo->store('pps-servicio-social/documentos', 'public');
                $this->convenio_marco_aplica = 'Si';
            } elseif ($this->convenio_marco_aplica === 'No') {
                $payload['archivo_convenio_marco'] = null;
            }

            $payload['adjunta_carta_formalizacion'] = $this->carta_formalizacion_aplica === 'Si' || filled($payload['archivo_carta_formalizacion']);
            $payload['adjunta_convenio_marco'] = $this->convenio_marco_aplica === 'Si' || filled($payload['archivo_convenio_marco']);

            if (! empty($this->modalDestinatarios)) {
                $payload['destinatarios_emisor'] = $this->modalDestinatarios;
            }

            $registro->update($payload);

            $registro = app(PpsServicioSocialWorkflowService::class)
                ->enviarARevision($registro, auth()->id());
        } catch (\RuntimeException $e) {
            Notification::make()->title('Flujo PPS/SS incompleto')->body($e->getMessage())->warning()->send();
            $this->showEnviarModal = false;
            return;
        } catch (\Throwable $e) {
            report($e);
            Notification::make()->title('Error')->body('No se pudo enviar el registro a revisión. Intente nuevamente.')->danger()->send();
            $this->showEnviarModal = false;
            return;
        }

        $this->showEnviarModal = false;
        $this->registroGuardado = true;

        Notification::make()
            ->title('Registro enviado')
            ->body('El FORM-DVUS-014 fue enviado a revisión correctamente.')
            ->success()
            ->send();

        $this->redirectRoute('pps-servicio-social.show', ['id' => $registro->id]);
    }

    public function guardar(): void
    {
        $this->resetErrorBag();
        $this->validate($this->rules(), $this->messages(), $this->validationAttributes());

        if (!$this->autoGuardarBorrador()) {
            return;
        }

        try {
            $registro = $this->ensureRegistroBorrador();
            $payload = $this->payloadParcial();

            $payload['archivo_carta_formalizacion'] = $registro->archivo_carta_formalizacion;
            if ($this->carta_formalizacion_archivo) {
                $payload['archivo_carta_formalizacion'] = $this->carta_formalizacion_archivo->store('pps-servicio-social/documentos', 'public');
                $this->carta_formalizacion_aplica = 'Si';
            } elseif ($this->carta_formalizacion_aplica === 'No') {
                $payload['archivo_carta_formalizacion'] = null;
            }

            $payload['archivo_convenio_marco'] = $registro->archivo_convenio_marco;
            if ($this->convenio_marco_archivo) {
                $payload['archivo_convenio_marco'] = $this->convenio_marco_archivo->store('pps-servicio-social/documentos', 'public');
                $this->convenio_marco_aplica = 'Si';
            } elseif ($this->convenio_marco_aplica === 'No') {
                $payload['archivo_convenio_marco'] = null;
            }

            $payload['adjunta_carta_formalizacion'] = $this->carta_formalizacion_aplica === 'Si'
                || filled($payload['archivo_carta_formalizacion']);
            $payload['adjunta_convenio_marco'] = $this->convenio_marco_aplica === 'Si'
                || filled($payload['archivo_convenio_marco']);

            $registro->update($payload);
            $this->estadoAutoGuardado = 'guardado';
        } catch (\Throwable $e) {
            report($e);
            $this->estadoAutoGuardado = 'error';

            Notification::make()
                ->title('Error')
                ->body('No se pudo guardar el registro. Intente nuevamente.')
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Registro guardado')
            ->body('El FORM-DVUS-014 fue guardado correctamente como borrador.')
            ->success()
            ->send();

        $this->registroGuardado = true;
        $this->redirectRoute('inicio');
    }

    public function autoGuardarBorrador(): bool
    {
        if (!$this->autoguardadoActivo) {
            return true;
        }

        try {
            $this->estadoAutoGuardado = 'guardando';
            $registro = $this->ensureRegistroBorrador();
            $registro->update($this->payloadParcial());
            $this->estadoAutoGuardado = 'guardado';

            return true;
        } catch (\Throwable $e) {
            report($e);
            $this->estadoAutoGuardado = 'error';

            return false;
        }
    }

    protected function ensureRegistroBorrador(): PpsServicioSocial
    {
        if ($this->registroId) {
            $registro = PpsServicioSocial::findOrFail($this->registroId);

            if ($registro->estado !== 'borrador') {
                throw new \RuntimeException('Solo los registros en borrador pueden autoguardarse.');
            }

            return $registro;
        }

        $registro = PpsServicioSocial::create(array_merge($this->payloadParcial(), [
            'codigo_registro' => $this->generarCodigoRegistro(),
            'estado' => 'borrador',
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]));

        $this->registroId = $registro->id;
        $this->registroGuardado = true;

        return $registro;
    }

    protected function payloadParcial(): array
    {
        $fechaInicio = $this->fecha_inicio ?: now()->toDateString();
        $fechaFinalizacion = $this->fecha_finalizacion ?: $fechaInicio;

        return [
            'facultad_centro' => $this->textoBorrador($this->nombreFacultadCentro()),
            'carrera' => $this->textoBorrador($this->nombreCarrera()),
            'numero_cuenta' => $this->textoBorrador($this->numero_cuenta),
            'nombre_estudiante' => $this->textoBorrador($this->estudiante_nombre_completo),
            'celular_estudiante' => $this->textoBorrador($this->estudiante_celular),
            'correo_institucional' => $this->textoBorrador($this->estudiante_correo_institucional, 'pendiente@unah.edu.hn'),
            'correo_personal' => $this->estudiante_correo_personal ?: null,
            'tipo_pps_ss' => $this->textoBorrador($this->tipo_pps_ss),
            'fecha_inicio' => $fechaInicio,
            'fecha_finalizacion' => $fechaFinalizacion,
            'tipo_instrumento' => $this->instrumentoOpciones[$this->tipo_instrumento] ?? $this->textoBorrador($this->tipo_instrumento),
            'territorio_ejecucion' => $this->territorio_ejecucion ?: 'Nacional',
            'region' => $this->region ?: null,
            'pais' => $this->territorio_ejecucion === 'Nacional' ? 'Honduras' : ($this->pais ?: null),
            'departamento_provincia' => $this->departamento_provincia ?: null,
            'departamento' => $this->territorio_ejecucion === 'Nacional' ? $this->nombreDepartamento() : null,
            'municipio' => $this->territorio_ejecucion === 'Nacional' ? $this->nombreMunicipio() : ($this->municipio_texto ?: null),
            'aldea_ciudad' => $this->nombreAldeaCiudad(),
            'caserio' => $this->caserio ?: null,
            'pais_sede_principal' => $this->pais_sede_principal ?: null,
            'departamento_provincia_sede_principal' => $this->departamento_provincia_sede_principal ?: null,
            'municipio_sede_principal' => $this->municipio_sede_principal ?: null,
            'aldea_ciudad_sede_principal' => $this->aldea_ciudad_sede_principal ?: null,
            'descripcion_tipo_pps' => $this->descripcion_tipo_pps ?: null,
            'descripcion_horas_tipo_pps_ss' => $this->descripcion_horas_tipo_pps_ss ?: null,
            'total_horas' => max(1, (int) $this->total_horas),
            'horas_presenciales' => $this->horas_presenciales === '' ? null : max(0, (int) $this->horas_presenciales),
            'horas_teletrabajo' => $this->horas_teletrabajo === '' ? null : max(0, (int) $this->horas_teletrabajo),
            'area_realizacion' => $this->area_realizacion ?: null,
            'resumen_responsabilidades' => $this->resumen_responsabilidades ?: null,
            'modalidad_ejecucion' => $this->textoBorrador($this->modalidad_ejecucion),
            'nombre_institucion' => $this->textoBorrador($this->institucion_nombre),
            'institucion_nacionalidad' => $this->institucion_nacionalidad ?: null,
            'institucion_pais' => $this->institucion_pais ?: null,
            'compromisos_institucion' => $this->institucion_compromisos ?: null,
            'direccion_institucion' => $this->institucion_direccion ?: null,
            'representante_legal' => $this->institucion_representante ?: null,
            'telefono_representante' => $this->institucion_telefono ?: null,
            'correo_rrhh' => $this->institucion_correo_rrhh ?: null,
            'tipo_institucion' => $this->tipoInstitucionOpciones[$this->institucion_tipo] ?? ($this->institucion_tipo ?: null),
            'sector_institucion' => $this->sectorOpciones[$this->institucion_sector] ?? ($this->institucion_sector ?: null),
            'nombre_jefe_directo' => $this->textoBorrador($this->jefe_directo_nombre),
            'celular_jefe_directo' => $this->jefe_directo_celular ?: null,
            'correo_jefe_directo' => $this->jefe_directo_correo ?: null,
            'cargo_jefe_directo' => $this->jefe_directo_cargo ?: null,
            'grado_academico_jefe_directo' => $this->jefe_directo_grado ?: null,
            'nombre_docente_supervisor' => $this->textoBorrador($this->docente_supervisor_nombre),
            'numero_empleado_docente' => $this->docente_numero_empleado ?: null,
            'celular_docente' => $this->docente_celular ?: null,
            'correo_docente' => $this->docente_correo ?: null,
            'categoria_docente' => $this->docente_categoria ?: null,
            'departamento_docente' => $this->docente_departamento ?: null,
            'jornada_laboral_docente' => $this->docente_jornada ?: null,
            'ubicacion_cubiculo_docente' => $this->docente_cubiculo ?: null,
            'adjunta_carta_formalizacion' => $this->carta_formalizacion_aplica === 'Si' || (bool) $this->carta_formalizacion_archivo,
            'adjunta_convenio_marco' => $this->convenio_marco_aplica === 'Si' || (bool) $this->convenio_marco_archivo,
            'updated_by' => auth()->id(),
        ];
    }

    protected function textoBorrador(?string $value, string $fallback = 'Pendiente'): string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : $fallback;
    }

    protected function validateCurrentStep(): void
    {
        $rules = $this->rulesForStep($this->currentStep);

        if ($rules) {
            $this->validate($rules, $this->messages(), $this->validationAttributes());
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
                'tipo_pps_ss' => ['required', 'string', Rule::in($this->tipoPpsValoresPermitidos())],
                'fecha_inicio' => 'required|date',
                'fecha_finalizacion' => 'required|date|after_or_equal:fecha_inicio',
                'tipo_instrumento' => 'required|string|in:carta_formal_solicitud,carta_intenciones,convenio_marco',
                'territorio_ejecucion' => 'required|string|in:Nacional,Internacional',
            ],
            4 => [
                'modalidad_ejecucion' => ['required', 'string', Rule::in($this->modalidadValoresPermitidos())],
                'departamento_id' => 'required_if:territorio_ejecucion,Nacional|nullable|integer|exists:departamento,id',
                'municipio_id' => 'required_if:territorio_ejecucion,Nacional|nullable|integer|exists:municipio,id',
                'municipio_texto' => 'nullable|string|max:255',
                'region' => 'nullable|string|max:255',
                'pais' => 'nullable|string|max:255',
                'departamento_provincia' => 'nullable|string|max:255',
                'aldea_ciudad' => 'nullable|string|max:255',
                'aldea' => 'nullable|string|max:255',
                'ciudad' => 'nullable|string|max:255',
                'caserio' => 'nullable|string|max:255',
                'pais_sede_principal' => 'nullable|string|max:255',
                'departamento_provincia_sede_principal' => 'nullable|string|max:255',
                'municipio_sede_principal' => 'nullable|string|max:255',
                'aldea_ciudad_sede_principal' => 'nullable|string|max:255',
                'horas_presenciales' => 'nullable|integer|min:0',
                'horas_teletrabajo' => 'nullable|integer|min:0',
            ],
            5 => [
                'descripcion_tipo_pps' => 'nullable|string',
                'descripcion_horas_tipo_pps_ss' => 'nullable|string',
                'total_horas' => 'required|integer|min:1',
                'area_realizacion' => 'nullable|string|max:255',
                'resumen_responsabilidades' => 'nullable|string',
            ],
            6 => [
                'institucion_nombre' => 'required|string|max:255',
                'institucion_nacionalidad' => 'nullable|string|in:Nacional,Internacional',
                'institucion_pais' => 'nullable|string|max:255',
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
            4 => filled($this->modalidad_ejecucion)
                && ($this->territorio_ejecucion === 'Internacional'
                    || (filled($this->departamento_id) && filled($this->municipio_id))),
            5 => filled($this->total_horas),
            6 => filled($this->institucion_nombre),
            7 => filled($this->jefe_directo_nombre),
            8 => filled($this->docente_supervisor_nombre),
            9 => filled($this->carta_formalizacion_aplica) && filled($this->convenio_marco_aplica),
            default => false,
        };
    }

    protected function cargarEtapasModal(): void
    {
        $flujo = app(PpsServicioSocialWorkflowService::class)->obtenerFlujoActivo();

        if (! $flujo) {
            $this->modalEtapas = [];
            return;
        }

        $this->modalEtapas = $flujo->etapas
            ->where('activo', true)
            ->where('emisor_define_destinatario', true)
            ->sortBy('orden')
            ->map(function (FlujoAprobacionEtapa $etapa): array {
                $usuarios = [];

                if ($etapa->rol_revisor_id) {
                    $usuarios = User::query()
                        ->select(['id', 'name', 'email'])
                        ->whereHas('roles', fn ($q) => $q->where('roles.id', $etapa->rol_revisor_id))
                        ->orderBy('name')
                        ->get()
                        ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email])
                        ->all();
                }

                return [
                    'id' => $etapa->id,
                    'nombre' => $etapa->nombre,
                    'rol_nombre' => $etapa->rolRevisor?->name ?? 'Sin rol',
                    'usuarios' => $usuarios,
                ];
            })
            ->values()
            ->all();
    }

    public function shouldLockStepNavigation(): bool
    {
        return $this->bloquearNavegacionPasos;
    }

    public function firstIncompleteStepBefore(int $targetStep): ?int
    {
        $limit = min(max($targetStep, 1), $this->totalSteps);

        for ($step = 1; $step < $limit; $step++) {
            if (!$this->isStepComplete($step)) {
                return $step;
            }
        }

        return null;
    }

    public function canAccessStep(int $step): bool
    {
        return !$this->shouldLockStepNavigation()
            || $this->firstIncompleteStepBefore($step) === null;
    }

    public function shouldShowStepComplete(int $step): bool
    {
        return $this->isStepComplete($step) && $this->canAccessStep($step);
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
            'fecha_finalizacion' => 'fecha de finalización',
            'departamento_id' => 'departamento',
            'municipio_id' => 'municipio',
            'municipio_texto' => 'municipio',
            'region' => 'región',
            'pais' => 'país',
            'departamento_provincia' => 'departamento o provincia',
            'aldea_ciudad' => 'aldea o ciudad',
            'aldea' => 'aldea',
            'ciudad' => 'ciudad',
            'pais_sede_principal' => 'país de la sede principal',
            'departamento_provincia_sede_principal' => 'departamento o provincia de la sede principal',
            'municipio_sede_principal' => 'municipio de la sede principal',
            'aldea_ciudad_sede_principal' => 'aldea o ciudad de la sede principal',
            'total_horas' => 'total de horas',
            'horas_presenciales' => 'horas presenciales',
            'horas_teletrabajo' => 'horas de teletrabajo',
            'descripcion_horas_tipo_pps_ss' => 'descripción de las horas del tipo de PPS/SS',
            'modalidad_ejecucion' => 'modalidad de ejecución',
            'institucion_nombre' => 'institución u organización',
            'institucion_nacionalidad' => 'nacionalidad de la institución',
            'institucion_pais' => 'país de la institución',
            'jefe_directo_nombre' => 'jefe directo',
            'docente_supervisor_nombre' => 'docente supervisor',
            'territorio_ejecucion' => 'territorio de ejecución',
        ];
    }

    protected function messages(): array
    {
        return [
            'departamento_id.required_if' => 'El departamento es obligatorio cuando el territorio de ejecución es Nacional.',
            'municipio_id.required_if' => 'El municipio es obligatorio cuando el territorio de ejecución es Nacional.',
            'horas_presenciales.integer' => 'Las horas presenciales deben ser un número entero.',
            'horas_presenciales.min' => 'Las horas presenciales no pueden ser negativas.',
            'horas_teletrabajo.integer' => 'Las horas de teletrabajo deben ser un número entero.',
            'horas_teletrabajo.min' => 'Las horas de teletrabajo no pueden ser negativas.',
            'institucion_nacionalidad.in' => 'La nacionalidad de la institución debe ser Nacional o Internacional.',
        ];
    }

    public function tipoPpsEtiqueta(?string $value): string
    {
        return $this->tipoPpsOpciones[$value] ?? ($value ?: 'Pendiente');
    }

    public function modalidadEtiqueta(?string $value): string
    {
        $etiquetas = array_merge($this->modalidadOpciones, [
            '100% presencial' => '100% presencial',
            'Híbrida' => 'Híbrida',
            'Teletrabajo' => 'Teletrabajo',
        ]);

        return $etiquetas[$value] ?? ($value ?: 'Pendiente');
    }

    protected function tipoPpsValoresPermitidos(): array
    {
        return collect($this->tipoPpsOpciones)
            ->keys()
            ->merge(array_values($this->tipoPpsOpciones))
            ->unique()
            ->values()
            ->all();
    }

    protected function modalidadValoresPermitidos(): array
    {
        return collect($this->modalidadOpciones)
            ->keys()
            ->merge(array_values($this->modalidadOpciones))
            ->unique()
            ->values()
            ->all();
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
        $aldeaCiudad = trim($this->aldea_ciudad);

        if ($aldeaCiudad !== '') {
            return $aldeaCiudad;
        }

        $aldea = trim($this->aldea);
        $ciudad = trim($this->ciudad);

        return collect([$aldea, $ciudad])->filter()->implode(' / ') ?: null;
    }

    protected function autoGuardarCampoTerritorialManual(): void
    {
        if ($this->currentStep !== 4 || $this->territorio_ejecucion !== 'Nacional') {
            return;
        }

        $this->autoGuardarBorrador();
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
            'docentes' => $docentes,
        ]);
    }
}
