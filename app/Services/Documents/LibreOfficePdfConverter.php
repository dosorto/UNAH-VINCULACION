<?php

namespace App\Services\Documents;

use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Convierte un DOCX llenado desde una plantilla a PDF con LibreOffice sin interfaz. La usan los
 * documentos que se muestran en el visor de PDF (FORM-DVUS-013, FORM-DVUS-018 y la solicitud de
 * práctica PPS): el PDF queda igual a la plantilla de Word.
 */
class LibreOfficePdfConverter
{
    /**
     * Devuelve la ruta del PDF creado en $directory con el mismo nombre del DOCX. $documento solo
     * nombra el documento en los mensajes de error.
     */
    public function convert(string $source, string $directory, string $documento = 'el documento'): string
    {
        $binary = $this->resolveExecutable(
            (string) config('documents.libreoffice_binary'),
            (array) config('documents.libreoffice_candidates', [])
        );
        if ($binary === null) {
            throw new RuntimeException('LibreOffice no está disponible. Configure LIBREOFFICE_BINARY con la ruta de libreoffice o soffice.');
        }

        $profile = $directory.'/profile-'.bin2hex(random_bytes(8));
        $config = $profile.'/config';
        $cache = $profile.'/cache';
        $runtime = $profile.'/runtime';

        foreach ([$profile, $config, $cache, $runtime] as $path) {
            if (! is_dir($path) && ! mkdir($path, 0700, true) && ! is_dir($path)) {
                throw new RuntimeException('No se pudo preparar el perfil temporal de LibreOffice.');
            }
        }

        // LibreOffice mantiene estado en HOME/XDG incluso cuando recibe
        // UserInstallation. Aislarlos evita que un perfil heredado, bloqueado
        // o dañado interrumpa la conversión de documentos de NEXO.
        $environment = [
            'HOME' => $profile,
            'XDG_CONFIG_HOME' => $config,
            'XDG_CACHE_HOME' => $cache,
            'XDG_RUNTIME_DIR' => $runtime,
        ];

        $process = new Process([
            $binary,
            '-env:UserInstallation=file://'.str_replace('%2F', '/', rawurlencode($profile)),
            '--headless',
            '--convert-to', 'pdf:writer_pdf_Export',
            '--outdir', $directory,
            $source,
        ], $directory, $environment);
        $process->setTimeout(180);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException("LibreOffice no pudo convertir {$documento}: ".trim($process->getErrorOutput() ?: $process->getOutput()));
        }

        $pdf = $directory.'/'.pathinfo($source, PATHINFO_FILENAME).'.pdf';
        if (! $this->isValidPdf($pdf)) {
            throw new RuntimeException("LibreOffice no generó un PDF válido de {$documento}.");
        }

        return $pdf;
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
