<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * «Trazabilidad de Registros» (bandeja de firmas) deja de colgar de docente.proyectos: el rol
 * docente ya no la ve. Los roles que firman (los que hoy tienen docente.proyectos, salvo
 * docente) reciben el permiso nuevo y conservan el acceso.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('permissions')->updateOrInsert(
            ['name' => 'docente.trazabilidad', 'guard_name' => 'web'],
            ['display_name' => 'Ver Trazabilidad de Registros', 'created_at' => $now, 'updated_at' => $now],
        );

        $permissionId = DB::table('permissions')
            ->where('name', 'docente.trazabilidad')
            ->where('guard_name', 'web')
            ->value('id');

        $rolesFirmantes = DB::table('role_has_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->join('roles', 'roles.id', '=', 'role_has_permissions.role_id')
            ->where('permissions.name', 'docente.proyectos')
            ->where('roles.name', '!=', 'docente')
            ->pluck('roles.id');

        if ($permissionId) {
            DB::table('role_has_permissions')->insertOrIgnore(
                $rolesFirmantes->map(fn ($roleId) => ['permission_id' => $permissionId, 'role_id' => $roleId])->all()
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::table('permissions')
            ->where('name', 'docente.trazabilidad')
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
