<?php

namespace Tests\Feature;

use App\Services\Documents\DocxTemplateEditor;
use Tests\TestCase;
use ZipArchive;

/** Marcadores {{...}} de las plantillas de Word, aunque Word los parta en varios fragmentos. */
class DocxTemplateEditorMarcadoresTest extends TestCase
{
    public function test_reemplaza_marcadores_completos_y_partidos_y_conserva_los_desconocidos(): void
    {
        $docx = sys_get_temp_dir().'/marcadores-'.uniqid().'.docx';
        $zip = new ZipArchive;
        $zip->open($docx, ZipArchive::CREATE);
        $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
            .'<w:p><w:r><w:t xml:space="preserve">Estimada {{trata</w:t></w:r><w:r><w:rPr><w:b/></w:rPr><w:t>miento}}:</w:t></w:r></w:p>'
            .'<w:p><w:r><w:t xml:space="preserve">{{horas}} horas en {{ lugar }} y {{otro}} &amp; más</w:t></w:r></w:p>'
            .'</w:body></w:document>');
        $zip->close();

        (new DocxTemplateEditor($docx))->replacePlaceholders([
            'tratamiento' => 'Licenciada',
            'horas' => 800,
            'lugar' => 'Choluteca & Valle',
        ])->save();

        $zip->open($docx);
        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();
        unlink($docx);

        $parrafos = array_map(
            fn (string $parrafo) => trim(html_entity_decode(strip_tags($parrafo))),
            explode('</w:p>', $xml)
        );
        $this->assertSame('Estimada Licenciada:', $parrafos[0]);
        $this->assertSame('800 horas en Choluteca & Valle y {{otro}} & más', $parrafos[1]);
        // El valor queda en el fragmento donde empieza el marcador; el siguiente conserva su formato.
        $this->assertStringContainsString('<w:t xml:space="preserve">Estimada Licenciada</w:t>', $xml);
        $this->assertStringContainsString('<w:b/></w:rPr><w:t xml:space="preserve">:</w:t>', $xml);
    }

    public function test_pone_la_imagen_en_su_marcador_y_sin_imagen_deja_el_espacio(): void
    {
        $firma = (string) file_get_contents(public_path('images/logo_nuevo.png'));
        [$ancho, $alto] = getimagesizefromstring($firma);

        $conFirma = $this->docxConParrafo('{{firma}}');
        (new DocxTemplateEditor($conFirma))->replaceImagePlaceholder('firma', $firma, 2160000, 648000)->save();
        $zip = new ZipArchive;
        $zip->open($conFirma);
        $documento = (string) $zip->getFromName('word/document.xml');
        $relaciones = (string) $zip->getFromName('word/_rels/document.xml.rels');
        $tipos = (string) $zip->getFromName('[Content_Types].xml');
        preg_match('/r:embed="([^"]+)"/', $documento, $embed);
        preg_match('/Id="'.($embed[1] ?? 'x').'"[^>]*Target="([^"]+)"/', $relaciones, $destino);
        $imagen = $zip->getFromName('word/'.($destino[1] ?? 'x'));
        $zip->close();
        unlink($conFirma);

        $this->assertStringNotContainsString('{{firma}}', $documento);
        $this->assertStringContainsString('<w:drawing>', $documento);
        $this->assertSame($firma, $imagen);
        $this->assertStringContainsString('<Default Extension="png"', $tipos);
        // Cabe en la caja de 6 × 1,8 cm sin deformarse.
        preg_match('/<wp:extent cx="(\d+)" cy="(\d+)"/', $documento, $medidas);
        $this->assertLessThanOrEqual(2160000, (int) $medidas[1]);
        $this->assertLessThanOrEqual(648000, (int) $medidas[2]);
        $this->assertEqualsWithDelta($ancho / $alto, $medidas[1] / $medidas[2], 0.01);

        $sinFirma = $this->docxConParrafo('{{firma}}');
        (new DocxTemplateEditor($sinFirma))->replaceImagePlaceholder('firma', null, 2160000, 648000)->save();
        $zip->open($sinFirma);
        $documento = (string) $zip->getFromName('word/document.xml');
        $zip->close();
        unlink($sinFirma);

        $this->assertStringNotContainsString('{{firma}}', $documento);
        $this->assertStringNotContainsString('<w:drawing>', $documento);
        $this->assertStringContainsString('<w:p>', $documento);
    }

    private function docxConParrafo(string $texto): string
    {
        $docx = sys_get_temp_dir().'/imagen-'.uniqid().'.docx';
        $zip = new ZipArchive;
        $zip->open($docx, ZipArchive::CREATE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="xml" ContentType="application/xml"/></Types>');
        $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
            .'<w:p><w:r><w:t>'.$texto.'</w:t></w:r></w:p></w:body></w:document>');
        $zip->close();

        return $docx;
    }
}
