<?php

namespace Tests\Feature;

use App\Models\PpsServicioSocial;
use App\Models\User;
use App\Support\PpsServicioSocial\FormDvus014Data;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PDF;
use Tests\TestCase;

class FormDvus014PdfRenderTest extends TestCase
{
    use DatabaseTransactions;

    public function test_conserva_fechas_de_inicio_y_finalizacion_distintas(): void
    {
        $registro = PpsServicioSocial::create([
            'codigo_registro' => 'PPS-DATES-001',
            'facultad_centro' => 'Facultad de Ciencias',
            'carrera' => 'Ingeniería en Sistemas',
            'numero_cuenta' => '20241000001',
            'nombre_estudiante' => 'Estudiante de prueba',
            'celular_estudiante' => '9999-0000',
            'correo_institucional' => 'estudiante@unah.edu.hn',
            'tipo_pps_ss' => 'Practica Profesional Supervisada',
            'fecha_inicio' => '2026-09-01',
            'fecha_finalizacion' => '2027-01-01',
            'tipo_instrumento' => 'Carta de intenciones con la UNAH',
            'territorio_ejecucion' => 'Nacional',
            'modalidad_ejecucion' => '100% presencial',
            'total_horas' => 1,
            'nombre_institucion' => 'Institución de prueba',
            'nombre_jefe_directo' => 'Jefe de prueba',
            'nombre_docente_supervisor' => 'Supervisor de prueba',
        ]);

        $data = FormDvus014Data::from($registro);
        $this->assertSame('2026-09-01', $data['fields']['fecha_inicio']->format('Y-m-d'));
        $this->assertSame('2027-01-01', $data['fields']['fecha_finalizacion']->format('Y-m-d'));

        $html = view('components.pps-servicio-social.form-014', [
            'registro' => $registro,
            'formData' => $data,
            'isPdf' => true,
        ])->render();

        $this->assertMatchesRegularExpression(
            '~<td class="data center">01</td>\s*<td class="data center">09</td>\s*<td class="data center">2026</td>~',
            $html
        );
        $this->assertMatchesRegularExpression(
            '~<td class="data center">01</td>\s*<td class="data center">01</td>\s*<td class="data center">2027</td>~',
            $html
        );
        $this->assertStringNotContainsString('2026-09-01', $html);
        $this->assertStringNotContainsString('2027-01-01', $html);
    }


    public function test_genera_pdf_con_datos_completos_y_lo_guarda_para_inspeccion(): void
    {
        $usuario = User::factory()->create();

        $registro = PpsServicioSocial::create([
            'codigo_registro' => 'PPS-2025-001',
            'facultad_centro' => 'Facultad de Ciencias',
            'carrera' => 'Ingeniería en Sistemas',
            'numero_cuenta' => '20241000001',
            'nombre_estudiante' => 'María José Pérez López',
            'celular_estudiante' => '9999-0001',
            'correo_institucional' => 'maria.perez@unah.edu.hn',
            'correo_personal' => 'maria.perez@gmail.com',
            'tipo_pps_ss' => 'Practica Profesional Supervisada',
            'fecha_inicio' => '2025-02-01',
            'fecha_finalizacion' => '2025-08-01',
            'tipo_instrumento' => 'Carta de intenciones con la UNAH',
            'territorio_ejecucion' => 'Nacional',
            'region' => 'Nacional',
            'pais' => 'Honduras',
            'departamento' => 'Francisco Morazán',
            'municipio' => 'Distrito Central',
            'aldea_ciudad' => 'Tegucigalpa',
            'descripcion_tipo_pps' => 'Desarrollo de software para gestión académica',
            'descripcion_horas_tipo_pps_ss' => 'La práctica se realizará en horario diurno de lunes a viernes, cumpliendo 120 horas totales distribuidas en 8 semanas.',
            'total_horas' => 120,
            'horas_presenciales' => 80,
            'horas_teletrabajo' => 40,
            'area_realizacion' => 'Departamento de Tecnología Educativa',
            'resumen_responsabilidades' => 'El estudiante apoyará en el desarrollo, pruebas e implementación de módulos del sistema de gestión académica, incluyendo la documentación técnica correspondiente y la capacitación a usuarios finales.',
            'modalidad_ejecucion' => 'Híbrida',
            'nombre_institucion' => 'Universidad Nacional Autónoma de Honduras',
            'institucion_nacionalidad' => 'Nacional',
            'institucion_pais' => 'Honduras',
            'compromisos_institucion' => 'Proporcionar espacio físico, equipo de cómputo, acceso a sistemas internos y asignar un tutor técnico durante todo el período de la práctica.',
            'direccion_institucion' => 'Boulevard Suyapa, Tegucigalpa, Honduras',
            'representante_legal' => 'Dr. Juan Carlos Pérez',
            'telefono_representante' => '2216-7000',
            'correo_rrhh' => 'rrhh@unah.edu.hn',
            'tipo_institucion' => 'Gobierno Nacional',
            'sector_institucion' => 'Educacion e investigacion',
            'nombre_jefe_directo' => 'Lic. Ana Torres Rivera',
            'celular_jefe_directo' => '9999-0002',
            'correo_jefe_directo' => 'ana.torres@unah.edu.hn',
            'cargo_jefe_directo' => 'Jefa del Departamento de Tecnología Educativa',
            'grado_academico_jefe_directo' => 'Maestría en Tecnología Educativa',
            'nombre_docente_supervisor' => 'Dr. Carlos Mendoza Paz',
            'numero_empleado_docente' => 'DOC-2025-001',
            'celular_docente' => '9999-0003',
            'correo_docente' => 'carlos.mendoza@unah.edu.hn',
            'categoria_docente' => 'Titular',
            'departamento_docente' => 'Departamento de Ciencias de la Computación',
            'jornada_laboral_docente' => 'Tiempo Completo',
            'ubicacion_cubiculo_docente' => 'Edificio A1, Cubículo 305',
            'adjunta_carta_formalizacion' => true,
            'archivo_carta_formalizacion' => 'carta_formalizacion_001.pdf',
            'adjunta_convenio_marco' => false,
            'archivo_convenio_marco' => null,
            'pais_sede_principal' => null,
            'departamento_provincia_sede_principal' => null,
            'municipio_sede_principal' => null,
            'aldea_ciudad_sede_principal' => null,
            'caserio' => null,
            'departamento_provincia' => null,
            'created_by' => $usuario->id,
        ]);

        $formData = FormDvus014Data::from($registro);

        $html = view('pdf.pps-servicio-social.form-014', [
            'registro' => $registro,
            'formData' => $formData,
        ])->render();

        $dompdf = PDF::loadView('pdf.pps-servicio-social.form-014', [
            'registro' => $registro,
            'formData' => $formData,
        ])->setPaper('letter', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans')
            ->setOption('dpi', 96);

        $output = $dompdf->output();

        $htmlPath = storage_path('app/pdfs-prueba/FORM-DVUS-014-test.html');
        $pdfPath = storage_path('app/pdfs-prueba/FORM-DVUS-014-test.pdf');

        file_put_contents($htmlPath, $html);
        file_put_contents($pdfPath, $output);

        $this->assertStringStartsWith('%PDF', $output);
        $this->assertNotEmpty($html);

        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        $cells = $dom->getElementsByTagName('td');
        $this->assertGreaterThan(50, $cells->length);

        unlink($htmlPath);
        unlink($pdfPath);
    }
}
