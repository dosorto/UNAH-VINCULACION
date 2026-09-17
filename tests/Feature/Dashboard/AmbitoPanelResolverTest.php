<?php

namespace Tests\Feature\Dashboard;

use App\Models\Personal\Empleado;
use App\Models\User;
use App\Services\Dashboard\AmbitoPanelResolver;
use App\Support\Dashboard\TipoAmbito;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * El ámbito es lo que decide qué ve cada rol en el panel.
 *
 * Antes, DashboardDirector calculaba todo sobre empleado_proyecto, así que los
 * roles que solo revisan (Director centro, Director Vinculacion) veían el panel
 * entero en cero. Estas pruebas fijan el reparto correcto.
 */
class AmbitoPanelResolverTest extends TestCase
{
    use DatabaseTransactions;

    private AmbitoPanelResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = app(AmbitoPanelResolver::class);

        foreach (['proyectos.historial', 'proyectos.solicitados', 'proyectos.revision-final',
            'director.proyectos', 'docente.proyectos'] as $permiso) {
            Permission::findOrCreate($permiso, 'web');
        }
    }

    public function test_sin_usuario_no_hay_ambito(): void
    {
        $this->assertSame(TipoAmbito::Ninguno, $this->resolver->para(null)->tipo);
    }

    public function test_admin_ve_toda_la_unah(): void
    {
        $ambito = $this->resolver->para($this->usuarioCon('admin', []));

        $this->assertSame(TipoAmbito::Global, $ambito->tipo);
        $this->assertSame('Toda la UNAH', $ambito->etiqueta);
    }

    public function test_los_revisores_institucionales_ven_toda_la_unah(): void
    {
        foreach (['proyectos.historial', 'proyectos.solicitados', 'proyectos.revision-final'] as $permiso) {
            $usuario = $this->usuarioCon('RolInstitucional_'.md5($permiso), [$permiso]);

            $this->assertSame(
                TipoAmbito::Global,
                $this->resolver->para($usuario)->tipo,
                "El permiso {$permiso} debe dar alcance institucional."
            );
        }
    }

    public function test_director_de_centro_ve_su_centro_aunque_no_tenga_proyectos_propios(): void
    {
        // El caso que motivó el rediseño: sin filas en empleado_proyecto.
        $usuario = $this->usuarioCon('RolDeCentro', ['director.proyectos'], centroFacultadId: 4);

        $ambito = $this->resolver->para($usuario);

        $this->assertSame(TipoAmbito::Centro, $ambito->tipo);
        $this->assertSame(4, $ambito->centroFacultadId);
        $this->assertFalse($ambito->centroIndeterminado);
    }

    public function test_el_rol_configurado_como_departamento_usa_su_departamento(): void
    {
        config(['nexo.dashboard.ambitos_por_rol' => ['RolDeDepartamento' => 'departamento']]);

        $usuario = $this->usuarioCon(
            'RolDeDepartamento',
            ['director.proyectos'],
            centroFacultadId: 4,
            departamentoAcademicoId: 9,
        );

        $ambito = $this->resolver->para($usuario);

        $this->assertSame(TipoAmbito::Departamento, $ambito->tipo);
        $this->assertSame(9, $ambito->departamentoAcademicoId);
    }

    public function test_sin_centro_asignado_cae_a_los_proyectos_donde_tiene_firma(): void
    {
        // NewUserOnboardingService crea empleados sin centro; degradar a
        // "personal" devolvería el panel vacío que este trabajo elimina.
        $usuario = $this->usuarioCon('RolSinCentro', ['director.proyectos'], centroFacultadId: null);

        $ambito = $this->resolver->para($usuario);

        $this->assertSame(TipoAmbito::Revision, $ambito->tipo);
        $this->assertTrue($ambito->centroIndeterminado);
    }

    public function test_docente_solo_ve_lo_suyo(): void
    {
        $ambito = $this->resolver->para($this->usuarioCon('RolDocente', ['docente.proyectos']));

        $this->assertSame(TipoAmbito::Personal, $ambito->tipo);
    }

    public function test_el_ambito_depende_del_rol_activo_no_de_todos_los_roles(): void
    {
        $usuario = $this->usuarioCon('RolDocenteMixto', ['docente.proyectos']);
        $institucional = $this->rol('RolInstitucionalMixto', ['proyectos.historial']);
        $usuario->assignRole($institucional);

        // Con el rol docente activo no debe ver toda la UNAH pese a tener el
        // otro rol asignado.
        $this->assertSame(TipoAmbito::Personal, $this->resolver->para($usuario->fresh())->tipo);

        $usuario->active_role_id = $institucional->id;
        $usuario->save();

        $this->assertSame(TipoAmbito::Global, $this->resolver->para($usuario->fresh())->tipo);
    }

    public function test_un_permiso_no_sembrado_no_rompe_el_panel(): void
    {
        // hasPermissionTo() lanza PermissionDoesNotExist; como el panel es la
        // primera pantalla tras el login, eso dejaría al usuario sin entrar.
        Permission::where('name', 'proyectos.historial')->delete();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $usuario = $this->usuarioCon('RolSinPermisosSembrados', []);

        $this->assertSame(TipoAmbito::Ninguno, $this->resolver->para($usuario)->tipo);
    }

    /** @param  list<string>  $permisos */
    private function usuarioCon(
        string $rol,
        array $permisos,
        ?int $centroFacultadId = 4,
        ?int $departamentoAcademicoId = null,
    ): User {
        $rolModelo = $this->rol($rol, $permisos);

        $usuario = User::create([
            'name' => 'Prueba '.$rol,
            'email' => 'prueba-'.md5($rol.microtime()).'@unah.hn',
            'password' => bcrypt('secret'),
        ]);

        Empleado::create([
            'user_id' => $usuario->id,
            'nombre_completo' => 'Prueba '.$rol,
            'numero_empleado' => random_int(800000, 899999),
            'tipo_empleado' => 'docente',
            'centro_facultad_id' => $centroFacultadId,
            'departamento_academico_id' => $departamentoAcademicoId,
        ]);

        $usuario->assignRole($rolModelo);
        $usuario->active_role_id = $rolModelo->id;
        $usuario->save();

        return $usuario->fresh();
    }

    /** @param  list<string>  $permisos */
    private function rol(string $nombre, array $permisos): Role
    {
        $rol = Role::findOrCreate($nombre, 'web');

        if ($permisos !== []) {
            $rol->syncPermissions($permisos);
        }

        return $rol;
    }
}
