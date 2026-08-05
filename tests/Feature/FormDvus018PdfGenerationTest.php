<?php

namespace Tests\Feature;

use App\Http\Controllers\ENF\EnfAccionController;
use App\Models\ENF\EnfAccion;
use App\Services\Documents\DocxTemplateEditor;
use App\Services\Documents\FormDvus018DataMapper;
use App\Services\FormDvus018DocumentService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;
use ZipArchive;

class FormDvus018PdfGenerationTest extends TestCase
{
    private array $temporaryPaths = [];

    protected function tearDown(): void
    {
        foreach (array_reverse($this->temporaryPaths) as $path) {
            if (is_file($path)) {
                @unlink($path);
            } elseif (is_dir($path)) {
                $this->removeDirectory($path);
            }
        }

        parent::tearDown();
    }

    public function test_pdf_endpoints_require_an_authenticated_user(): void
    {
        $this->get(route('enf.acciones.pdf.ver', 18))->assertRedirect(route('login'));
        $this->get(route('enf.acciones.pdf', 18))->assertRedirect(route('login'));
    }

    public function test_docx_editor_preserves_master_assets_page_size_and_section_breaks(): void
    {
        $master = storage_path('app/templates/form-dvus-018.docx');
        $copy = storage_path('framework/testing/form-dvus-018-'.uniqid().'.docx');
        copy($master, $copy);
        $this->temporaryPaths[] = $copy;

        $before = $this->zipEntries($master);
        $editor = new DocxTemplateEditor($copy);
        $editor->setCell(1, 2, 2, '2026', true)
            ->setCell(1, 3, 2, 'Proyecto dinámico de prueba')
            ->setCell(3, 14, 1, '5', true)
            ->enforceFixedTables()
            ->save();
        $after = $this->zipEntries($copy);

        $this->assertSame(array_keys($before), array_keys($after));
        foreach ($before as $entry => $contents) {
            if ($entry !== 'word/document.xml') {
                $this->assertSame(hash('sha256', $contents), hash('sha256', $after[$entry]), "Cambió el recurso {$entry}");
            }
        }
        $this->assertStringContainsString('w:w="12240"', $after['word/document.xml']);
        $this->assertStringContainsString('w:h="15840"', $after['word/document.xml']);
        $this->assertSame(
            substr_count($before['word/document.xml'], '<w:sectPr'),
            substr_count($after['word/document.xml'], '<w:sectPr')
        );
        $this->assertSame(
            substr_count($before['word/document.xml'], '<w:lastRenderedPageBreak'),
            substr_count($after['word/document.xml'], '<w:lastRenderedPageBreak')
        );
        $this->assertStringContainsString('Proyecto dinámico de prueba', $after['word/document.xml']);
        $this->assertStringContainsString('<w:noWrap', $after['word/document.xml']);
        $this->assertSame(
            substr_count($before['word/document.xml'], '<w:tblLayout w:type="fixed"'),
            substr_count($after['word/document.xml'], '<w:tblLayout w:type="fixed"')
        );
    }

    public function test_every_mapped_field_targets_an_existing_master_cell(): void
    {
        $action = new EnfAccion([
            'codigo_formulario' => 'FORM-DVUS-018',
            'nombre_accion' => 'Proyecto de prueba',
            'fecha_solicitud' => '2026-08-05',
            'fecha_inicio' => '2026-08-10',
            'fecha_finalizacion' => '2026-12-10',
        ]);
        $action->created_at = Carbon::parse('2026-08-05');
        foreach (['lugaresEjecucion', 'accionCatalogos', 'equipo', 'contrapartes', 'participacionUniversitaria', 'practicasAsignatura', 'objetivosEspecificos', 'resultados', 'ods', 'metasContribuye', 'presupuestos', 'cronograma', 'firmas', 'documentos'] as $relation) {
            $action->setRelation($relation, new Collection);
        }
        foreach (['beneficiarios', 'centroFacultad', 'departamentoAcademico', 'carrera', 'modalidad'] as $relation) {
            $action->setRelation($relation, null);
        }

        $copy = storage_path('framework/testing/form-dvus-018-map-'.uniqid().'.docx');
        copy(storage_path('app/templates/form-dvus-018.docx'), $copy);
        $this->temporaryPaths[] = $copy;
        $editor = new DocxTemplateEditor($copy);
        $cells = (new FormDvus018DataMapper)->cells($action);
        foreach ($cells as [$table, $row, $cell, $value, $noWrap]) {
            $editor->setCell($table, $row, $cell, $value, $noWrap);
        }
        $editor->enforceFixedTables()->save();

        $this->assertGreaterThan(200, count($cells));
        $this->assertFileExists($copy);
        $this->assertSame('Microsoft Word 2007+', trim((string) shell_exec('file -b '.escapeshellarg($copy))));
    }

    public function test_service_reuses_identical_pdf_and_removes_temporary_files(): void
    {
        $testDirectory = storage_path('framework/testing/form018-service-'.uniqid());
        mkdir($testDirectory, 0775, true);
        $this->temporaryPaths[] = $testDirectory;
        $libreOffice = $testDirectory.'/fake-libreoffice';
        $pdfInfo = $testDirectory.'/fake-pdfinfo';
        $counter = $testDirectory.'/calls';
        file_put_contents($libreOffice, <<<'SH'
#!/bin/sh
out=''
previous=''
for argument in "$@"; do
    if [ "$previous" = '--outdir' ]; then out="$argument"; fi
    previous="$argument"
done
printf 'call\n' >> "__COUNTER__"
printf '%%PDF-1.7\n%% FORM-DVUS-018 deterministic test payload %0150d\n%%%%EOF\n' 1 > "$out/FORM-DVUS-018.pdf"
SH);
        file_put_contents($libreOffice, str_replace('__COUNTER__', $counter, file_get_contents($libreOffice)));
        file_put_contents($pdfInfo, "#!/bin/sh\nprintf 'Pages:          11\\n'\n");
        chmod($libreOffice, 0755);
        chmod($pdfInfo, 0755);
        config()->set('documents.libreoffice_binary', $libreOffice);
        config()->set('documents.pdfinfo_binary', $pdfInfo);

        $mapper = Mockery::mock(FormDvus018DataMapper::class);
        $mapper->shouldReceive('cells')->twice()->andReturn([[1, 2, 2, '2026', true]]);
        $service = new FormDvus018DocumentService($mapper);
        $action = new EnfAccion(['codigo_formulario' => 'FORM-DVUS-018']);
        $action->id = random_int(900000, 999999);
        $cacheDirectory = storage_path('app/generated/form-dvus-018/'.$action->id);
        $this->temporaryPaths[] = $cacheDirectory;

        $first = $service->generatePdf($action);
        $second = $service->generatePdf($action);

        $this->assertSame($first, $second);
        $this->assertSame(hash_file('sha256', $first), hash_file('sha256', $second));
        $this->assertSame(1, substr_count((string) file_get_contents($counter), 'call'));
        $temporaryRoot = storage_path('app/tmp/form-dvus-018');
        $this->assertSame([], array_values(array_filter(glob($temporaryRoot.'/*') ?: [], 'is_dir')));
    }

    public function test_view_and_download_responses_use_the_same_pdf_with_only_disposition_changed(): void
    {
        $pdf = storage_path('framework/testing/form018-shared-'.uniqid().'.pdf');
        file_put_contents($pdf, "%PDF-1.7\n".str_repeat('same-binary-content', 20)."\n%%EOF\n");
        $this->temporaryPaths[] = $pdf;
        $hash = hash_file('sha256', $pdf);

        $action = Mockery::mock(EnfAccion::class)->makePartial();
        $action->id = 18;
        $action->codigo_formulario = 'FORM-DVUS-018';
        $action->shouldReceive('loadMissing')->twice()->andReturnSelf();
        $documents = Mockery::mock(FormDvus018DocumentService::class);
        $documents->shouldReceive('generatePdf')->twice()->with($action)->andReturn($pdf);
        $documents->shouldReceive('hash')->twice()->with($pdf)->andReturn($hash);
        $controller = app(EnfAccionController::class);

        $inline = $controller->verPdf($action, $documents);
        $attachment = $controller->descargarPdf($action, $documents);

        $this->assertSame('application/pdf', $inline->headers->get('Content-Type'));
        $this->assertSame('application/pdf', $attachment->headers->get('Content-Type'));
        $this->assertSame('inline; filename="FORM-DVUS-018-18.pdf"', $inline->headers->get('Content-Disposition'));
        $this->assertSame('attachment; filename="FORM-DVUS-018-18.pdf"', $attachment->headers->get('Content-Disposition'));
        $this->assertSame($hash, $inline->headers->get('X-Content-SHA256'));
        $this->assertSame($hash, $attachment->headers->get('X-Content-SHA256'));
        $this->assertSame($inline->getFile()->getRealPath(), $attachment->getFile()->getRealPath());
    }

    private function zipEntries(string $path): array
    {
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true);
        $entries = [];
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);
            $entries[$name] = $zip->getFromIndex($index);
        }
        $zip->close();

        return $entries;
    }

    private function removeDirectory(string $directory): void
    {
        foreach (scandir($directory) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $directory.'/'.$item;
            is_dir($path) ? $this->removeDirectory($path) : @unlink($path);
        }
        @rmdir($directory);
    }
}
