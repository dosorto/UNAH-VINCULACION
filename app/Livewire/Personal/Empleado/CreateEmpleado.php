<?php

namespace App\Livewire\Personal\Empleado;

use App\Models\Personal\CategoriaEmpleado;
use App\Models\Personal\Empleado;
use App\Models\UnidadAcademica\DepartamentoAcademico;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\User;
use App\Services\Personal\EmpleadoService;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class CreateEmpleado extends Component
{
    public const ESTADO_SIN_BUSCAR = 'sin_buscar';
    public const ESTADO_USUARIO_NUEVO = 'usuario_nuevo';
    public const ESTADO_VINCULAR = 'vincular';
    public const ESTADO_CON_EMPLEADO = 'con_empleado';
    public const ESTADO_USUARIO_ELIMINADO = 'usuario_eliminado';
    public const ESTADO_EMPLEADO_ELIMINADO = 'empleado_eliminado';

    public string $name = '';
    public string $email = '';
    public string $nombre_completo = '';
    public string $numero_empleado = '';
    public string $celular = '';
    public string $jornada_laboral = '';
    public string $tipo_empleado = 'docente';
    public ?int $categoria_id = null;
    public ?int $centro_facultad_id = null;
    public ?int $departamento_academico_id = null;
    public array $create_roles = [];

    public string $estadoBusqueda = self::ESTADO_SIN_BUSCAR;
    public string $emailConsultado = '';
    public ?int $usuarioExistenteId = null;
    public array $rolesExistentes = [];

    public function updatedEmail(): void
    {
        if ($this->normalizarCorreo($this->email) !== $this->emailConsultado) {
            $this->reiniciarResultadoBusqueda();
        }
    }

    public function updatingCentroFacultadId(): void
    {
        $this->departamento_academico_id = null;
    }

    public function buscarCorreo(): void
    {
        $this->resetErrorBag();
        $this->email = $this->normalizarCorreo($this->email);
        $this->validate($this->reglasCorreo());

        $this->reiniciarResultadoBusqueda();
        $this->emailConsultado = $this->email;

        $user = User::withTrashed()
            ->with('roles')
            ->whereRaw('LOWER(TRIM(email)) = ?', [$this->email])
            ->first();

        if (! $user) {
            $this->estadoBusqueda = self::ESTADO_USUARIO_NUEVO;
            return;
        }

        $this->usuarioExistenteId = $user->id;
        $this->rolesExistentes = $user->roles->pluck('name')->all();
        $this->name = (string) $user->name;
        $this->nombre_completo = $this->nombre_completo ?: (string) $user->name;

        if ($user->trashed()) {
            $this->estadoBusqueda = self::ESTADO_USUARIO_ELIMINADO;
            return;
        }

        $empleado = Empleado::withTrashed()->where('user_id', $user->id)->first();
        if ($empleado?->trashed()) {
            $this->estadoBusqueda = self::ESTADO_EMPLEADO_ELIMINADO;
            return;
        }

        if ($empleado) {
            $this->estadoBusqueda = self::ESTADO_CON_EMPLEADO;
            return;
        }

        $this->estadoBusqueda = self::ESTADO_VINCULAR;
    }

    public function create(EmpleadoService $empleadoService): void
    {
        $this->email = $this->normalizarCorreo($this->email);

        if ($this->emailConsultado !== $this->email
            || ! in_array($this->estadoBusqueda, [self::ESTADO_USUARIO_NUEVO, self::ESTADO_VINCULAR], true)
        ) {
            throw ValidationException::withMessages([
                'email' => 'Busque y confirme el estado del correo antes de guardar.',
            ]);
        }

        $validated = $this->validate($this->reglasParaEscenario());
        $empleadoData = collect($validated)->only([
            'nombre_completo', 'numero_empleado', 'celular', 'jornada_laboral', 'tipo_empleado',
            'categoria_id', 'centro_facultad_id', 'departamento_academico_id',
        ])->all();

        if ($this->estadoBusqueda === self::ESTADO_VINCULAR) {
            $user = User::query()->findOrFail($this->usuarioExistenteId);
            $empleadoService->convertirUsuarioEnEmpleado($user, $empleadoData);
            Notification::make()->title('Perfil laboral vinculado')->body('El usuario existente ahora posee perfil de empleado.')->success()->send();
        } else {
            $empleadoService->crearUsuarioConEmpleado([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ], $empleadoData, array_map('intval', $validated['create_roles']));
            Notification::make()->title('¡Éxito!')->body('Usuario y empleado creados correctamente.')->success()->send();
        }

        $this->redirectRoute('ListarEmpleados', navigate: true);
    }

    private function reglasCorreo(): array
    {
        return [
            'email' => ['required', 'email', 'max:255', 'regex:/^[^@\s]+@unah\.edu\.hn$/i'],
        ];
    }

    private function reglasParaEscenario(): array
    {
        $rules = array_merge($this->reglasCorreo(), [
            'nombre_completo' => ['required', 'string', 'max:255'],
            'numero_empleado' => ['required', 'regex:/^\d+$/', Rule::unique('empleado', 'numero_empleado')],
            'celular' => ['required', 'numeric'],
            'jornada_laboral' => ['nullable', 'string', 'max:120'],
            'tipo_empleado' => ['required', Rule::in(['docente', 'administrativo'])],
            'categoria_id' => ['nullable', 'exists:categoria,id'],
            'centro_facultad_id' => ['required', 'exists:centro_facultad,id'],
            'departamento_academico_id' => [
                'nullable',
                Rule::exists('departamento_academico', 'id')->where(
                    fn (Builder $query): Builder => $query->where('centro_facultad_id', $this->centro_facultad_id)
                ),
            ],
        ]);

        if ($this->estadoBusqueda === self::ESTADO_USUARIO_NUEVO) {
            $rules['name'] = ['required', 'string', 'max:255', Rule::unique('users', 'name')];
            $rules['email'][] = Rule::unique('users', 'email');
            $rules['create_roles'] = ['required', 'array', 'min:1'];
            $rules['create_roles.*'] = ['integer', 'distinct', 'exists:roles,id'];
        }

        return $rules;
    }

    private function normalizarCorreo(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    private function reiniciarResultadoBusqueda(): void
    {
        $this->estadoBusqueda = self::ESTADO_SIN_BUSCAR;
        $this->emailConsultado = '';
        $this->usuarioExistenteId = null;
        $this->rolesExistentes = [];
    }

    public function render(): View
    {
        $centros = FacultadCentro::orderBy('nombre')->pluck('nombre', 'id');
        $categorias = CategoriaEmpleado::orderBy('nombre')->pluck('nombre', 'id');
        $departamentos = $this->centro_facultad_id
            ? DepartamentoAcademico::where('centro_facultad_id', $this->centro_facultad_id)->orderBy('nombre')->pluck('nombre', 'id')
            : collect();
        $allRoles = Role::orderBy('name')->get(['id', 'name']);

        return view('livewire.personal.empleado.create-empleado', compact('centros', 'categorias', 'departamentos', 'allRoles'));
    }
}
