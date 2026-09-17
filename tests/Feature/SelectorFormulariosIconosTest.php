<?php

namespace Tests\Feature;

use App\Models\Personal\Empleado;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * El selector de formularios distingue cada trámite por su icono. El del
 * FORM-DVUS-015 dibujaba una hoja con dos aspas cruzadas, que se leía como
 * «documento anulado» en vez de como un registro de voluntariado.
 */
class SelectorFormulariosIconosTest extends TestCase
{
    use DatabaseTransactions;

    /** Hoja con aspas cruzadas: el icono que se retiró. */
    private const ICONO_DOCUMENTO_TACHADO = 'M11.5 12.5 8 16';

    /** Corazón: convención de voluntariado. */
    private const ICONO_CORAZON = 'M12.01 6.001C6.5 1 1 8 5.782 13.001';

    /** Persona con birrete: el del FORM-DVUS-014, que no se toca. */
    private const ICONO_BIRRETE = 'M14.6144 7.19994';

    public function test_el_form_015_no_usa_el_icono_de_documento_tachado(): void
    {
        $html = $this->selectorPps();

        $this->assertStringNotContainsString(
            self::ICONO_DOCUMENTO_TACHADO,
            $html,
            'El FORM-DVUS-015 volvió a usar el icono de hoja con aspas, que se lee como documento anulado.'
        );
    }

    public function test_el_form_015_usa_un_icono_de_voluntariado(): void
    {
        $html = $this->selectorPps();

        $this->assertStringContainsString('FORM-015 - Registro Proyecto de Voluntariado', $html);
        $this->assertStringContainsString(
            self::ICONO_CORAZON,
            $html,
            'El FORM-DVUS-015 no muestra el icono de voluntariado.'
        );
    }

    public function test_el_icono_del_form_014_no_cambia(): void
    {
        $html = $this->selectorPps();

        $this->assertStringContainsString('FORM-014 - Registro PPS y Servicio Social', $html);
        $this->assertStringContainsString(
            self::ICONO_BIRRETE,
            $html,
            'El icono del FORM-DVUS-014 cambió; solo debía tocarse el del 015.'
        );
    }

    /** El grupo se lee de la query string, no de los parámetros de montaje. */
    private function selectorPps(): string
    {
        $user = User::factory()->create(['email' => 'selector.iconos.'.uniqid().'@example.test']);
        $rol = Role::firstOrCreate(['name' => 'docente', 'guard_name' => 'web']);
        $rol->givePermissionTo(Permission::firstOrCreate([
            'name' => 'docente.proyectos',
            'guard_name' => 'web',
        ]));
        $user->assignRole($rol);

        // El sidebar consulta empleado->firmaProyectoPendientes(); sin empleado
        // la vista revienta antes de llegar al selector.
        Empleado::create([
            'nombre_completo' => 'Docente de prueba',
            'numero_empleado' => (string) random_int(100000, 999999),
            'celular' => '99999999',
            'sexo' => 'Femenino',
            'user_id' => $user->id,
            'tipo_empleado' => 'docente',
        ]);

        return $this->actingAs($user)
            ->get(route('selectorTipoAccion', ['grupo' => 'pps']))
            ->assertOk()
            ->getContent();
    }
}
