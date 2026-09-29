<?php

namespace Tests\Feature;

use App\Models\Pasantia;
use App\Models\User;
use App\Services\Documents\DocxTemplateEditor;
use App\Services\Documents\FormDvus013DataMapper;
use App\Services\Documents\LibreOfficePdfConverter;
use App\Services\Pasantias\FormDvus013DocumentService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;
use ZipArchive;

class FormDvus013DocumentTest extends TestCase
{
    use WithoutMiddleware;

    private array $directories = [];

    protected function tearDown(): void
    {
        foreach ($this->directories as $directory) File::deleteDirectory($directory);
        parent::tearDown();
    }

    private function directory(): string
    {
        $path = storage_path('framework/testing/form013-'.bin2hex(random_bytes(8)));
        File::ensureDirectoryExists($path);
        $this->directories[] = $path;
        return $path;
    }

    public function test_template_keeps_assets_and_maps_more_than_three_subjects_without_overwriting_labels(): void
    {
        $copy = $this->directory().'/test.docx';
        $master = storage_path('app/templates/form-dvus-013.docx');
        copy($master, $copy);
        $record = new Pasantia([
            'nombre_estudiante' => 'Ana & María', 'fecha_registro' => '2026-09-28',
            'asignaturas' => array_map(fn ($i) => ['codigo' => 'CODE-'.$i, 'nombre' => 'Materia '.$i], range(1, 6)),
            'descripcion_conocimientos_teoricos' => 'Conocimientos de prueba', 'habilidades_desarrollar' => 'Habilidades de prueba',
            'pasantia_remunerada' => false,
        ]);
        $editor = new DocxTemplateEditor($copy);
        (new FormDvus013DataMapper)->fill($editor, $record);
        $editor->save();
        $before = new ZipArchive; $before->open($master);
        $after = new ZipArchive; $after->open($copy);
        for ($i = 0; $i < $before->numFiles; $i++) {
            $entry = $before->getNameIndex($i);
            if ($entry !== 'word/document.xml') $this->assertSame($before->getFromName($entry), $after->getFromName($entry), $entry);
        }
        $xml = new \DOMDocument; $xml->loadXML($after->getFromName('word/document.xml'));
        $xpath = new \DOMXPath($xml); $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $cell = fn ($t, $r, $c) => $xpath->evaluate("string((//w:body/w:tbl)[$t]/w:tr[$r]/w:tc[$c])");
        $this->assertStringContainsString('Ana & María', $cell(1, 7, 3));
        $this->assertStringContainsString('CODE-6', $cell(2, 24, 3));
        $this->assertStringContainsString('Conocimientos de prueba', $cell(2, 25, 3));
        $this->assertStringContainsString('Habilidades de prueba', $cell(2, 26, 2));
        $this->assertStringContainsString('Resumen de las responsabilidades', $cell(2, 16, 2));
        $this->assertSame('X', trim($cell(2, 29, 3)));
        $before->close(); $after->close();
    }

    public function test_cache_is_shared_and_invalidates_when_record_changes(): void
    {
        $converter = Mockery::mock(LibreOfficePdfConverter::class);
        $converter->shouldReceive('convert')->twice()->andReturnUsing(function ($source, $directory) {
            $path = $directory.'/FORM-DVUS-013.pdf';
            file_put_contents($path, '%PDF-1.7'.str_repeat(' ', 150));
            return $path;
        });
        $record = new Pasantia(['nombre_estudiante' => 'Ana']);
        $record->id = random_int(900000000, 999999999);
        $this->directories[] = storage_path('app/generated/form-dvus-013/'.$record->id);
        $service = new FormDvus013DocumentService(new FormDvus013DataMapper, $converter);
        $first = $service->generatePdf($record);
        $this->assertSame($first, $service->generatePdf($record));
        $record->nombre_estudiante = 'María';
        $this->assertNotSame($first, $service->generatePdf($record));
    }

    public function test_signature_image_is_embedded_in_the_word_package(): void
    {
        $directory = $this->directory();
        $image = $directory.'/signature.png';
        file_put_contents($image, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl6V4sAAAAASUVORK5CYII='));
        $copy = $directory.'/signed.docx';
        copy(storage_path('app/templates/form-dvus-013.docx'), $copy);
        $editor = new DocxTemplateEditor($copy);
        $editor->setCellImage(5, 3, 1, $image)->save();
        $zip = new ZipArchive; $zip->open($copy);
        $xml = new \DOMDocument; $xml->loadXML($zip->getFromName('word/document.xml'));
        $xpath = new \DOMXPath($xml);
        $xpath->registerNamespace('v', 'urn:schemas-microsoft-com:vml');
        $xpath->registerNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $id = $xpath->evaluate('string(//v:imagedata[starts-with(@r:id,"signature")]/@r:id)');
        $this->assertNotEmpty($id);
        $this->assertSame(file_get_contents($image), $zip->getFromName('word/media/'.$id.'.png'));
        $this->assertStringContainsString($id, $zip->getFromName('word/_rels/document.xml.rels'));
        $zip->close();
    }

    public function test_pdf_routes_authorize_owner_and_share_inline_and_download_bytes(): void
    {
        $this->prepareDatabase();
        $record = Pasantia::create(['created_by' => 42]);
        $user = (new User)->forceFill(['id' => 42]);
        $this->actingAs($user);
        $path = $this->directory().'/fixture.pdf';
        file_put_contents($path, '%PDF-1.7'.str_repeat(' ', 150));
        $service = Mockery::mock(FormDvus013DocumentService::class);
        $service->shouldReceive('generatePdf')->twice()->andReturn($path);
        $this->app->instance(FormDvus013DocumentService::class, $service);
        $inline = $this->get(route('pasantias.pdf', [$record->id, 'inline' => 1]))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $download = $this->get(route('pasantias.pdf', $record->id))->assertOk()->assertDownload('FORM-DVUS-013-'.$record->id.'.pdf');
        $this->assertSame($inline->baseResponse->getFile()->getRealPath(), $download->baseResponse->getFile()->getRealPath());
        $this->assertStringStartsWith('inline;', $inline->headers->get('Content-Disposition'));

        $outsider = Mockery::mock(User::class)->makePartial();
        $outsider->forceFill(['id' => 99]);
        $outsider->shouldReceive('can')->andReturn(false);
        $this->actingAs($outsider)->get(route('pasantias.pdf', $record->id))->assertForbidden();
        $this->get(route('pasantias.anexo', [$record->id, 'carta']))->assertForbidden();
    }

    public function test_attachment_preview_serves_only_registered_files_and_converts_word(): void
    {
        $this->prepareDatabase();
        Storage::fake('public');
        Storage::disk('public')->put('pasantias/carta.docx', 'fixture');
        $record = Pasantia::create(['created_by' => 42, 'archivo_carta_formalizacion' => 'pasantias/carta.docx']);
        $this->actingAs((new User)->forceFill(['id' => 42]));
        $pdf = $this->directory().'/converted.pdf';
        file_put_contents($pdf, '%PDF-1.7'.str_repeat(' ', 150));
        $service = Mockery::mock(FormDvus013DocumentService::class);
        $service->shouldReceive('attachmentPdf')->once()->with(realpath(Storage::disk('public')->path('pasantias/carta.docx')))->andReturn($pdf);
        $this->app->instance(FormDvus013DocumentService::class, $service);
        $this->get(route('pasantias.anexo', [$record->id, 'carta']))->assertOk();
        $this->get(route('pasantias.anexo', [$record->id, 'convenio']))->assertNotFound();
        $this->get(route('pasantias.anexo', [$record->id, 'otro']))->assertNotFound();
    }

    private function prepareDatabase(): void
    {
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        Schema::create('pasantias', function (Blueprint $table) {
            $table->id(); $table->integer('created_by');
            $table->string('archivo_carta_formalizacion')->nullable();
            $table->string('archivo_convenio_marco')->nullable();
            $table->softDeletes(); $table->timestamps();
        });
    }
}
