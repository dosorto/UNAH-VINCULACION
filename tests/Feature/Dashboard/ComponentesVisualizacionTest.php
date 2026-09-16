<?php

namespace Tests\Feature\Dashboard;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Formas de visualización del panel.
 *
 * Cada bloque usa la forma que pide su dato —proceso encadenado, columnas,
 * matriz de intensidad, mosaico de áreas— en vez de repetir una misma barra
 * horizontal, que además leía como una carrera entre categorías.
 */
class ComponentesVisualizacionTest extends TestCase
{
    public function test_el_recorrido_encadena_las_etapas_en_orden(): void
    {
        $etapas = [
            ['etiqueta' => 'Sin enviar', 'valor' => 27, 'tono' => 'neutro'],
            ['etiqueta' => 'En revisión', 'valor' => 10, 'tono' => 'info'],
            ['etiqueta' => 'En curso', 'valor' => 3, 'tono' => 'exito'],
        ];

        $html = Blade::render('<x-dashboard.proceso-etapas :etapas="$etapas" :total="40" />', compact('etapas'));

        $this->assertStringContainsString('Sin enviar', $html);
        $this->assertStringContainsString('68%', $html);   // 27/40
        // El expediente recorre las etapas en orden: la forma lleva conectores.
        $this->assertStringContainsString('flex-1 bg-slate-200', $html);
    }

    public function test_el_disco_del_recorrido_escala_por_area_no_por_diametro(): void
    {
        $etapas = [
            ['etiqueta' => 'Muchos', 'valor' => 100, 'tono' => 'info'],
            ['etiqueta' => 'Pocos', 'valor' => 25, 'tono' => 'neutro'],
        ];

        $html = Blade::render('<x-dashboard.proceso-etapas :etapas="$etapas" />', compact('etapas'));

        // Con raíz cuadrada, un cuarto del valor da la mitad del lado (72 → 58),
        // no un cuarto: el ojo compara áreas.
        $this->assertStringContainsString('width: 72px', $html);
        $this->assertStringContainsString('width: 58px', $html);
    }

    public function test_la_matriz_pinta_los_diecisiete_objetivos(): void
    {
        $items = [
            ['etiqueta' => '1. Fin de la pobreza', 'valor' => 13],
            ['etiqueta' => '4. Educación de calidad', 'valor' => 7],
        ];

        $html = Blade::render('<x-dashboard.matriz-ods :items="$items" />', compact('items'));

        foreach (range(1, 17) as $n) {
            $this->assertStringContainsString(">{$n}</span>", $html, "Falta el ODS {$n} en la matriz.");
        }

        $this->assertStringContainsString('Fin de la pobreza', $html);
        $this->assertStringContainsString('Menos', $html);
    }

    public function test_las_celdas_de_ods_abren_su_detalle_en_linea(): void
    {
        $items = [['etiqueta' => '1. Fin de la pobreza', 'valor' => 5]];

        $html = Blade::render('<x-dashboard.matriz-ods :items="$items" />', compact('items'));

        $this->assertStringContainsString('x-collapse', $html);
        $this->assertStringContainsString('activo === 1', $html);
        // Un objetivo sin proyectos también se puede consultar.
        $this->assertStringContainsString('Ningún proyecto declara este objetivo todavía.', $html);
    }

    public function test_la_matriz_distingue_el_cero_de_un_valor_bajo(): void
    {
        $items = [['etiqueta' => '1. Fin de la pobreza', 'valor' => 5]];

        $html = Blade::render('<x-dashboard.matriz-ods :items="$items" />', compact('items'));

        // Sin proyectos usa el gris neutro, no el paso más claro de la rampa:
        // "ninguno" es otra categoría, no una magnitud pequeña.
        $this->assertStringContainsString('bg-slate-100', $html);
    }

    public function test_la_salud_del_flujo_separa_lo_que_tardo_de_lo_que_espera(): void
    {
        // Son dos cosas distintas: la altura es el tiempo que esa etapa tardó
        // en revisiones ya resueltas; "en cola" son los proyectos parados hoy.
        $etapas = [
            ['etiqueta' => 'Director centro', 'valor' => 44.3, 'firmas' => 3, 'porcentaje' => 100],
            ['etiqueta' => 'Coordinador Proyecto', 'valor' => 4.0, 'firmas' => 12, 'porcentaje' => 9],
        ];
        $esperando = [
            'director centro' => ['proyectos' => 6, 'dias_promedio' => 150, 'dias_maximo' => 274],
        ];

        $html = Blade::render(
            '<x-dashboard.salud-flujo :etapas="$etapas" :esperando="$esperando" />',
            compact('etapas', 'esperando')
        );

        $this->assertStringContainsString('44d', $html);
        $this->assertStringContainsString('6 <span class="font-normal">en cola</span>', $html);
        // Más de 30 días de media en rojo; 4 días en verde.
        $this->assertStringContainsString('bg-red-500', $html);
        $this->assertStringContainsString('bg-emerald-500', $html);
        // Columnas: la altura la lleva un style, no una clase de ancho.
        $this->assertStringContainsString('height:', $html);
    }

    public function test_la_salud_del_flujo_explica_que_significa_cada_cosa(): void
    {
        $etapas = [['etiqueta' => 'Director centro', 'valor' => 44.3, 'firmas' => 3, 'porcentaje' => 100]];

        $html = Blade::render('<x-dashboard.salud-flujo :etapas="$etapas" />', compact('etapas'));

        $this->assertStringContainsString('días que tardó esa etapa de media', $html);
        $this->assertStringContainsString('proyectos parados hoy en esa etapa', $html);
    }

    public function test_el_mosaico_reparte_el_area_en_proporcion_al_total(): void
    {
        $items = [
            ['etiqueta' => 'Ingeniería', 'valor' => 10],
            ['etiqueta' => 'Ciencias Médicas', 'valor' => 6],
            ['etiqueta' => 'Odontología', 'valor' => 4],
        ];

        $html = Blade::render('<x-dashboard.mosaico :items="$items" />', compact('items'));

        $this->assertStringContainsString('Ingeniería', $html);
        $this->assertStringContainsString('20', $html);   // total
        // Explica la codificación: sin ello el área no es interpretable.
        $this->assertStringContainsString('El área de cada bloque es su parte del total', $html);
    }

    public function test_el_mosaico_abre_detalle_al_pulsar_un_bloque(): void
    {
        $items = [['etiqueta' => 'Ingeniería', 'valor' => 10]];

        $html = Blade::render('<x-dashboard.mosaico :items="$items" />', compact('items'));

        $this->assertStringContainsString('x-collapse', $html);
        $this->assertStringContainsString('% del total', $html);
    }

    public function test_el_mosaico_ignora_los_valores_en_cero(): void
    {
        $items = [
            ['etiqueta' => 'Con datos', 'valor' => 5],
            ['etiqueta' => 'Sin datos', 'valor' => 0],
        ];

        $html = Blade::render('<x-dashboard.mosaico :items="$items" />', compact('items'));

        $this->assertStringContainsString('Con datos', $html);
        $this->assertStringNotContainsString('Sin datos', $html);
    }

    public function test_el_panel_expandible_llega_cerrado_con_su_resumen_visible(): void
    {
        // Se sabe si hay algo dentro sin tener que abrirlo.
        $html = Blade::render(
            '<x-dashboard.panel-expandible titulo="Unidades académicas" resumen="6">contenido</x-dashboard.panel-expandible>'
        );

        $this->assertStringContainsString('abierto: false', $html);
        $this->assertStringContainsString('Unidades académicas', $html);
        $this->assertStringContainsString('>6<', str_replace(["\n", ' '], ['', ''], $html));
        $this->assertStringContainsString('x-collapse', $html);
        $this->assertStringContainsString('aria-expanded', $html);
    }

    public function test_el_panel_expandible_puede_arrancar_abierto(): void
    {
        $html = Blade::render(
            '<x-dashboard.panel-expandible titulo="Actividad" :abierto="true">contenido</x-dashboard.panel-expandible>'
        );

        $this->assertStringContainsString('abierto: true', $html);
    }

    public function test_los_graficos_ofrecen_su_equivalente_en_tabla(): void
    {
        // Sin vista de tabla los valores solo existirían al pasar el ratón:
        // fuera del alcance de teclado y lector de pantalla.
        $html = Blade::render(
            '<x-dashboard.grafico-apex id="g1" tipo="area" :series="[[\'name\' => \'Proyectos\', \'data\' => [20, 24]]]" :categorias="[\'2025\', \'2026\']" />'
        );

        $this->assertStringContainsString('<details', $html);
        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('24', $html);
    }

    public function test_el_contenedor_del_grafico_reserva_sitio_para_el_eje(): void
    {
        $html = Blade::render('<x-dashboard.grafico-apex id="g2" :alto="200" />');

        $this->assertStringContainsString('min-height: 240px', $html);
    }

    public function test_no_queda_ningun_ranking_de_barras_horizontales(): void
    {
        // Varias barras paralelas compitiendo en un carril común se leen como
        // una carrera entre categorías, que no es lo que estos datos dicen.
        $this->assertFileDoesNotExist(resource_path('views/components/dashboard/ranking-barras.blade.php'));
        $this->assertFileDoesNotExist(resource_path('views/components/dashboard/barra-apilada.blade.php'));
    }
}
