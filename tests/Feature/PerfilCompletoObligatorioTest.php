<?php

namespace Tests\Feature;

use App\Livewire\Personal\Perfil\EditPerfilDocente;
use App\Livewire\Personal\Perfil\EditPerfilEstudiante;
use App\Models\Estudiante\Estudiante;
use App\Models\JornadaLaboral;
use App\Models\Personal\CategoriaEmpleado;
use App\Models\Personal\Empleado;
use App\Models\Personal\FirmaSelloEmpleado;
use App\Models\UnidadAcademica\Campus;
use App\Models\UnidadAcademica\Carrera;
use App\Models\UnidadAcademica\DepartamentoAcademico;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\User;
use App\Support\ProfileCompletion;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Quien tenga el perfil incompleto no puede navegar hasta completarlo, aunque no tenga la
 * bandera de incorporación (usuarios creados por un administrador, importados o anteriores).
 */
class PerfilCompletoObligatorioTest extends TestCase
{
    use DatabaseTransactions;

    protected bool $exigePerfilCompleto = true;

    public function test_quitar_fondo_actualiza_firma_existente_y_conserva_el_archivo_original(): void
    {
        if (! extension_loaded('imagick')) {
            $this->markTestSkipped('Se necesita Imagick.');
        }

        Storage::fake('public');
        $user = $this->usuarioConRol('docente');
        $empleado = $this->empleado($user);
        $archivo = UploadedFile::fake()->image('firma.png', 320, 120);
        $rutaOriginal = $archivo->store('images/firmas', 'public');
        $original = FirmaSelloEmpleado::create([
            'empleado_id' => $empleado->id, 'tipo' => 'firma', 'ruta_storage' => $rutaOriginal,
        ]);

        Livewire::actingAs($user)->test(EditPerfilDocente::class)
            ->assertSee('Quitar fondo')
            ->call('quitarFondoFirma')->assertHasNoErrors();

        $firmaActual = $empleado->fresh()->firma;
        $this->assertNotSame($original->id, $firmaActual->id);
        $this->assertNotSame($rutaOriginal, $firmaActual->ruta_storage);
        Storage::disk('public')->assertExists($rutaOriginal);
        Storage::disk('public')->assertExists($firmaActual->ruta_storage);
    }

    public function test_un_empleado_existente_con_datos_faltantes_no_puede_navegar(): void
    {
        $user = $this->usuarioConRol('docente');
        $this->empleado($user, ['celular' => null, 'sexo' => null]);

        $this->assertFalse(ProfileCompletion::hasPendingFlag($user));
        $this->actingAs($user)->get(route('home'))->assertRedirect(route('completar_perfil'));
        $this->actingAs($user)->get(route('enf.tipos'))->assertRedirect(route('completar_perfil'));
        $this->actingAs($user)->get(route('completar_perfil'))
            ->assertOk()
            ->assertSee('Completa tu perfil')
            ->assertSee('Falta: celular, sexo, firma.');
        // Cerrar sesión siempre está disponible.
        $this->actingAs($user)->get(route('logout'))->assertRedirect(route('login'));
    }

    public function test_un_perfil_completo_navega_normalmente(): void
    {
        $user = $this->usuarioConRol('docente');
        $this->conFirma($this->empleado($user));

        $this->assertSame([], ProfileCompletion::missingFields($user->fresh()));
        $this->actingAs($user->fresh())->get(route('home'))->assertOk();
    }

    public function test_completar_el_perfil_no_cambia_los_roles_de_un_usuario_existente(): void
    {
        Storage::fake('public');
        [$centro, $categoria, $departamento] = $this->catalogosAcademicos();
        $user = $this->usuarioConRol('Enlace perfil '.uniqid());
        $rolActivo = $user->active_role_id;
        $this->empleado($user, ['celular' => null, 'sexo' => null, 'categoria_id' => null, 'centro_facultad_id' => null, 'departamento_academico_id' => null]);

        Livewire::actingAs($user)->test(EditPerfilDocente::class)
            ->assertSet('completandoPerfil', true)
            ->assertSet('numeroEmpleadoEditable', false)
            ->set('celular', '99999999')
            ->set('sexo', 'Femenino')
            ->set('categoria_id', $categoria->id)
            ->set('centro_facultad_id', $centro->id)
            ->set('departamento_academico_id', $departamento->id)
            ->set('tiene_proyectos_previos', 'no')
            ->set('firmaUpload', UploadedFile::fake()->image('firma.png', 320, 120))
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('inicio'));

        $user = $user->fresh();
        $this->assertFalse(ProfileCompletion::isRequired($user));
        $this->assertSame($rolActivo, $user->active_role_id);
        $this->assertFalse($user->hasRole('docente'));
        $this->actingAs($user)->get(route('home'))->assertOk();
    }

    public function test_el_numero_de_empleado_que_falta_se_pide_y_no_puede_repetirse(): void
    {
        Storage::fake('public');
        [$centro, $categoria, $departamento] = $this->catalogosAcademicos();
        $otro = $this->empleado($this->usuarioConRol('docente'));
        $user = $this->usuarioConRol('docente');
        $empleado = $this->conFirma($this->empleado($user, ['numero_empleado' => null]));

        $this->assertSame(['número de empleado'], ProfileCompletion::missingFields($user->fresh()));

        $componente = Livewire::actingAs($user->fresh())->test(EditPerfilDocente::class)
            ->assertSet('numeroEmpleadoEditable', true)
            ->set('categoria_id', $categoria->id)
            ->set('centro_facultad_id', $centro->id)
            ->set('departamento_academico_id', $departamento->id)
            ->set('tiene_proyectos_previos', 'no')
            ->call('save')
            ->assertHasErrors(['numero_empleado' => 'required']);

        $componente->set('numero_empleado', $otro->numero_empleado)
            ->call('save')
            ->assertHasErrors(['numero_empleado' => 'unique']);

        $nuevo = 'N-'.uniqid();
        $componente->set('numero_empleado', $nuevo)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('inicio'));

        $this->assertSame($nuevo, $empleado->fresh()->numero_empleado);
        $this->assertFalse(ProfileCompletion::isRequired($user->fresh()));
    }

    public function test_el_numero_de_empleado_que_ya_existe_no_se_puede_cambiar(): void
    {
        Storage::fake('public');
        [$centro, $categoria, $departamento] = $this->catalogosAcademicos();
        $user = $this->usuarioConRol('docente');
        $empleado = $this->conFirma($this->empleado($user, ['celular' => null]));
        $original = $empleado->numero_empleado;

        Livewire::actingAs($user->fresh())->test(EditPerfilDocente::class)
            ->assertSet('numeroEmpleadoEditable', false)
            ->set('numero_empleado', 'CAMBIADO-'.uniqid())
            ->set('celular', '99999999')
            ->set('categoria_id', $categoria->id)
            ->set('centro_facultad_id', $centro->id)
            ->set('departamento_academico_id', $departamento->id)
            ->set('tiene_proyectos_previos', 'no')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($original, $empleado->fresh()->numero_empleado);
    }

    public function test_un_estudiante_con_datos_faltantes_tambien_queda_obligado(): void
    {
        [$centro, , , $carrera] = $this->catalogosAcademicos();
        $user = $this->usuarioConRol('estudiante');
        $rolActivo = $user->active_role_id;
        Estudiante::create(['user_id' => $user->id, 'nombre' => 'Estudiante', 'apellido' => 'Pendiente', 'cuenta' => '2026'.random_int(1000000, 9999999)]);

        $this->assertSame(['sexo', 'facultad o centro', 'carrera'], ProfileCompletion::missingFields($user->fresh()));
        $this->actingAs($user->fresh())->get(route('home'))->assertRedirect(route('completar_perfil'));

        Livewire::actingAs($user->fresh())->test(EditPerfilEstudiante::class)
            ->set('sexo', 'Femenino')
            ->set('centro_facultad_id', $centro->id)
            ->set('carrera_id', $carrera->id)
            ->call('save')
            ->assertRedirect(route('inicio'));

        $this->assertFalse(ProfileCompletion::isRequired($user->fresh()));
        $this->assertSame($rolActivo, $user->fresh()->active_role_id);
    }

    public function test_una_cuenta_sin_perfil_recibe_el_de_empleado_para_completarlo(): void
    {
        $user = $this->usuarioConRol('docente');

        $this->actingAs($user)->get(route('home'))->assertRedirect(route('completar_perfil'));
        $this->actingAs($user)->get(route('completar_perfil'))
            ->assertOk()
            ->assertSee('Perfil de Empleado')
            ->assertDontSee('No existe un perfil de empleado o estudiante');

        $empleado = $user->fresh()->empleado;
        $this->assertNotNull($empleado);
        $this->assertSame('docente', $empleado->tipo_empleado);
        $this->assertTrue(ProfileCompletion::isRequired($user->fresh()));
    }

    public function test_a_quien_le_borraron_el_empleado_no_se_le_crea_otro(): void
    {
        $user = $this->usuarioConRol('docente');
        $this->empleado($user)->delete();

        $this->actingAs($user)->get(route('home'))->assertRedirect(route('completar_perfil'));
        $this->actingAs($user)->get(route('completar_perfil'))
            ->assertOk()
            ->assertSee('No existe un perfil de empleado o estudiante');

        $this->assertNull($user->fresh()->empleado);
        $this->assertSame(1, Empleado::withTrashed()->where('user_id', $user->id)->count());
    }

    public function test_la_jornada_laboral_se_elige_del_catalogo(): void
    {
        Storage::fake('public');
        $jornada = $this->jornada();
        $user = $this->usuarioConRol('docente');
        $empleado = $this->conFirma($this->empleado($user, ['jornada_laboral' => null, 'celular' => null]));

        $this->assertSame(['celular', 'jornada laboral'], ProfileCompletion::missingFields($user->fresh()));
        $this->actingAs($user->fresh())->get(route('completar_perfil'))
            ->assertSee('Jornada laboral')
            ->assertSee($jornada->etiqueta);

        $componente = Livewire::actingAs($user->fresh())->test(EditPerfilDocente::class)
            ->set('celular', '99999999')
            ->set('tiene_proyectos_previos', 'no')
            ->call('save')
            ->assertHasErrors(['jornada_laboral' => 'required']);

        $componente->set('jornada_laboral', 'Tiempo completo')
            ->call('save')
            ->assertHasErrors(['jornada_laboral' => 'in']);

        $componente->set('jornada_laboral', $jornada->etiqueta)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('inicio'));

        $this->assertSame($jornada->etiqueta, $empleado->fresh()->jornada_laboral);
        $this->assertFalse(ProfileCompletion::isRequired($user->fresh()));
    }

    public function test_una_jornada_escrita_a_mano_se_cambia_por_una_del_catalogo_al_completar(): void
    {
        $user = $this->usuarioConRol('docente');
        $this->conFirma($this->empleado($user, ['jornada_laboral' => 'Tiempo completo', 'sexo' => null]));

        Livewire::actingAs($user->fresh())->test(EditPerfilDocente::class)
            ->assertSet('completandoPerfil', true)
            ->assertSet('jornada_laboral', '');
    }

    // ── Escenarios ──────────────────────────────────────────────────────────

    private function usuarioConRol(string $nombreRol): User
    {
        $rol = Role::firstOrCreate(['name' => $nombreRol, 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($rol);
        $user->forceFill(['active_role_id' => $rol->id])->save();

        return $user->fresh();
    }

    /** Empleado con todos los datos del perfil, salvo los que se sobrescriban. */
    private function empleado(User $user, array $datos = []): Empleado
    {
        [$centro, $categoria, $departamento] = $this->catalogosAcademicos();

        return Empleado::create(array_merge([
            'user_id' => $user->id,
            'nombre_completo' => 'Empleado perfil',
            'numero_empleado' => 'PC-'.uniqid(),
            'tipo_empleado' => 'docente',
            'celular' => '88888888',
            'jornada_laboral' => $this->jornada()->etiqueta,
            'sexo' => 'Masculino',
            'categoria_id' => $categoria->id,
            'centro_facultad_id' => $centro->id,
            'departamento_academico_id' => $departamento->id,
        ], $datos));
    }

    private function jornada(): JornadaLaboral
    {
        return JornadaLaboral::firstOrCreate(['hora_inicio' => '05:10:00', 'hora_fin' => '13:10:00'], ['activo' => true, 'orden' => 0]);
    }

    private function conFirma(Empleado $empleado): Empleado
    {
        FirmaSelloEmpleado::create(['empleado_id' => $empleado->id, 'tipo' => 'firma', 'ruta_storage' => 'images/firmas/prueba.png', 'estado' => true]);

        return $empleado;
    }

    private function catalogosAcademicos(): array
    {
        $campus = Campus::firstOrCreate(
            ['nombre_campus' => 'Campus perfil obligatorio'],
            ['siglas' => 'CPO', 'direccion' => 'Pruebas', 'telefono' => '00000000', 'url' => 'https://unah.edu.hn'],
        );
        $centro = FacultadCentro::firstOrCreate(
            ['nombre' => 'Centro perfil obligatorio'],
            ['es_facultad' => false, 'siglas' => 'CPO', 'campus_id' => $campus->id],
        );
        $categoria = CategoriaEmpleado::firstOrCreate(['nombre' => 'Categoría perfil obligatorio'], ['descripcion' => 'Pruebas']);
        $departamento = DepartamentoAcademico::firstOrCreate(
            ['nombre' => 'Departamento perfil obligatorio', 'centro_facultad_id' => $centro->id],
            ['siglas' => 'DPO'],
        );
        $carrera = Carrera::firstOrCreate(
            ['nombre' => 'Carrera perfil obligatorio', 'facultad_centro_id' => $centro->id],
            ['siglas' => 'CPO', 'departamento_academico_id' => $departamento->id],
        );

        return [$centro, $categoria, $departamento, $carrera];
    }
}
