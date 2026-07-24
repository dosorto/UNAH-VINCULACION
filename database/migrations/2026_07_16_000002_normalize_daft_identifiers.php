<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        $legacyPrefix = implode('', ['S', 'G', 'C', 'U']);
        $roleSuffixes = ['Gestor', 'Revisor Etapa 1', 'Revisor Etapa 2'];

        if (Schema::hasTable('roles')) {
            foreach ($roleSuffixes as $suffix) {
                $legacyName = "{$legacyPrefix} {$suffix}";
                $daftName = "DAFT {$suffix}";
                $legacyRole = DB::table('roles')->where('name', $legacyName)->where('guard_name', 'web')->first();

                if (! $legacyRole) {
                    continue;
                }

                $daftRole = DB::table('roles')->where('name', $daftName)->where('guard_name', 'web')->first();

                if (! $daftRole) {
                    DB::table('roles')->where('id', $legacyRole->id)->update([
                        'name' => $daftName,
                        'updated_at' => now(),
                    ]);
                    continue;
                }

                $this->mergeRole((int) $legacyRole->id, (int) $daftRole->id);
            }
        }

        $roleNames = collect($roleSuffixes)->mapWithKeys(fn (string $suffix) => [
            "{$legacyPrefix} {$suffix}" => "DAFT {$suffix}",
        ]);

        foreach (['programa_revisiones', 'enf_revisiones', 'firma_proyectos'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'rol_requerido')) {
                continue;
            }

            foreach ($roleNames as $legacyName => $daftName) {
                DB::table($table)->where('rol_requerido', $legacyName)->update(['rol_requerido' => $daftName]);
            }
        }

        if (Schema::hasTable('users')) {
            foreach (['gestor', 'revisor1', 'revisor2'] as $account) {
                DB::table('users')
                    ->where('email', strtolower($legacyPrefix).".{$account}@unah.test")
                    ->update([
                        'email' => "daft.{$account}@unah.test",
                        'given_name' => 'DAFT',
                        'updated_at' => now(),
                    ]);
            }

            DB::table('users')
                ->where('name', 'like', "{$legacyPrefix}%")
                ->get(['id', 'name'])
                ->each(fn (object $user) => DB::table('users')->where('id', $user->id)->update([
                    'name' => str_replace($legacyPrefix, 'DAFT', $user->name),
                    'updated_at' => now(),
                ]));
        }

        $legacyStoragePrefix = strtolower($legacyPrefix).'/';

        if (Schema::hasTable('tipos_programa') && Schema::hasColumn('tipos_programa', 'plantilla_docx_path')) {
            DB::table('tipos_programa')
                ->where('plantilla_docx_path', 'like', "{$legacyStoragePrefix}%")
                ->get(['id', 'plantilla_docx_path'])
                ->each(function (object $tipoPrograma) use ($legacyStoragePrefix): void {
                    $daftPath = preg_replace('/^'.preg_quote($legacyStoragePrefix, '/').'/', 'daft/', $tipoPrograma->plantilla_docx_path);

                    if (Storage::disk('public')->exists($tipoPrograma->plantilla_docx_path)
                        && ! Storage::disk('public')->exists($daftPath)) {
                        Storage::disk('public')->move($tipoPrograma->plantilla_docx_path, $daftPath);
                    }

                    DB::table('tipos_programa')->where('id', $tipoPrograma->id)->update([
                        'plantilla_docx_path' => $daftPath,
                        'updated_at' => now(),
                    ]);
                });
        }

        if (Schema::hasTable('flujos_aprobacion')) {
            DB::table('flujos_aprobacion')
                ->where('codigo', 'like', "%{$legacyPrefix}%")
                ->orWhere('nombre', 'like', "%{$legacyPrefix}%")
                ->orWhere('descripcion', 'like', "%{$legacyPrefix}%")
                ->get(['id', 'codigo', 'nombre', 'descripcion'])
                ->each(fn (object $flujo) => DB::table('flujos_aprobacion')->where('id', $flujo->id)->update([
                    'codigo' => str_replace($legacyPrefix, 'DAFT', $flujo->codigo),
                    'nombre' => str_replace($legacyPrefix, 'DAFT', $flujo->nombre),
                    'descripcion' => $flujo->descripcion === null
                        ? null
                        : str_replace($legacyPrefix, 'DAFT', $flujo->descripcion),
                    'updated_at' => now(),
                ]));
        }

        if (Schema::hasTable('migrations')) {
            DB::table('migrations')
                ->where('migration', '2026_05_13_000003_add_'.strtolower($legacyPrefix).'_fields_to_asignaturas_table')
                ->delete();
        }
    }

    public function down(): void
    {
        // No se revierten identificadores institucionales para evitar romper relaciones existentes.
    }

    private function mergeRole(int $legacyRoleId, int $daftRoleId): void
    {
        if (Schema::hasTable('model_has_roles')) {
            DB::table('model_has_roles')->where('role_id', $legacyRoleId)->get()->each(function (object $assignment) use ($daftRoleId): void {
                DB::table('model_has_roles')->insertOrIgnore([
                    'role_id' => $daftRoleId,
                    'model_type' => $assignment->model_type,
                    'model_id' => $assignment->model_id,
                ]);
            });
            DB::table('model_has_roles')->where('role_id', $legacyRoleId)->delete();
        }

        if (Schema::hasTable('role_has_permissions')) {
            DB::table('role_has_permissions')->where('role_id', $legacyRoleId)->get()->each(function (object $permission) use ($daftRoleId): void {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $permission->permission_id,
                    'role_id' => $daftRoleId,
                ]);
            });
            DB::table('role_has_permissions')->where('role_id', $legacyRoleId)->delete();
        }

        if (Schema::hasTable('flujos_aprobacion_etapas') && Schema::hasColumn('flujos_aprobacion_etapas', 'rol_revisor_id')) {
            DB::table('flujos_aprobacion_etapas')->where('rol_revisor_id', $legacyRoleId)->update(['rol_revisor_id' => $daftRoleId]);
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'active_role_id')) {
            DB::table('users')->where('active_role_id', $legacyRoleId)->update(['active_role_id' => $daftRoleId]);
        }

        DB::table('roles')->where('id', $legacyRoleId)->delete();
    }
};
