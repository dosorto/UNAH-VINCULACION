<?php

namespace App\Livewire\Proyectos\InformeFinal;

use App\Models\Estudiante\Estudiante;
use App\Models\InformeFinal\InformeFinalProyecto;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\MetaContribuye;
use App\Models\Proyecto\Od;
use App\Models\Proyecto\Proyecto;
use App\Services\InformeFinal\InformeFinalProyectoValidator;
use App\Services\InformeFinal\InformeFinalProyectoWorkflowService;
use App\Services\Integraciones\IntegracionApiService;
use App\Support\InformeFinal\ParticipacionEstudiantil;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
    public array $gruposEstudiantes = [];
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
    public array $fotografiasTemporales = [];
    public array $participanteSeleccion = [];
    public bool $showEstudianteModal = false;
    public bool $showVoluntarioModal = false;
    public bool $showNoParticipacionModal = false;
    public string $tipoParticipanteNoParticipacion = '';
    public ?int $indiceParticipanteNoParticipacion = null;
    public string $estadoNoParticipacion = 'no_participo';
    public string $observacionNoParticipacion = '';
    public string $estudianteBusquedaCuenta = '';
    public string $voluntarioBusquedaNumero = '';
    public ?array $estudianteEncontrado = null;
    public string $estudianteOrigen = 'PROYECTO';
    public bool $mostrarRegistroManual = false;
    public ?array $voluntarioEncontrado = null;
    public ?int $editEstudianteIndex = null;
    public ?int $grupoEstudianteSeleccionadoId = null;
    public string $tipoParticipacionSinPlanificacion = 'voluntariado';
    public ?int $editVoluntarioIndex = null;
    public array $estudianteModal = ['tipo_participacion'=>'practica_asignatura','horas_dedicadas'=>0];
    public array $estudianteManual = ['nombres'=>'','apellidos'=>'','numero_cuenta'=>'','sexo'=>'','carrera'=>'','correo'=>'','tipo_participacion'=>'practica_asignatura','horas_dedicadas'=>0];
    public array $voluntarioModal = ['empleado_id'=>null,'nombre'=>'','sexo'=>'','identidad'=>'','departamento'=>'','tipo'=>'egresado','horas_dedicadas'=>0];
    public string $mensaje = '';
    public string $estadoGuardado = 'guardado';
    private bool $autoGuardando = false;

    public function mount(Proyecto $proyecto, InformeFinalProyectoWorkflowService $workflow): void
    {
        abort_unless($proyecto->tipoAccion?->codigo === self::TIPO_ACCION_FORM_DVUS_001, 404);

        $this->proyecto = $proyecto;
        $existente = $proyecto->informeFinalInf001()->first();
        $this->informe = $existente
            ? tap($existente, fn (InformeFinalProyecto $informe) => abort_unless($workflow->puedeContinuarInformeFinal($informe, auth()->user()), 403))
            : $workflow->crearInformeFinal($proyecto, auth()->user());
        $this->cargarFormulario();
    }

    public function goToStep(int $step): void
    {
        $step = max(1, min(8, $step));
        if ($step === $this->currentStep) {
            return;
        }
        if ($step > $this->currentStep) {
            $this->authorizeSensitive();
            $this->validateCurrentStep();
            $this->persistir();
        }
        $this->currentStep = $step;
    }

    public function siguiente(): void
    {
        $this->authorizeSensitive();
        $this->validateCurrentStep();
        $this->persistir();
        $this->currentStep = min(8, $this->currentStep + 1);
    }

    public function anterior(): void { $this->currentStep = max(1, $this->currentStep - 1); }

    public function openEstudianteModal(?int $index = null, ?int $grupoId = null): void
    {
        $this->resetErrorBag();
        $this->editEstudianteIndex = $index;
        $this->estudianteBusquedaCuenta = '';
        $this->estudianteEncontrado = null;
        $this->estudianteOrigen = 'PROYECTO';
        $this->mostrarRegistroManual = false;
        $this->estudianteModal = ['horas_dedicadas'=>0];
        $this->estudianteManual = ['nombres'=>'','apellidos'=>'','numero_cuenta'=>'','sexo'=>'','carrera'=>'','correo'=>'','horas_dedicadas'=>0];
        if ($index !== null && isset($this->estudiantes[$index])) {
            $row = $this->estudiantes[$index];
            $grupoId = (int) ($row['informe_final_grupo_estudiante_id'] ?? 0);
            if (! empty($row['estudiante_id'])) {
                $this->estudianteEncontrado = Arr::only($row, ['estudiante_id','nombre','sexo','numero_cuenta','carrera','correo']);
                $this->estudianteModal = Arr::only($row, ['horas_dedicadas']);
            } else {
                $this->mostrarRegistroManual = true;
                $this->estudianteManual = array_replace($this->estudianteManual, [
                    'nombres'=>$row['nombre'] ?? '', 'numero_cuenta'=>$row['numero_cuenta'] ?? '',
                    'sexo'=>$row['sexo'] ?? '', 'carrera'=>$row['carrera'] ?? '', 'correo'=>$row['correo'] ?? '',
                    'horas_dedicadas'=>$row['horas_dedicadas'] ?? 0,
                ]);
            }
        }
        abort_unless(collect($this->gruposEstudiantes)->contains(fn ($grupo) => (int) $grupo['id'] === (int) $grupoId), 404);
        $this->grupoEstudianteSeleccionadoId = (int) $grupoId;
        $this->showEstudianteModal = true;
    }

    public function openEstudianteSinPlanificacionModal(): void
    {
        $this->authorizeSensitive();
        $this->validate(['tipoParticipacionSinPlanificacion' => [Rule::in(['practica_asignatura','pps_servicio_social','voluntariado'])]]);
        $grupo = $this->informe->gruposEstudiantes()->firstOrCreate(
            ['estudiante_proyecto_id' => null, 'tipo_participacion' => $this->tipoParticipacionSinPlanificacion],
            ['hombres_planificados' => 0, 'mujeres_planificadas' => 0]
        );
        $this->cargarFormulario();
        $this->openEstudianteModal(null, $grupo->id);
    }

    public function closeEstudianteModal(): void
    {
        $this->showEstudianteModal = false;
        $this->editEstudianteIndex = null;
        $this->grupoEstudianteSeleccionadoId = null;
        $this->estudianteEncontrado = null;
        $this->mostrarRegistroManual = false;
        $this->resetErrorBag();
    }

    public function buscarEstudiante(IntegracionApiService $integraciones): void
    {
        $this->resetErrorBag('estudianteBusquedaCuenta');
        $this->estudianteEncontrado = null;
        $this->mostrarRegistroManual = false;
        $cuenta = preg_replace('/\s+/', '', trim($this->estudianteBusquedaCuenta));
        if ($cuenta === '') {
            $this->addError('estudianteBusquedaCuenta', 'Ingrese un número de cuenta para realizar la búsqueda.');
            return;
        }
        if (! ctype_digit($cuenta)) {
            $this->addError('estudianteBusquedaCuenta', 'El número de cuenta no tiene un formato válido.');
            return;
        }
        $this->estudianteBusquedaCuenta = $cuenta;
        $estudiante = Estudiante::query()
            ->with(['carrera','user'])
            ->where('cuenta', $cuenta)
            ->first();
        if (! $estudiante) {
            try {
                $api=$integraciones->buscarEstudiantePorCuenta($cuenta);
                if ($api['ok']) {
                    $datos=$api['datos'];
                    $this->estudianteEncontrado=['estudiante_id'=>null,'nombre'=>$datos['nombre_completo'] ?? trim(($datos['nombres'] ?? '').' '.($datos['apellidos'] ?? '')),'sexo'=>$this->sexoVisual($datos['sexo'] ?? null),'numero_cuenta'=>$datos['numero_cuenta'] ?? $cuenta,'carrera'=>$datos['carrera'] ?? null,'correo'=>$datos['correo'] ?? null];
                    $this->estudianteOrigen = 'API';
                    return;
                }
            } catch (\RuntimeException $e) { if ($e->getMessage() === 'La integración de estudiantes no está configurada.') $this->addError('estudianteBusquedaCuenta',$e->getMessage()); }
            $this->addError('estudianteBusquedaCuenta', 'No se encontró un estudiante con el número de cuenta ingresado.');
            $this->mostrarRegistroManual = true;
            $this->estudianteManual['numero_cuenta'] = $cuenta;
            return;
        }
        if ($this->estudianteDuplicadoEnGrupo($estudiante->id, $cuenta)) {
            $this->addError('estudianteBusquedaCuenta', 'Este estudiante ya fue agregado al grupo.');
            return;
        }
        $this->estudianteEncontrado = $this->snapshotEstudiante($estudiante);
    }

    public function limpiarSeleccionEstudiante(): void
    {
        $this->resetErrorBag('estudianteBusquedaCuenta');
        $this->estudianteBusquedaCuenta = '';
        $this->estudianteEncontrado = null;
        $this->mostrarRegistroManual = false;
        $this->estudianteManual = ['nombres'=>'','apellidos'=>'','numero_cuenta'=>'','sexo'=>'','carrera'=>'','correo'=>'','horas_dedicadas'=>0];
    }

    private function snapshotEstudiante(Estudiante $estudiante): array
    {
        return [
            'estudiante_id'=>$estudiante->id,
            'nombre'=>trim($estudiante->nombre.' '.$estudiante->apellido),
            'sexo'=>$this->sexoVisual($estudiante->sexo),
            'numero_cuenta'=>$estudiante->cuenta,
            'carrera'=>$estudiante->carrera?->nombre,
            'correo'=>$estudiante->user?->email,
        ];
    }

    public function saveEstudianteModal(): void
    {
        $this->authorizeSensitive();
        $this->validate([
            'grupoEstudianteSeleccionadoId'=>['required','integer',Rule::exists('informe_final_grupos_estudiantes','id')->where(fn ($query) => $query->where('informe_final_proyecto_id',$this->informe->id))],
            'estudianteEncontrado.estudiante_id'=>['nullable','integer'],
            'estudianteEncontrado.nombre'=>['required','string','max:255'],
            'estudianteEncontrado.numero_cuenta'=>['required','string','max:30'],
            'estudianteEncontrado.sexo'=>['required', Rule::in(['Masculino','Femenino'])],
            'estudianteModal.horas_dedicadas'=>['required','numeric','min:0'],
        ]);
        $grupo = $this->grupoEstudianteParaGuardar();
        $studentId = (int) $this->estudianteEncontrado['estudiante_id'];
        $valid = $studentId ? Estudiante::whereKey($studentId)->exists() : $this->estudianteOrigen === 'API';
        abort_unless($valid, 422);
        if ($this->estudianteDuplicadoEnGrupo($studentId ?: null, $this->estudianteEncontrado['numero_cuenta'])) {
            $this->addError('estudianteEncontrado.estudiante_id', 'Este estudiante ya fue agregado al grupo.');
            return;
        }
        if (! $this->validarCupoGrupo($this->estudianteEncontrado['sexo'], 'estudianteEncontrado.sexo')) return;
        $row = array_replace($this->estudianteEncontrado, $this->estudianteModal, [
            'informe_final_grupo_estudiante_id'=>$grupo['id'],
            'tipo_participacion'=>$grupo['tipo_participacion'],
            'cantidad'=>1,
        ]);
        $row['origen'] = $this->estudianteOrigen;
        $index = $this->editEstudianteIndex;
        if ($index === null) {
            $this->estudiantes[] = $row;
            $index = array_key_last($this->estudiantes);
        } else {
            $row['id'] = $this->estudiantes[$index]['id'] ?? null;
            $this->estudiantes[$index] = $row;
        }
        $this->guardarFilaAutoguardado('estudiantes', $index);
        $this->closeEstudianteModal();
    }

    public function saveEstudianteManual(): void
    {
        $this->authorizeSensitive();
        $this->validate([
            'grupoEstudianteSeleccionadoId'=>['required','integer',Rule::exists('informe_final_grupos_estudiantes','id')->where(fn ($query) => $query->where('informe_final_proyecto_id',$this->informe->id))],
            'estudianteManual.nombres'=>['required','string','max:150'],
            'estudianteManual.apellidos'=>['nullable','string','max:150'],
            'estudianteManual.numero_cuenta'=>['required','regex:/^\d+$/','max:30'],
            'estudianteManual.sexo'=>['required', Rule::in(['Masculino','Femenino'])],
            'estudianteManual.carrera'=>['nullable','string','max:255'],
            'estudianteManual.correo'=>['nullable','email','max:255'],
            'estudianteManual.horas_dedicadas'=>['required','numeric','min:0'],
        ]);
        $grupo = $this->grupoEstudianteParaGuardar();
        $cuenta = preg_replace('/\s+/', '', $this->estudianteManual['numero_cuenta']);
        if ($this->estudianteDuplicadoEnGrupo(null, $cuenta)) {
            $this->addError('estudianteManual.numero_cuenta', 'Este estudiante ya fue agregado al grupo.');
            return;
        }
        if (! $this->validarCupoGrupo($this->estudianteManual['sexo'], 'estudianteManual.sexo')) return;
        $row = [
            'informe_final_grupo_estudiante_id'=>$grupo['id'],
            'estudiante_id'=>null,
            'nombre'=>trim($this->estudianteManual['nombres'].' '.$this->estudianteManual['apellidos']),
            'sexo'=>$this->estudianteManual['sexo'] ?: null,
            'numero_cuenta'=>$cuenta,
            'carrera'=>$this->estudianteManual['carrera'] ?: null,
            'correo'=>$this->estudianteManual['correo'] ?: null,
            'tipo_participacion'=>$grupo['tipo_participacion'],
            'horas_dedicadas'=>$this->estudianteManual['horas_dedicadas'],
            'cantidad'=>1,
            'origen'=>'MANUAL',
        ];
        $index = $this->editEstudianteIndex;
        if ($index === null) {
            $this->estudiantes[] = $row;
            $index = array_key_last($this->estudiantes);
        } else {
            $row['id'] = $this->estudiantes[$index]['id'] ?? null;
            $this->estudiantes[$index] = $row;
        }
        $this->guardarFilaAutoguardado('estudiantes', $index);
        $this->closeEstudianteModal();
    }

    private function grupoEstudianteSeleccionado(): array
    {
        $grupo = collect($this->gruposEstudiantes)
            ->first(fn ($row) => (int) $row['id'] === (int) $this->grupoEstudianteSeleccionadoId);
        abort_unless($grupo, 422);

        return $grupo;
    }

    private function grupoEstudianteParaGuardar(): array
    {
        if ($this->editEstudianteIndex !== null) {
            $row = $this->estudiantes[$this->editEstudianteIndex] ?? null;
            $persistido = ! empty($row['id'])
                ? $this->informe->estudiantes()->whereKey($row['id'])->first()
                : null;
            if ($persistido) {
                $this->grupoEstudianteSeleccionadoId = (int) $persistido->informe_final_grupo_estudiante_id;
            }
        }

        return $this->grupoEstudianteSeleccionado();
    }

    private function estudianteDuplicadoEnGrupo(?int $estudianteId, ?string $cuenta): bool
    {
        return collect($this->estudiantes)->contains(function ($row, $index) use ($estudianteId, $cuenta) {
            if ($index === $this->editEstudianteIndex || (int) ($row['informe_final_grupo_estudiante_id'] ?? 0) !== (int) $this->grupoEstudianteSeleccionadoId) {
                return false;
            }
            if ($estudianteId && (int) ($row['estudiante_id'] ?? 0) === $estudianteId) {
                return true;
            }

            return filled($cuenta) && (string) ($row['numero_cuenta'] ?? '') === (string) $cuenta;
        });
    }

    private function validarCupoGrupo(string $sexo, string $campo): bool
    {
        $grupo = $this->grupoEstudianteSeleccionado();
        $masculino = $sexo === 'Masculino';
        $limite = (int) $grupo[$masculino ? 'hombres_planificados' : 'mujeres_planificadas'];
        $registrados = collect($this->estudiantes)->filter(function ($row, $index) use ($sexo) {
            return $index !== $this->editEstudianteIndex
                && (int) ($row['informe_final_grupo_estudiante_id'] ?? 0) === (int) $this->grupoEstudianteSeleccionadoId
                && ($row['estado_participacion'] ?? 'activo') === 'activo'
                && ($row['sexo'] ?? null) === $sexo;
        })->count();
        if ($registrados < $limite) {
            return true;
        }

        $this->addError($campo, 'Ya se registró la cantidad máxima de '.($masculino ? 'hombres' : 'mujeres').' planificada para este grupo.');

        return false;
    }

    public function openVoluntarioModal(?int $index = null): void
    {
        $this->resetErrorBag();
        $this->editVoluntarioIndex = $index;
        $this->voluntarioBusquedaNumero = '';
        $this->voluntarioEncontrado = null;
        $this->voluntarioModal = ['empleado_id'=>null,'nombre'=>'','sexo'=>'','identidad'=>'','departamento'=>'','tipo'=>'egresado','horas_dedicadas'=>0];
        if ($index !== null && isset($this->voluntarios[$index])) {
            $this->voluntarioModal = Arr::only($this->voluntarios[$index], ['empleado_id','nombre','sexo','identidad','departamento','tipo','horas_dedicadas']);
            if (! empty($this->voluntarioModal['empleado_id'])) $this->voluntarioEncontrado = $this->voluntarioModal;
        }
        $this->showVoluntarioModal = true;
    }

    public function closeVoluntarioModal(): void
    {
        $this->showVoluntarioModal = false;
        $this->editVoluntarioIndex = null;
        $this->voluntarioEncontrado = null;
        $this->resetErrorBag();
    }

    public function buscarVoluntario(): void
    {
        $this->validate(['voluntarioBusquedaNumero'=>['required','string','max:30']]);
        $numero = trim($this->voluntarioBusquedaNumero);
        $empleado = Empleado::query()->with('departamento_academico')
            ->whereHas('proyectos', fn ($query) => $query->where('proyecto.id', $this->proyecto->id))
            ->where('numero_empleado', $numero)->first();
        if (! $empleado) {
            $this->voluntarioEncontrado = null;
            $this->addError('voluntarioBusquedaNumero', 'No se encontró una persona del proyecto con ese número de empleado.');
            return;
        }
        $this->voluntarioEncontrado = ['empleado_id'=>$empleado->id,'nombre'=>$empleado->nombre_completo,'sexo'=>$this->sexoVisual($empleado->sexo),'identidad'=>$empleado->numero_empleado,'departamento'=>$empleado->departamento_academico?->nombre];
        $this->voluntarioModal = array_replace($this->voluntarioModal, $this->voluntarioEncontrado);
    }

    public function saveVoluntarioModal(): void
    {
        $this->authorizeSensitive();
        $this->validate([
            'voluntarioModal.nombre'=>['required','string','max:255'],
            'voluntarioModal.sexo'=>['required', Rule::in(['Masculino','Femenino'])],
            'voluntarioModal.tipo'=>[Rule::in(['profesor_hora','pas','profesor_permanente','egresado'])],
            'voluntarioModal.horas_dedicadas'=>['required','numeric','min:0'],
        ]);
        $duplicate = collect($this->voluntarios)->contains(function ($row, $index) {
            if ($index === $this->editVoluntarioIndex) return false;
            if (! empty($this->voluntarioModal['empleado_id'])) return (int) ($row['empleado_id'] ?? 0) === (int) $this->voluntarioModal['empleado_id'];
            return filled($this->voluntarioModal['identidad']) && mb_strtolower((string) ($row['identidad'] ?? '')) === mb_strtolower((string) $this->voluntarioModal['identidad']);
        });
        if ($duplicate) {
            $this->addError('voluntarioModal.nombre', 'Esta persona ya fue agregada.');
            return;
        }
        $index = $this->editVoluntarioIndex;
        if ($index === null) {
            $this->voluntarios[] = $this->voluntarioModal;
            $index = array_key_last($this->voluntarios);
        } else {
            $this->voluntarioModal['id'] = $this->voluntarios[$index]['id'] ?? null;
            $this->voluntarios[$index] = $this->voluntarioModal;
        }
        $this->guardarFilaAutoguardado('voluntarios', $index);
        $this->closeVoluntarioModal();
    }

    public function openNoParticipacionModal(string $tipo, int $index): void
    {
        $this->authorizeSensitive();
        $config = $this->configParticipantes()[$tipo] ?? null;
        abort_unless($config && isset($this->{$config}[$index]), 404);
        $this->tipoParticipanteNoParticipacion = $tipo;
        $this->indiceParticipanteNoParticipacion = $index;
        $this->estadoNoParticipacion = 'no_participo';
        $this->observacionNoParticipacion = '';
        $this->resetErrorBag();
        $this->showNoParticipacionModal = true;
    }

    public function closeNoParticipacionModal(): void
    {
        $this->showNoParticipacionModal = false;
        $this->tipoParticipanteNoParticipacion = '';
        $this->indiceParticipanteNoParticipacion = null;
        $this->observacionNoParticipacion = '';
        $this->resetErrorBag();
    }

    public function confirmarNoParticipacion(): void
    {
        $this->authorizeSensitive();
        $this->validate([
            'estadoNoParticipacion' => ['required', Rule::in(['no_participo','retirado'])],
            'observacionNoParticipacion' => ['required','string','min:10','max:500'],
        ]);
        $property = $this->configParticipantes()[$this->tipoParticipanteNoParticipacion] ?? null;
        $index = $this->indiceParticipanteNoParticipacion;
        abort_unless($property && $index !== null && isset($this->{$property}[$index]), 404);
        $this->{$property}[$index]['estado_participacion'] = $this->estadoNoParticipacion;
        $this->{$property}[$index]['observacion_no_participacion'] = trim($this->observacionNoParticipacion);
        $this->{$property}[$index]['removido_en'] = now()->toDateTimeString();
        $this->{$property}[$index]['removido_por'] = auth()->id();
        $this->guardarFilaAutoguardado($property, $index);
        $this->closeNoParticipacionModal();
    }

    public function restaurarParticipante(string $tipo, int $index): void
    {
        $this->authorizeSensitive();
        $property = $this->configParticipantes()[$tipo] ?? null;
        abort_unless($property && isset($this->{$property}[$index]), 404);
        if ($property === 'estudiantes') {
            $this->grupoEstudianteSeleccionadoId = (int) ($this->estudiantes[$index]['informe_final_grupo_estudiante_id'] ?? 0);
            $this->editEstudianteIndex = $index;
            if (! $this->validarCupoGrupo($this->estudiantes[$index]['sexo'], "estudiantes.$index.sexo")) return;
        }
        $this->{$property}[$index]['estado_participacion'] = 'activo';
        $this->{$property}[$index]['observacion_no_participacion'] = null;
        $this->{$property}[$index]['removido_en'] = null;
        $this->{$property}[$index]['removido_por'] = null;
        $this->guardarFilaAutoguardado($property, $index);
        $this->grupoEstudianteSeleccionadoId = null;
        $this->editEstudianteIndex = null;
    }

    public function updatedFotografiasTemporales(): void
    {
        $this->authorizeSensitive();
        $existentes = collect($this->anexos)->where('categoria', 'fotografia')->count();
        $disponibles = max(0, 20 - $existentes);
        $guardadas = 0;
        $huboErrores = false;

        foreach ($this->fotografiasTemporales as $index => $foto) {
            if ($index >= $disponibles) {
                $this->addError('fotografiasTemporales', 'Solo se permiten hasta 20 fotografías por informe.');
                $this->addError("fotografiasTemporales.$index", $foto->getClientOriginalName().': solo se permiten hasta 20 fotografías por informe.');
                $huboErrores = true;
                continue;
            }

            $validator = Validator::make(
                ['fotografia' => $foto],
                ['fotografia' => ['required','image','mimes:jpg,jpeg,png,webp','max:10240']],
                [
                    'fotografia.image' => 'El formato de la fotografía no está permitido.',
                    'fotografia.mimes' => 'El formato de la fotografía no está permitido.',
                    'fotografia.max' => 'La fotografía supera el tamaño máximo de 10 MB.',
                ]
            );
            if ($validator->fails()) {
                $this->addError("fotografiasTemporales.$index", $foto->getClientOriginalName().': '.$validator->errors()->first('fotografia'));
                $huboErrores = true;
                continue;
            }

            $ruta = $foto->store('informes-finales/'.$this->informe->id.'/fotografias', 'public');
            $this->informe->anexos()->create([
                'tipo' => 'fotografias',
                'categoria' => 'fotografia',
                'archivo' => $ruta,
                'nombre_archivo' => $foto->getClientOriginalName(),
                'tamano_bytes' => $foto->getSize(),
                'fecha' => now()->toDateString(),
                'orden' => $existentes + 1,
                'origen' => 'INFORME',
            ]);
            $existentes++;
            $guardadas++;
        }
        $this->fotografiasTemporales = [];
        if ($guardadas > 0) {
            $this->cargarFormulario();
            $this->dispatch('fotografias-guardadas');
        }
        $this->estadoGuardado = $huboErrores ? 'error' : 'guardado';
    }

    public function quitarFotografia(int $id): void
    {
        $this->authorizeSensitive();
        $foto = $this->informe->anexos()->whereKey($id)->where('categoria', 'fotografia')->firstOrFail();
        if ($foto->origen === 'INFORME' && filled($foto->archivo)) Storage::disk('public')->delete($foto->archivo);
        $foto->delete();
        $this->cargarFormulario();
        $this->estadoGuardado = 'guardado';
    }

    public function anexoUrl(?string $ruta): ?string
    {
        if (blank($ruta)) return null;

        return filter_var($ruta, FILTER_VALIDATE_URL) ? $ruta : Storage::disk('public')->url($ruta);
    }

    private function configParticipantes(): array
    {
        return ['equipo'=>'equipo','cooperacion'=>'cooperacion','estudiante'=>'estudiantes','voluntario'=>'voluntarios'];
    }

    public function updated(string $propertyName): void
    {
        if (! $this->debeAutoguardar($propertyName)) {
            return;
        }

        $this->autoGuardarCampo($propertyName);
    }

    public function agregarFila(string $grupo): void
    {
        $this->authorizeSensitive();
        $defaults = [
            'cooperacion' => ['nombre'=>'','pasaporte'=>'','correo'=>'','pais'=>'','universidad'=>'','horas_dedicadas'=>0,'estado_participacion'=>'activo'],
            'estudiantes' => ['estudiante_id'=>null,'nombre'=>'','sexo'=>'','numero_cuenta'=>'','carrera'=>'','tipo_participacion'=>'practica_asignatura','horas_dedicadas'=>0,'estado_participacion'=>'activo'],
            'voluntarios' => ['nombre'=>'','sexo'=>'','identidad'=>'','departamento'=>'','tipo'=>'egresado','horas_dedicadas'=>0,'estado_participacion'=>'activo'],
            'contrapartes' => ['existe_apoyo'=>true,'nombre'=>'','tipo'=>'sociedad_civil','contacto'=>'','correo'=>'','cargo'=>'','telefono'=>'','tipo_instrumento'=>null,'compromisos_asumidos'=>'','compromisos_cumplidos'=>'','territorio'=>'','aporte_monetario'=>0,'aporte_especie'=>0,'documento_respaldo'=>''],
            'resultados' => ['objetivo_especifico'=>'','resultado_planificado'=>'','indicador_propuesto'=>'','meta_numerica'=>null,'unidad_medida'=>'','valor_alcanzado'=>null,'porcentaje_cumplimiento'=>0,'estado'=>'no_alcanzado','producto_logrado'=>'','observaciones'=>''],
            'actividades' => ['actividad_planificada'=>'','actividad_realizada'=>'','responsable'=>'','fecha_inicial'=>null,'fecha_final'=>null,'horas_dedicadas'=>0,'medio_verificacion'=>'','estado'=>'no_ejecutada','origen'=>'emergente','participantes'=>[]],
            'accionesNoEjecutadas' => ['resultado_previsto'=>'','actividad_planificada'=>'','explicacion'=>'','afectacion_proyecto'=>'','responsable'=>'','impacto'=>'medio'],
            'accionesEmergentes' => ['producto_logrado'=>'','actividad_realizada'=>'','justificacion'=>'','responsables'=>'','fecha'=>null,'horas'=>0,'informe_final_resultado_id'=>null],
            'ods' => ['ods_id'=>'','meta_contribuye_id'=>null,'meta_ods'=>'','descripcion_aporte'=>'','evidencia'=>'','nivel_contribucion'=>'directa'],
            'presupuesto' => ['fuente'=>'UNAH','concepto'=>'otros','unidad'=>'','cantidad'=>0,'costo_unitario'=>0,'origen_fondos'=>'','informe_final_contraparte_id'=>null],
            'anexos' => ['tipo'=>'otros','categoria'=>'documento_general','descripcion'=>'','archivo'=>null,'enlace'=>'','fecha'=>null,'informe_final_resultado_id'=>null,'informe_final_actividad_id'=>null,'informe_final_contraparte_id'=>null,'instrumento_formalizacion_id'=>null,'nombre_archivo'=>null,'tamano_bytes'=>null,'origen'=>'INFORME','orden'=>count($this->anexos)+1],
        ];
        abort_unless(array_key_exists($grupo, $defaults), 404);
        $this->{$grupo}[] = $defaults[$grupo];
        $this->guardarFilaAutoguardado($grupo, array_key_last($this->{$grupo}));
    }

    public function quitarFila(string $grupo, int $indice): void
    {
        $this->authorizeSensitive();
        abort_unless(property_exists($this, $grupo), 404);
        abort_if(in_array($grupo, ['equipo','cooperacion','estudiantes','voluntarios'], true), 422, 'Los participantes deben marcarse como no participantes para conservar la trazabilidad.');
        $this->eliminarFilaPersistida($grupo, $this->{$grupo}[$indice] ?? []);
        unset($this->{$grupo}[$indice]);
        $this->{$grupo} = array_values($this->{$grupo});
        $this->estadoGuardado = 'guardado';
    }

    public function agregarParticipanteActividad(int $actividadIndex): void
    {
        $this->authorizeSensitive();
        abort_unless(isset($this->actividades[$actividadIndex]), 404);
        $token = (string) ($this->participanteSeleccion[$actividadIndex] ?? 'externo:nuevo');
        [$tipo, $id] = array_pad(explode(':', $token, 2), 2, null);
        $participante = $this->resolverParticipante($tipo, $id);
        $actuales = collect($this->actividades[$actividadIndex]['participantes'] ?? []);
        $duplicado = $actuales->contains(fn ($row) => $this->mismaPersona($row, $participante));
        if ($duplicado) {
            $this->addError("actividades.$actividadIndex.participantes", 'La persona ya participa en esta actividad.');
            return;
        }
        $participante['orden'] = $actuales->count() + 1;
        $participante['es_responsable'] = $actuales->isEmpty();
        if ($participante['es_responsable']) {
            $participante['rol'] = 'Responsable principal';
            $this->actividades[$actividadIndex]['responsable'] = $participante['nombre'];
        }
        $this->actividades[$actividadIndex]['participantes'][] = $participante;
        $this->guardarFilaAutoguardado('actividades', $actividadIndex);
    }

    public function marcarResponsableActividad(int $actividadIndex, int $participanteIndex): void
    {
        $this->authorizeSensitive();
        abort_unless(isset($this->actividades[$actividadIndex]['participantes'][$participanteIndex]), 404);
        foreach ($this->actividades[$actividadIndex]['participantes'] as $index => &$row) {
            $row['es_responsable'] = $index === $participanteIndex;
            if ($index === $participanteIndex) {
                $row['rol'] = 'Responsable principal';
                $this->actividades[$actividadIndex]['responsable'] = $row['nombre'];
            }
        }
        unset($row);
        $this->guardarFilaAutoguardado('actividades', $actividadIndex);
    }

    public function quitarParticipanteActividad(int $actividadIndex, int $participanteIndex): void
    {
        $this->authorizeSensitive();
        $participante = $this->actividades[$actividadIndex]['participantes'][$participanteIndex] ?? null;
        if (! $participante) return;
        unset($this->actividades[$actividadIndex]['participantes'][$participanteIndex]);
        $this->actividades[$actividadIndex]['participantes'] = array_values($this->actividades[$actividadIndex]['participantes']);
        if ($participante['es_responsable'] ?? false) {
            $nextIndex = collect($this->actividades[$actividadIndex]['participantes'])->sortBy('orden')->keys()->first();
            if ($nextIndex === null) {
                $this->actividades[$actividadIndex]['responsable'] = '';
            } else {
                foreach ($this->actividades[$actividadIndex]['participantes'] as $index => &$row) {
                    $row['es_responsable'] = $index === $nextIndex;
                    if ($index === $nextIndex) { $row['rol'] = 'Responsable principal'; $this->actividades[$actividadIndex]['responsable'] = $row['nombre']; }
                }
                unset($row);
            }
        }
        $this->guardarFilaAutoguardado('actividades', $actividadIndex);
    }

    public function guardarBorrador(): void
    {
        $this->authorizeSensitive();
        $this->validate($this->draftRules());
        $this->persistir();
        $this->mensaje = 'Borrador guardado correctamente.';
    }

    public function validarInforme(InformeFinalProyectoValidator $validator): void
    {
        $this->marcarCompleto($validator);
    }

    public function marcarCompleto(InformeFinalProyectoValidator $validator): void
    {
        $this->authorizeSensitive();
        $this->validate($this->draftRules());
        $this->validarJustificacionesParticipacion();
        $this->persistir();
        $this->informe->refresh();
        $validator->validateForCompletion($this->informe);
        $eraCompleto = $this->informe->estado === InformeFinalProyecto::ESTADO_COMPLETO;
        $this->informe->update(['estado' => InformeFinalProyecto::ESTADO_COMPLETO, 'updated_by' => auth()->id()]);
        if (! $eraCompleto) {
            app(InformeFinalProyectoWorkflowService::class)->registrarInformeCompleto($this->informe, auth()->user());
        }
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

    public function getGruposEstudiantesConRegistroProperty(): array
    {
        return collect($this->gruposEstudiantes)->map(function ($grupo, $grupoIndex) {
            $estudiantes = collect($this->estudiantes)
                ->map(fn ($row, $index) => $row + ['indice_formulario' => $index])
                ->filter(fn ($row) => (int) ($row['informe_final_grupo_estudiante_id'] ?? 0) === (int) $grupo['id'])
                ->values();
            $activos = $estudiantes->filter(fn ($row) => ($row['estado_participacion'] ?? 'activo') === 'activo');
            $hombres = $activos->where('sexo', 'Masculino')->count();
            $mujeres = $activos->where('sexo', 'Femenino')->count();
            $asignatura = $grupo['asignatura'] ?? null;

            return $grupo + [
                'indice_formulario' => $grupoIndex,
                'tipo_etiqueta' => ParticipacionEstudiantil::etiqueta($grupo['tipo_participacion']),
                'tipo_codigo' => ParticipacionEstudiantil::codigo($grupo['tipo_participacion']),
                'asignatura_etiqueta' => $asignatura ? collect([$asignatura['codigo'] ?? null, $asignatura['nombre'] ?? null])->filter()->implode(' - ') : null,
                'total_planificado' => (int) $grupo['hombres_planificados'] + (int) $grupo['mujeres_planificadas'],
                'hombres_registrados' => $hombres,
                'mujeres_registradas' => $mujeres,
                'total_registrado' => $hombres + $mujeres,
                'hombres_pendientes' => max(0, (int) $grupo['hombres_planificados'] - $hombres),
                'mujeres_pendientes' => max(0, (int) $grupo['mujeres_planificadas'] - $mujeres),
                'estudiantes' => $estudiantes->all(),
            ];
        })->all();
    }

    public function getGrupoEstudianteActivoProperty(): ?array
    {
        return collect($this->gruposEstudiantesConRegistro)
            ->first(fn ($grupo) => (int) $grupo['id'] === (int) $this->grupoEstudianteSeleccionadoId);
    }

    public function getResumenPlanificacionEstudiantesProperty(): array
    {
        $grupos = collect($this->gruposEstudiantesConRegistro);
        $planificadosHombres = $grupos->sum('hombres_planificados');
        $planificadasMujeres = $grupos->sum('mujeres_planificadas');
        $registradosHombres = $grupos->sum('hombres_registrados');
        $registradasMujeres = $grupos->sum('mujeres_registradas');

        return [
            'planificados' => ['hombres'=>$planificadosHombres,'mujeres'=>$planificadasMujeres,'total'=>$planificadosHombres+$planificadasMujeres],
            'registrados' => ['hombres'=>$registradosHombres,'mujeres'=>$registradasMujeres,'total'=>$registradosHombres+$registradasMujeres],
            'pendientes' => ['hombres'=>max(0,$planificadosHombres-$registradosHombres),'mujeres'=>max(0,$planificadasMujeres-$registradasMujeres),'total'=>max(0,$planificadosHombres+$planificadasMujeres-$registradosHombres-$registradasMujeres)],
        ];
    }

    public function getTotalesParticipacionProperty(): array
    {
        $students = collect($this->estudiantes)->filter(fn ($row) => ($row['estado_participacion'] ?? 'activo') === 'activo');
        $volunteers = collect($this->voluntarios)->filter(fn ($row) => ($row['estado_participacion'] ?? 'activo') === 'activo');
        $sexo = fn ($value) => $this->sexoCanonico($value);
        return [
            'estudiantes' => $students->count(),
            'estudiantes_hombres' => $students->filter(fn ($row) => $sexo($row['sexo'] ?? null) === 'masculino')->count(),
            'estudiantes_mujeres' => $students->filter(fn ($row) => $sexo($row['sexo'] ?? null) === 'femenino')->count(),
            'estudiantes_practica' => $students->where('tipo_participacion', 'practica_asignatura')->count(),
            'estudiantes_pps' => $students->where('tipo_participacion', 'pps_servicio_social')->count(),
            'estudiantes_voluntariado' => $students->where('tipo_participacion', 'voluntariado')->count(),
            'horas_estudiantes' => $students->sum(fn ($row) => (float) ($row['horas_dedicadas'] ?? 0)),
            'voluntarios' => $volunteers->count(),
            'voluntarios_hombres' => $volunteers->filter(fn ($row) => $sexo($row['sexo'] ?? null) === 'masculino')->count(),
            'voluntarios_mujeres' => $volunteers->filter(fn ($row) => $sexo($row['sexo'] ?? null) === 'femenino')->count(),
            'horas_voluntarios' => $volunteers->sum(fn ($row) => (float) ($row['horas_dedicadas'] ?? 0)),
        ];
    }

    public function sexoVisual(mixed $value): string
    {
        return match ($this->sexoCanonico($value)) {
            'masculino' => 'Masculino',
            'femenino' => 'Femenino',
            default => '',
        };
    }

    public function estadoParticipacionVisual(?string $estado): string
    {
        return match ($estado ?: 'activo') {
            'no_participo' => 'No participó',
            'retirado' => 'Retirado',
            default => 'Participó',
        };
    }

    public function getOpcionesParticipantesActividadProperty(): array
    {
        return [
            'Equipo docente' => collect($this->equipo)->filter(fn ($row) => ! empty($row['empleado_id']) && ($row['estado_participacion'] ?? 'activo') === 'activo')->map(fn ($row) => ['value'=>'docente:'.$row['empleado_id'],'label'=>$row['nombre']])->values()->all(),
            'Estudiantes' => collect($this->estudiantes)->filter(fn ($row) => ! empty($row['id']) && ($row['estado_participacion'] ?? 'activo') === 'activo')->map(fn ($row) => ['value'=>'estudiante:'.$row['id'],'label'=>$row['nombre'].' · '.$this->sexoVisual($row['sexo'] ?? null)])->values()->all(),
            'Voluntarios' => collect($this->voluntarios)->filter(fn ($row) => ! empty($row['id']) && ($row['estado_participacion'] ?? 'activo') === 'activo')->map(fn ($row) => ['value'=>'voluntario:'.$row['id'],'label'=>$row['nombre']])->values()->all(),
        ];
    }

    public function getFotografiasProperty(): array
    {
        return collect($this->anexos)
            ->map(fn ($row, $index) => $row + ['indice_formulario' => $index])
            ->where('categoria', 'fotografia')->values()->all();
    }

    public function getDocumentosAnexosProperty(): array
    {
        return collect($this->anexos)
            ->map(fn ($row, $index) => $row + ['indice_formulario' => $index])
            ->reject(fn ($row) => ($row['categoria'] ?? 'documento_general') === 'fotografia')->values()->all();
    }

    public function getContrapartesConInstrumentosProperty(): array
    {
        return collect($this->contrapartes)->map(function ($contraparte) {
            $documentos = collect($this->anexos)->filter(fn ($anexo) => ($anexo['categoria'] ?? null) === 'instrumento_contraparte'
                && (int) ($anexo['informe_final_contraparte_id'] ?? 0) === (int) $contraparte['id'])->values();
            $disponible = $documentos->contains(fn ($anexo) => filled($anexo['archivo'] ?? null) || filled($anexo['enlace'] ?? null));

            return $contraparte + [
                'instrumentos' => $documentos->all(),
                'estado_instrumento' => $documentos->isEmpty() ? 'No aplica' : ($disponible ? 'Disponible' : 'Pendiente'),
            ];
        })->all();
    }

    public function participanteNoParticipacionActual(): ?array
    {
        $property = $this->configParticipantes()[$this->tipoParticipanteNoParticipacion] ?? null;
        return $property && $this->indiceParticipanteNoParticipacion !== null
            ? ($this->{$property}[$this->indiceParticipanteNoParticipacion] ?? null)
            : null;
    }

    public function getPorcentajesValoracionProperty(): array
    {
        $muestra = (int) ($this->general['valoracion_muestra'] ?? 0);
        return collect(['excelente','muy_buena','regular','mala'])->mapWithKeys(fn ($tipo) => [$tipo => $muestra > 0 ? round((int) ($this->general['valoracion_'.$tipo] ?? 0) / $muestra * 100, 2) : 0])->all();
    }

    public function isStepComplete(int $step): bool
    {
        return match ($step) {
            1 => filled($this->general['nombre_proyecto'] ?? null)
                && filled($this->general['fecha_inicio'] ?? null)
                && filled($this->general['fecha_finalizacion'] ?? null),
            2 => ! empty($this->equipo),
            3 => collect($this->estudiantes)->contains(fn ($row) => ($row['estado_participacion'] ?? 'activo') === 'activo')
                || collect($this->voluntarios)->contains(fn ($row) => ($row['estado_participacion'] ?? 'activo') === 'activo'),
            4 => collect($this->contrapartes)->contains(fn ($row) => filled($row['nombre'] ?? null)),
            5 => ! empty($this->resultados) && ! empty($this->actividades),
            6 => filled($this->general['transformacion_lograda'] ?? null)
                && filled($this->general['mecanismos_sostenibilidad'] ?? null),
            7 => (int) ($this->general['valoracion_muestra'] ?? 0)
                    === collect(['excelente','muy_buena','regular','mala'])->sum(fn ($tipo) => (int) ($this->general['valoracion_'.$tipo] ?? 0))
                && (float) ($this->general['presupuesto_planificado'] ?? 0) > 0,
            8 => filled($this->general['fecha_cierre'] ?? null)
                && (bool) ($this->general['confirmacion_veracidad'] ?? false),
            default => false,
        };
    }

    public function getCamposPendientesProperty(): array
    {
        $fields = [
            'Fecha de cierre' => $this->general['fecha_cierre'] ?? null,
            'Transformación lograda' => $this->general['transformacion_lograda'] ?? null,
            'Mecanismos de sostenibilidad' => $this->general['mecanismos_sostenibilidad'] ?? null,
            'Confirmación de veracidad' => $this->general['confirmacion_veracidad'] ?? false,
        ];
        if (empty($this->resultados)) $fields['Resultados'] = null;
        if (empty($this->actividades)) $fields['Actividades'] = null;
        return collect($fields)->filter(fn ($value) => blank($value))->keys()->all();
    }

    public function getInconsistenciasRevisionProperty(): array
    {
        $issues = [];
        if (count(array_unique($this->totalesBeneficiarios)) > 1) {
            $issues[] = 'Los totales de beneficiarios por sexo, edad y etnia no coinciden.';
        }
        $beneficiarios = (int) ($this->general['valoracion_total_beneficiarios'] ?? 0);
        $muestra = (int) ($this->general['valoracion_muestra'] ?? 0);
        if ($muestra > $beneficiarios) {
            $issues[] = 'La muestra comunitaria supera el total de beneficiarios.';
        }
        $respuestas = collect(['excelente','muy_buena','regular','mala'])->sum(fn ($tipo) => (int) ($this->general['valoracion_'.$tipo] ?? 0));
        if ($respuestas !== $muestra) {
            $issues[] = 'La suma de valoraciones no coincide con el tamaño de la muestra.';
        }
        return $issues;
    }

    public function getResumenRevisionProperty(): array
    {
        return [
            'Beneficiarios' => $this->totalesBeneficiarios['sexo'],
            'Integrantes del equipo' => count($this->equipo),
            'Estudiantes' => $this->totalesParticipacion['estudiantes'],
            'Voluntarios' => $this->totalesParticipacion['voluntarios'],
            'Resultados' => count($this->resultados),
            'Actividades' => count($this->actividades),
            'Ejecución presupuestaria' => number_format($this->totalesPresupuesto['porcentaje'], 2).'%',
            'Anexos' => count($this->anexos),
        ];
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
        $this->informe->load(['beneficiarios','equipoDocente','cooperacion','gruposEstudiantes.asignatura','estudiantes','voluntarios','contrapartes','resultados','actividades.participantes','accionesNoEjecutadas','accionesEmergentes','ods','presupuestoDetalles','anexos']);
        $date = fn ($value) => $value?->format('Y-m-d');
        $this->general = Arr::only($this->informe->toArray(), [
            'numero_registro','nombre_proyecto','facultad_centro','unidad_academica','departamento_academico','carrera','programa_vinculacion','linea_investigacion','modalidad','ejes_prioritarios','categoria','pais','region','departamento_territorial','municipio','aldea_ciudad','caserio','objetivo_general','dificultades','acciones_dificultades','lecciones_aprendidas','buenas_practicas','problema_inicial','transformacion_lograda','mecanismos_sostenibilidad','acciones_contraparte_sostenibilidad','desafios','respuesta_reforma_universitaria','recomendaciones','bibliografia','valoracion_total_beneficiarios','valoracion_muestra','valoracion_excelente','valoracion_muy_buena','valoracion_regular','valoracion_mala','presupuesto_planificado','aporte_beneficiarios','otros_aportes','observacion_voluntarios_no_incorporados','observaciones_finales','confirmacion_veracidad','estado',
        ]);
        $this->general['fecha_registro'] = $date($this->informe->fecha_registro);
        $this->general['fecha_inicio'] = $date($this->informe->fecha_inicio);
        $this->general['fecha_finalizacion'] = $date($this->informe->fecha_finalizacion);
        $this->general['fecha_cierre'] = $date($this->informe->fecha_cierre);
        $this->beneficiarios = Arr::except($this->informe->beneficiarios?->toArray() ?? [], ['id','informe_final_proyecto_id','created_at','updated_at']);
        $this->gruposEstudiantes = $this->informe->gruposEstudiantes->toArray();
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
        $this->estadoGuardado = 'guardando';
        DB::transaction(function () {
            $mainFields = array_keys(Arr::except($this->general, ['estado','fecha_registro']));
            $this->informe->update(Arr::only($this->general, $mainFields) + ['updated_by' => auth()->id()]);
            $this->informe->beneficiarios()->updateOrCreate([], Arr::except($this->beneficiarios, ['id','informe_final_proyecto_id','created_at','updated_at']));
            $participacion = ['estado_participacion','observacion_no_participacion','removido_en','removido_por'];
            foreach ($this->estudiantes as $index => &$estudiante) {
                $estudiante = $this->normalizarAsociacionEstudiante($estudiante, $index);
            }
            unset($estudiante);
            $this->syncRows('gruposEstudiantes', $this->gruposEstudiantes, ['observacion_no_cumplimiento']);
            $this->syncRows('equipoDocente', $this->equipo, array_merge(['empleado_id','nombre','numero_empleado','correo','categoria','departamento','sexo','horas_dedicadas'],$participacion));
            $this->syncRows('cooperacion', $this->cooperacion, array_merge(['nombre','pasaporte','correo','pais','universidad','horas_dedicadas'],$participacion));
            $this->syncRows('estudiantes', $this->estudiantes, array_merge(['informe_final_grupo_estudiante_id','estudiante_id','nombre','sexo','numero_cuenta','carrera','correo','tipo_participacion','horas_dedicadas','cantidad','origen'],$participacion));
            $this->syncRows('voluntarios', $this->voluntarios, array_merge(['empleado_id','nombre','sexo','identidad','departamento','tipo','horas_dedicadas'],$participacion));
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
            foreach ($this->actividades as $index => &$actividad) {
                $this->syncParticipantesActividad($actividad, $index);
            }
            unset($actividad);
            $this->syncRows('accionesNoEjecutadas', $this->accionesNoEjecutadas, ['resultado_previsto','actividad_planificada','explicacion','afectacion_proyecto','responsable','impacto']);
            $this->syncRows('accionesEmergentes', $this->accionesEmergentes, ['informe_final_resultado_id','producto_logrado','actividad_realizada','justificacion','responsables','fecha','horas']);
            $this->syncRows('ods', $this->ods, ['ods_id','meta_contribuye_id','meta_ods','descripcion_aporte','evidencia','nivel_contribucion']);
            $this->syncRows('presupuestoDetalles', $this->presupuesto, ['informe_final_contraparte_id','fuente','concepto','unidad','cantidad','costo_unitario','origen_fondos']);
            foreach ($this->anexoArchivos as $index => $file) {
                if ($file) {
                    $this->anexos[$index]['archivo'] = $file->store('informes-finales/'.$this->informe->id.'/documentos', 'public');
                    $this->anexos[$index]['nombre_archivo'] = $file->getClientOriginalName();
                    $this->anexos[$index]['tamano_bytes'] = $file->getSize();
                    $this->anexos[$index]['origen'] = 'INFORME';
                }
            }
            $this->syncRows('anexos', $this->anexos, ['informe_final_resultado_id','informe_final_actividad_id','informe_final_contraparte_id','instrumento_formalizacion_id','tipo','categoria','descripcion','archivo','nombre_archivo','tamano_bytes','origen','enlace','fecha','orden']);
        }, 3);
        $this->informe->refresh();
        $this->cargarFormulario();
        $this->estadoGuardado = 'guardado';
    }

    private function validateCurrentStep(): void
    {
        $rules = match ($this->currentStep) {
            1 => [
                'general.fecha_inicio' => ['required','date'],
                'general.fecha_finalizacion' => ['required','date','after_or_equal:general.fecha_inicio'],
                'beneficiarios.*' => ['nullable','integer','min:0'],
            ],
            2 => [
                'equipo' => ['required','array','min:1'],
                'equipo.*.horas_dedicadas' => ['nullable','numeric','min:0'],
                'cooperacion.*.horas_dedicadas' => ['nullable','numeric','min:0'],
            ],
            3 => [
                'gruposEstudiantes.*.observacion_no_cumplimiento' => ['nullable','string','min:10','max:1000'],
                'estudiantes.*.nombre' => ['required','string'],
                'estudiantes.*.informe_final_grupo_estudiante_id' => ['required','integer',Rule::exists('informe_final_grupos_estudiantes','id')->where(fn ($query) => $query->where('informe_final_proyecto_id',$this->informe->id))],
                'estudiantes.*.sexo' => ['required', Rule::in(['Masculino','Femenino'])],
                'estudiantes.*.tipo_participacion' => [Rule::in(['practica_asignatura','pps_servicio_social','voluntariado'])],
                'estudiantes.*.horas_dedicadas' => ['nullable','numeric','min:0'],
                'voluntarios.*.nombre' => ['required','string'],
                'voluntarios.*.sexo' => ['required', Rule::in(['Masculino','Femenino'])],
                'voluntarios.*.tipo' => [Rule::in(['profesor_hora','pas','profesor_permanente','egresado'])],
                'general.observacion_voluntarios_no_incorporados' => ['nullable','string','min:10','max:1000'],
            ],
            4 => [
                'contrapartes' => ['required','array','min:1'],
                'contrapartes.*.nombre' => ['required','string'],
                'contrapartes.*.tipo' => [Rule::in(['gobierno_nacional','gobierno_municipal','ong','sociedad_civil','sector_privado','internacional'])],
            ],
            5 => [
                'resultados' => ['required','array','min:1'],
                'resultados.*.resultado_planificado' => ['required','string'],
                'resultados.*.porcentaje_cumplimiento' => ['numeric','between:0,100'],
                'actividades' => ['required','array','min:1'],
                'actividades.*.actividad_planificada' => ['required','string'],
                'actividades.*.participantes.*.horas_dedicadas' => ['nullable','numeric','min:0'],
            ],
            6 => [
                'accionesNoEjecutadas.*.actividad_planificada' => ['required','string'],
                'accionesNoEjecutadas.*.explicacion' => ['required','string'],
                'accionesEmergentes.*.actividad_realizada' => ['required','string'],
                'accionesEmergentes.*.justificacion' => ['required','string'],
            ],
            7 => [
                'general.valoracion_total_beneficiarios' => ['integer','min:0'],
                'general.valoracion_muestra' => ['integer','min:0','lte:general.valoracion_total_beneficiarios'],
                'general.presupuesto_planificado' => ['required','numeric','gt:0'],
                'presupuesto.*.cantidad' => ['numeric','min:0'],
                'presupuesto.*.costo_unitario' => ['numeric','min:0'],
            ],
            8 => [
                'anexos.*.categoria' => [Rule::in(['documento_general','instrumento_contraparte','fotografia'])],
                'anexos.*.informe_final_contraparte_id' => ['required_if:anexos.*.categoria,instrumento_contraparte','nullable','integer',Rule::exists('informe_final_contrapartes','id')->where(fn ($query) => $query->where('informe_final_proyecto_id',$this->informe->id))],
                'anexoArchivos.*' => ['nullable','file','mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png','max:10240'],
            ],
            default => [],
        };
        if ($rules) {
            $this->validate($rules);
        }
        if ($this->currentStep === 5) {
            foreach ($this->actividades as $index => $actividad) {
                $responsableEnLista = collect($actividad['participantes'] ?? [])->contains(fn ($row) => (bool) ($row['es_responsable'] ?? false));
                if (($actividad['estado'] ?? null) === 'ejecutada' && blank($actividad['responsable'] ?? null) && ! $responsableEnLista) {
                    throw ValidationException::withMessages(["actividades.$index.responsable" => 'Indique un responsable principal para la actividad ejecutada.']);
                }
            }
        }
        if ($this->currentStep === 3) $this->validarJustificacionesParticipacion();
    }

    private function validarJustificacionesParticipacion(): void
    {
        $errores = [];
        foreach ($this->gruposEstudiantesConRegistro as $grupo) {
            $tienePlanificacion = (int) $grupo['total_planificado'] > 0;
            $tienePendientes = (int) $grupo['hombres_pendientes'] > 0 || (int) $grupo['mujeres_pendientes'] > 0;
            if ($tienePlanificacion && $tienePendientes && blank($grupo['observacion_no_cumplimiento'] ?? null)) {
                $errores['gruposEstudiantes.'.$grupo['indice_formulario'].'.observacion_no_cumplimiento'] = 'Debe explicar por qué no se agregó la totalidad de estudiantes planificados.';
            }
        }
        if ($errores) throw ValidationException::withMessages($errores);
    }

    private function syncRows(string $relation, array $rows, array $fields): void
    {
        $ids = [];
        foreach ($rows as $index => $row) {
            $data = Arr::only($row, $fields);
            $id = isset($row['id']) ? (int) $row['id'] : null;
            $record = $id ? $this->informe->{$relation}()->whereKey($id)->first() : null;
            $record ? $record->update($data) : $record = $this->informe->{$relation}()->create($data);
            $ids[] = $record->id;
            $this->setRelationRowId($relation, $index, $record->id);
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
            'estudiantes.*.informe_final_grupo_estudiante_id' => ['required','integer',Rule::exists('informe_final_grupos_estudiantes','id')->where(fn ($query) => $query->where('informe_final_proyecto_id',$this->informe->id))],
            'estudiantes.*.sexo' => ['required', Rule::in(['Masculino','Femenino'])],
            'voluntarios.*.sexo' => ['required', Rule::in(['Masculino','Femenino'])],
            'voluntarios.*.tipo' => [Rule::in(['profesor_hora','pas','profesor_permanente','egresado'])],
            'gruposEstudiantes.*.observacion_no_cumplimiento' => ['nullable','string','min:10','max:1000'],
            'general.observacion_voluntarios_no_incorporados' => ['nullable','string','min:10','max:1000'],
            'contrapartes.*.tipo' => [Rule::in(['gobierno_nacional','gobierno_municipal','ong','sociedad_civil','sector_privado','internacional'])],
            'resultados.*.porcentaje_cumplimiento' => ['numeric','between:0,100'],
            'resultados.*.estado' => [Rule::in(['alcanzado','parcialmente_alcanzado','no_alcanzado','no_aplica'])],
            'actividades.*.estado' => [Rule::in(['ejecutada','parcial','no_ejecutada'])],
            'actividades.*.origen' => [Rule::in(['planificada','emergente'])],
            'actividades.*.participantes.*.tipo' => [Rule::in(['docente','estudiante','voluntario','externo'])],
            'actividades.*.participantes.*.horas_dedicadas' => $nonNegative,
            'ods.*.nivel_contribucion' => [Rule::in(['directa','indirecta'])],
            'presupuesto.*.fuente' => [Rule::in(['UNAH','CONTRAPARTE'])],
            'anexos.*.categoria' => [Rule::in(['documento_general','instrumento_contraparte','fotografia'])],
            'anexos.*.informe_final_contraparte_id' => ['required_if:anexos.*.categoria,instrumento_contraparte','nullable','integer',Rule::exists('informe_final_contrapartes','id')->where(fn ($query) => $query->where('informe_final_proyecto_id',$this->informe->id))],
            'anexoArchivos.*' => ['nullable','file','mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png','max:10240'],
        ];
    }

    private function debeAutoguardar(string $propertyName): bool
    {
        foreach (['general.','beneficiarios.','gruposEstudiantes.','equipo.','cooperacion.','estudiantes.','voluntarios.','contrapartes.','resultados.','actividades.','accionesNoEjecutadas.','accionesEmergentes.','ods.','presupuesto.','anexos.'] as $root) {
            if (str_starts_with($propertyName, $root)) return true;
        }
        return false;
    }

    public function autoGuardarCampo(string $propertyName): void
    {
        if ($this->autoGuardando) return;
        $this->authorizeSensitive();
        $this->autoGuardando = true;
        $this->estadoGuardado = 'guardando';

        try {
            $rules = $this->draftRules();
            if ($this->reglaAplica($propertyName, $rules)) {
                $this->validateOnly($propertyName, $rules);
            }
            DB::transaction(function () use ($propertyName) {
                [$root, $index] = array_pad(explode('.', $propertyName, 3), 2, null);
                if ($root === 'general') {
                    $field = explode('.', $propertyName)[1] ?? null;
                    if ($field && ! in_array($field, ['estado','fecha_registro'], true) && array_key_exists($field, $this->general)) {
                        $this->informe->update([$field => $this->general[$field], 'updated_by' => auth()->id()]);
                    }
                } elseif ($root === 'beneficiarios') {
                    $field = explode('.', $propertyName)[1] ?? null;
                    if ($field && array_key_exists($field, $this->beneficiarios)) {
                        $this->informe->beneficiarios()->updateOrCreate([], [$field => $this->beneficiarios[$field]]);
                    }
                } elseif (is_numeric($index)) {
                    $this->guardarFilaAutogardadaSinTransaccion($root, (int) $index);
                }
            }, 3);
            $this->estadoGuardado = 'guardado';
        } catch (ValidationException $e) {
            $this->estadoGuardado = 'error';
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            $this->estadoGuardado = 'error';
        } finally {
            $this->autoGuardando = false;
        }
    }

    private function guardarFilaAutoguardado(string $grupo, ?int $index): void
    {
        if ($index === null) return;
        try {
            $this->estadoGuardado = 'guardando';
            DB::transaction(fn () => $this->guardarFilaAutogardadaSinTransaccion($grupo, $index), 3);
            $this->estadoGuardado = 'guardado';
        } catch (\Throwable $e) {
            report($e);
            $this->estadoGuardado = 'error';
        }
    }

    private function guardarFilaAutogardadaSinTransaccion(string $grupo, int $index): void
    {
        $config = $this->configColecciones()[$grupo] ?? null;
        $row = $this->{$grupo}[$index] ?? null;
        if ($grupo === 'estudiantes' && is_array($row)) {
            $row = $this->normalizarAsociacionEstudiante($row, $index);
            $this->estudiantes[$index] = $row;
        }
        if (! $config || ! is_array($row)) return;
        [$relation, $fields] = $config;
        $id = isset($row['id']) ? (int) $row['id'] : null;
        $record = $id ? $this->informe->{$relation}()->whereKey($id)->first() : null;
        $data = Arr::only($row, $fields);
        $record ? $record->update($data) : $record = $this->informe->{$relation}()->create($data);
        $this->{$grupo}[$index]['id'] = $record->id;
        if ($grupo === 'actividades') {
            $this->syncParticipantesActividad($this->actividades[$index], $index);
        }
    }

    private function normalizarAsociacionEstudiante(array $row, int $index): array
    {
        if (! empty($row['id'])) {
            $persistido = $this->informe->estudiantes()->whereKey($row['id'])->first();
            if ($persistido) $row['informe_final_grupo_estudiante_id'] = $persistido->informe_final_grupo_estudiante_id;
        }
        $grupo = $this->informe->gruposEstudiantes()
            ->whereKey($row['informe_final_grupo_estudiante_id'] ?? null)
            ->first();
        if (! $grupo) {
            throw ValidationException::withMessages([
                "estudiantes.$index.informe_final_grupo_estudiante_id" => 'El estudiante debe pertenecer a un grupo válido de este Informe Final.',
            ]);
        }
        $row['informe_final_grupo_estudiante_id'] = $grupo->id;
        $row['tipo_participacion'] = $grupo->tipo_participacion;

        return $row;
    }

    private function eliminarFilaPersistida(string $grupo, array $row): void
    {
        $config = $this->configColecciones()[$grupo] ?? null;
        if ($config && ! empty($row['id'])) {
            $this->informe->{$config[0]}()->whereKey($row['id'])->delete();
        }
    }

    private function configColecciones(): array
    {
        return [
            'gruposEstudiantes'=>['gruposEstudiantes',['observacion_no_cumplimiento']],
            'equipo'=>['equipoDocente',['empleado_id','nombre','numero_empleado','correo','categoria','departamento','sexo','horas_dedicadas','estado_participacion','observacion_no_participacion','removido_en','removido_por']],
            'cooperacion'=>['cooperacion',['nombre','pasaporte','correo','pais','universidad','horas_dedicadas','estado_participacion','observacion_no_participacion','removido_en','removido_por']],
            'estudiantes'=>['estudiantes',['informe_final_grupo_estudiante_id','estudiante_id','nombre','sexo','numero_cuenta','carrera','correo','tipo_participacion','horas_dedicadas','cantidad','origen','estado_participacion','observacion_no_participacion','removido_en','removido_por']],
            'voluntarios'=>['voluntarios',['empleado_id','nombre','sexo','identidad','departamento','tipo','horas_dedicadas','estado_participacion','observacion_no_participacion','removido_en','removido_por']],
            'contrapartes'=>['contrapartes',['entidad_contraparte_id','existe_apoyo','nombre','tipo','contacto','correo','cargo','telefono','tipo_instrumento','compromisos_asumidos','compromisos_cumplidos','territorio','aporte_monetario','aporte_especie','documento_respaldo']],
            'resultados'=>['resultados',['resultado_esperado_id','objetivo_especifico','resultado_planificado','indicador_propuesto','meta_numerica','unidad_medida','valor_alcanzado','porcentaje_cumplimiento','estado','producto_logrado','observaciones']],
            'actividades'=>['actividades',['actividad_id','actividad_planificada','actividad_realizada','responsable','fecha_inicial','fecha_final','horas_dedicadas','medio_verificacion','estado','origen']],
            'accionesNoEjecutadas'=>['accionesNoEjecutadas',['resultado_previsto','actividad_planificada','explicacion','afectacion_proyecto','responsable','impacto']],
            'accionesEmergentes'=>['accionesEmergentes',['informe_final_resultado_id','producto_logrado','actividad_realizada','justificacion','responsables','fecha','horas']],
            'ods'=>['ods',['ods_id','meta_contribuye_id','meta_ods','descripcion_aporte','evidencia','nivel_contribucion']],
            'presupuesto'=>['presupuestoDetalles',['informe_final_contraparte_id','fuente','concepto','unidad','cantidad','costo_unitario','origen_fondos']],
            'anexos'=>['anexos',['informe_final_resultado_id','informe_final_actividad_id','informe_final_contraparte_id','instrumento_formalizacion_id','tipo','categoria','descripcion','archivo','nombre_archivo','tamano_bytes','origen','enlace','fecha','orden']],
        ];
    }

    private function syncParticipantesActividad(array &$actividad, int $actividadIndex): void
    {
        $record = ! empty($actividad['id']) ? $this->informe->actividades()->whereKey($actividad['id'])->first() : null;
        if (! $record) return;
        $rows = $actividad['participantes'] ?? [];
        $responsables = collect($rows)->filter(fn ($row) => ! empty($row['es_responsable']));
        if ($rows && $responsables->isEmpty()) {
            $firstIndex = collect($rows)->sortBy('orden')->keys()->first();
            $rows[$firstIndex]['es_responsable'] = true;
        }
        if ($responsables->count() > 1) {
            $firstIndex = $responsables->sortBy('orden')->keys()->first();
            foreach ($rows as $index => &$row) $row['es_responsable'] = $index === $firstIndex;
            unset($row);
        }
        $responsable = collect($rows)->first(fn ($row) => ! empty($row['es_responsable']));
        $actividad['responsable'] = $responsable['nombre'] ?? '';
        $record->update(['responsable' => $actividad['responsable']]);
        $ids = [];
        $seen = [];
        foreach ($rows as $index => &$row) {
            $key = $this->claveParticipante($row);
            if ($key !== 'externo:' && in_array($key, $seen, true)) {
                throw ValidationException::withMessages(["actividades.$actividadIndex.participantes.$index.nombre" => 'La persona está duplicada en la actividad.']);
            }
            $seen[] = $key;
            $this->validarOrigenParticipante($row, $actividadIndex, $index);
            $participant = ! empty($row['id']) ? $record->participantes()->whereKey($row['id'])->first() : null;
            $data = Arr::only($row, ['tipo','empleado_id','informe_final_estudiante_id','informe_final_voluntario_id','nombre','rol','horas_dedicadas','es_responsable','orden']);
            $participant ? $participant->update($data) : $participant = $record->participantes()->create($data);
            $row['id'] = $participant->id;
            $ids[] = $participant->id;
        }
        unset($row);
        $actividad['participantes'] = $rows;
        $ids ? $record->participantes()->whereNotIn('id', $ids)->delete() : $record->participantes()->delete();
    }

    private function resolverParticipante(string $tipo, ?string $id): array
    {
        $base = ['id'=>null,'tipo'=>$tipo,'empleado_id'=>null,'informe_final_estudiante_id'=>null,'informe_final_voluntario_id'=>null,'nombre'=>'','rol'=>'Participante','horas_dedicadas'=>0,'es_responsable'=>false];
        if ($tipo === 'docente') {
            $row = collect($this->equipo)->first(fn ($item) => (string) ($item['empleado_id'] ?? '') === (string) $id);
            abort_unless($row, 422);
            return array_replace($base, ['empleado_id'=>(int)$id,'nombre'=>$row['nombre']]);
        }
        if ($tipo === 'estudiante') {
            $row = collect($this->estudiantes)->firstWhere('id', (int) $id);
            abort_unless($row, 422);
            return array_replace($base, ['informe_final_estudiante_id'=>(int)$id,'nombre'=>$row['nombre']]);
        }
        if ($tipo === 'voluntario') {
            $row = collect($this->voluntarios)->firstWhere('id', (int) $id);
            abort_unless($row, 422);
            return array_replace($base, ['informe_final_voluntario_id'=>(int)$id,'nombre'=>$row['nombre']]);
        }
        abort_unless($tipo === 'externo', 422);
        return array_replace($base, ['tipo'=>'externo']);
    }

    private function validarOrigenParticipante(array $row, int $activityIndex, int $participantIndex): void
    {
        $valid = match ($row['tipo'] ?? null) {
            'docente' => collect($this->equipo)->contains(fn ($item) => (int) ($item['empleado_id'] ?? 0) === (int) ($row['empleado_id'] ?? 0)),
            'estudiante' => collect($this->estudiantes)->contains(fn ($item) => (int) ($item['id'] ?? 0) === (int) ($row['informe_final_estudiante_id'] ?? 0)),
            'voluntario' => collect($this->voluntarios)->contains(fn ($item) => (int) ($item['id'] ?? 0) === (int) ($row['informe_final_voluntario_id'] ?? 0)),
            'externo' => filled($row['nombre'] ?? null),
            default => false,
        };
        if (! $valid) throw ValidationException::withMessages(["actividades.$activityIndex.participantes.$participantIndex.nombre" => 'El participante no pertenece a este proyecto o no tiene nombre.']);
        if ((float) ($row['horas_dedicadas'] ?? 0) < 0) throw ValidationException::withMessages(["actividades.$activityIndex.participantes.$participantIndex.horas_dedicadas" => 'Las horas no pueden ser negativas.']);
    }

    private function mismaPersona(array $a, array $b): bool
    {
        return $this->claveParticipante($a) === $this->claveParticipante($b) && $this->claveParticipante($a) !== 'externo:';
    }

    private function claveParticipante(array $row): string
    {
        return match ($row['tipo'] ?? 'externo') {
            'docente' => 'docente:'.($row['empleado_id'] ?? ''),
            'estudiante' => 'estudiante:'.($row['informe_final_estudiante_id'] ?? ''),
            'voluntario' => 'voluntario:'.($row['informe_final_voluntario_id'] ?? ''),
            default => 'externo:'.mb_strtolower(trim((string) ($row['nombre'] ?? ''))),
        };
    }

    private function sexoCanonico(mixed $value): ?string
    {
        $value = mb_strtolower(trim((string) $value));
        return match ($value) {
            'm', 'masculino', 'male', 'hombre' => 'masculino',
            'f', 'femenino', 'female', 'mujer' => 'femenino',
            default => null,
        };
    }

    private function reglaAplica(string $propertyName, array $rules): bool
    {
        foreach (array_keys($rules) as $rule) {
            $pattern = '/^'.str_replace(['\\*','\.'], ['[^.]+','\\.'], preg_quote($rule, '/')).'$/';
            if (preg_match($pattern, $propertyName)) return true;
        }
        return false;
    }

    private function setRelationRowId(string $relation, int $index, int $id): void
    {
        $property = collect($this->configColecciones())->search(fn ($config) => $config[0] === $relation);
        if ($property && isset($this->{$property}[$index])) $this->{$property}[$index]['id'] = $id;
    }

    private function authorizeSensitive(): void
    {
        $informe = $this->informe->fresh();
        if (! app(InformeFinalProyectoWorkflowService::class)->puedeContinuarInformeFinal($informe, auth()->user())) {
            throw new AuthorizationException('No está autorizado para modificar este informe final.');
        }
    }

}
