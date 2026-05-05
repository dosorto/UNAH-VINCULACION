<?php

namespace App\Livewire\Personal\Perfil;

use App\Models\Personal\Empleado;
use App\Models\Personal\EmpleadoCodigoInvestigacion;
use App\Models\Personal\FirmaSelloEmpleado;
use App\Models\Proyecto\Proyecto;
use App\Models\Personal\EmpleadoProyecto;
use App\Models\UnidadAcademica\DepartamentoAcademico;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\User;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
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
    public ?int $centro_facultad_id = null;
    public ?int $departamento_academico_id = null;

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

    public function mount(): void
    {
        $this->record = Auth::user();
        $this->name = $this->record->name;
        $this->email = $this->record->email;

        $empleado = $this->record->empleado;
        if ($empleado) {
            $this->nombre_completo          = $empleado->nombre_completo ?? '';
            $this->numero_empleado          = $empleado->numero_empleado ?? '';
            $this->celular                  = $empleado->celular ?? '';
            $this->centro_facultad_id       = $empleado->centro_facultad_id;
            $this->departamento_academico_id = $empleado->departamento_academico_id;
        }

        $this->add_año = (int) date('Y');
    }

    public function updatingCentroFacultadId(): void
    {
        $this->departamento_academico_id = null;
    }

    public function subirFirma(): void
    {
        $this->validate(['firmaUpload' => 'required|image|max:2048']);

        $path = $this->firmaUpload->store('images/firmas', 'public');

        FirmaSelloEmpleado::create([
            'empleado_id'   => $this->record->empleado->id,
            'ruta_storage'  => $path,
            'tipo'          => 'firma',
        ]);

        $this->firmaUpload = null;
        Notification::make()->title('Firma subida')->body('La firma ha sido guardada correctamente.')->success()->send();
    }

    public function subirSello(): void
    {
        $this->validate(['selloUpload' => 'required|image|max:2048']);

        $path = $this->selloUpload->store('images/firmas', 'public');

        FirmaSelloEmpleado::create([
            'empleado_id'  => $this->record->empleado->id,
            'ruta_storage' => $path,
            'tipo'         => 'sello',
        ]);

        $this->selloUpload = null;
        Notification::make()->title('Sello subido')->body('El sello ha sido guardado correctamente.')->success()->send();
    }

    public function openAddCodigo(): void
    {
        $this->reset(['add_codigo', 'add_nombre', 'add_rol', 'add_descripcion']);
        $this->add_año = (int) date('Y');
        $this->addCodigoModal = true;
    }

    public function agregarCodigo(): void
    {
        $this->validate([
            'add_codigo'  => 'required|string|max:50',
            'add_nombre'  => 'required|string|max:255',
            'add_rol'     => 'required|in:coordinador,integrante',
            'add_año'     => 'required|integer|min:2000|max:2100',
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
                'codigo_proyecto'     => $this->add_codigo,
                'nombre_proyecto'     => $this->add_nombre,
                'rol_docente'         => $this->add_rol,
                'descripcion'         => $this->add_descripcion ?: null,
                'año'                 => $this->add_año,
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
        EmpleadoCodigoInvestigacion::where('id', $id)->where('estado_verificacion', 'pendiente')->delete();
        Notification::make()->title('Código eliminado')->body('El código ha sido eliminado exitosamente.')->success()->send();
    }

    public function save(): void
    {
        $canEdit = Auth::user()->can('cambiar-datos-personales');
        if (!$canEdit) {
            return;
        }

        $this->validate([
            'name'           => 'required|string|max:255',
            'email'          => 'required|email|max:255',
            'nombre_completo' => 'required|string|max:255',
            'numero_empleado' => 'required|numeric',
            'celular'        => 'required|numeric',
            'centro_facultad_id' => 'required|exists:centro_facultad,id',
        ]);

        $this->record->update(['name' => $this->name, 'email' => $this->email]);

        if ($this->record->empleado) {
            $this->record->empleado->update([
                'nombre_completo'           => $this->nombre_completo,
                'numero_empleado'           => $this->numero_empleado,
                'celular'                   => $this->celular,
                'centro_facultad_id'        => $this->centro_facultad_id,
                'departamento_academico_id' => $this->departamento_academico_id,
            ]);
        }

        if ($this->record->empleado->tipo_empleado === 'docente') {
            $this->record->assignRole('docente');
            $this->record->active_role_id = Role::where('name', 'docente')->first()?->id;
        }

        $this->record->revokePermissionTo('cambiar-datos-personales');
        $this->record->save();

        Notification::make()->title('Exito!')->body('Perfil actualizado correctamente.')->success()->send();

        $this->redirectRoute('inicio');
    }

    public function render(): View
    {
        $centros = FacultadCentro::orderBy('nombre')->pluck('nombre', 'id');
        $departamentos = $this->centro_facultad_id
            ? DepartamentoAcademico::where('centro_facultad_id', $this->centro_facultad_id)->orderBy('nombre')->pluck('nombre', 'id')
            : collect();

        $firma = $this->record->empleado?->firma;
        $sello = $this->record->empleado?->sello;
        $codigos = $this->record->empleado?->codigosInvestigacion ?? collect();
        $anios = collect(range(date('Y') - 10, date('Y') + 2))->mapWithKeys(fn($y) => [$y => $y]);

        return view('livewire.personal.perfil.edit-perfil-docente', compact('centros', 'departamentos', 'firma', 'sello', 'codigos', 'anios'));
    }
}
