<?php

namespace App\Services\Proyecto;

use App\Services\Documents\LibreOfficePdfConverter;
use Illuminate\Support\Facades\File;
use RuntimeException;

/** Prepara archivos Office de los anexos para mostrarlos de forma uniforme en el visor. */
class ProyectoAnexoPreviewService
{
    public function __construct(private readonly LibreOfficePdfConverter $converter) {}

    public function officeToPdf(string $source): string
    {
        if (! is_file($source)) {
            throw new RuntimeException('No se encontró el archivo del anexo.');
        }

        $directory = storage_path('app/generated/proyectos-anexos');
        File::ensureDirectoryExists($directory);
        $pdf = $directory.'/'.hash_file('sha256', $source).'.pdf';
        $lock = fopen($directory.'/.generation.lock', 'c+');

        if ($lock === false || ! flock($lock, LOCK_EX)) {
            throw new RuntimeException('No se pudo preparar la vista previa del anexo.');
        }

        try {
            if ($this->converter->isValidPdf($pdf)) {
                return $pdf;
            }

            $temporary = storage_path('app/tmp/proyecto-anexo-'.bin2hex(random_bytes(12)));
            File::ensureDirectoryExists($temporary);

            try {
                $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));
                $copy = $temporary.'/anexo.'.$extension;
                if (! copy($source, $copy)) {
                    throw new RuntimeException('No se pudo preparar el archivo del anexo.');
                }

                $generated = $this->converter->convert($copy, $temporary, 'el anexo');
                if (! rename($generated, $pdf)) {
                    throw new RuntimeException('No se pudo guardar la vista previa del anexo.');
                }
            } finally {
                File::deleteDirectory($temporary);
            }

            return $pdf;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
