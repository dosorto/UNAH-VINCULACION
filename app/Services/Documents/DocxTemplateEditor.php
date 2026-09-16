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

    private const FORM_FIELD_HALF_POINTS = 16;

    private DOMDocument $document;

    private DOMXPath $xpath;

    public function __construct(private readonly string $path)
    {
        $xml = $this->readEntry('word/document.xml');
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

        $zip->addFromString('word/document.xml', $this->document->saveXML());
        $zip->close();
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
        $runProperties = $this->xpath->query('./w:rPr', $run)->item(0);
        $paragraphRuns = iterator_to_array($this->xpath->query('./w:r', $paragraph));
        foreach ($paragraphRuns as $paragraphRun) {
            $paragraphRun->parentNode?->removeChild($paragraphRun);
        }

        $parts = preg_split('/^([^:]+:\s*)/u', $value, 2, PREG_SPLIT_DELIM_CAPTURE);
        $hasLabel = count($parts) === 3 && trim((string) $parts[1]) !== '';
        $label = $hasLabel ? (string) $parts[1] : '';
        $response = $hasLabel ? (string) $parts[2] : $value;

        if ($hasLabel) {
            $labelRun = $this->document->createElementNS(self::WORD_NS, 'w:r');
            if ($runProperties instanceof DOMElement) {
                $labelRun->appendChild($runProperties->cloneNode(true));
            }
            $this->setRunFontSize($labelRun, self::FORM_FIELD_HALF_POINTS);
            $paragraph->appendChild($labelRun);
            $this->appendRunText($labelRun, $label);

            $responseRun = $this->document->createElementNS(self::WORD_NS, 'w:r');
            if ($runProperties instanceof DOMElement) {
                $responseProperties = $runProperties->cloneNode(true);
                foreach (['b', 'bCs', 'i', 'iCs'] as $styleName) {
                    foreach ($this->xpath->query('./w:'.$styleName, $responseProperties) as $style) {
                        $responseProperties->removeChild($style);
                    }
                }
                $responseRun->appendChild($responseProperties);
            }
            $this->setRunFontSize($responseRun, self::FORM_FIELD_HALF_POINTS);
            $paragraph->appendChild($responseRun);
            $this->appendRunText($responseRun, $response);
        } else {
            $paragraph->appendChild($run);
            $this->appendRunText($run, $response);
            $this->setRunFontSize($run, self::FORM_FIELD_HALF_POINTS);
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

    private function appendRunText(DOMElement $run, string $value): void
    {
        $lines = preg_split('/\R/u', $value) ?: [''];
        foreach ($lines as $index => $line) {
            if ($index > 0) {
                $run->appendChild($this->document->createElementNS(self::WORD_NS, 'w:br'));
            }
            $text = $this->document->createElementNS(self::WORD_NS, 'w:t');
            $text->setAttribute('xml:space', 'preserve');
            $text->appendChild($this->document->createTextNode($line));
            $run->appendChild($text);
        }
    }

    private function setRunFontSize(DOMElement $run, int $halfPoints): void
    {
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
