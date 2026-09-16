<?php

namespace Tests\Feature\Dashboard;

use App\Livewire\Inicio\Dashboards\DasboardDocente;
use App\Livewire\Inicio\Dashboards\Dashboard;
use App\Livewire\Inicio\Dashboards\DashboardDirector;
use App\Models\Personal\Empleado;
use App\Models\User;
use App\Support\Dashboard\EstadosProyecto;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Los tres paneles se montan de verdad, con un usuario de cada rol.
 *
 * El resto de pruebas del panel comprueban el markup leyendo los archivos, así
 * que un error en tiempo de ejecución —una variable que la vista espera y el
 * componente no pasa, una ruta inexistente— se les escaparía entero. Estas sí
 * ejecutan el render.
 */
class PanelesRenderizanTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        EstadosProyecto::olvidar();
        config(['nexo.dashboard.cache_ttl' => 0]);

        foreach ([
            'proyectos.historial', 'proyectos.solicitados', 'proyectos.revision-final',
            'director.proyectos', 'docente.proyectos', 'docente.crear-proyecto',
            'perfil.editar', 'dashboard.admin', 'dashboard.docente', 'dashboard.director',
        ] as $permiso) {
            Permission::findOrCreate($permiso, 'web');
        }
    }

    public function test_el_panel_del_docente_se_monta(): void
    {
        $usuario = $this->usuarioCon('docente-prueba', ['docente.proyectos', 'docente.crear-proyecto']);

        Livewire::actingAs($usuario)
            ->test(DasboardDocente::class)
            ->assertOk()
            ->assertSee('Mi panel de vinculación')
            ->assertSee('Mis proyectos');
    }

    public function test_el_panel_del_docente_permite_ampliar_y_cambiar_el_rango(): void
    {
        $usuario = $this->usuarioCon('docente-acciones', ['docente.proyectos']);

        Livewire::actingAs($usuario)
            ->test(DasboardDocente::class)
            ->call('verMasFormularios')
            ->assertSet('formulariosVisibles', 25)
            ->call('alternarRangoGrafico')
            ->assertSet('mesesGrafico', 36)
            ->assertDispatched('panel-grafico-actualizado');
    }

    public function test_el_panel_del_director_de_centro_muestra_su_unidad_sin_proyectos_propios(): void
    {
        // El caso que motivó el rediseño: quien solo revisa no tiene filas en
        // empleado_proyecto y antes veía el panel entero en cero.
        $usuario = $this->usuarioCon('director-centro-prueba', ['director.proyectos'], centroFacultadId: 4);

        Livewire::actingAs($usuario)
            ->test(DashboardDirector::class)
            ->assertOk()
            ->assertSee('Esperan tu revisión')
            // Sin proyectos propios, esa sección no se dibuja en vez de salir vacía.
            ->assertDontSee('Aquellos en los que participas');
    }

    public function test_el_panel_del_director_institucional_se_monta(): void
    {
        $usuario = $this->usuarioCon('director-vinculacion-prueba', ['proyectos.historial']);

        Livewire::actingAs($usuario)
            ->test(DashboardDirector::class)
            ->assertOk()
            ->assertSee('Panel estadístico');
    }

    public function test_el_panel_del_director_permite_ampliar_pendientes(): void
    {
        $usuario = $this->usuarioCon('director-acciones', ['director.proyectos'], centroFacultadId: 4);

        Livewire::actingAs($usuario)
            ->test(DashboardDirector::class)
            ->call('verMasPendientes')
            ->assertSet('pendientesVisibles', 18)
            ->call('verMasProyectos')
            ->assertSet('proyectosVisibles', 20);
    }

    public function test_el_panel_institucional_se_monta(): void
    {
        $usuario = $this->usuarioCon('admin', ['proyectos.historial']);

        Livewire::actingAs($usuario)
            ->test(Dashboard::class)
            ->assertOk()
            ->assertSee('Panel estadístico institucional')
            ->assertSee('Proyectos vigentes');
    }

    public function test_el_panel_institucional_filtra_por_nombre(): void
    {
        $usuario = $this->usuarioCon('admin-busqueda', ['proyectos.historial']);

        Livewire::actingAs($usuario)
            ->test(Dashboard::class)
            ->set('buscarDocente', 'nombre que no existe')
            ->assertOk();
    }

    public function test_quien_no_completo_su_perfil_es_redirigido(): void
    {
        $usuario = $this->usuarioCon('sin-perfil', ['docente.proyectos', 'perfil.editar']);

        Livewire::actingAs($usuario)
            ->test(DasboardDocente::class)
            ->assertRedirect(route('completar_perfil'));
    }

    /** @param  list<string>  $permisos */
    private function usuarioCon(string $rol, array $permisos, ?int $centroFacultadId = 4): User
    {
        $rolModelo = Role::findOrCreate($rol, 'web');

        if ($permisos !== []) {
            $rolModelo->syncPermissions($permisos);
        }

        $usuario = User::create([
            'name' => 'Prueba '.$rol,
            'email' => 'panel-'.md5($rol.microtime()).'@unah.hn',
            'password' => bcrypt('secret'),
        ]);

        Empleado::create([
            'user_id' => $usuario->id,
            'nombre_completo' => 'Prueba '.$rol,
            'numero_empleado' => 'PAN-'.random_int(10000, 99999),
            'tipo_empleado' => 'docente',
            'centro_facultad_id' => $centroFacultadId,
        ]);

        $usuario->assignRole($rolModelo);
        $usuario->active_role_id = $rolModelo->id;
        $usuario->save();

        return $usuario->fresh();
    }
}
