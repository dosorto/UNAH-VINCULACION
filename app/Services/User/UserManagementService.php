<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UserManagementService
{
    public function updateIdentity(User $user, array $data): User
    {
        $validated = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ])->validate();

        return DB::transaction(function () use ($user, $validated): User {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

            $lockedUser->update([
                'name' => trim((string) $validated['name']),
                'email' => mb_strtolower(trim((string) $validated['email'])),
            ]);

            return $lockedUser->fresh(['roles', 'empleado', 'estudiante']);
        }, 3);
    }

    public function createUser(array $data, User $actor): User
    {
        if (! $actor->can('usuarios.usuarios')) {
            abort(403);
        }

        $validated = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
            'role_ids' => ['required', 'array', 'min:1'],
            'active_role_id' => ['nullable', 'integer'],
        ])->validate();

        return DB::transaction(function () use ($validated, $actor): User {
            $roles = $this->lockedWebRoles($validated['role_ids']);
            $activeRoleId = $this->validatedActiveRoleId($roles, $validated['active_role_id'] ?? null);

            $user = User::query()->create([
                'name' => trim((string) $validated['name']),
                'email' => mb_strtolower(trim((string) $validated['email'])),
                'password' => Hash::make((string) $validated['password']),
            ]);

            $this->persistRoles($user, $roles);
            $user->update(['active_role_id' => $activeRoleId]);

            $this->recordRoleAudit($actor, $user, [], $roles->pluck('name')->all(), null, $activeRoleId, 'roles_assigned_on_creation');

            return $user->fresh(['roles', 'empleado', 'estudiante']);
        }, 3);
    }

    public function syncUserRoles(
        User $user,
        array $roleIds,
        ?int $activeRoleId,
        User $actor,
        bool $confirmedAdministrativeRemoval = false,
    ): User {
        if (! $actor->can('usuarios.usuarios')) {
            abort(403);
        }

        return DB::transaction(function () use ($user, $roleIds, $activeRoleId, $actor, $confirmedAdministrativeRemoval): User {
            $lockedUsers = User::query()
                ->whereIn('id', array_unique([$user->id, $actor->id]))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $lockedUser = $lockedUsers->get($user->id) ?? throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
            $lockedActor = $lockedUsers->get($actor->id) ?? throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
            $currentRoles = $lockedUser->roles()->where('guard_name', 'web')->orderBy('roles.id')->get();
            $newRoles = $this->lockedWebRoles($roleIds);
            $difference = $this->difference($currentRoles, $newRoles);

            $this->assertLastAdministratorIsPreserved($lockedUser, $currentRoles, $newRoles);
            $this->assertActorKeepsUserAdministrationAccess(
                $lockedUser,
                $lockedActor,
                $newRoles,
                $difference['removed_ids'],
                $confirmedAdministrativeRemoval,
            );

            $resolvedActiveRoleId = $this->validatedActiveRoleId($newRoles, $activeRoleId);
            $previousActiveRoleId = $lockedUser->active_role_id;

            $this->persistRoles($lockedUser, $newRoles);
            if ((int) $previousActiveRoleId !== $resolvedActiveRoleId) {
                $lockedUser->update(['active_role_id' => $resolvedActiveRoleId]);
            }

            $this->recordRoleAudit(
                $lockedActor,
                $lockedUser,
                $currentRoles->pluck('name')->all(),
                $newRoles->pluck('name')->all(),
                $previousActiveRoleId,
                $resolvedActiveRoleId,
                'roles_updated',
            );

            return $lockedUser->fresh(['roles', 'empleado', 'estudiante']);
        }, 3);
    }

    public function roleDifference(User $user, array $roleIds): array
    {
        $currentRoles = $user->roles()->where('guard_name', 'web')->orderBy('roles.id')->get();
        $newRoles = Role::query()->where('guard_name', 'web')->whereIn('id', $this->normalizeRoleIds($roleIds))->orderBy('id')->get();

        return $this->difference($currentRoles, $newRoles);
    }

    protected function persistRoles(User $user, Collection $roles): void
    {
        $user->syncRoles($roles);
    }

    private function lockedWebRoles(array $roleIds): Collection
    {
        $normalizedIds = $this->normalizeRoleIds($roleIds);
        if ($normalizedIds === []) {
            throw ValidationException::withMessages([
                'roles' => 'El usuario debe conservar al menos un rol.',
            ]);
        }

        $roles = Role::query()
            ->where('guard_name', 'web')
            ->whereIn('id', $normalizedIds)
            ->with('permissions:id,name,guard_name')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($roles->count() !== count($normalizedIds)) {
            throw ValidationException::withMessages([
                'roles' => 'Uno o más roles no existen o no pertenecen al acceso web.',
            ]);
        }

        return $roles;
    }

    private function validatedActiveRoleId(Collection $roles, ?int $activeRoleId): int
    {
        if ($roles->count() === 1 && $activeRoleId === null) {
            return (int) $roles->first()->id;
        }

        if ($activeRoleId === null || ! $roles->contains('id', $activeRoleId)) {
            throw ValidationException::withMessages([
                'active_role_id' => 'Seleccione un rol activo que pertenezca a los roles asignados.',
            ]);
        }

        return $activeRoleId;
    }

    private function assertLastAdministratorIsPreserved(User $user, Collection $currentRoles, Collection $newRoles): void
    {
        $adminRole = Role::query()->where('guard_name', 'web')->where('name', 'admin')->lockForUpdate()->first();
        if (! $adminRole
            || ! $currentRoles->contains('id', $adminRole->id)
            || $newRoles->contains('id', $adminRole->id)
        ) {
            return;
        }

        $otherActiveAdministrators = User::query()
            ->whereKeyNot($user->id)
            ->whereHas('roles', fn ($query) => $query->where('roles.id', $adminRole->id))
            ->lockForUpdate()
            ->exists();

        if (! $otherActiveAdministrators) {
            throw ValidationException::withMessages([
                'roles' => 'No es posible retirar el rol admin porque este usuario es el último administrador activo del sistema.',
            ]);
        }
    }

    private function assertActorKeepsUserAdministrationAccess(
        User $affectedUser,
        User $actor,
        Collection $newRoles,
        array $removedRoleIds,
        bool $confirmedAdministrativeRemoval,
    ): void {
        if ($affectedUser->id !== $actor->id) {
            return;
        }

        $removedAdministrativeRole = Role::query()
            ->whereIn('id', $removedRoleIds)
            ->whereHas('permissions', fn ($query) => $query->where('name', 'usuarios.usuarios')->where('guard_name', 'web'))
            ->exists();

        if ($removedAdministrativeRole && ! $confirmedAdministrativeRemoval) {
            throw ValidationException::withMessages([
                'confirm_administrative_removal' => 'Confirme explícitamente la retirada de roles administrativos de su propio usuario.',
            ]);
        }

        $keepsAccessThroughRole = $newRoles->contains(
            fn (Role $role): bool => $role->permissions->contains('name', 'usuarios.usuarios')
        );
        $keepsDirectAccess = $actor->getDirectPermissions()->contains('name', 'usuarios.usuarios');

        if (! $keepsAccessThroughRole && ! $keepsDirectAccess) {
            throw ValidationException::withMessages([
                'roles' => 'No puede retirar de su propio usuario el último rol que concede acceso a la administración de usuarios.',
            ]);
        }
    }

    private function difference(Collection $currentRoles, Collection $newRoles): array
    {
        $currentIds = $currentRoles->pluck('id')->map(fn ($id) => (int) $id)->all();
        $newIds = $newRoles->pluck('id')->map(fn ($id) => (int) $id)->all();

        return [
            'previous_ids' => $currentIds,
            'new_ids' => $newIds,
            'added_ids' => array_values(array_diff($newIds, $currentIds)),
            'removed_ids' => array_values(array_diff($currentIds, $newIds)),
        ];
    }

    private function normalizeRoleIds(array $roleIds): array
    {
        $normalizedIds = [];
        foreach ($roleIds as $roleId) {
            if (filter_var($roleId, FILTER_VALIDATE_INT) === false) {
                throw ValidationException::withMessages([
                    'roles' => 'Uno o más roles no son válidos.',
                ]);
            }
            $normalizedIds[] = (int) $roleId;
        }

        if (count($normalizedIds) !== count(array_unique($normalizedIds))) {
            throw ValidationException::withMessages([
                'roles' => 'No se permiten roles duplicados.',
            ]);
        }

        return array_values($normalizedIds);
    }

    private function recordRoleAudit(
        User $actor,
        User $affectedUser,
        array $previousRoles,
        array $newRoles,
        ?int $previousActiveRoleId,
        ?int $newActiveRoleId,
        string $event,
    ): void {
        activity('Usuario')
            ->causedBy($actor)
            ->performedOn($affectedUser)
            ->event($event)
            ->withProperties([
                'roles_anteriores' => $previousRoles,
                'roles_posteriores' => $newRoles,
                'rol_activo_anterior' => $previousActiveRoleId,
                'rol_activo_posterior' => $newActiveRoleId,
            ])
            ->log('Seguridad del usuario actualizada');
    }
}
