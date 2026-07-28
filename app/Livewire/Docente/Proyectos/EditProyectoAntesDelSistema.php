<?php

namespace App\Livewire\Docente\Proyectos;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Support\Notification;
use App\Models\Proyecto\Proyecto;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\Modalidad;
use App\Models\Proyecto\Categoria;
use App\Models\Proyecto\EjesPrioritariosUnah;
use App\Models\Proyecto\Od;
use App\Models\Proyecto\MetaContribuye;
use App\Models\Proyecto\IntegranteInternacional;
use App\Models\Proyecto\EntidadContraparte;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\UnidadAcademica\DepartamentoAcademico;
use App\Models\UnidadAcademica\Carrera;
use App\Models\Estado\TipoEstado;
use Illuminate\Contracts\View\View;

class EditProyectoAntesDelSistema extends Component
{
    use WithFileUploads;

    public int $currentStep = 1;
    public Proyecto $record;

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

    // Step 3
    public array $entidad_contraparte = [];

    // Step 4
    public array $actividades = [];

    // Step 5
    public string $resumen = '';
    public string $descripcion_participantes = '';
    public string $definicion_problema = '';
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
    public string $alineamiento_reforma = '';
    public string $impacto_deseado = '';
    public string $metodologia = '';
    public string $bibliografia = '';

    // Step 6
    public string $objetivo_general = '';
    public array $objetivosEspecificos = [];

    // Step 7
    public array $aporte_institucional = [];
    public float $aporte_contraparte = 0;
    public float $aporte_internacionales = 0;
    public float $aporte_otras_universidades = 0;
    public float $aporte_comunidad = 0;
    public float $otros_aportes = 0;

    // Step 8
    public $newAnexo;

    public function mount(Proyecto $proyecto): void
    {
        $estadosPermitidos = TipoEstado::whereIn('nombre', ['PendienteInformacion', 'Finalizado'])->pluck('id');
        $estadoActual = $proyecto->tipo_estado;

        if (!$estadoActual || !$estadosPermitidos->contains($estadoActual->id)) {
            Notification::make()->title('Error')->body('Este proyecto no está disponible para edición.')->danger()->send();
            redirect()->route('proyectosAntesDelSistema');
            return;
        }

        $esParticipante = $proyecto->docentes_proyecto()
            ->where('empleado_id', auth()->user()->empleado->id)->exists();

        if (!$esParticipante) {
            Notification::make()->title('Error')->body('No tienes permisos para editar este proyecto.')->danger()->send();
            redirect()->route('proyectosAntesDelSistema');
            return;
        }

        $this->record = $proyecto;
        $this->loadFromRecord($proyecto);
        $this->initDefaults();
    }

    protected function loadFromRecord(Proyecto $record): void
    {
        $record->load([
            'objetivosEspecificos.resultados',
            'estudiante_proyecto',
            'entidad_contraparte_proyecto.entidadContraparte', 'entidad_contraparte_proyecto.instrumentoFormalizacion',
            'actividades.empleados',
            'presupuesto',
            'ods',
            'anexos',
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
        $this->fecha_inicio = $record->fecha_inicio?->format('Y-m-d') ?? '';
        $this->fecha_finalizacion = $record->fecha_finalizacion?->format('Y-m-d') ?? '';

        $this->empleado_proyecto = $record->empleado_proyecto->map(fn($ep) => [
            'empleado_id' => $ep->empleado_id,
            'rol' => $ep->rol ?? 'Integrante',
            'nombre' => $ep->empleado?->nombre_completo ?? '',
        ])->toArray();

        $this->estudiante_proyecto = $record->estudiante_proyecto->map(fn($ep) => [
            'tipo_participacion_estudiante' => $ep->tipo_participacion_estudiante,
            'asignatura_id' => $ep->asignatura_id,
            'periodo_academico_id' => $ep->periodo_academico_id,
            'cantidad_estudiantes_hombres' => $ep->cantidad_estudiantes_hombres ?? 0,
            'cantidad_estudiantes_mujeres' => $ep->cantidad_estudiantes_mujeres ?? 0,
            'total_estudiantes' => $ep->total_estudiantes ?? 0,
        ])->toArray();

        $this->integrante_internacional_proyecto = $record->integrante_internacional_proyecto->map(fn($ip) => [
            'integrante_internacional_id' => $ip->integrante_internacional_id,
            'nombre' => $ip->integranteInternacional?->nombre_completo ?? '',
        ])->toArray();

        $this->entidad_contraparte = $record->entidad_contraparte_proyecto()->with('entidadContraparte')->with('instrumentoFormalizacion')->get()->map(fn($pivot) => [
            'entidad_contraparte_id' => $pivot->entidad_contraparte_id,
            'rtn' => $pivot->entidadContraparte?->rtn ?? '',
            'nombre' => $pivot->entidadContraparte?->nombre ?? '',
            'tipo_entidad' => $pivot->entidadContraparte?->tipo_entidad ?? '',
            'nombre_contacto' => $pivot->nombre_contacto ?? $pivot->entidadContraparte?->nombre_contacto ?? '',
            'cargo_contacto' => $pivot->cargo_contacto ?? $pivot->entidadContraparte?->cargo_contacto ?? '',
            'telefono' => $pivot->telefono ?? $pivot->entidadContraparte?->telefono ?? '',
            'correo' => $pivot->correo ?? $pivot->entidadContraparte?->correo ?? '',
            'descripcion_acuerdos' => $pivot->descripcion_acuerdos ?? '',
            'instrumento_formalizacion' => ($pivot->instrumentoFormalizacion ?? collect())->map(fn($i) => [
                'tipo_documento' => $i->tipo_documento,
                'documento_url' => $i->documento_url,
            ])->toArray(),
        ])->toArray();

        $this->actividades = $record->actividades->map(fn($a) => [
            'descripcion' => $a->descripcion,
            'empleados' => $a->empleados->pluck('id')->toArray(),
            'fecha_inicio' => $a->fecha_inicio?->format('Y-m-d') ?? '',
            'fecha_finalizacion' => $a->fecha_finalizacion?->format('Y-m-d') ?? '',
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
                'plazo' => $r->plazo,
            ])->toArray(),
        ])->toArray();

        if ($record->aporteInstitucional->isNotEmpty()) {
            $this->aporte_institucional = $record->aporteInstitucional->map(fn($a) => [
                'concepto' => $a->concepto, 'concepto_label' => $a->concepto ?? '',
                'unidad' => $a->unidad, 'unidad_label' => $a->unidad ?? '',
                'cantidad' => $a->cantidad ?? 0, 'costo_unitario' => $a->costo_unitario ?? 0, 'costo_total' => $a->costo_total ?? 0,
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
        if (empty($this->aporte_institucional)) {
            $this->aporte_institucional = [
                ['concepto' => 'horas_trabajo_docentes', 'concepto_label' => 'a) Horas de trabajo docentes', 'unidad' => 'hra_profes', 'unidad_label' => 'Hra/profes', 'cantidad' => 0, 'costo_unitario' => 0, 'costo_total' => 0],
                ['concepto' => 'horas_trabajo_estudiantes', 'concepto_label' => 'b) Horas de trabajo estudiantes', 'unidad' => 'hra_estud', 'unidad_label' => 'Hra/estud', 'cantidad' => 0, 'costo_unitario' => 0, 'costo_total' => 0],
                ['concepto' => 'gastos_movilizacion', 'concepto_label' => 'c) Gastos de movilización', 'unidad' => 'global', 'unidad_label' => 'Global', 'cantidad' => 0, 'costo_unitario' => 0, 'costo_total' => 0],
                ['concepto' => 'utiles_materiales_oficina', 'concepto_label' => 'd) Útiles y materiales de oficina', 'unidad' => 'global', 'unidad_label' => 'Global', 'cantidad' => 0, 'costo_unitario' => 0, 'costo_total' => 0],
                ['concepto' => 'gastos_impresion', 'concepto_label' => 'e) Gastos de impresión', 'unidad' => 'global', 'unidad_label' => 'Global', 'cantidad' => 0, 'costo_unitario' => 0, 'costo_total' => 0],
            ];
        }
        if (empty($this->objetivosEspecificos)) {
            $this->objetivosEspecificos = [['descripcion' => '', 'resultados' => [['nombre_resultado' => '', 'nombre_indicador' => '', 'nombre_medio_verificacion' => '', 'plazo' => '']]]];
        }
        if (empty($this->entidad_contraparte)) {
            $this->entidad_contraparte = [['entidad_contraparte_id' => null, 'rtn' => '', 'nombre' => '', 'tipo_entidad' => '', 'nombre_contacto' => '', 'cargo_contacto' => '', 'telefono' => '', 'correo' => '', 'descripcion_acuerdos' => '', 'instrumento_formalizacion' => []]];
        }
        if (empty($this->actividades)) {
            $this->actividades = [['descripcion' => '', 'empleados' => [], 'fecha_inicio' => '', 'fecha_finalizacion' => '', 'horas' => '']];
        }
    }

    public function nextStep(): void
    {
        $this->saveCurrentStep();
        if ($this->getErrorBag()->isEmpty() && $this->currentStep < 8) {
            $this->currentStep++;
        }
    }

    public function prevStep(): void
    {
        if ($this->currentStep > 1) $this->currentStep--;
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
            default => null,
        };
    }

    protected function saveStep1(): void
    {
        $this->record->update([
            'nombre_proyecto' => $this->nombre_proyecto,
            'modalidad_id' => $this->modalidad_id,
            'fecha_inicio' => $this->fecha_inicio ?: null,
            'fecha_finalizacion' => $this->fecha_finalizacion ?: null,
            'programa_pertenece' => $this->programa_pertenece,
            'lineas_investigacion_academica' => $this->lineas_investigacion_academica,
        ]);
        $this->record->categoria()->sync($this->categoria);
        $this->record->ejes_prioritarios_unah()->sync($this->ejes_prioritarios_unah);
        $this->record->facultades_centros()->sync($this->facultades_centros);
        $this->record->departamentos_academicos()->sync($this->departamentos_academicos ?? []);
        $this->record->carreras()->sync($this->carreras ?? []);
        $this->record->ods()->sync($this->ods);
        $this->record->metasContribuye()->sync($this->metasContribuye ?? []);
    }

    protected function saveStep2(): void
    {
        $coordId = auth()->user()->empleado->id;
        foreach ($this->empleado_proyecto as $item) {
            if (!empty($item['empleado_id']) && $item['empleado_id'] != $coordId) {
                $this->record->empleado_proyecto()->firstOrCreate(['empleado_id' => $item['empleado_id']], ['rol' => 'Integrante']);
            }
        }
        $this->record->estudiante_proyecto()->delete();
        foreach ($this->estudiante_proyecto as $item) {
            if (!empty($item['tipo_participacion_estudiante'])) {
                $this->record->estudiante_proyecto()->create([
                    'tipo_participacion_estudiante' => $item['tipo_participacion_estudiante'],
                    'asignatura_id' => $item['asignatura_id'] ?? null,
                    'periodo_academico_id' => $item['periodo_academico_id'] ?? null,
                    'cantidad_estudiantes_hombres' => $item['cantidad_estudiantes_hombres'] ?? 0,
                    'cantidad_estudiantes_mujeres' => $item['cantidad_estudiantes_mujeres'] ?? 0,
                    'total_estudiantes' => ($item['cantidad_estudiantes_hombres'] ?? 0) + ($item['cantidad_estudiantes_mujeres'] ?? 0),
                ]);
            }
        }
        foreach ($this->integrante_internacional_proyecto as $item) {
            if (!empty($item['integrante_internacional_id'])) {
                $this->record->integrante_internacional_proyecto()->firstOrCreate(
                    ['integrante_internacional_id' => $item['integrante_internacional_id']],
                    []
                );
            }
        }
    }

    protected function saveStep3(): void
    {
        $this->record->entidad_contraparte_proyecto()->each(fn($pivot) => $pivot->instrumentoFormalizacion()->delete());
        $this->record->entidad_contraparte_proyecto()->delete();

        foreach ($this->entidad_contraparte as $item) {
            if (!empty($item['nombre'])) {
                $catalogoId = $item['entidad_contraparte_id'] ?? null;
                if (!$catalogoId) {
                    $catalogo = EntidadContraparte::create([
                        'rtn' => $item['rtn'] ?? null,
                        'nombre' => $item['nombre'],
                        'tipo_entidad' => $item['tipo_entidad'] ?? '',
                    ]);
                    $catalogoId = $catalogo->id;
                }

                $pivot = $this->record->entidad_contraparte_proyecto()->create([
                    'entidad_contraparte_id' => $catalogoId,
                    'nombre' => $item['nombre'],
                    'tipo_entidad' => $item['tipo_entidad'] ?? '',
                    'rtn' => $item['rtn'] ?? null,
                    'nombre_contacto' => $item['nombre_contacto'] ?? '',
                    'cargo_contacto' => $item['cargo_contacto'] ?? '',
                    'telefono' => $item['telefono'] ?? '',
                    'correo' => $item['correo'] ?? '',
                    'descripcion_acuerdos' => $item['descripcion_acuerdos'] ?? '',
                ]);

                foreach ($item['instrumento_formalizacion'] ?? [] as $inst) {
                    if (!empty($inst['tipo_documento'])) {
                        $pivot->instrumentoFormalizacion()->create([
                            'tipo_documento' => $inst['tipo_documento'],
                            'documento_url' => $inst['documento_url'] ?? null,
                        ]);
                    }
                }
            }
        }
    }

    protected function saveStep4(): void
    {
        $this->record->actividades()->each(fn($a) => $a->empleados()->detach());
        $this->record->actividades()->delete();
        $validEmpleados = array_unique(array_merge($this->record->empleado_proyecto->pluck('empleado_id')->toArray(), [auth()->user()->empleado->id]));
        foreach ($this->actividades as $item) {
            if (!empty($item['descripcion'])) {
                $actividad = $this->record->actividades()->create([
                    'descripcion' => $item['descripcion'],
                    'fecha_inicio' => $item['fecha_inicio'] ?: null,
                    'fecha_finalizacion' => $item['fecha_finalizacion'] ?: null,
                    'horas' => $item['horas'] ?? 0,
                ]);
                $ids = array_intersect($item['empleados'] ?? [], $validEmpleados);
                if (!empty($ids)) $actividad->empleados()->sync($ids);
            }
        }
    }

    public function calcTotales(): void
    {
        $this->hombres = $this->indigenas_hombres + $this->afroamericanos_hombres + $this->mestizos_hombres;
        $this->mujeres = $this->indigenas_mujeres + $this->afroamericanos_mujeres + $this->mestizos_mujeres;
        $this->poblacion_participante = $this->hombres + $this->mujeres;
    }

    protected function saveStep5(): void
    {
        $this->calcTotales();
        $this->record->update([
            'resumen' => $this->resumen, 'descripcion_participantes' => $this->descripcion_participantes,
            'definicion_problema' => $this->definicion_problema,
            'indigenas_hombres' => $this->indigenas_hombres, 'indigenas_mujeres' => $this->indigenas_mujeres,
            'afroamericanos_hombres' => $this->afroamericanos_hombres, 'afroamericanos_mujeres' => $this->afroamericanos_mujeres,
            'mestizos_hombres' => $this->mestizos_hombres, 'mestizos_mujeres' => $this->mestizos_mujeres,
            'hombres' => $this->hombres, 'mujeres' => $this->mujeres, 'poblacion_participante' => $this->poblacion_participante,
            'pais' => $this->pais, 'region' => $this->region, 'caserio' => $this->caserio, 'aldea' => $this->aldea,
            'alineamiento_reforma' => $this->alineamiento_reforma, 'impacto_deseado' => $this->impacto_deseado,
            'metodologia' => $this->metodologia, 'bibliografia' => $this->bibliografia,
        ]);
        if (!empty($this->departamento_geo)) $this->record->departamento()->sync($this->departamento_geo);
        if (!empty($this->municipio_geo)) $this->record->municipio()->sync($this->municipio_geo);
    }

    protected function saveStep6(): void
    {
        $this->record->update(['objetivo_general' => $this->objetivo_general]);
        $this->record->objetivosEspecificos()->each(fn($obj) => $obj->resultados()->delete());
        $this->record->objetivosEspecificos()->delete();
        foreach ($this->objetivosEspecificos as $objData) {
            $obj = $this->record->objetivosEspecificos()->create(['descripcion' => $objData['descripcion']]);
            foreach ($objData['resultados'] ?? [] as $rData) {
                if (!empty($rData['nombre_resultado'])) {
                    $obj->resultados()->create([
                        'nombre_resultado' => $rData['nombre_resultado'], 'nombre_indicador' => $rData['nombre_indicador'] ?? '',
                        'nombre_medio_verificacion' => $rData['nombre_medio_verificacion'] ?? '', 'plazo' => $rData['plazo'] ?? '',
                    ]);
                }
            }
        }
    }

    protected function saveStep7(): void
    {
        $this->record->aporteInstitucional()->delete();
        foreach ($this->aporte_institucional as $item) {
            $this->record->aporteInstitucional()->create([
                'concepto' => $item['concepto'], 'unidad' => $item['unidad'],
                'cantidad' => $item['cantidad'] ?? 0, 'costo_unitario' => $item['costo_unitario'] ?? 0, 'costo_total' => $item['costo_total'] ?? 0,
            ]);
        }
        $this->record->presupuesto()->updateOrCreate([], [
            'aporte_contraparte' => $this->aporte_contraparte, 'aporte_internacionales' => $this->aporte_internacionales,
            'aporte_otras_universidades' => $this->aporte_otras_universidades,
            'aporte_comunidad' => $this->aporte_comunidad, 'otros_aportes' => $this->otros_aportes,
        ]);
    }

    public function uploadAnexo(): void
    {
        $this->validate(['newAnexo' => 'required|file|max:10240']);
        $path = $this->newAnexo->store('anexos', 'public');
        $this->record->anexos()->create(['documento_url' => $path]);
        $this->newAnexo = null;
        Notification::make()->title('Anexo subido')->success()->send();
    }

    public function deleteAnexo(int $id): void
    {
        $this->record->anexos()->where('id', $id)->delete();
    }

    // Repeater helpers
    public function addEmpleado(): void { $this->empleado_proyecto[] = ['empleado_id' => null, 'rol' => 'Integrante', 'nombre' => '']; }
    public function removeEmpleado(int $i): void { array_splice($this->empleado_proyecto, $i, 1); }
    public function addInternacional(): void { $this->integrante_internacional_proyecto[] = ['integrante_internacional_id' => null, 'nombre' => '']; }
    public function removeInternacional(int $i): void { array_splice($this->integrante_internacional_proyecto, $i, 1); }
    public function addEstudiante(): void { $this->estudiante_proyecto[] = ['tipo_participacion_estudiante' => '', 'asignatura_id' => null, 'periodo_academico_id' => null, 'cantidad_estudiantes_hombres' => 0, 'cantidad_estudiantes_mujeres' => 0, 'total_estudiantes' => 0]; }
    public function removeEstudiante(int $i): void { array_splice($this->estudiante_proyecto, $i, 1); }
    public function updateEstudianteTotal(int $i): void { $h = (int)($this->estudiante_proyecto[$i]['cantidad_estudiantes_hombres'] ?? 0); $m = (int)($this->estudiante_proyecto[$i]['cantidad_estudiantes_mujeres'] ?? 0); $this->estudiante_proyecto[$i]['total_estudiantes'] = $h + $m; }
    public function addContraparte(): void { $this->entidad_contraparte[] = ['entidad_contraparte_id' => null, 'rtn' => '', 'nombre' => '', 'tipo_entidad' => '', 'nombre_contacto' => '', 'cargo_contacto' => '', 'telefono' => '', 'correo' => '', 'descripcion_acuerdos' => '', 'instrumento_formalizacion' => []]; }
    public function removeContraparte(int $i): void { array_splice($this->entidad_contraparte, $i, 1); }
    public function addInstrumento(int $ci): void { $this->entidad_contraparte[$ci]['instrumento_formalizacion'][] = ['tipo_documento' => '', 'documento_url' => '']; }
    public function removeInstrumento(int $ci, int $ii): void { array_splice($this->entidad_contraparte[$ci]['instrumento_formalizacion'], $ii, 1); }
    public function addActividad(): void { $this->actividades[] = ['descripcion' => '', 'empleados' => [], 'fecha_inicio' => '', 'fecha_finalizacion' => '', 'horas' => '']; }
    public function removeActividad(int $i): void { array_splice($this->actividades, $i, 1); }
    public function addObjetivo(): void { $this->objetivosEspecificos[] = ['descripcion' => '', 'resultados' => [['nombre_resultado' => '', 'nombre_indicador' => '', 'nombre_medio_verificacion' => '', 'plazo' => '']]]; }
    public function removeObjetivo(int $i): void { array_splice($this->objetivosEspecificos, $i, 1); }
    public function addResultado(int $oi): void { $this->objetivosEspecificos[$oi]['resultados'][] = ['nombre_resultado' => '', 'nombre_indicador' => '', 'nombre_medio_verificacion' => '', 'plazo' => '']; }
    public function removeResultado(int $oi, int $ri): void { array_splice($this->objetivosEspecificos[$oi]['resultados'], $ri, 1); }
    public function updateAporteTotal(int $i): void { $this->aporte_institucional[$i]['costo_total'] = (float)($this->aporte_institucional[$i]['cantidad'] ?? 0) * (float)($this->aporte_institucional[$i]['costo_unitario'] ?? 0); }

    public function save(): void
    {
        try {
            $this->saveCurrentStep();
            $estadoActual = $this->record->tipo_estado;
            if ($estadoActual && $estadoActual->nombre === 'PendienteInformacion') {
                $estadoFinalizado = TipoEstado::where('nombre', 'Finalizado')->first();
                if ($estadoFinalizado) {
                    $this->record->estado_proyecto()->update([
                        'tipo_estado_id' => $estadoFinalizado->id,
                        'fecha' => now(),
                        'comentario' => 'Proyecto completado desde Proyectos Antes del Sistema',
                        'empleado_id' => auth()->user()->empleado->id,
                    ]);
                }
            }
            Notification::make()->title('¡Éxito!')->body('Proyecto actualizado exitosamente.')->success()->send();
            $this->redirect(route('proyectosAntesDelSistema'));
        } catch (\Exception $e) {
            Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
        }
    }

    public function render(): View
    {
        $centroId = auth()->user()->empleado?->centro_facultad_id;
        return view('livewire.docente.proyectos.edit-proyecto-antes-del-sistema', [
            'modalidades' => Modalidad::orderBy('nombre')->pluck('nombre', 'id'),
            'categorias' => Categoria::orderBy('nombre')->pluck('nombre', 'id'),
            'ejesPrioritarios' => EjesPrioritariosUnah::orderBy('nombre')->pluck('nombre', 'id'),
            'facultadesCentros' => FacultadCentro::orderBy('nombre')->pluck('nombre', 'id'),
            'departamentosAcademicos' => empty($this->facultades_centros) ? collect() : DepartamentoAcademico::whereIn('centro_facultad_id', $this->facultades_centros)->orderBy('nombre')->pluck('nombre', 'id'),
            'carrerasOpts' => empty($this->departamentos_academicos) ? collect() : Carrera::where(function($q) { $q->whereHas('departamentosAcademicos', fn($dq) => $dq->whereIn('departamento_academico.id', $this->departamentos_academicos))->orWhereIn('departamento_academico_id', $this->departamentos_academicos); })->orderBy('nombre')->pluck('nombre', 'id'),
            'odsList' => Od::orderBy('nombre')->pluck('nombre', 'id'),
            'metasList' => empty($this->ods) ? collect() : MetaContribuye::whereIn('ods_id', $this->ods)->orderBy('ods_id')->orderBy('numero_meta')->get()->mapWithKeys(fn($m) => [$m->id => "Meta {$m->numero_meta}: {$m->descripcion}"]),
            'empleados' => Empleado::where('user_id', '!=', auth()->id())->orderBy('nombre_completo')->pluck('nombre_completo', 'id'),
            'internacionales' => IntegranteInternacional::orderBy('nombre_completo')->get()->mapWithKeys(fn($i) => [$i->id => "{$i->nombre_completo} ({$i->pais})"]),
            'departamentosGeo' => \App\Models\Demografia\Departamento::orderBy('nombre')->pluck('nombre', 'id'),
            'municipiosGeo' => empty($this->departamento_geo) ? collect() : \App\Models\Demografia\Municipio::whereIn('departamento_id', $this->departamento_geo)->orderBy('nombre')->pluck('nombre', 'id'),
        ]);
    }
}
