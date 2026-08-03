<?php

namespace App\Services\Auth;

use App\Models\Personal\Empleado;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class NewUserOnboardingService
{
    public function requiresEmployeeProfile(User $user): bool
    {
        return ! $user->empleado()->exists() && ! $user->estudiante()->exists();
    }

    public function prepareEmployeeProfile(User $user, ?string $employeeNumber = null): User
    {
        return DB::transaction(function () use ($user, $employeeNumber): User {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);

            if (! $this->requiresEmployeeProfile($user)) {
                $this->fillMissingEmployeeNumber($user, $employeeNumber);

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
                'numero_empleado' => $this->availableEmployeeNumber($user, $employeeNumber),
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

    private function fillMissingEmployeeNumber(User $user, ?string $employeeNumber): void
    {
        $empleado = $user->empleado;

        if (! $empleado || filled($empleado->numero_empleado)) {
            return;
        }

        $employeeNumber = $this->availableEmployeeNumber($user, $employeeNumber);

        if ($employeeNumber !== null) {
            $empleado->forceFill(['numero_empleado' => $employeeNumber])->save();
        }
    }

    private function availableEmployeeNumber(User $user, ?string $employeeNumber): ?string
    {
        if (blank($employeeNumber)) {
            return null;
        }

        $alreadyAssigned = Empleado::withTrashed()
            ->where('numero_empleado', $employeeNumber)
            ->exists();

        if ($alreadyAssigned) {
            Log::warning('Microsoft employeeId already belongs to another employee', [
                'user_id' => $user->id,
                'microsoft_id' => $user->microsoft_id,
            ]);

            return null;
        }

        return $employeeNumber;
    }
}
