<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\MicrosoftAuthController;
use App\Livewire\User\Users;
use App\Models\Estudiante\Estudiante;
use App\Models\Personal\Empleado;
use App\Models\User;
use App\Services\User\UserManagementService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use ReflectionMethod;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UsersUserManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_usuario_con_permiso_puede_abrir_listado(): void
    {
        $this->actingAs($this->actor())->get(route('Usuarios'))->assertOk();
    }

    public function test_usuario_sin_permiso_recibe_403(): void
    {
        $this->actingAs($this->userWithRoles([$this->role()]))
            ->get(route('Usuarios'))
            ->assertForbidden();
    }

    public function test_encabezado_aparece_una_sola_vez(): void
    {
        $this->actingAs($this->actor());
        $html = Livewire::test(Users::class)->html();

        $this->assertSame(1, substr_count($html, 'Listado de usuarios registrados en el sistema.'));
    }

    public function test_tabla_muestra_roles_y_rol_activo(): void
    {
        $actor = $this->actor();
        $role = $this->role();
        $user = $this->userWithRoles([$role]);
        $this->actingAs($actor);

        Livewire::test(Users::class)
            ->set('search', $user->email)
            ->assertSee($role->name)
            ->assertSee('Rol activo')
            ->assertSee('Estado de acceso');
    }

    public function test_busqueda_por_nombre_o_correo_funciona(): void
    {
        $actor = $this->actor();
        $target = $this->userWithRoles([$this->role()], ['name' => 'Persona Buscable '.uniqid()]);
        $other = $this->userWithRoles([$this->role()], ['name' => 'Persona Oculta '.uniqid()]);
        $this->actingAs($actor);

        Livewire::test(Users::class)
            ->set('search', $target->email)
            ->assertSee($target->name)
            ->assertDontSee($other->name);
    }

    public function test_filtro_por_rol_funciona(): void
    {
        $actor = $this->actor();
        $selectedRole = $this->role();
        $otherRole = $this->role();
        $target = $this->userWithRoles([$selectedRole]);
        $other = $this->userWithRoles([$otherRole]);
        $this->actingAs($actor);

        Livewire::test(Users::class)
            ->set('filterRoleId', (string) $selectedRole->id)
            ->assertSee($target->email)
            ->assertDontSee($other->email);
    }

    public function test_filtro_por_perfil_funciona(): void
    {
        $actor = $this->actor();
        $employeeUser = $this->userWithRoles([$this->role()]);
        $studentUser = $this->userWithRoles([$this->role()]);
        $bothUser = $this->userWithRoles([$this->role()]);
        $plainUser = $this->userWithRoles([$this->role()]);
        $this->employee($employeeUser);
        $this->student($studentUser);
        $this->employee($bothUser);
        $this->student($bothUser);
        $this->actingAs($actor);

        $component = Livewire::test(Users::class)
            ->set('filterProfile', 'empleado')
            ->assertSee($employeeUser->email)
            ->assertDontSee($plainUser->email)
            ->assertDontSee($bothUser->email);

        $component->set('filterProfile', 'estudiante')
            ->assertSee($studentUser->email)
            ->assertDontSee($employeeUser->email);

        $component->set('filterProfile', 'ambos')
            ->assertSee($bothUser->email)
            ->assertDontSee($studentUser->email);

        $component->set('filterProfile', 'sin_perfil')
            ->assertSee($plainUser->email)
            ->assertDontSee($bothUser->email);
    }

    public function test_editar_nombre_funciona(): void
    {
        $actor = $this->actor();
        $target = $this->userWithRoles([$this->role()]);
        $this->actingAs($actor);

        Livewire::test(Users::class)
            ->call('openEdit', $target->id)
            ->set('edit_name', 'Nombre Actualizado')
            ->call('saveIdentity')
            ->assertHasNoErrors();

        $this->assertSame('Nombre Actualizado', $target->fresh()->name);
    }

    public function test_editar_correo_lo_normaliza(): void
    {
        $actor = $this->actor();
        $target = $this->userWithRoles([$this->role()]);
        $email = 'correo.actualizado.'.uniqid().'@unah.edu.hn';
        $this->actingAs($actor);

        Livewire::test(Users::class)
            ->call('openEdit', $target->id)
            ->set('edit_email', '  '.mb_strtoupper($email).'  ')
            ->call('saveIdentity')
            ->assertHasNoErrors();

        $this->assertSame($email, $target->fresh()->email);
    }

    public function test_correo_duplicado_es_rechazado(): void
    {
        $actor = $this->actor();
        $target = $this->userWithRoles([$this->role()]);
        $existing = $this->userWithRoles([$this->role()]);
        $this->actingAs($actor);

        Livewire::test(Users::class)
            ->call('openEdit', $target->id)
            ->set('edit_email', $existing->email)
            ->call('saveIdentity')
            ->assertHasErrors(['edit_email']);
    }

    public function test_edicion_no_modifica_password(): void
    {
        $actor = $this->actor();
        $target = $this->userWithRoles([$this->role()], ['password' => Hash::make('ClaveOriginal2026!')]);
        $original = $target->getRawOriginal('password');
        $this->actingAs($actor);

        Livewire::test(Users::class)
            ->call('openEdit', $target->id)
            ->set('edit_name', 'Cambio sin contraseña')
            ->call('saveIdentity');

        $this->assertSame($original, $target->fresh()->getRawOriginal('password'));
    }

    public function test_edicion_no_modifica_microsoft_id(): void
    {
        $actor = $this->actor();
        $microsoftId = 'microsoft-'.uniqid();
        $target = $this->userWithRoles([$this->role()], ['microsoft_id' => $microsoftId]);
        $this->actingAs($actor);

        Livewire::test(Users::class)
            ->call('openEdit', $target->id)
            ->set('edit_name', 'Cambio compatible Microsoft')
            ->call('saveIdentity');

        $this->assertSame($microsoftId, $target->fresh()->microsoft_id);
    }

    public function test_cambio_de_correo_microsoft_requiere_confirmacion_real_del_backend(): void
    {
        $actor = $this->actor();
        $target = $this->userWithRoles([$this->role()], ['microsoft_id' => 'microsoft-'.uniqid()]);
        $newEmail = 'microsoft.cambio.'.uniqid().'@unah.edu.hn';
        $this->actingAs($actor);

        $component = Livewire::test(Users::class)
            ->call('openEdit', $target->id)
            ->set('edit_email', $newEmail)
            ->set('edit_has_microsoft', false)
            ->call('saveIdentity')
            ->assertHasErrors(['confirm_microsoft_email_change']);

        $component->set('confirm_microsoft_email_change', true)
            ->call('saveIdentity')
            ->assertHasNoErrors();

        $this->assertSame($newEmail, $target->fresh()->email);
    }

    public function test_se_agregan_roles_correctamente(): void
    {
        $actor = $this->actor();
        $roleA = $this->role();
        $roleB = $this->role();
        $target = $this->userWithRoles([$roleA]);
        $this->actingAs($actor);

        Livewire::test(Users::class)
            ->call('openRoles', $target->id)
            ->set('manage_roles', [$roleA->id, $roleB->id])
            ->set('manage_active_role_id', $roleA->id)
            ->call('saveRoles')
            ->assertHasNoErrors();

        $this->assertEqualsCanonicalizing([$roleA->id, $roleB->id], $target->fresh()->roles->pluck('id')->all());
    }

    public function test_retirar_roles_requiere_confirmacion(): void
    {
        $actor = $this->actor();
        $roleA = $this->role();
        $roleB = $this->role();
        $target = $this->userWithRoles([$roleA, $roleB], activeRole: $roleA);
        $this->actingAs($actor);

        $component = Livewire::test(Users::class)
            ->call('openRoles', $target->id)
            ->set('manage_roles', [$roleA->id])
            ->set('manage_active_role_id', $roleA->id)
            ->call('saveRoles')
            ->assertHasErrors(['confirm_roles_removal']);

        $component->set('confirm_roles_removal', true)
            ->call('saveRoles')
            ->assertHasNoErrors();

        $this->assertSame([$roleA->id], $target->fresh()->roles->pluck('id')->all());
    }

    public function test_no_se_puede_retirar_el_ultimo_admin(): void
    {
        $actor = $this->actor();
        $admin = Role::findByName('admin', 'web');
        $fallback = $this->role();
        $target = $this->userWithRoles([$admin, $fallback], activeRole: $admin);

        User::role('admin')->whereKeyNot($target->id)->get()->each(fn (User $user) => $user->removeRole($admin));

        try {
            app(UserManagementService::class)->syncUserRoles($target, [$fallback->id], $fallback->id, $actor, true);
            $this->fail('Debió bloquear la retirada del último administrador.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('último administrador activo', $exception->errors()['roles'][0]);
            $this->assertTrue($target->fresh()->hasRole('admin'));
        }
    }

    public function test_no_se_puede_dejar_usuario_sin_roles(): void
    {
        $target = $this->userWithRoles([$this->role()]);

        $this->expectException(ValidationException::class);
        app(UserManagementService::class)->syncUserRoles($target, [], null, $this->actor());
    }

    public function test_operador_debe_confirmar_retirada_de_su_propio_rol_administrativo(): void
    {
        $actor = $this->actor();
        $currentAdministrativeRole = $actor->roles->first();
        $replacementAdministrativeRole = $this->role(['usuarios.usuarios']);
        $actor->assignRole($replacementAdministrativeRole);

        try {
            app(UserManagementService::class)->syncUserRoles(
                $actor,
                [$replacementAdministrativeRole->id],
                $replacementAdministrativeRole->id,
                $actor,
            );
            $this->fail('Debió exigir confirmación para retirar el rol administrativo propio.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('confirm_administrative_removal', $exception->errors());
            $this->assertTrue($actor->fresh()->hasRole($currentAdministrativeRole));
        }

        app(UserManagementService::class)->syncUserRoles(
            $actor,
            [$replacementAdministrativeRole->id],
            $replacementAdministrativeRole->id,
            $actor,
            true,
        );

        $this->assertFalse($actor->fresh()->hasRole($currentAdministrativeRole));
        $this->assertTrue($actor->fresh()->can('usuarios.usuarios'));
    }

    public function test_active_role_id_debe_pertenecer_a_roles_asignados(): void
    {
        $assigned = $this->role();
        $notAssigned = $this->role();
        $target = $this->userWithRoles([$assigned]);

        $this->expectException(ValidationException::class);
        app(UserManagementService::class)->syncUserRoles($target, [$assigned->id], $notAssigned->id, $this->actor());
    }

    public function test_si_se_retira_rol_activo_se_exige_otro(): void
    {
        $active = $this->role();
        $remainingA = $this->role();
        $remainingB = $this->role();
        $target = $this->userWithRoles([$active, $remainingA, $remainingB], activeRole: $active);

        $this->expectException(ValidationException::class);
        app(UserManagementService::class)->syncUserRoles(
            $target,
            [$remainingA->id, $remainingB->id],
            null,
            $this->actor(),
        );
    }

    public function test_si_rol_activo_sigue_asignado_se_conserva(): void
    {
        $active = $this->role();
        $other = $this->role();
        $target = $this->userWithRoles([$active, $other], activeRole: $active);

        app(UserManagementService::class)->syncUserRoles(
            $target,
            [$active->id, $other->id],
            $active->id,
            $this->actor(),
        );

        $this->assertSame($active->id, $target->fresh()->active_role_id);
    }

    public function test_usuario_sin_empleado_muestra_vincular_perfil(): void
    {
        $actor = $this->actor();
        $target = $this->userWithRoles([$this->role()]);
        $this->actingAs($actor);

        Livewire::test(Users::class)
            ->set('search', $target->email)
            ->assertSee('Vincular perfil laboral');
    }

    public function test_usuario_con_empleado_muestra_ver_empleado(): void
    {
        $actor = $this->actor();
        $target = $this->userWithRoles([$this->role()]);
        $this->employee($target);
        $this->actingAs($actor);

        Livewire::test(Users::class)
            ->set('search', $target->email)
            ->assertSee('Ver empleado')
            ->assertDontSee('Vincular perfil laboral');
    }

    public function test_operacion_de_roles_hace_rollback_si_falla_persistencia(): void
    {
        $roleA = $this->role();
        $roleB = $this->role();
        $target = $this->userWithRoles([$roleA]);
        $service = new class extends UserManagementService
        {
            protected function persistRoles(User $user, Collection $roles): void
            {
                parent::persistRoles($user, $roles);
                throw new RuntimeException('Falla controlada');
            }
        };

        try {
            $service->syncUserRoles($target, [$roleB->id], $roleB->id, $this->actor());
            $this->fail('La falla controlada debió interrumpir la operación.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Falla controlada', $exception->getMessage());
            $this->assertSame([$roleA->id], $target->fresh()->roles->pluck('id')->all());
            $this->assertSame($roleA->id, $target->fresh()->active_role_id);
        }
    }

    public function test_metodos_livewire_sensibles_verifican_autorizacion_interna(): void
    {
        $unauthorized = $this->userWithRoles([$this->role()]);
        $target = $this->userWithRoles([$this->role()]);
        $this->actingAs($unauthorized);

        Livewire::test(Users::class)
            ->call('openEdit', $target->id)
            ->assertForbidden();

        $source = file_get_contents(app_path('Livewire/User/Users.php'));
        $this->assertGreaterThanOrEqual(6, substr_count($source, '$this->authorizeUserManagement();'));
    }

    public function test_cambios_de_roles_quedan_auditados_sin_password(): void
    {
        $actor = $this->actor();
        $roleA = $this->role();
        $roleB = $this->role();
        $target = $this->userWithRoles([$roleA]);

        app(UserManagementService::class)->syncUserRoles(
            $target,
            [$roleA->id, $roleB->id],
            $roleA->id,
            $actor,
        );

        $activity = Activity::query()
            ->where('subject_type', User::class)
            ->where('subject_id', $target->id)
            ->where('event', 'roles_updated')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame($actor->id, $activity->causer_id);
        $this->assertArrayHasKey('roles_anteriores', $activity->properties->all());
        $this->assertArrayHasKey('roles_posteriores', $activity->properties->all());
        $this->assertStringNotContainsString('password', mb_strtolower($activity->properties->toJson()));
        $this->assertStringNotContainsString(
            "logOnly(['name', 'email', 'password'])",
            file_get_contents(app_path('Models/User.php')),
        );
    }

    public function test_creacion_exige_roles_y_guarda_usuario_en_transaccion(): void
    {
        $actor = $this->actor();
        $role = $this->role();
        $email = 'nuevo.usuario.'.uniqid().'@unah.edu.hn';
        $this->actingAs($actor);

        Livewire::test(Users::class)
            ->call('openCreate')
            ->set('create_name', 'Nuevo Usuario')
            ->set('create_email', mb_strtoupper($email))
            ->set('create_password', 'ClaveSegura2026!')
            ->set('create_password_confirmation', 'ClaveSegura2026!')
            ->set('create_roles', [$role->id])
            ->call('store')
            ->assertHasNoErrors();

        $user = User::where('email', $email)->firstOrFail();
        $this->assertTrue($user->hasRole($role));
        $this->assertSame($role->id, $user->active_role_id);
        $this->assertTrue(Hash::check('ClaveSegura2026!', $user->password));
    }

    public function test_inicio_microsoft_sigue_resolviendo_mismo_user_por_correo_o_id(): void
    {
        $method = new ReflectionMethod(MicrosoftAuthController::class, 'resolveUser');
        $controller = app(MicrosoftAuthController::class);

        $byEmail = $this->userWithRoles([$this->role()], ['microsoft_id' => null]);
        $emailMicrosoftId = 'ms-email-'.uniqid();
        $resolvedByEmail = $method->invoke($controller, [
            'id' => $emailMicrosoftId,
            'displayName' => $byEmail->name,
        ], $byEmail->email, $emailMicrosoftId);
        $this->assertSame($byEmail->id, $resolvedByEmail->id);

        $microsoftId = 'ms-id-'.uniqid();
        $byId = $this->userWithRoles([$this->role()], ['microsoft_id' => $microsoftId]);
        $resolvedById = $method->invoke($controller, [
            'id' => $microsoftId,
            'displayName' => $byId->name,
        ], $byId->email, $microsoftId);
        $this->assertSame($byId->id, $resolvedById->id);
    }

    private function actor(): User
    {
        $role = $this->role([
            'usuarios.usuarios',
            'empleados.empleados',
            'estudiante.admin',
        ]);

        return $this->userWithRoles([$role]);
    }

    private function userWithRoles(array $roles, array $attributes = [], ?Role $activeRole = null): User
    {
        $user = User::factory()->create(array_merge([
            'email' => 'usuario.'.uniqid().'@unah.edu.hn',
        ], $attributes));
        $user->syncRoles($roles);
        $user->update(['active_role_id' => ($activeRole ?? $roles[0])->id]);

        return $user->fresh(['roles']);
    }

    private function role(array $permissions = []): Role
    {
        $role = Role::create([
            'name' => 'rol-usuarios-'.uniqid(),
            'guard_name' => 'web',
        ]);

        if ($permissions !== []) {
            $role->syncPermissions(collect($permissions)->map(fn (string $name) => Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ])));
        }

        return $role;
    }

    private function employee(User $user): Empleado
    {
        return Empleado::query()->create([
            'user_id' => $user->id,
            'nombre_completo' => $user->name,
            'numero_empleado' => (string) random_int(10000000, 99999999),
            'celular' => '99999999',
            'tipo_empleado' => 'docente',
        ]);
    }

    private function student(User $user): Estudiante
    {
        return Estudiante::query()->create([
            'user_id' => $user->id,
            'nombre' => 'Estudiante',
            'apellido' => 'Prueba',
            'cuenta' => (string) random_int(10000000, 99999999),
        ]);
    }
}
