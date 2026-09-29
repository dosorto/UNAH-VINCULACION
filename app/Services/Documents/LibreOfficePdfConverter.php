<?php

namespace App\Services\Documents;

use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Convierte un DOCX llenado desde una plantilla a PDF con LibreOffice sin interfaz. Es la
 * herramienta de los documentos que se muestran en el visor de PDF (FORM-DVUS-018, solicitud
 * de práctica PPS): el PDF queda igual a la plantilla de Word.
 */
class LibreOfficePdfConverter
{
    /** Devuelve la ruta del PDF creado en $outputDirectory con el mismo nombre del DOCX. */
    public function convert(string $docxPath, string $outputDirectory, string $documento): string
    {
        $binary = $this->resolveExecutable(
            (string) config('documents.libreoffice_binary'),
            (array) config('documents.libreoffice_candidates', [])
        );
        if ($binary === null) {
            throw new RuntimeException('LibreOffice no está disponible. Configure LIBREOFFICE_BINARY con la ruta de libreoffice o soffice.');
        }

        $profileDirectory = $outputDirectory.'/libreoffice-profile';
        if (! is_dir($profileDirectory) && ! mkdir($profileDirectory, 0775, true) && ! is_dir($profileDirectory)) {
            throw new RuntimeException("No se pudo crear el directorio {$profileDirectory}.");
        }
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
            throw new RuntimeException("LibreOffice no pudo convertir {$documento}: ".trim($process->getErrorOutput() ?: $process->getOutput()));
        }

        $pdfPath = $outputDirectory.'/'.pathinfo($docxPath, PATHINFO_FILENAME).'.pdf';
        if (! $this->isValidPdf($pdfPath)) {
            throw new RuntimeException("LibreOffice finalizó sin crear un PDF válido de {$documento}.");
        }

        return $pdfPath;
    }

    public function isValidPdf(string $path): bool
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

    public function resolveExecutable(string $configured, array $candidates): ?string
    {
        foreach (array_unique(array_filter([$configured, ...$candidates])) as $candidate) {
            if (is_string($candidate) && is_executable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
