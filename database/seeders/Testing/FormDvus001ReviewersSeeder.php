<?php

namespace Database\Seeders\Testing;

use App\Models\Personal\Empleado;
use App\Models\UnidadAcademica\DepartamentoAcademico;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Spatie\Permission\Models\Role;

class FormDvus001ReviewersSeeder extends Seeder
{
    private const PASSWORD = 'Prueba1234!';

    private const GUARD = 'web';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Este seeder solo puede ejecutarse en entornos local o testing.');
        }

        $this->assertExpectedSchema();

        $rows = DB::transaction(function (): array {
            $roles = $this->resolveRoles();
            [$centro, $departamento] = $this->resolveUnidadAcademica();

            return collect($this->reviewers())
                ->map(function (array $reviewer) use ($roles, $centro, $departamento): array {
                    $role = $roles[$reviewer['role']];
                    $user = $this->upsertUser($reviewer, $role);
                    $empleado = $this->upsertEmpleado($reviewer, $user, $centro, $departamento);

                    return [
                        'Nombre' => $empleado->nombre_completo,
                        'Correo' => $user->email,
                        'Contraseña' => self::PASSWORD,
                        'Rol' => $role->name,
                        'Centro o Facultad' => $centro->nombre,
                        'Departamento' => $departamento?->nombre ?? 'Sin departamento',
                    ];
                })
                ->all();
        });

        $this->command?->table(
            ['Nombre', 'Correo', 'Contraseña', 'Rol', 'Centro o Facultad', 'Departamento'],
            $rows
        );
    }

    private function reviewers(): array
    {
        return [
            [
                'name' => 'Revisor Coordinador Proyecto',
                'email' => 'coordinador.proyecto.test@unah.edu.hn',
                'numero_empleado' => '990001',
                'role' => 'Coordinador Proyecto',
            ],
            [
                'name' => 'Revisor Director Centro',
                'email' => 'director.centro.test@unah.edu.hn',
                'numero_empleado' => '990002',
                'role' => 'Director centro',
            ],
            [
                'name' => 'Revisor Director Vinculación',
                'email' => 'director.vinculacion.test@unah.edu.hn',
                'numero_empleado' => '990003',
                'role' => 'Director Vinculacion',
            ],
        ];
    }

    private function resolveRoles(): array
    {
        return collect($this->reviewers())
            ->pluck('role')
            ->unique()
            ->mapWithKeys(function (string $roleName): array {
                $role = Role::where('name', $roleName)
                    ->where('guard_name', self::GUARD)
                    ->first();

                if (! $role) {
                    throw new RuntimeException(sprintf(
                        'No existe el rol "%s". Ejecute primero los seeders de roles y permisos.',
                        $roleName
                    ));
                }

                return [$roleName => $role];
            })
            ->all();
    }

    private function resolveUnidadAcademica(): array
    {
        $centroId = $this->envInteger('TEST_REVIEW_CENTRO_ID');
        $departamentoId = $this->envInteger('TEST_REVIEW_DEPARTAMENTO_ID');

        $centro = $centroId
            ? FacultadCentro::whereKey($centroId)->first()
            : FacultadCentro::orderBy('id')->first();

        if ($centroId && ! $centro) {
            throw new RuntimeException(sprintf(
                'No existe la Facultad/Centro con id %d indicada en TEST_REVIEW_CENTRO_ID.',
                $centroId
            ));
        }

        if (! $centro) {
            throw new RuntimeException('No existe ninguna Facultad/Centro disponible para asociar los revisores.');
        }

        if (! $centroId) {
            $this->command?->warn(sprintf(
                'TEST_REVIEW_CENTRO_ID no fue definido. Se usará "%s" (ID %d).',
                $centro->nombre,
                $centro->id
            ));
        }

        $departamento = $departamentoId
            ? DepartamentoAcademico::whereKey($departamentoId)->first()
            : DepartamentoAcademico::where('centro_facultad_id', $centro->id)->orderBy('id')->first();

        if ($departamentoId && ! $departamento) {
            throw new RuntimeException(sprintf(
                'No existe el Departamento Académico con id %d indicado en TEST_REVIEW_DEPARTAMENTO_ID.',
                $departamentoId
            ));
        }

        if ($departamento && (int) $departamento->centro_facultad_id !== (int) $centro->id) {
            throw new RuntimeException(sprintf(
                'El Departamento Académico "%s" (ID %d) no pertenece a la Facultad/Centro "%s" (ID %d).',
                $departamento->nombre,
                $departamento->id,
                $centro->nombre,
                $centro->id
            ));
        }

        if (! $departamento && ! $this->columnIsNullable('empleado', 'departamento_academico_id')) {
            throw new RuntimeException(sprintf(
                'La Facultad/Centro "%s" no tiene departamentos académicos y empleado.departamento_academico_id no permite null.',
                $centro->nombre
            ));
        }

        if (! $departamento && ! $departamentoId) {
            $this->command?->warn(sprintf(
                'La Facultad/Centro "%s" no tiene departamentos académicos. Se usará null.',
                $centro->nombre
            ));
        }

        return [$centro, $departamento];
    }

    private function upsertUser(array $reviewer, Role $role): User
    {
        $user = User::withTrashed()->where('email', $reviewer['email'])->first();

        if (! $user) {
            $user = new User(['email' => $reviewer['email']]);
        }

        if ($user->trashed()) {
            $user->restore();
        }

        $user->forceFill([
            'name' => $reviewer['name'],
            'email' => $reviewer['email'],
            'password' => Hash::make(self::PASSWORD),
            'email_verified_at' => now(),
            'active_role_id' => $role->id,
        ])->save();

        $user->syncRoles([$role]);
        $user->load('roles');

        if (! $user->roles->contains('id', $role->id)) {
            throw new RuntimeException(sprintf(
                'No se pudo asignar el rol "%s" al usuario "%s".',
                $role->name,
                $user->email
            ));
        }

        if (! $user->roles->contains('id', (int) $user->active_role_id)) {
            throw new RuntimeException(sprintf(
                'El active_role_id del usuario "%s" no pertenece a sus roles asignados.',
                $user->email
            ));
        }

        return $user;
    }

    private function upsertEmpleado(
        array $reviewer,
        User $user,
        FacultadCentro $centro,
        ?DepartamentoAcademico $departamento
    ): Empleado {
        $empleadoConNumero = Empleado::withTrashed()
            ->where('numero_empleado', $reviewer['numero_empleado'])
            ->first();

        if ($empleadoConNumero && (int) $empleadoConNumero->user_id !== (int) $user->id) {
            throw new RuntimeException(sprintf(
                'El número de empleado %s ya pertenece a otro usuario (user_id %d).',
                $reviewer['numero_empleado'],
                $empleadoConNumero->user_id
            ));
        }

        $empleado = Empleado::withTrashed()->where('user_id', $user->id)->first();

        if (! $empleado) {
            $empleado = new Empleado(['user_id' => $user->id]);
        }

        if ($empleado->trashed()) {
            $empleado->restore();
        }

        $attributes = [
            'user_id' => $user->id,
            'nombre_completo' => $reviewer['name'],
            'numero_empleado' => $reviewer['numero_empleado'],
            'celular' => '99999999',
            'jornada_laboral' => 'Pruebas de flujo',
            'centro_facultad_id' => $centro->id,
            'departamento_academico_id' => $departamento?->id,
        ];

        if (Schema::hasColumn('empleado', 'tipo_empleado')) {
            $attributes['tipo_empleado'] = 'administrativo';
        }

        $empleado->forceFill($attributes)->save();

        return $empleado;
    }

    private function assertExpectedSchema(): void
    {
        $columns = [
            'users' => ['name', 'email', 'password', 'email_verified_at', 'active_role_id'],
            'empleado' => [
                'user_id',
                'nombre_completo',
                'numero_empleado',
                'celular',
                'jornada_laboral',
                'centro_facultad_id',
                'departamento_academico_id',
            ],
            'centro_facultad' => ['id', 'nombre'],
            'departamento_academico' => ['id', 'nombre', 'centro_facultad_id'],
            'roles' => ['id', 'name', 'guard_name'],
        ];

        foreach ($columns as $table => $requiredColumns) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException(sprintf('No existe la tabla esperada "%s".', $table));
            }

            foreach ($requiredColumns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    throw new RuntimeException(sprintf('No existe la columna esperada "%s.%s".', $table, $column));
                }
            }
        }
    }

    private function envInteger(string $key): ?int
    {
        $value = env($key);

        if ($value === null || $value === '') {
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            throw new RuntimeException(sprintf('La variable %s debe ser un número entero.', $key));
        }

        return (int) $value;
    }

    private function columnIsNullable(string $table, string $column): bool
    {
        $columnDefinition = collect(Schema::getColumns($table))
            ->firstWhere('name', $column);

        return (bool) ($columnDefinition['nullable'] ?? false);
    }
}
