<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * «Administrar Asignaturas» es un permiso de administración: con él, el rol
 * docente veía en su menú Unidad Académica → Asignatura. Crear una asignatura
 * desde el formulario del proyecto no lo necesita (crearAsignaturaInline no lo
 * comprueba), así que se revoca de docente y Coordinador Proyecto. Las
 * asignaciones directas a usuarios no se tocan.
 */
return new class extends Migration
{
    private const PERMISO = 'unidad-academica.asignatura';

    private const ROLES = ['docente', 'Coordinador Proyecto'];

    public function up(): void
    {
        DB::table('role_has_permissions')
            ->whereIn('permission_id', $this->permisoIds())
            ->whereIn('role_id', $this->rolIds())
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $filas = [];

        foreach ($this->permisoIds() as $permisoId) {
            foreach ($this->rolIds() as $rolId) {
                $filas[] = ['permission_id' => $permisoId, 'role_id' => $rolId];
            }
        }

        DB::table('role_has_permissions')->insertOrIgnore($filas);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function permisoIds(): array
    {
        return DB::table('permissions')
            ->where('name', self::PERMISO)
            ->where('guard_name', 'web')
            ->pluck('id')
            ->all();
    }

    private function rolIds(): array
    {
        return DB::table('roles')
            ->whereIn('name', self::ROLES)
            ->where('guard_name', 'web')
            ->pluck('id')
            ->all();
    }
};
