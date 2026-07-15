<?php

namespace App\Services\Personal;

use App\Models\Personal\Empleado;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class EmpleadoService
{
    public function convertirUsuarioEnEmpleado(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $userBloqueado = User::withTrashed()->lockForUpdate()->findOrFail($user->id);

            if ($userBloqueado->trashed()) {
                throw ValidationException::withMessages([
                    'email' => 'La cuenta está eliminada y requiere recuperación explícita antes de vincularla.',
                ]);
            }

            $empleadoExistente = Empleado::withTrashed()
                ->where('user_id', $userBloqueado->id)
                ->lockForUpdate()
                ->first();

            if ($empleadoExistente?->trashed()) {
                throw ValidationException::withMessages([
                    'email' => 'El usuario posee un perfil laboral eliminado. Debe recuperarse explícitamente.',
                ]);
            }

            if ($empleadoExistente) {
                throw ValidationException::withMessages([
                    'email' => 'Este usuario ya posee un perfil de empleado.',
                ]);
            }

            $this->validarNumeroDisponible((string) $data['numero_empleado']);

            $this->crearEmpleado($this->empleadoData($data, $userBloqueado->id));

            if ($userBloqueado->active_role_id === null) {
                $primerRolId = $userBloqueado->roles()->orderBy('roles.id')->value('roles.id');
                if ($primerRolId) {
                    $userBloqueado->update(['active_role_id' => $primerRolId]);
                }
            }

            return $userBloqueado->fresh(['empleado', 'roles']);
        });
    }

    public function crearUsuarioConEmpleado(array $userData, array $empleadoData, array $roleIds): User
    {
        return DB::transaction(function () use ($userData, $empleadoData, $roleIds): User {
            $this->validarNumeroDisponible((string) $empleadoData['numero_empleado']);

            $roles = Role::query()->whereIn('id', $roleIds)->orderBy('id')->get();
            if ($roles->isEmpty()) {
                throw ValidationException::withMessages([
                    'create_roles' => 'Debe seleccionar al menos un rol para el nuevo usuario.',
                ]);
            }

            if ($roles->count() !== count(array_unique($roleIds))) {
                throw ValidationException::withMessages([
                    'create_roles' => 'Seleccione únicamente roles válidos.',
                ]);
            }

            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make(Str::random(12)),
            ]);

            $this->crearEmpleado($this->empleadoData($empleadoData, $user->id));

            $user->syncRoles($roles->pluck('name')->all());
            $user->update(['active_role_id' => $roles->first()->id]);

            return $user->fresh(['empleado', 'roles']);
        });
    }

    protected function crearEmpleado(array $data): Empleado
    {
        return Empleado::create($data);
    }

    private function validarNumeroDisponible(string $numeroEmpleado): void
    {
        if (Empleado::withTrashed()->where('numero_empleado', $numeroEmpleado)->lockForUpdate()->exists()) {
            throw ValidationException::withMessages([
                'numero_empleado' => 'El número de empleado ya está registrado o pertenece a un perfil eliminado.',
            ]);
        }
    }

    private function empleadoData(array $data, int $userId): array
    {
        return [
            'user_id' => $userId,
            'nombre_completo' => $data['nombre_completo'],
            'numero_empleado' => $data['numero_empleado'],
            'celular' => $data['celular'],
            'jornada_laboral' => $data['jornada_laboral'] ?: null,
            'categoria_id' => $data['categoria_id'] ?: null,
            'centro_facultad_id' => $data['centro_facultad_id'],
            'departamento_academico_id' => $data['departamento_academico_id'] ?: null,
            'tipo_empleado' => $data['tipo_empleado'],
        ];
    }
}
