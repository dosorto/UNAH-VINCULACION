<?php

namespace Tests\Feature;

use App\Livewire\Configuracion\ApiAccessTokens;
use App\Models\ApiAccessScope;
use App\Models\ApiAccessToken;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Support\IsolatedApiTestCase;

class ApiAccessTokensTest extends IsolatedApiTestCase
{
    private function administrador(): User
    {
        $user = User::factory()->create();
        $permission = Permission::create(['name' => 'configuracion.integraciones-api', 'guard_name' => 'web']);
        $role = Role::create(['name' => 'tokens-test', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);
        $user->update(['active_role_id' => $role->id]);
        return $user;
    }

    public function test_entrega_secreto_una_vez_sin_snapshot_publico(): void
    {
        $scope = ApiAccessScope::firstOrFail();
        $raw = null;
        $component = Livewire::actingAs($this->administrador())->test(ApiAccessTokens::class)
            ->set('nombre', 'Postman')->set('scopes', [(string) $scope->id])->call('guardar')
            ->assertHasNoErrors()
            ->assertDispatched('api-token-created', function ($event, $params) use (&$raw) {
                $raw = $params['token'];
                return str_starts_with($raw, 'nexo_') && strlen($raw) === 53;
            });
        $this->assertNotNull($raw);
        $token = ApiAccessToken::where('nombre', 'Postman')->firstOrFail();
        $this->assertSame(ApiAccessToken::hashToken($raw), $token->token_hash);
        $this->assertSame(substr($raw, 0, 12), $token->prefijo);
        $this->assertTrue($token->scopes->contains($scope));
        $this->assertFalse(property_exists($component->instance(), 'tokenVisible'));
        $component->assertDontSee($raw)->call('cerrar')->assertNotDispatched('api-token-created');
        $component->call('nuevo')->assertNotDispatched('api-token-created')->assertDontSee($raw);
        $this->assertStringNotContainsString($raw, $token->toJson());
    }

    public function test_edita_y_revoca_sin_cambiar_hash(): void
    {
        $hash = $this->token->token_hash;
        Livewire::actingAs($this->administrador())->test(ApiAccessTokens::class)
            ->call('editar', $this->token->id)->set('nombre', 'Actualizado')->call('guardar')
            ->assertHasNoErrors()->call('revocar', $this->token->id)->assertHasNoErrors();
        $this->token->refresh();
        $this->assertSame('Actualizado', $this->token->nombre);
        $this->assertSame($hash, $this->token->token_hash);
        $this->assertNotNull($this->token->revocado_en);
    }

    public function test_permite_limpiar_la_fecha_de_expiracion(): void
    {
        $this->token->update(['expira_en' => now()->addDay()]);

        Livewire::actingAs($this->administrador())->test(ApiAccessTokens::class)
            ->call('editar', $this->token->id)
            ->set('expira_en', '')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertNull($this->token->fresh()->expira_en);
    }

    public function test_rechaza_usuario_sin_permiso(): void
    {
        Livewire::actingAs(User::factory()->create())->test(ApiAccessTokens::class)->assertForbidden();
    }

    public function test_rechaza_alcances_inactivos_inexistentes_y_duplicados(): void
    {
        $scope = ApiAccessScope::firstOrFail();
        $scope->update(['activo' => false]);
        $component = Livewire::actingAs($this->administrador())->test(ApiAccessTokens::class)->set('nombre', 'Inválido');
        $component->set('scopes', [(string) $scope->id])->call('guardar')->assertHasErrors('scopes.0');
        $component->set('scopes', ['99999'])->call('guardar')->assertHasErrors('scopes.0');
        $scope->update(['activo' => true]);
        $component->set('scopes', [(string) $scope->id, (string) $scope->id])->call('guardar')->assertHasErrors('scopes.0');
        $this->assertDatabaseMissing('api_access_tokens', ['nombre' => 'Inválido']);
    }
}
