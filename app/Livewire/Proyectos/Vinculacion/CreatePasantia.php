<?php

namespace App\Livewire\Proyectos\Vinculacion;

use App\Models\Pasantia;
use App\Models\JornadaLaboral;
use App\Models\Personal\CategoriaEmpleado;
use App\Models\Personal\Empleado;
use App\Models\UnidadAcademica\DepartamentoAcademico;
use App\Models\UnidadAcademica\Carrera;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Support\Notification;
use App\Services\Integraciones\IntegracionApiService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Illuminate\Validation\Rule;

class CreatePasantia extends Component
{
    public ?int $registroId = null;
    public int $pasoActual = 1;
    public bool $modoEdicion = false;
    public bool $autoguardadoActivo = true;
    public array $form = [];
    public bool $buscandoEstudiante = false;

    public const PASOS = [
        1 => 'Estudiante', 2 => 'Información de la pasantía', 3 => 'Experiencia',
        4 => 'Institución', 5 => 'Contacto directo', 6 => 'Supervisor',
        7 => 'Firmas', 8 => 'Adjuntos',
    ];

    public function mount(?int $id = null): void
    {
        $this->inicializarFormulario();

        if ($id) {
            $registro = Pasantia::findOrFail($id);
            abort_unless($this->puedeVer($registro), 403);
            $this->registroId = $registro->id;
            $this->modoEdicion = true;
            $this->form = array_replace($this->form, $registro->only(array_keys($this->form)));
        }
    }

    public function updatedForm($value, $key): void
    {
        if ($this->autoguardadoActivo) {
            $this->guardarBorrador(false);
        }
    }

    public function siguiente(): void
    {
        $this->validate($this->reglasPaso($this->pasoActual), [], $this->atributos());
        $this->guardarBorrador(false);
        $this->pasoActual = min(8, $this->pasoActual + 1);
    }

    public function buscarEstudiante(IntegracionApiService $integraciones): void
    {
        $this->resetErrorBag('form.numero_cuenta');
        $cuenta = preg_replace('/\s+/u', '', trim((string) ($this->form['numero_cuenta'] ?? '')));

        if ($cuenta === '' || ! ctype_digit($cuenta)) {
            $this->addError('form.numero_cuenta', 'Ingrese un número de cuenta válido.');
            return;
        }

        $this->buscandoEstudiante = true;

        try {
            $resultado = $integraciones->buscarEstudiantePorCuenta($cuenta);
            if (! ($resultado['ok'] ?? false)) {
                $this->addError('form.numero_cuenta', $resultado['mensaje'] ?? 'No se encontró el estudiante.');
                return;
            }

            $datos = $resultado['datos'] ?? [];
            $this->form['numero_cuenta'] = $datos['numero_cuenta'] ?? $cuenta;
            $this->form['nombre_estudiante'] = $datos['nombre_completo']
                ?? trim(($datos['nombres'] ?? '').' '.($datos['apellidos'] ?? ''));
            $this->form['carrera'] = $datos['carrera'] ?? $datos['carrera_nombre'] ?? $this->form['carrera'];
            $this->form['facultad_centro'] = $datos['centro_nombre'] ?? $this->form['facultad_centro'];
            $this->form['correo_institucional'] = $datos['correo_institucional'] ?? $datos['correo'] ?? $this->form['correo_institucional'];
        } catch (\Throwable $e) {
            $this->addError('form.numero_cuenta', 'No fue posible consultar la integración de estudiantes.');
        } finally {
            $this->buscandoEstudiante = false;
        }
    }

    public function buscarDocente(): void
    {
        $this->resetErrorBag('form.numero_empleado_docente');
        $numero = preg_replace('/\s+/u', '', trim((string) ($this->form['numero_empleado_docente'] ?? '')));

        if ($numero === '' || ! ctype_digit($numero)) {
            $this->addError('form.numero_empleado_docente', 'Ingrese un número de empleado válido.');
            return;
        }

        $docente = Empleado::query()->with(['user', 'categoria', 'departamento_academico'])
            ->where('numero_empleado', $numero)->first();

        if (! $docente) {
            $this->addError('form.numero_empleado_docente', 'No se encontró un empleado con ese número.');
            return;
        }

        $this->form['numero_empleado_docente'] = $docente->numero_empleado;
        $this->form['nombre_docente_supervisor'] = $docente->nombre_completo;
        $this->form['celular_docente'] = $docente->celular;
        $this->form['correo_docente'] = $docente->user?->email;
        $this->form['categoria_docente'] = $docente->categoria?->nombre;
        $this->form['departamento_docente'] = $docente->departamento_academico?->nombre;
        $this->form['jornada_laboral_docente'] = $docente->jornada_laboral;
    }

    public function anterior(): void
    {
        $this->pasoActual = max(1, $this->pasoActual - 1);
    }

    public function irAPaso(int $paso): void
    {
        $this->pasoActual = max(1, min(8, $paso));
    }

    public function guardarBorrador(bool $notificar = true): void
    {
        $payload = $this->normalizarPayload($this->form);
        $payload['updated_by'] = Auth::id();

        if ($this->registroId) {
            $registro = Pasantia::findOrFail($this->registroId);
            abort_unless($this->puedeEditar($registro), 403);
            $registro->fill($payload)->save();
        } else {
            $payload['created_by'] = Auth::id();
            $payload['estado'] = 'borrador';
            $payload['proceso'] = Pasantia::PROCESO_FLUJO;
            $payload['tipo_accion_id'] = DB::table('vinculacion_tipos_accion')
                ->where('codigo', 'PASANTIAS')->where('activo', true)->value('id');
            $payload['flujo_aprobacion_id'] = DB::table('flujos_aprobacion')
                ->where('codigo', 'PASANTIAS_FORM_DVUS_013')
                ->where('proceso', Pasantia::PROCESO_FLUJO)
                ->where('codigo_formulario', Pasantia::FORMULARIO)
                ->where('activo', true)->value('id');
            $payload['codigo_registro'] = $this->generarCodigo();
            $registro = Pasantia::create($payload);
            $this->registroId = $registro->id;
            $this->modoEdicion = true;
        }

        $this->form = array_replace($this->form, $registro->only(array_keys($this->form)));

        if ($notificar) {
            Notification::make()->title('Borrador guardado')->body('La información de la pasantía se guardó correctamente.')->success()->send();
        }
    }

    public function render(): View
    {
        $facultadesCentros = FacultadCentro::query()->orderBy('nombre')->pluck('nombre', 'nombre');
        $facultadId = FacultadCentro::query()->where('nombre', $this->form['facultad_centro'] ?? '')->value('id');
        $carreras = $facultadId
            ? Carrera::query()
                ->where(function ($query) use ($facultadId) {
                    $query->where('facultad_centro_id', $facultadId)
                        ->orWhereHas('facultadCentros', fn ($q) => $q->where('centro_facultad.id', $facultadId));
                })->orderBy('nombre')->pluck('nombre', 'nombre')
            : collect();
        $categoriasDocente = CategoriaEmpleado::query()->orderBy('nombre')->pluck('nombre', 'nombre');
        $departamentosAcademicos = DepartamentoAcademico::query()->orderBy('nombre')->pluck('nombre', 'nombre');
        $jornadasLaborales = JornadaLaboral::query()->where('activo', true)->orderBy('orden')->orderBy('hora_inicio')->get()->pluck('etiqueta', 'etiqueta');

        return view('livewire.proyectos.vinculacion.create-pasantia', [
            'pasos' => self::PASOS,
            'facultadesCentros' => $facultadesCentros,
            'carreras' => $carreras,
            'categoriasDocente' => $categoriasDocente,
            'departamentosAcademicos' => $departamentosAcademicos,
            'jornadasLaborales' => $jornadasLaborales,
        ]);
    }

    protected function inicializarFormulario(): void
    {
        $this->form = array_fill_keys([
            'fecha_registro', 'facultad_centro', 'escuela_departamento', 'carrera', 'numero_cuenta', 'nombre_estudiante',
            'celular_estudiante', 'correo_institucional', 'correo_personal', 'tipo_pasantia', 'fecha_inicio',
            'fecha_finalizacion', 'duracion_semanas', 'total_horas', 'horas_semanales', 'pasantia_obligatoria',
            'otorga_creditos', 'cantidad_creditos', 'modalidad_ejecucion', 'descripcion_experiencia', 'descripcion_cargo',
            'resumen_responsabilidades', 'area_departamento', 'area_conocimiento', 'asignaturas', 'codigo_asignatura',
            'nombre_asignatura', 'descripcion_conocimientos_teoricos', 'habilidades_desarrollar', 'pasantia_remunerada',
            'monto_remuneracion', 'nombre_institucion', 'direccion_institucion', 'ciudad_institucion', 'pais_institucion',
            'representante_legal', 'telefono_representante', 'correo_rrhh', 'tipo_institucion', 'sector_institucion',
            'compromisos_institucion', 'nombre_contacto_directo', 'celular_contacto_directo', 'correo_contacto_directo',
            'cargo_contacto_directo', 'grado_academico_contacto_directo', 'tipo_instrumento', 'nombre_docente_supervisor',
            'numero_empleado_docente', 'celular_docente', 'correo_docente', 'categoria_docente', 'departamento_docente',
            'jornada_laboral_docente', 'ubicacion_cubiculo_docente', 'nombre_firma_coordinador', 'firma_coordinador',
            'nombre_firma_supervisor', 'firma_supervisor', 'nombre_firma_estudiante', 'firma_estudiante',
            'adjunta_carta_formalizacion', 'archivo_carta_formalizacion', 'adjunta_convenio_marco', 'archivo_convenio_marco',
        ], null);

        $this->form['fecha_registro'] = now()->format('Y-m-d');
    }

    protected function normalizarPayload(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if ($value === '') {
                $payload[$key] = null;
            }
        }

        return $payload;
    }

    protected function reglasPaso(int $paso): array
    {
        return match ($paso) {
            1 => ['form.fecha_registro' => ['nullable', 'date'], 'form.facultad_centro' => ['nullable', 'string', 'max:255'], 'form.carrera' => ['nullable', 'string', 'max:255'], 'form.correo_institucional' => ['nullable', 'email', 'max:255'], 'form.correo_personal' => ['nullable', 'email', 'max:255']],
            2 => ['form.tipo_pasantia' => ['nullable', 'string', Rule::in(['Pasantía profesional', 'Pasantía académica'])], 'form.fecha_inicio' => ['nullable', 'date'], 'form.fecha_finalizacion' => ['nullable', 'date', 'after_or_equal:form.fecha_inicio'], 'form.duracion_semanas' => ['nullable', 'integer', 'min:0'], 'form.total_horas' => ['nullable', 'integer', 'min:0'], 'form.horas_semanales' => ['nullable', 'integer', 'min:0'], 'form.cantidad_creditos' => ['nullable', 'numeric', 'min:0'], 'form.modalidad_ejecucion' => ['nullable', 'string', Rule::in(['100% presencial', 'Híbrida', 'Teletrabajo'])], 'form.pasantia_obligatoria' => ['nullable', Rule::in(['Sí', 'No'])], 'form.otorga_creditos' => ['nullable', Rule::in(['Sí', 'No'])]],
            3 => ['form.monto_remuneracion' => ['nullable', 'numeric', 'min:0'], 'form.pasantia_remunerada' => ['nullable', Rule::in(['Sí', 'No'])]],
            4 => ['form.correo_rrhh' => ['nullable', 'email', 'max:255'], 'form.tipo_institucion' => ['nullable', Rule::in(['Pública', 'Privada', 'ONG', 'Organismo internacional'])], 'form.sector_institucion' => ['nullable', Rule::in(['Educación', 'Gobierno', 'Empresa privada', 'Sociedad civil'])]],
            5 => ['form.correo_contacto_directo' => ['nullable', 'email', 'max:255'], 'form.tipo_instrumento' => ['nullable', Rule::in(['carta_formal_solicitud', 'carta_intenciones', 'convenio_marco'])], 'form.grado_academico_contacto_directo' => ['nullable', Rule::in(['Secundaria completa', 'Licenciatura', 'Maestría', 'Doctorado', 'Postdoctorado'])]],
            6 => ['form.correo_docente' => ['nullable', 'email']],
            8 => ['form.adjunta_carta_formalizacion' => ['nullable', Rule::in(['Sí', 'No'])], 'form.adjunta_convenio_marco' => ['nullable', Rule::in(['Sí', 'No'])]],
            default => [],
        };
    }

    protected function atributos(): array
    {
        return [
            'form.fecha_registro' => 'fecha de registro', 'form.fecha_inicio' => 'fecha de inicio',
            'form.fecha_finalizacion' => 'fecha de finalización', 'form.total_horas' => 'total de horas',
            'form.correo_institucional' => 'correo institucional', 'form.correo_personal' => 'correo personal',
            'form.correo_rrhh' => 'correo de recursos humanos', 'form.correo_contacto_directo' => 'correo del contacto directo',
            'form.correo_docente' => 'correo del docente supervisor',
        ];
    }

    protected function puedeVer(Pasantia $registro): bool
    {
        return $registro->created_by === Auth::id() || Auth::user()?->can('proyectos.historial') || Auth::user()?->can('docente.proyectos');
    }

    protected function puedeEditar(Pasantia $registro): bool
    {
        return in_array($registro->estado, ['borrador', 'subsanacion'], true)
            && $registro->created_by === Auth::id();
    }

    protected function generarCodigo(): string
    {
        do {
            $codigo = 'PAS-'.now()->format('Y').'-'.str()->upper(str()->random(6));
        } while (Pasantia::where('codigo_registro', $codigo)->exists());

        return $codigo;
    }
}
