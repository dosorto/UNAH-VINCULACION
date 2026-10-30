<?php

namespace App\Livewire\Personal\Perfil;

use App\Models\JornadaLaboral;
use App\Models\Personal\CategoriaEmpleado;
use App\Models\Personal\Empleado;
use App\Models\Personal\EmpleadoCodigoInvestigacion;
use App\Models\Personal\EmpleadoProyecto;
use App\Models\Personal\FirmaSelloEmpleado;
use App\Models\Proyecto\Proyecto;
use App\Models\UnidadAcademica\Carrera;
use App\Models\UnidadAcademica\DepartamentoAcademico;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\User;
use App\Services\Personal\ProcesadorFirma;
use App\Support\Notification;
use App\Support\ProfileCompletion;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Spatie\Permission\Models\Role;

class EditPerfilDocente extends Component
{
    use WithFileUploads;

    public User $record;

    // User fields
    public string $name = '';

    public string $email = '';

    // Empleado fields
    public string $nombre_completo = '';

    public string $numero_empleado = '';

    public string $celular = '';

    /** Etiqueta del catálogo de jornadas laborales («07:00 - 15:00»). */
    public string $jornada_laboral = '';

    public string $sexo = '';

    public ?int $categoria_id = null;

    public ?int $centro_facultad_id = null;

    public ?int $departamento_academico_id = null;

    public ?int $carrera_id = null;

    // File uploads
    public $firmaUpload = null;

    public $selloUpload = null;

    // Add codigo modal
    public bool $addCodigoModal = false;

    public string $add_codigo = '';

    public string $add_nombre = '';

    public string $add_rol = '';

    public string $add_descripcion = '';

    public int $add_año;

    public string $tiene_proyectos_previos = '';

    public bool $completandoPerfil = false;

    /** Solo se pide si falta (Microsoft no siempre lo envía); el que ya existe no se cambia. */
    #[Locked]
    public bool $numeroEmpleadoEditable = false;

    public function mount(): void
    {
        $this->record = Auth::user();
        $this->completandoPerfil = ProfileCompletion::isRequired($this->record);
        $this->name = $this->record->name;
        $this->email = $this->record->email;

        $empleado = $this->record->empleado;
        if ($empleado) {
            $this->nombre_completo = $empleado->nombre_completo ?? '';
            $this->numero_empleado = $empleado->numero_empleado ?? '';
            $this->numeroEmpleadoEditable = $this->completandoPerfil && blank($empleado->numero_empleado);
            $this->celular = $empleado->celular ?? '';
            // Un texto que no está en el catálogo (p. ej. «Tiempo completo», escrito a mano)
            // se muestra igual cuando el perfil ya está completo, pero al completarlo hay que
            // elegir una jornada del catálogo.
            $jornada = (string) ($empleado->jornada_laboral ?? '');
            $this->jornada_laboral = ! $this->completandoPerfil || in_array($jornada, JornadaLaboral::etiquetasActivas(), true)
                ? $jornada
                : '';
            $this->sexo = $empleado->sexo ?? '';
            $this->categoria_id = $empleado->categoria_id;
            $this->centro_facultad_id = $empleado->centro_facultad_id;
            $this->departamento_academico_id = $empleado->departamento_academico_id;
            $this->carrera_id = $empleado->carrera_id;
            $this->tiene_proyectos_previos = $empleado->codigosInvestigacion()->exists()
                ? 'si'
                : ($this->completandoPerfil ? '' : 'no');
        }

        $this->add_año = (int) date('Y');
    }

    public function updatingCentroFacultadId(): void
    {
        $this->departamento_academico_id = null;
        $this->carrera_id = null;
    }

    public function updatingDepartamentoAcademicoId(): void
    {
        $this->carrera_id = null;
    }

    public function subirFirma(): void
    {
        $this->ensureProfileCanBeEdited();

        $empleado = $this->record->empleado;

        if (! $empleado) {
            throw ValidationException::withMessages([
                'firmaUpload' => 'Primero debe existir el perfil de empleado.',
            ]);
        }

        $this->validate(['firmaUpload' => 'required|image|max:2048']);
        $this->storeFirmaSello($empleado, 'firma', $this->firmaUpload);

        $this->firmaUpload = null;
        Notification::make()->title('Firma subida')->body('La firma ha sido guardada correctamente.')->success()->send();
    }

    public function quitarFondoFirma(): void
    {
        $this->ensureProfileCanBeEdited();
        $this->resetValidation('firmaProcesada');
        $empleado = $this->record->empleado;
        $firma = $empleado?->firma()->first();

        if (! $firma || ! Storage::disk('public')->exists($firma->ruta_storage)) {
            $this->addError('firmaProcesada', 'No se encontró la firma guardada.');

            return;
        }

        try {
            $ruta = app(ProcesadorFirma::class)->guardar(Storage::disk('public')->path($firma->ruta_storage));
        } catch (\RuntimeException $exception) {
            report($exception);
            $this->addError('firmaProcesada', 'No se pudo procesar la firma. Inténtelo de nuevo o contacte al administrador.');

            return;
        }

        try {
            DB::transaction(function () use ($empleado, $firma, $ruta): void {
                $actual = $empleado->firma()->lockForUpdate()->first();
                if (! $actual || $actual->id !== $firma->id) {
                    throw ValidationException::withMessages([
                        'firmaProcesada' => 'La firma cambió durante el procesamiento. Inténtelo de nuevo.',
                    ]);
                }
                FirmaSelloEmpleado::create([
                    'empleado_id' => $empleado->id,
                    'ruta_storage' => $ruta,
                    'tipo' => 'firma',
                ]);
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($ruta);
            throw $exception;
        }

        $empleado->unsetRelation('firma');
        Notification::make()->title('Firma actualizada')->body('Se guardó la firma sin fondo.')->success()->send();
    }

    public function subirSello(): void
    {
        $this->ensureProfileCanBeEdited();

        $empleado = $this->record->empleado;

        if (! $empleado) {
            throw ValidationException::withMessages([
                'selloUpload' => 'Primero debe existir el perfil de empleado.',
            ]);
        }

        $this->validate(['selloUpload' => 'required|image|max:2048']);
        $this->storeFirmaSello($empleado, 'sello', $this->selloUpload);

        $this->selloUpload = null;
        Notification::make()->title('Sello subido')->body('El sello ha sido guardado correctamente.')->success()->send();
    }

    public function openAddCodigo(): void
    {
        $this->ensureProfileCanBeEdited();

        $this->reset(['add_codigo', 'add_nombre', 'add_rol', 'add_descripcion']);
        $this->add_año = (int) date('Y');
        $this->addCodigoModal = true;
    }

    public function agregarCodigo(): void
    {
        $this->ensureProfileCanBeEdited();

        $this->validate([
            'add_codigo' => 'required|string|max:50',
            'add_nombre' => 'required|string|max:255',
            'add_rol' => 'required|in:coordinador,integrante',
            'add_año' => 'required|integer|min:2000|max:2100',
        ]);

        $proyectoExistente = Proyecto::where('codigo_proyecto', $this->add_codigo)->first();

        if ($proyectoExistente) {
            $empleadoYaRegistrado = EmpleadoProyecto::where('empleado_id', $this->record->empleado->id)
                ->where('proyecto_id', $proyectoExistente->id)->exists();

            if ($empleadoYaRegistrado) {
                Notification::make()->title('Código ya registrado')->body('Este código ya está registrado y usted ya participa en él.')->warning()->send();
                $this->addCodigoModal = false;

                return;
            }

            Notification::make()->title('Código de proyecto existente')->body('Este código corresponde a un proyecto existente. Su solicitud será verificada.')->info()->send();
        }

        try {
            $this->record->empleado->codigosInvestigacion()->create([
                'codigo_proyecto' => $this->add_codigo,
                'nombre_proyecto' => $this->add_nombre,
                'rol_docente' => $this->add_rol,
                'descripcion' => $this->add_descripcion ?: null,
                'año' => $this->add_año,
                'estado_verificacion' => 'pendiente',
            ]);

            $this->addCodigoModal = false;
            Notification::make()->title('Código agregado')->body('El código de investigación ha sido agregado exitosamente.')->success()->send();
        } catch (\Exception $e) {
            Notification::make()->title('Error')->body('No se pudo agregar el código. Es posible que ya exista.')->danger()->send();
        }
    }

    public function eliminarCodigo(int $id): void
    {
        $this->ensureProfileCanBeEdited();

        EmpleadoCodigoInvestigacion::where('id', $id)
            ->where('empleado_id', $this->record->empleado->id)
            ->where('estado_verificacion', 'pendiente')
            ->delete();
        Notification::make()->title('Código eliminado')->body('El código ha sido eliminado exitosamente.')->success()->send();
    }

    public function save(): void
    {
        $this->ensureProfileCanBeEdited();

        $empleado = $this->record->empleado;
        $completandoPerfil = ProfileCompletion::isRequired($this->record->fresh());
        // Solo una cuenta nueva (con la bandera de incorporación) recibe el rol docente al
        // terminar; a un usuario existente con datos faltantes no se le cambian los roles.
        $esCuentaNueva = ProfileCompletion::hasPendingFlag($this->record);

        if (! $empleado) {
            throw ValidationException::withMessages([
                'nombre_completo' => 'No existe un perfil de empleado asociado a esta cuenta.',
            ]);
        }

        // Se calcula aquí y no con la propiedad: el número que ya existe no se puede cambiar.
        $numeroEditable = $completandoPerfil && blank($empleado->numero_empleado);

        $rules = [
            'tiene_proyectos_previos' => [
                Rule::requiredIf($empleado->tipo_empleado === 'docente'),
                Rule::in(['si', 'no']),
            ],
        ];

        $jornadas = JornadaLaboral::etiquetasActivas();

        if ($completandoPerfil) {
            $rules = array_merge($rules, [
                'celular' => 'required|numeric',
                // Sin jornadas activas en el catálogo no se puede exigir (ProfileCompletion tampoco).
                'jornada_laboral' => [Rule::requiredIf($jornadas !== []), 'nullable', 'string', Rule::in($jornadas)],
                'sexo' => ['required', Rule::in(['Masculino', 'Femenino'])],
                'categoria_id' => ['required', 'exists:categoria,id'],
                'centro_facultad_id' => 'required|exists:centro_facultad,id',
                'departamento_academico_id' => [
                    'required',
                    Rule::exists('departamento_academico', 'id')->where(
                        fn ($query) => $query->where('centro_facultad_id', $this->centro_facultad_id)
                    ),
                ],
                'carrera_id' => [
                    'nullable',
                    'integer',
                    'exists:carrera,id',
                ],
            ]);
        }

        if ($numeroEditable) {
            // El índice único de la base también cuenta a los empleados eliminados.
            $rules['numero_empleado'] = ['required', 'string', 'max:255', Rule::unique('empleado', 'numero_empleado')->ignore($empleado->id)];
        }

        $this->validate($rules, [
            'numero_empleado.required' => 'Ingrese su número de empleado.',
            'numero_empleado.unique' => 'Ese número de empleado ya está registrado a nombre de otra persona.',
            'celular.required' => 'Ingrese el número de celular.',
            'celular.numeric' => 'El celular solo debe contener números.',
            'jornada_laboral.required' => 'Seleccione su jornada laboral.',
            'jornada_laboral.in' => 'Seleccione una jornada laboral del catálogo.',
            'sexo.required' => 'Seleccione el sexo.',
            'categoria_id.required' => 'Seleccione una categoría.',
            'centro_facultad_id.required' => 'Seleccione una facultad o centro.',
            'departamento_academico_id.required' => 'Seleccione una unidad académica.',
            'departamento_academico_id.exists' => 'El departamento seleccionado no pertenece a la facultad o centro.',
            'carrera_id.exists' => 'La carrera seleccionada no es válida.',
            'tiene_proyectos_previos.required' => 'Indique si participó en proyectos previos.',
        ]);

        if ($completandoPerfil && $this->carrera_id !== null && ! $this->carreraPerteneceAlDepartamento()) {
            throw ValidationException::withMessages([
                'carrera_id' => 'La carrera seleccionada no pertenece al departamento académico.',
            ]);
        }

        if ($this->firmaUpload) {
            $this->validate(['firmaUpload' => 'image|max:2048']);
            $this->storeFirmaSello($empleado, 'firma', $this->firmaUpload);
            $this->firmaUpload = null;
        }

        if ($this->selloUpload) {
            $this->validate(['selloUpload' => 'image|max:2048']);
            $this->storeFirmaSello($empleado, 'sello', $this->selloUpload);
            $this->selloUpload = null;
        }

        if (! $empleado->firma()->exists()) {
            throw ValidationException::withMessages([
                'firmaUpload' => 'Debe subir su firma antes de finalizar el registro.',
            ]);
        }

        if ($empleado->tipo_empleado === 'docente') {
            $tieneCodigos = $empleado->codigosInvestigacion()->exists();

            if ($this->tiene_proyectos_previos === 'si' && ! $tieneCodigos) {
                throw ValidationException::withMessages([
                    'tiene_proyectos_previos' => 'Agregue al menos un proyecto previo antes de finalizar el registro.',
                ]);
            }

            if ($this->tiene_proyectos_previos === 'no' && $tieneCodigos) {
                throw ValidationException::withMessages([
                    'tiene_proyectos_previos' => 'Ya existen proyectos registrados; seleccione Sí para continuar.',
                ]);
            }
        }

        DB::transaction(function () use ($empleado, $completandoPerfil, $esCuentaNueva, $numeroEditable): void {
            if ($completandoPerfil) {
                $empleado->update([
                    'celular' => $this->celular,
                    'jornada_laboral' => $this->jornada_laboral !== '' ? $this->jornada_laboral : null,
                    'sexo' => $this->sexo,
                    'categoria_id' => $this->categoria_id,
                    'centro_facultad_id' => $this->centro_facultad_id,
                    'departamento_academico_id' => $this->departamento_academico_id,
                    'carrera_id' => $this->carrera_id,
                ] + ($numeroEditable ? ['numero_empleado' => trim($this->numero_empleado)] : []));
            }

            if ($esCuentaNueva && $empleado->tipo_empleado === 'docente') {
                $this->record->assignRole('docente');
                $this->record->active_role_id = Role::where('name', 'docente')->first()?->id;
            }

            if ($completandoPerfil) {
                ProfileCompletion::clear($this->record);
            }

            $this->record->save();
        });

        Notification::make()->title('¡Éxito!')->body('Perfil actualizado correctamente.')->success()->send();

        $this->redirectRoute('inicio');
    }

    public function render(): View
    {
        $centros = FacultadCentro::orderBy('nombre')->pluck('nombre', 'id');
        $categorias = CategoriaEmpleado::orderBy('nombre')->pluck('nombre', 'id');
        $jornadas = JornadaLaboral::etiquetasActivas();
        $departamentos = $this->centro_facultad_id
            ? DepartamentoAcademico::where('centro_facultad_id', $this->centro_facultad_id)->orderBy('nombre')->pluck('nombre', 'id')
            : collect();
        $carreras = $this->departamento_academico_id
            ? $this->carrerasDelDepartamento()->orderBy('nombre')->pluck('nombre', 'id')
            : collect();

        $firma = $this->record->empleado?->firma()->first();
        $sello = $this->record->empleado?->sello;
        $codigos = $this->record->empleado?->codigosInvestigacion ?? collect();
        $anios = collect(range(date('Y') - 10, date('Y') + 2))->mapWithKeys(fn ($y) => [$y => $y]);

        return view('livewire.personal.perfil.edit-perfil-docente', compact('centros', 'categorias', 'jornadas', 'departamentos', 'carreras', 'firma', 'sello', 'codigos', 'anios'));
    }

    private function ensureProfileCanBeEdited(): void
    {
        abort_unless(Auth::check() && (int) Auth::id() === (int) $this->record->id, 403);
    }

    private function storeFirmaSello(Empleado $empleado, string $tipo, mixed $upload): void
    {
        if ($tipo === 'firma') {
            try {
                $path = app(ProcesadorFirma::class)->guardar($upload->getRealPath());
            } catch (\RuntimeException $exception) {
                report($exception);
                throw ValidationException::withMessages([
                    'firmaUpload' => 'No se pudo procesar la firma. Inténtelo con otra imagen o contacte al administrador.',
                ]);
            }
        } else {
            $path = $upload->store('images/firmas', 'public');
        }

        FirmaSelloEmpleado::create([
            'empleado_id' => $empleado->id,
            'ruta_storage' => $path,
            'tipo' => $tipo,
        ]);
    }

    private function carrerasDelDepartamento()
    {
        return Carrera::query()->where(function ($query): void {
            $query->where('departamento_academico_id', $this->departamento_academico_id)
                ->orWhereHas('departamentosAcademicos', function ($departamentos): void {
                    $departamentos->where('departamento_academico.id', $this->departamento_academico_id);
                });
        });
    }

    private function carreraPerteneceAlDepartamento(): bool
    {
        return $this->carrerasDelDepartamento()
            ->whereKey($this->carrera_id)
            ->exists();
    }
}
