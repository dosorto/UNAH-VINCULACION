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
        $process->mustRun();
        $pdf = $directory.'/'.pathinfo($source, PATHINFO_FILENAME).'.pdf';
        if (! is_file($pdf) || filesize($pdf) < 100 || file_get_contents($pdf, false, null, 0, 5) !== '%PDF-') {
            throw new RuntimeException('LibreOffice no generó un PDF válido.');
        }
        return $pdf;
    }
}
