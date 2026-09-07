<?php

namespace Tests\Feature;

use App\Livewire\Configuracion\ApiAccessTokens;
use App\Models\ApiAccessScope;
use App\Models\ApiAccessToken;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApiAccessTokensTest extends TestCase
{
    use DatabaseTransactions;

    public function test_requiere_permiso_y_crea_token_mostrando_secreto_solo_en_la_respuesta(): void
    {
        $user = User::factory()->create();
        $permission = Permission::firstOrCreate(['name' => 'configuracion.integraciones-api', 'guard_name' => 'web']);
        $role = Role::firstOrCreate(['name' => 'tokens-test', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);
        $user->update(['active_role_id' => $role->id]);
        $scope = ApiAccessScope::firstOrCreate(['codigo' => 'empleados.proyectos'], ['nombre' => 'Proyectos', 'activo' => true]);

        $component = Livewire::actingAs($user)->test(ApiAccessTokens::class)
            ->set('nombre', 'Postman')
            ->set('scopes', [(string) $scope->id])
            ->call('guardar');

        $token = ApiAccessToken::firstOrFail();
        $this->assertNotNull($component->get('tokenVisible'));
        $this->assertNotSame($component->get('tokenVisible'), $token->token_hash);
        $this->assertTrue($token->scopes->contains($scope));
    }

    public function test_edita_metadatos_y_revoca_sin_cambiar_hash(): void
    {
        $token = ApiAccessToken::create(['nombre' => 'Original', 'prefijo' => 'nexo_test', 'token_hash' => ApiAccessToken::hashToken('secreto'), 'created_by' => User::factory()->create()->id]);
        $hash = $token->token_hash;
        $user = User::factory()->create();
        $permission = Permission::firstOrCreate(['name' => 'configuracion.integraciones-api', 'guard_name' => 'web']);
        $role = Role::firstOrCreate(['name' => 'tokens-edit-test', 'guard_name' => 'web']);
        $role->givePermissionTo($permission); $user->assignRole($role); $user->update(['active_role_id' => $role->id]);

        Livewire::actingAs($user)->test(ApiAccessTokens::class)->call('editar', $token->id)->set('nombre', 'Actualizado')->call('guardar')->call('revocar', $token->id);
        $token->refresh();
        $this->assertSame('Actualizado', $token->nombre);
        $this->assertSame($hash, $token->token_hash);
        $this->assertNotNull($token->revocado_en);
    }
}
