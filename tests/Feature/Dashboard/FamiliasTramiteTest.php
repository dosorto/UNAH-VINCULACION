<?php

namespace Tests\Feature\Dashboard;

use App\Services\Dashboard\RegistroFamiliasTramite;
use App\Support\Dashboard\AmbitoPanel;
use App\Support\Dashboard\EstadosProyecto;
use App\Support\Dashboard\TipoAmbito;
use App\Support\Dashboard\Tramites\FamiliaEnf;
use App\Support\Dashboard\Tramites\FamiliaPorEstados;
use App\Support\Dashboard\Tramites\FamiliaPps;
use App\Support\Dashboard\Tramites\FamiliaProyectos;
use App\Support\Dashboard\Tramites\FamiliaTramite;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * NEXO digitaliza decenas de formularios y cada uno trae su propio itinerario.
 * El panel no puede conocerlos uno a uno: pregunta al registro qué familias hay
 * y a cada una por su recorrido.
 *
 * Estas pruebas fijan justo eso — que dar de alta un formulario nuevo no exija
 * tocar el panel — y que cada familia declare solo las fases que recorre.
 */
class FamiliasTramiteTest extends TestCase
{
    use DatabaseTransactions;

    private RegistroFamiliasTramite $registro;

    private AmbitoPanel $global;

    protected function setUp(): void
    {
        parent::setUp();

        EstadosProyecto::olvidar();
        config(['nexo.dashboard.cache_ttl' => 0]);

        $this->registro = app(RegistroFamiliasTramite::class);
        $this->global = new AmbitoPanel(tipo: TipoAmbito::Global, etiqueta: 'Prueba');
    }

    public function test_el_registro_carga_las_familias_declaradas_en_configuracion(): void
    {
        $claves = $this->registro->todas()->map->clave()->all();

        $this->assertContains('proyectos', $claves);
        $this->assertContains('pps', $claves);
        $this->assertContains('enf', $claves);
    }

    public function test_cada_familia_declara_solo_las_fases_que_recorre(): void
    {
        // Una práctica profesional se agota en la autorización: no tiene
        // informes, así que no puede mostrar fases que nunca alcanzará.
        $pps = (new FamiliaPps)->itinerario();
        $claves = array_column($pps, 'clave');

        $this->assertSame(['solicitud', 'autorizado'], $claves);
        $this->assertNotContains('intermedio', $claves);
        $this->assertNotContains('final', $claves);
    }

    public function test_el_proyecto_recorre_inscripcion_informes_y_cierre(): void
    {
        $claves = array_column((new FamiliaProyectos)->itinerario(), 'clave');

        $this->assertSame(['inscripcion', 'ejecucion', 'intermedio', 'final', 'cerrado'], $claves);
    }

    public function test_educacion_no_formal_tiene_su_propio_itinerario(): void
    {
        // ENF no usa estado_proyecto: lleva su estado en una columna propia y
        // repite el ciclo de etapas dentro de cada proceso.
        $enf = new FamiliaEnf;

        $this->assertNotInstanceOf(FamiliaPorEstados::class, $enf);
        $this->assertContains('intermedio', array_column($enf->itinerario(), 'clave'));
    }

    public function test_una_familia_nueva_aparece_en_el_panel_sin_tocar_codigo(): void
    {
        // Es la prueba de que el panel escala a los formularios que faltan:
        // se declara una familia con FamiliaPorEstados y el registro la sirve.
        config(['nexo.dashboard.familias_tramite' => [
            FamiliaProyectos::class,
            FamiliaInventadaParaPrueba::class,
        ]]);

        $registro = new RegistroFamiliasTramite;
        $claves = $registro->todas()->map->clave()->all();

        $this->assertContains('inventada', $claves);
        $this->assertSame(
            'Trámite de prueba',
            $registro->porClave('inventada')?->etiqueta()
        );
    }

    public function test_el_registro_resuelve_la_familia_de_un_formulario(): void
    {
        $this->assertSame('pps', $this->registro->porFormulario('FORM-DVUS-014')?->clave());
        $this->assertSame('proyectos', $this->registro->porFormulario('FORM-DVUS-001')?->clave());
        $this->assertSame('enf', $this->registro->porFormulario('FORM-DVUS-018')?->clave());
        $this->assertNull($this->registro->porFormulario('FORM-DVUS-999'));
    }

    public function test_los_carriles_traen_itinerario_y_conteos_de_cada_familia(): void
    {
        $carriles = $this->registro->carriles($this->global, incluirVacias: true);

        $this->assertNotEmpty($carriles);

        foreach ($carriles as $carril) {
            $this->assertArrayHasKey('etiqueta', $carril);
            $this->assertArrayHasKey('total', $carril);
            $this->assertArrayHasKey('sin_iniciar', $carril);
            $this->assertNotEmpty($carril['fases'], "La familia {$carril['clave']} no declara fases.");

            foreach ($carril['fases'] as $fase) {
                $this->assertArrayHasKey('valor', $fase);
                $this->assertIsInt($fase['valor']);
            }
        }
    }

    public function test_las_familias_vacias_se_omiten_salvo_que_se_pidan(): void
    {
        $conVacias = $this->registro->carriles($this->global, incluirVacias: true);
        $sinVacias = $this->registro->carriles($this->global, incluirVacias: false);

        $this->assertGreaterThanOrEqual(count($sinVacias), count($conVacias));

        foreach ($sinVacias as $carril) {
            $this->assertGreaterThan(0, $carril['total']);
        }
    }

    public function test_una_familia_que_no_sabe_acotarse_al_centro_lo_advierte(): void
    {
        // PPS guarda la facultad como texto libre, sin clave foránea: su cifra
        // es institucional aunque el ámbito sea de centro, y el panel debe
        // decirlo en vez de mostrar un cero engañoso.
        $centro = new AmbitoPanel(
            tipo: TipoAmbito::Centro,
            centroFacultadId: 4,
            etiqueta: 'Centro de prueba',
        );

        $this->assertFalse((new FamiliaPps)->admiteAmbito($centro));
        $this->assertTrue((new FamiliaProyectos)->admiteAmbito($centro));
        $this->assertTrue((new FamiliaEnf)->admiteAmbito($centro));
    }

    public function test_toda_familia_registrada_cumple_el_contrato(): void
    {
        foreach ($this->registro->todas() as $familia) {
            $this->assertInstanceOf(FamiliaTramite::class, $familia);
            $this->assertNotSame('', $familia->clave());
            $this->assertNotSame('', $familia->etiqueta());
            $this->assertNotEmpty($familia->formularios(), $familia->clave().' no declara formularios.');
        }
    }
}

/**
 * Familia de mentira que representa uno de los formularios pendientes de
 * digitalizar: sigue el patrón habitual, así que se declara sin escribir
 * lógica propia.
 */
class FamiliaInventadaParaPrueba extends FamiliaPorEstados
{
    public function __construct()
    {
        parent::__construct(
            clave: 'inventada',
            etiqueta: 'Trámite de prueba',
            modelo: \App\Models\Proyecto\Proyecto::class,
            formularios: ['FORM-DVUS-099'],
            fases: [
                ['clave' => 'solicitud', 'etiqueta' => 'Solicitud', 'tono' => 'info', 'estados' => ['En revision']],
                ['clave' => 'listo', 'etiqueta' => 'Resuelto', 'tono' => 'exito', 'estados' => ['Finalizado']],
            ],
        );
    }
}
