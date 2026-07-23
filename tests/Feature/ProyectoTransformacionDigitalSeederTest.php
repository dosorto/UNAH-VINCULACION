<?php

namespace Tests\Feature;

use App\Models\Proyecto\Proyecto;
use App\Models\User;
use App\Services\InformeFinal\InformeFinalProyectoWorkflowService;
use Database\Seeders\ProyectoTransformacionDigitalSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProyectoTransformacionDigitalSeederTest extends TestCase
{
    use DatabaseTransactions;

    #[DataProvider('scenarioProvider')]
    public function test_escenarios_configurables(
        string $scenario,
        string $expectedProjectState,
        ?string $expectedReportState,
        int $expectedDocuments
    ): void {
        Storage::fake('public');
        putenv("NEXO_SEED_SCENARIO={$scenario}");

        try {
            $this->seed(ProyectoTransformacionDigitalSeeder::class);

            $project = Proyecto::query()
                ->where('codigo_proyecto', ProyectoTransformacionDigitalSeeder::PROJECT_CODE)
                ->firstOrFail();

            $this->assertSame($expectedProjectState, $project->estado?->tipoestado?->nombre);
            $this->assertSame($expectedDocuments, $project->documentos()->count());

            $report = $project->informeFinalInf001()->first();
            if ($expectedReportState === null) {
                $this->assertNull($report);
            } else {
                $this->assertNotNull($report);
                $this->assertSame($expectedReportState, $report->estadoFlujo());
            }
        } finally {
            putenv('NEXO_SEED_SCENARIO');
        }
    }

    public function test_seeder_es_idempotente_y_deja_el_proyecto_listo_para_crear_inf001(): void
    {
        Storage::fake('public');
        putenv('NEXO_SEED_SCENARIO=en_curso');

        $original = Proyecto::query()
            ->where('nombre_proyecto', ProyectoTransformacionDigitalSeeder::BASE_PROJECT_NAME)
            ->first();
        $originalAttributes = $original?->getAttributes();

        try {
            $this->seed(ProyectoTransformacionDigitalSeeder::class);

            $project = Proyecto::query()
                ->where('codigo_proyecto', ProyectoTransformacionDigitalSeeder::PROJECT_CODE)
                ->firstOrFail();
            $coordinator = User::query()
                ->where('email', 'coordinador.transformacion@nexo.test')
                ->firstOrFail();

            $this->assertStringEndsWith('[PRUEBA]', $project->nombre_proyecto);
            $this->assertSame(3, $project->docentes_proyecto()->count());
            $this->assertTrue($project->entidad_contraparte()
                ->where('nombre', 'Patronato Pro Mejoramiento de Orocuina')
                ->exists());
            $this->assertTrue($project->gruposEstudiantesPlanificados()
                ->whereHas('asignatura', fn ($query) => $query->where('codigo', 'IS-802'))
                ->where('periodo_academico_id', 'Primer Periodo')
                ->exists());
            $this->assertSame(13, $project->actividades()->count());
            $this->assertSame(6, $project->objetivosEspecificos()->count());
            $this->assertSame('En curso', $project->estado?->tipoestado?->nombre);
            $this->assertSame(
                ProyectoTransformacionDigitalSeeder::FLOW_CODE,
                $project->flujoAprobacion?->codigo
            );

            $normalStageIds = $project
                ->flujoEtapasActivasOrdenadas(Proyecto::FLUJO_INSCRIPCION)
                ->pluck('id');
            $closureStageIds = $project
                ->flujoEtapasActivasOrdenadas(Proyecto::FLUJO_CIERRE_PROYECTO)
                ->pluck('id');
            $normalSignatures = $project->firma_proyecto()
                ->whereIn('flujo_aprobacion_etapa_id', $normalStageIds)
                ->get();

            $this->assertCount($normalStageIds->count(), $normalSignatures);
            $this->assertTrue($normalSignatures->every(
                fn ($signature) => $signature->estado_revision === 'Aprobado'
                    && $signature->firma_id === null
                    && $signature->sello_id === null
            ));
            $this->assertSame(0, $project->firma_proyecto()
                ->whereIn('flujo_aprobacion_etapa_id', $closureStageIds)
                ->count());
            $this->assertTrue($project->puedeMostrarCierreProyecto($coordinator));

            $countsBeforeSecondRun = $this->relatedCounts($project);
            $this->seed(ProyectoTransformacionDigitalSeeder::class);
            $project->refresh();

            $this->assertSame($countsBeforeSecondRun, $this->relatedCounts($project));

            $workflow = app(InformeFinalProyectoWorkflowService::class);
            $this->assertTrue($workflow->puedeIniciarInformeFinal($project, $coordinator));
            $report = $workflow->crearInformeFinal($project, $coordinator);

            $this->assertSame($project->id, $report->proyecto_id);
            $this->assertSame(1, $project->informeFinalInf001()->count());

            $signatureCount = $project->firma_proyecto()->count();
            $documentCount = $project->documentos()->count();
            $this->seed(ProyectoTransformacionDigitalSeeder::class);
            $project->refresh();

            $this->assertSame(1, $project->informeFinalInf001()->count());
            $this->assertSame($signatureCount, $project->firma_proyecto()->count());
            $this->assertSame($documentCount, $project->documentos()->count());
            $this->assertTrue($project->firma_proyecto()->get()->every(
                fn ($signature) => $signature->firma_id === null
                    && $signature->sello_id === null
            ));

            $source = file_get_contents(database_path(
                'seeders/ProyectoTransformacionDigitalSeeder.php'
            ));
            $this->assertIsString($source);
            $this->assertDoesNotMatchRegularExpression(
                '/(?:find|findOrFail)\(\s*\d+\s*\)/',
                $source
            );

            if ($original && $originalAttributes) {
                $this->assertNotSame($original->id, $project->id);
                $this->assertSame(
                    $originalAttributes,
                    $original->fresh()->getAttributes(),
                    'El seeder no debe modificar el proyecto real usado como referencia.'
                );
            }
        } finally {
            putenv('NEXO_SEED_SCENARIO');
        }
    }

    private function relatedCounts(Proyecto $project): array
    {
        return [
            'project' => Proyecto::query()
                ->where('codigo_proyecto', ProyectoTransformacionDigitalSeeder::PROJECT_CODE)
                ->count(),
            'team' => $project->docentes_proyecto()->count(),
            'counterparts' => $project->entidad_contraparte()->count(),
            'student_groups' => $project->gruposEstudiantesPlanificados()->count(),
            'activities' => $project->actividades()->count(),
            'objectives' => $project->objetivosEspecificos()->count(),
            'contributions' => $project->aportesInstitucionales()->count(),
            'annexes' => $project->anexos()->count(),
            'states' => $project->estado_proyecto()->count(),
            'signatures' => $project->firma_proyecto()->count(),
            'documents' => $project->documentos()->count(),
            'reports' => $project->informeFinalInf001()->count(),
        ];
    }

    public static function scenarioProvider(): array
    {
        return [
            'borrador' => ['borrador', 'Borrador', null, 0],
            'revision' => ['revision', 'En revision', null, 0],
            'subsanacion' => ['subsanacion', 'Subsanacion', null, 0],
            'en curso' => ['en_curso', 'En curso', null, 0],
            'INF-001 borrador' => ['inf001_borrador', 'En curso', 'BORRADOR', 0],
            'INF-001 revision' => ['inf001_revision', 'En curso', 'EN_REVISION', 1],
            'finalizado' => ['finalizado', 'Finalizado', 'APROBADO', 1],
        ];
    }
}
