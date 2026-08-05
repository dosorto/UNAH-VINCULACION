<?php

use App\Services\Documents\DocxTemplateEditor;

require dirname(__DIR__).'/vendor/autoload.php';

$output = $argv[1] ?? null;
if (! $output) {
    fwrite(STDERR, "Uso: php scripts/verify-form-dvus-018.php <salida.docx>\n");
    exit(1);
}

$template = dirname(__DIR__).'/storage/app/templates/form-dvus-018.docx';
if (! copy($template, $output)) {
    fwrite(STDERR, "No se pudo copiar la plantilla a {$output}.\n");
    exit(1);
}

$scenario = $argv[2] ?? '--full';
if ($scenario === '--blank') {
    (new DocxTemplateEditor($output))->enforceFixedTables()->save();
    fwrite(STDOUT, $output."\n");
    exit(0);
}

if ($scenario === '--page3-name') {
    (new DocxTemplateEditor($output))->setCell(3, 10, 2, 'María José Hernández')->enforceFixedTables()->save();
    fwrite(STDOUT, $output."\n");
    exit(0);
}

if ($scenario === '--page3-email') {
    (new DocxTemplateEditor($output))->setCell(3, 10, 4, 'maria.hernandez@unah.edu.hn')->enforceFixedTables()->save();
    fwrite(STDOUT, $output."\n");
    exit(0);
}

if ($scenario === '--summary') {
    (new DocxTemplateEditor($output))
        ->setCell(6, 2, 1, 'Resumen dinámico de prueba. El contenido conserva las dimensiones y los saltos definidos por el documento maestro.')
        ->enforceFixedTables()
        ->save();
    fwrite(STDOUT, $output."\n");
    exit(0);
}

(new DocxTemplateEditor($output))
    ->setCell(1, 2, 2, '2026', true)
    ->setCell(1, 2, 3, '08', true)
    ->setCell(1, 2, 4, '05', true)
    ->setCell(1, 3, 2, 'PROYECTO DE EDUCACIÓN CONTINUA CON DATOS DE PRUEBA')
    ->setCell(1, 5, 2, 'X')
    ->setCell(1, 17, 4, '2026', true)
    ->setCell(3, 10, 2, 'María José Hernández')
    ->setCell(3, 10, 4, 'maria.hernandez@unah.edu.hn')
    ->setCell(6, 2, 1, 'Resumen dinámico de prueba. El contenido conserva las dimensiones y los saltos definidos por el documento maestro.')
    ->setCell(9, 4, 1, 'Planificación inicial')
    ->setCell(9, 4, 2, 'Plan aprobado')
    ->setCell(9, 4, 3, '05/08/2026', true)
    ->enforceFixedTables()
    ->save();

fwrite(STDOUT, $output."\n");
