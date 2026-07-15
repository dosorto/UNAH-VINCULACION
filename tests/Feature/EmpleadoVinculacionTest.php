<?php

namespace Tests\Feature;

use App\Livewire\Personal\Empleado\CreateEmpleado;
use App\Models\Personal\Empleado;
use App\Models\UnidadAcademica\Campus;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\User;
use App\Services\Personal\EmpleadoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmpleadoVinculacionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_busca_correo_inexistente_y_lo_normaliza(): void
    {
        Livewire::test(CreateEmpleado::class)
            ->set('email', '  NUEVO.'.uniqid().'@UNAH.EDU.HN  ')
            ->call('buscarCorreo')
            ->assertSet('estadoBusqueda', CreateEmpleado::ESTADO_USUARIO_NUEVO)
            ->assertSet('email', fn (string $email): bool => $email === mb_strtolower(trim($email)))
            ->assertSee('No existe una cuenta con este correo');
    }

    public function test_busca_usuario_existente_sin_empleado(): void
    {
        $role = $this->role();
        $user = $this->usuario(['email' => 'sin.empleado.'.uniqid().'@unah.edu.hn']);
        $user->assignRole($role);

        Livewire::test(CreateEmpleado::class)
            ->set('email', $user->email)
            ->call('buscarCorreo')
            ->assertSet('estadoBusqueda', CreateEmpleado::ESTADO_VINCULAR)
            ->assertSet('usuarioExistenteId', $user->id)
            ->assertSee('todavía no posee perfil laboral')
            ->assertSee($role->name)
            ->assertSee('Vincular perfil laboral');
    }

    public function test_busca_usuario_existente_con_empleado_y_bloquea_el_formulario(): void
    {
        $user = $this->usuario();
        $this->empleado($user);

        Livewire::test(CreateEmpleado::class)
            ->set('email', $user->email)
            ->call('buscarCorreo')
            ->assertSet('estadoBusqueda', CreateEmpleado::ESTADO_CON_EMPLEADO)
            ->assertSee('Este usuario ya posee un perfil de empleado')
            ->assertDontSee('Vincular perfil laboral');
    }

    public function test_crea_usuario_y_empleado_con_roles_en_una_transaccion(): void
    {
        $role = $this->role();
        $email = 'nuevo.empleado.'.uniqid().'@unah.edu.hn';

        $user = app(EmpleadoService::class)->crearUsuarioConEmpleado(
            ['name' => 'Nuevo Empleado '.uniqid(), 'email' => $email],
            $this->datosEmpleado(),
            [$role->id],
        );

        $this->assertSame($email, $user->email);
        $this->assertNotNull($user->empleado);
        $this->assertTrue($user->hasRole($role));
        $this->assertSame($role->id, $user->active_role_id);
    }

    public function test_vincula_usuario_existente_sin_crear_otro_usuario(): void
    {
        $user = $this->usuario();
        $totalUsuarios = User::withTrashed()->count();

        app(EmpleadoService::class)->convertirUsuarioEnEmpleado($user, $this->datosEmpleado());

        $this->assertSame($totalUsuarios, User::withTrashed()->count());
        $this->assertDatabaseHas('empleado', ['user_id' => $user->id]);
    }

    public function test_vinculacion_preserva_password_microsoft_roles_y_rol_activo_valido(): void
    {
        $role = $this->role();
        $password = Hash::make('secreto-original');
        $user = $this->usuario([
            'password' => $password,
            'microsoft_id' => 'microsoft-'.uniqid(),
        ]);
        $user->assignRole($role);
        $user->update(['active_role_id' => $role->id]);

        app(EmpleadoService::class)->convertirUsuarioEnEmpleado($user, $this->datosEmpleado());
        $actual = $user->fresh(['roles']);

        $this->assertSame($password, $actual->getRawOriginal('password'));
        $this->assertSame($user->microsoft_id, $actual->microsoft_id);
        $this->assertSame([$role->id], $actual->roles->pluck('id')->all());
        $this->assertSame($role->id, $actual->active_role_id);
    }

    public function test_asigna_primer_rol_activo_si_falta_y_el_usuario_ya_tiene_roles(): void
    {
        $role = $this->role();
        $user = $this->usuario(['active_role_id' => null]);
        $user->assignRole($role);

        app(EmpleadoService::class)->convertirUsuarioEnEmpleado($user, $this->datosEmpleado());

        $this->assertSame($role->id, $user->fresh()->active_role_id);
    }

    public function test_bloquea_numero_de_empleado_duplicado(): void
    {
        $numero = (string) random_int(10000000, 99999999);
        $this->empleado($this->usuario(), ['numero_empleado' => $numero]);
        $user = $this->usuario();

        try {
            app(EmpleadoService::class)->convertirUsuarioEnEmpleado(
                $user,
                $this->datosEmpleado(['numero_empleado' => $numero]),
            );
            $this->fail('La vinculación debió rechazar el número duplicado.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('numero_empleado', $exception->errors());
            $this->assertNull($user->fresh()->empleado);
        }
    }

    public function test_bloquea_doble_vinculacion(): void
    {
        $user = $this->usuario();
        $service = app(EmpleadoService::class);
        $service->convertirUsuarioEnEmpleado($user, $this->datosEmpleado());

        $this->expectException(ValidationException::class);
        $service->convertirUsuarioEnEmpleado($user, $this->datosEmpleado());
    }

    public function test_detecta_empleado_eliminado_y_exige_recuperacion_explicita(): void
    {
        $user = $this->usuario();
        $empleado = $this->empleado($user);
        $empleado->delete();

        Livewire::test(CreateEmpleado::class)
            ->set('email', $user->email)
            ->call('buscarCorreo')
            ->assertSet('estadoBusqueda', CreateEmpleado::ESTADO_EMPLEADO_ELIMINADO)
            ->assertSee('Debe recuperarse explícitamente');
    }

    public function test_revierte_usuario_nuevo_si_falla_creacion_del_empleado(): void
    {
        $email = 'rollback.'.uniqid().'@unah.edu.hn';
        $service = new class extends EmpleadoService
        {
            protected function crearEmpleado(array $data): Empleado
            {
                throw new RuntimeException('Falla controlada');
            }
        };

        try {
            $service->crearUsuarioConEmpleado(
                ['name' => 'Rollback '.uniqid(), 'email' => $email],
                $this->datosEmpleado(),
                [$this->role()->id],
            );
            $this->fail('La falla controlada debió interrumpir la operación.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Falla controlada', $exception->getMessage());
            $this->assertDatabaseMissing('users', ['email' => $email]);
        }
    }

    public function test_usuario_sin_permiso_recibe_403(): void
    {
        $this->actingAs($this->usuario())
            ->get(route('crearEmpleado'))
            ->assertForbidden();
    }

    public function test_empleado_vinculado_queda_disponible_para_selector_form_dvus_001(): void
    {
        $administrador = $this->usuario();
        $user = $this->usuario();
        $vinculado = app(EmpleadoService::class)
            ->convertirUsuarioEnEmpleado($user, $this->datosEmpleado())
            ->empleado;

        $opciones = Empleado::where('user_id', '!=', $administrador->id)
            ->orderBy('nombre_completo')
            ->pluck('nombre_completo', 'id');

        $this->assertSame($vinculado->nombre_completo, $opciones->get($vinculado->id));
        $this->assertStringContainsString(
            "Empleado::where('user_id', '!=', auth()->id())",
            file_get_contents(app_path('Livewire/Proyectos/Vinculacion/CreateProyectoVinculacion.php')),
        );
    }

    public function test_rechaza_correo_fuera_del_dominio_institucional(): void
    {
        Livewire::test(CreateEmpleado::class)
            ->set('email', 'persona@example.com')
            ->call('buscarCorreo')
            ->assertHasErrors(['email']);
    }

    public function test_usuario_nuevo_requiere_al_menos_un_rol(): void
    {
        $email = 'sin.rol.'.uniqid().'@unah.edu.hn';
        $centro = $this->centro();

        Livewire::test(CreateEmpleado::class)
            ->set('email', $email)
            ->call('buscarCorreo')
            ->set('name', 'Usuario Sin Rol '.uniqid())
            ->set('nombre_completo', 'Usuario Sin Rol')
            ->set('numero_empleado', (string) random_int(10000000, 99999999))
            ->set('celular', '99999999')
            ->set('centro_facultad_id', $centro->id)
            ->call('create')
            ->assertHasErrors(['create_roles']);
    }

    public function test_correo_corregido_de_ibis_permite_vincular_sin_crear_otro_usuario(): void
    {
        $user = User::withTrashed()->find(36);

        if (! $user || $user->email !== 'ijzavala@unah.edu.hn' || Empleado::withTrashed()->where('user_id', 36)->exists()) {
            $this->markTestSkipped('La base de prueba no contiene a User 36 pendiente con el correo corregido.');
        }

        $totalUsuarios = User::withTrashed()->count();

        Livewire::test(CreateEmpleado::class)
            ->set('email', 'ijzavala@unah.edu.hn')
            ->call('buscarCorreo')
            ->assertSet('email', 'ijzavala@unah.edu.hn')
            ->assertSet('usuarioExistenteId', 36)
            ->assertSet('estadoBusqueda', CreateEmpleado::ESTADO_VINCULAR)
            ->assertSee('Vincular perfil laboral');

        $this->assertSame($totalUsuarios, User::withTrashed()->count());
        $this->assertSame('ijzavala@unah.edu.hn', $user->fresh()->email);
    }

    public function test_diseno_conserva_tarjetas_campos_responsive_errores_y_botones_del_modulo(): void
    {
        $vista = file_get_contents(resource_path('views/livewire/personal/empleado/create-empleado.blade.php'));

        foreach (['rounded-xl', 'border-gray-200', 'dark:bg-gray-900', 'sm:grid-cols-2',
            "@error('email')", 'wire:loading', 'Cancelar', 'Nombre Completo', 'Facultad o Centro'] as $patron) {
            $this->assertStringContainsString($patron, $vista);
        }

        $this->assertStringNotContainsString('filament', mb_strtolower($vista));
        $this->assertStringNotContainsString('selector previo', mb_strtolower($vista));
    }

    private function usuario(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'email' => 'empleado.'.uniqid().'@unah.edu.hn',
        ], $attributes));
    }

    private function role(): Role
    {
        return Role::firstOrCreate(['name' => 'empleado-prueba', 'guard_name' => 'web']);
    }

    private function centro(): FacultadCentro
    {
        $campus = Campus::firstOrCreate(
            ['nombre_campus' => 'Campus pruebas empleados'],
            ['siglas' => 'CPE', 'direccion' => 'Pruebas', 'telefono' => '00000000', 'url' => 'https://unah.edu.hn'],
        );

        return FacultadCentro::firstOrCreate(
            ['nombre' => 'Centro pruebas empleados'],
            ['es_facultad' => false, 'siglas' => 'CPE', 'campus_id' => $campus->id],
        );
    }

    private function datosEmpleado(array $attributes = []): array
    {
        return array_merge([
            'nombre_completo' => 'Empleado de Prueba',
            'numero_empleado' => (string) random_int(10000000, 99999999),
            'celular' => '99999999',
            'jornada_laboral' => 'Tiempo completo',
            'categoria_id' => null,
            'centro_facultad_id' => $this->centro()->id,
            'departamento_academico_id' => null,
            'tipo_empleado' => 'docente',
        ], $attributes);
    }

    private function empleado(User $user, array $attributes = []): Empleado
    {
        return Empleado::create(array_merge($this->datosEmpleado(), $attributes, [
            'user_id' => $user->id,
        ]));
    }
}
