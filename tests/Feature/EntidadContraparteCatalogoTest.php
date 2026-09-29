<?php

namespace Tests\Feature;

use App\Livewire\Proyectos\Contrapartes\EntidadContraparteList;
use App\Models\Proyecto\EntidadContraparte;
use App\Models\Proyecto\EntidadContraparteProyecto;
use App\Models\Proyecto\Proyecto;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Catálogo de entidades contraparte: el administrador corrige o completa los datos que
 * los docentes ya no pueden modificar desde el proyecto.
 */
class EntidadContraparteCatalogoTest extends TestCase
{
    use DatabaseTransactions;

    private function contraparte(array $datos = []): EntidadContraparte
    {
        return EntidadContraparte::create($datos + [
            'nombre' => 'Contraparte de Catálogo de Prueba', 'tipo_entidad' => 'ong',
            'nombre_contacto' => 'Ana López', 'cargo_contacto' => 'Directora',
            'telefono' => '2222-0000', 'correo' => 'ong@example.com',
        ]);
    }

    public function test_solo_quien_tiene_el_permiso_abre_el_catalogo(): void
    {
        $admin = User::role('admin')->first();
        $this->assertNotNull($admin, 'Falta un usuario administrador en la base de pruebas.');

        $this->actingAs($admin)->get(route('proyectos.contrapartes'))
            ->assertOk()
            ->assertSee('Entidades contraparte');

        $sinPermiso = User::query()->get()->first(fn (User $user) => !$user->can('proyectos.contrapartes'));
        $this->assertNotNull($sinPermiso);

        $this->actingAs($sinPermiso)->get(route('proyectos.contrapartes'))->assertForbidden();
    }

    public function test_el_admin_corrige_y_completa_los_datos(): void
    {
        $contraparte = $this->contraparte();

        Livewire::test(EntidadContraparteList::class)
            ->call('openEdit', $contraparte->id)
            ->assertSet('form.nombre', 'Contraparte de Catálogo de Prueba')
            ->set('form.rtn', '08019999000021')
            ->set('form.telefono', '3333-0000')
            ->set('form.correo', '')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('formModal', false);

        $contraparte->refresh();
        $this->assertSame('08019999000021', $contraparte->rtn);
        $this->assertSame('3333-0000', $contraparte->telefono);
        $this->assertNull($contraparte->correo);
    }

    public function test_valida_rtn_y_evita_duplicados(): void
    {
        $this->contraparte(['nombre' => 'Contraparte Existente de Prueba', 'rtn' => '08019999000022']);
        $contraparte = $this->contraparte();

        Livewire::test(EntidadContraparteList::class)
            ->call('openEdit', $contraparte->id)
            ->set('form.rtn', str_repeat('9', 51))
            ->call('save')
            ->assertHasErrors(['form.rtn' => 'max'])
            ->set('form.rtn', '08019999000022')
            ->call('save')
            ->assertHasErrors(['form.rtn' => 'unique'])
            ->set('form.rtn', '')
            ->set('form.nombre', 'contraparte existente de prueba')
            ->call('save')
            ->assertHasErrors(['form.nombre' => 'unique']);

        // El RTN es texto libre: letras, números, puntos y guiones.
        Livewire::test(EntidadContraparteList::class)
            ->call('openCreate')
            ->set('form.nombre', 'Universidad Extranjera de Prueba')
            ->set('form.tipo_entidad', 'internacional')
            ->set('form.rtn', 'EIN 12-3456.789/B')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('EIN 12-3456.789/B', EntidadContraparte::where('nombre', 'Universidad Extranjera de Prueba')->value('rtn'));
    }

    public function test_un_duplicado_existente_se_puede_seguir_editando(): void
    {
        $this->contraparte(['nombre' => 'Duplicado de Prueba']);
        $duplicado = $this->contraparte(['nombre' => 'Duplicado de Prueba']);

        Livewire::test(EntidadContraparteList::class)
            ->call('openEdit', $duplicado->id)
            ->set('form.telefono', '4444-0000')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('4444-0000', $duplicado->fresh()->telefono);
    }

    public function test_no_se_elimina_una_contraparte_usada_en_proyectos(): void
    {
        $usada = $this->contraparte(['nombre' => 'Contraparte Usada de Prueba']);
        $libre = $this->contraparte(['nombre' => 'Contraparte Libre de Prueba']);
        $proyecto = Proyecto::create(['nombre_proyecto' => 'Proyecto con contraparte']);
        EntidadContraparteProyecto::create([
            'proyecto_id' => $proyecto->id, 'entidad_contraparte_id' => $usada->id, 'nombre' => $usada->nombre,
        ]);

        Livewire::test(EntidadContraparteList::class)
            ->call('delete', $usada->id)
            ->call('delete', $libre->id);

        $this->assertNotSoftDeleted($usada);
        $this->assertSoftDeleted($libre);

        Livewire::test(EntidadContraparteList::class)->call('restore', $libre->id);

        $this->assertNotSoftDeleted($libre);
    }
}
