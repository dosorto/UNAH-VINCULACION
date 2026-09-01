<?php

namespace Tests\Feature;

use Tests\TestCase;

class FormDvus014PdfLayoutTest extends TestCase
{
    public function test_usa_los_tres_recursos_institucionales_configurados(): void
    {
        $header = $this->viewSource('components/fichas/partials/institutional-pdf-header');
        $watermark = $this->viewSource('components/fichas/partials/institutional-pdf-watermark');
        $combined = $header . "\n" . $watermark;

        $this->assertStringContainsString('assets/pdf/common/vra.png', $header);
        $this->assertStringContainsString('assets/pdf/common/rectangulo_amarillo.png', $header);
        $this->assertStringContainsString('assets/pdf/common/sol_gris.png', $watermark);
        $this->assertStringNotContainsString('base64', $combined);
        $this->assertSame(3, substr_count($combined, 'assets/pdf/common/'));
    }

    public function test_incluye_los_tres_componentes_institucionales(): void
    {
        $view = $this->viewSource('components.pps-servicio-social.form-014');

        $this->assertStringContainsString('institutional-pdf-chrome-styles', $view);
        $this->assertStringContainsString('institutional-pdf-watermark', $view);
        $this->assertStringContainsString('institutional-pdf-footer', $view);
        $this->assertStringContainsString('form-014-header', $view);
    }

    public function test_encabezado_usa_el_header_institucional_con_FORM_DVUS_014(): void
    {
        $headerPartial = $this->viewSource('components.pps-servicio-social.partials.form-014-header');

        $this->assertStringContainsString('institutional-pdf-header', $headerPartial);
        $this->assertStringContainsString('FORM-DVUS-014', $headerPartial);
        $this->assertStringContainsString('institutionalCode', $headerPartial);
        $this->assertStringContainsString('FORMULARIO DE REGISTRO DE PRÁCTICA PROFESIONAL', $headerPartial);
        $this->assertStringContainsString('SUPERVISADA O SERVICIO SOCIAL', $headerPartial);
    }

    public function test_estilos_pdf_definen_carta_vertical_y_margenes_correctos(): void
    {
        $view = $this->viewSource('components.pps-servicio-social.form-014');

        $this->assertStringContainsString('size: letter portrait', $view);
        $this->assertStringContainsString('margin: 35mm 9mm 13mm', $view);
        $this->assertStringContainsString('table-layout: fixed', $view);
        $this->assertStringContainsString('page-break-inside: avoid', $view);
    }

    public function test_usa_casillas_css_sin_glifos_unicode(): void
    {
        $view = $this->viewSource('components.pps-servicio-social.form-014');
        $content = $this->viewSource('components.pps-servicio-social.partials.form-014-content');

        $this->assertStringContainsString('.cb', $view);
        $this->assertStringContainsString("? 'X' : ''", $view);
        $this->assertStringNotContainsString('☐', $content);
        $this->assertStringNotContainsString('☑', $content);
        $this->assertStringNotContainsString('✓', $content);
    }

    public function test_secciones_conservan_el_orden_institucional_requerido(): void
    {
        $content = $this->viewSource('components.pps-servicio-social.partials.form-014-content');

        $positions = array_map(
            fn (string $heading): int|false => strpos($content, $heading),
            [
                'I. Información general',
                'II. Datos del estudiante',
                'III. Información de la práctica profesional / servicio social',
                'IV. Datos territoriales de la PPS / servicio social',
                'V. Alcances de la PPS / servicio social',
                'VI. Información de la institución / empresa',
                'VII. Información del(a) docente supervisor(a) de la PPS – SS',
                'VIII. Firmas',
                'IX. Documentos adjuntos a la ficha',
            ]
        );

        $this->assertNotContains(false, $positions);
        $this->assertSame($positions, collect($positions)->sort()->values()->all());
    }

    public function test_numeracion_de_items_es_continua_del_1_al_37(): void
    {
        $content = $this->viewSource('components.pps-servicio-social.partials.form-014-content');

        $subHeadings = [14, 15, 16, 29];

        for ($i = 1; $i <= 37; $i++) {
            if (in_array($i, $subHeadings)) {
                $this->assertStringContainsString(">{$i}.", $content, "Falta el elemento número {$i}");
            } else {
                $this->assertStringContainsString(">{$i}<", $content, "Falta el elemento número {$i}");
            }
        }
    }

    public function test_no_tiene_pagina_6_vacia_por_min_height_excesivo(): void
    {
        $view = $this->viewSource('components.pps-servicio-social.form-014');

        $this->assertStringContainsString('min-height: auto', $view);
        $this->assertStringNotContainsString('min-height: 1056px', $view);
    }

    public function test_telefono_institucional_es_2216_7070(): void
    {
        $headerPartial = $this->viewSource('components.pps-servicio-social.partials.form-014-header');

        $this->assertStringContainsString('2216-7070', $headerPartial);
        $this->assertStringContainsString('Ext. 110576', $headerPartial);
        $this->assertStringContainsString('institutionalPhone', $headerPartial);
        $this->assertStringNotContainsString('2216-6100', $headerPartial);
    }

    public function test_contiene_cuatro_saltos_de_pagina(): void
    {
        $content = $this->viewSource('components.pps-servicio-social.partials.form-014-content');

        $this->assertSame(4, substr_count($content, 'section--page-break'));
    }

    public function test_paleta_de_colores_alineada_con_dvus_001(): void
    {
        $view = $this->viewSource('components.pps-servicio-social.form-014');

        $this->assertStringContainsString('#001b44', $view);
        $this->assertStringContainsString('#edf0f4', $view);
        $this->assertStringNotContainsString('#002060', $view);
        $this->assertStringNotContainsString('#d9d9d9', $view);
    }

    public function test_bordes_y_tipografia_alineados_con_dvus_001(): void
    {
        $view = $this->viewSource('components.pps-servicio-social.form-014');

        $this->assertStringContainsString('.5pt solid #374151', $view);
        $this->assertStringContainsString('Arial, Helvetica, "DejaVu Sans", sans-serif', $view);
        $this->assertStringContainsString('section--page-break', $view);
        $this->assertStringContainsString('page-break-inside: avoid', $view);
    }

    public function test_formulario_resuelve_firmas_sin_romper_si_no_existe_archivo(): void
    {
        $data = $this->viewSource('components.pps-servicio-social.form-014');
        $content = $this->viewSource('components.pps-servicio-social.partials.form-014-content');
        $mapper = file_get_contents(app_path('Support/PpsServicioSocial/FormDvus014Data.php'));

        $this->assertStringContainsString('FirmaImagen::resolver', $mapper);
        $this->assertStringContainsString("\$firmas = \$formData['firmas'] ?? []", $data);
        $this->assertStringContainsString("\$firmas['coordinador']['src']", $content);
        $this->assertStringContainsString("\$firmas['supervisor']['src']", $content);
        $this->assertStringContainsString("\$firmas['estudiante']['src']", $content);
    }

    public function test_controlador_no_usa_isPhpEnabled(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Proyectos/Vinculacion/PpsServicioSocialPdfController.php'));

        $this->assertStringNotContainsString('isPhpEnabled', $source);
        $this->assertStringContainsString('isHtml5ParserEnabled', $source);
    }

    private function viewSource(string $view): string
    {
        return file_get_contents(resource_path('views/' . str_replace('.', '/', $view) . '.blade.php'));
    }
}
