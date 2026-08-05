<?php

namespace App\Services;

use App\Models\ENF\EnfAccion;
use App\Services\Documents\DocxTemplateEditor;
use App\Services\Documents\FormDvus018DataMapper;
use RuntimeException;
use Symfony\Component\Process\Process;

class FormDvus018DocumentService
{
    public function __construct(private readonly FormDvus018DataMapper $mapper) {}

    public function generatePdf(EnfAccion $action): string
    {
        $template = (string) config('documents.form_dvus_018_template');
        $this->assertReadableFile($template, 'No se encontró la plantilla maestra FORM-DVUS-018.');

        $cells = $this->mapper->cells($action);
        $fingerprint = hash('sha256', hash_file('sha256', $template).'|'.json_encode($cells, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $cacheDirectory = storage_path('app/generated/form-dvus-018/'.(int) $action->getKey());
        $pdfPath = $cacheDirectory.'/'.$fingerprint.'.pdf';
        $this->ensureDirectory($cacheDirectory);

        if ($this->isValidPdf($pdfPath)) {
            return $pdfPath;
        }

        $lockPath = $cacheDirectory.'/.generation.lock';
        $lock = fopen($lockPath, 'c+');
        if ($lock === false || ! flock($lock, LOCK_EX)) {
            throw new RuntimeException('No se pudo bloquear la generación concurrente de FORM-DVUS-018.');
        }

        try {
            if ($this->isValidPdf($pdfPath)) {
                return $pdfPath;
            }

            $temporaryDirectory = storage_path('app/tmp/form-dvus-018/'.bin2hex(random_bytes(16)));
            $this->ensureDirectory($temporaryDirectory);
            $docxPath = $temporaryDirectory.'/FORM-DVUS-018.docx';

            try {
                if (! copy($template, $docxPath)) {
                    throw new RuntimeException('No se pudo copiar la plantilla maestra de FORM-DVUS-018.');
                }

                $editor = new DocxTemplateEditor($docxPath);
                foreach ($cells as [$table, $row, $cell, $value, $noWrap]) {
                    $editor->setCell($table, $row, $cell, $value, $noWrap);
                }
                $editor->enforceFixedTables()->save();

                $convertedPdf = $this->convertToPdf($docxPath, $temporaryDirectory);
                $this->validatePdf($convertedPdf);

                $stagedPath = $cacheDirectory.'/.'.$fingerprint.'.'.bin2hex(random_bytes(6)).'.pdf';
                if (! copy($convertedPdf, $stagedPath) || ! rename($stagedPath, $pdfPath)) {
                    @unlink($stagedPath);
                    throw new RuntimeException('No se pudo publicar el PDF generado de FORM-DVUS-018.');
                }

                foreach (glob($cacheDirectory.'/*.pdf') ?: [] as $oldPdf) {
                    if ($oldPdf !== $pdfPath) {
                        @unlink($oldPdf);
                    }
                }
            } finally {
                $this->removeDirectory($temporaryDirectory);
            }

            return $pdfPath;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    public function hash(string $pdfPath): string
    {
        $this->assertReadableFile($pdfPath, 'No se puede calcular la huella del PDF FORM-DVUS-018.');

        return hash_file('sha256', $pdfPath);
    }

    private function convertToPdf(string $docxPath, string $outputDirectory): string
    {
        $binary = $this->resolveExecutable(
            (string) config('documents.libreoffice_binary'),
            (array) config('documents.libreoffice_candidates', [])
        );
        if ($binary === null) {
            throw new RuntimeException('LibreOffice no está disponible. Configure LIBREOFFICE_BINARY con la ruta de libreoffice o soffice.');
        }

        $profileDirectory = $outputDirectory.'/libreoffice-profile';
        $this->ensureDirectory($profileDirectory);
        $profileUri = 'file://'.str_replace('%2F', '/', rawurlencode($profileDirectory));
        $process = new Process([
            $binary,
            '-env:UserInstallation='.$profileUri,
            '--headless',
            '--convert-to',
            'pdf:writer_pdf_Export',
            '--outdir',
            $outputDirectory,
            $docxPath,
        ]);
        $process->setTimeout(180);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('LibreOffice no pudo convertir FORM-DVUS-018: '.trim($process->getErrorOutput() ?: $process->getOutput()));
        }

        $pdfPath = $outputDirectory.'/'.pathinfo($docxPath, PATHINFO_FILENAME).'.pdf';
        if (! $this->isValidPdf($pdfPath)) {
            throw new RuntimeException('LibreOffice finalizó sin crear un PDF válido de FORM-DVUS-018.');
        }

        return $pdfPath;
    }

    private function validatePdf(string $pdfPath): void
    {
        $expectedPages = (int) config('documents.form_dvus_018_expected_pages', 11);
        $pdfInfo = $this->resolveExecutable(
            (string) config('documents.pdfinfo_binary'),
            (array) config('documents.pdfinfo_candidates', [])
        );
        if ($pdfInfo === null) {
            return;
        }

        $process = new Process([$pdfInfo, $pdfPath]);
        $process->setTimeout(30);
        $process->run();
        if (! $process->isSuccessful() || ! preg_match('/^Pages:\s+(\d+)/mi', $process->getOutput(), $match)) {
            throw new RuntimeException('No se pudo validar la cantidad de páginas del PDF FORM-DVUS-018.');
        }
        if ((int) $match[1] !== $expectedPages) {
            throw new RuntimeException("El PDF FORM-DVUS-018 contiene {$match[1]} páginas; se esperaban {$expectedPages}.");
        }
    }

    private function isValidPdf(string $path): bool
    {
        if (! is_file($path) || filesize($path) < 100) {
            return false;
        }
        $handle = fopen($path, 'rb');
        $signature = $handle ? fread($handle, 5) : false;
        if (is_resource($handle)) {
            fclose($handle);
        }

        return $signature === '%PDF-';
    }

    private function assertReadableFile(string $path, string $message): void
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException($message.' Ruta: '.$path);
        }
    }

    private function ensureDirectory(string $directory): void
    {
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException("No se pudo crear el directorio {$directory}.");
        }
    }

    private function resolveExecutable(string $configured, array $candidates): ?string
    {
        foreach (array_unique(array_filter([$configured, ...$candidates])) as $candidate) {
            if (is_string($candidate) && is_executable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }
        $items = scandir($directory) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $directory.'/'.$item;
            if (is_dir($path) && ! is_link($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($directory);
    }
}
