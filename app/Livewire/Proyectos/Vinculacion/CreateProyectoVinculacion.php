<?php

namespace App\Livewire\Proyectos\Vinculacion;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Support\Notification;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\CargoFirma;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\Modalidad;
use App\Models\Proyecto\Categoria;
use App\Models\Proyecto\EjesPrioritariosUnah;
use App\Models\Proyecto\Od;
use App\Models\Proyecto\MetaContribuye;
use App\Models\Proyecto\IntegranteInternacional;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Estado\TipoEstado;
use App\Models\Asignatura;
use App\Models\PeriodoAcademico;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\UnidadAcademica\DepartamentoAcademico;
use App\Models\UnidadAcademica\Carrera;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Mail;
use App\Mail\ProyectoCreado;

class CreateProyectoVinculacion extends Component
{
    use WithFileUploads;

    public int $currentStep = 1;
    public ?int $recordId = null;

    // Step 1
    public string $nombre_proyecto = '';
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
    public string $fecha_inicio = '';
    public string $fecha_finalizacion = '';

    // Step 2
    public array $empleado_proyecto = [];
    public array $estudiante_proyecto = [];
    public array $integrante_internacional_proyecto = [];

    // Step 2 – modals
    public bool $showEmpleadoModal = false;
    public string $empleadoModalSearch = '';

    public bool $showEstudianteModal = false;
    public ?int $editEstudianteIndex = null;
    public array $nuevoEstudiante = [
        'tipo_participacion_estudiante' => '',
        'asignatura_id' => null,
        'periodo_academico_id' => null,
        'cantidad_estudiantes_hombres' => 0,
        'cantidad_estudiantes_mujeres' => 0,
        'total_estudiantes' => 0,
    ];

    public bool $showInternacionalModal = false;
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
    public int $indigenas_hombres = 0;
    public int $indigenas_mujeres = 0;
    public int $afroamericanos_hombres = 0;
    public int $afroamericanos_mujeres = 0;
    public int $mestizos_hombres = 0;
    public int $mestizos_mujeres = 0;
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

    // Step 10 (was 9) – firmas
    public ?int $jefe_empleado_id = null;
    public ?int $decano_empleado_id = null;
    public ?int $enlace_empleado_id = null;
    public string $firmaSearch = '';

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

    public function mount(?int $record = null): void
    {
        if ($record !== null) {
            $proyecto = Proyecto::find($record);
            if ($proyecto) {
                if (!$proyecto->coordinadorIsCurrentUser() ||
                    !$proyecto->proyectoIsInAnyEstados(['Borrador', 'Subsanacion', 'Autoguardado'])) {
                    abort(403);
                }
                $this->recordId = $proyecto->id;
                $this->loadFromRecord($proyecto);
            }
        }
        $this->initDefaults();
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
        ]);

        $this->nombre_proyecto = $record->nombre_proyecto ?? '';
        $this->modalidad_id = $record->modalidad_id;
        $this->categoria = $record->categoria->pluck('id')->toArray();
        $this->ejes_prioritarios_unah = $record->ejes_prioritarios_unah->pluck('id')->toArray();
        $this->facultades_centros = $record->facultades_centros->pluck('id')->toArray();
        $this->departamentos_academicos = $record->departamentos_academicos->pluck('id')->toArray();
        $this->carreras = $record->carreras->pluck('id')->toArray();
        $this->programa_pertenece = $record->programa_pertenece ?? '';
        $this->lineas_investigacion_academica = $record->lineas_investigacion_academica ?? '';
        $this->ods = $record->ods->pluck('id')->toArray();
        $this->metasContribuye = $record->metasContribuye->pluck('id')->toArray();
        $this->fecha_inicio = $this->dateForInput($record->fecha_inicio);
        $this->fecha_finalizacion = $this->dateForInput($record->fecha_finalizacion);

        $this->empleado_proyecto = $record->empleado_proyecto->map(fn($ep) => [
            'empleado_id' => $ep->empleado_id,
            'rol' => $ep->rol ?? 'Integrante',
            'nombre' => $ep->empleado?->nombre_completo ?? '',
        ])->toArray();

        $this->estudiante_proyecto = $record->estudiante_proyecto->map(fn($ep) => [
            'tipo_participacion_estudiante' => $this->normalizeTipoParticipacionEstudiante($ep->tipo_participacion_estudiante) ?: $ep->tipo_participacion_estudiante,
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
                'documento_file' => null,
            ])->toArray(),
        ])->toArray();

        $this->actividades = $record->actividades->map(fn($a) => [
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
        $this->caserio = $record->caserio ?? '';
        $this->aldea = $record->aldea ?? '';
        $this->alineamiento_reforma = $record->alineamiento_reforma ?? '';
        $this->impacto_deseado = $record->impacto_deseado ?? '';
        $this->metodologia = $record->metodologia ?? '';
        $this->bibliografia = $record->bibliografia ?? '';

        $this->objetivo_general = $record->objetivo_general ?? '';
        $this->objetivosEspecificos = $record->objetivosEspecificos->map(fn($obj) => [
            'descripcion' => $obj->descripcion,
            'resultados' => $obj->resultados->map(fn($r) => [
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
            $this->objetivosEspecificos = [['descripcion' => '', 'resultados' => [['nombre_resultado' => '', 'nombre_indicador' => '', 'nombre_medio_verificacion' => '', 'plazo' => 'corto_plazo']]]];
        }
    }

    // ─── Step Navigation ────────────────────────────────────────────────────

    public function nextStep(): void
    {
        $this->saveCurrentStep();
        if ($this->getErrorBag()->isEmpty() && $this->currentStep < 10) {
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
        if ($this->recordId && $step >= 1 && $step <= 10) {
            $this->currentStep = $step;
            $this->selectedObjetivoIndex = 0;
        }
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
                && !empty($this->fecha_inicio)
                && !empty($this->fecha_finalizacion)
                && !empty($this->programa_pertenece)
                && !empty($this->lineas_investigacion_academica)
                && !empty($this->ods),
            2 => !empty($this->estudiante_proyecto)
                && !empty(array_filter(array_column($this->estudiante_proyecto, 'tipo_participacion_estudiante'))),
            3 => !empty(array_filter(array_column($this->entidad_contraparte, 'nombre'))),
            4 => !empty(array_filter(array_column($this->actividades, 'descripcion'))),
            5 => !empty($this->resumen) && !empty($this->descripcion_participantes) && !empty($this->definicion_problema),
            6 => !empty($this->departamento_geo) || $this->poblacion_participante > 0,
            7 => !empty($this->objetivo_general)
                && !empty($this->objetivosEspecificos)
                && !empty($this->objetivosEspecificos[0]['descripcion'] ?? ''),
            8 => collect($this->aporte_institucional)->sum('costo_total') > 0,
            9 => true,
            10 => $this->jefe_empleado_id && $this->decano_empleado_id && $this->enlace_empleado_id,
            default => false,
        };
    }

    // ─── Ensure Record ───────────────────────────────────────────────────────

    protected function ensureRecord(): Proyecto
    {
        if ($this->recordId) return Proyecto::findOrFail($this->recordId);

        $empleado = auth()->user()->empleado;
        $record = Proyecto::create([
            'nombre_proyecto' => $this->nombre_proyecto,
            'modalidad_id' => $this->modalidad_id,
            'fecha_inicio' => $this->fecha_inicio ?: null,
            'fecha_finalizacion' => $this->fecha_finalizacion ?: null,
            'programa_pertenece' => $this->programa_pertenece,
            'lineas_investigacion_academica' => $this->lineas_investigacion_academica,
            'flujo_aprobacion_id' => FlujoAprobacion::defaultForProyectos()?->id,
        ]);
        $record->coordinador_proyecto()->firstOrCreate(
            ['empleado_id' => $empleado->id],
            ['rol' => 'Coordinador']
        );
        $record->agregarEstadoByName(
            empleado: $empleado,
            tipoEstadoNombre: 'Autoguardado',
            comentario: 'Proyecto guardado automáticamente',
        );
        $this->recordId = $record->id;
        return $record;
    }

    // ─── Save Steps ──────────────────────────────────────────────────────────

    protected function saveStep1(): void
    {
        $this->validate([
            'nombre_proyecto' => 'required|string|max:255',
            'modalidad_id' => 'required|integer',
            'categoria' => 'required|array|min:1',
            'ejes_prioritarios_unah' => 'required|array|min:1',
            'facultades_centros' => 'required|array|min:1',
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
        $record->metasContribuye()->sync($this->metasContribuye ?? []);
        Notification::make()->title('Paso I guardado')->success()->send();
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
            'estudiante_proyecto.*.asignatura_id' => 'nullable|exists:asignaturas,id',
            'estudiante_proyecto.*.periodo_academico_id' => 'nullable|exists:periodos_academicos,id',
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
                if (empty($item['asignatura_id'])) {
                    $this->addError("estudiante_proyecto.$i.asignatura_id", 'Seleccione la asignatura.');
                    $hasInvalidStudentRows = true;
                }
                if (empty($item['periodo_academico_id'])) {
                    $this->addError("estudiante_proyecto.$i.periodo_academico_id", 'Seleccione el periodo académico.');
                    $hasInvalidStudentRows = true;
                }
            } else {
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
                $record->estudiante_proyecto()->create([
                    'tipo_participacion_estudiante' => $tipo,
                    'asignatura_id' => $isAsignatura ? ($item['asignatura_id'] ?? null) : null,
                    'periodo_academico_id' => $isAsignatura ? ($item['periodo_academico_id'] ?? null) : null,
                    'cantidad_estudiantes_hombres' => $item['cantidad_estudiantes_hombres'] ?? 0,
                    'cantidad_estudiantes_mujeres' => $item['cantidad_estudiantes_mujeres'] ?? 0,
                    'total_estudiantes' => ($item['cantidad_estudiantes_hombres'] ?? 0) + ($item['cantidad_estudiantes_mujeres'] ?? 0),
                ]);
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
            'entidad_contraparte.*.instrumento_formalizacion.*.documento_file' => 'nullable|file|max:10240',
        ]);

        $hasMissingDocument = false;
        foreach ($this->entidad_contraparte as $ci => $item) {
            foreach ($item['instrumento_formalizacion'] ?? [] as $ii => $inst) {
                if (empty($inst['tipo_documento'])) continue;
                $isExisting = !empty($inst['id']);
                $hasStoredDocument = !empty($inst['documento_url']);
                $hasUploadedDocument = isset($inst['documento_file']) && is_object($inst['documento_file']);
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
                        $documentoUrl = $inst['documento_url'] ?? null;
                        if (isset($inst['documento_file']) && is_object($inst['documento_file'])) {
                            $documentoUrl = $inst['documento_file']->store('instrumentos-formalizacion', 'public');
                        }
                        $instrumento = $entidad->instrumento_formalizacion()->create([
                            'tipo_documento' => $inst['tipo_documento'],
                            'documento_url' => $documentoUrl,
                        ]);
                        $this->entidad_contraparte[$ci]['instrumento_formalizacion'][$ii]['id'] = $instrumento->id;
                        $this->entidad_contraparte[$ci]['instrumento_formalizacion'][$ii]['documento_url'] = $documentoUrl;
                        $this->entidad_contraparte[$ci]['instrumento_formalizacion'][$ii]['documento_file'] = null;
                    }
                }
            }
        }
        Notification::make()->title('Paso III guardado')->success()->send();
    }

    protected function saveStep4(): void
    {
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

        $record->actividades()->each(fn($a) => $a->empleados()->detach());
        $record->actividades()->delete();
        foreach ($this->actividades as $item) {
            if (!empty($item['descripcion'])) {
                $actividad = $record->actividades()->create([
                    'descripcion' => $item['descripcion'],
                    'fecha_inicio' => $item['fecha_inicio'] ?: null,
                    'fecha_finalizacion' => $item['fecha_finalizacion'] ?: null,
                    'horas' => $item['horas'] ?? 0,
                ]);
                $ids = collect($item['empleados'] ?? [])->filter()->map(fn($id) => (int) $id)->unique()->values()->toArray();
                if (!empty($ids)) $actividad->empleados()->sync($ids);
            }
        }
        Notification::make()->title('Paso IV guardado')->success()->send();
    }

    protected function saveStep5(): void
    {
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
        if (!empty($this->departamento_geo)) $record->departamento()->sync($this->departamento_geo);
        if (!empty($this->municipio_geo)) $record->municipio()->sync($this->municipio_geo);
        Notification::make()->title('Paso VI guardado')->success()->send();
    }

    protected function saveStep7(): void
    {
        $this->validate([
            'objetivo_general' => 'required|string',
            'objetivosEspecificos' => 'required|array|min:1',
            'objetivosEspecificos.*.descripcion' => 'required|string',
            'objetivosEspecificos.*.resultados.*.plazo' => 'nullable|in:' . implode(',', $this->plazoOpciones),
        ]);
        $record = $this->ensureRecord();
        $record->update(['objetivo_general' => $this->objetivo_general]);
        $record->objetivosEspecificos()->each(fn($obj) => $obj->resultados()->delete());
        $record->objetivosEspecificos()->delete();
        foreach ($this->objetivosEspecificos as $objData) {
            $obj = $record->objetivosEspecificos()->create(['descripcion' => $objData['descripcion']]);
            foreach ($objData['resultados'] ?? [] as $rData) {
                if (!empty($rData['nombre_resultado'])) {
                    $obj->resultados()->create([
                        'nombre_resultado' => $rData['nombre_resultado'],
                        'nombre_indicador' => $rData['nombre_indicador'] ?? '',
                        'nombre_medio_verificacion' => $rData['nombre_medio_verificacion'] ?? '',
                        'plazo' => $this->normalizePlazo($rData['plazo'] ?? '') ?: 'corto_plazo',
                    ]);
                }
            }
        }
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
        $this->hombres = $this->indigenas_hombres + $this->afroamericanos_hombres + $this->mestizos_hombres;
        $this->mujeres = $this->indigenas_mujeres + $this->afroamericanos_mujeres + $this->mestizos_mujeres;
        $this->poblacion_participante = $this->hombres + $this->mujeres;
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
    }

    // ─── Estudiante Modal (Step 2) ────────────────────────────────────────────

    public function openEstudianteModal(?int $index = null): void
    {
        $this->resetErrorBag();
        if ($index !== null && isset($this->estudiante_proyecto[$index])) {
            $this->nuevoEstudiante = $this->estudiante_proyecto[$index];
            $this->editEstudianteIndex = $index;
        } else {
            $this->nuevoEstudiante = ['tipo_participacion_estudiante' => '', 'asignatura_id' => null, 'periodo_academico_id' => null, 'cantidad_estudiantes_hombres' => 0, 'cantidad_estudiantes_mujeres' => 0, 'total_estudiantes' => 0];
            $this->editEstudianteIndex = null;
        }
        $this->showEstudianteModal = true;
    }

    public function closeEstudianteModal(): void
    {
        $this->showEstudianteModal = false;
        $this->editEstudianteIndex = null;
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
            if (empty($this->nuevoEstudiante['asignatura_id'])) {
                $this->addError('nuevoEstudiante.asignatura_id', 'Seleccione la asignatura.');
                return;
            }
            if (empty($this->nuevoEstudiante['periodo_academico_id'])) {
                $this->addError('nuevoEstudiante.periodo_academico_id', 'Seleccione el periodo académico.');
                return;
            }
        } else {
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
        $this->nuevoEstudiante = ['tipo_participacion_estudiante' => '', 'asignatura_id' => null, 'periodo_academico_id' => null, 'cantidad_estudiantes_hombres' => 0, 'cantidad_estudiantes_mujeres' => 0, 'total_estudiantes' => 0];
    }

    public function removeEstudiante(int $i): void
    {
        array_splice($this->estudiante_proyecto, $i, 1);
    }

    // ─── Internacional Modal (Step 2) ─────────────────────────────────────────

    public function openInternacionalModal(): void
    {
        $this->resetErrorBag();
        $this->showInternacionalModal = true;
    }

    public function closeInternacionalModal(): void
    {
        $this->showInternacionalModal = false;
        $this->resetNuevoIntegranteInternacional();
    }

    public function saveNuevoIntegranteInternacional(): void
    {
        $data = $this->validate([
            'nuevoIntegranteInternacional.nombre_completo' => 'required|string|max:255',
            'nuevoIntegranteInternacional.documento_identidad' => 'required|string|max:255',
            'nuevoIntegranteInternacional.sexo' => 'nullable|in:masculino,femenino,otro',
            'nuevoIntegranteInternacional.email' => 'required|email|max:255',
            'nuevoIntegranteInternacional.pais' => 'required|string|max:255',
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
        if (empty($this->nuevaContraparte['nombre'])) {
            $this->addError('nuevaContraparte.nombre', 'El nombre de la entidad es obligatorio.');
            return;
        }
        if ($this->editContraparteIndex !== null) {
            $this->entidad_contraparte[$this->editContraparteIndex] = $this->nuevaContraparte;
        } else {
            $this->entidad_contraparte[] = $this->nuevaContraparte;
        }
        $this->showContraparteModal = false;
        $this->editContraparteIndex = null;
    }

    public function removeContraparte(int $i): void
    {
        array_splice($this->entidad_contraparte, $i, 1);
    }

    public function addInstrumentoToModal(): void
    {
        $this->nuevaContraparte['instrumento_formalizacion'][] = ['id' => null, 'tipo_documento' => '', 'documento_url' => null, 'documento_file' => null];
    }

    public function removeInstrumentoFromModal(int $ii): void
    {
        array_splice($this->nuevaContraparte['instrumento_formalizacion'], $ii, 1);
    }

    // Keep for backward compat
    public function addInstrumento(int $ci): void
    {
        $this->entidad_contraparte[$ci]['instrumento_formalizacion'][] = ['id' => null, 'tipo_documento' => '', 'documento_url' => null, 'documento_file' => null];
    }

    public function removeInstrumento(int $ci, int $ii): void
    {
        array_splice($this->entidad_contraparte[$ci]['instrumento_formalizacion'], $ii, 1);
    }

    // ─── Actividad Modal (Step 4) ─────────────────────────────────────────────

    public function openActividadModal(?int $index = null): void
    {
        $this->resetErrorBag();
        if ($index !== null && isset($this->actividades[$index])) {
            $this->nuevaActividad = $this->actividades[$index];
            $this->editActividadIndex = $index;
        } else {
            $this->nuevaActividad = ['descripcion' => '', 'empleados' => [], 'fecha_inicio' => '', 'fecha_finalizacion' => '', 'horas' => ''];
            $this->editActividadIndex = null;
        }
        $this->showActividadModal = true;
    }

    public function closeActividadModal(): void
    {
        $this->showActividadModal = false;
        $this->editActividadIndex = null;
    }

    public function saveActividad(): void
    {
        if (empty($this->nuevaActividad['descripcion'])) {
            $this->addError('nuevaActividad.descripcion', 'La descripción de la actividad es obligatoria.');
            return;
        }
        if ($this->editActividadIndex !== null) {
            $this->actividades[$this->editActividadIndex] = $this->nuevaActividad;
        } else {
            $this->actividades[] = $this->nuevaActividad;
        }
        $this->showActividadModal = false;
        $this->editActividadIndex = null;
    }

    public function removeActividad(int $i): void
    {
        array_splice($this->actividades, $i, 1);
    }

    // ─── Marco Lógico (Step 7) ────────────────────────────────────────────────

    public function selectObjetivo(int $index): void
    {
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
    }

    // ─── Objetivo / Resultado Helpers (Step 7) ────────────────────────────────

    public function addObjetivo(): void
    {
        $this->objetivosEspecificos[] = ['descripcion' => '', 'resultados' => [['nombre_resultado' => '', 'nombre_indicador' => '', 'nombre_medio_verificacion' => '', 'plazo' => 'corto_plazo']]];
        $this->selectedObjetivoIndex = count($this->objetivosEspecificos) - 1;
    }

    public function removeObjetivo(int $i): void
    {
        array_splice($this->objetivosEspecificos, $i, 1);
        $this->selectedObjetivoIndex = max(0, min($this->selectedObjetivoIndex, count($this->objetivosEspecificos) - 1));
    }

    public function addResultado(int $oi): void
    {
        $this->objetivosEspecificos[$oi]['resultados'][] = ['nombre_resultado' => '', 'nombre_indicador' => '', 'nombre_medio_verificacion' => '', 'plazo' => 'corto_plazo'];
    }

    public function removeResultado(int $oi, int $ri): void
    {
        array_splice($this->objetivosEspecificos[$oi]['resultados'], $ri, 1);
    }

    // ─── Presupuesto Helpers (Step 8) ─────────────────────────────────────────

    public function updateAporteTotal(int $i): void
    {
        $this->aporte_institucional[$i]['costo_total'] = (float)($this->aporte_institucional[$i]['cantidad'] ?? 0) * (float)($this->aporte_institucional[$i]['costo_unitario'] ?? 0);
        $this->recalculateAporteInstitucional();
    }

    // ─── Anexo Methods (Step 9) ───────────────────────────────────────────────

    public function uploadAnexo(): void
    {
        $this->validate(['newAnexo' => 'required|file|max:10240']);
        $path = $this->newAnexo->store('anexos', 'public');
        $record = $this->ensureRecord();
        $record->anexos()->create(['documento_url' => $path]);
        $this->newAnexo = null;
        Notification::make()->title('Anexo subido')->success()->send();
    }

    public function deleteAnexo(int $id): void
    {
        if ($this->recordId) {
            Proyecto::findOrFail($this->recordId)->anexos()->where('id', $id)->delete();
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
        if (!$this->recordId) {
            Notification::make()->title('Error')->body('Complete al menos el primer paso.')->danger()->send();
            return;
        }
        $record = Proyecto::findOrFail($this->recordId);
        $empleado = auth()->user()->empleado;
        try {
            $this->saveFirmas($record);
            $record->agregarFirma(cargoFirma: 'Coordinador Proyecto', empleado: $empleado);
            $cargoFirma = CargoFirma::join('tipo_cargo_firma', 'tipo_cargo_firma.id', '=', 'cargo_firma.tipo_cargo_firma_id')
                ->where('tipo_cargo_firma.nombre', 'Coordinador Proyecto')
                ->where('cargo_firma.descripcion', 'Proyecto')
                ->first();

            $nextEstadoId = $cargoFirma
                ? $record->nextEstadoIdForCargo($cargoFirma->id)
                : null;

            if (!$nextEstadoId) {
                $nextEstadoId = TipoEstado::where('nombre', 'Enlace Vinculacion')->first()?->id;
            }
            if ($nextEstadoId) {
                $record->agregarEstado(empleado: $empleado, tipoEstadoId: $nextEstadoId, comentario: 'Proyecto enviado para firma');
            }
        } catch (\Exception $e) {
            Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
            return;
        }
        try { Mail::to(auth()->user()->email)->send(new ProyectoCreado($record, auth()->user())); } catch (\Exception $e) { \Log::warning($e->getMessage()); }
        Notification::make()->title('Proyecto enviado a firmar')->success()->send();
        redirect()->route('proyectosDocente');
    }

    protected function saveFirmas(Proyecto $record): void
    {
        $map = ['Jefe Departamento' => $this->jefe_empleado_id, 'Director centro' => $this->decano_empleado_id, 'Enlace Vinculacion' => $this->enlace_empleado_id];
        foreach ($map as $nombre => $empId) {
            if (!$empId) continue;
            $cargo = CargoFirma::join('tipo_cargo_firma', 'tipo_cargo_firma.id', '=', 'cargo_firma.tipo_cargo_firma_id')
                ->where('tipo_cargo_firma.nombre', $nombre)->select('cargo_firma.*')->first();
            if ($cargo) {
                $record->firma_proyecto()->updateOrCreate(
                    ['cargo_firma_id' => $cargo->id, 'empleado_id' => $empId],
                    ['estado_revision' => 'Pendiente', 'hash' => 'hash']
                );
            }
        }
    }

    public function borrador(): void
    {
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
            'metasList' => empty($this->ods)
                ? collect()
                : MetaContribuye::whereIn('ods_id', $this->ods)->orderBy('ods_id')->orderBy('numero_meta')
                    ->get()->mapWithKeys(fn($m) => [$m->id => "ODS {$m->ods_id} – Meta {$m->numero_meta}: {$m->descripcion}"]),
            'empleados' => Empleado::where('user_id', '!=', auth()->id())->orderBy('nombre_completo')->pluck('nombre_completo', 'id'),
            'empleadosModal' => $empleadosModal,
            'responsablesOptions' => $this->responsableOptions($record),
            'internacionales' => IntegranteInternacional::orderBy('nombre_completo')->get()->mapWithKeys(fn($i) => [$i->id => "{$i->nombre_completo} ({$i->pais})"]),
            'tiposParticipacionEstudiante' => $this->tipoParticipacionEstudianteOpciones,
            'asignaturas' => Asignatura::orderBy('codigo')->orderBy('nombre')->get()->mapWithKeys(fn($a) => [$a->id => trim("{$a->codigo} - {$a->nombre}", ' -')]),
            'periodosAcademicos' => PeriodoAcademico::orderBy('nombre')->pluck('nombre', 'id'),
            'departamentosGeo' => \App\Models\Demografia\Departamento::orderBy('nombre')->pluck('nombre', 'id'),
            'municipiosGeo' => empty($this->departamento_geo)
                ? collect()
                : \App\Models\Demografia\Municipio::whereIn('departamento_id', $this->departamento_geo)->orderBy('nombre')->pluck('nombre', 'id'),
            'firmantesOpts' => $firmantesOpts,
            'record' => $record,
            'coordNombre' => auth()->user()->empleado?->nombre_completo ?? auth()->user()->name,
        ]);
    }
}
