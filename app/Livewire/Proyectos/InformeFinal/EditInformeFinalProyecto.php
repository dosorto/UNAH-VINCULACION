<?php

namespace App\Livewire\Proyectos\InformeFinal;

use App\Models\InformeFinal\InformeFinalProyecto;
use App\Models\Proyecto\MetaContribuye;
use App\Models\Proyecto\Od;
use App\Models\Proyecto\Proyecto;
use App\Services\InformeFinal\InformeFinalProyectoInitializer;
use App\Services\InformeFinal\InformeFinalProyectoValidator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class EditInformeFinalProyecto extends Component
{
    use WithFileUploads;

    private const TIPO_ACCION_FORM_DVUS_001 = 'DESARROLLO_LOCAL_REGIONAL';

    public Proyecto $proyecto;
    public InformeFinalProyecto $informe;
    public int $currentStep = 1;
    public array $general = [];
    public array $beneficiarios = [];
    public array $equipo = [];
    public array $cooperacion = [];
    public array $estudiantes = [];
    public array $voluntarios = [];
    public array $contrapartes = [];
    public array $resultados = [];
    public array $actividades = [];
    public array $accionesNoEjecutadas = [];
    public array $accionesEmergentes = [];
    public array $ods = [];
    public array $presupuesto = [];
    public array $anexos = [];
    public array $anexoArchivos = [];
    public string $mensaje = '';

    public function mount(Proyecto $proyecto, InformeFinalProyectoInitializer $initializer): void
    {
        abort_unless($proyecto->tipoAccion?->codigo === self::TIPO_ACCION_FORM_DVUS_001, 404);
        abort_unless($this->canManage($proyecto), 403);

        $this->proyecto = $proyecto;
        $this->informe = $initializer->initialize($proyecto, auth()->id());
        $this->cargarFormulario();
    }

    public function goToStep(int $step): void
    {
        $this->currentStep = max(1, min(8, $step));
    }

    public function siguiente(): void { $this->currentStep = min(8, $this->currentStep + 1); }
    public function anterior(): void { $this->currentStep = max(1, $this->currentStep - 1); }

    public function agregarFila(string $grupo): void
    {
        $this->authorizeSensitive();
        $defaults = [
            'cooperacion' => ['nombre'=>'','pasaporte'=>'','correo'=>'','pais'=>'','universidad'=>'','horas_dedicadas'=>0],
            'estudiantes' => ['estudiante_id'=>null,'nombre'=>'','sexo'=>'','numero_cuenta'=>'','carrera'=>'','tipo_participacion'=>'practica_asignatura','horas_dedicadas'=>0,'cantidad'=>1],
            'voluntarios' => ['nombre'=>'','sexo'=>'','identidad'=>'','departamento'=>'','tipo'=>'egresado','horas_dedicadas'=>0],
            'contrapartes' => ['existe_apoyo'=>true,'nombre'=>'','tipo'=>'sociedad_civil','contacto'=>'','correo'=>'','cargo'=>'','telefono'=>'','tipo_instrumento'=>null,'compromisos_asumidos'=>'','compromisos_cumplidos'=>'','territorio'=>'','aporte_monetario'=>0,'aporte_especie'=>0,'documento_respaldo'=>''],
            'resultados' => ['objetivo_especifico'=>'','resultado_planificado'=>'','indicador_propuesto'=>'','meta_numerica'=>null,'unidad_medida'=>'','valor_alcanzado'=>null,'porcentaje_cumplimiento'=>0,'estado'=>'no_alcanzado','producto_logrado'=>'','observaciones'=>''],
            'actividades' => ['actividad_planificada'=>'','actividad_realizada'=>'','responsable'=>'','fecha_inicial'=>null,'fecha_final'=>null,'horas_dedicadas'=>0,'medio_verificacion'=>'','estado'=>'no_ejecutada','origen'=>'emergente'],
            'accionesNoEjecutadas' => ['resultado_previsto'=>'','actividad_planificada'=>'','explicacion'=>'','afectacion_proyecto'=>'','responsable'=>'','impacto'=>'medio'],
            'accionesEmergentes' => ['producto_logrado'=>'','actividad_realizada'=>'','justificacion'=>'','responsables'=>'','fecha'=>null,'horas'=>0,'informe_final_resultado_id'=>null],
            'ods' => ['ods_id'=>'','meta_contribuye_id'=>null,'meta_ods'=>'','descripcion_aporte'=>'','evidencia'=>'','nivel_contribucion'=>'directa'],
            'presupuesto' => ['fuente'=>'UNAH','concepto'=>'otros','unidad'=>'','cantidad'=>0,'costo_unitario'=>0,'origen_fondos'=>'','informe_final_contraparte_id'=>null],
            'anexos' => ['tipo'=>'otros','descripcion'=>'','archivo'=>null,'enlace'=>'','fecha'=>null,'informe_final_resultado_id'=>null,'informe_final_actividad_id'=>null,'orden'=>count($this->anexos)+1],
        ];
        abort_unless(array_key_exists($grupo, $defaults), 404);
        $this->{$grupo}[] = $defaults[$grupo];
    }

    public function quitarFila(string $grupo, int $indice): void
    {
        $this->authorizeSensitive();
        abort_unless(property_exists($this, $grupo), 404);
        unset($this->{$grupo}[$indice]);
        $this->{$grupo} = array_values($this->{$grupo});
    }

    public function guardarBorrador(): void
    {
        $this->authorizeSensitive();
        $this->validate($this->draftRules());
        $this->persistir();
        $this->mensaje = 'Borrador guardado correctamente.';
    }

    public function marcarCompleto(InformeFinalProyectoValidator $validator): void
    {
        $this->authorizeSensitive();
        $this->validate($this->draftRules());
        $this->persistir();
        $this->informe->refresh();
        $validator->validateForCompletion($this->informe);
        $this->informe->update(['estado' => 'COMPLETO', 'updated_by' => auth()->id()]);
        $this->general['estado'] = 'COMPLETO';
        $this->mensaje = 'El INF-001 quedó marcado como completo. No se inició ningún flujo ni firma.';
    }

    public function getTotalesBeneficiariosProperty(): array
    {
        $sum = fn (array $campos) => collect($campos)->sum(fn ($campo) => (int) ($this->beneficiarios[$campo] ?? 0));
        return [
            'sexo' => $sum(['hombres','mujeres']),
            'edad' => $sum(['edad_0_10','edad_11_18','edad_19_25','edad_26_35','edad_36_50','edad_51_65','edad_66_80','edad_81_mas']),
            'etnia' => $sum(['indigena_hombres','indigena_mujeres','afrodescendiente_hombres','afrodescendiente_mujeres','mestizo_hombres','mestizo_mujeres']),
        ];
    }

    public function getTotalesPresupuestoProperty(): array
    {
        $rowsUnah = collect($this->presupuesto)->where('fuente', 'UNAH');
        $cost = fn ($fila) => (float) ($fila['cantidad'] ?? 0) * (float) ($fila['costo_unitario'] ?? 0);
        $baseRows = $rowsUnah->reject(fn ($fila) => str_contains(mb_strtolower((string) ($fila['concepto'] ?? '')), 'infraestructura') || str_contains(mb_strtolower((string) ($fila['concepto'] ?? '')), 'servicio'));
        $subtotal = $baseRows->sum($cost);
        $infraRows = $rowsUnah->filter(fn ($fila) => str_contains(mb_strtolower((string) ($fila['concepto'] ?? '')), 'infraestructura'));
        $serviceRows = $rowsUnah->filter(fn ($fila) => str_contains(mb_strtolower((string) ($fila['concepto'] ?? '')), 'servicio'));
        $infraestructura = $infraRows->isNotEmpty() ? $infraRows->sum($cost) : $subtotal * .03;
        $servicios = $serviceRows->isNotEmpty() ? $serviceRows->sum($cost) : $subtotal * .03;
        $unah = $subtotal + $infraestructura + $servicios;
        $total = fn (string $fuente) => collect($this->presupuesto)->where('fuente', $fuente)->sum($cost);
        $contraparte = $total('CONTRAPARTE');
        $ejecucion = $unah + $contraparte + (float) ($this->general['aporte_beneficiarios'] ?? 0) + (float) ($this->general['otros_aportes'] ?? 0);
        $planificado = (float) ($this->general['presupuesto_planificado'] ?? 0);
        return ['subtotal'=>$subtotal,'infraestructura'=>$infraestructura,'servicios'=>$servicios,'unah'=>$unah,'contraparte'=>$contraparte,'ejecucion'=>$ejecucion,'porcentaje'=>$planificado > 0 ? round($ejecucion/$planificado*100, 2) : 0];
    }

    public function getTotalesParticipacionProperty(): array
    {
        $students = collect($this->estudiantes);
        $volunteers = collect($this->voluntarios);
        return [
            'estudiantes' => $students->sum(fn ($row) => (int) ($row['cantidad'] ?? 1)),
            'estudiantes_hombres' => $students->filter(fn ($row) => mb_strtolower((string) ($row['sexo'] ?? '')) === 'masculino')->sum(fn ($row) => (int) ($row['cantidad'] ?? 1)),
            'estudiantes_mujeres' => $students->filter(fn ($row) => mb_strtolower((string) ($row['sexo'] ?? '')) === 'femenino')->sum(fn ($row) => (int) ($row['cantidad'] ?? 1)),
            'horas_estudiantes' => $students->sum(fn ($row) => (float) ($row['horas_dedicadas'] ?? 0)),
            'voluntarios' => $volunteers->count(),
            'voluntarios_hombres' => $volunteers->where('sexo', 'Masculino')->count(),
            'voluntarios_mujeres' => $volunteers->where('sexo', 'Femenino')->count(),
            'horas_voluntarios' => $volunteers->sum(fn ($row) => (float) ($row['horas_dedicadas'] ?? 0)),
        ];
    }

    public function getPorcentajesValoracionProperty(): array
    {
        $muestra = (int) ($this->general['valoracion_muestra'] ?? 0);
        return collect(['excelente','muy_buena','regular','mala'])->mapWithKeys(fn ($tipo) => [$tipo => $muestra > 0 ? round((int) ($this->general['valoracion_'.$tipo] ?? 0) / $muestra * 100, 2) : 0])->all();
    }

    public function render(): View
    {
        return view('livewire.proyectos.informe-final.edit-informe-final-proyecto', [
            'odsCatalogo' => Od::orderBy('nombre')->get(),
            'metasCatalogo' => MetaContribuye::orderBy('ods_id')->orderBy('numero_meta')->get(),
        ]);
    }

    private function cargarFormulario(): void
    {
        $this->informe->load(['beneficiarios','equipoDocente','cooperacion','estudiantes','voluntarios','contrapartes','resultados','actividades','accionesNoEjecutadas','accionesEmergentes','ods','presupuestoDetalles','anexos']);
        $date = fn ($value) => $value?->format('Y-m-d');
        $this->general = Arr::only($this->informe->toArray(), [
            'numero_registro','nombre_proyecto','facultad_centro','unidad_academica','departamento_academico','carrera','programa_vinculacion','linea_investigacion','modalidad','ejes_prioritarios','categoria','pais','region','departamento_territorial','municipio','aldea_ciudad','caserio','objetivo_general','dificultades','acciones_dificultades','lecciones_aprendidas','buenas_practicas','problema_inicial','transformacion_lograda','mecanismos_sostenibilidad','acciones_contraparte_sostenibilidad','desafios','respuesta_reforma_universitaria','recomendaciones','bibliografia','valoracion_total_beneficiarios','valoracion_muestra','valoracion_excelente','valoracion_muy_buena','valoracion_regular','valoracion_mala','presupuesto_planificado','aporte_beneficiarios','otros_aportes','observaciones_finales','confirmacion_veracidad','estado',
        ]);
        $this->general['fecha_registro'] = $date($this->informe->fecha_registro);
        $this->general['fecha_inicio'] = $date($this->informe->fecha_inicio);
        $this->general['fecha_finalizacion'] = $date($this->informe->fecha_finalizacion);
        $this->general['fecha_cierre'] = $date($this->informe->fecha_cierre);
        $this->beneficiarios = Arr::except($this->informe->beneficiarios?->toArray() ?? [], ['id','informe_final_proyecto_id','created_at','updated_at']);
        foreach (['equipo'=>'equipoDocente','cooperacion'=>'cooperacion','estudiantes'=>'estudiantes','voluntarios'=>'voluntarios','contrapartes'=>'contrapartes','resultados'=>'resultados','actividades'=>'actividades','accionesNoEjecutadas'=>'accionesNoEjecutadas','accionesEmergentes'=>'accionesEmergentes','ods'=>'ods','presupuesto'=>'presupuestoDetalles','anexos'=>'anexos'] as $property => $relation) {
            $this->{$property} = $this->informe->{$relation}->toArray();
        }
        foreach ([['actividades',['fecha_inicial','fecha_final']], ['accionesEmergentes',['fecha']], ['anexos',['fecha']]] as [$property,$fields]) {
            foreach ($this->{$property} as &$row) {
                foreach ($fields as $field) {
                    $row[$field] = filled($row[$field] ?? null) ? Carbon::parse($row[$field])->format('Y-m-d') : null;
                }
            }
            unset($row);
        }
    }

    private function persistir(): void
    {
        DB::transaction(function () {
            $mainFields = array_keys(Arr::except($this->general, ['estado','fecha_registro']));
            $this->informe->update(Arr::only($this->general, $mainFields) + ['updated_by' => auth()->id()]);
            $this->informe->beneficiarios()->updateOrCreate([], Arr::except($this->beneficiarios, ['id','informe_final_proyecto_id','created_at','updated_at']));
            $this->syncRows('equipoDocente', $this->equipo, ['empleado_id','nombre','numero_empleado','correo','categoria','departamento','sexo','horas_dedicadas','tipo_participacion','es_coordinador']);
            $this->syncRows('cooperacion', $this->cooperacion, ['nombre','pasaporte','correo','pais','universidad','horas_dedicadas']);
            $this->syncRows('estudiantes', $this->estudiantes, ['estudiante_id','nombre','sexo','numero_cuenta','carrera','tipo_participacion','horas_dedicadas','cantidad']);
            $this->syncRows('voluntarios', $this->voluntarios, ['nombre','sexo','identidad','departamento','tipo','horas_dedicadas']);
            $this->syncRows('contrapartes', $this->contrapartes, ['entidad_contraparte_id','existe_apoyo','nombre','tipo','contacto','correo','cargo','telefono','tipo_instrumento','compromisos_asumidos','compromisos_cumplidos','territorio','aporte_monetario','aporte_especie','documento_respaldo']);
            foreach ($this->resultados as &$resultado) {
                if (is_numeric($resultado['meta_numerica'] ?? null) && (float) $resultado['meta_numerica'] > 0 && is_numeric($resultado['valor_alcanzado'] ?? null)) {
                    $resultado['porcentaje_cumplimiento'] = min(100, round((float) $resultado['valor_alcanzado'] / (float) $resultado['meta_numerica'] * 100, 2));
                    if (($resultado['estado'] ?? null) !== 'no_aplica') {
                        $resultado['estado'] = $resultado['porcentaje_cumplimiento'] >= 100 ? 'alcanzado' : ($resultado['porcentaje_cumplimiento'] > 0 ? 'parcialmente_alcanzado' : 'no_alcanzado');
                    }
                }
            }
            unset($resultado);
            $this->syncRows('resultados', $this->resultados, ['resultado_esperado_id','objetivo_especifico','resultado_planificado','indicador_propuesto','meta_numerica','unidad_medida','valor_alcanzado','porcentaje_cumplimiento','estado','producto_logrado','observaciones']);
            $this->syncRows('actividades', $this->actividades, ['actividad_id','actividad_planificada','actividad_realizada','responsable','fecha_inicial','fecha_final','horas_dedicadas','medio_verificacion','estado','origen']);
            $this->syncRows('accionesNoEjecutadas', $this->accionesNoEjecutadas, ['resultado_previsto','actividad_planificada','explicacion','afectacion_proyecto','responsable','impacto']);
            $this->syncRows('accionesEmergentes', $this->accionesEmergentes, ['informe_final_resultado_id','producto_logrado','actividad_realizada','justificacion','responsables','fecha','horas']);
            $this->syncRows('ods', $this->ods, ['ods_id','meta_contribuye_id','meta_ods','descripcion_aporte','evidencia','nivel_contribucion']);
            $this->syncRows('presupuestoDetalles', $this->presupuesto, ['informe_final_contraparte_id','fuente','concepto','unidad','cantidad','costo_unitario','origen_fondos']);
            foreach ($this->anexoArchivos as $index => $file) {
                if ($file) {
                    $this->anexos[$index]['archivo'] = $file->store('informes-finales/inf-001', 'public');
                }
            }
            $this->syncRows('anexos', $this->anexos, ['informe_final_resultado_id','informe_final_actividad_id','tipo','descripcion','archivo','enlace','fecha','orden']);
        }, 3);
        $this->informe->refresh();
        $this->cargarFormulario();
    }

    private function syncRows(string $relation, array $rows, array $fields): void
    {
        $ids = [];
        foreach ($rows as $row) {
            $data = Arr::only($row, $fields);
            $id = isset($row['id']) ? (int) $row['id'] : null;
            $record = $id ? $this->informe->{$relation}()->whereKey($id)->first() : null;
            $record ? $record->update($data) : $record = $this->informe->{$relation}()->create($data);
            $ids[] = $record->id;
        }
        $query = $this->informe->{$relation}();
        $ids ? $query->whereNotIn('id', $ids)->delete() : $query->delete();
    }

    private function draftRules(): array
    {
        $nonNegative = ['nullable','numeric','min:0'];
        return [
            'general.nombre_proyecto' => ['nullable','string','max:255'],
            'general.fecha_inicio' => ['nullable','date'],
            'general.fecha_finalizacion' => ['nullable','date','after_or_equal:general.fecha_inicio'],
            'general.fecha_cierre' => ['nullable','date'],
            'general.valoracion_total_beneficiarios' => ['integer','min:0'],
            'general.valoracion_muestra' => ['integer','min:0','lte:general.valoracion_total_beneficiarios'],
            'general.valoracion_excelente' => ['integer','min:0'],
            'general.valoracion_muy_buena' => ['integer','min:0'],
            'general.valoracion_regular' => ['integer','min:0'],
            'general.valoracion_mala' => ['integer','min:0'],
            'general.presupuesto_planificado' => $nonNegative,
            'general.aporte_beneficiarios' => $nonNegative,
            'general.otros_aportes' => $nonNegative,
            'general.confirmacion_veracidad' => ['boolean'],
            'beneficiarios.*' => ['nullable','integer','min:0'],
            'voluntarios.*.tipo' => [Rule::in(['profesor_hora','pas','profesor_permanente','egresado'])],
            'contrapartes.*.tipo' => [Rule::in(['gobierno_nacional','gobierno_municipal','ong','sociedad_civil','sector_privado','internacional'])],
            'resultados.*.porcentaje_cumplimiento' => ['numeric','between:0,100'],
            'resultados.*.estado' => [Rule::in(['alcanzado','parcialmente_alcanzado','no_alcanzado','no_aplica'])],
            'actividades.*.estado' => [Rule::in(['ejecutada','parcial','no_ejecutada'])],
            'actividades.*.origen' => [Rule::in(['planificada','emergente'])],
            'ods.*.nivel_contribucion' => [Rule::in(['directa','indirecta'])],
            'presupuesto.*.fuente' => [Rule::in(['UNAH','CONTRAPARTE'])],
            'anexoArchivos.*' => ['nullable','file','max:20480'],
        ];
    }

    private function authorizeSensitive(): void
    {
        if (! $this->canManage($this->proyecto->fresh())) {
            throw new AuthorizationException('No está autorizado para modificar este informe final.');
        }
    }

    private function canManage(Proyecto $proyecto): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        if ($user->hasRole('admin')) {
            return true;
        }
        $empleadoId = $user->empleado?->id;
        return $empleadoId && $proyecto->coordinador_proyecto()->where('empleado_id', $empleadoId)->exists();
    }
}
