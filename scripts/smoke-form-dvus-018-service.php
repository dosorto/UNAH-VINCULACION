<?php

use App\Models\ENF\EnfAccion;
use App\Services\Documents\FormDvus018DataMapper;
use App\Services\FormDvus018DocumentService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

require dirname(__DIR__).'/vendor/autoload.php';

$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$action = new EnfAccion([
    'codigo_formulario' => 'FORM-DVUS-018',
    'nombre_accion' => 'Proyecto de verificación documental',
    'fecha_solicitud' => '2026-08-05',
    'fecha_inicio' => '2026-08-10',
    'fecha_finalizacion' => '2026-12-10',
    'numero_edicion' => 1,
]);
$action->id = (int) ($argv[1] ?? 999999);
$action->created_at = Carbon::parse('2026-08-05');
foreach (['lugaresEjecucion', 'accionCatalogos', 'equipo', 'contrapartes', 'participacionUniversitaria', 'practicasAsignatura', 'objetivosEspecificos', 'resultados', 'ods', 'metasContribuye', 'presupuestos', 'cronograma', 'firmas', 'documentos'] as $relation) {
    $action->setRelation($relation, new Collection);
}
foreach (['beneficiarios', 'centroFacultad', 'departamentoAcademico', 'carrera', 'modalidad'] as $relation) {
    $action->setRelation($relation, null);
}

$service = new FormDvus018DocumentService(new FormDvus018DataMapper);
$pdf = $service->generatePdf($action);

fwrite(STDOUT, "PDF: {$pdf}\nSHA-256: {$service->hash($pdf)}\n");
