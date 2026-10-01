<?php

namespace Tests\Feature;

use App\Models\Personal\Empleado;
use App\Models\Personal\FirmaSelloEmpleado;
use App\Models\PpsServicioSocial;
use App\Models\User;
use App\Services\Documents\DocxTemplateEditor;
use App\Services\Documents\FormDvus014DataMapper;
use App\Services\PpsServicioSocial\FormDvus014DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;
use Tests\Support\SimulaLibreOffice;
use Tests\TestCase;

class FormDvus014DocumentTest extends TestCase
{
    use DatabaseTransactions;
    use SimulaLibreOffice;

    public function test_llena_cada_seccion_de_la_plantilla_oficial(): void
    {
        [$registro, $empleado] = $this->registro([
            'modalidad_ejecucion' => 'Hibrida',
            'tipo_instrumento' => 'convenio_marco',
            'adjunta_carta_formalizacion' => true,
            'archivo_carta_formalizacion' => 'pps-servicio-social/anexos/carta.pdf',
        ]);

        $cells = app(FormDvus014DataMapper::class)->cells($registro);
        $celda = function (int $tabla, int $fila, int $columna) use ($cells): ?string {
            $encontrada = collect($cells)->first(fn (array $cell) => [$cell[0], $cell[1], $cell[2]] === [$tabla, $fila, $columna]);

            return $encontrada[3] ?? null;
        };

        // I y II: fecha de registro por partes, facultad, carrera y estudiante.
        $this->assertSame(['2026', '02', '20'], [$celda(1, 2, 2), $celda(1, 2, 3), $celda(1, 2, 4)]);
        $this->assertSame('Facultad de Test', $celda(1, 3, 2));
        $this->assertSame('20241234', $celda(2, 1, 2));
        $this->assertSame('Estudiante Test', $celda(2, 2, 2));
        // III: tipo, fechas por partes, instrumento y territorio.
        $this->assertSame(['X', ''], [$celda(3, 1, 3), $celda(3, 1, 5)]);
        $this->assertSame(['27', '02', '2026', '25', '07', '2026'], array_map(fn ($c) => $celda(3, 4, $c), range(2, 7)));
        $this->assertSame(['', '', 'X'], [$celda(3, 5, 3), $celda(3, 6, 3), $celda(3, 7, 3)]);
        $this->assertSame(['X', ''], [$celda(3, 8, 3), $celda(3, 9, 3)]);
        // IV: híbrida llena la práctica presencial y la de teletrabajo.
        $this->assertSame(['', 'X', ''], [$celda(4, 2, 2), $celda(4, 2, 3), $celda(4, 2, 4)]);
        $this->assertSame(['X', ''], [$celda(4, 4, 3), $celda(4, 4, 5)]);
        $this->assertSame('Choluteca', $celda(4, 6, 2));
        $this->assertSame('Honduras', $celda(4, 11, 2));
        $this->assertSame(['20', '20'], [$celda(4, 16, 2), $celda(4, 16, 4)]);
        // VI: nacionalidad, tipo de institución (ONG) y sector (educación, segunda columna).
        $this->assertSame(['X', ''], [$celda(6, 1, 3), $celda(6, 1, 5)]);
        $this->assertSame('Empresa Test S.A.', $celda(6, 2, 2));
        $this->assertSame('X', $celda(6, 9, 4));
        $this->assertSame('X', $celda(6, 12, 5));
        $this->assertSame('Jefe Test', $celda(6, 15, 2));
        // VII y VIII: supervisor; el coordinador(a) de la carrera es quien llena el formulario.
        $this->assertSame('Docente Test', $celda(7, 1, 2));
        $this->assertSame('Nombre: '.$empleado->nombre_completo, $celda(8, 2, 1));
        $this->assertSame('Nombre: Docente Test', $celda(8, 2, 2));
        $this->assertSame('Nombre: Estudiante Test', $celda(8, 2, 3));
        // IX: carta adjunta (Sí), convenio sin archivo (No).
        $this->assertSame(['X', ''], [$celda(9, 2, 3), $celda(9, 2, 4)]);
        $this->assertSame(['', 'X'], [$celda(9, 3, 3), $celda(9, 3, 4)]);

        // Cada celda existe en la plantilla oficial y la firma entra sin recuadro.
        $docx = sys_get_temp_dir().'/form-dvus-014-'.uniqid().'.docx';
        copy(config('documents.form_dvus_014_template'), $docx);
        $editor = new DocxTemplateEditor($docx);
        foreach ($cells as [$tabla, $fila, $columna, $valor, $sinAjuste]) {
            $editor->setCell($tabla, $fila, $columna, $valor, $sinAjuste);
        }
        $firmas = app(FormDvus014DataMapper::class)->signatures($registro);
        $this->assertSame([1], array_keys($firmas));
        foreach ($firmas as $columna => $ruta) {
            $editor->setCellImage(FormDvus014DataMapper::TABLA_FIRMAS, FormDvus014DataMapper::FILA_FIRMAS, $columna, $ruta);
        }
        $editor->save();
        $zip = new \ZipArchive;
        $zip->open($docx);
        $documento = (string) $zip->getFromName('word/document.xml');
        $encabezado = (string) $zip->getFromName('word/header2.xml');
        $zip->close();
        unlink($docx);

        $this->assertStringContainsString('Empresa Test S.A.', $documento);
        $this->assertStringContainsString('stroked="f"', $documento);
        // Sin el círculo con el número de página que LibreOffice dibuja vacío.
        $this->assertStringNotContainsString('Page Numbers', $encabezado);
    }

    public function test_la_modalidad_presencial_no_llena_la_seccion_de_teletrabajo(): void
    {
        [$registro] = $this->registro(['modalidad_ejecucion' => 'Presencial', 'pais_sede_principal' => 'Guatemala']);

        $cells = collect(app(FormDvus014DataMapper::class)->cells($registro));

        $this->assertNotNull($cells->first(fn (array $cell) => [$cell[0], $cell[1], $cell[2]] === [4, 5, 2]));
        $this->assertNull($cells->first(fn (array $cell) => [$cell[0], $cell[1], $cell[2]] === [4, 11, 2]));
    }

    public function test_genera_el_pdf_con_libreoffice_y_lo_reutiliza_mientras_no_cambien_los_datos(): void
    {
        $this->simularLibreOffice();
        [$registro] = $this->registro();
        $this->beforeApplicationDestroyed(fn () => File::deleteDirectory(storage_path('app/generated/form-dvus-014/'.$registro->id)));
        $service = app(FormDvus014DocumentService::class);

        $pdf = $service->generatePdf($registro);
        $this->assertStringStartsWith('%PDF-', (string) file_get_contents($pdf));
        $this->assertSame($pdf, $service->generatePdf($registro->fresh()));

        $registro->update(['nombre_institucion' => 'Otra Empresa S.A.']);
        $nuevo = $service->generatePdf($registro->fresh());
        $this->assertNotSame($pdf, $nuevo);
        // Solo se guarda el PDF vigente de cada registro.
        $this->assertFileDoesNotExist($pdf);
    }

    /** @return array{0: PpsServicioSocial, 1: Empleado} */
    private function registro(array $datos = []): array
    {
        $usuario = User::factory()->create();
        $empleado = Empleado::create([
            'nombre_completo' => 'Coordinadora '.uniqid(),
            'numero_empleado' => (string) random_int(100000, 999999),
            'celular' => '99999999',
            'sexo' => 'Femenino',
            'user_id' => $usuario->id,
            'tipo_empleado' => 'docente',
        ]);
        FirmaSelloEmpleado::create([
            'empleado_id' => $empleado->id,
            'tipo' => 'firma',
            'ruta_storage' => public_path('images/logo_nuevo.png'),
            'estado' => true,
        ]);

        $registro = PpsServicioSocial::create(array_merge([
            'codigo_registro' => 'PPS-TEST-'.uniqid(),
            'fecha_registro' => '2026-02-20 10:00:00',
            'facultad_centro' => 'Facultad de Test',
            'carrera' => 'Test Carrera',
            'numero_cuenta' => '20241234',
            'nombre_estudiante' => 'Estudiante Test',
            'celular_estudiante' => '99999999',
            'correo_institucional' => 'test@unah.edu.hn',
            'tipo_pps_ss' => 'Practica Profesional Supervisada',
            'fecha_inicio' => '2026-02-27',
            'fecha_finalizacion' => '2026-07-25',
            'tipo_instrumento' => 'carta_formal_solicitud',
            'territorio_ejecucion' => 'Nacional',
            'region' => 'Nacional',
            'modalidad_ejecucion' => 'Presencial',
            'pais' => 'Honduras',
            'departamento' => 'Choluteca',
            'municipio' => 'Choluteca',
            'aldea_ciudad' => 'Choluteca',
            'caserio' => 'Centro',
            'pais_sede_principal' => 'Honduras',
            'departamento_provincia_sede_principal' => 'Francisco Morazán',
            'municipio_sede_principal' => 'Distrito Central',
            'aldea_ciudad_sede_principal' => 'Tegucigalpa',
            'horas_presenciales' => 20,
            'horas_teletrabajo' => 20,
            'institucion_nacionalidad' => 'Nacional',
            'nombre_institucion' => 'Empresa Test S.A.',
            'tipo_institucion' => 'ONG',
            'sector_institucion' => 'Educación e investigación',
            'nombre_jefe_directo' => 'Jefe Test',
            'nombre_docente_supervisor' => 'Docente Test',
            'total_horas' => 800,
            'created_by' => $usuario->id,
        ], $datos));

        return [$registro, $empleado];
    }
}
