<?php

namespace Tests\Unit;

use App\Models\Proyecto\FirmaProyecto;
use App\Models\Proyecto\Proyecto;
use App\Services\Proyecto\ProyectoWorkflowService;
use Tests\TestCase;

class ProyectoRevisionActualTest extends TestCase
{
    public function test_resumen_usa_el_ciclo_actual_y_conserva_la_etapa_rechazada(): void
    {
        $proyecto = new Proyecto(['flujo_aprobacion_id' => 1]);
        $firma = fn (array $attrs) => new FirmaProyecto(array_merge([
            'flujo_aprobacion_id' => 1, 'revision_ciclo' => 1,
            'orden_revision' => 1, 'estado_revision' => 'Pendiente',
        ], $attrs));
        $actual = $firma(['revision_ciclo' => 2, 'orden_revision' => 2, 'estado_revision' => 'Rechazado']);
        $proyecto->setRelation('firma_proyecto', collect([
            $firma([]),
            $firma(['revision_ciclo' => 2, 'estado_revision' => 'Aprobado']),
            $actual,
            $firma(['revision_ciclo' => 2, 'orden_revision' => 3]),
            $firma(['flujo_aprobacion_id' => 9, 'revision_ciclo' => 3]),
        ]));
        $service = app(ProyectoWorkflowService::class);

        $this->assertSame($actual, $service->revisionActualInscripcion($proyecto));
        $actual->estado_revision = 'Aprobado';
        $this->assertSame(3, $service->revisionActualInscripcion($proyecto)->orden_revision);
        $proyecto->firma_proyecto->each(fn ($f) => $f->estado_revision = 'Aprobado');
        $this->assertNull($service->revisionActualInscripcion($proyecto));
    }

    public function test_proyecto_sin_flujo_no_muestra_etapa_configurable(): void
    {
        $this->assertNull(app(ProyectoWorkflowService::class)->revisionActualInscripcion(new Proyecto));
    }
}
