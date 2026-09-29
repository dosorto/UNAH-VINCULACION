<?php

namespace App\Services\PpsServicioSocial;

use App\Models\PpsServicioSocial;
use App\Services\Documents\DocxTemplateEditor;
use App\Services\Documents\FormDvus014DataMapper;
use App\Services\Documents\LibreOfficePdfConverter;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Ficha FORM-DVUS-014 en PDF: llena la plantilla oficial de Word y la convierte con LibreOffice,
 * igual que el FORM-DVUS-018. El PDF se guarda por huella (plantilla, datos y firma) en
 * storage/app/generated/form-dvus-014/{id} y se reutiliza mientras nada de eso cambie.
 */
class FormDvus014DocumentService
{
    public function __construct(
        private readonly FormDvus014DataMapper $mapper,
        private readonly LibreOfficePdfConverter $converter,
    ) {}

    public function generatePdf(PpsServicioSocial $registro): string
    {
        $template = (string) config('documents.form_dvus_014_template');
        if (! is_file($template) || ! is_readable($template)) {
            throw new RuntimeException('No se encontró la plantilla FORM-DVUS-014. Ruta: '.$template);
        }

        $cells = $this->mapper->cells($registro);
        $signatures = $this->mapper->signatures($registro);
        $fingerprint = hash('sha256', implode('|', [
            hash_file('sha256', $template),
            json_encode($cells, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            json_encode(array_map(fn (string $path) => hash_file('sha256', $path), $signatures)),
        ]));

        return $this->cached('form-dvus-014/'.(int) $registro->getKey(), $fingerprint, true, function (string $directory) use ($template, $cells, $signatures): string {
            $docx = $directory.'/FORM-DVUS-014.docx';
            if (! copy($template, $docx)) {
                throw new RuntimeException('No se pudo copiar la plantilla FORM-DVUS-014.');
            }

            $editor = new DocxTemplateEditor($docx);
            foreach ($cells as [$table, $row, $cell, $value, $noWrap]) {
                $editor->setCell($table, $row, $cell, $value, $noWrap);
            }
            foreach ($signatures as $cell => $path) {
                $editor->setCellImage(FormDvus014DataMapper::TABLA_FIRMAS, FormDvus014DataMapper::FILA_FIRMAS, $cell, $path);
            }
            $editor->enforceFixedTables()->save();

            return $this->converter->convert($docx, $directory, 'FORM-DVUS-014');
        });
    }

    /** Los anexos de Word se muestran en el visor convertidos a PDF (el original se descarga igual). */
    public function attachmentPdf(string $path): string
    {
        return $this->cached('pps-servicio-social-anexos', hash_file('sha256', $path), false, function (string $directory) use ($path): string {
            $copy = $directory.'/anexo.'.strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (! copy($path, $copy)) {
                throw new RuntimeException('No se pudo preparar el anexo.');
            }

            return $this->converter->convert($copy, $directory, 'el anexo');
        });
    }

    /**
     * @param  bool  $replacePrevious  borra los PDF anteriores de la carpeta (solo se usa el vigente)
     * @param  callable(string): string  $generate  recibe un directorio temporal y devuelve el PDF creado
     */
    private function cached(string $folder, string $key, bool $replacePrevious, callable $generate): string
    {
        $directory = storage_path('app/generated/'.$folder);
        File::ensureDirectoryExists($directory);
        $pdf = $directory.'/'.$key.'.pdf';

        $lock = fopen($directory.'/.generation.lock', 'c+');
        if ($lock === false || ! flock($lock, LOCK_EX)) {
            throw new RuntimeException('No se pudo iniciar la generación del PDF.');
        }

        try {
            if ($this->converter->isValidPdf($pdf)) {
                return $pdf;
            }

            $temporary = storage_path('app/tmp/form-dvus-014/'.bin2hex(random_bytes(12)));
            File::ensureDirectoryExists($temporary);

            try {
                if (! rename($generate($temporary), $pdf)) {
                    throw new RuntimeException('No se pudo guardar el PDF generado.');
                }
            } finally {
                File::deleteDirectory($temporary);
            }

            if ($replacePrevious) {
                foreach (glob($directory.'/*.pdf') ?: [] as $previous) {
                    if ($previous !== $pdf) {
                        @unlink($previous);
                    }
                }
            }

            return $pdf;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
