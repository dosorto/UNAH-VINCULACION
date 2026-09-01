<?php

namespace Tests\Unit;

use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\VinculacionTipoAccion;
use App\Services\InformeFinal\InformeFinalProyectoWorkflowService;
use Mockery;
use Tests\TestCase;

/**
 * El INF-001 es el formato único de informe final para todos los proyectos de vinculación.
 * `aplicaInformeFinalInf001()` es la puerta por tipo de acción; la disponibilidad real del
 * cierre la sigue gobernando la configuración de etapas (`tieneFlujoCierreProyecto`).
 */
class InformeFinalInf001AplicabilidadTest extends TestCase
{
    private function service(): InformeFinalProyectoWorkflowService
    {
        return new InformeFinalProyectoWorkflowService(
            Mockery::mock(\App\Services\InformeFinal\InformeFinalProyectoInitializer::class),
            Mockery::mock(\App\Services\InformeFinal\InformeFinalProyectoValidator::class),
            Mockery::mock(\App\Services\InformeFinal\InformeFinalPdfGenerator::class),
            Mockery::mock(\App\Services\Proyecto\DocumentoProyectoWorkflowService::class),
        );
    }

    private function proyectoCon(?string $codigo): Proyecto
    {
        $proyecto = new Proyecto;
        $tipo = $codigo === null ? null : tap(new VinculacionTipoAccion, fn ($t) => $t->codigo = $codigo);
        $proyecto->setRelation('tipoAccion', $tipo);

        return $proyecto;
    }

    public function test_aplica_a_desarrollo_local_y_a_voluntariado(): void
    {
        $service = $this->service();

        $this->assertTrue($service->aplicaInformeFinalInf001($this->proyectoCon('DESARROLLO_LOCAL_REGIONAL')));
        $this->assertTrue($service->aplicaInformeFinalInf001($this->proyectoCon('VOLUNTARIADO')));
    }

    public function test_no_aplica_a_otros_tipos_ni_sin_tipo(): void
    {
        $service = $this->service();

        $this->assertFalse($service->aplicaInformeFinalInf001($this->proyectoCon('CULTURA')));
        $this->assertFalse($service->aplicaInformeFinalInf001($this->proyectoCon(null)));
    }

    public function test_la_lista_blanca_esta_declarada(): void
    {
        $this->assertContains('DESARROLLO_LOCAL_REGIONAL', InformeFinalProyectoWorkflowService::TIPOS_ACCION_INF_001);
        $this->assertContains('VOLUNTARIADO', InformeFinalProyectoWorkflowService::TIPOS_ACCION_INF_001);
    }
}
