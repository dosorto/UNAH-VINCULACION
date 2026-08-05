<?php

namespace Tests\Feature;

use App\Livewire\Login\Login;
use App\Livewire\Personal\Perfil\EditPerfilDocente;
use App\Livewire\Personal\Perfil\EditPerfilEstudiante;
use App\Models\Estudiante\Estudiante;
use App\Models\Personal\CategoriaEmpleado;
use App\Models\UnidadAcademica\Campus;
use App\Models\UnidadAcademica\Carrera;
use App\Models\UnidadAcademica\DepartamentoAcademico;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\User;
use App\Services\Auth\NewUserOnboardingService;
use App\Support\ProfileCompletion;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NewUserOnboardingTest extends TestCase
{
    use DatabaseTransactions;

    public function test_microsoft_crea_usuario_nuevo_y_lo_envia_a_completar_perfil(): void
    {
        config()->set('services.microsoft.enabled', true);
        config()->set('services.microsoft.auto_create_users', true);
        config()->set('services.microsoft.allowed_domains', ['unah.edu.hn']);
        config()->set('services.microsoft.client_id', 'client-test');
        config()->set('services.microsoft.client_secret', 'secret-test');
        config()->set('services.microsoft.tenant', 'organizations');
        config()->set('services.microsoft.redirect', route('login.microsoft.callback'));

        $email = 'nuevo.microsoft.'.uniqid().'@unah.edu.hn';
        $employeeNumber = (string) random_int(10000000, 99999999);
        $state = 'estado-oauth-de-prueba';

        Http::fake([
            'https://login.microsoftonline.com/*' => Http::response([
                'access_token' => 'token-prueba',
            ]),
            'https://graph.microsoft.com/*' => Http::response([
                'id' => 'microsoft-'.uniqid(),
                'displayName' => 'Usuario Microsoft Nuevo',
                'givenName' => 'Usuario',
                'surname' => 'Microsoft Nuevo',
                'mail' => $email,
                'userPrincipalName' => $email,
                'employeeId' => $employeeNumber,
            ]),
        ]);

        $this->withSession(['microsoft_oauth_state' => $state])
            ->get(route('login.microsoft.callback', ['state' => $state, 'code' => 'codigo-prueba']))
            ->assertRedirect(route('completar_perfil'));

        $user = User::query()->where('email', $email)->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->empleado);
        $this->assertSame($employeeNumber, $user->empleado->numero_empleado);
        $this->assertSame('docente', $user->empleado->tipo_empleado);
        $this->assertTrue($user->hasRole('docente'));
        $this->assertTrue($user->hasDirectPermission('perfil.editar'));
    }

    public function test_login_muestra_microsoft_y_acceso_con_correo_y_contrasena(): void
    {
        config()->set('services.microsoft.enabled', true);

        Livewire::test(Login::class)
            ->assertSee('Continuar con tu correo institucional')
            ->assertSee('Correo electrónico')
            ->assertSee('Contraseña')
            ->assertSee('Iniciar sesión')
            ->assertSee('¿Olvidaste tu contraseña?')
            ->assertDontSee('Crear usuario nuevo de prueba');
    }

    public function test_usuario_puede_iniciar_sesion_con_correo_y_contrasena(): void
    {
        $user = User::factory()->create();

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('create')
            ->assertHasNoErrors()
            ->assertRedirect(route('inicio'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_usuario_pendiente_no_puede_navegar_y_admite_el_permiso_historico(): void
    {
        $user = User::factory()->create();
        $user = app(NewUserOnboardingService::class)->prepareEmployeeProfile($user);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertRedirect(route('completar_perfil'));

        $this->actingAs($user)
            ->get(route('enf.tipos'))
            ->assertRedirect(route('completar_perfil'));

        $this->actingAs($user)
            ->get(route('completar_perfil'))
            ->assertOk()
            ->assertSee('Completa tu perfil');

        $legacyPermission = Permission::firstOrCreate([
            'name' => 'cambiar-datos-personales',
            'guard_name' => 'web',
        ]);

        $user->revokePermissionTo('perfil.editar');
        $user->givePermissionTo($legacyPermission);

        $this->actingAs($user->fresh())
            ->get(route('home'))
            ->assertRedirect(route('completar_perfil'));
    }

    public function test_formulario_empleado_exige_datos_firma_sello_y_proyectos_cuando_aplica(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'email' => 'perfil.nuevo.'.uniqid().'@unah.edu.hn',
        ]);
        $user = app(NewUserOnboardingService::class)->prepareEmployeeProfile($user);
        [$centro, $categoria, $departamento, $carrera] = $this->catalogosAcademicos();
        $numeroEmpleado = (string) random_int(10000000, 99999999);

        $component = Livewire::actingAs($user)->test(EditPerfilDocente::class)
            ->set('name', 'Perfil Nuevo')
            ->set('nombre_completo', 'Perfil Nuevo Completo')
            ->set('numero_empleado', $numeroEmpleado)
            ->set('celular', '99999999')
            ->set('centro_facultad_id', $centro->id)
            ->call('save')
            ->assertHasErrors([
                'sexo',
                'categoria_id',
                'departamento_academico_id',
                'carrera_id',
                'tiene_proyectos_previos',
            ]);

        $component
            ->set('sexo', 'Masculino')
            ->set('categoria_id', $categoria->id)
            ->set('departamento_academico_id', $departamento->id)
            ->set('carrera_id', $carrera->id)
            ->set('tiene_proyectos_previos', 'no')
            ->call('save')
            ->assertHasErrors(['firmaUpload']);

        $component
            ->set('firmaUpload', UploadedFile::fake()->image('firma.png', 320, 120))
            ->call('save')
            ->assertHasErrors(['selloUpload']);

        $component
            ->set('selloUpload', UploadedFile::fake()->image('sello.png', 320, 120))
            ->set('tiene_proyectos_previos', 'si')
            ->call('save')
            ->assertHasErrors(['tiene_proyectos_previos']);

        $component
            ->set('tiene_proyectos_previos', 'no')
            ->call('save')
            ->assertRedirect(route('inicio'));

        $user = $user->fresh(['empleado.firma', 'empleado.sello']);

        $this->assertSame($numeroEmpleado, $user->empleado->numero_empleado);
        $this->assertSame('Masculino', $user->empleado->sexo);
        $this->assertSame($categoria->id, $user->empleado->categoria_id);
        $this->assertSame($departamento->id, $user->empleado->departamento_academico_id);
        $this->assertSame($carrera->id, $user->empleado->carrera_id);
        $this->assertNotNull($user->empleado->firma);
        $this->assertNotNull($user->empleado->sello);
        $this->assertFalse(ProfileCompletion::isRequired($user));
        Storage::disk('public')->assertExists($user->empleado->firma->ruta_storage);
        Storage::disk('public')->assertExists($user->empleado->sello->ruta_storage);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk();
    }

    public function test_formulario_estudiante_exige_sexo_centro_y_carrera(): void
    {
        [$centro, , , $carrera] = $this->catalogosAcademicos();
        $role = Role::firstOrCreate(['name' => 'estudiante', 'guard_name' => 'web']);
        $permission = Permission::firstOrCreate(['name' => 'perfil.editar', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);
        $user->givePermissionTo($permission);
        $user->forceFill(['active_role_id' => $role->id])->save();

        Estudiante::create([
            'user_id' => $user->id,
            'nombre' => 'Estudiante',
            'apellido' => 'Prueba',
            'cuenta' => '20269999999',
        ]);

        $component = Livewire::actingAs($user)->test(EditPerfilEstudiante::class)
            ->call('save')
            ->assertHasErrors(['sexo', 'centro_facultad_id', 'carrera_id']);

        $component
            ->set('sexo', 'Femenino')
            ->set('centro_facultad_id', $centro->id)
            ->set('carrera_id', $carrera->id)
            ->call('save')
            ->assertRedirect(route('inicio'));

        $user = $user->fresh(['estudiante']);

        $this->assertSame('Femenino', $user->estudiante->sexo);
        $this->assertSame($centro->id, $user->estudiante->centro_facultad_id);
        $this->assertSame($carrera->id, $user->estudiante->carrera_id);
        $this->assertSame($role->id, $user->active_role_id);
        $this->assertFalse(ProfileCompletion::isRequired($user));
    }

    private function centro(): FacultadCentro
    {
        $campus = Campus::firstOrCreate(
            ['nombre_campus' => 'Campus onboarding'],
            ['siglas' => 'COB', 'direccion' => 'Pruebas', 'telefono' => '00000000', 'url' => 'https://unah.edu.hn'],
        );

        return FacultadCentro::firstOrCreate(
            ['nombre' => 'Centro onboarding'],
            ['es_facultad' => false, 'siglas' => 'COB', 'campus_id' => $campus->id],
        );
    }

    private function catalogosAcademicos(): array
    {
        $centro = $this->centro();
        $categoria = CategoriaEmpleado::firstOrCreate(
            ['nombre' => 'Categoría onboarding'],
            ['descripcion' => 'Pruebas de completar perfil'],
        );
        $departamento = DepartamentoAcademico::firstOrCreate(
            ['nombre' => 'Departamento onboarding', 'centro_facultad_id' => $centro->id],
            ['siglas' => 'DOB'],
        );
        $carrera = Carrera::firstOrCreate(
            ['nombre' => 'Carrera onboarding', 'facultad_centro_id' => $centro->id],
            ['siglas' => 'CRON', 'departamento_academico_id' => $departamento->id],
        );

        if ($carrera->departamento_academico_id !== null) {
            $carrera->forceFill(['departamento_academico_id' => null])->save();
        }

        $carrera->departamentosAcademicos()->syncWithoutDetaching([$departamento->id]);

        return [$centro, $categoria, $departamento, $carrera];
    }
}
