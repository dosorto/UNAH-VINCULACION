<?php

namespace Tests\Feature;

use App\Livewire\Configuracion\IntegracionesApi;
use App\Models\IntegracionApi;
use App\Models\User;
use App\Services\Integraciones\EstudianteApiService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IntegracionApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_usuario_sin_permiso_recibe_403_en_ruta_y_componente(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('configuracion.integraciones-api'))
            ->assertForbidden();

        Livewire::actingAs($user)
            ->test(IntegracionesApi::class)
            ->assertForbidden();
    }

    public function test_usuario_con_permiso_accede_a_la_configuracion(): void
    {
        $user = $this->authorizedUser();

        Livewire::actingAs($user)
            ->test(IntegracionesApi::class)
            ->assertOk()
            ->assertSee('Integración de estudiantes');
    }

    public function test_crea_y_edita_integracion_con_secretos_cifrados(): void
    {
        $user = $this->authorizedUser();

        Livewire::actingAs($user)
            ->test(IntegracionesApi::class)
            ->call('create')
            ->set('form.nombre', 'Consulta académica')
            ->set('form.codigo', 'consulta-academica')
            ->set('form.base_url', 'https://api.example.test')
            ->set('form.ruta_busqueda', '/estudiantes')
            ->set('form.tipo_autenticacion', 'BEARER')
            ->set('form.token', 'secreto-super-sensible')
            ->set('form.mapeo_campos_json', '{"numero_cuenta":"data.numeroCuenta"}')
            ->call('save')
            ->assertHasNoErrors();

        $integration = IntegracionApi::where('codigo', 'consulta-academica')->firstOrFail();
        $rawToken = DB::table('integraciones_api')->where('id', $integration->id)->value('token_encriptado');

        $this->assertSame('secreto-super-sensible', $integration->token_encriptado);
        $this->assertNotSame('secreto-super-sensible', $rawToken);
        $this->assertStringNotContainsString('secreto-super-sensible', (string) $rawToken);

        Livewire::actingAs($user)
            ->test(IntegracionesApi::class)
            ->call('edit', $integration->id)
            ->set('form.nombre', 'Consulta académica actualizada')
            ->set('form.token', '')
            ->call('save')
            ->assertHasNoErrors();

        $integration->refresh();
        $this->assertSame('Consulta académica actualizada', $integration->nombre);
        $this->assertSame('secreto-super-sensible', $integration->token_encriptado);
    }

    public function test_activa_desactiva_e_invalida_cache(): void
    {
        $user = $this->authorizedUser();
        $integration = $this->integration(['activo' => false]);
        $service = app(EstudianteApiService::class);

        Livewire::actingAs($user)
            ->test(IntegracionesApi::class)
            ->call('toggle', $integration->id)
            ->assertHasNoErrors();

        $this->assertTrue($integration->fresh()->activo);
        $this->assertSame($integration->id, $service->obtenerConfiguracionActiva()?->id);

        Livewire::actingAs($user)
            ->test(IntegracionesApi::class)
            ->call('toggle', $integration->id)
            ->assertHasNoErrors();

        $this->assertFalse($integration->fresh()->activo);
        $this->assertNull($service->obtenerConfiguracionActiva());
    }

    public function test_integracion_protegida_no_puede_eliminarse(): void
    {
        $user = $this->authorizedUser();
        $integration = $this->integration(['protegida' => true]);

        Livewire::actingAs($user)
            ->test(IntegracionesApi::class)
            ->call('delete', $integration->id)
            ->assertForbidden();

        $this->assertDatabaseHas('integraciones_api', ['id' => $integration->id, 'deleted_at' => null]);
    }

    public function test_mapeo_no_permite_campos_arbitrarios(): void
    {
        $user = $this->authorizedUser();

        Livewire::actingAs($user)
            ->test(IntegracionesApi::class)
            ->call('create')
            ->set('form.nombre', 'Mapeo inválido')
            ->set('form.codigo', 'mapeo-invalido')
            ->set('form.base_url', 'https://api.example.test')
            ->set('form.ruta_busqueda', '/estudiantes')
            ->set('form.mapeo_campos_json', '{"password":"secret.path"}')
            ->call('save')
            ->assertHasErrors('form.mapeo_campos_json');

        $this->assertDatabaseMissing('integraciones_api', ['codigo' => 'mapeo-invalido']);
    }

    public function test_auditoria_no_contiene_credenciales(): void
    {
        $user = $this->authorizedUser();
        $this->actingAs($user);
        $integration = $this->integration([
            'tipo_autenticacion' => 'BEARER',
            'token_encriptado' => 'token-que-no-debe-auditarse',
        ]);
        $integration->update(['base_url' => 'https://second.example.test']);

        $audit = DB::table('activity_log')
            ->where('subject_type', IntegracionApi::class)
            ->where('subject_id', $integration->id)
            ->pluck('properties')
            ->implode(' ');

        $this->assertStringNotContainsString('token-que-no-debe-auditarse', $audit);
        $this->assertStringNotContainsString('token_encriptado', $audit);
        $this->assertStringNotContainsString('password_api_encriptado', $audit);
    }

    private function authorizedUser(): User
    {
        $user = User::factory()->create();
        Permission::firstOrCreate(['name' => 'perfil.editar', 'guard_name' => 'web']);
        $permission = Permission::firstOrCreate([
            'name' => 'configuracion.integraciones-api',
            'guard_name' => 'web',
        ]);
        $role = Role::firstOrCreate(['name' => 'admin-integraciones-test', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);
        $user->update(['active_role_id' => $role->id]);

        return $user;
    }

    private function integration(array $overrides = []): IntegracionApi
    {
        return IntegracionApi::create(array_merge([
            'nombre' => 'Estudiantes ' . fake()->unique()->numerify('###'),
            'codigo' => fake()->unique()->slug(2),
            'tipo_perfil' => 'ESTUDIANTE',
            'base_url' => 'https://api.example.test',
            'ruta_busqueda' => '/estudiantes',
            'metodo_http' => 'GET',
            'tipo_autenticacion' => 'NINGUNA',
            'api_key_ubicacion' => 'HEADER',
            'parametro_busqueda' => 'numero_cuenta',
            'timeout_segundos' => 15,
            'reintentos' => 0,
            'verificar_ssl' => true,
            'headers_json' => ['Accept' => 'application/json'],
            'mapeo_campos_json' => ['numero_cuenta' => 'numeroCuenta'],
            'activo' => true,
            'protegida' => false,
        ], $overrides));
    }
}
