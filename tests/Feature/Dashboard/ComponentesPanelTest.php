<?php

namespace Tests\Feature\Dashboard;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Los componentes del panel se renderizan de verdad aquí.
 *
 * Un fallo de props o de sintaxis Blade solo aparece al compilar la vista, no
 * al leerla, y estos componentes se usan en las tres pantallas de inicio.
 */
class ComponentesPanelTest extends TestCase
{
    public function test_la_cabecera_muestra_titulo_rol_y_ambito(): void
    {
        $html = Blade::render(
            '<x-dashboard.cabecera titulo="Panel estadístico" subtitulo="Resumen" rol="Director centro" ambito="Ingeniería" />'
        );

        $this->assertStringContainsString('Panel estadístico', $html);
        $this->assertStringContainsString('Director centro', $html);
        $this->assertStringContainsString('Ingeniería', $html);
        // El azul institucional sustituye al amarillo genérico anterior.
        $this->assertStringContainsString('bg-primary-900', $html);
        $this->assertStringNotContainsString('bg-yellow-500', $html);
    }

    public function test_el_kpi_formatea_el_valor_y_admite_enlace(): void
    {
        $html = Blade::render(
            '<x-dashboard.kpi etiqueta="Proyectos" :valor="1234" tono="acento" href="/inicio" />'
        );

        $this->assertStringContainsString('1,234', $html);
        $this->assertStringContainsString('Proyectos', $html);
        $this->assertStringContainsString('href="/inicio"', $html);
    }

    public function test_el_kpi_usa_figuras_proporcionales(): void
    {
        // tabular-nums está reservado para columnas que deben alinearse; en una
        // cifra grande suelta deja los dígitos sueltos.
        $html = Blade::render('<x-dashboard.kpi etiqueta="Total" :valor="121" />');

        $this->assertStringNotContainsString('tabular-nums', $html);
    }

    public function test_la_cifra_de_cabecera_resume_sin_gastar_una_tarjeta(): void
    {
        // Horas, aporte o personas alcanzadas se leen una vez: como tarjeta
        // propia ocupaban media pantalla para tres números.
        $html = Blade::render(
            '<x-dashboard.cifra-cabecera :valor="7981" etiqueta="Horas de vinculación" icono="heroicon-o-clock" />'
        );

        $this->assertStringContainsString('7,981', $html);
        $this->assertStringContainsString('Horas de vinculación', $html);
        $this->assertStringContainsString('text-white', $html);
    }

    public function test_los_pendientes_marcan_la_antiguedad_con_color_e_icono(): void
    {
        // El color de estado nunca va solo: acompaña siempre a la cifra de días.
        $items = [
            (object) ['tipo' => 'Proyecto', 'codigo' => 'P-1', 'nombre' => 'Reciente', 'etapa' => 'Revisión', 'dias_espera' => 2, 'href' => null],
            (object) ['tipo' => 'PPS/SS', 'codigo' => null, 'nombre' => 'Detenido', 'etapa' => null, 'dias_espera' => 40, 'href' => null],
        ];

        $html = Blade::render('<x-dashboard.lista-pendientes :items="$items" />', compact('items'));

        $this->assertStringContainsString('2 días', $html);
        $this->assertStringContainsString('40 días', $html);
        $this->assertStringContainsString('text-emerald-700', $html);
        $this->assertStringContainsString('text-red-700', $html);
    }

    public function test_los_pendientes_vacios_muestran_un_estado_amable(): void
    {
        $html = Blade::render('<x-dashboard.lista-pendientes :items="[]" vacio="Bandeja al día" />');

        $this->assertStringContainsString('Bandeja al día', $html);
    }

    public function test_el_chip_distingue_los_estados_del_flujo(): void
    {
        $enCurso = Blade::render('<x-dashboard.chip-estado nombre="En curso" />');
        $subsanar = Blade::render('<x-dashboard.chip-estado nombre="Subsanacion" />');
        $vacio = Blade::render('<x-dashboard.chip-estado :nombre="null" />');

        $this->assertStringContainsString('text-emerald-700', $enCurso);
        $this->assertStringContainsString('text-red-700', $subsanar);
        $this->assertStringContainsString('Sin estado', $vacio);
    }

    public function test_la_linea_de_tiempo_pliega_los_comentarios_largos(): void
    {
        $items = [
            (object) [
                'tipo_elemento' => 'Proyecto',
                'nombre_elemento' => 'Proyecto de prueba',
                'estado' => 'Subsanacion',
                'es_actual' => true,
                'comentario' => str_repeat('Observación detallada del revisor. ', 10),
                'fecha' => '01/01/2026 10:00',
                'href' => null,
            ],
        ];

        $html = Blade::render('<x-dashboard.linea-tiempo :items="$items" />', compact('items'));

        $this->assertStringContainsString('Proyecto de prueba', $html);
        $this->assertStringContainsString('Ver más', $html);
        $this->assertStringContainsString('line-clamp-3', $html);
    }

    public function test_el_aviso_de_alerta_ofrece_una_accion(): void
    {
        $html = Blade::render(
            '<x-dashboard.aviso tono="alerta" titulo="Sin centro" mensaje="Complete su perfil" accionTexto="Ir al perfil" accionHref="/perfil" />'
        );

        $this->assertStringContainsString('Sin centro', $html);
        $this->assertStringContainsString('href="/perfil"', $html);
        // El icono acompaña siempre al color: el tono no puede ser el único aviso.
        $this->assertStringContainsString('<svg', $html);
    }

    public function test_el_grafico_se_declara_por_atributos_sin_script(): void
    {
        $html = Blade::render(
            '<x-dashboard.grafico-apex id="panel-serie-prueba" :series="[[\'name\' => \'Proyectos\', \'data\' => [1,2]]]" :categorias="[\'2025\',\'2026\']" />'
        );

        $this->assertStringContainsString('data-nexo-chart', $html);
        $this->assertStringContainsString('wire:ignore', $html);
        $this->assertStringNotContainsString('<script', $html);
    }

    public function test_todos_los_componentes_traen_variantes_de_modo_oscuro(): void
    {
        $componentes = [
            '<x-dashboard.cabecera titulo="T" />',
            '<x-dashboard.kpi etiqueta="E" :valor="1" />',
            '<x-dashboard.panel titulo="T">contenido</x-dashboard.panel>',
            '<x-dashboard.estado-vacio titulo="T" />',
            '<x-dashboard.chip-estado nombre="En curso" />',
        ];

        foreach ($componentes as $componente) {
            $this->assertStringContainsString(
                'dark:',
                Blade::render($componente),
                "El componente {$componente} no declara variantes de modo oscuro."
            );
        }
    }
}
