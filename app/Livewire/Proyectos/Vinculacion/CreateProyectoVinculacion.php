<?php

namespace App\Livewire\Proyectos\Vinculacion;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Support\Notification;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\Modalidad;
use App\Models\Proyecto\Categoria;
use App\Models\Proyecto\EjesPrioritariosUnah;
use App\Models\Proyecto\Od;
use App\Models\Proyecto\MetaContribuye;
use App\Models\Proyecto\IntegranteInternacional;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Demografia\Municipio;
use App\Models\Estado\TipoEstado;
use App\Models\Demografia\Pais;
use App\Models\Asignatura;
use App\Models\PeriodoAcademico;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\UnidadAcademica\DepartamentoAcademico;
use App\Models\UnidadAcademica\Carrera;
use App\Services\Workflow\WorkflowReviewerResolver;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Renderless;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use App\Mail\ProyectoCreado;
use RuntimeException;

class CreateProyectoVinculacion extends Component
{
    use WithFileUploads;

    public int $currentStep = 1;
    public ?int $recordId = null;
    public ?int $proyectoId = null;
    public ?int $tipo_accion_id = null;
    public string $estadoAutoGuardado = 'idle';
    public bool $autoguardadoActivo = true;
    private bool $autoGuardando = false;

    // Step 1
    public string $nombre_proyecto = '';
    public array $asignaturas = [];
    public ?int $modalidad_id = null;
    public array $categoria = [];
    public array $ejes_prioritarios_unah = [];
    public array $facultades_centros = [];
    public array $departamentos_academicos = [];
    public array $carreras = [];
    public string $programa_pertenece = '';
    public string $lineas_investigacion_academica = '';
    public array $ods = [];
    public array $metasContribuye = [];
    public array $metasDisponibles = [];
    public string $fecha_inicio = '';
    public string $fecha_finalizacion = '';

    // Step 2
    public array $empleado_proyecto = [];
    public array $estudiante_proyecto = [];
    public array $integrante_internacional_proyecto = [];
    public array $asignaturasDisponibles = [];
    public array $periodosAcademicosDisponibles = [];

    // Step 2 – modals
    public bool $showEmpleadoModal = false;
    public string $empleadoModalSearch = '';

    public bool $showEstudianteModal = false;
    public ?int $editEstudianteIndex = null;
    public array $nuevoEstudiante = [
        'tipo_participacion_estudiante' => '',
        'carrera_id' => null,
        'asignatura_id' => null,
        'periodo_academico_id' => null,
        'cantidad_estudiantes_hombres' => 0,
        'cantidad_estudiantes_mujeres' => 0,
        'total_estudiantes' => 0,
    ];
    public bool $showCrearAsignaturaInline = false;
    public string $nuevaAsignaturaCodigo = '';
    public string $nuevaAsignaturaNombre = '';
    public ?int $nuevaAsignaturaCarreraId = null;

    public bool $showInternacionalModal = false;
    public $integranteInternacionalSeleccionadoId = null;
    public array $nuevoIntegranteInternacional = [
        'nombre_completo' => '',
        'documento_identidad' => '',
        'sexo' => '',
        'email' => '',
        'pais' => '',
        'institucion' => '',
    ];

    // Step 3 – modal
    public bool $showContraparteModal = false;
    public ?int $editContraparteIndex = null;
    public array $nuevaContraparte = [
        'nombre' => '', 'tipo_entidad' => '', 'nombre_contacto' => '',
        'cargo_contacto' => '', 'telefono' => '', 'correo' => '',
        'descripcion_acuerdos' => '', 'instrumento_formalizacion' => [],
    ];
    public array $entidad_contraparte = [];

    // Step 4 – modal
    public bool $showActividadModal = false;
    public ?int $editActividadIndex = null;
    public array $nuevaActividad = [
        'descripcion' => '', 'empleados' => [],
        'fecha_inicio' => '', 'fecha_finalizacion' => '', 'horas' => '',
    ];
    public array $actividades = [];

    // Step 5 – description
    public string $resumen = '';
    public string $descripcion_participantes = '';
    public string $definicion_problema = '';
    public string $alineamiento_reforma = '';
    public string $impacto_deseado = '';
    public string $metodologia = '';
    public string $bibliografia = '';

    // Step 6 – beneficiaries
    public int|string|null $indigenas_hombres = 0;
    public int|string|null $indigenas_mujeres = 0;
    public int|string|null $afroamericanos_hombres = 0;
    public int|string|null $afroamericanos_mujeres = 0;
    public int|string|null $mestizos_hombres = 0;
    public int|string|null $mestizos_mujeres = 0;
    public int $hombres = 0;
    public int $mujeres = 0;
    public int $poblacion_participante = 0;
    public array $pais = ['Honduras'];
    public array $region = [];
    public array $departamento_geo = [];
    public array $municipio_geo = [];
    public string $caserio = '';
    public string $aldea = '';

    // Step 7 (was 6) – marco lógico
    public string $objetivo_general = '';
    public array $objetivosEspecificos = [];
    public int $selectedObjetivoIndex = 0;

    // Step 8 (was 7) – presupuesto
    public array $aporte_institucional = [];
    public float $aporte_contraparte = 0;
    public float $aporte_internacionales = 0;
    public float $aporte_otras_universidades = 0;
    public float $aporte_comunidad = 0;
    public float $otros_aportes = 0;

    // Step 9 (was 8) – anexos
    public $newAnexo;
    public int $anexosCount = 0;

    // Step 10 (was 9) – firmas
    public ?int $jefe_empleado_id = null;
    public ?int $decano_empleado_id = null;
    public ?int $enlace_empleado_id = null;
    public string $firmaSearch = '';
    public array $firmantesPorEtapa = [];
    public array $candidatosPorEtapa = [];
    public array $unidadesSinCandidatosPorEtapa = [];
    public array $mensajesFirmantesPorEtapa = [];
    public array $erroresFirmantesPorEtapa = [];
    public bool $firmantesPorEtapaListos = false;
    public bool $firmantesPorEtapaBloqueado = false;
    public ?string $mensajeBloqueoFirmantesPorEtapa = null;
    public bool $mostrarFirmantesPorEtapa = false;
    public ?string $mensajeFirmantesPorEtapaVista = null;
    public bool $usarFirmantesPorEtapaParaEnvio = false;

    // ── FORM-DVUS-015 (Voluntariado Académico) ──────────────────────────────
    // Campos propios que sólo aplican cuando el tipo de acción es Voluntariado.
    public bool $esVoluntariado = false;
    public string $tematica_principal = '';
    public string $tematica_principal_otro = '';
    public array $metodologia_seguimiento = [];
    public string $experiencia_conocimientos_teoricos = '';
    public string $experiencia_habilidades_tecnicas = '';
    public string $experiencia_competencias_blandas = '';
    public array $espacios_institucionales = [];

    protected array $tematicaPrincipalOpciones = [
        'educacion' => 'Educación',
        'salud_bienestar' => 'Salud y bienestar',
        'cultura_patrimonio' => 'Cultura y patrimonio',
        'ambiente_sostenibilidad' => 'Ambiente y sostenibilidad',
        'desarrollo_comunitario' => 'Desarrollo comunitario',
        'otros' => 'Otros',
    ];

    protected array $metodologiaSeguimientoOpciones = [
        'encuestas' => 'Encuestas',
        'entrevistas' => 'Entrevistas',
    ];

    protected array $validationAttributes = [
        'tematica_principal' => 'temática principal',
        'tematica_principal_otro' => 'temática principal (otro)',
        'metodologia_seguimiento' => 'metodología de seguimiento',
        'experiencia_conocimientos_teoricos' => 'conocimientos teóricos',
        'experiencia_habilidades_tecnicas' => 'habilidades técnicas',
        'experiencia_competencias_blandas' => 'competencias blandas',
        'espacios_institucionales' => 'espacios institucionales',
    ];

    protected array $instrumentoTipos = [
        'carta_formal_solicitud',
        'carta_intenciones',
        'convenio_marco',
    ];

    protected array $plazoOpciones = [
        'corto_plazo',
        'mediano_plazo',
        'largo_plazo',
    ];

    protected array $tipoParticipacionEstudianteOpciones = [
        'Servicio Social o PPS' => 'PPS / Servicio Social',
        'Practica Profesional' => 'Práctica Profesional',
        'Practica Asignatura' => 'Asignatura',
        'Voluntariado' => 'Voluntariado',
    ];

    protected array $tipoParticipacionEstudiantePermitidos = [
        'Servicio Social o PPS',
        'Practica Profesional',
        'Practica Asignatura',
        'Voluntariado',
    ];

    private const CAMPOS_DESCRIPCION_REQUERIDOS = [
        'resumen',
        'descripcion_participantes',
        'definicion_problema',
        'alineamiento_reforma',
        'impacto_deseado',
        'metodologia',
        'bibliografia',
    ];

    private const CAMPOS_BENEFICIARIOS = [
        'indigenas_hombres',
        'indigenas_mujeres',
        'afroamericanos_hombres',
        'afroamericanos_mujeres',
        'mestizos_hombres',
        'mestizos_mujeres',
    ];

    public function mount(?int $record = null): void
    {
        $this->tipo_accion_id = request()->integer('tipo_accion_id') ?: null;

        if ($record !== null) {
            $proyecto = Proyecto::find($record);
            if ($proyecto) {
                if (!$proyecto->coordinadorIsCurrentUser() ||
                    !$proyecto->proyectoIsInAnyEstados(['Borrador', 'Subsanacion', 'Autoguardado'])) {
                    abort(403);
                }
                $this->recordId = $proyecto->id;
                $this->proyectoId = $proyecto->id;
                $this->tipo_accion_id = $proyecto->tipo_accion_id ?: $this->tipo_accion_id;
                $this->loadFromRecord($proyecto);
                $this->anexosCount = $proyecto->anexos()->count();
            }
        }
        $this->resolverEsVoluntariado();
        $this->initDefaults();
        $this->cargarOpcionesPracticaAsignatura();
        $this->cargarMetasPorOds();
    }

    private function resolverEsVoluntariado(): void
    {
        $codigo = $this->tipo_accion_id
            ? DB::table('vinculacion_tipos_accion')->where('id', $this->tipo_accion_id)->value('codigo')
            : null;

        $this->esVoluntariado = $codigo === 'VOLUNTARIADO';
    }

    private function codigoFormularioFlujo(): ?string
    {
        $codigo = $this->tipo_accion_id
            ? DB::table('vinculacion_tipos_accion')->where('id', $this->tipo_accion_id)->value('codigo')
            : null;

        return match ($codigo) {
            'DESARROLLO_LOCAL_REGIONAL' => 'FORM-DVUS-001',
            'VOLUNTARIADO' => 'FORM-DVUS-015',
            default => null,
        };
    }

    private function nuevoEspacioInstitucional(): array
    {
        return [
            'id' => null,
            'descripcion' => '',
            'ubicacion' => '',
            'unidad_gestora' => '',
            'tiempo_uso_horas' => '',
        ];
    }

    protected function getRecord(): ?Proyecto
    {
        return $this->recordId ? Proyecto::find($this->recordId) : null;
    }

    protected function loadFromRecord(Proyecto $record): void
    {
        $record->load([
            'objetivosEspecificos.resultados',
            'estudiante_proyecto',
            'entidad_contraparte.instrumento_formalizacion',
            'actividades.empleados',
            'presupuesto',
            'ods',
            'anexos',
            'coordinador_proyecto',
            'empleado_proyecto.empleado',
            'integrante_internacional_proyecto.integranteInternacional',
            'aporteInstitucional',
            'categoria',
            'ejes_prioritarios_unah',
            'facultades_centros',
            'departamentos_academicos',
            'carreras',
            'metasContribuye',
            'departamento',
            'municipio',
            'firma_proyecto.cargo_firma.tipoCargoFirma',
            'espaciosInstitucionales',
        ]);

        $this->nombre_proyecto = $record->nombre_proyecto ?? '';
        $this->modalidad_id = $record->modalidad_id;
        $this->categoria = $record->categoria->pluck('id')->toArray();
        $this->ejes_prioritarios_unah = $record->ejes_prioritarios_unah->pluck('id')->toArray();
        $this->facultades_centros = $record->facultades_centros->pluck('id')->toArray();
        $this->departamentos_academicos = $record->departamentos_academicos->pluck('id')->toArray();
        $this->carreras = $record->carreras->pluck('id')->toArray();
        $this->asignaturas = $record->asignaturas->pluck('id')->toArray();
        $this->programa_pertenece = $record->programa_pertenece ?? '';
        $this->lineas_investigacion_academica = $record->lineas_investigacion_academica ?? '';
        $this->ods = $record->ods->pluck('id')->toArray();
        $this->metasContribuye = $record->metasContribuye()
            ->pluck('metas_contribuye.id')
            ->map(fn($id) => (string) $id)
            ->toArray();

        $this->fecha_inicio = $this->dateForInput($record->fecha_inicio);
        $this->fecha_finalizacion = $this->dateForInput($record->fecha_finalizacion);
        $this->loadFirmasFromRecord($record);

        $this->empleado_proyecto = $record->empleado_proyecto->map(fn($ep) => [
            'empleado_id' => $ep->empleado_id,
            'rol' => $ep->rol ?? 'Integrante',
            'nombre' => $ep->empleado?->nombre_completo ?? '',
        ])->toArray();

        $this->estudiante_proyecto = $record->estudiante_proyecto->map(fn($ep) => [
            'tipo_participacion_estudiante' => $this->normalizeTipoParticipacionEstudiante($ep->tipo_participacion_estudiante) ?: $ep->tipo_participacion_estudiante,
            'carrera_id' => $ep->carrera_id,
            'asignatura_id' => $ep->asignatura_id,
            'periodo_academico_id' => $ep->periodo_academico_id,
            'cantidad_estudiantes_hombres' => $ep->cantidad_estudiantes_hombres ?? 0,
            'cantidad_estudiantes_mujeres' => $ep->cantidad_estudiantes_mujeres ?? 0,
            'total_estudiantes' => $ep->total_estudiantes ?? 0,
        ])->toArray();

        $this->integrante_internacional_proyecto = $record->integrante_internacional_proyecto->map(fn($ip) => [
            'integrante_internacional_id' => $ip->integrante_internacional_id,
            'nombre' => $ip->integranteInternacional?->nombre_completo ?? '',
            'pais' => $ip->integranteInternacional?->pais ?? '',
            'institucion' => $ip->integranteInternacional?->institucion ?? '',
        ])->toArray();

        $this->entidad_contraparte = $record->entidad_contraparte->map(fn($e) => [
            'nombre' => $e->nombre,
            'tipo_entidad' => $e->tipo_entidad,
            'nombre_contacto' => $e->nombre_contacto,
            'cargo_contacto' => $e->cargo_contacto,
            'telefono' => $e->telefono,
            'correo' => $e->correo,
            'descripcion_acuerdos' => $e->descripcion_acuerdos,
            'instrumento_formalizacion' => $e->instrumento_formalizacion->map(fn($i) => [
                'id' => $i->id,
                'tipo_documento' => $this->normalizeInstrumentoTipo($i->tipo_documento) ?: $i->tipo_documento,
                'documento_url' => $i->documento_url,
                'nombre_archivo' => $i->nombre_archivo,
                'documento_file' => null,
            ])->toArray(),
        ])->toArray();

        $this->actividades = $record->actividades->map(fn($a) => [
            'id' => $a->id,
            'descripcion' => $a->descripcion,
            'empleados' => $a->empleados->pluck('id')->toArray(),
            'fecha_inicio' => $this->dateForInput($a->fecha_inicio),
            'fecha_finalizacion' => $this->dateForInput($a->fecha_finalizacion),
            'horas' => $a->horas ?? '',
        ])->toArray();

        $this->resumen = $record->resumen ?? '';
        $this->descripcion_participantes = $record->descripcion_participantes ?? '';
        $this->definicion_problema = $record->definicion_problema ?? '';
        $this->indigenas_hombres = (int)($record->indigenas_hombres ?? 0);
        $this->indigenas_mujeres = (int)($record->indigenas_mujeres ?? 0);
        $this->afroamericanos_hombres = (int)($record->afroamericanos_hombres ?? 0);
        $this->afroamericanos_mujeres = (int)($record->afroamericanos_mujeres ?? 0);
        $this->mestizos_hombres = (int)($record->mestizos_hombres ?? 0);
        $this->mestizos_mujeres = (int)($record->mestizos_mujeres ?? 0);
        $this->hombres = (int)($record->hombres ?? 0);
        $this->mujeres = (int)($record->mujeres ?? 0);
        $this->poblacion_participante = (int)($record->poblacion_participante ?? 0);
        $this->pais = $record->pais ?? ['Honduras'];
        $this->region = $record->region ?? [];
        $this->departamento_geo = $record->departamento?->pluck('id')->toArray() ?? [];
        $this->municipio_geo = $record->municipio?->pluck('id')->toArray() ?? [];
        $this->filtrarMunicipiosImpactoSeleccionados();
        $this->caserio = $record->caserio ?? '';
        $this->aldea = $record->aldea ?? '';
        $this->alineamiento_reforma = $record->alineamiento_reforma ?? '';
        $this->impacto_deseado = $record->impacto_deseado ?? '';
        $this->metodologia = $record->metodologia ?? '';
        $this->bibliografia = $record->bibliografia ?? '';

        // FORM-DVUS-015 (Voluntariado Académico)
        $this->tematica_principal = $record->tematica_principal ?? '';
        $this->tematica_principal_otro = $record->tematica_principal_otro ?? '';
        $this->metodologia_seguimiento = is_array($record->metodologia_seguimiento) ? $record->metodologia_seguimiento : [];
        $this->experiencia_conocimientos_teoricos = $record->experiencia_conocimientos_teoricos ?? '';
        $this->experiencia_habilidades_tecnicas = $record->experiencia_habilidades_tecnicas ?? '';
        $this->experiencia_competencias_blandas = $record->experiencia_competencias_blandas ?? '';
        $this->espacios_institucionales = $record->espaciosInstitucionales->map(fn($e) => [
            'id' => $e->id,
            'descripcion' => $e->descripcion ?? '',
            'ubicacion' => $e->ubicacion ?? '',
            'unidad_gestora' => $e->unidad_gestora ?? '',
            'tiempo_uso_horas' => $e->tiempo_uso_horas !== null ? (string) $e->tiempo_uso_horas : '',
        ])->toArray();

        $this->objetivo_general = $record->objetivo_general ?? '';
        $this->objetivosEspecificos = $record->objetivosEspecificos->map(fn($obj) => [
            'id' => $obj->id,
            'wire_key' => (string) Str::uuid(),
            'descripcion' => $obj->descripcion,
            'resultados' => $obj->resultados->map(fn($r) => [
                'id' => $r->id,
                'wire_key' => (string) Str::uuid(),
                'nombre_resultado' => $r->nombre_resultado,
                'nombre_indicador' => $r->nombre_indicador,
                'nombre_medio_verificacion' => $r->nombre_medio_verificacion,
                'plazo' => $this->normalizePlazo($r->plazo),
            ])->toArray(),
        ])->toArray();

        if ($record->aporteInstitucional->isNotEmpty()) {
            $this->aporte_institucional = $record->aporteInstitucional->map(fn($a) => [
                'concepto' => $a->concepto,
                'concepto_label' => $a->concepto ?? '',
                'unidad' => $a->unidad,
                'unidad_label' => $a->unidad ?? '',
                'cantidad' => $a->cantidad ?? 0,
                'costo_unitario' => $a->costo_unitario ?? 0,
                'costo_total' => $a->costo_total ?? 0,
            ])->toArray();
        }

        if ($record->presupuesto) {
            $this->aporte_contraparte = (float)($record->presupuesto->aporte_contraparte ?? 0);
            $this->aporte_internacionales = (float)($record->presupuesto->aporte_internacionales ?? 0);
            $this->aporte_otras_universidades = (float)($record->presupuesto->aporte_otras_universidades ?? 0);
            $this->aporte_comunidad = (float)($record->presupuesto->aporte_comunidad ?? 0);
            $this->otros_aportes = (float)($record->presupuesto->otros_aportes ?? 0);
        }
    }

    protected function initDefaults(): void
    {
        if (!$this->recordId && $this->fecha_inicio === '') {
            $this->fecha_inicio = Carbon::now('America/Tegucigalpa')->format('Y-m-d');
        }
        $this->aporte_institucional = $this->normalizeAporteRows($this->aporte_institucional);
        $this->recalculateAporteInstitucional();
        if (empty($this->objetivosEspecificos)) {
            $this->objetivosEspecificos = [$this->nuevoObjetivoEspecifico()];
        }
        if ($this->esVoluntariado && empty($this->espacios_institucionales)) {
            $this->espacios_institucionales = [$this->nuevoEspacioInstitucional()];
        }
    }

    // ─── Step Navigation ────────────────────────────────────────────────────

    public function nextStep(): void
    {
        $this->resetErrorBag();

        if (!$this->validarPasoActualParaNavegacion()) {
            return;
        }

        if ($this->currentStep < 9) {
            $this->currentStep++;
            $this->selectedObjetivoIndex = 0;
        }
    }

    public function prevStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
            $this->selectedObjetivoIndex = 0;
        }
    }

    public function goToStep(int $step): void
    {
        if (!$this->recordId || $step < 1 || $step > 9 || $step === $this->currentStep) {
            return;
        }

        if ($step > $this->currentStep) {
            $this->resetErrorBag();

            if (!$this->validarPasoActualParaNavegacion()) {
                return;
            }
        }

        $this->currentStep = $step;
        $this->selectedObjetivoIndex = 0;
    }

    private function validarPasoActualParaNavegacion(): bool
    {
        $this->normalizarDatosAntesDeValidarPaso($this->currentStep);
        $rules = $this->rulesPasoActualParaNavegacion();

        try {
            if ($this->currentStep === 7) {
                $this->validarMarcoLogicoCompleto();
            } elseif (!empty($rules)) {
                $this->validate($rules);
            }
        } catch (ValidationException $e) {
            throw $e;
        }

        return $this->validacionesAdicionalesPasoActualParaNavegacion();
    }

    private function rulesPasoActualParaNavegacion(): array
    {
        $rules = $this->rulesPasoActualBase();

        if ($this->esVoluntariado) {
            $rules = array_merge($rules, $this->rulesVoluntariadoPaso($this->currentStep));
        }

        return $rules;
    }

    private function rulesVoluntariadoPaso(int $step): array
    {
        return match ($step) {
            1 => [
                'tematica_principal' => 'required|string|in:' . implode(',', array_keys($this->tematicaPrincipalOpciones)),
                'tematica_principal_otro' => 'nullable|required_if:tematica_principal,otros|string|max:180',
            ],
            5 => [
                'experiencia_conocimientos_teoricos' => 'required|string',
                'experiencia_habilidades_tecnicas' => 'required|string',
                'experiencia_competencias_blandas' => 'required|string',
            ],
            6 => [
                'metodologia_seguimiento' => 'nullable|array',
                'metodologia_seguimiento.*' => 'in:' . implode(',', array_keys($this->metodologiaSeguimientoOpciones)),
            ],
            9 => [
                'espacios_institucionales' => 'nullable|array',
                'espacios_institucionales.*.descripcion' => 'nullable|string|max:255',
                'espacios_institucionales.*.ubicacion' => 'nullable|string|max:255',
                'espacios_institucionales.*.unidad_gestora' => 'nullable|string|max:255',
                'espacios_institucionales.*.tiempo_uso_horas' => 'nullable|numeric|min:0',
            ],
            default => [],
        };
    }

    private function rulesPasoActualBase(): array
    {
        return match ($this->currentStep) {
            1 => [
                'nombre_proyecto' => 'required|string|max:255',
                'modalidad_id' => 'required|integer',
                'categoria' => 'required|array|min:1',
                'ejes_prioritarios_unah' => 'required|array|min:1',
                'facultades_centros' => 'required|array|min:1',
                'facultades_centros.*' => 'integer|exists:centro_facultad,id',
                'departamentos_academicos' => 'required|array|min:1',
                'departamentos_academicos.*' => 'integer|exists:departamento_academico,id',
                'carreras' => 'required|array|min:1',
                'carreras.*' => 'integer|exists:carrera,id',
                'programa_pertenece' => 'required|string',
                'lineas_investigacion_academica' => 'required|string',
                'ods' => 'required|array|min:1',
                'fecha_inicio' => 'required|date',
                'fecha_finalizacion' => 'required|date|after_or_equal:fecha_inicio',
            ],
            2 => [
                'estudiante_proyecto' => 'required|array|min:1',
                'estudiante_proyecto.*.tipo_participacion_estudiante' => 'required|string',
                'estudiante_proyecto.*.carrera_id' => 'nullable|exists:carrera,id',
                'estudiante_proyecto.*.asignatura_id' => 'nullable|exists:asignaturas,id',
                'estudiante_proyecto.*.periodo_academico_id' => 'nullable|string|max:50',
            ],
            3 => [
                'entidad_contraparte' => 'required|array|min:1',
                'entidad_contraparte.*.nombre' => 'required|string',
            ],
            4 => [
                'actividades' => 'required|array|min:1',
                'actividades.*.descripcion' => 'required|string',
                'actividades.*.fecha_inicio' => 'required|date',
                'actividades.*.fecha_finalizacion' => 'required|date',
            ],
            5 => $this->rulesDescripcion(),
            6 => [
                'indigenas_hombres' => 'nullable|integer|min:0',
                'indigenas_mujeres' => 'nullable|integer|min:0',
                'afroamericanos_hombres' => 'nullable|integer|min:0',
                'afroamericanos_mujeres' => 'nullable|integer|min:0',
                'mestizos_hombres' => 'nullable|integer|min:0',
                'mestizos_mujeres' => 'nullable|integer|min:0',
                'departamento_geo' => 'nullable|array',
                'departamento_geo.*' => 'integer|exists:departamento,id',
                'municipio_geo' => 'nullable|array',
                'municipio_geo.*' => 'integer|exists:municipio,id',
                'aldea' => 'nullable|string|max:255',
                'caserio' => 'nullable|string|max:255',
            ],
            7 => [
                'objetivo_general' => 'required|string',
                'objetivosEspecificos' => 'required|array|min:1',
                'objetivosEspecificos.*.descripcion' => 'required|string',
                'objetivosEspecificos.*.resultados' => 'required|array|min:1',
                'objetivosEspecificos.*.resultados.*.nombre_resultado' => 'required|string',
                'objetivosEspecificos.*.resultados.*.nombre_indicador' => 'required|string',
                'objetivosEspecificos.*.resultados.*.nombre_medio_verificacion' => 'required|string',
                'objetivosEspecificos.*.resultados.*.plazo' => 'required|in:' . implode(',', $this->plazoOpciones),
            ],
            default => [],
        };
    }

    private function normalizarDatosAntesDeValidarPaso(int $step): void
    {
        if ($step === 5) {
            $this->trimCamposDescripcion();
        }

        if ($step === 6) {
            $this->normalizarBeneficiarios();
        }

        if ($step === 7) {
            $this->normalizarMarcoLogico();
        }
    }

    private function rulesDescripcion(): array
    {
        return collect(self::CAMPOS_DESCRIPCION_REQUERIDOS)
            ->mapWithKeys(fn(string $campo) => [$campo => 'required|string'])
            ->all();
    }

    private function trimCamposDescripcion(): void
    {
        foreach (self::CAMPOS_DESCRIPCION_REQUERIDOS as $campo) {
            $this->{$campo} = trim((string) ($this->{$campo} ?? ''));
        }
    }

    private function validacionesAdicionalesPasoActualParaNavegacion(): bool
    {
        if ($this->currentStep === 4) {
            foreach ($this->actividades as $i => $actividad) {
                $fechaInicio = $this->dateOrNull($actividad['fecha_inicio'] ?? null);
                $fechaFin = $this->dateOrNull($actividad['fecha_finalizacion'] ?? null);

                if ($fechaInicio && $fechaFin && $fechaFin < $fechaInicio) {
                    $this->addError(
                        "actividades.$i.fecha_finalizacion",
                        'La fecha de finalización debe ser igual o posterior a la fecha de inicio de la actividad.'
                    );
                }
            }
        }

        if ($this->currentStep === 2) {
            foreach ($this->estudiante_proyecto as $i => $item) {
                $tipo = $this->normalizeTipoParticipacionEstudiante($item['tipo_participacion_estudiante'] ?? '')
                    ?: ($item['tipo_participacion_estudiante'] ?? '');

                if ($this->isTipoParticipacionAsignatura($tipo)) {
                    if (empty($this->carreras)) {
                        $this->addError("estudiante_proyecto.$i.carrera_id", 'Seleccione primero una carrera en Información General.');
                    }

                    if (empty($item['asignatura_id'])) {
                        $this->addError("estudiante_proyecto.$i.asignatura_id", 'Seleccione la asignatura.');
                    } elseif (!$this->asignaturaPerteneceACarrerasSeleccionadas($item['asignatura_id'])) {
                        $this->addError("estudiante_proyecto.$i.asignatura_id", 'La asignatura no corresponde a la carrera seleccionada.');
                    }

                    if (empty($item['periodo_academico_id'])) {
                        $this->addError("estudiante_proyecto.$i.periodo_academico_id", 'Seleccione el periodo académico.');
                    }
                }
            }
        }

        if ($this->currentStep === 6 && empty($this->departamento_geo) && $this->poblacion_participante <= 0) {
            $this->addError('departamento_geo', 'Complete la zona de impacto o registre beneficiarios para continuar.');
        }

        if ($this->currentStep === 8 && collect($this->aporte_institucional)->sum('costo_total') <= 0) {
            $this->addError('aporte_institucional', 'Registre al menos un aporte institucional para continuar.');
        }

        return $this->getErrorBag()->isEmpty();
    }

    private function validarMarcoLogicoCompleto(): void
    {
        $this->normalizarMarcoLogico();

        $this->validate(
            $this->rulesMarcoLogico(),
            [],
            $this->atributosMarcoLogico()
        );
    }

    private function rulesMarcoLogico(): array
    {
        return [
            'objetivo_general' => 'required|string',
            'objetivosEspecificos' => 'required|array|min:1',
            'objetivosEspecificos.*.descripcion' => 'required|string',
            'objetivosEspecificos.*.resultados' => 'required|array|min:1',
            'objetivosEspecificos.*.resultados.*.nombre_resultado' => 'required|string',
            'objetivosEspecificos.*.resultados.*.nombre_indicador' => 'required|string',
            'objetivosEspecificos.*.resultados.*.nombre_medio_verificacion' => 'required|string',
            'objetivosEspecificos.*.resultados.*.plazo' => 'required|in:' . implode(',', $this->plazoOpciones),
        ];
    }

    private function atributosMarcoLogico(): array
    {
        $attributes = [
            'objetivo_general' => 'objetivo general',
            'objetivosEspecificos' => 'objetivos específicos',
        ];

        foreach ($this->objetivosEspecificos as $oi => $objetivo) {
            $objetivoLabel = 'objetivo OE' . ($oi + 1);
            $attributes["objetivosEspecificos.$oi.descripcion"] = "descripción del {$objetivoLabel}";
            $attributes["objetivosEspecificos.$oi.resultados"] = "resultados esperados del {$objetivoLabel}";

            foreach (($objetivo['resultados'] ?? []) as $ri => $resultado) {
                $resultadoLabel = 'resultado R' . ($ri + 1) . ' del ' . $objetivoLabel;
                $attributes["objetivosEspecificos.$oi.resultados.$ri.nombre_resultado"] = "nombre del {$resultadoLabel}";
                $attributes["objetivosEspecificos.$oi.resultados.$ri.nombre_indicador"] = "indicador del {$resultadoLabel}";
                $attributes["objetivosEspecificos.$oi.resultados.$ri.nombre_medio_verificacion"] = "medio de verificación del {$resultadoLabel}";
                $attributes["objetivosEspecificos.$oi.resultados.$ri.plazo"] = "plazo del {$resultadoLabel}";
            }
        }

        return $attributes;
    }

    private function normalizarMarcoLogico(): void
    {
        $this->objetivo_general = trim($this->objetivo_general);

        foreach ($this->objetivosEspecificos as $oi => $objetivo) {
            $this->objetivosEspecificos[$oi]['descripcion'] = trim((string) ($objetivo['descripcion'] ?? ''));

            if (!isset($this->objetivosEspecificos[$oi]['resultados']) || !is_array($this->objetivosEspecificos[$oi]['resultados'])) {
                $this->objetivosEspecificos[$oi]['resultados'] = [];
            }

            foreach ($this->objetivosEspecificos[$oi]['resultados'] as $ri => $resultado) {
                $this->objetivosEspecificos[$oi]['resultados'][$ri]['nombre_resultado'] = trim((string) ($resultado['nombre_resultado'] ?? ''));
                $this->objetivosEspecificos[$oi]['resultados'][$ri]['nombre_indicador'] = trim((string) ($resultado['nombre_indicador'] ?? ''));
                $this->objetivosEspecificos[$oi]['resultados'][$ri]['nombre_medio_verificacion'] = trim((string) ($resultado['nombre_medio_verificacion'] ?? ''));
                $this->objetivosEspecificos[$oi]['resultados'][$ri]['plazo'] = $this->normalizePlazo($resultado['plazo'] ?? '') ?: '';
            }
        }
    }

    private function marcoLogicoTieneResultadosCompletos(): bool
    {
        if (empty($this->objetivosEspecificos)) {
            return false;
        }

        foreach ($this->objetivosEspecificos as $objetivo) {
            if (trim((string) ($objetivo['descripcion'] ?? '')) === '') {
                return false;
            }

            $resultados = $objetivo['resultados'] ?? [];
            if (empty($resultados)) {
                return false;
            }

            foreach ($resultados as $resultado) {
                if (trim((string) ($resultado['nombre_resultado'] ?? '')) === ''
                    || trim((string) ($resultado['nombre_indicador'] ?? '')) === ''
                    || trim((string) ($resultado['nombre_medio_verificacion'] ?? '')) === ''
                    || !$this->normalizePlazo($resultado['plazo'] ?? '')) {
                    return false;
                }
            }
        }

        return true;
    }

    protected function saveCurrentStep(): void
    {
        match ($this->currentStep) {
            1 => $this->saveStep1(),
            2 => $this->saveStep2(),
            3 => $this->saveStep3(),
            4 => $this->saveStep4(),
            5 => $this->saveStep5(),
            6 => $this->saveStep6(),
            7 => $this->saveStep7(),
            8 => $this->saveStep8(),
            9 => $this->saveStep9(),
            default => null,
        };
    }

    public function isStepComplete(int $step): bool
    {
        if (!$this->recordId) return false;

        return match ($step) {
            1 => !empty($this->nombre_proyecto)
                && $this->modalidad_id
                && !empty($this->categoria)
                && !empty($this->ejes_prioritarios_unah)
                && !empty($this->facultades_centros)
                && !empty($this->departamentos_academicos)
                && !empty($this->carreras)
                && !empty($this->fecha_inicio)
                && !empty($this->fecha_finalizacion)
                && !empty($this->programa_pertenece)
                && !empty($this->lineas_investigacion_academica)
                && !empty($this->ods)
                && (!$this->esVoluntariado || !empty($this->tematica_principal)),
            2 => !empty($this->estudiante_proyecto)
                && !empty(array_filter(array_column($this->estudiante_proyecto, 'tipo_participacion_estudiante'))),
            3 => !empty(array_filter(array_column($this->entidad_contraparte, 'nombre'))),
            4 => !empty(array_filter(array_column($this->actividades, 'descripcion'))),
            5 => collect(self::CAMPOS_DESCRIPCION_REQUERIDOS)->every(
                    fn(string $campo) => trim((string) ($this->{$campo} ?? '')) !== ''
                )
                && (!$this->esVoluntariado || (
                    !empty($this->experiencia_conocimientos_teoricos)
                    && !empty($this->experiencia_habilidades_tecnicas)
                    && !empty($this->experiencia_competencias_blandas)
                )),
            6 => !empty($this->departamento_geo) || $this->poblacion_participante > 0,
            7 => !empty($this->objetivo_general)
                && $this->marcoLogicoTieneResultadosCompletos(),
            8 => collect($this->aporte_institucional)->sum('costo_total') > 0,
            9 => $this->anexosCount > 0,
            default => false,
        };
    }

    // ─── Ensure Record ───────────────────────────────────────────────────────

    protected function ensureRecord(): Proyecto
    {
        if ($this->recordId || $this->proyectoId) {
            $this->recordId = $this->recordId ?: $this->proyectoId;
            $this->proyectoId = $this->proyectoId ?: $this->recordId;

            return Proyecto::findOrFail($this->recordId);
        }

        $empleado = auth()->user()->empleado;
        $nombreProyecto = trim($this->nombre_proyecto) !== ''
            ? $this->nombre_proyecto
            : 'Borrador sin título';

        $record = Proyecto::create([
            'nombre_proyecto' => $nombreProyecto,
            'tipo_accion_id' => $this->tipo_accion_id,
            'modalidad_id' => $this->modalidad_id,
            'fecha_inicio' => $this->fecha_inicio ?: null,
            'fecha_finalizacion' => $this->fecha_finalizacion ?: null,
            'programa_pertenece' => $this->programa_pertenece,
            'lineas_investigacion_academica' => $this->lineas_investigacion_academica,
            'flujo_aprobacion_id' => FlujoAprobacion::defaultForProyectos($this->tipo_accion_id, $this->codigoFormularioFlujo())?->id
                ?? FlujoAprobacion::defaultForProyectos($this->tipo_accion_id)?->id
                ?? FlujoAprobacion::defaultForProyectos()?->id,
        ]);
        $record->coordinador_proyecto()->firstOrCreate(
            ['empleado_id' => $empleado->id],
            ['rol' => 'Coordinador']
        );
        $record->agregarEstadoByName(
            empleado: $empleado,
            tipoEstadoNombre: 'Borrador',
            comentario: 'Borrador creado automáticamente',
        );
        $this->recordId = $record->id;
        $this->proyectoId = $record->id;
        $this->dispatch('borrador-creado', id: $record->id);
        return $record;
    }

    // ─── Save Steps ──────────────────────────────────────────────────────────
    public function updated(string $propertyName): void
    {
        if ($this->esCampoBeneficiario($propertyName)) {
            $this->normalizarCampoBeneficiario($propertyName);
            $this->calcTotales();
        }

        if ($this->esPropiedadAcademicaDependiente($propertyName)) {
            $this->limpiarRelacionesDependientes();
        }

        if (!$this->autoguardadoActivo || !$this->debeAutoguardar($propertyName)) {
            return;
        }

        $this->autoGuardarBorrador();
    }

    private function esPropiedadAcademicaDependiente(string $propertyName): bool
    {
        foreach (['facultades_centros', 'departamentos_academicos', 'carreras'] as $propiedad) {
            if ($propertyName === $propiedad || str_starts_with($propertyName, $propiedad . '.')) {
                return true;
            }
        }

        return false;
    }

    private function esCampoBeneficiario(string $propertyName): bool
    {
        return in_array($propertyName, self::CAMPOS_BENEFICIARIOS, true);
    }

    private function debeAutoguardar(string $propertyName): bool
    {
        $propiedadesIgnoradas = [
            'estadoAutoGuardado',
            'autoguardadoActivo',
            'currentStep',
            'recordId',
            'proyectoId',
            'metasDisponibles',
            'showInternacionalModal',
            'nuevoIntegranteInternacional',
            'showActividadModal',
            'editActividadIndex',
            'nuevaActividad',
        ];

        foreach ($propiedadesIgnoradas as $ignorada) {
            if ($propertyName === $ignorada || str_starts_with($propertyName, $ignorada . '.')) {
                return false;
            }
        }

        return true;
    }

    #[Renderless]
    public function guardarMetasContribuyeSeleccionadas(array $ids): void
    {
        $metasSeleccionadas = collect($ids)
            ->filter(fn($id) => $id !== null && $id !== '')
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values();

        $odsSeleccionados = $this->ids($this->ods);

        $metasQuery = MetaContribuye::whereIn('id', $metasSeleccionadas->all());
        if (!empty($odsSeleccionados)) {
            $metasQuery->whereIn('ods_id', $odsSeleccionados);
        }

        $this->metasContribuye = $metasQuery
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->values()
            ->toArray();

        try {
            $record = $this->ensureRecord();
            $record->metasContribuye()->sync($this->ids($this->metasContribuye));
            $this->estadoAutoGuardado = 'guardado';
        } catch (\Throwable $e) {
            report($e);
            $this->estadoAutoGuardado = 'error';
        }
    }

    public function autoGuardarBorrador(): void
    {
        if ($this->autoGuardando) {
            return;
        }

        try {
            $this->autoGuardando = true;
            $this->estadoAutoGuardado = 'guardando';

            DB::transaction(function () {
                $record = $this->ensureRecord();
                $this->guardarBorradorParcial($record);
            });

            $this->estadoAutoGuardado = 'guardado';
        } catch (\Throwable $e) {
            report($e);
            $this->estadoAutoGuardado = 'error';
        } finally {
            $this->autoGuardando = false;
        }
    }

    private function guardarBorradorParcial(Proyecto $record): void
    {
        $this->cargarMetasPorOds();
        $this->limpiarRelacionesDependientes();
        $this->calcTotales();
        $this->recalculateAporteInstitucional();
        $this->filtrarMunicipiosImpactoSeleccionados();

        $record->update([
            'nombre_proyecto' => trim($this->nombre_proyecto) !== '' ? $this->nombre_proyecto : 'Borrador sin título',
            'modalidad_id' => $this->nullableInt($this->modalidad_id),
            'fecha_inicio' => $this->dateOrNull($this->fecha_inicio),
            'fecha_finalizacion' => $this->dateOrNull($this->fecha_finalizacion),
            'programa_pertenece' => $this->programa_pertenece,
            'lineas_investigacion_academica' => $this->lineas_investigacion_academica,
            'resumen' => $this->resumen,
            'descripcion_participantes' => $this->descripcion_participantes,
            'definicion_problema' => $this->definicion_problema,
            'indigenas_hombres' => (int) $this->indigenas_hombres,
            'indigenas_mujeres' => (int) $this->indigenas_mujeres,
            'afroamericanos_hombres' => (int) $this->afroamericanos_hombres,
            'afroamericanos_mujeres' => (int) $this->afroamericanos_mujeres,
            'mestizos_hombres' => (int) $this->mestizos_hombres,
            'mestizos_mujeres' => (int) $this->mestizos_mujeres,
            'hombres' => (int) $this->hombres,
            'mujeres' => (int) $this->mujeres,
            'poblacion_participante' => (int) $this->poblacion_participante,
            'pais' => $this->pais,
            'region' => $this->region,
            'caserio' => $this->caserio,
            'aldea' => $this->aldea,
            'alineamiento_reforma' => $this->alineamiento_reforma,
            'impacto_deseado' => $this->impacto_deseado,
            'metodologia' => $this->metodologia,
            'bibliografia' => $this->bibliografia,
            'tematica_principal' => $this->stringOrNull($this->tematica_principal),
            'tematica_principal_otro' => $this->tematica_principal === 'otros' ? $this->stringOrNull($this->tematica_principal_otro) : null,
            'metodologia_seguimiento' => $this->metodologiaSeguimientoNormalizada(),
            'experiencia_conocimientos_teoricos' => $this->stringOrNull($this->experiencia_conocimientos_teoricos),
            'experiencia_habilidades_tecnicas' => $this->stringOrNull($this->experiencia_habilidades_tecnicas),
            'experiencia_competencias_blandas' => $this->stringOrNull($this->experiencia_competencias_blandas),
            'objetivo_general' => $this->objetivo_general,
            'total_aporte_institucional' => collect($this->aporte_institucional)->sum('costo_total'),
        ]);

        $record->categoria()->sync($this->ids($this->categoria));
        $record->ejes_prioritarios_unah()->sync($this->ids($this->ejes_prioritarios_unah));
        $record->facultades_centros()->sync($this->ids($this->facultades_centros));
        $record->departamentos_academicos()->sync($this->ids($this->departamentos_academicos));
        $record->carreras()->sync($this->ids($this->carreras));
        // sincronizar asignaturas seleccionadas (si existe la tabla)
        if (Schema::hasTable('proyecto_asignatura')) {
            $record->asignaturas()->sync($this->ids($this->asignaturas));
        }
        $record->ods()->sync($this->ids($this->ods));
        $record->departamento()->sync($this->ids($this->departamento_geo));
        $record->municipio()->sync($this->ids($this->municipio_geo));

        $this->guardarEquipoParcial($record);
        $this->guardarContrapartesParcial($record);
        $this->guardarActividadesParcial($record);
        $this->guardarMarcoLogicoParcial($record);
        $this->guardarPresupuestoParcial($record);
        $this->guardarAnexoParcial($record);
        $this->guardarEspaciosInstitucionalesParcial($record);
        $this->guardarFirmasParcial($record);
    }

    private function metodologiaSeguimientoNormalizada(): array
    {
        return collect($this->metodologia_seguimiento)
            ->filter(fn ($valor) => in_array($valor, array_keys($this->metodologiaSeguimientoOpciones), true))
            ->unique()
            ->values()
            ->all();
    }

    private function guardarEspaciosInstitucionalesParcial(Proyecto $record): void
    {
        if (!$this->esVoluntariado) {
            return;
        }

        $idsEnEstado = [];

        foreach ($this->espacios_institucionales as $i => $item) {
            $descripcion = trim((string) ($item['descripcion'] ?? ''));
            $tieneDatos = $descripcion !== ''
                || trim((string) ($item['ubicacion'] ?? '')) !== ''
                || trim((string) ($item['unidad_gestora'] ?? '')) !== ''
                || trim((string) ($item['tiempo_uso_horas'] ?? '')) !== '';

            if (!$tieneDatos) {
                continue;
            }

            $espacioId = $this->nullableInt($item['id'] ?? null);
            $data = [
                'descripcion' => $descripcion !== '' ? $descripcion : 'Espacio sin descripción',
                'ubicacion' => $this->stringOrNull($item['ubicacion'] ?? null),
                'unidad_gestora' => $this->stringOrNull($item['unidad_gestora'] ?? null),
                'tiempo_uso_horas' => trim((string) ($item['tiempo_uso_horas'] ?? '')) === ''
                    ? null
                    : (float) $item['tiempo_uso_horas'],
            ];

            $espacio = $espacioId
                ? $record->espaciosInstitucionales()->whereKey($espacioId)->first()
                : null;

            if ($espacio) {
                $espacio->update($data);
            } else {
                $espacio = $record->espaciosInstitucionales()->create($data);
            }

            $this->espacios_institucionales[$i]['id'] = $espacio->id;
            $idsEnEstado[] = $espacio->id;
        }

        $record->espaciosInstitucionales()
            ->when(!empty($idsEnEstado), fn ($query) => $query->whereNotIn('id', $idsEnEstado))
            ->delete();
    }

    public function addEspacioInstitucional(): void
    {
        $this->espacios_institucionales[] = $this->nuevoEspacioInstitucional();
    }

    public function removeEspacioInstitucional(int $i): void
    {
        unset($this->espacios_institucionales[$i]);
        $this->espacios_institucionales = array_values($this->espacios_institucionales);

        if (empty($this->espacios_institucionales)) {
            $this->espacios_institucionales = [$this->nuevoEspacioInstitucional()];
        }

        $this->autoGuardarBorrador();
    }

    private function guardarEquipoParcial(Proyecto $record): void
    {
        $coordId = auth()->user()->empleado?->id;
        $empleadoIds = collect($this->empleado_proyecto)
            ->pluck('empleado_id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->reject(fn($id) => $id === (int) $coordId)
            ->unique()
            ->values();

        $record->empleado_proyecto()->delete();
        foreach ($empleadoIds as $empleadoId) {
            $record->empleado_proyecto()->create([
                'empleado_id' => $empleadoId,
                'rol' => 'Integrante',
            ]);
        }

        $record->estudiante_proyecto()->delete();
        foreach ($this->estudiante_proyecto as $i => $item) {
            $tipo = $this->normalizeTipoParticipacionEstudiante($item['tipo_participacion_estudiante'] ?? '')
                ?: ($item['tipo_participacion_estudiante'] ?? '');

            if ($tipo === '') {
                continue;
            }

            $isAsignatura = $this->isTipoParticipacionAsignatura($tipo);
            $hombres = (int) ($item['cantidad_estudiantes_hombres'] ?? 0);
            $mujeres = (int) ($item['cantidad_estudiantes_mujeres'] ?? 0);
            $carreraId = $isAsignatura ? $this->carreraIdParaAsignatura($item['asignatura_id'] ?? null) : null;

            $this->estudiante_proyecto[$i]['tipo_participacion_estudiante'] = $tipo;
            $this->estudiante_proyecto[$i]['carrera_id'] = $carreraId;
            $this->estudiante_proyecto[$i]['total_estudiantes'] = $hombres + $mujeres;

            $data = [
                'tipo_participacion_estudiante' => $tipo,
                'carrera_id' => $carreraId,
                'asignatura_id' => $isAsignatura ? $this->nullableInt($item['asignatura_id'] ?? null) : null,
                'periodo_academico_id' => $isAsignatura ? $this->stringOrNull($item['periodo_academico_id'] ?? null) : null,
                'cantidad_estudiantes_hombres' => $hombres,
                'cantidad_estudiantes_mujeres' => $mujeres,
                'total_estudiantes' => $hombres + $mujeres,
            ];

            if (!Schema::hasColumn('estudiante_proyecto', 'carrera_id')) {
                unset($data['carrera_id']);
            }

            $record->estudiante_proyecto()->create($data);
        }

        $integranteIds = $this->ids(collect($this->integrante_internacional_proyecto)->pluck('integrante_internacional_id')->all());
        if (empty($integranteIds)) {
            $record->integrante_internacional_proyecto()->delete();
            return;
        }

        $record->integrante_internacional_proyecto()
            ->whereNotIn('integrante_internacional_id', $integranteIds)
            ->delete();

        foreach ($integranteIds as $integranteId) {
            $integranteProyecto = $record->integrante_internacional_proyecto()
                ->withTrashed()
                ->where('integrante_internacional_id', $integranteId)
                ->first();

            if ($integranteProyecto) {
                if ($integranteProyecto->trashed()) {
                    $integranteProyecto->restore();
                }

                $integranteProyecto->update(['rol' => 'Integrante']);
                continue;
            }

            $record->integrante_internacional_proyecto()->create([
                'integrante_internacional_id' => $integranteId,
                'rol' => 'Integrante',
            ]);
        }
    }

    private function guardarContrapartesParcial(Proyecto $record): void
    {
        $record->entidad_contraparte()->each(fn($entidad) => $entidad->instrumento_formalizacion()->delete());
        $record->entidad_contraparte()->delete();

        foreach ($this->entidad_contraparte as $ci => $item) {
            $tieneDatos = collect([
                $item['nombre'] ?? '',
                $item['tipo_entidad'] ?? '',
                $item['nombre_contacto'] ?? '',
                $item['cargo_contacto'] ?? '',
                $item['telefono'] ?? '',
                $item['correo'] ?? '',
                $item['descripcion_acuerdos'] ?? '',
            ])->contains(fn($value) => trim((string) $value) !== '');

            if (!$tieneDatos) {
                continue;
            }

            $entidad = $record->entidad_contraparte()->create([
                'nombre' => $item['nombre'] ?: 'Contraparte sin nombre',
                'tipo_entidad' => $item['tipo_entidad'] ?? '',
                'nombre_contacto' => $item['nombre_contacto'] ?? '',
                'cargo_contacto' => $item['cargo_contacto'] ?? '',
                'telefono' => $item['telefono'] ?? '',
                'correo' => $item['correo'] ?? '',
                'descripcion_acuerdos' => $item['descripcion_acuerdos'] ?? '',
            ]);

            foreach ($item['instrumento_formalizacion'] ?? [] as $ii => $inst) {
                $tipo = $this->normalizeInstrumentoTipo($inst['tipo_documento'] ?? '');
                $documentoUrl = $this->normalizarRutaDocumentoInstrumento($inst['documento_url'] ?? null);
                $nombreArchivo = $inst['nombre_archivo'] ?? null;

                if ($this->instrumentoTieneArchivoNuevo($inst)) {
                    $nombreArchivo = $inst['documento_file']->getClientOriginalName();
                    $documentoUrl = $this->guardarDocumentoInstrumento($inst['documento_file']);
                }

                if ($tipo === '' && empty($documentoUrl)) {
                    continue;
                }

                $instrumento = $entidad->instrumento_formalizacion()->create([
                    'tipo_documento' => $tipo,
                    'documento_url' => $documentoUrl,
                    'nombre_archivo' => $nombreArchivo,
                ]);

                $this->entidad_contraparte[$ci]['instrumento_formalizacion'][$ii]['id'] = $instrumento->id;
                $this->entidad_contraparte[$ci]['instrumento_formalizacion'][$ii]['tipo_documento'] = $tipo;
                $this->entidad_contraparte[$ci]['instrumento_formalizacion'][$ii]['documento_url'] = $documentoUrl;
                $this->entidad_contraparte[$ci]['instrumento_formalizacion'][$ii]['nombre_archivo'] = $nombreArchivo;
                $this->entidad_contraparte[$ci]['instrumento_formalizacion'][$ii]['documento_file'] = null;
            }
        }
    }

    private function guardarActividadesParcial(Proyecto $record): void
    {
        $validEmpleados = $this->responsableIdsDisponibles($record);
        $actividadIdsEnEstado = [];

        foreach ($this->actividades as $i => $item) {
            $tieneDatos = trim((string) ($item['descripcion'] ?? '')) !== ''
                || !empty($item['fecha_inicio'])
                || !empty($item['fecha_finalizacion'])
                || !empty($item['horas'])
                || !empty($item['empleados']);

            if (!$tieneDatos) {
                continue;
            }

            $actividadId = $this->nullableInt($item['id'] ?? null);
            $actividad = $actividadId
                ? $record->actividades()->whereKey($actividadId)->first()
                : null;

            $data = [
                'descripcion' => trim((string) ($item['descripcion'] ?? '')) !== '' ? $item['descripcion'] : 'Actividad sin descripción',
                'fecha_inicio' => $this->dateOrNull($item['fecha_inicio'] ?? null),
                'fecha_finalizacion' => $this->dateOrNull($item['fecha_finalizacion'] ?? null),
                'horas' => (int) ($item['horas'] ?? 0),
            ];

            if ($actividad) {
                $actividad->update($data);
            } else {
                $actividad = $record->actividades()->create($data);
            }

            $ids = collect($item['empleados'] ?? [])
                ->filter()
                ->map(fn($id) => (int) $id)
                ->intersect($validEmpleados)
                ->unique()
                ->values()
                ->toArray();

            $this->actividades[$i]['empleados'] = array_map('strval', $ids);
            $actividad->empleados()->sync($ids);

            $this->actividades[$i]['id'] = $actividad->id;
            $this->actividades[$i]['fecha_inicio'] = $this->dateForInput($actividad->fecha_inicio);
            $this->actividades[$i]['fecha_finalizacion'] = $this->dateForInput($actividad->fecha_finalizacion);
            $this->actividades[$i]['horas'] = $actividad->horas ?? '';
            $actividadIdsEnEstado[] = $actividad->id;
        }

        $actividadesAEliminar = $record->actividades()
            ->when(!empty($actividadIdsEnEstado), fn($query) => $query->whereNotIn('id', $actividadIdsEnEstado))
            ->get();

        foreach ($actividadesAEliminar as $actividad) {
            $actividad->empleados()->detach();
            $actividad->delete();
        }
    }

    private function guardarMarcoLogicoParcial(Proyecto $record): void
    {
        $record->update(['objetivo_general' => $this->objetivo_general]);
        $objetivoIdsEnEstado = [];

        foreach ($this->objetivosEspecificos as $oi => $objData) {
            $descripcion = trim((string) ($objData['descripcion'] ?? ''));
            $objetivoId = $this->nullableInt($objData['id'] ?? null);
            $resultados = collect($objData['resultados'] ?? []);
            $tieneResultados = $resultados->contains(function ($resultado) {
                if ($this->nullableInt($resultado['id'] ?? null)) {
                    return true;
                }

                return trim((string) ($resultado['nombre_resultado'] ?? '')) !== ''
                    || trim((string) ($resultado['nombre_indicador'] ?? '')) !== ''
                    || trim((string) ($resultado['nombre_medio_verificacion'] ?? '')) !== '';
            });

            if (!$objetivoId && $descripcion === '' && !$tieneResultados) {
                continue;
            }

            $objetivo = $objetivoId
                ? $record->objetivosEspecificos()->whereKey($objetivoId)->first()
                : null;

            $objetivoData = [
                'descripcion' => $descripcion !== '' ? $descripcion : 'Objetivo específico sin descripción',
                'orden' => $oi + 1,
            ];

            if ($objetivo) {
                $objetivo->update($objetivoData);
            } else {
                $objetivo = $record->objetivosEspecificos()->create($objetivoData);
            }

            $this->objetivosEspecificos[$oi]['id'] = $objetivo->id;
            $objetivoIdsEnEstado[] = $objetivo->id;
            $resultadoIdsEnEstado = [];

            foreach ($objData['resultados'] ?? [] as $ri => $rData) {
                $resultadoId = $this->nullableInt($rData['id'] ?? null);
                $tieneDatos = trim((string) ($rData['nombre_resultado'] ?? '')) !== ''
                    || trim((string) ($rData['nombre_indicador'] ?? '')) !== ''
                    || trim((string) ($rData['nombre_medio_verificacion'] ?? '')) !== '';

                if (!$resultadoId && !$tieneDatos) {
                    continue;
                }

                $resultado = $resultadoId
                    ? $objetivo->resultados()->whereKey($resultadoId)->first()
                    : null;

                $resultadoData = [
                    'nombre_resultado' => $rData['nombre_resultado'] ?: 'Resultado sin nombre',
                    'nombre_indicador' => $rData['nombre_indicador'] ?? '',
                    'nombre_medio_verificacion' => $rData['nombre_medio_verificacion'] ?? '',
                    'plazo' => $this->normalizePlazo($rData['plazo'] ?? '') ?: 'corto_plazo',
                    'orden' => $ri + 1,
                ];

                if ($resultado) {
                    $resultado->update($resultadoData);
                } else {
                    $resultado = $objetivo->resultados()->create($resultadoData);
                }

                $this->objetivosEspecificos[$oi]['resultados'][$ri]['id'] = $resultado->id;
                $this->objetivosEspecificos[$oi]['resultados'][$ri]['plazo'] = $resultadoData['plazo'];
                $resultadoIdsEnEstado[] = $resultado->id;
            }

            $resultadosAEliminar = $objetivo->resultados()
                ->when(!empty($resultadoIdsEnEstado), fn($query) => $query->whereNotIn('id', $resultadoIdsEnEstado))
                ->get();

            foreach ($resultadosAEliminar as $resultado) {
                $resultado->delete();
            }
        }

        $objetivosAEliminar = $record->objetivosEspecificos()
            ->when(!empty($objetivoIdsEnEstado), fn($query) => $query->whereNotIn('id', $objetivoIdsEnEstado))
            ->get();

        foreach ($objetivosAEliminar as $objetivo) {
            $objetivo->resultados()->delete();
            $objetivo->delete();
        }
    }

    private function guardarPresupuestoParcial(Proyecto $record): void
    {
        $this->aporte_institucional = $this->normalizeAporteRows($this->aporte_institucional);
        $this->recalculateAporteInstitucional();

        $record->aporteInstitucional()->delete();
        foreach ($this->aporte_institucional as $item) {
            $record->aporteInstitucional()->create([
                'concepto' => $item['concepto'],
                'unidad' => $item['unidad'],
                'cantidad' => (float) ($item['cantidad'] ?? 0),
                'costo_unitario' => (float) ($item['costo_unitario'] ?? 0),
                'costo_total' => (float) ($item['costo_total'] ?? 0),
            ]);
        }

        $record->presupuesto()->updateOrCreate([], [
            'aporte_contraparte' => (float) $this->aporte_contraparte,
            'aporte_internacionales' => (float) $this->aporte_internacionales,
            'aporte_otras_universidades' => (float) $this->aporte_otras_universidades,
            'aporte_comunidad' => (float) $this->aporte_comunidad,
            'otros_aportes' => (float) $this->otros_aportes,
        ]);
    }

    private function guardarAnexoParcial(Proyecto $record): void
    {
        if (!$this->newAnexo || !is_object($this->newAnexo)) {
            return;
        }

        $path = $this->newAnexo->store('anexos', 'public');
        $record->anexos()->create(['documento_url' => $path]);
        $this->newAnexo = null;
    }

    private function guardarFirmasParcial(Proyecto $record): void
    {
        $map = [
            'Jefe Departamento' => $this->jefe_empleado_id,
            'Director centro' => $this->decano_empleado_id,
            'Enlace Vinculacion' => $this->enlace_empleado_id,
        ];

        foreach ($map as $nombre => $empId) {
            $cargo = CargoFirma::join('tipo_cargo_firma', 'tipo_cargo_firma.id', '=', 'cargo_firma.tipo_cargo_firma_id')
                ->where('tipo_cargo_firma.nombre', $nombre)
                ->select('cargo_firma.*')
                ->first();

            if (!$cargo) {
                continue;
            }

            if (!$empId) {
                $record->firma_proyecto()
                    ->where('cargo_firma_id', $cargo->id)
                    ->where('estado_revision', 'Pendiente')
                    ->delete();
                continue;
            }

            $record->guardarFirmaDeCargo($cargo->id, \App\Models\Personal\Empleado::findOrFail((int) $empId), [
                'estado_revision' => 'Pendiente',
                'firma_id' => null,
                'sello_id' => null,
                'fecha_firma' => null,
            ]);
        }
    }

    private function limpiarRelacionesDependientes(): void
    {
        $facultades = $this->ids($this->facultades_centros);
        if (empty($facultades)) {
            $this->departamentos_academicos = [];
            $this->carreras = [];
        } else {
            $departamentosValidos = DepartamentoAcademico::whereIn('centro_facultad_id', $facultades)->pluck('id')->map(fn($id) => (string) $id)->toArray();
            $this->departamentos_academicos = collect($this->departamentos_academicos)->map(fn($id) => (string) $id)->intersect($departamentosValidos)->values()->toArray();
        }

        $departamentos = $this->ids($this->departamentos_academicos);
        if (empty($departamentos)) {
            $this->carreras = [];
        } else {
            $carrerasValidas = Carrera::where(function ($q) use ($departamentos) {
                $q->whereHas('departamentosAcademicos', fn($dq) => $dq->whereIn('departamento_academico.id', $departamentos))
                    ->orWhereIn('departamento_academico_id', $departamentos);
            })->pluck('id')->map(fn($id) => (string) $id)->toArray();

            $this->carreras = collect($this->carreras)->map(fn($id) => (string) $id)->intersect($carrerasValidas)->values()->toArray();
        }

        $this->cargarOpcionesPracticaAsignatura();
        $this->limpiarAsignaturasIncompatibles();
    }

    private function ids(array $values): array
    {
        return collect($values)
            ->filter(fn($id) => $id !== null && $id !== '')
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->toArray();
    }

    private function idsComoStrings(array $values): array
    {
        return collect($this->ids($values))
            ->map(fn($id) => (string) $id)
            ->values()
            ->toArray();
    }

    private function municipiosValidosImpacto(): array
    {
        $departamentos = $this->ids($this->departamento_geo);

        if (empty($departamentos)) {
            return [];
        }

        return Municipio::whereIn('departamento_id', $departamentos)
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->toArray();
    }

    private function filtrarMunicipiosImpactoSeleccionados(): void
    {
        $validos = $this->municipiosValidosImpacto();

        $this->departamento_geo = $this->idsComoStrings($this->departamento_geo);
        $this->municipio_geo = collect($this->idsComoStrings($this->municipio_geo))
            ->intersect($validos)
            ->values()
            ->toArray();
    }

    public function actualizarDepartamentosImpacto(array $ids): void
    {
        $this->departamento_geo = $this->idsComoStrings($ids);
        $this->filtrarMunicipiosImpactoSeleccionados();
        $this->autoGuardarBorrador();
    }

    public function actualizarMunicipiosImpacto(array $ids): void
    {
        $this->municipio_geo = $this->idsComoStrings($ids);
        $this->filtrarMunicipiosImpactoSeleccionados();
        $this->autoGuardarBorrador();
    }

    private function nullableInt(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    private function cargarOpcionesPracticaAsignatura(): void
    {
        $carreras = $this->ids($this->carreras);

        if (empty($carreras) || !Schema::hasColumn('asignaturas', 'carrera_id')) {
            $this->asignaturasDisponibles = [];
        } else {
            $this->asignaturasDisponibles = Asignatura::whereIn('carrera_id', $carreras)
                ->orderBy('codigo')
                ->orderBy('nombre')
                ->get()
                ->mapWithKeys(fn($asignatura) => [
                    $asignatura->id => trim("{$asignatura->codigo} - {$asignatura->nombre}", ' -'),
                ])
                ->toArray();
        }

        $periodos = PeriodoAcademico::orderBy('nombre')->pluck('nombre', 'id')->toArray();

        $this->periodosAcademicosDisponibles = !empty($periodos)
            ? $periodos
            : collect($this->periodosAcademicosBase())->mapWithKeys(fn($periodo) => [$periodo => $periodo])->toArray();
    }

    private function carrerasSeleccionadasOptions(): array
    {
        $carreras = $this->ids($this->carreras);

        if (empty($carreras)) {
            return [];
        }

        return Carrera::whereIn('id', $carreras)
            ->orderBy('nombre')
            ->pluck('nombre', 'id')
            ->toArray();
    }

    private function limpiarAsignaturasIncompatibles(): void
    {
        $validas = array_map('strval', array_keys($this->asignaturasDisponibles));

        foreach ($this->estudiante_proyecto as $i => $item) {
            $tipo = $this->normalizeTipoParticipacionEstudiante($item['tipo_participacion_estudiante'] ?? '')
                ?: ($item['tipo_participacion_estudiante'] ?? '');

            if (!$this->isTipoParticipacionAsignatura($tipo)) {
                $this->estudiante_proyecto[$i]['carrera_id'] = null;
                $this->estudiante_proyecto[$i]['asignatura_id'] = null;
                $this->estudiante_proyecto[$i]['periodo_academico_id'] = null;
                continue;
            }

            $asignaturaId = (string) ($item['asignatura_id'] ?? '');
            if ($asignaturaId === '' || !in_array($asignaturaId, $validas, true)) {
                $this->estudiante_proyecto[$i]['carrera_id'] = null;
                $this->estudiante_proyecto[$i]['asignatura_id'] = null;
            }
        }

        if ($this->isTipoParticipacionAsignatura($this->nuevoEstudiante['tipo_participacion_estudiante'] ?? '')) {
            $asignaturaId = (string) ($this->nuevoEstudiante['asignatura_id'] ?? '');
            if ($asignaturaId === '' || !in_array($asignaturaId, $validas, true)) {
                $this->nuevoEstudiante['carrera_id'] = null;
                $this->nuevoEstudiante['asignatura_id'] = null;
            }
        }
    }

    private function asignaturaPerteneceACarrerasSeleccionadas(mixed $asignaturaId): bool
    {
        $asignaturaId = $this->nullableInt($asignaturaId);
        $carreras = $this->ids($this->carreras);

        if (!$asignaturaId || empty($carreras) || !Schema::hasColumn('asignaturas', 'carrera_id')) {
            return false;
        }

        return Asignatura::whereKey($asignaturaId)
            ->whereIn('carrera_id', $carreras)
            ->exists();
    }

    private function carreraIdParaAsignatura(mixed $asignaturaId): ?int
    {
        $asignaturaId = $this->nullableInt($asignaturaId);
        $carreras = $this->ids($this->carreras);

        if (!$asignaturaId || empty($carreras) || !Schema::hasColumn('asignaturas', 'carrera_id')) {
            return null;
        }

        $carreraId = Asignatura::whereKey($asignaturaId)
            ->whereIn('carrera_id', $carreras)
            ->value('carrera_id');

        return $carreraId ? (int) $carreraId : null;
    }

    private function periodosAcademicosBase(): array
    {
        return [
            'Primer Periodo',
            'Segundo Periodo',
            'Tercer Periodo',
            'Primer Semestre',
            'Segundo Semestre',
        ];
    }

    private function dateOrNull(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    protected function saveStep1(): void
    {
        $this->cargarMetasPorOds();

        $this->validate([
            'nombre_proyecto' => 'required|string|max:255',
            'modalidad_id' => 'required|integer',
            'categoria' => 'required|array|min:1',
            'ejes_prioritarios_unah' => 'required|array|min:1',
            'facultades_centros' => 'required|array|min:1',
            'facultades_centros.*' => 'integer|exists:centro_facultad,id',
            'departamentos_academicos' => 'required|array|min:1',
            'departamentos_academicos.*' => 'integer|exists:departamento_academico,id',
            'carreras' => 'required|array|min:1',
            'carreras.*' => 'integer|exists:carrera,id',
            'fecha_inicio' => 'required|date',
            'fecha_finalizacion' => 'required|date|after_or_equal:fecha_inicio',
            'programa_pertenece' => 'required|string',
            'lineas_investigacion_academica' => 'required|string',
            'ods' => 'required|array|min:1',
        ]);
        $record = $this->ensureRecord();
        $record->update([
            'nombre_proyecto' => $this->nombre_proyecto,
            'modalidad_id' => $this->modalidad_id,
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_finalizacion' => $this->fecha_finalizacion,
            'programa_pertenece' => $this->programa_pertenece,
            'lineas_investigacion_academica' => $this->lineas_investigacion_academica,
        ]);
        $record->categoria()->sync($this->categoria);
        $record->ejes_prioritarios_unah()->sync($this->ejes_prioritarios_unah);
        $record->facultades_centros()->sync($this->facultades_centros);
        $record->departamentos_academicos()->sync($this->departamentos_academicos ?? []);
        $record->carreras()->sync($this->carreras ?? []);
        $record->ods()->sync($this->ods);
        Notification::make()->title('Paso I guardado')->success()->send();
    }

    public function updatedOds($value = null, ?string $key = null): void
    {
        $this->cargarMetasPorOds();
        $this->autoGuardarBorrador();
    }

    private function cargarMetasPorOds(): void
    {
        $odsSeleccionados = collect($this->ods)
            ->filter(fn($id) => $id !== null && $id !== '')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if ($odsSeleccionados->isEmpty() && !empty($this->metasContribuye)) {
            $odsSeleccionados = MetaContribuye::whereIn('id', $this->ids($this->metasContribuye))
                ->pluck('ods_id')
                ->map(fn($id) => (int) $id)
                ->unique()
                ->values();
        }

        $this->ods = $odsSeleccionados->map(fn($id) => (string) $id)->toArray();

        if ($odsSeleccionados->isEmpty()) {
            $this->metasDisponibles = [];
            $this->metasContribuye = [];
            return;
        }

        $metas = MetaContribuye::query()
            ->whereIn('ods_id', $odsSeleccionados->all())
            ->orderBy('ods_id')
            ->orderBy('numero_meta')
            ->get();

        $this->metasDisponibles = $metas
            ->mapWithKeys(fn($meta) => [
                (string) $meta->id => "Meta {$meta->numero_meta}: {$meta->descripcion}",
            ])
            ->toArray();

        $metasValidas = array_map('strval', array_keys($this->metasDisponibles));

        $this->metasContribuye = collect($this->metasContribuye)
            ->filter(fn($id) => $id !== null && $id !== '')
            ->map(fn($id) => (string) $id)
            ->filter(fn($id) => in_array($id, $metasValidas, true))
            ->unique()
            ->values()
            ->toArray();
    }

    protected function saveStep2(): void
    {
        // Require at least one student group
        $hasStudents = !empty($this->estudiante_proyecto) &&
            collect($this->estudiante_proyecto)->contains(fn($e) => !empty($e['tipo_participacion_estudiante']));
        if (!$hasStudents) {
            $this->addError('estudiante_proyecto', 'Debe agregar al menos un grupo de participación de estudiantes.');
            return;
        }

        $this->validate([
            'empleado_proyecto.*.empleado_id' => 'nullable|exists:empleado,id',
            'estudiante_proyecto.*.tipo_participacion_estudiante' => 'nullable|string',
            'estudiante_proyecto.*.carrera_id' => 'nullable|exists:carrera,id',
            'estudiante_proyecto.*.asignatura_id' => 'nullable|exists:asignaturas,id',
            'estudiante_proyecto.*.periodo_academico_id' => 'nullable|string|max:50',
            'estudiante_proyecto.*.cantidad_estudiantes_hombres' => 'nullable|integer|min:0',
            'estudiante_proyecto.*.cantidad_estudiantes_mujeres' => 'nullable|integer|min:0',
            'integrante_internacional_proyecto.*.integrante_internacional_id' => 'nullable|exists:integrante_internacional,id',
        ]);

        $hasInvalidStudentRows = false;
        foreach ($this->estudiante_proyecto as $i => $item) {
            $tipo = $this->normalizeTipoParticipacionEstudiante($item['tipo_participacion_estudiante'] ?? '') ?: ($item['tipo_participacion_estudiante'] ?? '');
            $this->estudiante_proyecto[$i]['tipo_participacion_estudiante'] = $tipo;

            if ($tipo !== '' && !in_array($tipo, $this->tipoParticipacionEstudiantePermitidos, true)) {
                $this->addError("estudiante_proyecto.$i.tipo_participacion_estudiante", 'Seleccione un tipo de participación válido.');
                $hasInvalidStudentRows = true;
                continue;
            }

            if ($this->isTipoParticipacionAsignatura($tipo)) {
                if (empty($this->carreras)) {
                    $this->addError("estudiante_proyecto.$i.carrera_id", 'Seleccione primero una carrera en Información General.');
                    $hasInvalidStudentRows = true;
                }
                if (empty($item['asignatura_id'])) {
                    $this->addError("estudiante_proyecto.$i.asignatura_id", 'Seleccione la asignatura.');
                    $hasInvalidStudentRows = true;
                } elseif (!$this->asignaturaPerteneceACarrerasSeleccionadas($item['asignatura_id'])) {
                    $this->addError("estudiante_proyecto.$i.asignatura_id", 'La asignatura no corresponde a la carrera seleccionada.');
                    $hasInvalidStudentRows = true;
                }
                if (empty($item['periodo_academico_id'])) {
                    $this->addError("estudiante_proyecto.$i.periodo_academico_id", 'Seleccione el periodo académico.');
                    $hasInvalidStudentRows = true;
                }

                $this->estudiante_proyecto[$i]['carrera_id'] = $this->carreraIdParaAsignatura($item['asignatura_id'] ?? null);
            } else {
                $this->estudiante_proyecto[$i]['carrera_id'] = null;
                $this->estudiante_proyecto[$i]['asignatura_id'] = null;
                $this->estudiante_proyecto[$i]['periodo_academico_id'] = null;
            }
        }

        if ($hasInvalidStudentRows) {
            return;
        }

        $record = $this->ensureRecord();
        $coordId = auth()->user()->empleado->id;
        foreach ($this->empleado_proyecto as $item) {
            if (!empty($item['empleado_id']) && $item['empleado_id'] != $coordId) {
                $record->empleado_proyecto()->firstOrCreate(
                    ['empleado_id' => $item['empleado_id']],
                    ['rol' => 'Integrante']
                );
            }
        }
        $record->estudiante_proyecto()->delete();
        foreach ($this->estudiante_proyecto as $item) {
            $tipo = $this->normalizeTipoParticipacionEstudiante($item['tipo_participacion_estudiante'] ?? '') ?: ($item['tipo_participacion_estudiante'] ?? '');
            if (!empty($tipo)) {
                $isAsignatura = $this->isTipoParticipacionAsignatura($tipo);
                $data = [
                    'tipo_participacion_estudiante' => $tipo,
                    'carrera_id' => $isAsignatura ? $this->carreraIdParaAsignatura($item['asignatura_id'] ?? null) : null,
                    'asignatura_id' => $isAsignatura ? ($item['asignatura_id'] ?? null) : null,
                    'periodo_academico_id' => $isAsignatura ? ($item['periodo_academico_id'] ?? null) : null,
                    'cantidad_estudiantes_hombres' => $item['cantidad_estudiantes_hombres'] ?? 0,
                    'cantidad_estudiantes_mujeres' => $item['cantidad_estudiantes_mujeres'] ?? 0,
                    'total_estudiantes' => ($item['cantidad_estudiantes_hombres'] ?? 0) + ($item['cantidad_estudiantes_mujeres'] ?? 0),
                ];

                if (!Schema::hasColumn('estudiante_proyecto', 'carrera_id')) {
                    unset($data['carrera_id']);
                }

                $record->estudiante_proyecto()->create($data);
            }
        }
        foreach ($this->integrante_internacional_proyecto as $item) {
            if (!empty($item['integrante_internacional_id'])) {
                $record->integrante_internacional_proyecto()->firstOrCreate(
                    ['integrante_internacional_id' => $item['integrante_internacional_id']],
                    []
                );
            }
        }
        Notification::make()->title('Paso II guardado')->success()->send();
    }

    protected function saveStep3(): void
    {
        foreach ($this->entidad_contraparte as $ci => $item) {
            foreach ($item['instrumento_formalizacion'] ?? [] as $ii => $inst) {
                $tipo = $this->normalizeInstrumentoTipo($inst['tipo_documento'] ?? '');
                if ($tipo !== '') {
                    $this->entidad_contraparte[$ci]['instrumento_formalizacion'][$ii]['tipo_documento'] = $tipo;
                }
            }
        }

        $this->validate([
            'entidad_contraparte.*.instrumento_formalizacion.*.tipo_documento' => 'nullable|in:' . implode(',', $this->instrumentoTipos),
            'entidad_contraparte.*.instrumento_formalizacion.*.documento_file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        $hasMissingDocument = false;
        foreach ($this->entidad_contraparte as $ci => $item) {
            foreach ($item['instrumento_formalizacion'] ?? [] as $ii => $inst) {
                if (empty($inst['tipo_documento'])) continue;
                $isExisting = !empty($inst['id']);
                $hasStoredDocument = !empty($inst['documento_url']);
                $hasUploadedDocument = $this->instrumentoTieneArchivoNuevo($inst);
                if (!$isExisting && !$hasStoredDocument && !$hasUploadedDocument) {
                    $this->addError("entidad_contraparte.$ci.instrumento_formalizacion.$ii.documento_file", 'El documento es obligatorio para instrumentos nuevos.');
                    $hasMissingDocument = true;
                }
            }
        }
        if ($hasMissingDocument) return;

        $record = $this->ensureRecord();
        $record->entidad_contraparte()->each(fn($e) => $e->instrumento_formalizacion()->delete());
        $record->entidad_contraparte()->delete();
        foreach ($this->entidad_contraparte as $ci => $item) {
            if (!empty($item['nombre'])) {
                $entidad = $record->entidad_contraparte()->create([
                    'nombre' => $item['nombre'],
                    'tipo_entidad' => $item['tipo_entidad'] ?? '',
                    'nombre_contacto' => $item['nombre_contacto'] ?? '',
                    'cargo_contacto' => $item['cargo_contacto'] ?? '',
                    'telefono' => $item['telefono'] ?? '',
                    'correo' => $item['correo'] ?? '',
                    'descripcion_acuerdos' => $item['descripcion_acuerdos'] ?? '',
                ]);
                foreach ($item['instrumento_formalizacion'] ?? [] as $ii => $inst) {
                    if (!empty($inst['tipo_documento'])) {
                        $documentoUrl = $this->normalizarRutaDocumentoInstrumento($inst['documento_url'] ?? null);
                        $nombreArchivo = $inst['nombre_archivo'] ?? null;
                        if ($this->instrumentoTieneArchivoNuevo($inst)) {
                            $nombreArchivo = $inst['documento_file']->getClientOriginalName();
                            $documentoUrl = $this->guardarDocumentoInstrumento($inst['documento_file']);
                        }
                        $instrumento = $entidad->instrumento_formalizacion()->create([
                            'tipo_documento' => $inst['tipo_documento'],
                            'documento_url' => $documentoUrl,
                            'nombre_archivo' => $nombreArchivo,
                        ]);
                        $this->entidad_contraparte[$ci]['instrumento_formalizacion'][$ii]['id'] = $instrumento->id;
                        $this->entidad_contraparte[$ci]['instrumento_formalizacion'][$ii]['documento_url'] = $documentoUrl;
                        $this->entidad_contraparte[$ci]['instrumento_formalizacion'][$ii]['nombre_archivo'] = $nombreArchivo;
                        $this->entidad_contraparte[$ci]['instrumento_formalizacion'][$ii]['documento_file'] = null;
                    }
                }
            }
        }
        Notification::make()->title('Paso III guardado')->success()->send();
    }

    protected function saveStep4(): void
    {
        $this->resetErrorBag();

        $this->validate([
            'actividades' => 'required|array|min:1',
            'actividades.*.descripcion' => 'required|string',
            'actividades.*.fecha_inicio' => 'required|date',
            'actividades.*.fecha_finalizacion' => 'required|date',
        ]);

        foreach ($this->actividades as $i => $actividad) {
            $fechaInicio = $this->dateOrNull($actividad['fecha_inicio'] ?? null);
            $fechaFin = $this->dateOrNull($actividad['fecha_finalizacion'] ?? null);

            if ($fechaInicio && $fechaFin && $fechaFin < $fechaInicio) {
                $this->addError(
                    "actividades.$i.fecha_finalizacion",
                    'La fecha de finalización debe ser igual o posterior a la fecha de inicio de la actividad.'
                );
            }
        }

        if (!$this->getErrorBag()->isEmpty()) {
            return;
        }

        $record = $this->ensureRecord();
        $validEmpleados = $this->responsableIdsDisponibles($record);
        $hasInvalidResponsables = false;

        foreach ($this->actividades as $i => $item) {
            $selected = collect($item['empleados'] ?? [])
                ->filter()->map(fn($id) => (int) $id)->unique()->values()->toArray();
            $invalid = array_diff($selected, $validEmpleados);
            if (!empty($invalid)) {
                $this->addError("actividades.$i.empleados", 'Seleccione únicamente responsables del equipo ejecutor.');
                $hasInvalidResponsables = true;
            }
        }
        if ($hasInvalidResponsables) return;

        DB::transaction(fn() => $this->guardarActividadesParcial($record));
        Notification::make()->title('Paso IV guardado')->success()->send();
    }

    protected function saveStep5(): void
    {
        $this->trimCamposDescripcion();
        $this->validate($this->rulesDescripcion());

        $record = $this->ensureRecord();
        $record->update([
            'resumen' => $this->resumen,
            'descripcion_participantes' => $this->descripcion_participantes,
            'definicion_problema' => $this->definicion_problema,
            'alineamiento_reforma' => $this->alineamiento_reforma,
            'impacto_deseado' => $this->impacto_deseado,
            'metodologia' => $this->metodologia,
            'bibliografia' => $this->bibliografia,
        ]);
        Notification::make()->title('Paso V guardado')->success()->send();
    }

    protected function saveStep6(): void
    {
        $this->calcTotales();
        $record = $this->ensureRecord();
        $record->update([
            'indigenas_hombres' => $this->indigenas_hombres,
            'indigenas_mujeres' => $this->indigenas_mujeres,
            'afroamericanos_hombres' => $this->afroamericanos_hombres,
            'afroamericanos_mujeres' => $this->afroamericanos_mujeres,
            'mestizos_hombres' => $this->mestizos_hombres,
            'mestizos_mujeres' => $this->mestizos_mujeres,
            'hombres' => $this->hombres,
            'mujeres' => $this->mujeres,
            'poblacion_participante' => $this->poblacion_participante,
            'pais' => $this->pais,
            'region' => $this->region,
            'caserio' => $this->caserio,
            'aldea' => $this->aldea,
        ]);
        $this->filtrarMunicipiosImpactoSeleccionados();
        $record->departamento()->sync($this->ids($this->departamento_geo));
        $record->municipio()->sync($this->ids($this->municipio_geo));
        Notification::make()->title('Paso VI guardado')->success()->send();
    }

    protected function saveStep7(): void
    {
        $this->validarMarcoLogicoCompleto();
        $record = $this->ensureRecord();
        DB::transaction(fn() => $this->guardarMarcoLogicoParcial($record));
        Notification::make()->title('Paso VII guardado')->success()->send();
    }

    protected function saveStep8(): void
    {
        $record = $this->ensureRecord();
        $this->aporte_institucional = $this->normalizeAporteRows($this->aporte_institucional);
        $this->recalculateAporteInstitucional();
        $totalAporteInstitucional = collect($this->aporte_institucional)->sum('costo_total');

        $record->aporteInstitucional()->delete();
        foreach ($this->aporte_institucional as $item) {
            $record->aporteInstitucional()->create([
                'concepto' => $item['concepto'],
                'unidad' => $item['unidad'],
                'cantidad' => $item['cantidad'] ?? 0,
                'costo_unitario' => $item['costo_unitario'] ?? 0,
                'costo_total' => $item['costo_total'] ?? 0,
            ]);
        }
        $record->update(['total_aporte_institucional' => $totalAporteInstitucional]);
        $record->presupuesto()->updateOrCreate([], [
            'aporte_contraparte' => $this->aporte_contraparte,
            'aporte_internacionales' => $this->aporte_internacionales,
            'aporte_otras_universidades' => $this->aporte_otras_universidades,
            'aporte_comunidad' => $this->aporte_comunidad,
            'otros_aportes' => $this->otros_aportes,
        ]);
        Notification::make()->title('Paso VIII guardado')->success()->send();
    }

    protected function saveStep9(): void
    {
        if ($this->newAnexo) {
            $this->validate(['newAnexo' => 'file|max:10240']);
            $path = $this->newAnexo->store('anexos', 'public');
            $record = $this->ensureRecord();
            $record->anexos()->create(['documento_url' => $path]);
            $this->newAnexo = null;
        }
        Notification::make()->title('Paso IX guardado')->success()->send();
    }

    // ─── Calc Totals ─────────────────────────────────────────────────────────

    public function calcTotales(): void
    {
        $this->normalizarBeneficiarios();
        $this->hombres = $this->indigenas_hombres + $this->afroamericanos_hombres + $this->mestizos_hombres;
        $this->mujeres = $this->indigenas_mujeres + $this->afroamericanos_mujeres + $this->mestizos_mujeres;
        $this->poblacion_participante = $this->hombres + $this->mujeres;
    }

    private function normalizarBeneficiarios(): void
    {
        foreach (self::CAMPOS_BENEFICIARIOS as $campo) {
            $this->normalizarCampoBeneficiario($campo);
        }
    }

    private function normalizarCampoBeneficiario(string $campo): void
    {
        if (!$this->esCampoBeneficiario($campo)) {
            return;
        }

        $valor = $this->{$campo} ?? 0;
        $this->{$campo} = max(0, (int) (is_numeric($valor) ? $valor : 0));
    }

    // ─── Empleado Modal (Step 2) ──────────────────────────────────────────────

    public function openEmpleadoModal(): void
    {
        $this->empleadoModalSearch = '';
        $this->showEmpleadoModal = true;
    }

    public function closeEmpleadoModal(): void
    {
        $this->showEmpleadoModal = false;
        $this->empleadoModalSearch = '';
    }

    public function selectEmpleadoFromModal(int $empleadoId, string $nombre): void
    {
        foreach ($this->empleado_proyecto as $item) {
            if ((int)($item['empleado_id'] ?? 0) === $empleadoId) {
                Notification::make()->title('Ya agregado')->body('Este empleado ya está en el equipo.')->warning()->send();
                $this->showEmpleadoModal = false;
                return;
            }
        }
        $this->empleado_proyecto[] = ['empleado_id' => $empleadoId, 'rol' => 'Integrante', 'nombre' => $nombre];
        $this->showEmpleadoModal = false;
        $this->empleadoModalSearch = '';
        $this->autoGuardarBorrador();
    }

    // ─── Estudiante Modal (Step 2) ────────────────────────────────────────────

    public function openEstudianteModal(?int $index = null): void
    {
        $this->resetErrorBag();
        $this->cargarOpcionesPracticaAsignatura();
        $this->resetFormularioAsignaturaInline();

        if ($index !== null && isset($this->estudiante_proyecto[$index])) {
            $this->nuevoEstudiante = $this->estudiante_proyecto[$index];
            $this->editEstudianteIndex = $index;
        } else {
            $this->nuevoEstudiante = ['tipo_participacion_estudiante' => '', 'carrera_id' => null, 'asignatura_id' => null, 'periodo_academico_id' => null, 'cantidad_estudiantes_hombres' => 0, 'cantidad_estudiantes_mujeres' => 0, 'total_estudiantes' => 0];
            $this->editEstudianteIndex = null;
        }
        $this->showEstudianteModal = true;
    }

    public function closeEstudianteModal(): void
    {
        $this->showEstudianteModal = false;
        $this->editEstudianteIndex = null;
        $this->resetFormularioAsignaturaInline();
    }

    public function openCrearAsignaturaInline(): void
    {
        $this->resetErrorBag();
        $this->showCrearAsignaturaInline = true;

        if (empty($this->ids($this->carreras))) {
            $this->addError('nuevaAsignaturaCarreraId', 'Seleccione primero una carrera en Información General.');
            return;
        }

        if ($this->nuevaAsignaturaCarreraId === null) {
            $this->nuevaAsignaturaCarreraId = count($this->ids($this->carreras)) === 1
                ? $this->ids($this->carreras)[0]
                : null;
        }
    }

    public function closeCrearAsignaturaInline(): void
    {
        $this->resetFormularioAsignaturaInline();
    }

    public function crearAsignaturaInline(): void
    {
        $carrerasValidas = $this->ids($this->carreras);

        if (empty($carrerasValidas)) {
            $this->addError('nuevaAsignaturaCarreraId', 'Seleccione primero una carrera en Información General.');
            return;
        }

        $this->validate([
            'nuevaAsignaturaCodigo' => 'nullable|string|max:50',
            'nuevaAsignaturaNombre' => 'required|string|max:255',
            'nuevaAsignaturaCarreraId' => 'required|integer|exists:carrera,id',
        ]);

        if (!in_array((int) $this->nuevaAsignaturaCarreraId, $carrerasValidas, true)) {
            $this->addError('nuevaAsignaturaCarreraId', 'La carrera debe ser una de las seleccionadas en Información General.');
            return;
        }

        $asignatura = Asignatura::create([
            'codigo' => $this->stringOrNull($this->nuevaAsignaturaCodigo),
            'nombre' => trim($this->nuevaAsignaturaNombre),
            'carrera_id' => (int) $this->nuevaAsignaturaCarreraId,
        ]);

        $this->cargarOpcionesPracticaAsignatura();
        $this->nuevoEstudiante['asignatura_id'] = $asignatura->id;
        $this->nuevoEstudiante['carrera_id'] = $asignatura->carrera_id;
        $this->resetFormularioAsignaturaInline();

        Notification::make()->title('Asignatura creada')->success()->send();
    }

    private function resetFormularioAsignaturaInline(): void
    {
        $this->showCrearAsignaturaInline = false;
        $this->nuevaAsignaturaCodigo = '';
        $this->nuevaAsignaturaNombre = '';
        $this->nuevaAsignaturaCarreraId = count($this->ids($this->carreras)) === 1
            ? $this->ids($this->carreras)[0]
            : null;
    }

    public function saveEstudiante(): void
    {
        $tipo = $this->normalizeTipoParticipacionEstudiante($this->nuevoEstudiante['tipo_participacion_estudiante'] ?? '');
        if (empty($tipo)) {
            $this->addError('nuevoEstudiante.tipo_participacion_estudiante', 'Seleccione el tipo de participación.');
            return;
        }
        $this->nuevoEstudiante['tipo_participacion_estudiante'] = $tipo;

        if ($this->isTipoParticipacionAsignatura($tipo)) {
            if (empty($this->carreras)) {
                $this->addError('nuevoEstudiante.asignatura_id', 'Seleccione primero una carrera en Información General.');
                return;
            }

            if (empty($this->asignaturasDisponibles)) {
                $this->addError('nuevoEstudiante.asignatura_id', 'No hay asignaturas registradas para la carrera seleccionada.');
                return;
            }

            if (empty($this->nuevoEstudiante['asignatura_id'])) {
                $this->addError('nuevoEstudiante.asignatura_id', 'Seleccione la asignatura.');
                return;
            }

            if (!array_key_exists((int) $this->nuevoEstudiante['asignatura_id'], $this->asignaturasDisponibles)) {
                $this->addError('nuevoEstudiante.asignatura_id', 'Seleccione una asignatura válida para la carrera seleccionada.');
                return;
            }

            $carreraId = $this->carreraIdParaAsignatura($this->nuevoEstudiante['asignatura_id']);
            if (!$carreraId) {
                $this->addError('nuevoEstudiante.asignatura_id', 'La asignatura no corresponde a la carrera seleccionada.');
                return;
            }
            $this->nuevoEstudiante['carrera_id'] = $carreraId;

            if (empty($this->nuevoEstudiante['periodo_academico_id'])) {
                $this->addError('nuevoEstudiante.periodo_academico_id', 'Seleccione el periodo académico.');
                return;
            }

            $periodosValidos = array_map('strval', array_keys($this->periodosAcademicosDisponibles));
            if (!in_array((string) $this->nuevoEstudiante['periodo_academico_id'], $periodosValidos, true)) {
                $this->addError('nuevoEstudiante.periodo_academico_id', 'Seleccione un periodo académico válido.');
                return;
            }
        } else {
            $this->nuevoEstudiante['carrera_id'] = null;
            $this->nuevoEstudiante['asignatura_id'] = null;
            $this->nuevoEstudiante['periodo_academico_id'] = null;
        }

        $h = (int)($this->nuevoEstudiante['cantidad_estudiantes_hombres'] ?? 0);
        $m = (int)($this->nuevoEstudiante['cantidad_estudiantes_mujeres'] ?? 0);
        $this->nuevoEstudiante['total_estudiantes'] = $h + $m;

        if ($this->editEstudianteIndex !== null) {
            $this->estudiante_proyecto[$this->editEstudianteIndex] = $this->nuevoEstudiante;
        } else {
            $this->estudiante_proyecto[] = $this->nuevoEstudiante;
        }

        $this->showEstudianteModal = false;
        $this->editEstudianteIndex = null;
        $this->nuevoEstudiante = ['tipo_participacion_estudiante' => '', 'carrera_id' => null, 'asignatura_id' => null, 'periodo_academico_id' => null, 'cantidad_estudiantes_hombres' => 0, 'cantidad_estudiantes_mujeres' => 0, 'total_estudiantes' => 0];
        $this->autoGuardarBorrador();
    }

    public function removeEstudiante(int $i): void
    {
        array_splice($this->estudiante_proyecto, $i, 1);
        $this->autoGuardarBorrador();
    }

    // ─── Internacional Modal (Step 2) ─────────────────────────────────────────

    public function openInternacionalModal(): void
    {
        $this->resetErrorBag();
        $this->integranteInternacionalSeleccionadoId = null;
        $this->showInternacionalModal = true;
    }

    public function closeInternacionalModal(): void
    {
        $this->showInternacionalModal = false;
        $this->integranteInternacionalSeleccionadoId = null;
        $this->resetNuevoIntegranteInternacional();
    }

    public function agregarIntegranteInternacionalExistente(): void
    {
        $this->resetErrorBag('integranteInternacionalSeleccionadoId');

        if (empty($this->integranteInternacionalSeleccionadoId)) {
            $this->addError('integranteInternacionalSeleccionadoId', 'Seleccione un integrante internacional.');
            return;
        }

        $integrante = IntegranteInternacional::find($this->integranteInternacionalSeleccionadoId);

        if (!$integrante) {
            $this->addError('integranteInternacionalSeleccionadoId', 'El integrante seleccionado no existe.');
            return;
        }

        $yaExiste = collect($this->integrante_internacional_proyecto)
            ->contains(fn($item) => (int)($item['integrante_internacional_id'] ?? 0) === (int)$integrante->id);

        $this->integranteInternacionalSeleccionadoId = null;

        if ($yaExiste) {
            Notification::make()->title('Integrante internacional ya agregado')->info()->send();
            return;
        }

        $this->selectIntegranteInternacional((int)$integrante->id);
        $this->showInternacionalModal = false;
        $this->resetNuevoIntegranteInternacional();
        $this->autoGuardarBorrador();
    }

    public function saveNuevoIntegranteInternacional(): void
    {
        $data = $this->validate([
            'nuevoIntegranteInternacional.nombre_completo' => 'required|string|max:255',
            'nuevoIntegranteInternacional.documento_identidad' => 'required|string|max:255',
            'nuevoIntegranteInternacional.sexo' => 'nullable|in:masculino,femenino,otro',
            'nuevoIntegranteInternacional.email' => 'required|email|max:255',
            'nuevoIntegranteInternacional.pais' => ['required', 'string', 'max:255', Rule::exists('pais', 'nombre')->whereNull('deleted_at')],
            'nuevoIntegranteInternacional.institucion' => 'required|string|max:255',
        ])['nuevoIntegranteInternacional'];

        $data = [
            'nombre_completo' => trim($data['nombre_completo']),
            'documento_identidad' => trim($data['documento_identidad']),
            'sexo' => $data['sexo'] ?: null,
            'email' => trim($data['email']),
            'pais' => trim($data['pais']),
            'institucion' => trim($data['institucion']),
        ];

        $integrante = IntegranteInternacional::where('email', $data['email'])
            ->orWhere('documento_identidad', $data['documento_identidad'])
            ->first();

        if (!$integrante) {
            $integrante = IntegranteInternacional::create($data);
            Notification::make()->title('Integrante internacional creado')->success()->send();
        } else {
            Notification::make()->title('Integrante internacional existente seleccionado')->success()->send();
        }

        $this->selectIntegranteInternacional((int) $integrante->id, $data);
        $this->showInternacionalModal = false;
        $this->resetNuevoIntegranteInternacional();
        $this->autoGuardarBorrador();
    }

    protected function resetNuevoIntegranteInternacional(): void
    {
        $this->nuevoIntegranteInternacional = ['nombre_completo' => '', 'documento_identidad' => '', 'sexo' => '', 'email' => '', 'pais' => '', 'institucion' => ''];
    }

    protected function selectIntegranteInternacional(int $integranteId, array $data = []): void
    {
        foreach ($this->integrante_internacional_proyecto as $i => $item) {
            if ((int)($item['integrante_internacional_id'] ?? 0) === $integranteId) return;
        }
        $integrante = IntegranteInternacional::find($integranteId);
        $this->integrante_internacional_proyecto[] = [
            'integrante_internacional_id' => $integranteId,
            'nombre' => $integrante?->nombre_completo ?? ($data['nombre_completo'] ?? ''),
            'pais' => $integrante?->pais ?? ($data['pais'] ?? ''),
            'institucion' => $integrante?->institucion ?? ($data['institucion'] ?? ''),
        ];
    }

    public function removeInternacional(int $i): void
    {
        array_splice($this->integrante_internacional_proyecto, $i, 1);
        $this->autoGuardarBorrador();
    }

    // ─── Contraparte Modal (Step 3) ───────────────────────────────────────────

    public function openContraparteModal(?int $index = null): void
    {
        $this->resetErrorBag();
        if ($index !== null && isset($this->entidad_contraparte[$index])) {
            $this->nuevaContraparte = $this->entidad_contraparte[$index];
            $this->editContraparteIndex = $index;
        } else {
            $this->nuevaContraparte = ['nombre' => '', 'tipo_entidad' => '', 'nombre_contacto' => '', 'cargo_contacto' => '', 'telefono' => '', 'correo' => '', 'descripcion_acuerdos' => '', 'instrumento_formalizacion' => []];
            $this->editContraparteIndex = null;
        }
        $this->showContraparteModal = true;
    }

    public function closeContraparteModal(): void
    {
        $this->showContraparteModal = false;
        $this->editContraparteIndex = null;
    }

    public function saveContraparte(): void
    {
        $this->resetErrorBag();
        $this->normalizarInstrumentosContraparteModal();

        $this->validate([
            'nuevaContraparte.nombre' => 'required|string|max:255',
            'nuevaContraparte.tipo_entidad' => 'required|string',
            'nuevaContraparte.nombre_contacto' => 'nullable|string|max:255',
            'nuevaContraparte.cargo_contacto' => 'nullable|string|max:255',
            'nuevaContraparte.telefono' => 'nullable|string|max:255',
            'nuevaContraparte.correo' => 'nullable|email|max:255',
            'nuevaContraparte.descripcion_acuerdos' => 'nullable|string',
            'nuevaContraparte.instrumento_formalizacion.*.tipo_documento' => 'nullable|in:' . implode(',', $this->instrumentoTipos),
            'nuevaContraparte.instrumento_formalizacion.*.documento_file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        if (!$this->validarInstrumentosContraparteModal()) {
            return;
        }

        if ($this->editContraparteIndex !== null) {
            $this->entidad_contraparte[$this->editContraparteIndex] = $this->nuevaContraparte;
        } else {
            $this->entidad_contraparte[] = $this->nuevaContraparte;
        }

        $this->autoGuardarBorrador();
        $this->showContraparteModal = false;
        $this->editContraparteIndex = null;
    }

    public function removeContraparte(int $i): void
    {
        array_splice($this->entidad_contraparte, $i, 1);
        $this->autoGuardarBorrador();
    }

    public function addInstrumentoToModal(): void
    {
        $this->nuevaContraparte['instrumento_formalizacion'][] = ['id' => null, 'tipo_documento' => '', 'documento_url' => null, 'nombre_archivo' => null, 'documento_file' => null];
    }

    public function removeInstrumentoFromModal(int $ii): void
    {
        array_splice($this->nuevaContraparte['instrumento_formalizacion'], $ii, 1);
    }

    // Keep for backward compat
    public function addInstrumento(int $ci): void
    {
        $this->entidad_contraparte[$ci]['instrumento_formalizacion'][] = ['id' => null, 'tipo_documento' => '', 'documento_url' => null, 'nombre_archivo' => null, 'documento_file' => null];
    }

    public function removeInstrumento(int $ci, int $ii): void
    {
        array_splice($this->entidad_contraparte[$ci]['instrumento_formalizacion'], $ii, 1);
    }

    private function normalizarInstrumentosContraparteModal(): void
    {
        foreach ($this->nuevaContraparte['instrumento_formalizacion'] ?? [] as $ii => $inst) {
            $tipo = $this->normalizeInstrumentoTipo($inst['tipo_documento'] ?? '');
            $this->nuevaContraparte['instrumento_formalizacion'][$ii]['tipo_documento'] = $tipo;
            $this->nuevaContraparte['instrumento_formalizacion'][$ii]['documento_url'] = $this->normalizarRutaDocumentoInstrumento($inst['documento_url'] ?? null);
            $this->nuevaContraparte['instrumento_formalizacion'][$ii]['nombre_archivo'] = $inst['nombre_archivo'] ?? null;
            $this->nuevaContraparte['instrumento_formalizacion'][$ii]['documento_file'] = $inst['documento_file'] ?? null;
            $this->nuevaContraparte['instrumento_formalizacion'][$ii]['id'] = $inst['id'] ?? null;
        }
    }

    private function validarInstrumentosContraparteModal(): bool
    {
        $valido = true;

        foreach ($this->nuevaContraparte['instrumento_formalizacion'] ?? [] as $ii => $inst) {
            $tipo = $inst['tipo_documento'] ?? '';
            $hasStoredDocument = !empty($inst['documento_url']);
            $hasUploadedDocument = $this->instrumentoTieneArchivoNuevo($inst);

            if ($tipo === '') {
                $this->addError("nuevaContraparte.instrumento_formalizacion.$ii.tipo_documento", 'Seleccione el tipo de instrumento.');
                $valido = false;
            }

            if (!$hasStoredDocument && !$hasUploadedDocument) {
                $this->addError("nuevaContraparte.instrumento_formalizacion.$ii.documento_file", 'Seleccione el documento del instrumento.');
                $valido = false;
            }
        }

        return $valido;
    }

    private function instrumentoTieneArchivoNuevo(array $instrumento): bool
    {
        return ($instrumento['documento_file'] ?? null) instanceof TemporaryUploadedFile;
    }

    private function guardarDocumentoInstrumento(TemporaryUploadedFile $documento): string
    {
        return $documento->store('proyectos/contrapartes/instrumentos', 'public');
    }

    private function normalizarRutaDocumentoInstrumento(?string $ruta): ?string
    {
        if (empty($ruta)) {
            return null;
        }

        if (filter_var($ruta, FILTER_VALIDATE_URL)) {
            return $ruta;
        }

        $ruta = ltrim($ruta, '/');

        if (str_starts_with($ruta, 'storage/')) {
            $ruta = substr($ruta, strlen('storage/'));
        }

        if (str_starts_with($ruta, 'public/')) {
            $ruta = substr($ruta, strlen('public/'));
        }

        if (str_starts_with($ruta, 'app/public/')) {
            $ruta = substr($ruta, strlen('app/public/'));
        }

        return $ruta;
    }

    public function instrumentoDocumentoUrl(?int $id, ?string $ruta): ?string
    {
        $ruta = $this->normalizarRutaDocumentoInstrumento($ruta);

        if (empty($id) || empty($ruta)) {
            return null;
        }

        return route('instrumentos-formalizacion.documento', ['instrumento' => $id], false);
    }

    public function instrumentoDocumentoNombre(?string $ruta, ?string $nombreArchivo = null): string
    {
        if (!empty($nombreArchivo)) {
            return $nombreArchivo;
        }

        $ruta = $this->normalizarRutaDocumentoInstrumento($ruta);

        return $ruta ? basename($ruta) : 'Documento cargado';
    }

    // ─── Actividad Modal (Step 4) ─────────────────────────────────────────────

    public function openActividadModal(?int $index = null): void
    {
        $this->resetErrorBag();
        if ($index !== null && isset($this->actividades[$index])) {
            $this->nuevaActividad = $this->actividades[$index];
            $this->editActividadIndex = $index;
        } else {
            $this->nuevaActividad = ['id' => null, 'descripcion' => '', 'empleados' => [], 'fecha_inicio' => '', 'fecha_finalizacion' => '', 'horas' => ''];
            $this->editActividadIndex = null;
        }
        $this->showActividadModal = true;
    }

    public function closeActividadModal(): void
    {
        $this->showActividadModal = false;
        $this->editActividadIndex = null;
        $this->nuevaActividad = ['id' => null, 'descripcion' => '', 'empleados' => [], 'fecha_inicio' => '', 'fecha_finalizacion' => '', 'horas' => ''];
    }

    public function saveActividad(): void
    {
        $this->nuevaActividad['descripcion'] = trim((string) ($this->nuevaActividad['descripcion'] ?? ''));
        $this->nuevaActividad['horas'] = max(0, (int) ($this->nuevaActividad['horas'] ?? 0));

        $this->validate([
            'nuevaActividad.descripcion' => 'required|string',
            'nuevaActividad.fecha_inicio' => 'required|date',
            'nuevaActividad.fecha_finalizacion' => 'required|date|after_or_equal:nuevaActividad.fecha_inicio',
            'nuevaActividad.horas' => 'nullable|integer|min:0',
        ], [], [
            'nuevaActividad.descripcion' => 'descripción de la actividad',
            'nuevaActividad.fecha_inicio' => 'fecha de inicio de la actividad',
            'nuevaActividad.fecha_finalizacion' => 'fecha de finalización de la actividad',
            'nuevaActividad.horas' => 'horas de la actividad',
        ]);

        $record = $this->ensureRecord();
        $validEmpleados = $this->responsableIdsDisponibles($record);
        $ids = collect($this->nuevaActividad['empleados'] ?? [])
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->diff($validEmpleados)->isNotEmpty()) {
            $this->addError('nuevaActividad.empleados', 'Seleccione únicamente responsables del equipo ejecutor.');
            return;
        }

        DB::transaction(function () use ($record, $ids) {
            $actividadId = $this->editActividadIndex !== null
                ? $this->nullableInt($this->actividades[$this->editActividadIndex]['id'] ?? null)
                : null;
            $actividadId = $actividadId ?: $this->nullableInt($this->nuevaActividad['id'] ?? null);
            $actividad = $actividadId
                ? $record->actividades()->whereKey($actividadId)->first()
                : null;

            $data = [
                'descripcion' => trim((string) $this->nuevaActividad['descripcion']),
                'fecha_inicio' => $this->dateOrNull($this->nuevaActividad['fecha_inicio'] ?? null),
                'fecha_finalizacion' => $this->dateOrNull($this->nuevaActividad['fecha_finalizacion'] ?? null),
                'horas' => (int) ($this->nuevaActividad['horas'] ?? 0),
            ];

            if ($actividad) {
                $actividad->update($data);
            } else {
                $actividad = $record->actividades()->create($data);
            }

            $actividad->empleados()->sync($ids->all());

            $this->nuevaActividad['id'] = $actividad->id;
            $this->nuevaActividad['empleados'] = $ids->map(fn($id) => (string) $id)->all();
            $this->nuevaActividad['fecha_inicio'] = $this->dateForInput($actividad->fecha_inicio);
            $this->nuevaActividad['fecha_finalizacion'] = $this->dateForInput($actividad->fecha_finalizacion);
            $this->nuevaActividad['horas'] = $actividad->horas ?? '';
        });

        if ($this->editActividadIndex !== null) {
            $this->actividades[$this->editActividadIndex] = $this->nuevaActividad;
        } else {
            $this->actividades[] = $this->nuevaActividad;
        }
        $this->showActividadModal = false;
        $this->editActividadIndex = null;
        $this->nuevaActividad = ['id' => null, 'descripcion' => '', 'empleados' => [], 'fecha_inicio' => '', 'fecha_finalizacion' => '', 'horas' => ''];
    }

    public function removeActividad(int $i): void
    {
        $actividadId = $this->nullableInt($this->actividades[$i]['id'] ?? null);

        if ($actividadId) {
            $record = $this->ensureRecord();

            DB::transaction(function () use ($record, $actividadId) {
                $actividad = $record->actividades()->whereKey($actividadId)->first();

                if ($actividad) {
                    $actividad->empleados()->detach();
                    $actividad->delete();
                }
            });
        }

        array_splice($this->actividades, $i, 1);
    }

    // ─── Marco Lógico (Step 7) ────────────────────────────────────────────────

    public function selectObjetivo(int $index): void
    {
        if (!array_key_exists($index, $this->objetivosEspecificos)) {
            $this->normalizarObjetivoSeleccionado();
            return;
        }

        $this->selectedObjetivoIndex = $index;
    }

    // ─── Empleado Helpers (Step 2) ────────────────────────────────────────────

    public function addEmpleado(): void
    {
        $this->openEmpleadoModal();
    }

    public function removeEmpleado(int $i): void
    {
        array_splice($this->empleado_proyecto, $i, 1);
        $this->autoGuardarBorrador();
    }

    // ─── Objetivo / Resultado Helpers (Step 7) ────────────────────────────────

    public function addObjetivo(): void
    {
        $this->objetivosEspecificos[] = $this->nuevoObjetivoEspecifico();
        $this->selectedObjetivoIndex = count($this->objetivosEspecificos) - 1;
    }

    public function removeObjetivo(int $i): void
    {
        if (!array_key_exists($i, $this->objetivosEspecificos)) {
            $this->normalizarObjetivoSeleccionado();
            return;
        }

        $objetivoId = $this->nullableInt($this->objetivosEspecificos[$i]['id'] ?? null);
        $selectedBeforeRemoval = $this->selectedObjetivoIndex;

        if ($objetivoId && $this->recordId) {
            DB::transaction(function () use ($objetivoId) {
                $record = $this->ensureRecord();
                $objetivo = $record->objetivosEspecificos()->whereKey($objetivoId)->first();

                if ($objetivo) {
                    $objetivo->resultados()->delete();
                    $objetivo->delete();
                }
            });
        }

        array_splice($this->objetivosEspecificos, $i, 1);

        if (empty($this->objetivosEspecificos)) {
            $this->objetivosEspecificos[] = $this->nuevoObjetivoEspecifico();
        }

        if ($selectedBeforeRemoval === $i) {
            $this->selectedObjetivoIndex = min($i, count($this->objetivosEspecificos) - 1);
        } elseif ($selectedBeforeRemoval > $i) {
            $this->selectedObjetivoIndex = $selectedBeforeRemoval - 1;
        }

        $this->normalizarObjetivoSeleccionado();
        $this->autoGuardarBorrador();
    }

    public function addResultado(int $oi): void
    {
        if (!array_key_exists($oi, $this->objetivosEspecificos)) {
            $this->normalizarObjetivoSeleccionado();
            return;
        }

        $this->objetivosEspecificos[$oi]['resultados'][] = $this->nuevoResultadoEsperado();
    }

    public function removeResultado(int $oi, int $ri): void
    {
        if (!array_key_exists($oi, $this->objetivosEspecificos)
            || !array_key_exists($ri, $this->objetivosEspecificos[$oi]['resultados'] ?? [])) {
            $this->normalizarObjetivoSeleccionado();
            return;
        }

        $objetivoId = $this->nullableInt($this->objetivosEspecificos[$oi]['id'] ?? null);
        $resultadoId = $this->nullableInt($this->objetivosEspecificos[$oi]['resultados'][$ri]['id'] ?? null);

        if ($objetivoId && $resultadoId && $this->recordId) {
            DB::transaction(function () use ($objetivoId, $resultadoId) {
                $record = $this->ensureRecord();
                $objetivo = $record->objetivosEspecificos()->whereKey($objetivoId)->first();

                if ($objetivo) {
                    $objetivo->resultados()->whereKey($resultadoId)->delete();
                }
            });
        }

        array_splice($this->objetivosEspecificos[$oi]['resultados'], $ri, 1);
        $this->autoGuardarBorrador();
    }

    private function nuevoObjetivoEspecifico(): array
    {
        return [
            'id' => null,
            'wire_key' => (string) Str::uuid(),
            'descripcion' => '',
            'resultados' => [$this->nuevoResultadoEsperado()],
        ];
    }

    private function nuevoResultadoEsperado(): array
    {
        return [
            'id' => null,
            'wire_key' => (string) Str::uuid(),
            'nombre_resultado' => '',
            'nombre_indicador' => '',
            'nombre_medio_verificacion' => '',
            'plazo' => 'corto_plazo',
        ];
    }

    private function normalizarObjetivoSeleccionado(): void
    {
        if (empty($this->objetivosEspecificos)) {
            $this->selectedObjetivoIndex = 0;
            return;
        }

        $this->selectedObjetivoIndex = max(0, min(
            $this->selectedObjetivoIndex,
            count($this->objetivosEspecificos) - 1,
        ));
    }

    public function updateAporteTotal(int $i): void
    {
        $this->aporte_institucional[$i]['costo_total'] = (float)($this->aporte_institucional[$i]['cantidad'] ?? 0) * (float)($this->aporte_institucional[$i]['costo_unitario'] ?? 0);
        $this->recalculateAporteInstitucional();
        $this->autoGuardarBorrador();
    }

    // ─── Anexo Methods (Step 9) ───────────────────────────────────────────────

    public function uploadAnexo(): void
    {
        $this->validate(['newAnexo' => 'required|file|max:10240']);
        $path = $this->newAnexo->store('anexos', 'public');
        $record = $this->ensureRecord();
        $record->anexos()->create(['documento_url' => $path]);
        $this->newAnexo = null;
        $this->anexosCount = $record->anexos()->count();
        Notification::make()->title('Anexo subido')->success()->send();
    }

    public function deleteAnexo(int $id): void
    {
        if ($this->recordId) {
            $record = Proyecto::findOrFail($this->recordId);
            $record->anexos()->where('id', $id)->delete();
            $this->anexosCount = $record->anexos()->count();
        }
    }

    // ─── Student participacion updater ───────────────────────────────────────

    public function updatedEstudianteProyecto($value, ?string $key = null): void
    {
        if ($key === null || !str_ends_with($key, '.tipo_participacion_estudiante')) return;
        $index = (int) explode('.', $key, 2)[0];
        $tipo = $this->normalizeTipoParticipacionEstudiante($value) ?: (string) $value;
        $this->estudiante_proyecto[$index]['tipo_participacion_estudiante'] = $tipo;
        if (!$this->isTipoParticipacionAsignatura($tipo)) {
            $this->estudiante_proyecto[$index]['carrera_id'] = null;
            $this->estudiante_proyecto[$index]['asignatura_id'] = null;
            $this->estudiante_proyecto[$index]['periodo_academico_id'] = null;
        }
    }

    public function updateEstudianteTotal(int $i): void
    {
        $h = (int)($this->estudiante_proyecto[$i]['cantidad_estudiantes_hombres'] ?? 0);
        $m = (int)($this->estudiante_proyecto[$i]['cantidad_estudiantes_mujeres'] ?? 0);
        $this->estudiante_proyecto[$i]['total_estudiantes'] = $h + $m;
    }

    // ─── Submit (Step 10) ─────────────────────────────────────────────────────

    public function create(): void
    {
        $this->autoGuardarBorrador();

        if (!$this->recordId) {
            Notification::make()->title('Error')->body('Complete al menos el primer paso.')->danger()->send();
            return;
        }

        if (!$this->validarFormularioAntesDeEnviar()) {
            return;
        }

        $record = Proyecto::findOrFail($this->recordId);
        $empleado = auth()->user()->empleado;
        try {
            if ($this->usarFirmantesPorEtapaParaEnvio) {
                $this->debeEnviarPorFlujoDeEtapas();
                $this->guardarFirmasPorEtapaDesdeSeleccionDinamica($record);
            } else {
                $this->saveFirmas($record);
                $record->sincronizarFirmasDelFlujo();
                $cargoFirma = CargoFirma::join('tipo_cargo_firma', 'tipo_cargo_firma.id', '=', 'cargo_firma.tipo_cargo_firma_id')
                    ->where('tipo_cargo_firma.nombre', 'Coordinador Proyecto')
                    ->where('cargo_firma.descripcion', 'Proyecto')
                    ->first();

                if ($cargoFirma) {
                    $record->guardarFirmaDeCargo($cargoFirma->id, $empleado, [
                        'estado_revision' => 'Pendiente',
                        'firma_id' => null,
                        'sello_id' => null,
                        'fecha_firma' => null,
                    ]);

                    $record->agregarEstado(empleado: $empleado, tipoEstadoId: $cargoFirma->tipo_estado_id, comentario: 'Proyecto enviado para firma');
                }
            }
        } catch (\Exception $e) {
            Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
            return;
        }
        try { Mail::to(auth()->user()->email)->send(new ProyectoCreado($record, auth()->user())); } catch (\Exception $e) { \Log::warning($e->getMessage()); }
        Notification::make()->title('Proyecto enviado a firmar')->success()->send();
        redirect()->route('proyectosDocente');
    }

    private function validarFormularioAntesDeEnviar(): bool
    {
        try {
            $this->trimCamposDescripcion();
            $this->validate($this->rulesDescripcion());
            $this->validarMarcoLogicoCompleto();
        } catch (ValidationException $e) {
            $errores = $e->validator->errors();
            $primerCampo = collect($errores->keys())->first();
            $this->currentStep = str_starts_with((string) $primerCampo, 'objetivo')
                ? 7
                : 5;

            throw $e;
        }

        return true;
    }

    protected function saveFirmas(Proyecto $record): void
    {
        $map = ['Jefe Departamento' => $this->jefe_empleado_id, 'Director centro' => $this->decano_empleado_id, 'Enlace Vinculacion' => $this->enlace_empleado_id];
        foreach ($map as $nombre => $empId) {
            if (!$empId) continue;
            $cargo = CargoFirma::join('tipo_cargo_firma', 'tipo_cargo_firma.id', '=', 'cargo_firma.tipo_cargo_firma_id')
                ->where('tipo_cargo_firma.nombre', $nombre)->select('cargo_firma.*')->first();
            if ($cargo) {
                $record->guardarFirmaDeCargo($cargo->id, \App\Models\Personal\Empleado::findOrFail((int) $empId), [
                    'estado_revision' => 'Pendiente',
                    'firma_id' => null,
                    'sello_id' => null,
                    'fecha_firma' => null,
                ]);
            }
        }
    }

    protected function debeEnviarPorFlujoDeEtapas(): bool
    {
        $proyecto = $this->getRecord();
        $fallar = fn () => throw new RuntimeException('Los firmantes por etapa fueron activados, pero ya no estan listos para el envio.');

        if (! $this->usarFirmantesPorEtapaParaEnvio
            || ! $proyecto
            || empty($this->firmantesPorEtapa)
            || ! $this->firmantesPorEtapaListos
            || $this->firmantesPorEtapaBloqueado
            || ! empty($this->erroresFirmantesPorEtapa)
        ) {
            $fallar();
        }

        if (collect($this->unidadesSinCandidatosPorEtapa)->contains(fn (array $unidades): bool => ! empty($unidades))) {
            $fallar();
        }

        try {
            $this->asignacionesFirmantesPorEtapaNormalizadas($proyecto);
        } catch (RuntimeException $exception) {
            $fallar();
        }

        return true;
    }

    protected function guardarFirmasPorEtapaDesdeSeleccionDinamica(Proyecto $proyecto): Collection
    {
        $this->validarPrecondicionesFirmantesPorEtapa($proyecto);
        $empleadosPorEtapa = $this->asignacionesFirmantesPorEtapaNormalizadas($proyecto);

        return DB::transaction(function () use ($proyecto, $empleadosPorEtapa): Collection {
            $proyectoBloqueado = Proyecto::query()
                ->whereKey($proyecto->id)
                ->lockForUpdate()
                ->first();

            if (! $proyectoBloqueado) {
                throw new RuntimeException('Los firmantes por etapa no estan listos para enviar el proyecto a revision.');
            }

            $flujo = $proyectoBloqueado->resolveFlujoAprobacion();

            if (! $flujo) {
                throw new RuntimeException('Los firmantes por etapa no estan listos para enviar el proyecto a revision.');
            }

            $this->validarPrecondicionesFirmantesPorEtapa($proyectoBloqueado);
            $empleadosPorEtapa = $this->asignacionesFirmantesPorEtapaNormalizadas($proyectoBloqueado);
            $this->validarSinFirmasPreviasParaEnvioPorEtapa($proyectoBloqueado, (int) $flujo->id);

            $firmas = $proyectoBloqueado->sincronizarFirmasDeEtapasDelFlujo(
                $empleadosPorEtapa,
                Proyecto::FLUJO_INSCRIPCION,
                null,
                1
            );

            $primeraFirma = $proyectoBloqueado->firmaActualDeEtapasDelFlujo((int) $flujo->id, 1);

            if (! $primeraFirma || (int) $primeraFirma->id !== (int) $firmas->first()?->id) {
                throw new RuntimeException('No se pudo preparar el envio por etapas de forma segura.');
            }

            $this->registrarEstadoInicialDeFirmasPorEtapa($proyectoBloqueado, $primeraFirma);
            $this->validarResultadoFirmasPorEtapa(
                $proyectoBloqueado->fresh(),
                $firmas->map(fn (FirmaProyecto $firma): FirmaProyecto => $firma->fresh())->values()
            );

            return $firmas->map(fn (FirmaProyecto $firma): FirmaProyecto => $firma->fresh())->values();
        });
    }

    protected function validarPrecondicionesFirmantesPorEtapa(Proyecto $proyecto): void
    {
        $fallar = fn () => throw new RuntimeException('Los firmantes por etapa no estan listos para enviar el proyecto a revision.');

        if (! $proyecto->exists || ! $proyecto->resolveFlujoAprobacion()) {
            $fallar();
        }

        if (empty($this->firmantesPorEtapa)
            || ! $this->firmantesPorEtapaListos
            || $this->firmantesPorEtapaBloqueado
            || ! empty($this->erroresFirmantesPorEtapa)
        ) {
            $fallar();
        }

        if (collect($this->unidadesSinCandidatosPorEtapa)->contains(fn (array $unidades): bool => ! empty($unidades))) {
            $fallar();
        }

        $hayPorCadaUnidad = collect($this->firmantesPorEtapa)
            ->contains(fn (array $firmante): bool => ($firmante['multiplicidad_revision'] ?? null) === FlujoAprobacionEtapa::MULTIPLICIDAD_POR_CADA_UNIDAD);

        if ($hayPorCadaUnidad) {
            $fallar();
        }

        $faltaEmpleado = collect($this->firmantesPorEtapa)
            ->contains(fn (array $firmante): bool => ($firmante['multiplicidad_revision'] ?? null) === FlujoAprobacionEtapa::MULTIPLICIDAD_UNICO
                && empty($firmante['empleado_id']));

        if ($faltaEmpleado) {
            $fallar();
        }

        try {
            $this->asignacionesFirmantesPorEtapaNormalizadas($proyecto);
        } catch (RuntimeException $exception) {
            $fallar();
        }
    }

    protected function validarSinFirmasPreviasParaEnvioPorEtapa(Proyecto $proyecto, int $flujoAprobacionId): void
    {
        $existenFirmasPorEtapa = $proyecto->firma_proyecto()
            ->where('flujo_aprobacion_id', $flujoAprobacionId)
            ->where('revision_ciclo', 1)
            ->whereNotNull('flujo_aprobacion_etapa_id')
            ->whereNull('deleted_at')
            ->where('estado_revision', '!=', 'Anulado')
            ->exists();

        if ($existenFirmasPorEtapa) {
            throw new RuntimeException('Ya existen firmas por etapa para este proyecto.');
        }

        $existenFirmasLegacy = $proyecto->firma_proyecto()
            ->whereNull('flujo_aprobacion_etapa_id')
            ->whereNull('deleted_at')
            ->where('estado_revision', 'Pendiente')
            ->exists();

        if ($existenFirmasLegacy) {
            throw new RuntimeException('Ya existen firmas legacy para este envio y no se puede iniciar el flujo por etapa.');
        }
    }

    protected function registrarEstadoInicialDeFirmasPorEtapa(Proyecto $proyecto, FirmaProyecto $primeraFirma): void
    {
        $tipoEstadoId = $primeraFirma->cargo_firma()->value('tipo_estado_id');
        $empleado = auth()->user()?->empleado;

        if (! $tipoEstadoId || ! $empleado) {
            throw new RuntimeException('No se pudo preparar el envio por etapas de forma segura.');
        }

        $proyecto->agregarEstado(
            empleado: $empleado,
            tipoEstadoId: (int) $tipoEstadoId,
            comentario: 'Proyecto enviado a revision por flujo de etapas.'
        );
    }

    protected function validarResultadoFirmasPorEtapa(Proyecto $proyecto, Collection $firmas): void
    {
        $flujo = $proyecto->resolveFlujoAprobacion();
        $etapas = $this->etapasActivasParaEnvioPorFlujo($proyecto)
            ->filter(fn (FlujoAprobacionEtapa $etapa): bool => $etapa->multiplicidad_revision === FlujoAprobacionEtapa::MULTIPLICIDAD_UNICO)
            ->values();

        if (! $flujo || $firmas->count() !== $etapas->count()) {
            throw new RuntimeException('No se pudo preparar el envio por etapas de forma segura.');
        }

        $firmas = $firmas->map(fn (FirmaProyecto $firma): FirmaProyecto => $firma->fresh())->values();

        $idsEtapa = $firmas->pluck('flujo_aprobacion_etapa_id')->filter()->map(fn ($id): int => (int) $id);
        $primeraFirma = $proyecto->firmaActualDeEtapasDelFlujo((int) $flujo->id, 1);
        $tipoEstadoPrimera = $primeraFirma?->cargo_firma()->value('tipo_estado_id');
        $estadoActualId = $proyecto->fresh()->estado?->tipo_estado_id;

        if ($firmas->contains(fn (FirmaProyecto $firma): bool => $firma->estado_revision !== 'Pendiente')
            || $firmas->contains(fn (FirmaProyecto $firma): bool => (int) $firma->revision_ciclo !== 1)
            || $idsEtapa->count() !== $idsEtapa->unique()->count()
            || ! $primeraFirma
            || ! $tipoEstadoPrimera
            || (int) $estadoActualId !== (int) $tipoEstadoPrimera
            || $proyecto->firmasDeEtapasCompletadas((int) $flujo->id, 1)
        ) {
            throw new RuntimeException('No se pudo preparar el envio por etapas de forma segura.');
        }

        foreach ($firmas as $firma) {
            $esPrimera = (int) $firma->id === (int) $primeraFirma->id;

            if ($proyecto->firmaEsActualEnFlujoPorEtapa($firma) !== $esPrimera) {
                throw new RuntimeException('No se pudo preparar el envio por etapas de forma segura.');
            }
        }
    }

    public function prepararFirmantesPorEtapaParaVista(): void
    {
        $proyecto = $this->getRecord();

        if (! $proyecto) {
            $this->mostrarFirmantesPorEtapa = true;
            $this->firmantesPorEtapaListos = false;
            $this->firmantesPorEtapaBloqueado = true;
            $this->mensajeFirmantesPorEtapaVista = null;
            $this->mensajeBloqueoFirmantesPorEtapa = 'Debe guardar el borrador antes de preparar firmantes por etapa.';

            return;
        }

        $this->mensajeFirmantesPorEtapaVista = null;
        $this->prepararEstadoFirmantesPorEtapa($proyecto);
        $this->mostrarFirmantesPorEtapa = true;
    }

    public function cerrarFirmantesPorEtapaParaVista(): void
    {
        $this->mostrarFirmantesPorEtapa = false;
        $this->mensajeFirmantesPorEtapaVista = null;
        $this->limpiarEstadoFirmantesPorEtapa();
    }

    public function seleccionarFirmantePorEtapaParaVista(int $etapaId, int $empleadoId): void
    {
        $proyecto = $this->getRecord();

        if (! $proyecto) {
            $this->firmantesPorEtapaListos = false;
            $this->firmantesPorEtapaBloqueado = true;
            $this->mensajeFirmantesPorEtapaVista = null;
            $this->mensajeBloqueoFirmantesPorEtapa = 'Debe guardar el borrador antes de seleccionar firmantes por etapa.';

            return;
        }

        if (! array_key_exists($etapaId, $this->firmantesPorEtapa)) {
            $this->firmantesPorEtapaListos = false;
            $this->mensajesFirmantesPorEtapa[$etapaId] = 'La etapa indicada no esta preparada para seleccionar firmante.';

            return;
        }

        if ($empleadoId <= 0) {
            $this->firmantesPorEtapa[$etapaId]['empleado_id'] = null;
            $this->firmantesPorEtapa[$etapaId]['mensaje'] = null;
            unset($this->mensajesFirmantesPorEtapa[$etapaId]);
            $this->mensajeFirmantesPorEtapaVista = null;
            $this->recalcularEstadoFirmantesPorEtapa();

            return;
        }

        try {
            $this->seleccionarFirmantePorEtapa($proyecto, $etapaId, $empleadoId);
            $this->mensajeFirmantesPorEtapaVista = null;
        } catch (RuntimeException $exception) {
            $this->mensajeFirmantesPorEtapaVista = null;
        }
    }

    public function validarFirmantesPorEtapaParaVista(): void
    {
        $proyecto = $this->getRecord();

        if (! $proyecto) {
            $this->firmantesPorEtapaListos = false;
            $this->firmantesPorEtapaBloqueado = true;
            $this->mensajeFirmantesPorEtapaVista = null;
            $this->mensajeBloqueoFirmantesPorEtapa = 'Debe guardar el borrador antes de validar firmantes por etapa.';

            return;
        }

        try {
            $this->asignacionesFirmantesPorEtapaNormalizadas($proyecto);
            $this->firmantesPorEtapaListos = true;
            $this->firmantesPorEtapaBloqueado = false;
            $this->mensajeBloqueoFirmantesPorEtapa = null;
            $this->mensajeFirmantesPorEtapaVista = 'Firmantes por etapa validados correctamente. Se activaran para envio en una fase posterior.';
        } catch (RuntimeException $exception) {
            $this->firmantesPorEtapaListos = false;
            $this->mensajeFirmantesPorEtapaVista = null;
            $this->mensajeBloqueoFirmantesPorEtapa = $exception->getMessage();
        }
    }

    public function activarFirmantesPorEtapaParaEnvio(): void
    {
        $proyecto = $this->getRecord();

        if (! $proyecto) {
            $this->usarFirmantesPorEtapaParaEnvio = false;
            $this->mensajeFirmantesPorEtapaVista = null;
            $this->mensajeBloqueoFirmantesPorEtapa = 'Debe guardar el borrador antes de activar firmantes por etapa.';

            return;
        }

        try {
            if (! $this->firmantesPorEtapaListos
                || $this->firmantesPorEtapaBloqueado
                || ! empty($this->erroresFirmantesPorEtapa)
                || collect($this->unidadesSinCandidatosPorEtapa)->contains(fn (array $unidades): bool => ! empty($unidades))
            ) {
                throw new RuntimeException('Los firmantes por etapa no estan listos para enviar el proyecto a revision.');
            }

            $this->asignacionesFirmantesPorEtapaNormalizadas($proyecto);
            $this->usarFirmantesPorEtapaParaEnvio = true;
            $this->mensajeBloqueoFirmantesPorEtapa = null;
            $this->mensajeFirmantesPorEtapaVista = 'Firmantes por etapa activados para este envio.';
        } catch (RuntimeException $exception) {
            $this->usarFirmantesPorEtapaParaEnvio = false;
            $this->mensajeFirmantesPorEtapaVista = null;
            $this->mensajeBloqueoFirmantesPorEtapa = $exception->getMessage();
        }
    }

    public function desactivarFirmantesPorEtapaParaEnvio(): void
    {
        $this->usarFirmantesPorEtapaParaEnvio = false;
        $this->mensajeFirmantesPorEtapaVista = null;
    }

    protected function limpiarEstadoFirmantesPorEtapa(): void
    {
        $this->firmantesPorEtapa = [];
        $this->candidatosPorEtapa = [];
        $this->unidadesSinCandidatosPorEtapa = [];
        $this->mensajesFirmantesPorEtapa = [];
        $this->erroresFirmantesPorEtapa = [];
        $this->firmantesPorEtapaListos = false;
        $this->firmantesPorEtapaBloqueado = false;
        $this->mensajeBloqueoFirmantesPorEtapa = null;
        $this->usarFirmantesPorEtapaParaEnvio = false;
    }

    protected function prepararEstadoFirmantesPorEtapa(Proyecto $proyecto): void
    {
        $this->limpiarEstadoFirmantesPorEtapa();

        try {
            $etapas = $this->etapasActivasParaEnvioPorFlujo($proyecto);
            $candidatosPorEtapa = $this->candidatosPorEtapaParaEnvio($proyecto)
                ->keyBy(fn (array $grupo): int => (int) $grupo['etapa']->id);
            $unidadesSinCandidatos = $this->unidadesSinCandidatosParaEnvio($proyecto)
                ->groupBy(fn (array $grupo): int => (int) $grupo['etapa']->id);

            foreach ($etapas as $etapa) {
                $etapaId = (int) $etapa->id;
                $candidatos = $candidatosPorEtapa->get($etapaId)['candidatos'] ?? collect();
                $unidades = $unidadesSinCandidatos->get($etapaId, collect());
                $cantidadCandidatos = $candidatos->count();
                $esPorCadaUnidad = $etapa->multiplicidad_revision === FlujoAprobacionEtapa::MULTIPLICIDAD_POR_CADA_UNIDAD;
                $empleadoId = null;
                $requiereSeleccion = $cantidadCandidatos > 0;
                $bloqueado = false;
                $mensaje = null;

                if ($esPorCadaUnidad) {
                    $bloqueado = true;
                    $requiereSeleccion = false;
                    $mensaje = 'La etapa requiere un revisor por unidad academica y aun no esta integrada en el formulario de envio.';
                } elseif ($cantidadCandidatos === 0) {
                    $bloqueado = true;
                    $requiereSeleccion = false;
                    $mensaje = 'No existen candidatos elegibles para esta etapa.';
                } elseif ($cantidadCandidatos === 1 && $etapa->usuario_responsable_id) {
                    $empleadoId = (int) $candidatos->first()->id;
                    $requiereSeleccion = false;
                }

                $this->firmantesPorEtapa[$etapaId] = [
                    'etapa_id' => $etapaId,
                    'nombre' => (string) $etapa->nombre,
                    'codigo' => $etapa->codigo,
                    'orden' => $etapa->orden !== null ? (int) $etapa->orden : null,
                    'rol' => $etapa->rolRevisor?->name,
                    'alcance_academico' => (string) $etapa->alcance_academico,
                    'multiplicidad_revision' => (string) $etapa->multiplicidad_revision,
                    'cargo_firma_id' => $etapa->cargo_firma_id ? (int) $etapa->cargo_firma_id : null,
                    'empleado_id' => $empleadoId,
                    'requiere_seleccion' => $requiereSeleccion,
                    'bloqueado' => $bloqueado,
                    'mensaje' => $mensaje,
                ];

                $this->candidatosPorEtapa[$etapaId] = $candidatos
                    ->map(fn (Empleado $empleado): array => $this->serializarCandidatoFirmantePorEtapa($empleado))
                    ->values()
                    ->all();

                $this->unidadesSinCandidatosPorEtapa[$etapaId] = $unidades
                    ->map(fn (array $grupo): array => $this->serializarUnidadSinCandidatoPorEtapa($grupo['unidad']))
                    ->values()
                    ->all();

                if ($mensaje) {
                    $this->mensajesFirmantesPorEtapa[$etapaId] = $mensaje;
                }
            }
        } catch (RuntimeException $exception) {
            $this->erroresFirmantesPorEtapa[] = $exception->getMessage();
            $this->firmantesPorEtapaBloqueado = true;
            $this->mensajeBloqueoFirmantesPorEtapa = 'No se puede preparar el envio dinamico de firmantes: '.$exception->getMessage();
        }

        $this->recalcularEstadoFirmantesPorEtapa();
    }

    protected function seleccionarFirmantePorEtapa(
        Proyecto $proyecto,
        int $etapaId,
        int $empleadoId
    ): void {
        if (! array_key_exists($etapaId, $this->firmantesPorEtapa)) {
            throw new RuntimeException('La etapa indicada no esta preparada para seleccionar firmante.');
        }

        try {
            $empleado = $this->validarEmpleadoParaEtapaDeEnvio($proyecto, $etapaId, $empleadoId);
        } catch (RuntimeException $exception) {
            $this->firmantesPorEtapa[$etapaId]['empleado_id'] = null;
            $this->mensajesFirmantesPorEtapa[$etapaId] = $exception->getMessage();
            $this->firmantesPorEtapa[$etapaId]['mensaje'] = $exception->getMessage();
            $this->recalcularEstadoFirmantesPorEtapa();

            throw $exception;
        }

        $this->firmantesPorEtapa[$etapaId]['empleado_id'] = (int) $empleado->id;
        unset($this->mensajesFirmantesPorEtapa[$etapaId], $this->erroresFirmantesPorEtapa[$etapaId]);
        $this->firmantesPorEtapa[$etapaId]['mensaje'] = null;
        $this->recalcularEstadoFirmantesPorEtapa();
    }

    protected function asignacionesFirmantesPorEtapaNormalizadas(Proyecto $proyecto): array
    {
        $asignaciones = [];

        foreach ($this->firmantesPorEtapa as $etapaId => $firmante) {
            $nombre = (string) ($firmante['nombre'] ?? 'sin nombre');

            if (($firmante['multiplicidad_revision'] ?? null) === FlujoAprobacionEtapa::MULTIPLICIDAD_POR_CADA_UNIDAD) {
                throw new RuntimeException(sprintf(
                    'La etapa "%s" requiere un revisor por unidad academica y aun no esta integrada al formulario de envio.',
                    $nombre
                ));
            }

            if (($firmante['bloqueado'] ?? false) === true) {
                throw new RuntimeException(sprintf('La etapa "%s" se encuentra bloqueada.', $nombre));
            }

            if (($firmante['multiplicidad_revision'] ?? null) === FlujoAprobacionEtapa::MULTIPLICIDAD_UNICO && empty($firmante['empleado_id'])) {
                throw new RuntimeException(sprintf('Debe seleccionar un firmante para la etapa "%s".', $nombre));
            }

            $asignaciones[(int) $etapaId] = (int) $firmante['empleado_id'];
        }

        return $this->validarAsignacionesPorEtapaParaEnvio($proyecto, $asignaciones);
    }

    protected function recalcularEstadoFirmantesPorEtapa(): void
    {
        $hayEtapas = ! empty($this->firmantesPorEtapa);
        $hayErrores = ! empty($this->erroresFirmantesPorEtapa);
        $hayUnidadesSinCandidatos = collect($this->unidadesSinCandidatosPorEtapa)
            ->contains(fn (array $unidades): bool => ! empty($unidades));
        $etapaBloqueada = collect($this->firmantesPorEtapa)
            ->contains(fn (array $firmante): bool => ($firmante['bloqueado'] ?? false) === true);
        $hayPorCadaUnidad = collect($this->firmantesPorEtapa)
            ->contains(fn (array $firmante): bool => ($firmante['multiplicidad_revision'] ?? null) === FlujoAprobacionEtapa::MULTIPLICIDAD_POR_CADA_UNIDAD);
        $faltaSeleccionUnica = collect($this->firmantesPorEtapa)
            ->contains(fn (array $firmante): bool => ($firmante['multiplicidad_revision'] ?? null) === FlujoAprobacionEtapa::MULTIPLICIDAD_UNICO
                && empty($firmante['empleado_id']));

        $this->firmantesPorEtapaBloqueado = $hayErrores || $hayUnidadesSinCandidatos || $etapaBloqueada || $hayPorCadaUnidad;
        $this->firmantesPorEtapaListos = $hayEtapas
            && ! $this->firmantesPorEtapaBloqueado
            && ! $faltaSeleccionUnica;

        $this->mensajeBloqueoFirmantesPorEtapa = match (true) {
            $hayErrores => $this->mensajeBloqueoFirmantesPorEtapa ?: 'Hay errores de configuracion en las etapas de firma.',
            $hayUnidadesSinCandidatos => 'Hay unidades academicas sin candidatos elegibles.',
            $hayPorCadaUnidad => 'Existen etapas por unidad academica no integradas al formulario de envio.',
            $etapaBloqueada => 'Existen etapas bloqueadas sin candidatos elegibles.',
            default => null,
        };
    }

    protected function serializarCandidatoFirmantePorEtapa(Empleado $empleado): array
    {
        $empleado->loadMissing(['user.roles', 'centro_facultad', 'departamento_academico', 'carrera']);

        return [
            'empleado_id' => (int) $empleado->id,
            'nombre' => (string) $empleado->nombre_completo,
            'correo' => $empleado->user?->email,
            'centro' => $empleado->centro_facultad?->nombre,
            'departamento' => $empleado->departamento_academico?->nombre,
            'carrera' => $empleado->carrera?->nombre,
            'rol_activo' => $empleado->user?->roles
                ?->first(fn ($rol): bool => (int) $rol->id === (int) $empleado->user->active_role_id)
                ?->name,
        ];
    }

    protected function serializarUnidadSinCandidatoPorEtapa(array $unidad): array
    {
        return [
            'tipo' => (string) ($unidad['tipo'] ?? ''),
            'unidad_id' => (int) ($unidad['unidad_id'] ?? 0),
            'unidad_nombre' => (string) ($unidad['unidad_nombre'] ?? ''),
        ];
    }

    protected function etapasActivasParaEnvioPorFlujo(Proyecto $proyecto): Collection
    {
        $etapas = $proyecto
            ->flujoEtapasActivasOrdenadas(Proyecto::FLUJO_INSCRIPCION)
            ->values();

        if ($etapas->isEmpty()) {
            throw new RuntimeException('No hay etapas activas configuradas para enviar este proyecto a revisión.');
        }

        $etapas->each(function (FlujoAprobacionEtapa $etapa): void {
            if (! $etapa->cargo_firma_id) {
                throw new RuntimeException(sprintf('La etapa "%s" no tiene cargo de firma configurado.', $etapa->nombre));
            }

            if (! $etapa->rol_revisor_id && ! $etapa->usuario_responsable_id) {
                throw new RuntimeException(sprintf('La etapa "%s" no tiene rol revisor ni responsable configurado.', $etapa->nombre));
            }
        });

        return $etapas;
    }

    protected function candidatosPorEtapaParaEnvio(Proyecto $proyecto): Collection
    {
        $resolver = $this->workflowReviewerResolver();

        return $this->etapasActivasParaEnvioPorFlujo($proyecto)
            ->map(fn (FlujoAprobacionEtapa $etapa): array => [
                'etapa' => $etapa,
                'candidatos' => $resolver->candidatosParaEtapa($etapa, $proyecto),
                'unidades_sin_candidatos' => $resolver->unidadesSinCandidatos($etapa, $proyecto),
            ])
            ->values();
    }

    protected function unidadesSinCandidatosParaEnvio(Proyecto $proyecto): Collection
    {
        return $this->candidatosPorEtapaParaEnvio($proyecto)
            ->flatMap(fn (array $grupo): Collection => $grupo['unidades_sin_candidatos']
                ->map(fn (array $unidad): array => [
                    'etapa' => $grupo['etapa'],
                    'unidad' => $unidad,
                ]))
            ->values();
    }

    protected function validarEmpleadoParaEtapaDeEnvio(
        Proyecto $proyecto,
        int $etapaId,
        int $empleadoId
    ): Empleado {
        $etapa = $this->etapasActivasParaEnvioPorFlujo($proyecto)
            ->first(fn (FlujoAprobacionEtapa $etapa): bool => (int) $etapa->id === $etapaId);

        if (! $etapa) {
            throw new RuntimeException('La etapa indicada no pertenece al flujo del proyecto.');
        }

        try {
            return $this->workflowReviewerResolver()->validarEmpleadoElegible($etapa, $proyecto, $empleadoId);
        } catch (RuntimeException $exception) {
            if (str_contains($exception->getMessage(), 'no es elegible')) {
                throw new RuntimeException(sprintf(
                    'El empleado seleccionado no es elegible para la etapa "%s".',
                    $etapa->nombre
                ), previous: $exception);
            }

            throw $exception;
        }
    }

    protected function validarAsignacionesPorEtapaParaEnvio(
        Proyecto $proyecto,
        array $empleadosPorEtapa
    ): array {
        $etapas = $this->etapasActivasParaEnvioPorFlujo($proyecto)->keyBy('id');
        $asignacionesNormalizadas = collect($empleadosPorEtapa)
            ->filter(fn ($empleadoId, $etapaId): bool => filled($etapaId))
            ->mapWithKeys(fn ($empleadoId, $etapaId): array => [
                (int) $etapaId => filled($empleadoId) ? (int) $empleadoId : null,
            ])
            ->all();

        foreach (array_keys($asignacionesNormalizadas) as $etapaId) {
            if (! $etapas->has($etapaId)) {
                throw new RuntimeException('La etapa indicada no pertenece al flujo del proyecto.');
            }
        }

        $validadas = [];

        foreach ($etapas as $etapa) {
            if ($etapa->multiplicidad_revision === FlujoAprobacionEtapa::MULTIPLICIDAD_POR_CADA_UNIDAD) {
                throw new RuntimeException(sprintf(
                    'La etapa "%s" requiere un revisor por unidad académica y aún no está integrada al formulario de envío.',
                    $etapa->nombre
                ));
            }

            if (! array_key_exists((int) $etapa->id, $asignacionesNormalizadas) || ! $asignacionesNormalizadas[(int) $etapa->id]) {
                throw new RuntimeException(sprintf('No se indicó un empleado para la etapa "%s".', $etapa->nombre));
            }

            $validadas[(int) $etapa->id] = $this->validarEmpleadoParaEtapaDeEnvio(
                $proyecto,
                (int) $etapa->id,
                $asignacionesNormalizadas[(int) $etapa->id],
            )->id;
        }

        return $validadas;
    }

    protected function workflowReviewerResolver(): WorkflowReviewerResolver
    {
        return app(WorkflowReviewerResolver::class);
    }

    protected function loadFirmasFromRecord(Proyecto $record): void
    {
        foreach ($record->firma_proyecto as $firma) {
            $nombreCargo = $firma->cargo_firma?->tipoCargoFirma?->nombre;

            match ($nombreCargo) {
                'Jefe Departamento' => $this->jefe_empleado_id = $firma->empleado_id,
                'Director centro' => $this->decano_empleado_id = $firma->empleado_id,
                'Enlace Vinculacion' => $this->enlace_empleado_id = $firma->empleado_id,
                default => null,
            };
        }
    }

    public function borrador(): void
    {
        $this->autoGuardarBorrador();

        if (!$this->recordId) {
            Notification::make()->title('Error')->body('Complete al menos el primer paso.')->danger()->send();
            return;
        }
        $record = Proyecto::findOrFail($this->recordId);
        $empleado = auth()->user()->empleado;
        try {
            $record->agregarFirma(cargoFirma: 'Coordinador Proyecto', empleado: $empleado);
            $record->agregarEstadoByName(empleado: $empleado, tipoEstadoNombre: 'Borrador', comentario: 'Guardado como borrador');
        } catch (\Exception $e) {
            Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
            return;
        }
        Notification::make()->title('Borrador guardado')->success()->send();
        redirect()->route('proyectosDocente');
    }

    // ─── Normalization helpers ────────────────────────────────────────────────

    protected function dateForInput(mixed $value): string
    {
        if (empty($value)) return '';
        try {
            if ($value instanceof \DateTimeInterface) return $value->format('Y-m-d');
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return '';
        }
    }

    protected function normalizePlazo(?string $value): string
    {
        $n = trim(str_replace(['-', ' '], '_', mb_strtolower((string) $value)));
        return match ($n) {
            'corto', 'corto_plazo' => 'corto_plazo',
            'mediano', 'mediano_plazo' => 'mediano_plazo',
            'largo', 'largo_plazo' => 'largo_plazo',
            default => '',
        };
    }

    protected function normalizeTipoParticipacionEstudiante(?string $value): string
    {
        $n = trim(mb_strtolower((string) $value));
        $n = strtr($n, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u']);
        $n = trim((string) preg_replace('/[^a-z0-9]+/u', '_', $n), '_');
        return match ($n) {
            'pps', 'servicio_social', 'servicio_social_o_pps', 'pps_servicio_social', 'servicio_social_pps' => 'Servicio Social o PPS',
            'practica_profesional' => 'Practica Profesional',
            'asignatura', 'practica_asignatura', 'practica_de_asignatura' => 'Practica Asignatura',
            'voluntariado' => 'Voluntariado',
            default => '',
        };
    }

    protected function isTipoParticipacionAsignatura(?string $value): bool
    {
        return $this->normalizeTipoParticipacionEstudiante($value) === 'Practica Asignatura';
    }

    protected function responsableIdsDisponibles(?Proyecto $record = null): array
    {
        $coordId = auth()->user()->empleado?->id;
        $stateIds = collect($this->empleado_proyecto)->pluck('empleado_id')->filter()->map(fn($id) => (int) $id);
        $recordIds = $record ? $record->empleado_proyecto()->pluck('empleado_id')->map(fn($id) => (int) $id) : collect();
        return collect([$coordId])->merge($stateIds)->merge($recordIds)->filter()->map(fn($id) => (int) $id)->unique()->values()->toArray();
    }

    protected function responsableOptions(?Proyecto $record = null)
    {
        $ids = $this->responsableIdsDisponibles($record);
        if (empty($ids)) return collect();
        return Empleado::whereIn('id', $ids)->orderBy('nombre_completo')->pluck('nombre_completo', 'id');
    }

    protected function normalizeInstrumentoTipo(?string $value): string
    {
        $n = trim(mb_strtolower((string) $value));
        $n = strtr($n, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
        $n = trim((string) preg_replace('/[^a-z0-9]+/u', '_', $n), '_');
        return match ($n) {
            'carta_formal_solicitud', 'carta_formal_de_solicitud_a_la_unidad_academica' => 'carta_formal_solicitud',
            'carta_intenciones', 'carta_de_intenciones', 'carta_intencion', 'carta_de_intencion' => 'carta_intenciones',
            'convenio_marco', 'convenio_marco_con_la_unah', 'convenio' => 'convenio_marco',
            default => '',
        };
    }

    protected function defaultAporteInstitucionalRows(): array
    {
        return [
            ['concepto' => 'horas_trabajo_docentes', 'concepto_label' => 'a) Horas de trabajo docentes', 'unidad' => 'hra_profes', 'unidad_label' => 'Hra/profes', 'cantidad' => 0, 'costo_unitario' => 0, 'costo_total' => 0, 'editable' => true],
            ['concepto' => 'horas_trabajo_estudiantes', 'concepto_label' => 'b) Horas de trabajo estudiantes', 'unidad' => 'hra_estud', 'unidad_label' => 'Hra/estud', 'cantidad' => 0, 'costo_unitario' => 0, 'costo_total' => 0, 'editable' => true],
            ['concepto' => 'gastos_movilizacion', 'concepto_label' => 'c) Gastos de movilización', 'unidad' => 'global', 'unidad_label' => 'Global', 'cantidad' => 0, 'costo_unitario' => 0, 'costo_total' => 0, 'editable' => true],
            ['concepto' => 'utiles_materiales_oficina', 'concepto_label' => 'd) Útiles y materiales de oficina', 'unidad' => 'global', 'unidad_label' => 'Global', 'cantidad' => 0, 'costo_unitario' => 0, 'costo_total' => 0, 'editable' => true],
            ['concepto' => 'gastos_impresion', 'concepto_label' => 'e) Gastos de impresión', 'unidad' => 'global', 'unidad_label' => 'Global', 'cantidad' => 0, 'costo_unitario' => 0, 'costo_total' => 0, 'editable' => true],
            ['concepto' => 'costos_indirectos_infraestructura', 'concepto_label' => 'f) Costos indirectos por infraestructura', 'unidad' => 'porcentaje', 'unidad_label' => '%', 'cantidad' => 0, 'costo_unitario' => 0, 'costo_total' => 0, 'editable' => false],
            ['concepto' => 'costos_indirectos_servicios', 'concepto_label' => 'g) Costos indirectos por servicios públicos', 'unidad' => 'porcentaje', 'unidad_label' => '%', 'cantidad' => 0, 'costo_unitario' => 0, 'costo_total' => 0, 'editable' => false],
        ];
    }

    protected function normalizeAporteRows(array $rows): array
    {
        $byConcept = collect($rows)->filter(fn($row) => !empty($row['concepto']))->keyBy('concepto');
        return collect($this->defaultAporteInstitucionalRows())->map(function (array $default) use ($byConcept) {
            $existing = $byConcept->get($default['concepto'], []);
            return array_merge($default, [
                'cantidad' => (float)($existing['cantidad'] ?? $default['cantidad']),
                'costo_unitario' => (float)($existing['costo_unitario'] ?? $default['costo_unitario']),
                'costo_total' => (float)($existing['costo_total'] ?? $default['costo_total']),
            ]);
        })->toArray();
    }

    protected function recalculateAporteInstitucional(): void
    {
        $this->aporte_institucional = $this->normalizeAporteRows($this->aporte_institucional);
        foreach ($this->aporte_institucional as $index => $aporte) {
            if ($aporte['editable'] ?? true) {
                $this->aporte_institucional[$index]['costo_total'] = (float)($aporte['cantidad'] ?? 0) * (float)($aporte['costo_unitario'] ?? 0);
            }
        }
        $base = collect($this->aporte_institucional)->whereIn('concepto', ['horas_trabajo_docentes', 'horas_trabajo_estudiantes', 'gastos_movilizacion', 'utiles_materiales_oficina', 'gastos_impresion']);
        $cantidad = round($base->sum('cantidad') * 0.05, 2);
        $costoUnitario = round($base->sum('costo_unitario') * 0.05, 2);
        $costoTotal = round($cantidad * $costoUnitario, 2);
        foreach ($this->aporte_institucional as $index => $aporte) {
            if (in_array($aporte['concepto'], ['costos_indirectos_infraestructura', 'costos_indirectos_servicios'], true)) {
                $this->aporte_institucional[$index]['cantidad'] = $cantidad;
                $this->aporte_institucional[$index]['costo_unitario'] = $costoUnitario;
                $this->aporte_institucional[$index]['costo_total'] = $costoTotal;
            }
        }
    }

    // ─── Render ───────────────────────────────────────────────────────────────

    public function render(): View
    {
        $record = $this->recordId ? Proyecto::with('anexos')->find($this->recordId) : null;

        // Empleados para modal de búsqueda (paso 2)
        $empleadosModal = $this->showEmpleadoModal
            ? Empleado::when(!empty($this->empleadoModalSearch), function ($q) {
                $q->where('nombre_completo', 'LIKE', '%' . $this->empleadoModalSearch . '%')
                  ->orWhere('numero_empleado', 'LIKE', '%' . $this->empleadoModalSearch . '%');
            })
            ->where('user_id', '!=', auth()->id())
            ->orderBy('nombre_completo')
            ->limit(50)
            ->get(['id', 'nombre_completo', 'numero_empleado', 'tipo_empleado'])
            : collect();

        // Firmantes filtrados por las facultades del paso 1
        $firmantesOpts = Empleado::when(!empty($this->firmaSearch), function ($q) {
                $q->where('nombre_completo', 'LIKE', '%' . $this->firmaSearch . '%')
                  ->orWhere('numero_empleado', 'LIKE', '%' . $this->firmaSearch . '%');
            })
            ->when(!empty($this->facultades_centros), fn($q) => $q->whereIn('centro_facultad_id', $this->facultades_centros))
            ->orderBy('nombre_completo')
            ->get(['id', 'nombre_completo', 'numero_empleado']);

        return view('livewire.proyectos.vinculacion.create-proyecto-vinculacion', [
            'modalidades' => Modalidad::orderBy('nombre')->pluck('nombre', 'id'),
            'categorias' => Categoria::orderBy('nombre')->pluck('nombre', 'id'),
            'ejesPrioritarios' => EjesPrioritariosUnah::orderBy('nombre')->pluck('nombre', 'id'),
            'facultadesCentros' => FacultadCentro::orderBy('nombre')->pluck('nombre', 'id'),
            'departamentosAcademicos' => empty($this->facultades_centros)
                ? collect()
                : DepartamentoAcademico::whereIn('centro_facultad_id', $this->facultades_centros)->orderBy('nombre')->pluck('nombre', 'id'),
            'carrerasOpts' => empty($this->departamentos_academicos)
                ? collect()
                : Carrera::where(function ($q) {
                    $q->whereHas('departamentosAcademicos', fn($dq) => $dq->whereIn('departamento_academico.id', $this->departamentos_academicos))
                      ->orWhereIn('departamento_academico_id', $this->departamentos_academicos);
                })->orderBy('nombre')->pluck('nombre', 'id'),
            'odsList' => Od::orderBy('nombre')->pluck('nombre', 'id'),
            'metasList' => collect($this->metasDisponibles),
            'empleados' => Empleado::where('user_id', '!=', auth()->id())->orderBy('nombre_completo')->pluck('nombre_completo', 'id'),
            'empleadosModal' => $empleadosModal,
            'responsablesOptions' => $this->responsableOptions($record),
            'internacionales' => IntegranteInternacional::orderBy('nombre_completo')->get()->mapWithKeys(fn($i) => [$i->id => "{$i->nombre_completo} ({$i->pais})"]),
            'paises' => Pais::orderBy('nombre')->pluck('nombre', 'id'),
            'tiposParticipacionEstudiante' => $this->tipoParticipacionEstudianteOpciones,
            'asignaturasOpciones' => $this->asignaturasDisponibles,
            'carrerasSeleccionadas' => $this->carrerasSeleccionadasOptions(),
            'periodosAcademicos' => $this->periodosAcademicosDisponibles,
            'departamentosGeo' => \App\Models\Demografia\Departamento::orderBy('nombre')->pluck('nombre', 'id'),
            'municipiosGeo' => empty($this->departamento_geo)
                ? collect()
                : Municipio::whereIn('departamento_id', $this->ids($this->departamento_geo))->orderBy('nombre')->pluck('nombre', 'id'),
            'firmantesOpts' => $firmantesOpts,
            'tematicaPrincipalOpciones' => $this->tematicaPrincipalOpciones,
            'metodologiaSeguimientoOpciones' => $this->metodologiaSeguimientoOpciones,
            'record' => $record,
            'coordNombre' => auth()->user()->empleado?->nombre_completo ?? auth()->user()->name,
        ]);
    }
}
