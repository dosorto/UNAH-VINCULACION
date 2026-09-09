<?php

namespace Tests\Unit;

use App\Models\Pasantia;
use App\Support\Pasantias\PasantiaDocumentoRequirements;
use PHPUnit\Framework\TestCase;

class PasantiaDocumentoRequirementsTest extends TestCase
{
    public function test_solicitud_usa_requisitos_de_pasantia_y_no_fechas(): void
    {
        $registro = new Pasantia([
            'nombre_estudiante' => 'Ana Pérez', 'numero_cuenta' => '20200001', 'carrera' => 'Derecho',
            'facultad_centro' => 'CU', 'nombre_institucion' => 'Institución', 'modalidad_ejecucion' => 'Presencial',
            'total_horas' => 400, 'nombre_contacto_directo' => 'Laura López', 'cargo_contacto_directo' => 'Directora',
        ]);

        $this->assertSame([], PasantiaDocumentoRequirements::missing($registro, PasantiaDocumentoRequirements::SOLICITUD));
    }

    public function test_autorizacion_exige_fechas_y_firma_disponible(): void
    {
        $registro = new Pasantia([
            'nombre_estudiante' => 'Ana Pérez', 'numero_cuenta' => '20200001', 'carrera' => 'Derecho',
            'facultad_centro' => 'CU', 'nombre_institucion' => 'Institución', 'modalidad_ejecucion' => 'Presencial',
            'nombre_firma_coordinador' => 'Coordinador', 'firma_coordinador' => 'data:image/png;base64,AA==',
        ]);

        $missing = PasantiaDocumentoRequirements::missing($registro, PasantiaDocumentoRequirements::AUTORIZACION);
        $this->assertArrayHasKey('fecha_inicio', $missing);
        $this->assertArrayHasKey('fecha_finalizacion', $missing);
        $this->assertArrayNotHasKey('firma_coordinador', $missing);
    }

    public function test_borrador_incompleto_se_bloquea_solo_para_el_documento_correspondiente(): void
    {
        $registro = new Pasantia(['nombre_estudiante' => 'Ana Pérez']);

        $solicitud = PasantiaDocumentoRequirements::missing($registro, PasantiaDocumentoRequirements::SOLICITUD);
        $autorizacion = PasantiaDocumentoRequirements::missing($registro, PasantiaDocumentoRequirements::AUTORIZACION);

        $this->assertArrayHasKey('nombre_contacto_directo', $solicitud);
        $this->assertArrayHasKey('fecha_inicio', $autorizacion);
        $this->assertArrayNotHasKey('firma_coordinador', $solicitud);
    }

    public function test_la_vista_compartida_conserva_los_textos_de_pasantia(): void
    {
        $template = file_get_contents(__DIR__.'/../../resources/views/pdf/pps-servicio-social/generado.blade.php');
        $this->assertStringContainsString("\$esPasantia ? 'PASANTÍA' : 'PRÁCTICA'", $template);
        $this->assertStringContainsString("\$esPasantia ? 'pasantía' : 'práctica profesional supervisada'", $template);
        $this->assertStringContainsString("\$esPasantia ? 'la pasantía' : 'la Práctica Profesional Supervisada'", $template);
    }
}
