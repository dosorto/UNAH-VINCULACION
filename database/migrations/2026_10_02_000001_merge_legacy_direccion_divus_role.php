<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Consolida el rol legado "DIRECCION DIVUS" en el rol institucional
 * "Director Vinculacion".
 *
 * Ambos representan la misma responsabilidad. Se preservan los accesos de
 * quienes aún tenían el rol legado y únicamente se actualizan las revisiones
 * pendientes; los registros históricos conservan el nombre con el que fueron
 * resueltos.
 */
return new class extends Migration
{
    private const ROL_LEGADO = 'DIRECCION DIVUS';

    private const ROL_CANONICO = 'Director Vinculacion';

    public function up(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        $rolLegado = DB::table('roles')
            ->where('name', self::ROL_LEGADO)
            ->where('guard_name', 'web')
            ->first(['id']);
        $rolCanonico = DB::table('roles')
            ->where('name', self::ROL_CANONICO)
            ->where('guard_name', 'web')
            ->first(['id']);

        // Si el rol legado ya fue consolidado o falta el destino, no se debe
        // crear una variante sin la configuración institucional aprobada.
        if (! $rolLegado || ! $rolCanonico) {
            return;
        }

        $legadoId = (int) $rolLegado->id;
        $canonicoId = (int) $rolCanonico->id;

        DB::transaction(function () use ($legadoId, $canonicoId): void {
            $this->migrarAsignacionesDeRol($legadoId, $canonicoId);
            $this->migrarPermisosDeRol($legadoId, $canonicoId);

            if (Schema::hasTable('flujos_aprobacion_etapas')
                && Schema::hasColumn('flujos_aprobacion_etapas', 'rol_revisor_id')) {
                DB::table('flujos_aprobacion_etapas')
                    ->where('rol_revisor_id', $legadoId)
                    ->update(['rol_revisor_id' => $canonicoId]);
            }

            if (Schema::hasTable('users') && Schema::hasColumn('users', 'active_role_id')) {
                DB::table('users')
                    ->where('active_role_id', $legadoId)
                    ->update(['active_role_id' => $canonicoId]);
            }

            $this->actualizarRevisionesPendientes('firma_proyecto', 'estado_revision', true);
            $this->actualizarRevisionesPendientes('enf_revisiones', 'estado');
            $this->actualizarRevisionesPendientes('programa_revisiones', 'estado', true);

            DB::table('roles')->where('id', $legadoId)->delete();
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // No se revierte: restaurar el rol duplicado reabriría inconsistencias
        // en usuarios, flujos y revisiones que ya fueron consolidadas.
    }

    private function migrarAsignacionesDeRol(int $legadoId, int $canonicoId): void
    {
        if (! Schema::hasTable('model_has_roles')) {
            return;
        }

        DB::table('model_has_roles')
            ->where('role_id', $legadoId)
            ->get(['model_type', 'model_id'])
            ->each(function (object $asignacion) use ($canonicoId): void {
                DB::table('model_has_roles')->insertOrIgnore([
                    'role_id' => $canonicoId,
                    'model_type' => $asignacion->model_type,
                    'model_id' => $asignacion->model_id,
                ]);
            });

        DB::table('model_has_roles')->where('role_id', $legadoId)->delete();
    }

    private function migrarPermisosDeRol(int $legadoId, int $canonicoId): void
    {
        if (! Schema::hasTable('role_has_permissions')) {
            return;
        }

        DB::table('role_has_permissions')
            ->where('role_id', $legadoId)
            ->get(['permission_id'])
            ->each(function (object $permiso) use ($canonicoId): void {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $permiso->permission_id,
                    'role_id' => $canonicoId,
                ]);
            });

        DB::table('role_has_permissions')->where('role_id', $legadoId)->delete();
    }

    private function actualizarRevisionesPendientes(string $tabla, string $columnaEstado, bool $usaSoftDeletes = false): void
    {
        if (! Schema::hasTable($tabla)
            || ! Schema::hasColumn($tabla, 'rol_requerido')
            || ! Schema::hasColumn($tabla, $columnaEstado)) {
            return;
        }

        $consulta = DB::table($tabla)
            ->where('rol_requerido', self::ROL_LEGADO)
            ->where($columnaEstado, 'Pendiente');

        if ($usaSoftDeletes && Schema::hasColumn($tabla, 'deleted_at')) {
            $consulta->whereNull('deleted_at');
        }

        $cambios = ['rol_requerido' => self::ROL_CANONICO];

        if (Schema::hasColumn($tabla, 'updated_at')) {
            $cambios['updated_at'] = now();
        }

        $consulta->update($cambios);
    }
};
