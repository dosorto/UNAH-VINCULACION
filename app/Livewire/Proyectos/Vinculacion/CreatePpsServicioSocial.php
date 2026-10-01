<?php

namespace App\Livewire\Proyectos\Vinculacion;

use App\Models\Demografia\Departamento;
use App\Models\Demografia\Municipio;
use App\Models\Demografia\Pais;
use App\Models\JornadaLaboral;
use App\Models\Personal\CategoriaEmpleado;
use App\Models\Personal\Empleado;
use App\Models\PpsDocumentoGenerado;
use App\Models\PpsInstitucion;
use App\Models\PpsServicioSocial;
use App\Models\UnidadAcademica\Carrera;
use App\Models\UnidadAcademica\DepartamentoAcademico;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\User;
use App\Services\Integraciones\IntegracionApiService;
use App\Services\PpsServicioSocial\PpsDocumentoGenerator;
use App\Services\PpsServicioSocial\PpsServicioSocialWorkflowService;
use App\Support\Notification;
use App\Support\PpsServicioSocial\PpsDocumentoRequirements;
use App\Support\PpsServicioSocial\TratamientoDestinatario;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * FORM-DVUS-014: registro de Práctica Profesional Supervisada o Servicio Social.
 *
 * Seis pasos que siguen el trámite: estudiante y práctica (I, II, 9 y 10); institución y
 * SOLICITUD DE PRÁCTICA (VI y 12), donde se genera la carta para la empresa; respuesta de la
 * institución: fechas, funciones y jefe inmediato (10, V y 11); ubicación y jornada (12 y IV);
 * formalización y docente supervisor (VI, IX y VII); y revisión y envío. Cada paso exige sus
 * campos y no se avanza sin completarlo; el borrador puede guardarse incompleto.
 */
class CreatePpsServicioSocial extends Component
{
    use WithFileUploads;

    // Valores permitidos para el grado academico del jefe directo (select restringido en el formulario)
    public const GRADO_ACADEMICO_JEFE_DIRECTO_OPCIONES = [
        'Secundaria completa',
        'Licenciatura',
        'Maestría',
        'Doctorado',
        'Postdoctorado',
    ];

    public const PASOS = [
        1 => 'Estudiante y práctica',
        2 => 'Institución y solicitud',
        3 => 'Respuesta de la institución',
        4 => 'Ubicación y jornada',
        5 => 'Formalización y supervisor',
        6 => 'Revisión y envío',
    ];

    public const PASO_INSTITUCION = 2;
    public const PASO_SOLICITUD = 2;
    public const PASO_UBICACION = 4;
    public const PASO_SUPERVISOR = 5;

    /** Jornada con la que se estiman las horas planificadas: 8 horas de lunes a viernes. */
    public const HORAS_POR_DIA_PLANIFICADO = 8;

    /** Propiedad del formulario => columna del catálogo de instituciones. */
    protected const CAMPOS_INSTITUCION = [
        'institucion_nombre' => 'nombre',
        'institucion_nacionalidad' => 'nacionalidad',
        'institucion_pais' => 'pais',
        'institucion_tipo' => 'tipo',
        'institucion_sector' => 'sector',
        'institucion_direccion' => 'direccion',
        'institucion_representante' => 'representante_legal',
        'institucion_telefono' => 'telefono',
        'institucion_correo_rrhh' => 'correo_rrhh',
    ];

    /** Datos del supervisor que vienen del empleado; solo se escriben si el empleado no los tiene. */
    protected const CAMPOS_DOCENTE_SISTEMA = [
        'docente_supervisor_nombre',
        'docente_numero_empleado',
        'docente_celular',
        'docente_correo',
        'docente_categoria',
        'docente_departamento',
    ];

    public int $currentStep = 1;
    public int $totalSteps = 6;
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

    // Paso 1: Estudiante y práctica (unidad académica, tipo y total de horas)
    public ?int $facultad_centro_id = null;
    public ?int $carrera_id = null;
    public string $tipo_pps_ss = '';
    public string $fecha_inicio = '';
    public string $fecha_finalizacion = '';

    // Paso 1: Datos del estudiante
    public string $numero_cuenta = '';
    public string $estudiante_nombre_completo = '';
    public string $estudiante_celular = '';
    public string $estudiante_correo_institucional = '';
    public string $estudiante_correo_personal = '';
    public bool $estudianteConsultado = false;

    // Pasos 2 (modalidad) y 4: Ubicación y jornada
    public string $territorio_ejecucion = 'Nacional';
    public string $modalidad_ejecucion = '';
    public string $region = '';
    // Práctica internacional: país del catálogo; su nombre se guarda en «pais».
    public ?int $pais_id = null;
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
    // Sede principal (teletrabajo): se elige del catálogo y se guarda su nombre en los campos de texto.
    public ?int $pais_sede_id = null;
    public ?int $departamento_sede_id = null;
    public ?int $municipio_sede_id = null;
    public string $horas_presenciales = '';
    public string $horas_teletrabajo = '';

    // Paso 3: Respuesta de la institución: fechas y alcance (el total de horas va en el paso 1)
    public string $descripcion_tipo_pps = '';
    public string $descripcion_horas_tipo_pps_ss = '';
    public string $total_horas = '';
    public string $area_realizacion = '';
    public string $resumen_responsabilidades = '';

    // Paso 2: Institución (del catálogo) y destinatario; paso 3: jefe directo; paso 5: instrumento
    public ?int $pps_institucion_id = null;
    public $institucionBuscadaId = null;
    // null: aún no elige; 'existente': datos del catálogo, solo lectura; 'nueva': se crea en el catálogo.
    #[Locked]
    public ?string $modoInstitucion = null;
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
    // Destinatario de la solicitud de práctica: quien recibe la carta en la institución.
    public string $destinatario_tratamiento = '';
    public string $destinatario_nombre = '';
    public string $destinatario_cargo = '';
    // Solicitud de práctica: lugar de emisión. La firma el coordinador que llena el formulario.
    public string $solicitud_lugar = '';
    // Versión de la solicitud que muestra el visor; sin elegir, la más reciente.
    public ?int $solicitudVisibleId = null;
    public string $tipo_instrumento = '';
    public string $jefe_directo_nombre = '';
    public string $jefe_directo_celular = '';
    public string $jefe_directo_correo = '';
    public string $jefe_directo_cargo = '';
    public string $jefe_directo_grado = '';

    // Paso 5: Docente supervisor
    public ?int $docente_supervisor_id = null;
    public string $docenteBusqueda = '';
    #[Locked]
    public array $docenteCamposDelSistema = [];
    public string $docente_supervisor_nombre = '';
    public string $docente_numero_empleado = '';
    public string $docente_celular = '';
    public string $docente_correo = '';
    public string $docente_categoria = '';
    public string $docente_departamento = '';
    public string $docente_jornada = '';
    public string $docente_cubiculo = '';

    // Documentos adjuntos (IX): se cargan en el paso 5, junto al instrumento que formaliza la práctica.
    public string $carta_formalizacion_aplica = 'No';
    public $carta_formalizacion_archivo = null;
    public string $convenio_marco_aplica = 'No';
    public $convenio_marco_archivo = null;
    public ?string $archivo_carta_formalizacion_actual = null;
    public ?string $archivo_convenio_marco_actual = null;

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
        'Hibrida' => 'Híbrida (presencial + teletrabajo)',
        '100% virtual' => 'Teletrabajo',
    ];

    public array $tipoInstitucionOpciones = PpsInstitucion::TIPOS;

    public array $sectorOpciones = PpsInstitucion::SECTORES;

    public array $institucionNacionalidadOpciones = PpsInstitucion::NACIONALIDADES;

    /** Completitud de cada paso dentro de la petición (la vista la consulta varias veces). */
    private array $pasosCompletos = [];

    /** Si el país elegido tiene departamentos en el catálogo (se consulta varias veces por petición). */
    private ?bool $paisConDepartamentos = null;

    private ?bool $paisSedeConDepartamentos = null;

    public function updatedFacultadCentroId(): void
    {
        $this->carrera_id = null;
    }

    public function updatedNumeroCuenta(): void
    {
        $this->estudianteConsultado = false;
        $this->resetErrorBag('numero_cuenta');
    }

    public function updatedFechaInicio(?string $value): void
    {
        if (! filled($value) || filled($this->fecha_finalizacion)) {
            return;
        }

        try {
            $this->fecha_finalizacion = \Illuminate\Support\Carbon::parse($value)
                ->addMonthsNoOverflow(5)
                ->format('Y-m-d');
        } catch (\Throwable) {
            // La validación del paso informará si la fecha ingresada no es válida.
        }
    }

    /** Días de lunes a viernes entre las fechas de ejecución, ambas incluidas. */
    public function diasHabilesPlanificados(): ?int
    {
        if (! filled($this->fecha_inicio) || ! filled($this->fecha_finalizacion)) {
            return null;
        }

        try {
            $inicio = \Illuminate\Support\Carbon::parse($this->fecha_inicio)->startOfDay();
            $fin = \Illuminate\Support\Carbon::parse($this->fecha_finalizacion)->startOfDay();
        } catch (\Throwable) {
            return null;
        }

        if ($fin->lt($inicio)) {
            return null;
        }

        // Cada semana completa aporta 5 días; solo se recorren los días sobrantes.
        $dias = (int) $inicio->diffInDays($fin) + 1;
        $habiles = intdiv($dias, 7) * 5;
        $dia = $inicio->copy()->addDays($dias - $dias % 7);

        for ($i = 0; $i < $dias % 7; $i++, $dia->addDay()) {
            if (! $dia->isWeekend()) {
                $habiles++;
            }
        }

        return $habiles;
    }

    public function horasPlanificadas(): ?int
    {
        $dias = $this->diasHabilesPlanificados();

        return $dias === null ? null : $dias * self::HORAS_POR_DIA_PLANIFICADO;
    }

    public function limpiarErrorBusquedaEstudiante(): void
    {
        $this->resetErrorBag('numero_cuenta');
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
        // Nacional e internacional usan listas de departamentos distintas.
        $this->pais_id = null;
        $this->pais = '';
        $this->limpiarDepartamentoYMunicipio();

        $this->pais_sede_id = null;
        $this->pais_sede_principal = $value === 'Internacional' ? '' : 'Honduras';
        $this->paisSedeConDepartamentos = null;
        $this->limpiarDepartamentoYMunicipioSede();
    }

    public function updatedPaisSedeId(): void
    {
        $this->pais_sede_principal = (string) (Pais::find($this->pais_sede_id)?->nombre ?? '');
        $this->paisSedeConDepartamentos = null;
        $this->limpiarDepartamentoYMunicipioSede();
    }

    public function updatedDepartamentoSedeId(): void
    {
        $this->departamento_provincia_sede_principal = (string) (Departamento::find($this->departamento_sede_id)?->nombre ?? '');
        $this->municipio_sede_id = null;
        $this->municipio_sede_principal = '';
    }

    public function updatedMunicipioSedeId(): void
    {
        $this->municipio_sede_principal = (string) (Municipio::find($this->municipio_sede_id)?->nombre ?? '');
    }

    protected function limpiarDepartamentoYMunicipioSede(): void
    {
        $this->departamento_sede_id = null;
        $this->municipio_sede_id = null;
        $this->departamento_provincia_sede_principal = '';
        $this->municipio_sede_principal = '';
        $this->resetValidation(['departamento_sede_id', 'municipio_sede_id', 'departamento_provincia_sede_principal', 'municipio_sede_principal']);
    }

    public function updatedPaisId(): void
    {
        $this->pais = (string) (Pais::find($this->pais_id)?->nombre ?? '');
        $this->paisConDepartamentos = null;
        $this->limpiarDepartamentoYMunicipio();
    }

    protected function limpiarDepartamentoYMunicipio(): void
    {
        $this->departamento_id = null;
        $this->municipio_id = null;
        $this->departamento_provincia = '';
        $this->municipio_texto = '';
        $this->resetValidation(['departamento_id', 'municipio_id', 'departamento_provincia', 'municipio_texto']);
    }

    /**
     * Departamento y municipio se eligen del catálogo en Honduras y en los países que ya tienen
     * departamentos registrados; en los demás se escriben.
     */
    public function usaCatalogoDepartamentos(): bool
    {
        if ($this->territorio_ejecucion !== 'Internacional') {
            return true;
        }

        if (! $this->pais_id) {
            return false;
        }

        return $this->paisConDepartamentos ??= Departamento::where('pais_id', $this->pais_id)->exists();
    }

    /** Igual que en la práctica presencial, para la sede principal del teletrabajo. */
    public function usaCatalogoDepartamentosSede(): bool
    {
        if ($this->territorio_ejecucion !== 'Internacional') {
            return true;
        }

        if (! $this->pais_sede_id) {
            return false;
        }

        return $this->paisSedeConDepartamentos ??= Departamento::where('pais_id', $this->pais_sede_id)->exists();
    }

    protected function honduras(): ?int
    {
        return Pais::where('nombre', 'Honduras')->value('id');
    }

    public function buscarEstudiante(IntegracionApiService $integraciones): void
    {
        $this->resetErrorBag();
        $this->estudianteConsultado = false;

        $cuenta = preg_replace('/\s+/u', '', trim($this->numero_cuenta));

        if ($cuenta === '' || ! ctype_digit($cuenta)) {
            $this->addError('numero_cuenta', 'Ingrese un número de cuenta válido.');
            return;
        }

        try {
            $resultado = $integraciones->buscarEstudiantePorCuenta($cuenta);

            if (! ($resultado['ok'] ?? false)) {
                $this->addError('numero_cuenta', $resultado['mensaje'] ?? 'No se encontró el estudiante.');
                return;
            }

            $datos = $resultado['datos'] ?? [];
            $this->numero_cuenta = (string) ($datos['numero_cuenta'] ?? $cuenta);
            $this->estudiante_nombre_completo = (string) ($datos['nombre_completo'] ?? '');
            $this->estudiante_correo_institucional = (string) ($datos['correo_institucional'] ?? '');

            $this->estudianteConsultado = true;
        } catch (\Throwable $e) {
            report($e);
            $this->addError('numero_cuenta', 'No fue posible consultar la integración de estudiantes.');
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

    // ─── Institución (paso 2) ──────────────────────────────────────────────────

    public function usarInstitucionSeleccionada(): void
    {
        $this->resetErrorBag(['institucionBuscadaId', 'pps_institucion_id']);
        $institucion = filled($this->institucionBuscadaId) ? PpsInstitucion::find($this->institucionBuscadaId) : null;

        if (! $institucion) {
            $this->addError('institucionBuscadaId', 'Seleccione una institución de la lista.');
            return;
        }

        $this->aplicarInstitucion($institucion);
        $this->autoGuardarBorrador();
    }

    public function crearInstitucionNueva(): void
    {
        $this->resetErrorBag();
        $this->institucionBuscadaId = null;
        $this->pps_institucion_id = null;

        foreach (array_keys(self::CAMPOS_INSTITUCION) as $propiedad) {
            $this->{$propiedad} = '';
        }

        $this->modoInstitucion = 'nueva';
    }

    /** Crea la institución en el catálogo; si ya existe (mismo nombre) se debe usar la del catálogo. */
    public function guardarInstitucionNueva(): void
    {
        foreach (array_keys(self::CAMPOS_INSTITUCION) as $propiedad) {
            $this->{$propiedad} = trim((string) $this->{$propiedad});
        }

        $this->validate($this->rulesInstitucionNueva(), $this->messages(), $this->validationAttributes());

        $existente = PpsInstitucion::where('nombre', $this->institucion_nombre)->first();

        if ($existente) {
            $this->addError('institucion_nombre', "Ya existe la institución «{$existente->nombre}». Búsquela en la lista y presione «Usar seleccionada».");
            return;
        }

        $datos = collect(self::CAMPOS_INSTITUCION)
            ->mapWithKeys(fn (string $columna, string $propiedad) => [$columna => $this->{$propiedad} !== '' ? $this->{$propiedad} : null])
            ->all();

        if ($datos['nacionalidad'] === 'Nacional') {
            $datos['pais'] = null;
        }

        $this->aplicarInstitucion(PpsInstitucion::create($datos));
        $this->autoGuardarBorrador();
    }

    public function cambiarInstitucion(): void
    {
        $this->crearInstitucionNueva();
        $this->modoInstitucion = null;
    }

    protected function aplicarInstitucion(PpsInstitucion $institucion): void
    {
        $this->pps_institucion_id = $institucion->id;
        $this->institucionBuscadaId = null;

        foreach (self::CAMPOS_INSTITUCION as $propiedad => $columna) {
            $this->{$propiedad} = (string) ($institucion->{$columna} ?? '');
        }

        $this->modoInstitucion = 'existente';
    }

    protected function rulesInstitucionNueva(): array
    {
        $reglas = [];

        foreach (PpsInstitucion::reglas('institucion_') as $clave => $regla) {
            // El catálogo llama «representante_legal» a lo que el formulario llama «institucion_representante».
            $reglas[$clave === 'institucion_representante_legal' ? 'institucion_representante' : $clave] = $regla;
        }

        return $reglas;
    }

    // ─── Docente supervisor (paso 5) ───────────────────────────────────────────

    public function seleccionarDocente(int $empleadoId): void
    {
        $this->docente_supervisor_id = $empleadoId;
        $this->updatedDocenteSupervisorId($empleadoId);
        $this->docenteBusqueda = '';
        $this->resetErrorBag('docente_supervisor_id');
        $this->autoGuardarBorrador();
    }

    public function cambiarDocente(): void
    {
        $this->docente_supervisor_id = null;
        $this->updatedDocenteSupervisorId(null);
    }

    public function updatedDocenteSupervisorId($docenteId): void
    {
        $this->docenteCamposDelSistema = [];

        if (!$docenteId) {
            $this->reset([
                'docente_supervisor_nombre',
                'docente_numero_empleado',
                'docente_celular',
                'docente_correo',
                'docente_categoria',
                'docente_departamento',
                'docente_jornada',
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
        $this->marcarCamposDocenteDelSistema();

        // La jornada del supervisor es un dato distinto de la distribución
        // de horas de la PPS. Si el empleado ya tiene una jornada registrada,
        // la usamos como valor inicial solamente cuando sigue disponible en
        // el catálogo configurable.
        $jornada = trim((string) ($docente->jornada_laboral ?? ''));
        $this->docente_jornada = in_array($jornada, $this->jornadasLaboralesValidas(), true)
            ? $jornada
            : '';
    }

    /** Los datos que el empleado ya tiene quedan de solo lectura; los que le faltan se escriben. */
    protected function marcarCamposDocenteDelSistema(): void
    {
        $this->docenteCamposDelSistema = collect(self::CAMPOS_DOCENTE_SISTEMA)
            ->filter(fn (string $campo) => filled($this->{$campo}))
            ->values()
            ->all();
    }

    // ─── Documentos adjuntos (paso 5) ──────────────────────────────────────────

    public function updatedCartaFormalizacionArchivo(): void
    {
        $this->guardarDocumentoAlMomento('carta_formalizacion_archivo', 'carta_formalizacion_aplica');
    }

    public function updatedConvenioMarcoArchivo(): void
    {
        $this->guardarDocumentoAlMomento('convenio_marco_archivo', 'convenio_marco_aplica');
    }

    /** El convenio marco solo se adjunta «en el caso de tenerse»: se puede quitar. */
    public function quitarConvenioMarco(): void
    {
        $this->convenio_marco_archivo = null;
        $this->archivo_convenio_marco_actual = null;
        $this->convenio_marco_aplica = 'No';
        $this->guardarDocumentosEnBorrador();
    }

    /**
     * El archivo se guarda en el borrador en cuanto se carga: si la persona sale o recarga la
     * página, el documento sigue ahí.
     */
    protected function guardarDocumentoAlMomento(string $propiedad, string $aplica): void
    {
        if (! $this->{$propiedad}) {
            return;
        }

        $this->validateOnly($propiedad, $this->rulesArchivos(), $this->messages(), $this->validationAttributes());
        $this->{$aplica} = 'Si';
        $this->guardarDocumentosEnBorrador();
    }

    protected function guardarDocumentosEnBorrador(): void
    {
        if (! $this->puedeGuardarDocumentosAlMomento()) {
            return;
        }

        try {
            $registro = $this->ensureRegistroBorrador();
            $registro->update($this->payloadParcial() + $this->payloadArchivos($registro));
            $this->estadoAutoGuardado = 'guardado';
        } catch (\Throwable $e) {
            report($e);
            $this->estadoAutoGuardado = 'error';
            Notification::make()->title('No se pudo guardar el documento')->body('Intente cargarlo de nuevo.')->danger()->send();
        }
    }

    /** Los documentos se guardan al momento donde también se autoguarda el borrador. */
    protected function puedeGuardarDocumentosAlMomento(): bool
    {
        return $this->autoguardadoActivo;
    }

    // ─── Navegación ────────────────────────────────────────────────────────────

    public function nextStep(): void
    {
        $this->resetErrorBag();

        if ($this->shouldLockStepNavigation()) {
            $this->validateCurrentStep();
        }

        if ($this->shouldLockStepNavigation() && !$this->autoGuardarBorrador()) {
            return;
        }

        // Lo que sigue se llena con la respuesta de la institución a la solicitud.
        if ($this->shouldLockStepNavigation() && $this->currentStep === self::PASO_SOLICITUD && ! $this->solicitudGenerada()) {
            $this->addError('solicitud', 'Genere la solicitud de práctica antes de continuar.');

            return;
        }

        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
            $this->prepararPasoSolicitud();
        }
    }

    public function prevStep(): void
    {
        if ($this->currentStep > 1) {
            $this->resetErrorBag();
            $this->currentStep--;
            $this->prepararPasoSolicitud();
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
        $this->prepararPasoSolicitud();
    }

    // ─── Solicitud de práctica ─────────────────────────────────────────────────

    public function solicitudGenerada(): bool
    {
        return $this->registroId !== null && PpsDocumentoGenerado::query()
            ->where('pps_servicio_social_id', $this->registroId)
            ->where('tipo', PpsDocumentoGenerator::SOLICITUD)
            ->exists();
    }

    /** Al llegar al paso, propone el lugar de emisión de su última solicitud. */
    protected function prepararPasoSolicitud(): void
    {
        if ($this->currentStep !== self::PASO_SOLICITUD || filled($this->solicitud_lugar)) {
            return;
        }

        $anterior = PpsServicioSocial::query()
            ->where('created_by', auth()->id())
            ->when($this->registroId, fn ($query) => $query->whereKeyNot($this->registroId))
            ->whereNotNull('solicitud_lugar')
            ->latest('id')
            ->value('solicitud_lugar');

        $this->solicitud_lugar = (string) ($anterior ?: $this->lugarDelCentro());
    }

    /** «UNAH Campus Choluteca» → «Choluteca»; Ciudad Universitaria → Tegucigalpa. */
    protected function lugarDelCentro(): string
    {
        $campus = FacultadCentro::with('campus')->find($this->facultad_centro_id)?->campus;

        if (! $campus || $campus->siglas === 'CU') {
            return 'Tegucigalpa, M.D.C.';
        }

        return trim((string) preg_replace('/^UNAH\s+(Campus\s+)?/u', '', (string) $campus->nombre_campus));
    }

    /**
     * Genera la SOLICITUD DE PRÁCTICA con los pasos anteriores. La firma quien llena el
     * formulario; cada vez que se genera queda una nueva versión.
     */
    public function generarSolicitud(): void
    {
        $this->resetErrorBag();

        if (method_exists($this, 'esEdicionRevisor') && $this->esEdicionRevisor()) {
            $this->addError('solicitud', 'La solicitud la genera quien registra la práctica.');

            return;
        }

        for ($paso = 1; $paso <= self::PASO_SOLICITUD; $paso++) {
            $this->currentStep = $paso;
            $this->validateCurrentStep();
        }

        try {
            $registro = $this->ensureRegistroBorrador();
            $registro->update($this->payloadParcial());
            app(PpsDocumentoGenerator::class)->generarSolicitud($registro->fresh(), (int) auth()->id());
        } catch (\RuntimeException $e) {
            $this->addError('solicitud', $e->getMessage());

            return;
        } catch (\Throwable $e) {
            report($e);
            $this->addError('solicitud', 'No se pudo generar la solicitud. Intente nuevamente.');

            return;
        }

        $this->estadoAutoGuardado = 'guardado';
        $this->solicitudVisibleId = null;
        Notification::make()
            ->title('Solicitud generada')
            ->body('Descárguela y envíela a la institución. Con su respuesta complete los siguientes pasos.')
            ->success()
            ->send();
    }

    /** El borrador puede guardarse incompleto: solo se valida el formato de los archivos. */
    public function guardarBorrador(): void
    {
        $this->resetErrorBag();
        $this->guardar();
    }

    /** Antes de enviar, cada paso debe cumplir el formato; si uno falla, el formulario se queda ahí. */
    protected function validarTodosLosPasos(): void
    {
        for ($paso = 1; $paso <= $this->totalSteps; $paso++) {
            $this->currentStep = $paso;
            $this->validateCurrentStep();
        }
    }

    public function abrirModalEnviar(): void
    {
        $this->resetErrorBag();
        $this->validarTodosLosPasos();

        if (! $this->autoGuardarBorrador()) {
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
            $payload = $this->payloadParcial() + $this->payloadArchivos($registro);

            if (! empty($this->modalDestinatarios)) {
                $payload['destinatarios_emisor'] = $this->modalDestinatarios;
            }

            $registro->update($payload);

            $registro = app(PpsServicioSocialWorkflowService::class)
                ->enviarARevision($registro, auth()->id(), $this->modalDestinatarios);
        } catch (\RuntimeException $e) {
            Notification::make()->title('Flujo PPS/SS incompleto')->body($e->getMessage())->warning()->send();
            $this->showEnviarModal = false;
            return;
        } catch (\Throwable $e) {
            report($e);
            Log::error('Error enviando PPS/SS a revisión desde formulario', [
                'registro_id' => $this->registroId,
                'error' => $e->getMessage(),
                'exception' => $e::class,
            ]);
            Notification::make()->title('Error')->body('No se pudo enviar el registro a revisión. Detalle: '.$e->getMessage())->danger()->send();
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
        $this->validate($this->rulesArchivos(), $this->messages(), $this->validationAttributes());

        if (!$this->autoGuardarBorrador()) {
            return;
        }

        try {
            $registro = $this->ensureRegistroBorrador();
            $registro->update($this->payloadParcial() + $this->payloadArchivos($registro));
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

    /** Guarda los archivos nuevos y conserva (o quita) los ya cargados. */
    protected function payloadArchivos(PpsServicioSocial $registro): array
    {
        $carta = $this->archivo_carta_formalizacion_actual ?? $registro->archivo_carta_formalizacion;
        if ($this->carta_formalizacion_archivo) {
            $carta = $this->almacenarDocumento($this->carta_formalizacion_archivo);
        }

        $convenio = $this->convenio_marco_aplica === 'No' && ! $this->convenio_marco_archivo
            ? null
            : ($this->archivo_convenio_marco_actual ?? $registro->archivo_convenio_marco);
        if ($this->convenio_marco_archivo) {
            $convenio = $this->almacenarDocumento($this->convenio_marco_archivo);
        }

        $this->archivo_carta_formalizacion_actual = $carta;
        $this->archivo_convenio_marco_actual = $convenio;
        $this->carta_formalizacion_archivo = null;
        $this->convenio_marco_archivo = null;
        $this->carta_formalizacion_aplica = filled($carta) ? 'Si' : 'No';
        $this->convenio_marco_aplica = filled($convenio) ? 'Si' : 'No';

        return [
            'archivo_carta_formalizacion' => $carta,
            'archivo_convenio_marco' => $convenio,
            'adjunta_carta_formalizacion' => filled($carta),
            'adjunta_convenio_marco' => filled($convenio),
        ];
    }

    /** Carpeta única por archivo y nombre original saneado, para mostrarlo tal como se subió. */
    protected function almacenarDocumento($archivo): string
    {
        $nombre = Str::slug(pathinfo($archivo->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'documento';
        $extension = strtolower($archivo->getClientOriginalExtension() ?: $archivo->extension() ?: 'pdf');

        return $archivo->storeAs('pps-servicio-social/documentos/'.Str::uuid(), "{$nombre}.{$extension}", 'public');
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
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]));

        $this->registroId = $registro->id;
        $this->registroGuardado = true;
        // Sin navegar: la barra de direcciones pasa a la edición del borrador para que recargar no lo pierda.
        $this->js('history.replaceState(history.state, "", '.json_encode(route('pps-servicio-social.edit', ['id' => $registro->id])).')');

        return $registro;
    }

    protected function payloadParcial(): array
    {
        $fechaInicio = $this->fecha_inicio ?: PpsDocumentoRequirements::BORRADOR_FECHA;
        $fechaFinalizacion = $this->fecha_finalizacion ?: $fechaInicio;

        // Con la modalidad elegida, los datos del bloque que no aplica no se guardan.
        $modalidadElegida = $this->modalidadClave() !== '';
        $presencial = ! $modalidadElegida || $this->aplicaPresencial();
        $teletrabajo = ! $modalidadElegida || $this->aplicaTeletrabajo();
        $nacional = $this->territorio_ejecucion !== 'Internacional';
        $catalogo = $this->usaCatalogoDepartamentos();

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
            // 14.1 del formato (Nacional / Extranjero) repite el territorio de ejecución (ítem 12).
            'region' => $presencial ? ($nacional ? 'Nacional' : 'Extranjero') : null,
            'pais' => $presencial ? ($nacional ? 'Honduras' : ($this->pais ?: null)) : null,
            'departamento_provincia' => $presencial && ! $nacional
                ? ($catalogo ? $this->nombreDepartamento() : ($this->departamento_provincia ?: null))
                : null,
            'departamento' => $presencial && $nacional ? $this->nombreDepartamento() : null,
            'municipio' => $presencial
                ? ($catalogo ? $this->nombreMunicipio() : ($this->municipio_texto ?: null))
                : null,
            'aldea_ciudad' => $presencial ? $this->nombreAldeaCiudad() : null,
            'caserio' => $presencial ? ($this->caserio ?: null) : null,
            'pais_sede_principal' => $teletrabajo ? ($nacional ? 'Honduras' : ($this->pais_sede_principal ?: null)) : null,
            'departamento_provincia_sede_principal' => $teletrabajo ? ($this->departamento_provincia_sede_principal ?: null) : null,
            'municipio_sede_principal' => $teletrabajo ? ($this->municipio_sede_principal ?: null) : null,
            'aldea_ciudad_sede_principal' => $teletrabajo ? ($this->aldea_ciudad_sede_principal ?: null) : null,
            'descripcion_tipo_pps' => $this->descripcion_tipo_pps ?: null,
            'descripcion_horas_tipo_pps_ss' => $this->descripcion_horas_tipo_pps_ss ?: null,
            'total_horas' => $this->total_horas === '' ? 0 : max(0, (int) $this->total_horas),
            'horas_presenciales' => ! $presencial || $this->horas_presenciales === '' ? null : max(0, (int) $this->horas_presenciales),
            'horas_teletrabajo' => ! $teletrabajo || $this->horas_teletrabajo === '' ? null : max(0, (int) $this->horas_teletrabajo),
            'area_realizacion' => $this->area_realizacion ?: null,
            'resumen_responsabilidades' => $this->resumen_responsabilidades ?: null,
            'modalidad_ejecucion' => $this->textoBorrador($this->modalidad_ejecucion),
            'pps_institucion_id' => $this->pps_institucion_id,
            'nombre_institucion' => $this->textoBorrador($this->institucion_nombre),
            'destinatario_tratamiento' => $this->destinatario_tratamiento ?: null,
            'destinatario_nombre' => $this->destinatario_nombre ?: null,
            'destinatario_cargo' => $this->destinatario_cargo ?: null,
            'solicitud_lugar' => $this->solicitud_lugar ?: null,
            'institucion_nacionalidad' => $this->institucion_nacionalidad ?: null,
            'institucion_pais' => $this->institucion_nacionalidad === 'Nacional' ? 'Honduras' : ($this->institucion_pais ?: null),
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
            'adjunta_carta_formalizacion' => $this->tieneCartaFormalizacion(),
            'adjunta_convenio_marco' => $this->tieneConvenioMarco(),
            'updated_by' => auth()->id(),
        ];
    }

    protected function textoBorrador(?string $value, string $fallback = 'Pendiente'): string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : $fallback;
    }

    protected function valorParaFormulario(mixed $value, ?string $campo = null): string
    {
        return PpsDocumentoRequirements::isBlank($value, $campo)
            ? ''
            : trim((string) $value);
    }

    protected function fechaParaFormulario(?\DateTimeInterface $fecha): string
    {
        if (! $fecha || $fecha->format('Y-m-d') === PpsDocumentoRequirements::BORRADOR_FECHA) {
            return '';
        }

        return $fecha->format('Y-m-d');
    }

    protected function validateCurrentStep(): void
    {
        $rules = $this->rulesForStep($this->currentStep);

        if ($rules) {
            $this->validate($rules, $this->messages(), $this->validationAttributes());
        }
    }

    protected function jornadasLaboralesValidas(): array
    {
        return JornadaLaboral::where('activo', true)
            ->orderBy('orden')
            ->orderBy('hora_inicio')
            ->get()
            ->pluck('etiqueta')
            ->all();
    }

    /** Clave de la modalidad elegida (el registro puede traer la etiqueta). */
    public function modalidadClave(): string
    {
        $valor = trim($this->modalidad_ejecucion);

        if ($valor === '' || array_key_exists($valor, $this->modalidadOpciones)) {
            return $valor;
        }

        return match (Str::of($valor)->ascii()->lower()->value()) {
            '100% presencial', 'presencial' => 'Presencial',
            'hibrida', 'hibrida (presencial + teletrabajo)', 'mixta' => 'Hibrida',
            'teletrabajo', 'virtual', '100% virtual' => '100% virtual',
            default => $valor,
        };
    }

    public function aplicaPresencial(): bool
    {
        return in_array($this->modalidadClave(), ['Presencial', 'Hibrida'], true);
    }

    public function aplicaTeletrabajo(): bool
    {
        return in_array($this->modalidadClave(), ['Hibrida', '100% virtual'], true);
    }

    public function tieneCartaFormalizacion(): bool
    {
        return (bool) $this->carta_formalizacion_archivo || filled($this->archivo_carta_formalizacion_actual);
    }

    public function tieneConvenioMarco(): bool
    {
        return (bool) $this->convenio_marco_archivo
            || ($this->convenio_marco_aplica !== 'No' && filled($this->archivo_convenio_marco_actual));
    }

    protected function rulesForStep(int $step): array
    {
        $texto = fn (int $max = 255) => ['required', 'string', "max:{$max}"];

        return match ($step) {
            1 => [
                'facultad_centro_id' => ['required', 'integer', 'exists:centro_facultad,id'],
                'carrera_id' => ['required', 'integer', 'exists:carrera,id'],
                'tipo_pps_ss' => ['required', 'string', Rule::in($this->tipoPpsValoresPermitidos())],
                'total_horas' => ['required', 'integer', 'min:1'],
                'numero_cuenta' => ['required', 'string', 'max:50', 'regex:/^\d+$/'],
                'estudiante_nombre_completo' => $texto(),
                'estudiante_celular' => $texto(30),
                'estudiante_correo_institucional' => ['required', 'email', 'max:255'],
                'estudiante_correo_personal' => ['required', 'email', 'max:255'],
            ],
            2 => [
                'pps_institucion_id' => ['required', 'integer', Rule::exists('pps_instituciones', 'id')->whereNull('deleted_at')],
                'destinatario_tratamiento' => ['required', 'string', Rule::in(TratamientoDestinatario::opciones())],
                'destinatario_nombre' => $texto(),
                'destinatario_cargo' => $texto(),
                'modalidad_ejecucion' => ['required', 'string', Rule::in($this->modalidadValoresPermitidos())],
                'solicitud_lugar' => $texto(),
            ],
            3 => [
                'fecha_inicio' => ['required', 'date'],
                'fecha_finalizacion' => ['required', 'date', 'after_or_equal:fecha_inicio'],
                'descripcion_tipo_pps' => $texto(5000),
                'descripcion_horas_tipo_pps_ss' => ['nullable', 'string', 'max:500'],
                'area_realizacion' => $texto(),
                'resumen_responsabilidades' => $texto(5000),
                'jefe_directo_nombre' => $texto(),
                'jefe_directo_celular' => $texto(30),
                'jefe_directo_correo' => ['required', 'email', 'max:255'],
                'jefe_directo_cargo' => $texto(),
                'jefe_directo_grado' => ['required', 'string', Rule::in(self::GRADO_ACADEMICO_JEFE_DIRECTO_OPCIONES)],
            ],
            4 => $this->rulesUbicacion(),
            5 => array_merge([
                'institucion_compromisos' => $texto(5000),
                'tipo_instrumento' => ['required', 'string', Rule::in(array_keys($this->instrumentoOpciones))],
                'docente_supervisor_id' => ['required', 'integer', Rule::exists((new Empleado)->getTable(), 'id')],
                'docente_supervisor_nombre' => $texto(),
                'docente_numero_empleado' => $texto(50),
                'docente_celular' => $texto(30),
                'docente_correo' => ['required', 'email', 'max:255'],
                'docente_categoria' => $texto(),
                'docente_departamento' => $texto(),
                'docente_jornada' => ['required', 'string', Rule::in($this->jornadasLaboralesValidas())],
                'docente_cubiculo' => $texto(),
            ], $this->rulesArchivos(true)),
            default => [],
        };
    }

    /** Ítem 12 y sección IV: cada bloque se exige solo si la modalidad lo usa. */
    protected function rulesUbicacion(): array
    {
        $presencial = $this->aplicaPresencial();
        $teletrabajo = $this->aplicaTeletrabajo();
        $nacional = $this->territorio_ejecucion !== 'Internacional';
        $catalogo = $this->usaCatalogoDepartamentos();
        $catalogoSede = $this->usaCatalogoDepartamentosSede();
        $texto = fn (bool $exigir) => [Rule::requiredIf($exigir), 'nullable', 'string', 'max:255'];

        return [
            'territorio_ejecucion' => ['required', 'string', 'in:Nacional,Internacional'],
            'pais_id' => [Rule::requiredIf($presencial && ! $nacional), 'nullable', 'integer', 'exists:pais,id'],
            'departamento_id' => [Rule::requiredIf($presencial && $catalogo), 'nullable', 'integer', 'exists:departamento,id'],
            'municipio_id' => [Rule::requiredIf($presencial && $catalogo), 'nullable', 'integer', 'exists:municipio,id'],
            'departamento_provincia' => $texto($presencial && ! $nacional && $this->pais_id && ! $catalogo),
            'municipio_texto' => $texto($presencial && ! $nacional && $this->pais_id && ! $catalogo),
            'aldea_ciudad' => $texto($presencial),
            'caserio' => $texto($presencial),
            'horas_presenciales' => [Rule::requiredIf($presencial), 'nullable', 'integer', 'min:1'],
            'pais_sede_id' => [Rule::requiredIf($teletrabajo && ! $nacional), 'nullable', 'integer', 'exists:pais,id'],
            'departamento_sede_id' => [Rule::requiredIf($teletrabajo && $catalogoSede), 'nullable', 'integer', 'exists:departamento,id'],
            'municipio_sede_id' => [Rule::requiredIf($teletrabajo && $catalogoSede), 'nullable', 'integer', 'exists:municipio,id'],
            'departamento_provincia_sede_principal' => $texto($teletrabajo && ! $nacional && $this->pais_sede_id && ! $catalogoSede),
            'municipio_sede_principal' => $texto($teletrabajo && ! $nacional && $this->pais_sede_id && ! $catalogoSede),
            'aldea_ciudad_sede_principal' => $texto($teletrabajo),
            'horas_teletrabajo' => [Rule::requiredIf($teletrabajo), 'nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * Sección IX. La carta de formalización es obligatoria; el convenio marco solo si el
     * instrumento que formaliza la PPS/SS es un convenio marco («en el caso de tenerse»).
     */
    protected function rulesArchivos(bool $exigirDocumentos = false): array
    {
        $archivo = ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'];

        return [
            'carta_formalizacion_archivo' => array_merge(
                [Rule::requiredIf($exigirDocumentos && blank($this->archivo_carta_formalizacion_actual))],
                $archivo
            ),
            'convenio_marco_archivo' => array_merge(
                [Rule::requiredIf($exigirDocumentos && $this->tipo_instrumento === 'convenio_marco' && ! $this->tieneConvenioMarco())],
                $archivo
            ),
        ];
    }

    protected function rules(): array
    {
        return collect(range(1, $this->totalSteps))
            ->flatMap(fn (int $step) => $this->rulesForStep($step))
            ->all();
    }

    /**
     * Un paso está completo con la misma regla con la que se valida al avanzar. La revisión
     * final está completa cuando lo están todos los pasos anteriores.
     */
    public function isStepComplete(int $step): bool
    {
        if ($step === $this->totalSteps) {
            return $this->pasosCompletos[$step] ??= collect(range(1, $this->totalSteps - 1))
                ->every(fn (int $paso) => $this->isStepComplete($paso));
        }

        return $this->pasosCompletos[$step] ??= Validator::make($this->all(), $this->rulesForStep($step))->passes()
            && ($step !== self::PASO_SOLICITUD || $this->solicitudGenerada());
    }

    protected function cargarEtapasModal(): void
    {
        $registro = $this->registroId ? PpsServicioSocial::find($this->registroId) : null;

        if (! $registro) {
            $this->modalEtapas = [];
            return;
        }

        $this->modalEtapas = app(PpsServicioSocialWorkflowService::class)
            ->etapasQueRequierenDestinatario($registro)
            ->map(function (array $etapa): array {
                $usuarios = [];

                if ($etapa['rol_requerido']) {
                    $usuarios = User::query()
                        ->select(['id', 'name', 'email'])
                        ->whereHas('roles', fn ($q) => $q->where('roles.name', $etapa['rol_requerido']))
                        ->whereHas('empleado')
                        ->orderBy('name')
                        ->get()
                        ->filter(fn (User $u): bool => filled($u->email) && filter_var($u->email, FILTER_VALIDATE_EMAIL))
                        ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email])
                        ->all();
                }

                return [
                    'id' => $etapa['id'],
                    'nombre' => $etapa['nombre'],
                    'rol_nombre' => $etapa['rol_requerido'] ?? 'Sin rol',
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
            'tipo_pps_ss' => 'tipo (PPS o servicio social)',
            'fecha_inicio' => 'fecha de inicio',
            'fecha_finalizacion' => 'fecha de finalización',
            'numero_cuenta' => 'número de cuenta',
            'estudiante_nombre_completo' => 'nombre completo del estudiante',
            'estudiante_celular' => 'celular del estudiante',
            'estudiante_correo_institucional' => 'correo institucional',
            'estudiante_correo_personal' => 'correo personal',
            'territorio_ejecucion' => 'territorio de ejecución',
            'modalidad_ejecucion' => 'modalidad',
            'pais' => 'país',
            'pais_id' => 'país',
            'departamento_id' => 'departamento',
            'municipio_id' => 'municipio',
            'departamento_provincia' => 'departamento o provincia',
            'municipio_texto' => 'municipio',
            'aldea_ciudad' => 'aldea o ciudad',
            'caserio' => 'caserío',
            'horas_presenciales' => 'horas presenciales',
            'pais_sede_principal' => 'país de la sede principal',
            'pais_sede_id' => 'país de la sede principal',
            'departamento_sede_id' => 'departamento de la sede principal',
            'municipio_sede_id' => 'municipio de la sede principal',
            'departamento_provincia_sede_principal' => 'departamento o provincia de la sede principal',
            'municipio_sede_principal' => 'municipio de la sede principal',
            'aldea_ciudad_sede_principal' => 'aldea o ciudad de la sede principal',
            'horas_teletrabajo' => 'horas de teletrabajo',
            'descripcion_tipo_pps' => 'descripción del tipo de PPS',
            'total_horas' => 'total de horas',
            'descripcion_horas_tipo_pps_ss' => 'desglose de horas',
            'area_realizacion' => 'departamento o área',
            'resumen_responsabilidades' => 'resumen de responsabilidades y tareas',
            'pps_institucion_id' => 'institución',
            'institucionBuscadaId' => 'institución',
            'institucion_nombre' => 'nombre de la institución',
            'institucion_nacionalidad' => 'nacionalidad',
            'institucion_pais' => 'país',
            'institucion_tipo' => 'tipo de institución',
            'institucion_sector' => 'sector',
            'institucion_direccion' => 'dirección de la sede principal',
            'institucion_representante' => 'representante legal',
            'institucion_telefono' => 'teléfono',
            'institucion_correo_rrhh' => 'correo de recursos humanos',
            'institucion_compromisos' => 'compromisos asumidos por la institución',
            'tipo_instrumento' => 'tipo de instrumento',
            'jefe_directo_nombre' => 'nombre del jefe directo',
            'jefe_directo_celular' => 'celular del jefe directo',
            'jefe_directo_correo' => 'correo del jefe directo',
            'jefe_directo_cargo' => 'cargo del jefe directo',
            'jefe_directo_grado' => 'grado académico del jefe directo',
            'docente_supervisor_id' => 'docente supervisor',
            'docente_supervisor_nombre' => 'nombre del supervisor',
            'docente_numero_empleado' => 'número de empleado',
            'docente_celular' => 'celular del supervisor',
            'docente_correo' => 'correo del supervisor',
            'docente_categoria' => 'categoría',
            'docente_departamento' => 'departamento del supervisor',
            'docente_jornada' => 'jornada laboral',
            'docente_cubiculo' => 'ubicación del cubículo',
            'carta_formalizacion_archivo' => 'carta de formalización',
            'convenio_marco_archivo' => 'convenio marco',
        ];
    }

    protected function messages(): array
    {
        return [
            'numero_cuenta.regex' => 'El número de cuenta solo puede tener dígitos.',
            'fecha_finalizacion.after_or_equal' => 'La fecha de finalización debe ser igual o posterior a la de inicio.',
            'pps_institucion_id.required' => 'Seleccione la institución del catálogo o cree una nueva.',
            'docente_supervisor_id.required' => 'Busque y seleccione al docente supervisor.',
            'carta_formalizacion_archivo.required' => 'Adjunte la carta de formalización firmada por la contraparte.',
            'convenio_marco_archivo.required' => 'El instrumento es un convenio marco: adjúntelo.',
            'institucion_pais.required_if' => 'Indique el país de la institución internacional.',
            'institucion_pais.exists' => 'Seleccione un país de la lista.',
        ];
    }

    public function tipoPpsEtiqueta(?string $value): string
    {
        return $this->tipoPpsOpciones[$value] ?? ($value ?: 'Pendiente');
    }

    public function modalidadEtiqueta(?string $value): string
    {
        return $this->modalidadOpciones[$value] ?? ($value ?: 'Pendiente');
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
            ->merge(['100% presencial', 'Híbrida', 'Teletrabajo'])
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
        if ($this->currentStep !== self::PASO_UBICACION || $this->territorio_ejecucion !== 'Nacional') {
            return;
        }

        $this->autoGuardarBorrador();
    }

    public function render(): View
    {
        $this->pasosCompletos = [];

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

        $this->paisConDepartamentos = null;
        $paisDepartamentos = $this->territorio_ejecucion === 'Internacional' ? $this->pais_id : $this->honduras();
        $departamentos = $this->territorio_ejecucion === 'Internacional' && ! $this->pais_id
            ? collect()
            : Departamento::query()
                ->when($paisDepartamentos, fn ($query) => $query->where('pais_id', $paisDepartamentos))
                ->orderBy('nombre')
                ->pluck('nombre', 'id');
        $paises = Pais::where('nombre', '!=', 'Honduras')->orderBy('nombre')->pluck('nombre', 'id');

        $municipios = $this->departamento_id
            ? Municipio::where('departamento_id', $this->departamento_id)->orderBy('nombre')->pluck('nombre', 'id')
            : collect();

        $this->paisSedeConDepartamentos = null;
        $paisSede = $this->territorio_ejecucion === 'Internacional' ? $this->pais_sede_id : $this->honduras();
        $departamentosSede = $this->aplicaTeletrabajo() && $paisSede
            ? Departamento::where('pais_id', $paisSede)->orderBy('nombre')->pluck('nombre', 'id')
            : collect();
        $municipiosSede = $this->aplicaTeletrabajo() && $this->departamento_sede_id
            ? Municipio::where('departamento_id', $this->departamento_sede_id)->orderBy('nombre')->pluck('nombre', 'id')
            : collect();

        // Buscador de docentes: sin límite de catálogo, solo los que coinciden con lo escrito.
        $termino = trim($this->docenteBusqueda);
        $docentesEncontrados = $this->currentStep === self::PASO_SUPERVISOR && ! $this->docente_supervisor_id && mb_strlen($termino) >= 2
            ? Empleado::docentes()
                ->with(['categoria', 'departamento_academico'])
                ->where(fn ($query) => $query
                    ->where('nombre_completo', 'like', "%{$termino}%")
                    ->orWhere('numero_empleado', 'like', "%{$termino}%"))
                ->orderBy('nombre_completo')
                ->limit(15)
                ->get()
            : collect();

        $institucionesCatalogo = $this->currentStep === self::PASO_INSTITUCION && $this->modoInstitucion === null
            ? PpsInstitucion::orderBy('nombre')->get(['id', 'nombre', 'tipo', 'nacionalidad', 'pais'])
                ->map(fn (PpsInstitucion $institucion) => [
                    'id' => (string) $institucion->id,
                    'nombre' => $institucion->nombre,
                    'detalle' => ($this->tipoInstitucionOpciones[$institucion->tipo] ?? $institucion->tipo).' · '.$institucion->pais_visible,
                ])
                ->values()
            : collect();

        $jornadasLaborales = JornadaLaboral::where('activo', true)
            ->orderBy('orden')
            ->orderBy('hora_inicio')
            ->get()
            ->pluck('etiqueta', 'etiqueta');

        return view('livewire.proyectos.vinculacion.create-pps-servicio-social', [
            'facultadesCentros' => $facultadesCentros,
            'carreras' => $carreras,
            'departamentos' => $departamentos,
            'paises' => $paises,
            'municipios' => $municipios,
            'departamentosSede' => $departamentosSede,
            'tratamientos' => TratamientoDestinatario::opciones(),
            // Catálogos para completar lo que falta en el expediente del docente supervisor.
            'categoriasDocente' => $this->currentStep === self::PASO_SUPERVISOR && $this->docente_supervisor_id
                ? CategoriaEmpleado::orderBy('nombre')->pluck('nombre', 'nombre')
                : collect(),
            'departamentosDocente' => $this->currentStep === self::PASO_SUPERVISOR && $this->docente_supervisor_id
                ? DepartamentoAcademico::query()->distinct()->orderBy('nombre')->pluck('nombre', 'nombre')
                : collect(),
            'firmaRegistrada' => $this->currentStep === self::PASO_SOLICITUD
                && filled(PpsDocumentoGenerator::imagenFirma(auth()->user()?->empleado)),
            'solicitudesGeneradas' => $this->currentStep === self::PASO_SOLICITUD && $this->registroId
                ? PpsDocumentoGenerado::query()
                    ->where('pps_servicio_social_id', $this->registroId)
                    ->where('tipo', PpsDocumentoGenerator::SOLICITUD)
                    ->with('usuario:id,name')
                    ->latest('version')
                    ->get()
                : collect(),
            'municipiosSede' => $municipiosSede,
            'docentesEncontrados' => $docentesEncontrados,
            'institucionesCatalogo' => $institucionesCatalogo,
            'jornadasLaborales' => $jornadasLaborales,
        ]);
    }
}
