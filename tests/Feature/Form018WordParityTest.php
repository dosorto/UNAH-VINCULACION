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

    public function test_form_018_mantiene_el_selector_de_programas_aprobados_daft(): void
    {
        $vista = file_get_contents(resource_path('views/enf/acciones/create.blade.php'));
        $controlador = file_get_contents(app_path('Http/Controllers/ENF/EnfAccionController.php'));

        $this->assertStringContainsString('data-approved-program-select', $vista);
        $this->assertStringContainsString('@js($programasAprobadosData)', $vista);
        $this->assertStringContainsString("applyApprovedProgram(event.target.value)", $vista);
        $this->assertStringNotContainsString('const approvedPrograms = [];', $vista);
        $this->assertStringContainsString("->where('estado_flujo', 'APROBADO')", $controlador);
        $this->assertStringContainsString("'source' => 'Programa DAFT'", $controlador);
    }

    public function test_participacion_universitaria_se_resume_fuera_del_modal(): void
    {
        $vista = file_get_contents(resource_path('views/enf/acciones/create.blade.php'));

        $this->assertStringContainsString('data-participacion-summary', $vista);
        $this->assertStringContainsString('data-participacion-summary-totals', $vista);
        $this->assertStringContainsString('Sin participación registrada.', $vista);
        $this->assertStringContainsString('registeredRows.reduce', $vista);
    }

    public function test_resultados_se_gestionan_en_modal_y_se_muestran_en_tabla(): void
    {
        $vista = file_get_contents(resource_path('views/enf/acciones/create.blade.php'));

        $this->assertStringContainsString('data-resultados-list', $vista);
        $this->assertStringContainsString('data-resultados-fields class="hidden"', $vista);
        $this->assertStringContainsString('data-open-resultado-modal=', $vista);
        $this->assertSame(3, substr_count($vista, "'clave' =>"));
        $this->assertStringContainsString('Resultados de corto plazo', $vista);
        $this->assertStringContainsString('Resultados de mediano plazo', $vista);
        $this->assertStringContainsString('Resultados de largo plazo / impacto', $vista);
        $this->assertStringContainsString('data-grupo="{{ $grupoResultado[\'clave\'] }}"', $vista);
        $this->assertStringContainsString('table-fixed', $vista);
        $this->assertStringContainsString('[overflow-wrap:anywhere]', $vista);
        $this->assertStringContainsString('data-resultado-modal', $vista);
        $this->assertStringContainsString('data-save-resultado', $vista);
        $this->assertStringContainsString('data-edit-resultado=', $vista);
        $this->assertStringContainsString('data-remove-resultado=', $vista);
        $this->assertStringContainsString('const renderResultados = () =>', $vista);
        $this->assertStringContainsString('const openResultadoModal = (tipo, index = null) =>', $vista);
        $this->assertStringContainsString('name="resultados[{{ $resultadoIndex }}][descripcion]"', $vista);
        $this->assertStringContainsString('name="resultados[{{ $resultadoIndex }}][indicador]"', $vista);
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

    public function test_documento_form_018_reserva_el_pie_en_pdf_y_compacta_la_vista(): void
    {
        $documento = file_get_contents(resource_path('views/enf/acciones/partials/form-018-document.blade.php'));

        $this->assertStringContainsString('min-height: 10.98in;', $documento);
        $this->assertStringContainsString('.form018-shell.screen-document .form018-page', $documento);
        $this->assertStringContainsString('min-height: 0;', $documento);
        $this->assertStringContainsString('.form018-shell.screen-document .form018-footer', $documento);
        $this->assertStringContainsString('position: static;', $documento);
        $this->assertStringContainsString('.form018-shell.is-pdf .form018-auto-row', $documento);
        $this->assertStringContainsString('.form018-shell.is-pdf .form018-footer', $documento);
        $this->assertStringNotContainsString('display: none !important;', $documento);
        $this->assertSame(11, substr_count($documento, '<section class="form018-page">'));
    }
}
