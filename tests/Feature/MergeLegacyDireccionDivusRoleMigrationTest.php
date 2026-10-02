<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MergeLegacyDireccionDivusRoleMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->sqliteDisponible()) {
            return;
        }

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);

        DB::purge('sqlite');

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });
        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['role_id', 'model_id', 'model_type']);
        });
        Schema::create('role_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('active_role_id')->nullable();
        });
        Schema::create('flujos_aprobacion_etapas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('rol_revisor_id')->nullable();
        });

        $this->crearTablaRevisiones('firma_proyecto', 'estado_revision', true);
        $this->crearTablaRevisiones('enf_revisiones', 'estado');
        $this->crearTablaRevisiones('programa_revisiones', 'estado', true);
    }

    protected function tearDown(): void
    {
        if (! $this->sqliteDisponible()) {
            parent::tearDown();

            return;
        }

        foreach (['programa_revisiones', 'enf_revisiones', 'firma_proyecto', 'flujos_aprobacion_etapas', 'users', 'role_has_permissions', 'model_has_roles', 'roles'] as $tabla) {
            Schema::dropIfExists($tabla);
        }

        DB::purge('sqlite');

        parent::tearDown();
    }

    public function test_consolida_el_rol_legado_y_conserva_las_revisiones_historicas(): void
    {
        if (! $this->sqliteDisponible()) {
            $this->markTestSkipped('La prueba aislada de migración requiere el controlador pdo_sqlite.');
        }

        DB::table('roles')->insert([
            ['id' => 5, 'name' => 'DIRECCION DIVUS', 'guard_name' => 'web'],
            ['id' => 19, 'name' => 'Director Vinculacion', 'guard_name' => 'web'],
        ]);
        DB::table('model_has_roles')->insert([
            ['role_id' => 5, 'model_type' => 'App\\Models\\User', 'model_id' => 1],
            ['role_id' => 19, 'model_type' => 'App\\Models\\User', 'model_id' => 2],
        ]);
        DB::table('role_has_permissions')->insert([
            ['permission_id' => 10, 'role_id' => 5],
            ['permission_id' => 11, 'role_id' => 5],
            ['permission_id' => 10, 'role_id' => 19],
        ]);
        DB::table('users')->insert(['id' => 1, 'active_role_id' => 5]);
        DB::table('flujos_aprobacion_etapas')->insert(['id' => 1, 'rol_revisor_id' => 5]);

        foreach (['firma_proyecto' => 'estado_revision', 'enf_revisiones' => 'estado', 'programa_revisiones' => 'estado'] as $tabla => $estado) {
            DB::table($tabla)->insert([
                ['id' => 1, 'rol_requerido' => 'DIRECCION DIVUS', $estado => 'Pendiente', 'deleted_at' => null],
                ['id' => 2, 'rol_requerido' => 'DIRECCION DIVUS', $estado => 'Aprobado', 'deleted_at' => null],
            ]);
        }

        $migracion = $this->migracion();
        $migracion->up();
        $migracion->up();

        $this->assertFalse(DB::table('roles')->where('name', 'DIRECCION DIVUS')->exists());
        $this->assertSame(19, DB::table('users')->where('id', 1)->value('active_role_id'));
        $this->assertSame(19, DB::table('flujos_aprobacion_etapas')->where('id', 1)->value('rol_revisor_id'));
        $this->assertSame([1, 2], DB::table('model_has_roles')->where('role_id', 19)->orderBy('model_id')->pluck('model_id')->all());
        $this->assertSame([10, 11], DB::table('role_has_permissions')->where('role_id', 19)->orderBy('permission_id')->pluck('permission_id')->all());

        foreach (['firma_proyecto' => 'estado_revision', 'enf_revisiones' => 'estado', 'programa_revisiones' => 'estado'] as $tabla => $estado) {
            $this->assertSame('Director Vinculacion', DB::table($tabla)->where('id', 1)->value('rol_requerido'));
            $this->assertSame('DIRECCION DIVUS', DB::table($tabla)->where('id', 2)->value('rol_requerido'));
            $this->assertSame('Aprobado', DB::table($tabla)->where('id', 2)->value($estado));
        }
    }

    private function crearTablaRevisiones(string $tabla, string $columnaEstado, bool $softDeletes = false): void
    {
        Schema::create($tabla, function (Blueprint $table) use ($columnaEstado, $softDeletes): void {
            $table->id();
            $table->string('rol_requerido')->nullable();
            $table->string($columnaEstado);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    private function migracion(): object
    {
        return require database_path('migrations/2026_10_02_000001_merge_legacy_direccion_divus_role.php');
    }

    private function sqliteDisponible(): bool
    {
        return in_array('sqlite', \PDO::getAvailableDrivers(), true);
    }
}
