<?php

namespace Tests\Feature\Dashboard;

use Tests\TestCase;

/**
 * Regresiones de los arreglos de base del panel. Ninguna necesita base de datos.
 */
class PanelFundacionesTest extends TestCase
{
    private const PANELES = [
        'views/livewire/inicio/dashboards/dashboard.blade.php',
        'views/livewire/inicio/dashboards/dasboard-docente.blade.php',
        'views/livewire/inicio/dashboards/dashboard-director.blade.php',
    ];

    /**
     * Los gráficos se inicializaban en un <script> inline con DOMContentLoaded,
     * que no se vuelve a disparar con wire:navigate: al volver al inicio desde
     * el sidebar el contenedor quedaba vacío hasta recargar con F5.
     */
    public function test_los_paneles_no_llevan_scripts_inline(): void
    {
        foreach (self::PANELES as $panel) {
            $vista = file_get_contents(resource_path($panel));

            $this->assertStringNotContainsString(
                '<script',
                $vista,
                "{$panel} volvió a incluir un <script> inline; los gráficos deben montarse desde resources/js/panel-charts.js."
            );
        }
    }

    public function test_los_paneles_montan_el_grafico_por_atributo(): void
    {
        foreach (self::PANELES as $panel) {
            $this->assertStringContainsString(
                '<x-dashboard.grafico-apex',
                file_get_contents(resource_path($panel)),
                "{$panel} debe declarar su gráfico con el componente compartido."
            );
        }
    }

    public function test_el_helper_de_graficos_reacciona_a_la_navegacion_de_livewire(): void
    {
        $js = file_get_contents(resource_path('js/app.js'));

        $this->assertStringContainsString('livewire:navigated', $js);
        $this->assertStringContainsString('livewire:navigating', $js);
        $this->assertStringContainsString('destruirGraficosHuerfanos', $js);
        $this->assertStringContainsString('montarGraficos', $js);
        $this->assertStringContainsString('panel-grafico-actualizado', $js);
    }

    public function test_cada_panel_usa_un_contenedor_de_grafico_distinto(): void
    {
        // dashboard.blade y dasboard-docente compartían window.projectsChart,
        // así que el segundo pisaba la instancia del primero.
        $ids = [];

        foreach (self::PANELES as $panel) {
            preg_match_all('/panel-serie-[a-z]+/', file_get_contents(resource_path($panel)), $coincidencias);
            $ids = array_merge($ids, $coincidencias[0]);
        }

        // Los ids viven en los componentes PHP; aquí basta con que las vistas
        // no repitan uno literal entre sí.
        $this->assertSame(array_unique($ids), $ids, 'Dos paneles declaran el mismo id de gráfico.');
    }

    /**
     * tailwind.config.js definía `primary` como cadena, así que
     * text-primary-600 / bg-primary-50 y otras 20 utilidades usadas por el
     * sidebar no llegaban a generarse.
     */
    public function test_la_paleta_primary_es_una_escala_con_default(): void
    {
        $config = file_get_contents(base_path('tailwind.config.js'));

        $this->assertMatchesRegularExpression('/primary:\s*\{/', $config, '`primary` debe ser un objeto de escala.');

        foreach (['DEFAULT', '50', '400', '600', '900'] as $tono) {
            $this->assertMatchesRegularExpression(
                '/\b'.$tono.':\s*[\'"]#[0-9a-fA-F]{6}[\'"]/',
                $config,
                "Falta el tono primary-{$tono}."
            );
        }
    }

    /**
     * La ruta era la del contenedor Docker, así que en local nunca hacía match
     * y las clases escritas en componentes Livewire no se compilaban.
     */
    public function test_tailwind_analiza_las_clases_escritas_en_php(): void
    {
        $config = file_get_contents(base_path('tailwind.config.js'));

        $this->assertStringNotContainsString('"/app/Livewire/**/*.php"', $config);
        $this->assertStringContainsString('./app/Livewire/**/*.php', $config);
    }

    public function test_las_tarjetas_de_estado_enlazan_al_historial(): void
    {
        // La columna "En curso" del panel admin tenía hhref= y no navegaba.
        $vista = file_get_contents(resource_path('views/livewire/inicio/dashboards/dashboard.blade.php'));

        $this->assertStringNotContainsString('hhref=', $vista);
    }

    public function test_no_quedan_clases_de_tailwind_mal_escritas(): void
    {
        $vista = file_get_contents(resource_path('views/livewire/inicio/dashboards/dasboard-docente.blade.php'));

        $this->assertStringNotContainsString('ywllow', $vista);
    }
}
