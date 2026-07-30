<?php

namespace Tests\Feature;

use App\Models\Constancias\ConstanciaFinalizacionProyecto;
use App\Services\Constancias\ConstanciaFinalizacionPdfGenerator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class ConstanciaFinalizacionArchitectureTest extends TestCase
{
    public function test_las_rutas_de_constancia_finalizacion_estan_registradas(): void
    {
        $this->assertNotNull(app('router')->getRoutes()->getByName('constancias.finalizacion.descargar'));
        $this->assertNotNull(app('router')->getRoutes()->getByName('constancias.finalizacion.verificar'));
    }

    public function test_la_plantilla_pdf_usa_componentes_locales_y_fecha_en_espanol(): void
    {
        $vista = file_get_contents(resource_path('views/pdf/constancias/constancia-finalizacion-proyecto.blade.php'));
        $generador = file_get_contents(app_path('Services/Constancias/ConstanciaFinalizacionPdfGenerator.php'));

        $this->assertStringContainsString("@include('pdf.constancias.partials.header')", $vista);
        $this->assertStringContainsString("@include('pdf.constancias.partials.footer')", $vista);
        $this->assertStringContainsString("@include('pdf.constancias.partials.watermark')", $vista);
        $this->assertStringContainsString('page-break-before: always', $vista);
        $this->assertStringContainsString("->locale('es')", $vista);
        $this->assertStringNotContainsString('Storage::url', $vista);
        $this->assertStringNotContainsString('http://', $vista);
        $this->assertStringNotContainsString('data:image', $generador);
        $this->assertStringContainsString('temporaryQr', $generador);
        $this->assertStringContainsString("'qr' => 'file://'.\$qrPath", $generador);
        $this->assertStringContainsString("'file://'", $generador);

        $this->assertFileExists(resource_path('views/pdf/constancias/partials/header.blade.php'));
        $this->assertFileExists(resource_path('views/pdf/constancias/partials/footer.blade.php'));
        $this->assertFileExists(resource_path('views/pdf/constancias/partials/watermark.blade.php'));
        $this->assertFileExists(public_path('images/enf/form-018-header.png'));
        $this->assertFileExists(public_path('images/enf/form-018-footer.png'));
        $this->assertFileExists(public_path('images/enf/form-018-watermark.png'));
        $this->assertSame('julio', Carbon::parse('2026-07-29')->locale('es')->translatedFormat('F'));
    }

    public function test_el_pdf_renderiza_el_qr_sin_uri_data_ni_advertencias_de_protocolo(): void
    {
        $constancia = new ConstanciaFinalizacionProyecto;
        $constancia->forceFill([
            'token_cifrado' => Crypt::encryptString('token-de-prueba'),
            'snapshot' => [
                'constancia' => [
                    'numero' => 'N.º 0001-VRA/DVUS-2026',
                    'codigo_validacion' => 'ABC123',
                    'fecha_emision' => '2026-07-29 10:00:00',
                    'ciudad_emision' => 'Tegucigalpa',
                ],
                'proyecto' => ['nombre' => 'Proyecto de prueba', 'codigo' => 'PV-001'],
                'coordinador' => ['rol' => 'Coordinación', 'nombre' => 'Persona de prueba', 'numero_empleado' => '1', 'categoria' => 'Docente', 'departamento' => 'DVUS', 'horas' => 8],
                'equipo' => [],
                'beneficiarios' => ['hombres' => 1, 'mujeres' => 1],
                'participacion' => ['estudiantes' => 1, 'voluntarios_docentes' => 0, 'voluntarios_estudiantes' => 0, 'personal_administrativo' => 0],
                'presupuesto' => ['moneda' => 'L', 'unah' => '0.00', 'contraparte' => '0.00', 'total' => '0.00'],
                'autoridad' => ['nombre' => 'Autoridad de prueba', 'cargo' => 'Director Vinculación'],
            ],
        ]);

        $GLOBALS['_dompdf_warnings'] = [];
        $temporalesAntes = glob(storage_path('app/constancias/tmp/constancia-qr-*')) ?: [];
        $contenido = app(ConstanciaFinalizacionPdfGenerator::class)->content($constancia);
        $advertencias = implode("\n", array_map('strval', $GLOBALS['_dompdf_warnings'] ?? []));

        $this->assertStringStartsWith('%PDF', $contenido);
        $this->assertSame(2, preg_match_all('/\/Type\s*\/Page\b/', $contenido));
        $this->assertStringNotContainsString('Permission denied', $advertencias);
        $this->assertStringNotContainsString('communication protocol is not supported', $advertencias);
        $this->assertSame($temporalesAntes, glob(storage_path('app/constancias/tmp/constancia-qr-*')) ?: []);
    }
}
