<?php

namespace Tests\Feature;

use Database\Seeders\Personal\PermisosSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * «Administrar Asignaturas» es de administración: en modo docente el menú no
 * debe mostrar Unidad Académica → Asignatura.
 */
class PermisoAdministrarAsignaturasTest extends TestCase
{
    use DatabaseTransactions;

    public function test_solo_admin_administra_asignaturas(): void
    {
        $this->seed(PermisosSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertTrue(Role::findByName('admin', 'web')->hasPermissionTo('unidad-academica.asignatura'));
        $this->assertFalse(Role::findByName('docente', 'web')->hasPermissionTo('unidad-academica.asignatura'));
        $this->assertFalse(Role::findByName('Coordinador Proyecto', 'web')->hasPermissionTo('unidad-academica.asignatura'));
    }
}
