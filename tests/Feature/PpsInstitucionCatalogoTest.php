<?php

namespace Tests\Feature;

use App\Livewire\Proyectos\PpsInstituciones\PpsInstitucionList;
use App\Models\PpsInstitucion;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Catálogo de instituciones del FORM-DVUS-014: el administrador corrige o completa los datos
 * que el registro de PPS/SS ya no modifica.
 */
class PpsInstitucionCatalogoTest extends TestCase
{
    use DatabaseTransactions;

    private function institucion(array $datos = []): PpsInstitucion
    {
        return PpsInstitucion::create($datos + [
            'nombre' => 'Institución de Catálogo de Prueba', 'nacionalidad' => 'Nacional',
            'tipo' => 'ong', 'sector' => 'educacion_investigacion', 'direccion' => 'Centro',
            'representante_legal' => 'Ana López', 'telefono' => '2222-0000', 'correo_rrhh' => 'rrhh@example.com',
        ]);
    }

    public function test_solo_quien_tiene_el_permiso_abre_el_catalogo(): void
    {
        $admin = User::role('admin')->first();
        $this->assertNotNull($admin, 'Falta un usuario administrador en la base de pruebas.');

        $this->actingAs($admin)->get(route('pps.instituciones'))->assertOk()->assertSee('Instituciones PPS / Servicio Social');

        $sinPermiso = User::query()->get()->first(fn (User $user) => ! $user->can('pps.instituciones'));
        $this->actingAs($sinPermiso)->get(route('pps.instituciones'))->assertForbidden();
    }

    public function test_el_admin_crea_y_corrige_con_los_datos_del_formato(): void
    {
        Livewire::test(PpsInstitucionList::class)
            ->call('openCreate')
            ->set('form.nombre', 'Empresa Extranjera de Prueba')
            ->set('form.nacionalidad', 'Internacional')
            ->call('save')
            ->assertHasErrors(['form.pais', 'form.tipo', 'form.sector', 'form.direccion', 'form.representante_legal', 'form.telefono', 'form.correo_rrhh'])
            // El país sale del catálogo de países.
            ->set('form.pais', 'País Que No Existe')
            ->call('save')
            ->assertHasErrors(['form.pais' => 'exists'])
            ->set('form.pais', 'Costa Rica')
            ->set('form.tipo', 'sector_privado')
            ->set('form.sector', 'produccion')
            ->set('form.direccion', 'San José')
            ->set('form.representante_legal', 'María Díaz')
            ->set('form.telefono', '+506 2222 0000')
            ->set('form.correo_rrhh', 'rrhh@empresa.test')
            ->call('save')
            ->assertHasNoErrors();

        $institucion = PpsInstitucion::where('nombre', 'Empresa Extranjera de Prueba')->firstOrFail();
        $this->assertSame('Costa Rica', $institucion->pais_visible);

        Livewire::test(PpsInstitucionList::class)
            ->call('openEdit', $institucion->id)
            ->set('form.telefono', '+506 3333 0000')
            ->call('save')
            ->assertHasNoErrors();
        $this->assertSame('+506 3333 0000', $institucion->fresh()->telefono);
    }

    public function test_no_se_repite_el_nombre_ni_se_elimina_una_institucion_en_uso(): void
    {
        $this->institucion(['nombre' => 'Institución Repetida de Prueba']);
        $libre = $this->institucion(['nombre' => 'Institución Libre de Prueba']);

        Livewire::test(PpsInstitucionList::class)
            ->call('openEdit', $libre->id)
            ->set('form.nombre', 'institución repetida de prueba')
            ->call('save')
            ->assertHasErrors(['form.nombre' => 'unique']);

        $usada = $this->institucion(['nombre' => 'Institución Usada de Prueba']);
        $usuario = User::factory()->create();
        Livewire::actingAs($usuario)->test(\App\Livewire\Proyectos\Vinculacion\CreatePpsServicioSocial::class)
            ->set('autoguardadoActivo', false)
            ->call('guardarBorrador');
        \App\Models\PpsServicioSocial::where('created_by', $usuario->id)->latest('id')->firstOrFail()
            ->update(['pps_institucion_id' => $usada->id]);

        Livewire::test(PpsInstitucionList::class)
            ->call('delete', $usada->id)
            ->call('delete', $libre->id);

        $this->assertNotSoftDeleted($usada);
        $this->assertSoftDeleted($libre);
    }
}
