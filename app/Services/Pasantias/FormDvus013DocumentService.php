<?php

namespace App\Services\Pasantias;

use App\Models\Pasantia;
use App\Services\Documents\DocxTemplateEditor;
use App\Services\Documents\FormDvus013DataMapper;
use App\Services\Documents\LibreOfficePdfConverter;
use Illuminate\Support\Facades\File;
use RuntimeException;

class FormDvus013DocumentService
{
    public function __construct(private FormDvus013DataMapper $mapper, private LibreOfficePdfConverter $converter) {}

    public function generatePdf(Pasantia $registro): string
    {
        $template = config('documents.form_dvus_013_template', storage_path('app/templates/form-dvus-013.docx'));
        if (! is_readable($template)) throw new RuntimeException('No se encontró la plantilla FORM-DVUS-013.');
        $assets = [];
        foreach (['coordinador', 'supervisor', 'estudiante'] as $role) {
            $asset = \App\Support\Fichas\FirmaImagen::resolver($registro->{'firma_'.$role}, true);
            $assets[$role] = isset($asset['path']) && is_file($asset['path']) ? hash_file('sha256', $asset['path']) : null;
        }
        $key = hash('sha256', 'form013-v1|'.hash_file('sha256', $template).'|'.json_encode([$registro->getAttributes(), $assets]));
        return $this->cached('form-dvus-013/'.$registro->id, $key, function ($dir) use ($registro, $template) {
            $docx = $dir.'/FORM-DVUS-013.docx';
            if (! copy($template, $docx)) throw new RuntimeException('No se pudo copiar la plantilla FORM-DVUS-013.');
            $editor = new DocxTemplateEditor($docx);
            $this->mapper->fill($editor, $registro);
            $editor->save();
            return $this->converter->convert($docx, $dir);
        });
    }

    public function attachmentPdf(string $path): string
    {
        $key = hash_file('sha256', $path);
        return $this->cached('pasantias-adjuntos', $key, function ($dir) use ($path) {
            $copy = $dir.'/adjunto.'.strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (! copy($path, $copy)) throw new RuntimeException('No se pudo preparar el adjunto.');
            return $this->converter->convert($copy, $dir);
        });
    }

    private function cached(string $folder, string $key, callable $generate): string
    {
        $directory = storage_path('app/generated/'.$folder);
        File::ensureDirectoryExists($directory);
        $pdf = $directory.'/'.$key.'.pdf';
        $lock = fopen($directory.'/.generation.lock', 'c+');
        if (! $lock || ! flock($lock, LOCK_EX)) throw new RuntimeException('No se pudo iniciar la generación del PDF.');
        try {
            if (is_file($pdf) && filesize($pdf) > 100 && file_get_contents($pdf, false, null, 0, 5) === '%PDF-') return $pdf;
            $temporary = storage_path('app/tmp/form013-'.bin2hex(random_bytes(12)));
            File::ensureDirectoryExists($temporary);
            try {
                $generated = $generate($temporary);
                if (! rename($generated, $pdf)) throw new RuntimeException('No se pudo guardar el PDF.');
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
