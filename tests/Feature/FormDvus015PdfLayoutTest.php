<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * La ficha `ficha-proyecto-vinculacion` es compartida por FORM-DVUS-001 y FORM-DVUS-015.
 * En "modo voluntariado" (`$esVoluntariado`) debe alternar encabezado, numeración de
 * secciones/ítems y mostrar los campos exclusivos del formato 015, sin alterar el 001.
 */
class FormDvus015PdfLayoutTest extends TestCase
{
    private function fichaSource(): string
    {
        return file_get_contents(resource_path('views/components/fichas/ficha-proyecto-vinculacion.blade.php'));
    }

    public function test_encabezado_y_titulo_se_resuelven_por_codigo_de_formulario(): void
    {
        $view = $this->fichaSource();

        $this->assertStringContainsString('$esVoluntariado = $proyecto->esVoluntariado();', $view);
        $this->assertStringContainsString("\$codigoFormulario = \$esVoluntariado ? 'FORM-DVUS-015' : 'FORM-DVUS-001';", $view);
        $this->assertStringContainsString('<title>{{ $codigoFormulario }} - {{ $tituloFormulario }}</title>', $view);
        $this->assertStringContainsString("'institutionalCode' => 'FORM-DVUS-015'", $view);
        $this->assertStringContainsString('VOLUNTARIADO ACADÉMICO', $view);
    }

    public function test_helpers_de_numeracion_mantienen_el_001_intacto(): void
    {
        $view = $this->fichaSource();

        // El 001 no numera "Temática", "Metodología de seguimiento" ni "Experiencia".
        $this->assertStringContainsString("'tematica' => null, 'fecha_ejecucion' => '6'", $view);
        $this->assertStringContainsString("'experiencia' => null, 'metodologia' => '30', 'bibliografia' => '31'", $view);
        // El 015 corre la numeración y reinicia el cronograma/presupuesto.
        $this->assertStringContainsString("'tematica' => '6', 'fecha_ejecucion' => '7'", $view);
        $this->assertStringContainsString("'experiencia' => '32', 'metodologia' => '33', 'bibliografia' => '34'", $view);
        $this->assertStringContainsString("'actividades' => '1', 'aporte_institucional' => '2', 'otras_aportaciones' => '3'", $view);
        $this->assertStringContainsString("'cronograma'  => \$esVoluntariado ? 'VII. ' : 'VI. '", $view);
        $this->assertStringContainsString("'presupuesto' => \$esVoluntariado ? 'VIII. ' : 'VII. '", $view);
    }

    public function test_campos_exclusivos_del_015_estan_presentes_y_condicionados(): void
    {
        $view = $this->fichaSource();

        $this->assertStringContainsString('Temática principal del proyecto', $view);
        $this->assertStringContainsString('. Metodología de seguimiento', $view);
        $this->assertStringContainsString('DESCRIPCIÓN DE LA EXPERIENCIA ACADÉMICA QUE SE DESARROLLARÁ', $view);
        $this->assertStringContainsString('INFORMACIÓN SOBRE EL USO DE ESPACIOS, SERVICIOS Y MEDIOS INSTITUCIONALES', $view);
        $this->assertStringContainsString('$proyecto->espaciosInstitucionales', $view);
        $this->assertStringContainsString('$proyecto->experiencia_conocimientos_teoricos', $view);
        $this->assertStringContainsString('$proyecto->tematica_principal', $view);

        // Cada bloque nuevo va detrás de una guarda de voluntariado.
        foreach ([
            'Temática principal del proyecto',
            'INFORMACIÓN SOBRE EL USO DE ESPACIOS, SERVICIOS Y MEDIOS INSTITUCIONALES',
            'DESCRIPCIÓN DE LA EXPERIENCIA ACADÉMICA QUE SE DESARROLLARÁ',
        ] as $marcador) {
            $pos = strpos($view, $marcador);
            $this->assertNotFalse($pos);
            $this->assertStringContainsString('@if ($esVoluntariado)', substr($view, 0, $pos));
        }
    }

    public function test_secciones_propias_del_001_se_ocultan_para_voluntariado(): void
    {
        $view = $this->fichaSource();

        $pos = strpos($view, 'DOCUMENTOS ADJUNTOS A LA FICHA');
        $this->assertNotFalse($pos);
        $this->assertStringContainsString('@if (!$esVoluntariado)', substr($view, 0, $pos));
        // El cierre @endif envuelve también el bloque de anexos del sistema.
        $this->assertLessThan(
            strpos($view, '</body>'),
            strpos($view, 'XI. ANEXOS')
        );
    }

    public function test_nombre_de_archivo_del_pdf_usa_el_codigo_de_formulario(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/PDFController.php'));

        $this->assertStringContainsString("\$proyecto->codigoFormularioFlujo() ?: 'FORM-DVUS-001'", $controller);
        $this->assertStringContainsString("return \$codigoFormulario . '-' . \$identificador . '.pdf';", $controller);
        $this->assertStringContainsString("'espaciosInstitucionales',", $controller);
    }
}
