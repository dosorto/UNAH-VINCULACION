<?php

namespace Tests\Support;

use Illuminate\Support\Facades\File;

/**
 * Reemplaza LibreOffice por un script que deja un PDF mínimo con el nombre del DOCX, como en
 * FormDvus018PdfGenerationTest: las pruebas no dependen de tener LibreOffice instalado.
 */
trait SimulaLibreOffice
{
    protected function simularLibreOffice(): void
    {
        $directorio = sys_get_temp_dir().'/nexo-libreoffice-'.uniqid();
        File::ensureDirectoryExists($directorio);
        $script = $directorio.'/soffice';

        file_put_contents($script, <<<'SH'
#!/bin/sh
out=''
previous=''
last=''
for argument in "$@"; do
    if [ "$previous" = '--outdir' ]; then out="$argument"; fi
    previous="$argument"
    last="$argument"
done
name=$(basename "$last" .docx)
printf '%%PDF-1.7\n%% documento de prueba %0150d\n%%%%EOF\n' 1 > "$out/$name.pdf"
SH);
        chmod($script, 0755);

        config()->set('documents.libreoffice_binary', $script);
        config()->set('documents.libreoffice_candidates', []);
        $this->beforeApplicationDestroyed(fn () => File::deleteDirectory($directorio));
    }
}
