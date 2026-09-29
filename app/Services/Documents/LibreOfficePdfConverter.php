<?php

namespace App\Services\Documents;

use RuntimeException;
use Symfony\Component\Process\Process;

class LibreOfficePdfConverter
{
    public function convert(string $source, string $directory): string
    {
        $binary = collect([config('documents.libreoffice_binary'), ...config('documents.libreoffice_candidates', [])])
            ->first(fn ($path) => is_string($path) && is_executable($path));
        if (! $binary) {
            throw new RuntimeException('LibreOffice no está disponible. Configure LIBREOFFICE_BINARY.');
        }
        $profile = $directory.'/profile-'.bin2hex(random_bytes(8));
        $process = new Process([$binary, '-env:UserInstallation=file://'.str_replace('%2F', '/', rawurlencode($profile)),
            '--headless', '--convert-to', 'pdf:writer_pdf_Export', '--outdir', $directory, $source]);
        $process->setTimeout(180);
        $process->mustRun();
        $pdf = $directory.'/'.pathinfo($source, PATHINFO_FILENAME).'.pdf';
        if (! is_file($pdf) || filesize($pdf) < 100 || file_get_contents($pdf, false, null, 0, 5) !== '%PDF-') {
            throw new RuntimeException('LibreOffice no generó un PDF válido.');
        }
        return $pdf;
    }
}
