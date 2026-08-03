<?php

namespace App\Services\Auth;

use App\Models\Personal\Empleado;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class NewUserOnboardingService
{
    public function requiresEmployeeProfile(User $user): bool
    {
        return ! $user->empleado()->exists() && ! $user->estudiante()->exists();
    }

    public function prepareEmployeeProfile(User $user): User
    {
        return DB::transaction(function () use ($user): User {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);

            if (! $this->requiresEmployeeProfile($user)) {
                return $user->fresh(['empleado', 'estudiante', 'roles']);
            }

            $docenteRole = Role::firstOrCreate([
                'name' => 'docente',
                'guard_name' => 'web',
            ]);

            $completeProfilePermission = Permission::firstOrCreate(
                ['name' => 'perfil.editar', 'guard_name' => 'web'],
                ['display_name' => 'Editar Datos Personales'],
            );

            Empleado::create([
                'user_id' => $user->id,
                'nombre_completo' => $user->name,
                'tipo_empleado' => 'docente',
            ]);

            if ($user->roles()->doesntExist()) {
                $user->assignRole($docenteRole);
            }

            $activeRoleId = $user->roles()->orderBy('roles.id')->value('roles.id');
            $user->forceFill(['active_role_id' => $activeRoleId])->save();
            $user->givePermissionTo($completeProfilePermission);

            return $user->fresh(['empleado', 'roles']);
        });
    }
}
