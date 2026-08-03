<?php

namespace Tests\Feature;

use Tests\TestCase;

class Form018WordParityTest extends TestCase
{
    public function test_formulario_de_captura_conserva_solo_los_campos_del_form_018(): void
    {
        $vista = file_get_contents(resource_path('views/enf/acciones/create.blade.php'));

        $this->assertStringNotContainsString('name="departamento_id"', $vista);
        $this->assertStringNotContainsString('name="municipio_id"', $vista);
        $this->assertStringNotContainsString('name="beneficiarios[hombres]"', $vista);
        $this->assertStringNotContainsString('name="beneficiarios[mujeres]"', $vista);
        $this->assertStringNotContainsString('name="descripcion_participantes"', $vista);
        $this->assertStringNotContainsString('name="metodologia"', $vista);
        $this->assertStringNotContainsString('name="bibliografia"', $vista);
        $this->assertStringNotContainsString('name="eje_unah_ids[]"', $vista);

        $this->assertStringContainsString('name="beneficiarios[total]"', $vista);
        $this->assertStringContainsString("'Profesores x hora'", $vista);
        $this->assertStringContainsString("'Administrativo'", $vista);
        $this->assertStringContainsString("'Servicios'", $vista);
        $this->assertStringContainsString("'aporte_unah'", $vista);
        $this->assertStringContainsString("'cantidad' => 6", $vista);
        $this->assertSame(2, substr_count($vista, "'cantidad' => 5"));
    }

    public function test_form_018_solo_ofrece_los_cuatro_tipos_del_documento_oficial(): void
    {
        $controlador = file_get_contents(app_path('Http/Controllers/ENF/EnfAccionController.php'));

        preg_match('/private const TIPOS_ACCION_FORM_018 = \[(.*?)\];/s', $controlador, $coincidencia);

        $this->assertNotEmpty($coincidencia);
        $this->assertStringContainsString("'Proyecto de educacion continua'", $coincidencia[1]);
        $this->assertStringContainsString("'Diplomado'", $coincidencia[1]);
        $this->assertStringContainsString("'Congreso'", $coincidencia[1]);
        $this->assertStringContainsString("'Seminario'", $coincidencia[1]);
        $this->assertStringNotContainsString("'Certificado universitario'", $coincidencia[1]);
        $this->assertStringNotContainsString("'Programa de educacion continua'", $coincidencia[1]);
        $this->assertStringNotContainsString("'Curso'", $coincidencia[1]);
        $this->assertStringNotContainsString("'Taller'", $coincidencia[1]);
    }

    public function test_documento_form_018_mantiene_los_desgloses_del_word(): void
    {
        $documento = file_get_contents(resource_path('views/enf/acciones/partials/form-018-document.blade.php'));

        $this->assertStringContainsString("\$participacion('Profesores x hora', 'hombres')", $documento);
        $this->assertStringContainsString("\$participacion('Administrativo', 'hombres')", $documento);
        $this->assertStringContainsString("\$participacion('Servicios', 'hombres')", $documento);
        $this->assertStringContainsString('Profesionales universitarios otros CES', $documento);
        $this->assertStringContainsString('Personas con discapacidades', $documento);
        $this->assertStringContainsString('Nota: El documento 1 obligatorio.', $documento);
    }
}
