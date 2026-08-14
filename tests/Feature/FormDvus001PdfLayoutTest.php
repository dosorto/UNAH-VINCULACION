<?php

namespace Tests\Feature;

use Tests\TestCase;

class FormDvus001PdfLayoutTest extends TestCase
{
    public function test_usa_exclusivamente_los_tres_recursos_institucionales_configurados(): void
    {
        $header = $this->viewSource('components/fichas/partials/institutional-pdf-header');
        $watermark = $this->viewSource('components/fichas/partials/institutional-pdf-watermark');
        $combined = $header . "\n" . $watermark;

        $this->assertStringContainsString("assets/pdf/common/vra.png", $header);
        $this->assertStringContainsString("assets/pdf/common/rectangulo_amarillo.png", $header);
        $this->assertStringContainsString("assets/pdf/common/sol_gris.png", $watermark);
        $this->assertStringNotContainsString('base64', $combined);
        $this->assertSame(3, substr_count($combined, 'assets/pdf/common/'));
    }

    public function test_encabezado_y_marca_de_agua_se_configuran_como_elementos_repetibles(): void
    {
        $styles = $this->viewSource('components/fichas/partials/institutional-pdf-chrome-styles');
        $wrapper = $this->viewSource('components/fichas/partials/form-dvus-001-header');
        $institutionalHeader = $this->viewSource('components/fichas/partials/institutional-pdf-header');

        $this->assertMatchesRegularExpression('/\.institutional-pdf-header\s*\{[^}]*position:\s*fixed/s', $styles);
        $this->assertMatchesRegularExpression('/\.institutional-pdf-watermark\s*\{[^}]*position:\s*fixed/s', $styles);
        $this->assertMatchesRegularExpression('/\.institutional-pdf-accent\s*\{[^}]*position:\s*fixed/s', $styles);
        $this->assertStringContainsString('FORM-DVUS-001', $wrapper);
        $this->assertStringContainsString('vinculacion.sociedad@unah.edu.hn', $institutionalHeader);
        $this->assertStringContainsString('2216-6100 Ext. 110576', $institutionalHeader);
    }

    public function test_secciones_conservan_el_orden_institucional_requerido(): void
    {
        $view = $this->viewSource('components/fichas/ficha-proyecto-vinculacion');

        $positions = array_map(
            fn (string $heading): int|false => strpos($view, $heading),
            [
                'I. INFORMACIÓN GENERAL DEL PROYECTO',
                'II. EQUIPO EJECUTOR DEL PROYECTO',
                'III. PARTICIPACIÓN DE ESTUDIANTES Y VOLUNTARIOS',
                'IV. INFORMACIÓN DE LA ENTIDAD CONTRAPARTE DEL PROYECTO',
                'V. FORMULACIÓN DEL PROYECTO',
                'VI. CRONOGRAMA DE LAS ACTIVIDADES DEL PROYECTO',
                'VII. DETALLE DEL PRESUPUESTO',
                "components.fichas.firmas-fijas-proyecto",
                'DOCUMENTOS ADJUNTOS A LA FICHA',
                'XI. ANEXOS',
            ]
        );

        $this->assertNotContains(false, $positions);
        $this->assertSame($positions, collect($positions)->sort()->values()->all());
        $this->assertSame(1, substr_count($view, 'VII. DETALLE DEL PRESUPUESTO'));
    }

    public function test_documentos_adjuntos_incluyen_los_cuatro_requisitos_y_la_nota(): void
    {
        $view = $this->viewSource('components/fichas/ficha-proyecto-vinculacion');

        $this->assertStringContainsString('Carta de solicitud del proyecto', $view);
        $this->assertStringContainsString('Convenio/ carta de intenciones', $view);
        $this->assertStringContainsString('Oficio de remisión del Decano/Director Centro Regional', $view);
        $this->assertStringContainsString('Otros (detallar)', $view);
        $this->assertStringContainsString(
            'El documento 1 o el documento 2 (cualquiera de los dos) es obligatorio. El documento 3 es obligatorio.',
            $view
        );
        $this->assertStringContainsString("'adjunto' => \$anexosPorCodigo->has(\$documento['codigo'])", $view);
        $this->assertStringContainsString("\$pdfCheck(\$documento['adjunto'])", $view);
        $this->assertStringContainsString("\$pdfCheck(! \$documento['adjunto'])", $view);
        $this->assertStringContainsString("->pluck('detalle')", $view);
        $this->assertStringContainsString("'Otros (detallar)'.(\$detallesOtros !== '' ? ': '.\$detallesOtros : '')", $view);
        $this->assertLessThan(strpos($view, 'XI. ANEXOS'), strpos($view, 'DOCUMENTOS ADJUNTOS A LA FICHA'));
    }

    public function test_estilos_pdf_definen_carta_vertical_y_protegen_bloques_criticos(): void
    {
        $styles = $this->viewSource('components/fichas/partials/form-dvus-001-pdf-styles');

        $this->assertStringContainsString('size: letter portrait', $styles);
        $this->assertStringContainsString('table-layout: fixed', $styles);
        $this->assertStringContainsString('page-break-inside: avoid', $styles);
        $this->assertStringContainsString('.section-signatures', $styles);
        $this->assertStringContainsString('.section-documents', $styles);
        $this->assertStringContainsString('display: table-header-group', $styles);
    }

    public function test_pdf_usa_casillas_html_compatibles_y_beneficiarios_compactos(): void
    {
        $view = $this->viewSource('components/fichas/ficha-proyecto-vinculacion');
        $styles = $this->viewSource('components/fichas/partials/form-dvus-001-pdf-styles');

        $this->assertStringContainsString('$pdfCheck', $view);
        $this->assertStringContainsString("'X' : '&nbsp;'", $view);
        $this->assertStringContainsString('beneficiary-summary', $view);
        $this->assertStringContainsString('beneficiary-ethnicity', $view);
        $this->assertStringContainsString('.pdf-check', $styles);
        $this->assertStringNotContainsString('☐', $view);
        $this->assertStringNotContainsString('☑', $view);
        $this->assertStringNotContainsString('✓', $view);
    }

    private function viewSource(string $view): string
    {
        return file_get_contents(resource_path('views/' . str_replace('.', '/', $view) . '.blade.php'));
    }
}
