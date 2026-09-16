<?php

namespace Tests\Feature\Dashboard;

use App\Models\Estado\TipoEstado;
use App\Support\Dashboard\EstadosProyecto;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Regresión del catálogo tipo_estado duplicado.
 *
 * La base de producción tiene 33 filas para 16 nombres (TipoEstado usa
 * SoftDeletes y el seeder llama a firstOrCreate, así que cada re-siembra crea
 * un juego nuevo de ids). Todo el panel usaba
 * TipoEstado::where('nombre', X)->first()->id, que se queda con uno solo.
 */
class EstadosProyectoTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        EstadosProyecto::olvidar();
    }

    protected function tearDown(): void
    {
        EstadosProyecto::olvidar();
        parent::tearDown();
    }

    public function test_devuelve_todos_los_ids_cuando_el_nombre_esta_duplicado(): void
    {
        $primero = TipoEstado::create(['nombre' => 'EstadoDePrueba']);
        $segundo = TipoEstado::create(['nombre' => 'EstadoDePrueba']);
        EstadosProyecto::olvidar();

        $ids = EstadosProyecto::ids('EstadoDePrueba');

        $this->assertContains($primero->id, $ids);
        $this->assertContains($segundo->id, $ids, 'El segundo juego de ids duplicados quedaba fuera del conteo.');
        $this->assertCount(2, $ids);
    }

    public function test_la_busqueda_ignora_mayusculas_y_espacios(): void
    {
        $estado = TipoEstado::create(['nombre' => 'Director centro']);
        EstadosProyecto::olvidar();

        // Los paneles buscaban 'Director Centro' mientras config/nexo.php
        // define 'Director centro'; solo funcionaba por la collation de MySQL.
        $this->assertContains($estado->id, EstadosProyecto::ids('Director Centro'));
        $this->assertContains($estado->id, EstadosProyecto::ids('  director CENTRO  '));
    }

    public function test_acepta_una_lista_de_nombres_y_no_repite_ids(): void
    {
        $uno = TipoEstado::create(['nombre' => 'AlfaPrueba']);
        $dos = TipoEstado::create(['nombre' => 'BetaPrueba']);
        EstadosProyecto::olvidar();

        $ids = EstadosProyecto::ids(['AlfaPrueba', 'BetaPrueba', 'AlfaPrueba']);

        $this->assertEqualsCanonicalizing([$uno->id, $dos->id], $ids);
    }

    public function test_devuelve_vacio_si_el_estado_no_existe(): void
    {
        $this->assertSame([], EstadosProyecto::ids('EstadoQueNoExisteEnNingunLado'));
    }

    public function test_en_revision_activa_no_solapa_con_las_demas_tarjetas(): void
    {
        // Si 'Subsanacion' estuviera aquí, la tarjeta "En revisión" incluiría
        // los proyectos de la tarjeta "Subsanación" y los números del panel
        // parecerían contradecirse.
        foreach ([EstadosProyecto::SUBSANACION, EstadosProyecto::EN_CURSO,
            EstadosProyecto::FINALIZADO, EstadosProyecto::BORRADOR] as $excluido) {
            $this->assertNotContains($excluido, EstadosProyecto::EN_REVISION_ACTIVA);
        }
    }

    public function test_autoguardado_cuenta_como_no_enviado(): void
    {
        // En la base real hay proyectos en 'Autoguardado' y ninguno en
        // 'Borrador': contar solo 'Borrador' mostraba cero sin enviar.
        $this->assertContains(EstadosProyecto::AUTOGUARDADO, EstadosProyecto::SIN_ENVIAR);
        $this->assertContains(EstadosProyecto::BORRADOR, EstadosProyecto::SIN_ENVIAR);
    }
}
