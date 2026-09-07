<?php

namespace App\Livewire\Proyectos\Vinculacion;

use App\Models\Pasantia;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CreatePasantia extends Component
{
    public ?int $registroId = null;
    public int $pasoActual = 1;
    public bool $modoEdicion = false;
    public bool $autoguardadoActivo = true;
    public array $form = [];

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
        return view('livewire.proyectos.vinculacion.create-pasantia', ['pasos' => self::PASOS]);
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
            1 => ['form.fecha_registro' => ['nullable', 'date'], 'form.correo_institucional' => ['nullable', 'email'], 'form.correo_personal' => ['nullable', 'email']],
            2 => ['form.fecha_inicio' => ['nullable', 'date'], 'form.fecha_finalizacion' => ['nullable', 'date'], 'form.duracion_semanas' => ['nullable', 'integer', 'min:0'], 'form.total_horas' => ['nullable', 'integer', 'min:0'], 'form.horas_semanales' => ['nullable', 'integer', 'min:0']],
            3 => ['form.monto_remuneracion' => ['nullable', 'numeric', 'min:0']],
            4 => ['form.correo_rrhh' => ['nullable', 'email']],
            5 => ['form.correo_contacto_directo' => ['nullable', 'email']],
            6 => ['form.correo_docente' => ['nullable', 'email']],
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
