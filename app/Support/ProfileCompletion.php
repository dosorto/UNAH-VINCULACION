<?php

namespace App\Support;

use App\Models\User;

final class ProfileCompletion
{
    /**
     * perfil.editar is the current onboarding flag. The second permission keeps
     * compatibility with users provisioned by the original Microsoft login.
     */
    public const PERMISSIONS = [
        'perfil.editar',
        'cambiar-datos-personales',
    ];

    public static function isRequired(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->permissions()
            ->whereIn('permissions.name', self::PERMISSIONS)
            ->exists();
    }

    public static function clear(User $user): void
    {
        $permissions = $user->permissions()
            ->whereIn('permissions.name', self::PERMISSIONS)
            ->get();

        foreach ($permissions as $permission) {
            $user->revokePermissionTo($permission);
        }
    }
}
