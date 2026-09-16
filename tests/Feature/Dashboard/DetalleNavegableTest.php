<?php

namespace Tests\Feature\Dashboard;

use App\Services\Dashboard\PanelEstadisticoService;
use App\Support\Dashboard\AmbitoPanel;
use App\Support\Dashboard\EstadosProyecto;
use App\Support\Dashboard\TipoAmbito;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Cada agrupación del panel tiene que dejar llegar al proyecto concreto.
 *
 * Un "5 proyectos" que no deja ver cuáles obliga a salir del panel a buscarlos
 * a mano, con lo que la cifra deja de ser un punto de partida y pasa a ser un
 * callejón sin salida.
 */
class DetalleNavegableTest extends TestCase
{
    use DatabaseTransactions;

    private PanelEstadisticoService $servicio;

    private AmbitoPanel $global;

    protected function setUp(): void
    {
        parent::setUp();

        EstadosProyecto::olvidar();
        config(['nexo.dashboard.cache_ttl' => 0]);

        $this->servicio = app(PanelEstadisticoService::class);
        $this->global = new AmbitoPanel(tipo: TipoAmbito::Global, etiqueta: 'Prueba');
    }

    public function test_el_ranking_de_ods_nombra_los_proyectos_que_lo_componen(): void
    {
        $proyecto = $this->proyectoConOds('Proyecto navegable', '13. Acción por el clima');

        $entrada = collect($this->servicio->rankingPorOds($this->global, 17))
            ->firstWhere('etiqueta', '13. Acción por el clima');

        $this->assertNotNull($entrada, 'El ODS sembrado no aparece en el ranking.');
        $this->assertSame(1, $entrada['valor']);
        $this->assertSame(
            [['id' => $proyecto->id, 'nombre' => 'Proyecto navegable', 'codigo' => $proyecto->codigo_proyecto]],
            $entrada['proyectos'],
            'El detalle debe permitir llegar al proyecto concreto.'
        );
    }

    public function test_los_proyectos_listados_no_exceden_el_recuento_de_su_entrada(): void
    {
        $this->proyectoConOds('Uno', '14. Vida submarina');
        $this->proyectoConOds('Dos', '14. Vida submarina');

        $entradas = $this->servicio->rankingPorOds($this->global, 17);
        $this->assertNotEmpty($entradas);

        foreach ($entradas as $entrada) {
            $this->assertLessThanOrEqual(
                $entrada['valor'],
                count($entrada['proyectos']),
                "Se listan más proyectos de los que dice el recuento en {$entrada['etiqueta']}."
            );
        }
    }

    private function proyectoConOds(string $nombre, string $nombreOds): \App\Models\Proyecto\Proyecto
    {
        $proyecto = \App\Models\Proyecto\Proyecto::create([
            'nombre_proyecto' => $nombre,
            'codigo_proyecto' => 'PRU-'.str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT),
        ]);

        $odsId = \Illuminate\Support\Facades\DB::table('ods')->where('nombre', $nombreOds)->value('id')
            ?: \Illuminate\Support\Facades\DB::table('ods')->insertGetId([
                'nombre' => $nombreOds,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        \Illuminate\Support\Facades\DB::table('proyecto_ods')->insert([
            'proyecto_id' => $proyecto->id,
            'ods_id' => $odsId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $proyecto;
    }

    public function test_la_cobertura_cuenta_proyectos_y_categorias_por_separado(): void
    {
        // La cifra de cabecera debe decir cuántos PROYECTOS hay: el número de
        // facultades no cambia nunca, así que como titular no informa de nada y
        // se lee como si fueran proyectos.
        $cobertura = $this->servicio->coberturaDimension($this->global, 'centro');

        $this->assertArrayHasKey('proyectos', $cobertura);
        $this->assertArrayHasKey('categorias', $cobertura);
        $this->assertIsInt($cobertura['proyectos']);
    }

    public function test_la_cobertura_no_se_limita_al_top_del_ranking(): void
    {
        // Sumar los valores del ranking daría una cifra corta sin avisar,
        // porque está recortado a los primeros N.
        $cobertura = $this->servicio->coberturaDimension($this->global, 'departamento');
        $rankingCorto = $this->servicio->rankingPorDepartamento($this->global, 3);

        $this->assertGreaterThanOrEqual(
            count($rankingCorto),
            $cobertura['categorias'],
            'La cobertura debe contar todas las categorías, no solo las del top.'
        );
    }

    public function test_una_dimension_desconocida_no_revienta(): void
    {
        $this->assertSame(
            ['proyectos' => 0, 'categorias' => 0],
            $this->servicio->coberturaDimension($this->global, 'inventada')
        );
    }

    public function test_el_mosaico_enlaza_cada_proyecto_del_detalle(): void
    {
        $items = [[
            'etiqueta' => 'Ingeniería Eléctrica',
            'valor' => 2,
            'proyectos' => [
                ['id' => 7, 'nombre' => 'Riego fotovoltaico', 'codigo' => 'VRA-001'],
                ['id' => 9, 'nombre' => 'Alumbrado comunitario', 'codigo' => null],
            ],
        ]];

        $html = Blade::render('<x-dashboard.mosaico :items="$items" />', compact('items'));

        $this->assertStringContainsString('Riego fotovoltaico', $html);
        $this->assertStringContainsString('Alumbrado comunitario', $html);
        $this->assertStringContainsString('VRA-001', $html);
        $this->assertStringContainsString('/historialproyecto/7', $html);
        $this->assertStringContainsString('/historialproyecto/9', $html);
    }

    public function test_el_mosaico_avisa_cuando_no_lista_todos(): void
    {
        $items = [[
            'etiqueta' => 'Ingeniería',
            'valor' => 30,
            'proyectos' => [['id' => 1, 'nombre' => 'Uno', 'codigo' => null]],
        ]];

        $html = Blade::render('<x-dashboard.mosaico :items="$items" />', compact('items'));

        $this->assertStringContainsString('y 29 más', $html);
    }

    public function test_la_matriz_de_ods_enlaza_los_proyectos_del_objetivo(): void
    {
        $items = [[
            'etiqueta' => '7. Energía asequible y no contaminante',
            'valor' => 1,
            'proyectos' => [['id' => 12, 'nombre' => 'Paneles solares en Choluteca', 'codigo' => 'VRA-007']],
        ]];

        $html = Blade::render('<x-dashboard.matriz-ods :items="$items" />', compact('items'));

        $this->assertStringContainsString('Paneles solares en Choluteca', $html);
        $this->assertStringContainsString('/historialproyecto/12', $html);
    }
}
