<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('permissions')->updateOrInsert(
            [
                'name' => 'unidad-academica.tipo-anexo',
                'guard_name' => 'web',
            ],
            [
                'display_name' => 'Administrar Tipos de Anexos',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );

        $permissionId = DB::table('permissions')
            ->where('name', 'unidad-academica.tipo-anexo')
            ->where('guard_name', 'web')
            ->value('id');

        $adminRoleId = DB::table('roles')
            ->where('name', 'admin')
            ->where('guard_name', 'web')
            ->value('id');

        if ($permissionId && $adminRoleId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $adminRoleId,
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::table('permissions')
            ->where('name', 'unidad-academica.tipo-anexo')
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
