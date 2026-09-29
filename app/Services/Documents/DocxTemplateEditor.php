<?php

namespace App\Services\Documents;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use ZipArchive;

class DocxTemplateEditor
{
    private const WORD_NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    private DOMDocument $document;

    private DOMXPath $xpath;

    /** @var array<string, string> Images to add on save: zip path => bytes. */
    private array $media = [];

    /** @var array<string, string> Relationships of the edited part to add on save: id => target. */
    private array $relationships = [];

    /** $entry: parte del DOCX que se edita (el cuerpo, o un encabezado o pie). */
    public function __construct(private readonly string $path, private readonly string $entry = 'word/document.xml')
    {
        $xml = $this->readEntry($this->entry);
        $this->document = new DOMDocument('1.0', 'UTF-8');
        $this->document->preserveWhiteSpace = true;

        if (! $this->document->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS)) {
            throw new RuntimeException('La plantilla FORM-DVUS-018 contiene XML inválido.');
        }

        $this->xpath = new DOMXPath($this->document);
        $this->xpath->registerNamespace('w', self::WORD_NS);
    }

    public function setCell(int $table, int $row, int $cell, mixed $value, bool $noWrap = false): self
    {
        $target = $this->cell($table, $row, $cell);
        $normalizedValue = $this->normalize($value);
        $this->replaceCellContent($target, $normalizedValue);

        if ($noWrap) {
            $properties = $this->child($target, 'tcPr', true);
            if (! $this->xpath->query('./w:noWrap', $properties)->item(0)) {
                $properties->appendChild($this->document->createElementNS(self::WORD_NS, 'w:noWrap'));
            }
        }

        return $this;
    }

    public function cloneRow(int $table, int $row): int
    {
        $source = $this->row($table, $row);
        $clone = $source->cloneNode(true);
        $source->parentNode?->insertBefore($clone, $source->nextSibling);

        return $row + 1;
    }

    public function setCellImage(int $table, int $row, int $cell, string $path): self
    {
        $size = @getimagesize($path);
        if (! $size || ! in_array($size['mime'], ['image/png', 'image/jpeg', 'image/webp'], true)) {
            throw new RuntimeException('La firma debe ser una imagen PNG, JPEG o WebP válida.');
        }

        // WebP es válido para la web, pero no es un formato de imagen portable
        // dentro de DOCX. Se normaliza a PNG solo en el directorio temporal de
        // generación, sin alterar el archivo de firma almacenado por el usuario.
        if ($size['mime'] === 'image/webp') {
            if (! function_exists('imagecreatefromwebp') || ! function_exists('imagepng')) {
                throw new RuntimeException('El servidor no tiene soporte para convertir firmas WebP a PNG.');
            }

            $source = @imagecreatefromwebp($path);
            if (! $source) {
                throw new RuntimeException('No se pudo leer la firma WebP.');
            }

            $path = dirname($this->path).'/signature-'.bin2hex(random_bytes(8)).'.png';
            imagealphablending($source, false);
            imagesavealpha($source, true);
            $saved = imagepng($source, $path);
            imagedestroy($source);

            if (! $saved) {
                throw new RuntimeException('No se pudo convertir la firma WebP a PNG.');
            }

            $size = @getimagesize($path);
        }

        $extension = $size['mime'] === 'image/png' ? 'png' : 'jpg';
        $id = 'signature'.bin2hex(random_bytes(8));
        $name = $id.'.'.$extension;
        $rels = new DOMDocument;
        $rels->loadXML($this->readEntry('word/_rels/document.xml.rels'), LIBXML_NONET);
        $relation = $rels->createElementNS('http://schemas.openxmlformats.org/package/2006/relationships', 'Relationship');
        $relation->setAttribute('Id', $id);
        $relation->setAttribute('Type', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/image');
        $relation->setAttribute('Target', 'media/'.$name);
        $rels->documentElement->appendChild($relation);
        $types = new DOMDocument;
        $types->loadXML($this->readEntry('[Content_Types].xml'), LIBXML_NONET);
        $type = $types->createElementNS('http://schemas.openxmlformats.org/package/2006/content-types', 'Override');
        $type->setAttribute('PartName', '/word/media/'.$name);
        $type->setAttribute('ContentType', $size['mime']);
        $types->documentElement->appendChild($type);
        $zip = new ZipArchive;
        if ($zip->open($this->path) !== true) throw new RuntimeException('No se pudo insertar la firma en el documento.');
        $zip->addFile($path, 'word/media/'.$name);
        $zip->addFromString('word/_rels/document.xml.rels', $rels->saveXML());
        $zip->addFromString('[Content_Types].xml', $types->saveXML());
        $zip->close();

        $target = $this->cell($table, $row, $cell);
        $paragraph = $this->child($target, 'p', false);
        $run = $this->document->createElementNS(self::WORD_NS, 'w:r');
        $pict = $this->document->createElementNS(self::WORD_NS, 'w:pict');
        $shape = $this->document->createElementNS('urn:schemas-microsoft-com:vml', 'v:shape');
        $scale = min(120 / $size[0], 40 / $size[1]);
        $shape->setAttribute('id', $id);
        $shape->setAttribute('type', '#_x0000_t75');
        // Sin contorno ni relleno: LibreOffice dibuja un recuadro alrededor de la firma si no se indica.
        $shape->setAttribute('stroked', 'f');
        $shape->setAttribute('filled', 'f');
        $shape->setAttribute('style', 'width:'.round($size[0] * $scale, 2).'pt;height:'.round($size[1] * $scale, 2).'pt');
        $image = $this->document->createElementNS('urn:schemas-microsoft-com:vml', 'v:imagedata');
        $image->setAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'r:id', $id);
        $shape->appendChild($image);
        $pict->appendChild($shape);
        $run->appendChild($pict);
        $paragraph->appendChild($run);
        return $this;
    }

    /**
     * Replaces {{name}} markers in the edited part. Word may split a marker across several
     * runs when the template is edited, so each paragraph is read as a whole and the value is
     * written in the run where the marker starts, keeping that run's formatting.
     *
     * @param  array<string, mixed>  $values
     */
    public function replacePlaceholders(array $values): self
    {
        foreach ($this->xpath->query('//w:p') as $paragraph) {
            $texts = iterator_to_array($this->xpath->query('.//w:t', $paragraph));
            $content = implode('', array_map(fn (DOMElement $text) => $text->textContent, $texts));

            if (! str_contains($content, '{{')
                || ! preg_match_all('/\{\{\s*([A-Za-z0-9_]+)\s*\}\}/', $content, $matches, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            // From the last marker to the first, so earlier byte offsets stay valid.
            foreach (array_reverse(array_keys($matches[0])) as $index) {
                [$marker, $offset] = $matches[0][$index];
                $name = $matches[1][$index][0];

                if (array_key_exists($name, $values)) {
                    $this->replaceSpan($texts, $offset, strlen($marker), $this->normalize($values[$name]));
                }
            }
        }

        return $this;
    }

    /**
     * Puts an inline image where the {{name}} marker is, scaled to fit the given box (EMU) with
     * its proportions. Without a usable PNG/JPEG the marker is just removed, so the paragraph
     * keeps its blank space (e.g. to sign by hand).
     */
    public function replaceImagePlaceholder(string $name, ?string $image, int $maxWidth, int $maxHeight): self
    {
        $marker = '{{'.$name.'}}';

        foreach ($this->xpath->query('//w:p') as $paragraph) {
            if (! $paragraph instanceof DOMElement || ! str_contains($paragraph->textContent, $marker)) {
                continue;
            }

            $this->replacePlaceholders([$name => '']);
            $size = $image !== null ? @getimagesizefromstring($image) : false;
            $extension = $size ? match ($size[2]) {
                IMAGETYPE_PNG => 'png',
                IMAGETYPE_JPEG => 'jpeg',
                default => null,
            } : null;

            if ($extension === null || $size[0] <= 0 || $size[1] <= 0) {
                return $this;
            }

            $scale = min($maxWidth / $size[0], $maxHeight / $size[1]);
            $width = (int) round($size[0] * $scale);
            $height = (int) round($size[1] * $scale);
            $id = 'rIdImagen'.bin2hex(random_bytes(4));
            $target = 'media/'.$name.'-'.bin2hex(random_bytes(4)).'.'.$extension;
            $this->media['word/'.$target] = $image;
            $this->relationships[$id] = $target;

            $run = new DOMDocument;
            $run->loadXML($this->imageRun($id, $name, $width, $height));
            $paragraph->appendChild($this->document->importNode($run->documentElement, true));

            return $this;
        }

        return $this;
    }

    public function enforceFixedTables(): self
    {
        foreach ($this->xpath->query('//w:tbl') as $table) {
            if (! $table instanceof DOMElement) {
                continue;
            }

            // Do not add layout properties that are absent in the master: doing so
            // changes Word's original column calculation. Existing fixed layouts,
            // grids and exact cell widths remain untouched.
            $layout = $this->xpath->query('./w:tblPr/w:tblLayout', $table)->item(0);
            if ($layout instanceof DOMElement && $layout->getAttributeNS(self::WORD_NS, 'type') === 'fixed') {
                $layout->setAttributeNS(self::WORD_NS, 'w:type', 'fixed');
            }
        }

        return $this;
    }

    public function save(): void
    {
        $zip = new ZipArchive;
        if ($zip->open($this->path) !== true) {
            throw new RuntimeException("No se pudo escribir el DOCX temporal: {$this->path}");
        }

        $zip->addFromString($this->entry, $this->document->saveXML());

        if ($this->relationships !== []) {
            foreach ($this->media as $mediaPath => $bytes) {
                $zip->addFromString($mediaPath, $bytes);
            }

            $relsPath = dirname($this->entry).'/_rels/'.basename($this->entry).'.rels';
            $rels = $zip->getFromName($relsPath)
                ?: '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"></Relationships>';
            $nuevas = '';
            foreach ($this->relationships as $id => $target) {
                $nuevas .= '<Relationship Id="'.$id.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="'.$target.'"/>';
            }
            $zip->addFromString($relsPath, str_replace('</Relationships>', $nuevas.'</Relationships>', $rels));

            $types = (string) $zip->getFromName('[Content_Types].xml');
            foreach (['png' => 'image/png', 'jpeg' => 'image/jpeg'] as $extension => $mime) {
                if (! preg_match('/<Default Extension="'.$extension.'"/i', $types)) {
                    $types = str_replace('</Types>', '<Default Extension="'.$extension.'" ContentType="'.$mime.'"/></Types>', $types);
                }
            }
            $zip->addFromString('[Content_Types].xml', $types);
        }

        $zip->close();
    }

    private function imageRun(string $relationshipId, string $name, int $width, int $height): string
    {
        $docPrId = random_int(10000, 99999);

        return '<w:r xmlns:w="'.self::WORD_NS.'"'
            .' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"'
            .' xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"'
            .' xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"'
            .' xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            .'<w:drawing><wp:inline distT="0" distB="0" distL="0" distR="0">'
            .'<wp:extent cx="'.$width.'" cy="'.$height.'"/>'
            .'<wp:docPr id="'.$docPrId.'" name="'.htmlspecialchars($name, ENT_XML1).'"/>'
            .'<wp:cNvGraphicFramePr><a:graphicFrameLocks noChangeAspect="1"/></wp:cNvGraphicFramePr>'
            .'<a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:pic>'
            .'<pic:nvPicPr><pic:cNvPr id="0" name="'.htmlspecialchars($name, ENT_XML1).'"/><pic:cNvPicPr/></pic:nvPicPr>'
            .'<pic:blipFill><a:blip r:embed="'.$relationshipId.'"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>'
            .'<pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="'.$width.'" cy="'.$height.'"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>'
            .'</pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing></w:r>';
    }

    private function replaceCellContent(DOMElement $cell, string $value): void
    {
        foreach (iterator_to_array($this->xpath->query('.//w:br[not(@w:type="page")]', $cell)) as $break) {
            $break->parentNode?->removeChild($break);
        }

        $texts = iterator_to_array($this->xpath->query('.//w:t', $cell));
        foreach ($texts as $textNode) {
            while ($textNode->firstChild) {
                $textNode->removeChild($textNode->firstChild);
            }
        }

        $paragraph = $this->xpath->query('./w:p', $cell)->item(0);
        if (! $paragraph instanceof DOMElement) {
            $paragraph = $this->document->createElementNS(self::WORD_NS, 'w:p');
            $cell->appendChild($paragraph);
        }
        $run = $this->xpath->query('.//w:r', $paragraph)->item(0);
        if (! $run instanceof DOMElement) {
            $run = $this->document->createElementNS(self::WORD_NS, 'w:r');
            $paragraph->appendChild($run);
        }
        $text = $texts[0] ?? null;
        if ($text instanceof DOMElement && $text->parentNode instanceof DOMElement && $text->parentNode->localName === 'r') {
            $run = $text->parentNode;
        } elseif (! $text instanceof DOMElement) {
            $text = $this->document->createElementNS(self::WORD_NS, 'w:t');
            $run->appendChild($text);
        }

        $lines = preg_split('/\R/u', $value) ?: [''];
        $text->setAttribute('xml:space', 'preserve');
        $text->appendChild($this->document->createTextNode(array_shift($lines) ?? ''));
        $this->applyControlledFontSize($run, $value);
        foreach ($lines as $line) {
            $break = $this->document->createElementNS(self::WORD_NS, 'w:br');
            $nextText = $this->document->createElementNS(self::WORD_NS, 'w:t');
            $nextText->setAttribute('xml:space', 'preserve');
            $nextText->appendChild($this->document->createTextNode($line));
            $run->appendChild($break);
            $run->appendChild($nextText);
        }

        $width = (int) $this->xpath->evaluate('string(./w:tcPr/w:tcW/@w:w)', $cell);
        $widthType = $this->xpath->evaluate('string(./w:tcPr/w:tcW/@w:type)', $cell);
        $charactersPerLine = $widthType === 'pct'
            ? max(12, (int) floor(95 * $width / 5000))
            : max(12, (int) floor($width / 100));
        $estimatedLines = collect(preg_split('/\R/u', $value) ?: [''])
            ->sum(fn (string $line) => max(1, (int) ceil(mb_strlen($line) / $charactersPerLine)));
        $paragraphsToRemove = max(0, $estimatedLines - 1);
        $paragraphs = iterator_to_array($this->xpath->query('./w:p', $cell));
        for ($index = count($paragraphs) - 1; $index > 0 && $paragraphsToRemove > 0; $index--) {
            $candidate = $paragraphs[$index];
            $hasText = trim($this->xpath->evaluate('string(.)', $candidate)) !== '';
            $hasPageMarker = $this->xpath->query('.//w:lastRenderedPageBreak | .//w:br[@w:type="page"]', $candidate)->length > 0;
            if (! $hasText && ! $hasPageMarker) {
                $candidate->parentNode?->removeChild($candidate);
                $paragraphsToRemove--;
            }
        }
    }

    /** @param  list<DOMElement>  $texts */
    private function replaceSpan(array $texts, int $start, int $length, string $replacement): void
    {
        $end = $start + $length;
        $position = 0;
        $written = false;

        foreach ($texts as $text) {
            $content = $text->textContent;
            $textStart = $position;
            $position += strlen($content);

            if ($position <= $start || $textStart >= $end) {
                continue;
            }

            $from = max($start, $textStart) - $textStart;
            $to = min($end, $position) - $textStart;
            $updated = substr($content, 0, $from).($written ? '' : $replacement).substr($content, $to);
            $written = true;

            while ($text->firstChild) {
                $text->removeChild($text->firstChild);
            }
            $text->setAttribute('xml:space', 'preserve');
            $text->appendChild($this->document->createTextNode($updated));
        }
    }

    private function applyControlledFontSize(DOMElement $run, string $value): void
    {
        $length = mb_strlen($value);
        $halfPoints = str_contains($value, '@')
            ? 14
            : ($length > 600 ? 14 : ($length > 300 ? 16 : null));
        if ($halfPoints === null) {
            return;
        }

        $properties = $this->child($run, 'rPr', true);
        foreach (['sz', 'szCs'] as $name) {
            $size = $this->xpath->query('./w:'.$name, $properties)->item(0);
            if (! $size instanceof DOMElement) {
                $size = $this->document->createElementNS(self::WORD_NS, 'w:'.$name);
                $properties->appendChild($size);
            }
            $size->setAttributeNS(self::WORD_NS, 'w:val', (string) $halfPoints);
        }
    }

    private function cell(int $table, int $row, int $cell): DOMElement
    {
        $targetRow = $this->row($table, $row);
        $target = $this->xpath->query('./w:tc', $targetRow)->item($cell - 1);
        if (! $target instanceof DOMElement) {
            throw new RuntimeException("No existe la celda {$table}:{$row}:{$cell} en la plantilla FORM-DVUS-018.");
        }

        return $target;
    }

    private function row(int $table, int $row): DOMElement
    {
        $targetTable = $this->xpath->query('//w:body/w:tbl')->item($table - 1);
        $target = $targetTable ? $this->xpath->query('./w:tr', $targetTable)->item($row - 1) : null;
        if (! $target instanceof DOMElement) {
            throw new RuntimeException("No existe la fila {$table}:{$row} en la plantilla FORM-DVUS-018.");
        }

        return $target;
    }

    private function child(DOMElement $parent, string $name, bool $prepend): DOMElement
    {
        $existing = $this->xpath->query('./w:'.$name, $parent)->item(0);
        if ($existing instanceof DOMElement) {
            return $existing;
        }

        $child = $this->document->createElementNS(self::WORD_NS, 'w:'.$name);
        if ($prepend && $parent->firstChild) {
            $parent->insertBefore($child, $parent->firstChild);
        } else {
            $parent->appendChild($child);
        }

        return $child;
    }

    private function normalize(mixed $value): string
    {
        return trim(preg_replace("/\r\n?|\u{2028}|\u{2029}/u", "\n", (string) ($value ?? '')) ?? '');
    }

    private function readEntry(string $entry): string
    {
        $zip = new ZipArchive;
        if ($zip->open($this->path) !== true) {
            throw new RuntimeException("No se pudo abrir la plantilla DOCX: {$this->path}");
        }
        $contents = $zip->getFromName($entry);
        $zip->close();
        if (! is_string($contents)) {
            throw new RuntimeException("La plantilla DOCX no contiene {$entry}.");
        }

        return $contents;
    }
}
