<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * «Trazabilidad de Registros» (bandeja de firmas) ya no es visible para el rol docente;
 * solo para los roles que firman.
 */
class TrazabilidadRegistrosPermisoTest extends TestCase
{
    use DatabaseTransactions;

    private const RUTAS = ['SolicitudProyectosDocente', 'FichasActualizacionPorFirmar', 'AprobadoProyectosDocente', 'RechazadoProyectosDocente'];

    public function test_el_rol_docente_no_tiene_el_permiso_y_los_roles_firmantes_si(): void
    {
        $this->assertFalse(Role::findByName('docente')->hasPermissionTo('docente.trazabilidad'));
        $this->assertTrue(Role::findByName('docente')->hasPermissionTo('docente.proyectos'));

        foreach (['Coordinador Proyecto', 'Enlace Vinculacion', 'Jefe Departamento', 'Director centro', 'Revisor Vinculacion', 'Director Vinculacion'] as $rol) {
            $this->assertTrue(Role::findByName($rol)->hasPermissionTo('docente.trazabilidad'), "El rol {$rol} debe ver Trazabilidad de Registros.");
        }
    }

    public function test_las_rutas_y_el_menu_usan_el_permiso_de_trazabilidad(): void
    {
        foreach (self::RUTAS as $nombre) {
            $this->assertContains('can:docente.trazabilidad', Route::getRoutes()->getByName($nombre)->gatherMiddleware(), $nombre);
        }

        $grupo = collect(config('navbar.0.items'))->firstWhere('titulo', 'Trazabilidad de Registros');
        $this->assertSame(['docente.trazabilidad'], $grupo['permisos']);
        $this->assertSame(['docente.trazabilidad'], collect($grupo['children'])->pluck('permiso')->unique()->values()->all());
    }

    public function test_un_usuario_solo_docente_no_entra_a_la_bandeja(): void
    {
        $docente = User::factory()->create();
        $docente->assignRole('docente');
        $docente->update(['active_role_id' => Role::findByName('docente')->id]);

        foreach (self::RUTAS as $nombre) {
            $this->actingAs($docente)->get(route($nombre))->assertForbidden();
        }

        // Sigue registrando acciones: docente.proyectos no cambia.
        $this->assertTrue($docente->fresh()->can('docente.proyectos'));
    }
}
